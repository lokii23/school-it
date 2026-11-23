<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\FeedNotification;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FeedController extends Controller
{
    private const TYPES = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

    public function index(Request $request)
    {
        $me     = $request->user();
        $filter = $request->filter === 'mine' ? 'mine' : 'all';
        $isNew  = $request->filled('after');   // the live check asking for posts newer than the ones on screen

        $posts = Post::with('author.section')
            ->withCount('comments')
            ->when($request->filled('post'), fn ($q) => $q->whereKey((int) $request->post))
            ->when($isNew, fn ($q) => $q->where('id', '>', (int) $request->after))
            ->when($filter === 'mine', fn ($q) => $q->where('user_id', $me->id))
            ->orderByRaw('pinned_at is null')      // pinned posts first
            ->orderByDesc('pinned_at')
            ->orderByDesc('id')
            ->paginate($isNew ? 30 : 10)
            ->withQueryString();

        $ids = $posts->pluck('id');

        // post id => [type => count]
        $reactionCounts = PostReaction::whereIn('post_id', $ids)
            ->selectRaw('post_id, type, count(*) as c')
            ->groupBy('post_id', 'type')
            ->get()
            ->groupBy('post_id')
            ->map(fn ($g) => $g->pluck('c', 'type')->all())
            ->all();

        // post id => the current user's reaction
        $myReactions = PostReaction::whereIn('post_id', $ids)
            ->where('user_id', $me->id)
            ->pluck('type', 'post_id')
            ->all();

        $data = compact('posts', 'reactionCounts', 'myReactions');

        // "Load more" and the live check receive just the posts
        if ($request->ajax()) {
            if ($isNew && $posts->isEmpty()) {
                return response('');
            }

            return response(view('feed._posts', $data))
                ->header('X-Next-Page', $posts->nextPageUrl() ?? '');
        }

        return view('feed.index', $data + [
            'filter'        => $filter,
            'single'        => $request->filled('post'),
            'announcements' => Announcement::latest()->take(3)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body'       => ['nullable', 'string', 'max:3000'],
            'image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'mentions'   => ['nullable', 'array', 'max:10'],
            'mentions.*' => ['integer'],
        ], [
            'image.max'   => 'The photo must be 4 MB or smaller.',
            'image.image' => 'Please choose a valid photo.',
            'image.mimes' => 'Only JPG, PNG or WebP photos are allowed.',
        ]);

        $me   = $request->user();
        $body = trim((string) ($data['body'] ?? ''));

        if ($body === '' && ! $request->hasFile('image')) {
            throw ValidationException::withMessages(['body' => 'Write something or add a photo.']);
        }

        $mentions = $this->resolveMentions($body, $data['mentions'] ?? []);

        $post = Post::create([
            'user_id'  => $me->id,
            'body'     => $body !== '' ? $body : null,
            'image'    => $request->hasFile('image') ? $request->file('image')->store('posts', 'public') : null,
            'mentions' => $mentions ?: null,
        ]);

        foreach ($mentions as $m) {
            FeedNotification::sendNotification($m['id'], $me->id, 'mention', $post->id);
        }

        return redirect()->route('feed.index')->with('success', 'Your post is up.');
    }

    // Only the author can edit, and only the text and the tags
    public function update(Request $request, Post $post)
    {
        abort_unless($post->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'body'       => ['nullable', 'string', 'max:3000'],
            'mentions'   => ['nullable', 'array', 'max:10'],
            'mentions.*' => ['integer'],
        ]);

        $body = trim((string) ($data['body'] ?? ''));

        if ($body === '' && ! $post->image) {
            throw ValidationException::withMessages(['body' => 'A post needs some text or a photo.']);
        }

        $mentions = $this->resolveMentions($body, $data['mentions'] ?? []);
        $before   = collect($post->mentions ?? [])->pluck('id')->all();

        if (($post->body ?? '') !== $body || $before !== collect($mentions)->pluck('id')->all()) {
            $post->update([
                'body'      => $body !== '' ? $body : null,
                'mentions'  => $mentions ?: null,
                'edited_at' => now(),
            ]);

            // Only the people tagged by this edit are notified
            foreach ($mentions as $m) {
                if (! in_array($m['id'], $before, true)) {
                    FeedNotification::sendNotification($m['id'], $request->user()->id, 'mention', $post->id);
                }
            }
        }

        return response()->json([
            'body'     => $post->body ?? '',
            'mentions' => $post->mentions ?? [],
            'edited'   => (bool) $post->edited_at,
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();

        abort_unless($post->user_id === $user->id || $user->role === 'admin', 403);

        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        $post->delete();   // its reactions, comments and notifications are deleted with it

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : redirect()->route('feed.index')->with('success', 'Post deleted.');
    }

    public function pin(Request $request, Post $post)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $post->update(['pinned_at' => $post->pinned_at ? null : now()]);

        return response()->json(['pinned' => (bool) $post->pinned_at]);
    }

    // Set, change or remove the current user's reaction. type = null removes it.
    public function react(Request $request, Post $post)
    {
        $data = $request->validate(['type' => ['nullable', Rule::in(self::TYPES)]]);
        $uid  = $request->user()->id;
        $type = $data['type'] ?? null;

        if ($type) {
            PostReaction::updateOrCreate(['post_id' => $post->id, 'user_id' => $uid], ['type' => $type]);
            FeedNotification::reaction($post, $uid, $type);
        } else {
            PostReaction::where('post_id', $post->id)->where('user_id', $uid)->delete();

            FeedNotification::where('post_id', $post->id)->where('actor_id', $uid)->where('type', 'reaction')->delete();
        }

        $counts = PostReaction::where('post_id', $post->id)
            ->selectRaw('type, count(*) as c')
            ->groupBy('type')
            ->pluck('c', 'type')
            ->all();

        return response()->json(['counts' => (object) $counts, 'mine' => $type]);
    }

    // Who reacted
    public function reactions(Post $post)
    {
        $rows = PostReaction::with('user.section')
            ->where('post_id', $post->id)
            ->latest()
            ->limit(300)
            ->get();

        return response()->json([
            'reactions' => $rows->map(fn ($r) => $this->person($r->user) + ['type' => $r->type])->values(),
        ]);
    }

    public function comments(Request $request, Post $post)
    {
        $me = $request->user();

        $rows = PostComment::with('author.section')
            ->where('post_id', $post->id)
            ->orderBy('id')
            ->limit(200)
            ->get();

        return response()->json([
            'comments' => $rows->map(fn ($c) => $this->commentPayload($c, $me, $post))->values(),
        ]);
    }

    public function commentStore(Request $request, Post $post)
    {
        $data = $request->validate([
            'body'       => ['required', 'string', 'max:1000'],
            'mentions'   => ['nullable', 'array', 'max:10'],
            'mentions.*' => ['integer'],
        ]);

        $me       = $request->user();
        $body     = trim($data['body']);
        $mentions = $this->resolveMentions($body, $data['mentions'] ?? []);

        $comment = PostComment::create([
            'post_id'  => $post->id,
            'user_id'  => $me->id,
            'body'     => $body,
            'mentions' => $mentions ?: null,
        ]);

        // Tagged people are told they were tagged
        $tagged = collect($mentions)->pluck('id');

        foreach ($tagged as $id) {
            FeedNotification::sendNotification($id, $me->id, 'mention', $post->id, $comment->id);
        }

        // The post's author is told about the comment, unless they were just tagged in it
        if (! $tagged->contains($post->user_id)) {
            FeedNotification::sendNotification($post->user_id, $me->id, 'comment', $post->id, $comment->id);
        }

        $comment->load('author.section');

        return response()->json(['comment' => $this->commentPayload($comment, $me, $post)], 201);
    }

    // The comment's author, the post's author and the admin can delete a comment
    public function commentDestroy(Request $request, PostComment $comment)
    {
        $user = $request->user();
        $comment->loadMissing('post');

        abort_unless(
            $user->role === 'admin'
                || $comment->user_id === $user->id
                || $comment->post?->user_id === $user->id,
            403
        );

        FeedNotification::where('comment_id', $comment->id)->delete();
        $comment->delete();

        return response()->json(['ok' => true]);
    }

    // Suggestions while typing a tag
    public function people(Request $request)
    {
        $q = trim(str_replace(['%', '_', '\\'], '', (string) $request->query('q', '')));

        if ($q === '') {
            return response()->json(['people' => []]);
        }

        $people = User::with('section')
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('student_number', 'like', "%{$q}%"))
            ->orderByRaw('case when name like ? then 0 else 1 end', ["{$q}%"])
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn ($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'photo'    => $u->photo_url,
                'initials' => $u->initials,
                'sub'      => $u->role . ($u->role === 'student' && ! $u->is_irregular && $u->section ? ' · ' . $u->section->name : ''),
            ]);

        return response()->json(['people' => $people]);
    }

    // ---------- helpers ----------

    // Keeps only real users whose "@Name" is still in the text
    private function resolveMentions(string $body, array $ids): array
    {
        $ids = collect($ids)->map(fn ($i) => (int) $i)->unique()->take(10)->values();

        if ($ids->isEmpty() || $body === '') {
            return [];
        }

        return User::whereIn('id', $ids)->get(['id', 'name'])
            ->filter(fn ($u) => mb_stripos($body, '@' . $u->name) !== false)
            ->map(fn ($u) => ['id' => $u->id, 'label' => $u->name])
            ->values()
            ->all();
    }

    private function person(?User $u): array
    {
        if (! $u) {
            return ['name' => 'Deleted user', 'role' => '', 'photo' => null, 'initials' => '?', 'section' => null];
        }

        return [
            'name'     => $u->name,
            'role'     => $u->role,
            'photo'    => $u->photo_url,
            'initials' => $u->initials,
            'section'  => $u->role === 'student' ? ($u->is_irregular ? 'Irregular' : ($u->section->name ?? null)) : null,
        ];
    }

    private function commentPayload(PostComment $c, User $me, Post $post): array
    {
        return [
            'id'         => $c->id,
            'body'       => $c->body,
            'mentions'   => $c->mentions ?? [],
            'time'       => $c->created_at->diffForHumans(),
            'can_delete' => $me->role === 'admin' || $c->user_id === $me->id || $post->user_id === $me->id,
            'url'        => route('feed.comments.destroy', $c),
            'author'     => $this->person($c->author),
        ];
    }
    
    // The live check: what changed on the posts that are on screen, and whether there are new ones
    public function live(Request $request)
    {
        $me    = $request->user();
        $ids   = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($i) => (int) $i)->filter()->unique()->take(60)->values();
        $after = max(0, (int) $request->query('after', 0));
        $mine  = $request->query('filter') === 'mine';

        $posts = Post::withCount('comments')->whereIn('id', $ids)->get()->keyBy('id');

        $counts = PostReaction::whereIn('post_id', $ids)
            ->selectRaw('post_id, type, count(*) as c')
            ->groupBy('post_id', 'type')
            ->get()
            ->groupBy('post_id')
            ->map(fn ($g) => $g->pluck('c', 'type')->all());

        $mineR = PostReaction::whereIn('post_id', $ids)->where('user_id', $me->id)->pluck('type', 'post_id');

        $out = [];
        foreach ($posts as $id => $p) {
            $out[$id] = [
                'counts'   => (object) ($counts->get($id, [])),
                'mine'     => $mineR[$id] ?? null,
                'comments' => $p->comments_count,
                'pinned'   => (bool) $p->pinned_at,
                'body'     => $p->body ?? '',
                'mentions' => $p->mentions ?? [],
                'edited'   => (bool) $p->edited_at,
            ];
        }

        $new = Post::where('id', '>', $after)->when($mine, fn ($q) => $q->where('user_id', $me->id));

        return response()->json([
            'posts'        => (object) $out,
            'deleted'      => $ids->diff($posts->keys())->values(),
            'new'          => ['count' => (clone $new)->count()],
            'announcement' => (int) Announcement::latest()->value('id'),
        ]);
    }

    // The small announcement card
    public function rail()
    {
        return view('feed._rail', ['announcements' => Announcement::latest()->take(3)->get()]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\FeedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeedNotificationController extends Controller
{
    private const ICONS = ['like' => '👍', 'love' => '❤️', 'haha' => '😂', 'wow' => '😮', 'sad' => '😢', 'angry' => '😡'];

    public function index(Request $request)
    {
        $me = $request->user();

        $rows = FeedNotification::with(['actor', 'post'])
            ->where('user_id', $me->id)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'unread' => $this->unread($me->id),
            'items'  => $rows->map(fn ($n) => $this->payload($n))->values(),
        ]);
    }

    public function count(Request $request)
    {
        return response()->json(['unread' => $this->unread($request->user()->id)]);
    }

    public function readAll(Request $request)
    {
        FeedNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function read(Request $request, FeedNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    // ---------- helpers ----------

    private function unread(int $userId): int
    {
        return FeedNotification::where('user_id', $userId)->whereNull('read_at')->count();
    }

    private function payload(FeedNotification $n): array
    {
        $a        = $n->actor;
        $reaction = $n->data['reaction'] ?? 'like';

        [$icon, $text] = match (true) {
            $n->type === 'reaction'  => [self::ICONS[$reaction] ?? '👍', 'reacted to your post'],
            $n->type === 'comment'   => ['💬', 'commented on your post'],
            $n->comment_id !== null  => ['🏷️', 'tagged you in a comment'],
            default                  => ['🏷️', 'tagged you in a post'],
        };

        $post = $n->post;

        return [
            'id'       => $n->id,
            'actor'    => $a->name ?? 'Someone',
            'photo'    => $a?->photo_url,
            'initials' => $a?->initials ?? '?',
            'icon'     => $icon,
            'text'     => $text,
            'snippet'  => $post ? Str::limit($post->body ?: ($post->image ? 'Photo' : ''), 60) : '',
            'time'     => $n->created_at->diffForHumans(),
            'unread'   => $n->read_at === null,
            'url'      => route('feed.index', ['post' => $n->post_id] + ($n->comment_id ? ['c' => 1] : [])),
            'read_url' => route('notifications.read', $n),
        ];
    }
}
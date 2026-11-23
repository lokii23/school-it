@php
    $me      = auth()->user();
    $a       = $post->author;
    $isOwner = $post->user_id === $me->id;
    $isAdmin = $me->role === 'admin';
    $rb      = ['admin' => 'rb-admin', 'teacher' => 'rb-teacher', 'student' => 'rb-student'];
    $tagged  = collect($post->mentions ?? [])->contains(fn ($m) => ($m['id'] ?? null) === $me->id);

    $cfg = [
        'id'            => $post->id,
        'body'          => $post->body ?? '',
        'mentions'      => $post->mentions ?? [],
        'meId'          => $me->id,
        'tagged'        => $tagged,
        'autoComments'  => ($single ?? false) && request()->boolean('c'),
        'pinned'        => (bool) $post->pinned_at,
        'edited'        => (bool) $post->edited_at,
        'counts'        => (object) ($reactionCounts[$post->id] ?? []),
        'mine'          => $myReactions[$post->id] ?? null,
        'commentsCount' => $post->comments_count,
        'urls'          => [
            'react'     => route('feed.react', $post),
            'reactions' => route('feed.reactions', $post),
            'comments'  => route('feed.comments', $post),
            'update'    => route('feed.update', $post),
            'pin'       => route('feed.pin', $post),
            'destroy'   => route('feed.destroy', $post),
        ],
    ];
@endphp

<article x-data="feedPost({{ Js::from($cfg) }})" x-ref="card" id="post-{{ $post->id }}"
         data-id="{{ $post->id }}" data-pinned="{{ $post->pinned_at ? 1 : 0 }}"
         :class="tagged ? 'fd-tagged' : ''" class="fd-card fd-in">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 p-4 pb-2.5">
        <div class="flex min-w-0 items-center gap-2.5">
            <span class="flex shrink-0 rounded-full p-[2px]" style="background: linear-gradient(135deg, #f6c945, #7a1230)">
                <span class="flex rounded-full p-[2px]" style="background: var(--f-card)">
                    @if ($a)
                        <x-avatar :user="$a" size="h-9 w-9" text="text-xs" />
                    @else
                        <span class="block h-9 w-9 rounded-full bg-slate-300"></span>
                    @endif
                </span>
            </span>

            <div class="min-w-0">
                <p class="fd-text flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm font-bold">
                    {{ $a->name ?? 'Deleted user' }}
                    @if ($a)
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $rb[$a->role] ?? '' }}">
                            {{ $a->role === 'student' && $a->is_irregular ? 'Irregular' : $a->role }}
                        </span>
                    @endif
                </p>
                <p class="fd-muted text-[11px]">
                    @if ($a && $a->role === 'student' && ! $a->is_irregular && $a->section) {{ $a->section->name }} · @endif
                    <span title="{{ $post->created_at->format('M d, Y g:i A') }}">{{ $post->created_at->diffForHumans() }}</span>
                    <span x-show="edited" style="display:none"> · edited</span>
                </p>
            </div>
        </div>

        @if ($isOwner || $isAdmin)
            <div class="relative shrink-0">
                <button type="button" @click="menu = !menu" aria-label="Post options"
                        class="fd-hover fd-muted flex h-8 w-8 items-center justify-center rounded-full text-xl font-bold leading-none transition">⋯</button>

                <div x-show="menu" @click.outside="menu = false" style="display:none"
                     class="fd-pop absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl py-1 text-sm">
                    @if ($isOwner)
                        <button type="button" @click="startEdit()" class="fd-text block w-full px-4 py-2 text-left">✎ Edit post</button>
                    @endif
                    @if ($isAdmin)
                        <button type="button" @click="togglePin()" class="fd-text block w-full px-4 py-2 text-left"
                                x-text="pinned ? '📌 Unpin post' : '📌 Pin to top'"></button>
                    @endif
                    <button type="button" @click="remove()" class="block w-full px-4 py-2 text-left text-[#e0506d]">🗑 Delete post</button>
                </div>
            </div>
        @endif
    </div>

    <div x-show="pinned || tagged" style="display:none" class="mx-4 mb-2 flex flex-wrap gap-2">
        <span x-show="pinned" style="display:none" class="fd-pin inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold">📌 Pinned by the admin</span>
        <span x-show="tagged" style="display:none" class="fd-ribbon inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold">🏷️ You were tagged</span>
    </div>

    {{-- Text, with tagged names highlighted --}}
    <div class="px-4">
        <p x-show="!editing && body" x-html="html(shown, mentions)"
           class="fd-body whitespace-pre-line break-words text-sm leading-relaxed"></p>

        <button type="button" x-show="!editing && !expanded && body.length > 420" @click="expanded = true" style="display:none"
                class="fd-link mt-1 text-sm font-semibold hover:underline">See more</button>

        <div x-show="editing" style="display:none">
            <div class="relative">
                <textarea x-ref="ta_draft" x-model="draft" rows="4" maxlength="3000" class="fd-input"
                          @input="mnInput($event, 'draft')" @keydown="mnKey($event)" @blur="mnClose()"></textarea>
                @include('feed._mention-list', ['field' => 'draft'])
            </div>
            <div class="mt-2 flex justify-end gap-2">
                <button type="button" @click="editing = false" class="fd-tool">Cancel</button>
                <button type="button" @click="saveEdit()" :disabled="busy"
                        class="fd-primary rounded-lg px-5 py-1.5 text-sm font-semibold disabled:opacity-60">Save</button>
            </div>
        </div>
    </div>

    {{-- Photo --}}
    @if ($post->image_url)
        <a href="{{ $post->image_url }}" target="_blank" rel="noopener" class="mx-4 mt-3 block overflow-hidden rounded-xl">
            <img src="{{ $post->image_url }}" alt="Photo in a post by {{ $a->name ?? 'a user' }}" loading="lazy"
                 class="max-h-72 w-full bg-slate-200 object-cover transition duration-300 hover:scale-[1.02]">
        </a>
    @endif

    {{-- Reaction and comment counts --}}
    <div x-show="total > 0 || commentsCount > 0" style="display:none"
         class="fd-muted flex items-center justify-between px-4 pb-2 pt-2.5 text-xs">
        <button type="button" x-show="total > 0" @click="showWho()" style="display:none" class="flex items-center gap-1.5 hover:underline">
            <span class="flex -space-x-1">
                <template x-for="r in top" :key="r.key">
                    <span class="fd-emoji flex h-5 w-5 items-center justify-center rounded-full text-sm" x-text="r.emoji"></span>
                </template>
            </span>
            <span x-text="summary"></span>
        </button>

        <button type="button" x-show="commentsCount > 0" @click="toggleComments()" style="display:none"
                class="ml-auto hover:underline" x-text="commentsCount + (commentsCount === 1 ? ' comment' : ' comments')"></button>
    </div>

    {{-- Like / Comment --}}
    <div class="fd-line mx-4 flex gap-1 border-t py-1">
        <div class="relative flex flex-1" @mouseenter="showPicker()" @mouseleave="hidePicker()">
            <div x-show="picker" x-transition.opacity.duration.150ms style="display:none" @mouseenter="keepPicker()"
                 class="fd-pop absolute bottom-full left-0 z-20 mb-2 flex gap-1 rounded-full px-2.5 py-1.5">
                <template x-for="r in reactions" :key="r.key">
                    <button type="button" @click="react(r.key)" :title="r.label"
                            class="origin-bottom rounded-full text-2xl transition hover:-translate-y-1 hover:scale-125" x-text="r.emoji"></button>
                </template>
            </div>

            <button type="button" @click="quick()" @touchstart.passive="pressStart()" @touchend="pressEnd()" @touchcancel="pressEnd()" @contextmenu.prevent
                    class="fd-act" :class="mine ? meta(mine).color : ''">
                <span class="text-base" :class="!mine && 'opacity-60 grayscale'" x-text="mine ? meta(mine).emoji : '👍'"></span>
                <span x-text="mine ? meta(mine).label : 'Like'"></span>
            </button>
        </div>

        <button type="button" @click="toggleComments()" class="fd-act">
            <span class="text-base">💬</span> Comment
        </button>
    </div>

    {{-- Comments --}}
    <div x-show="commentsOpen" style="display:none" class="fd-soft fd-line rounded-b-[1rem] border-t px-4 py-3.5">
        <p x-show="loading" class="fd-muted text-sm">Loading comments…</p>

        <ul class="space-y-2.5">
            <template x-for="c in comments" :key="c.id">
                <li class="flex gap-2">
                    <template x-if="c.author.photo">
                        <img :src="c.author.photo" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover">
                    </template>
                    <template x-if="!c.author.photo">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#f6c945] text-[10px] font-bold text-[#0a1f55]" x-text="c.author.initials"></span>
                    </template>

                    <div class="min-w-0 flex-1">
                        <div class="fd-bubble inline-block max-w-full px-3 py-1.5">
                            <p class="fd-text text-xs font-bold">
                                <span x-text="c.author.name"></span>
                                <span class="ml-1 rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase" :class="roleClass(c.author.role)" x-text="c.author.role"></span>
                            </p>
                            <p class="fd-body whitespace-pre-line break-words text-[13px]" x-html="html(c.body, c.mentions)"></p>
                        </div>
                        <p class="fd-muted mt-0.5 pl-2 text-[11px]">
                            <span x-text="c.time"></span>
                            <button type="button" x-show="c.can_delete" @click="deleteComment(c)" style="display:none"
                                    class="ml-3 font-semibold text-[#e0506d] hover:underline">Delete</button>
                        </p>
                    </div>
                </li>
            </template>
        </ul>

        <p x-show="loaded && comments.length === 0" style="display:none" class="fd-muted text-sm">No comments yet. Be the first!</p>

        <div class="mt-3 flex items-end gap-2">
            <x-avatar :user="$me" size="h-7 w-7" text="text-[10px]" />

            <div class="relative min-w-0 flex-1">
                <textarea x-ref="ta_newComment" x-model="newComment" rows="1" maxlength="1000"
                          placeholder="Write a comment… use @@ to tag someone"
                          @input="mnInput($event, 'newComment')"
                          @keydown="if (!mnKey($event) && $event.key === 'Enter' && !$event.shiftKey) { $event.preventDefault(); sendComment(); }"
                          @blur="mnClose()"
                          class="fd-input !rounded-2xl !py-2" style="background: var(--f-card); border-color: var(--f-line)"></textarea>
                @include('feed._mention-list', ['field' => 'newComment'])
            </div>

            <button type="button" @click="sendComment()" :disabled="!newComment.trim() || sending"
                    class="fd-primary rounded-xl px-4 py-1.5 text-sm font-semibold disabled:opacity-50">Send</button>
        </div>
    </div>

    {{-- Who reacted --}}
    <div x-show="whoOpen" x-transition.opacity style="display:none" @keydown.escape.window="whoOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#071640]/70 p-4 backdrop-blur-sm">
        <div @click.outside="whoOpen = false" class="fd-card w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="text-lg font-semibold">Reactions</h2>
                <button type="button" @click="whoOpen = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <div class="fd-line flex flex-wrap gap-2 border-b px-5 py-3">
                <button type="button" @click="whoFilter = 'all'" :class="whoFilter === 'all' ? 'on' : ''" class="fd-chip !py-1">
                    All <span x-text="total"></span>
                </button>
                <template x-for="r in present" :key="r.key">
                    <button type="button" @click="whoFilter = r.key" :class="whoFilter === r.key ? 'on' : ''" class="fd-chip !py-1">
                        <span x-text="r.emoji"></span> <span x-text="counts[r.key]"></span>
                    </button>
                </template>
            </div>

            <ul class="max-h-80 overflow-y-auto">
                <li x-show="whoLoading" class="fd-muted px-6 py-8 text-center text-sm">Loading…</li>
                <template x-for="w in whoShown" :key="w.name + w.type + w.initials">
                    <li class="fd-line flex items-center justify-between gap-3 border-t px-6 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <template x-if="w.photo"><img :src="w.photo" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover"></template>
                            <template x-if="!w.photo"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#f6c945] text-xs font-bold text-[#0a1f55]" x-text="w.initials"></span></template>
                            <div class="min-w-0">
                                <p class="fd-text truncate text-sm font-semibold" x-text="w.name"></p>
                                <p class="fd-muted truncate text-xs capitalize" x-text="w.section ? w.role + ' · ' + w.section : w.role"></p>
                            </div>
                        </div>
                        <span class="text-xl" x-text="meta(w.type).emoji"></span>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</article>
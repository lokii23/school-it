@php
    $me  = auth()->user();
    $all = $me->role === 'admin' && Route::has('announcements.index') ? route('announcements.index') : route('dashboard');
@endphp

{{-- 📢 New announcements: one small card --}}
<section class="fd-card overflow-hidden">
    <div class="fd-rail-head">
        <h3 class="fd-text text-[13px] font-bold">📢 New announcements</h3>
        <a href="{{ $all }}" class="fd-link text-[11px] font-semibold hover:underline">View all</a>
    </div>

    @forelse ($announcements as $an)
        <div x-data="{ open: false }" class="fd-line border-t px-3.5 py-2.5 first:border-t-0">
            <div class="flex items-start justify-between gap-2">
                <p class="fd-text text-[13px] font-semibold leading-snug">{{ $an->title }}</p>
                @if ($an->created_at->isToday()) <span class="fd-new shrink-0 rounded-full px-1.5 py-0.5 text-[9px] font-bold">NEW</span> @endif
            </div>
            <p class="fd-body mt-0.5 whitespace-pre-line text-xs leading-relaxed" :class="open ? '' : 'line-clamp-2'">{{ $an->body }}</p>
            <div class="fd-muted mt-1 flex items-center justify-between text-[11px]">
                <span>{{ $an->created_at->diffForHumans() }}</span>
                @if (mb_strlen($an->body) > 90)
                    <button type="button" @click="open = !open" class="fd-link font-semibold hover:underline" x-text="open ? 'Show less' : 'Read more'"></button>
                @endif
            </div>
        </div>
    @empty
        <p class="fd-muted px-4 py-6 text-center text-sm">No announcements yet.</p>
    @endforelse
</section>
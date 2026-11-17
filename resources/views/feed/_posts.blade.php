@forelse ($posts as $post)
    @include('feed._post')
@empty
    @if ($posts->currentPage() === 1)
        @php $f = $filter ?? 'all'; @endphp
        <div class="fd-card flex flex-col items-center justify-center px-6 py-14 text-center">
            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">📰</div>
            <p class="fd-text text-base font-semibold">{{ $f === 'mine' ? "You haven't posted yet." : 'No posts yet.' }}</p>
            <p class="fd-muted mt-1 max-w-sm text-sm">
                {{ $f === 'mine' ? 'Share something with the school above.' : 'Be the first to share something with the school.' }}
            </p>
        </div>
    @endif
@endforelse
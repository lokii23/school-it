@extends('layouts.portal')

@section('title', 'Announcements')
@section('live', '10')

@section('content')
<div
    x-data="{
        open: {{ $errors->any() ? 'true' : 'false' }},
        editId: {{ Js::from(old('_edit_id')) }},
        title: {{ Js::from(old('title', '')) }},
        body: {{ Js::from(old('body', '')) }},
        create() { this.open = true; this.editId = null; this.title = ''; this.body = ''; },
        edit(id, t, b) { this.open = true; this.editId = id; this.title = t; this.body = b; },
    }"
>

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="relative w-full sm:max-w-sm">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search announcements…"
                   class="w-full rounded-xl border-0 bg-white py-2.5 pl-4 pr-20 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]">
            <button class="absolute inset-y-1 right-1 rounded-lg bg-[#0a1f55] px-4 text-xs font-semibold text-white hover:bg-[#12307f]">Search</button>
        </form>

        <button @click="create()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
            <span class="text-lg leading-none">+</span> New announcement
        </button>
    </div>

    {{-- List --}}
    <div class="space-y-4">
        @forelse ($announcements as $a)
            <article class="rounded-2xl border-l-4 border-[#f6c945] bg-white p-6 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-[#0a1f55]">{{ $a->title }}</h3>
                            @if ($a->created_at->isToday())
                                <span class="rounded-full bg-[#f6c945]/30 px-2 py-0.5 text-xs font-semibold text-[#7a5a00]">New</span>
                            @endif
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ Str::limit($a->body, 280) }}</p>
                        <p class="mt-3 text-xs text-slate-400">
                            Posted by <span class="font-medium text-slate-500">{{ $a->author->name ?? 'Unknown' }}</span>
                            · {{ $a->created_at->format('M d, Y g:i A') }}
                            · {{ $a->created_at->diffForHumans() }}
                        </p>
                    </div>

                    <div class="flex shrink-0 gap-2">
                        <button type="button"
                                @click="edit({{ $a->id }}, {{ Js::from($a->title) }}, {{ Js::from($a->body) }})"
                                class="rounded-lg bg-[#0a1f55]/10 px-4 py-2 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">
                            Edit
                        </button>
                        <form method="POST" action="{{ route('announcements.destroy', $a) }}"
                              onsubmit="return confirm('Delete this announcement? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg bg-[#7a1230]/10 px-4 py-2 text-xs font-semibold text-[#7a1230] transition hover:bg-[#7a1230] hover:text-white">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">📢</div>
                <p class="font-semibold text-[#0a1f55]">
                    {{ request('q') ? 'No announcements match your search.' : 'No announcements yet.' }}
                </p>
                <p class="mt-1 text-sm text-slate-400">
                    {{ request('q') ? 'Try a different keyword.' : 'Click “New announcement” to post the first one.' }}
                </p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $announcements->links() }}</div>

    {{-- ============ MODAL (create + edit) ============ --}}
    <div x-show="open" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#071640]/70 p-4"
         @keydown.escape.window="open = false">
        <div @click.outside="open = false" class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="text-lg font-semibold" x-text="editId ? 'Edit announcement' : 'New announcement'"></h2>
                <button @click="open = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST"
                  :action="editId ? '{{ url('announcements') }}/' + editId : '{{ route('announcements.store') }}'"
                  class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!editId">
                <input type="hidden" name="_edit_id" :value="editId">

                <div>
                    <label for="title" class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
                    <input id="title" type="text" name="title" x-model="title" required maxlength="150"
                           placeholder="e.g. Midterm examination schedule"
                           class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('title') ring-[#7a1230] @enderror">
                    @error('title') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="body" class="mb-1.5 block text-sm font-medium text-slate-700">Message</label>
                    <textarea id="body" name="body" x-model="body" rows="6" required maxlength="5000"
                              placeholder="Write your announcement here…"
                              class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('body') ring-[#7a1230] @enderror"></textarea>
                    <div class="mt-1 flex justify-between text-xs">
                        @error('body') <p class="text-[#7a1230]">{{ $message }}</p> @else <span></span> @enderror
                        <span class="text-slate-400" x-text="body.length + ' / 5000'"></span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false"
                            class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit"
                            class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]"
                            x-text="editId ? 'Save changes' : 'Post announcement'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
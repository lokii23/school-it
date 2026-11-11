@extends('layouts.portal')

@section('title', 'Section Management')

@section('content')
@php
    $inputBase = 'w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]';
    $selectSm  = 'rounded-lg border-0 bg-white py-1.5 pl-3 pr-8 text-xs font-medium text-slate-700 shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]';
    $totalStudents = $sections->sum('students_count') + $unassigned->count();
@endphp

<div
    x-data="{
        q: '',
        open: {{ $errors->any() ? 'true' : 'false' }},
        editId: {{ Js::from(old('_edit_id')) }},
        name: {{ Js::from(old('name', '')) }},
        create() { this.open = true; this.editId = null; this.name = ''; },
        rename(id, n) { this.open = true; this.editId = id; this.name = n; },
        match(n) { return !this.q || n.includes(this.q.toLowerCase()); },
    }"
>

    {{-- Summary --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Sections</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $sections->count() }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Students</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $totalStudents }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Unassigned</p>
            <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $unassigned->count() }}</p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <input type="text" x-model="q" placeholder="Filter students by name…"
               class="w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] sm:max-w-sm">

        <button @click="create()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
            <span class="text-lg leading-none">+</span> New section
        </button>
    </div>

    {{-- Unassigned students --}}
    @if ($unassigned->isNotEmpty())
        <div class="mb-6 overflow-hidden rounded-2xl border-l-4 border-[#7a1230] bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="flex items-center justify-between bg-[#7a1230]/5 px-6 py-4">
                <div>
                    <h2 class="font-semibold text-[#7a1230]">Unassigned students</h2>
                    <p class="text-xs text-slate-500">These students can’t see any exams until they have a section.</p>
                </div>
                <span class="rounded-full bg-[#7a1230] px-3 py-1 text-xs font-bold text-white">{{ $unassigned->count() }}</span>
            </div>

            <ul class="divide-y divide-slate-100">
                @foreach ($unassigned as $s)
                    <li class="flex flex-col gap-3 px-6 py-3 sm:flex-row sm:items-center sm:justify-between"
                        x-show="match({{ Js::from(strtolower($s->name)) }})">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#7a1230]/10 text-sm font-bold text-[#7a1230]">{{ strtoupper(substr($s->name, 0, 1)) }}</span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $s->name }}</p>
                                <p class="text-xs text-slate-400">{{ $s->email }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('sections.assign', $s) }}">
                            @csrf @method('PATCH')
                            <select name="section_id" onchange="this.form.submit()" class="{{ $selectSm }}">
                                <option value="">Assign to section…</option>
                                @foreach ($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Section cards --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($sections as $section)
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
                <div class="flex items-start justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $section->name }}</h2>
                        <p class="text-xs text-slate-300">
                            {{ $section->students_count }} {{ Str::plural('student', $section->students_count) }}
                            · {{ $section->exams_count }} {{ Str::plural('exam', $section->exams_count) }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="rename({{ $section->id }}, {{ Js::from($section->name) }})"
                                class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold transition hover:bg-[#f6c945] hover:text-[#0a1f55]">Rename</button>
                        <form method="POST" action="{{ route('sections.destroy', $section) }}"
                              onsubmit="return confirm('Delete section {{ addslashes($section->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg bg-[#7a1230] px-3 py-1.5 text-xs font-semibold transition hover:bg-[#9a1a3f]">Delete</button>
                        </form>
                    </div>
                </div>

                <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                    @forelse ($section->students as $s)
                        <li class="flex flex-col gap-3 px-6 py-3 sm:flex-row sm:items-center sm:justify-between"
                            x-show="match({{ Js::from(strtolower($s->name)) }})">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0a1f55] text-sm font-bold text-[#f6c945]">{{ strtoupper(substr($s->name, 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $s->name }}</p>
                                    <p class="truncate text-xs text-slate-400">{{ $s->email }}</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('sections.assign', $s) }}">
                                @csrf @method('PATCH')
                                <select name="section_id" onchange="this.form.submit()" class="{{ $selectSm }}">
                                    <option value="">Unassign</option>
                                    @foreach ($sections as $sec)
                                        <option value="{{ $sec->id }}" @selected($sec->id === $section->id)>{{ $sec->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </li>
                    @empty
                        <li class="px-6 py-10 text-center text-sm text-slate-400">No students in this section yet.</li>
                    @endforelse
                </ul>
            </div>
        @empty
            <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70 lg:col-span-2">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🏫</div>
                <p class="font-semibold text-[#0a1f55]">No sections yet.</p>
                <p class="mt-1 text-sm text-slate-400">Click “New section” to create the first one.</p>
            </div>
        @endforelse
    </div>

    {{-- ============ MODAL (create + rename) ============ --}}
    <div x-show="open" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#071640]/70 p-4"
         @keydown.escape.window="open = false">
        <div @click.outside="open = false" class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">

            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="text-lg font-semibold" x-text="editId ? 'Rename section' : 'New section'"></h2>
                <button @click="open = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST"
                  :action="editId ? '{{ url('sections') }}/' + editId : '{{ route('sections.store') }}'"
                  class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!editId">
                <input type="hidden" name="_edit_id" :value="editId">

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Section name</label>
                    <input type="text" name="name" x-model="name" required maxlength="50" placeholder="e.g. BSIT 2-A"
                           class="{{ $inputBase }} @error('name') ring-[#7a1230] @enderror">
                    @error('name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="open = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit"
                            class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]"
                            x-text="editId ? 'Save' : 'Create section'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
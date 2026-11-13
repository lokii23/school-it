@extends('layouts.portal')

@section('title', 'Subjects')

@section('content')
@php
    $inputBase  = 'w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]';
    $selectBase = 'rounded-xl border-0 bg-white py-2.5 pl-4 pr-9 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]';
    $allIds     = $sections->pluck('id')->map(fn ($i) => (string) $i)->values();
    $filtered   = request()->hasAny(['q', 'teacher_id']);
@endphp

<div x-data="{
        subjectOpen: {{ $errors->hasAny(['name', 'code']) ? 'true' : 'false' }},
        editId: {{ Js::from(old('_edit_id')) }},
        name: {{ Js::from(old('name', '')) }},
        code: {{ Js::from(old('code', '')) }},

        assignOpen: {{ $errors->hasAny(['teacher_id', 'section_ids']) ? 'true' : 'false' }},
        aId: {{ Js::from(old('_assign_id')) }},
        aName: {{ Js::from(old('_assign_name', '')) }},
        current: {},
        teacher: {{ Js::from((string) old('teacher_id', '')) }},
        picked: {{ Js::from(array_map('strval', (array) old('section_ids', []))) }},

        newSubject() { this.subjectOpen = true; this.editId = null; this.name = ''; this.code = ''; },
        editSubject(s) { this.subjectOpen = true; this.editId = s.id; this.name = s.name; this.code = s.code || ''; },
        openAssign(s) { this.assignOpen = true; this.aId = s.id; this.aName = s.name; this.current = s.current; this.teacher = ''; this.picked = []; },
     }">

    {{-- Summary --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Subjects</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $total }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Teachers</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $teachers->count() }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Subjects with no teacher</p>
            <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $unassigned }}</p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search subject or code…"
                   class="w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] sm:max-w-xs">

            <select name="teacher_id" onchange="this.form.submit()" class="{{ $selectBase }}">
                <option value="">All teachers</option>
                @foreach ($teachers as $t)
                    <option value="{{ $t->id }}" @selected((string) request('teacher_id') === (string) $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>

            <button class="rounded-xl bg-[#0a1f55] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#12307f]">Filter</button>
            @if ($filtered) <a href="{{ route('subjects.index') }}" class="self-center text-sm font-medium text-[#7a1230] hover:underline">Clear</a> @endif
        </form>

        <button @click="newSubject()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
            <span class="text-lg leading-none">+</span> New subject
        </button>
    </div>

    {{-- Subjects --}}
    @if ($subjects->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-20 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#f6c945]/25 text-3xl">📚</div>
            <p class="text-lg font-semibold text-[#0a1f55]">No subjects found.</p>
            <p class="mt-1 max-w-sm text-sm text-slate-400">{{ $filtered ? 'No subject matches your filters.' : 'Create the first subject, then assign a teacher to it.' }}</p>
            @unless ($filtered)
                <button @click="newSubject()" class="mt-6 rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#5a0c22]">+ New subject</button>
            @endunless
        </div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($subjects as $sub)
                @php $rows = $sub->assignments->sortBy(fn ($a) => mb_strtolower($a->section->name ?? ''))->values(); @endphp

                <div class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
                    <div class="flex items-start justify-between gap-3 border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-semibold">{{ $sub->name }}</h3>
                            <p class="text-xs text-slate-300">{{ $sub->code ?: 'No code' }} · {{ $rows->count() }} {{ Str::plural('section', $rows->count()) }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2 text-xs font-semibold">
                            <button type="button"
                                    @click="editSubject({{ Js::from(['id' => $sub->id, 'name' => $sub->name, 'code' => $sub->code]) }})"
                                    class="rounded-lg bg-white/10 px-3 py-1.5 transition hover:bg-[#f6c945] hover:text-[#0a1f55]">Edit</button>
                            <form method="POST" action="{{ route('subjects.destroy', $sub) }}"
                                  onsubmit="return confirm('Delete the subject {{ addslashes($sub->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg bg-[#7a1230] px-3 py-1.5 transition hover:bg-[#9a1a3f]">Delete</button>
                            </form>
                        </div>
                    </div>

                    <ul class="flex-1 divide-y divide-slate-100">
                        @forelse ($rows as $a)
                            <li class="flex items-center justify-between gap-3 px-6 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="shrink-0 rounded-full bg-[#0a1f55]/10 px-3 py-1 text-xs font-semibold text-[#0a1f55]">{{ $a->section->name ?? '—' }}</span>
                                    <div class="flex min-w-0 items-center gap-2">
                                        @if ($a->teacher) <x-avatar :user="$a->teacher" size="h-7 w-7" text="text-[10px]" /> @endif
                                        <span class="truncate text-sm font-medium text-slate-700">{{ $a->teacher->name ?? 'Teacher removed' }}</span>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('subjects.unassign', $a) }}"
                                      onsubmit="return confirm('Remove {{ addslashes($a->teacher->name ?? 'this teacher') }} from {{ addslashes($sub->name) }} in {{ addslashes($a->section->name ?? 'this section') }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-[#7a1230] hover:underline">Remove</button>
                                </form>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-sm text-slate-400">No teacher assigned yet.</li>
                        @endforelse
                    </ul>

                    <div class="border-t border-slate-100 bg-[#fbf8f1] p-4">
                        <button type="button"
                                @click="openAssign({{ Js::from([
                                    'id'      => $sub->id,
                                    'name'    => $sub->name,
                                    'current' => $sub->assignments->mapWithKeys(fn ($a) => [(string) $a->section_id => $a->teacher->name ?? '']),
                                ]) }})"
                                class="w-full rounded-xl bg-[#f6c945] py-2.5 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#fde9a8]">
                            + Assign teacher
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $subjects->links() }}</div>
    @endif

    {{-- ============ MODAL: create / edit subject ============ --}}
    <div x-show="subjectOpen" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#071640]/70 p-4" @keydown.escape.window="subjectOpen = false">
        <div @click.outside="subjectOpen = false" class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="text-lg font-semibold" x-text="editId ? 'Edit subject' : 'New subject'"></h2>
                <button @click="subjectOpen = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST" :action="editId ? '{{ url('subjects') }}/' + editId : '{{ route('subjects.store') }}'" class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!editId">
                <input type="hidden" name="_edit_id" :value="editId">

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject name</label>
                    <input type="text" name="name" x-model="name" required maxlength="100" placeholder="e.g. Programming 1"
                           class="{{ $inputBase }} @error('name') ring-[#7a1230] @enderror">
                    @error('name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject code <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="text" name="code" x-model="code" maxlength="30" placeholder="e.g. CC 101"
                           class="{{ $inputBase }} @error('code') ring-[#7a1230] @enderror">
                    @error('code') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <p x-show="editId" class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-500">Renaming also updates this subject on existing grades and exams.</p>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="subjectOpen = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 hover:bg-[#12307f]"
                            x-text="editId ? 'Save changes' : 'Create subject'"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ MODAL: assign a teacher ============ --}}
    <div x-show="assignOpen" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-[#071640]/70 p-4 sm:items-center" @keydown.escape.window="assignOpen = false">
        <div @click.outside="assignOpen = false" class="my-8 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <div>
                    <h2 class="text-lg font-semibold">Assign teacher</h2>
                    <p class="text-xs text-slate-300" x-text="aName"></p>
                </div>
                <button @click="assignOpen = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST" :action="'{{ url('subjects') }}/' + aId + '/assign'" class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="_assign_id" :value="aId">
                <input type="hidden" name="_assign_name" :value="aName">

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Teacher</label>
                    <select name="teacher_id" x-model="teacher" required class="{{ $inputBase }} @error('teacher_id') ring-[#7a1230] @enderror">
                        <option value="">— Choose teacher —</option>
                        @foreach ($teachers as $t) <option value="{{ $t->id }}">{{ $t->name }}</option> @endforeach
                    </select>
                    @error('teacher_id') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    @if ($teachers->isEmpty())
                        <p class="mt-1.5 text-xs text-[#7a1230]">There are no teacher accounts yet. Add one in Users first.</p>
                    @endif
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-sm font-medium text-slate-700">Sections this teacher handles for this subject</label>
                        <div class="flex gap-3 text-xs font-semibold">
                            <button type="button" @click="picked = {{ Js::from($allIds) }}" class="text-[#0a1f55] hover:underline">All</button>
                            <button type="button" @click="picked = []" class="text-slate-400 hover:underline">None</button>
                        </div>
                    </div>

                    <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                        @forelse ($sections as $s)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl p-3 ring-1 ring-slate-200 transition hover:bg-[#fbf8f1] has-[:checked]:bg-[#f6c945]/20 has-[:checked]:ring-2 has-[:checked]:ring-[#f6c945]">
                                <input type="checkbox" name="section_ids[]" value="{{ $s->id }}" x-model="picked"
                                       class="h-4 w-4 rounded border-slate-300 text-[#7a1230] focus:ring-[#7a1230]">
                                <span class="flex-1 text-sm font-semibold text-[#0a1f55]">{{ $s->name }}</span>
                                <span x-show="current['{{ $s->id }}']" x-text="'Now: ' + current['{{ $s->id }}']"
                                      class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500"></span>
                            </label>
                        @empty
                            <p class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-sm text-slate-500">There are no sections yet. Create them in Section Management.</p>
                        @endforelse
                    </div>
                    @error('section_ids') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    <p class="mt-2 text-xs text-slate-400">A section that already has a teacher for this subject is switched to the new teacher.</p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="assignOpen = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#7a1230] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 hover:bg-[#5a0c22]">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
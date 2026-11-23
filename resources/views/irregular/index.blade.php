@extends('layouts.portal')

@section('title', 'Irregular Students')
@section('live', '15')

@section('content')
@php
    $inputBase  = 'w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]';
    $blank      = collect($offerings)->mapWithKeys(fn ($o) => [(string) $o['id'] => '']);
@endphp

<div x-data="{
        markOpen: false, q: '', pick: null,
        candidates: {{ Js::from($candidates) }},
        get matches() { const s = this.q.trim().toLowerCase(); return this.candidates.filter(c => !s || c.search.includes(s)).slice(0, 8); },

        manageOpen: {{ $errors->has('enroll') ? 'true' : 'false' }}, sid: null, sname: '', sel: {},
        blank: {{ Js::from((object) $blank->all()) }},
        openManage(s) { this.manageOpen = true; this.sid = s.id; this.sname = s.name; this.sel = Object.assign({}, this.blank, s.current); },
        get picked() { return Object.values(this.sel).filter(v => v).length; },

        regularOpen: false, rid: null, rname: '',
        openRegular(s) { this.regularOpen = true; this.rid = s.id; this.rname = s.name; },
     }">

    {{-- Summary --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Irregular students</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $total }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Subjects they can be enrolled in</p>
            <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ count($offerings) }}</p>
        </div>
    </div>

    <p class="mb-6 rounded-xl bg-white px-5 py-3 text-sm text-slate-500 shadow-sm ring-1 ring-slate-200/70">
        An irregular student has no section. They only see the exams, and appear in the grade tables, of the subjects you enroll them in.
        A subject appears here once a teacher is assigned to it for a section.
    </p>

    {{-- Toolbar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="relative w-full sm:max-w-sm">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, email or student ID…"
                   class="w-full rounded-xl border-0 bg-white py-2.5 pl-4 pr-20 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]">
            <button class="absolute inset-y-1 right-1 rounded-lg bg-[#0a1f55] px-4 text-xs font-semibold text-white hover:bg-[#12307f]">Search</button>
        </form>

        <button @click="markOpen = true; q = ''; pick = null"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
            <span class="text-lg leading-none">+</span> Mark a student as irregular
        </button>
    </div>

    {{-- List --}}
    @if ($students->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-20 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#f6c945]/25 text-3xl">🎓</div>
            <p class="text-lg font-semibold text-[#0a1f55]">No irregular students found.</p>
            <p class="mt-1 max-w-sm text-sm text-slate-400">{{ request('q') ? 'No one matches your search.' : 'Mark a student as irregular, then choose the subjects they take.' }}</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-3.5 font-semibold">Student</th>
                            <th class="px-6 py-3.5 font-semibold">Enrolled subjects</th>
                            <th class="px-6 py-3.5 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $s)
                            @php
                                $enr = $s->enrollments->filter(fn ($e) => $e->subject && $e->section)->sortBy(fn ($e) => mb_strtolower($e->subject->name));
                            @endphp
                            <tr class="align-top transition hover:bg-[#fbf8f1]">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$s" size="h-10 w-10" />
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-[#0a1f55]">{{ $s->list_name }}</p>
                                            <p class="truncate text-xs text-slate-400">{{ $s->student_number ? $s->student_number . ' · ' : '' }}{{ $s->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    @forelse ($enr as $e)
                                        <span class="mb-1 mr-1 inline-block rounded-full bg-[#7a1230]/10 px-3 py-1 text-xs font-semibold text-[#7a1230]">
                                            {{ $e->subject->name }} <span class="font-normal text-slate-500">· {{ $e->section->name }}</span>
                                        </span>
                                    @empty
                                        <span class="rounded-full bg-[#f6c945]/30 px-3 py-1 text-xs font-semibold text-[#7a5a00]">No subjects yet</span>
                                    @endforelse
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                                @click="openManage({{ Js::from([
                                                    'id'      => $s->id,
                                                    'name'    => $s->name,
                                                    'current' => (object) $s->enrollments->mapWithKeys(fn ($e) => [(string) $e->subject_id => (string) $e->section_id])->all(),
                                                ]) }})"
                                                class="rounded-lg bg-[#0a1f55]/10 px-3.5 py-1.5 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">Manage subjects</button>
                                        <button type="button" @click="openRegular({{ Js::from(['id' => $s->id, 'name' => $s->name]) }})"
                                                class="rounded-lg bg-[#7a1230]/10 px-3.5 py-1.5 text-xs font-semibold text-[#7a1230] transition hover:bg-[#7a1230] hover:text-white">Make regular</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $students->links() }}</div>
    @endif

    {{-- ============ MODAL: mark a student as irregular ============ --}}
    <div x-show="markOpen" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#071640]/70 p-4" @keydown.escape.window="markOpen = false">
        <div @click.outside="markOpen = false" class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <h2 class="text-lg font-semibold">Mark a student as irregular</h2>
                <button @click="markOpen = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST" action="{{ route('irregular.store') }}" class="space-y-4 p-6">
                @csrf
                <input type="hidden" name="student_id" :value="pick ? pick.id : ''">

                <input type="text" x-model="q" placeholder="Search name, email or student ID…" autocomplete="off" class="{{ $inputBase }}">

                <ul class="max-h-64 space-y-2 overflow-y-auto">
                    <template x-for="c in matches" :key="c.id">
                        <li>
                            <button type="button" @click="pick = c"
                                    :class="pick && pick.id === c.id ? 'bg-[#f6c945]/20 ring-2 ring-[#f6c945]' : 'ring-1 ring-slate-200 hover:bg-[#fbf8f1]'"
                                    class="flex w-full items-center justify-between rounded-xl px-4 py-2.5 text-left transition">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-[#0a1f55]" x-text="c.name"></span>
                                    <span class="block text-xs text-slate-400" x-text="(c.number ? c.number + ' · ' : '') + c.section"></span>
                                </span>
                                <span x-show="pick && pick.id === c.id" class="text-[#7a1230]">✓</span>
                            </button>
                        </li>
                    </template>
                    <li x-show="matches.length === 0" class="rounded-xl bg-[#fbf8f1] px-4 py-6 text-center text-sm text-slate-400">No regular student matches.</li>
                </ul>

                <p class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-500">
                    The student leaves their section and sees nothing until you enroll them in subjects. Their saved grades are kept.
                </p>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="markOpen = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" :disabled="!pick"
                            class="rounded-xl bg-[#7a1230] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22] disabled:opacity-50">Mark as irregular</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ MODAL: manage subjects ============ --}}
    <div x-show="manageOpen" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-[#071640]/70 p-4 sm:items-center" @keydown.escape.window="manageOpen = false">
        <div @click.outside="manageOpen = false" class="my-8 w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <div>
                    <h2 class="text-lg font-semibold">Subjects</h2>
                    <p class="text-xs text-slate-300" x-text="sname"></p>
                </div>
                <button @click="manageOpen = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST" :action="'{{ url('irregular') }}/' + sid + '/subjects'" class="space-y-4 p-6">
                @csrf

                @error('enroll') <p class="rounded-xl bg-[#7a1230]/10 px-4 py-3 text-sm text-[#7a1230]">{{ $message }}</p> @enderror

                @if (count($offerings) === 0)
                    <p class="rounded-xl bg-[#fbf8f1] px-4 py-6 text-center text-sm text-slate-500">
                        No subject has a teacher assigned yet. Assign teachers in Subjects first.
                    </p>
                @else
                    <p class="text-xs text-slate-500">For each subject the student takes, choose the section they attend it in. Leave the rest as “Not taking”.</p>

                    <div class="max-h-[55vh] space-y-2 overflow-y-auto pr-1">
                        @foreach ($offerings as $o)
                            <div class="flex flex-col gap-2 rounded-xl p-3 ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between"
                                 :class="sel['{{ $o['id'] }}'] ? 'bg-[#f6c945]/15 ring-[#f6c945]' : ''">
                                <p class="text-sm font-semibold text-[#0a1f55]">{{ $o['name'] }}</p>
                                <select name="enroll[{{ $o['id'] }}]" x-model="sel['{{ $o['id'] }}']"
                                        class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55] sm:w-64">
                                    <option value="">Not taking</option>
                                    @foreach ($o['sections'] as $sec)
                                        <option value="{{ $sec['id'] }}">{{ $sec['name'] }} · {{ $sec['teacher'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-500">
                    Removing a subject hides it from this student, but grades already entered are kept.
                </p>

                <div class="flex items-center justify-between gap-3 pt-1">
                    <span class="text-sm font-semibold text-[#0a1f55]"><span x-text="picked"></span> selected</span>
                    <div class="flex gap-3">
                        <button type="button" @click="manageOpen = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 hover:bg-[#12307f]">Save subjects</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ MODAL: make regular ============ --}}
    <div x-show="regularOpen" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#071640]/70 p-4" @keydown.escape.window="regularOpen = false">
        <div @click.outside="regularOpen = false" class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <div>
                    <h2 class="text-lg font-semibold">Make a regular student</h2>
                    <p class="text-xs text-slate-300" x-text="rname"></p>
                </div>
                <button @click="regularOpen = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST" :action="'{{ url('irregular') }}/' + rid + '/regular'" class="space-y-4 p-6">
                @csrf

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Section</label>
                    <select name="section_id" class="{{ $inputBase }}">
                        <option value="">— No section yet —</option>
                        @foreach ($sections as $sec) <option value="{{ $sec->id }}">{{ $sec->name }}</option> @endforeach
                    </select>
                </div>

                <p class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-500">
                    The student will take every subject of that section. Their subject-by-subject enrollments are removed. Grades are kept.
                </p>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="regularOpen = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#7a1230] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 hover:bg-[#5a0c22]">Make regular</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
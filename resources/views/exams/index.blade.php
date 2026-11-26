@extends('layouts.portal')

@section('title', auth()->user()->role === 'admin' ? 'Exams & Quizzes' : 'Create Exams')
@section('live', '10')

@section('content')
<div x-data="{
        cOpen: {{ old('_form') === 'copy' ? 'true' : 'false' }},
        c: { id: {{ Js::from(old('_edit_id')) }}, name: '', questions: 0, closes: false, type: 'exam' },
        title: {{ Js::from(old('title', '')) }},
        section: {{ Js::from((string) old('section_id', '')) }},
        subject: {{ Js::from(old('subject', '')) }},
        keepKey: false, passkey: {{ Js::from(old('passkey', '')) }}, keepTimes: false,
        map: {{ Js::from($copyMap) }},
        get subjects() { const l = [...(this.map[this.section] || [])]; if (this.subject && !l.includes(this.subject)) l.push(this.subject); return l; },
        openCopy(e) {
            this.c = e; this.title = e.title; this.section = e.section_id; this.subject = e.subject;
            this.keepKey = false; this.passkey = ''; this.keepTimes = false; this.cOpen = true;
        },
     }">
@php
    $isAdmin = auth()->user()->role === 'admin';
    $filtered = request()->hasAny(['q', 'type', 'section_id']);

    $typeStyle = [
        'exam' => 'bg-[#0a1f55]/10 text-[#0a1f55]',
        'quiz' => 'bg-[#f6c945]/30 text-[#7a5a00]',
    ];
    $stateStyle = [
        'open'     => 'bg-emerald-100 text-emerald-700',
        'upcoming' => 'bg-[#f6c945]/30 text-[#7a5a00]',
        'closed'   => 'bg-slate-100 text-slate-500',
    ];
    $selectBase = 'rounded-xl border-0 bg-white py-2.5 pl-4 pr-9 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]';
@endphp

{{-- Toolbar --}}
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search title or subject…"
               class="w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] sm:max-w-xs">

        <select name="type" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">All types</option>
            <option value="exam" @selected(request('type') === 'exam')>Exams</option>
            <option value="quiz" @selected(request('type') === 'quiz')>Quizzes</option>
        </select>

        <select name="section_id" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">All sections</option>
            @foreach ($sections as $s)
                <option value="{{ $s->id }}" @selected((string) request('section_id') === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>

        <button class="rounded-xl bg-[#0a1f55] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#12307f]">Filter</button>

        @if ($filtered)
            <a href="{{ route('exams.index') }}" class="self-center text-sm font-medium text-[#7a1230] hover:underline">Clear</a>
        @endif
    </form>

    <a href="{{ route('exams.create') }}"
       class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
        <span class="text-lg leading-none">+</span> New exam
    </a>
</div>

@if ($exams->isEmpty())

    {{-- Empty state: always centered on the page --}}
    <div class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-20 text-center shadow-sm ring-1 ring-slate-200/70">
        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#f6c945]/25 text-3xl">📝</div>

        <p class="text-lg font-semibold text-[#0a1f55]">No exams found.</p>

        <p class="mt-1 max-w-sm text-sm text-slate-400">
            {{ $filtered
                ? 'No exam matches your filters. Try clearing them.'
                : 'Click “New exam” to create your first one.' }}
        </p>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            @if ($filtered)
                <a href="{{ route('exams.index') }}"
                   class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Clear filters</a>
            @endif
            <a href="{{ route('exams.create') }}"
               class="rounded-xl bg-[#7a1230] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">+ New exam</a>
        </div>
    </div>

@else

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">Title</th>
                        <th class="px-6 py-3.5 font-semibold">Type</th>
                        <th class="px-6 py-3.5 font-semibold">Section</th>
                        <th class="px-6 py-3.5 font-semibold">Passkey</th>
                        <th class="px-6 py-3.5 font-semibold">Schedule</th>
                        <th class="px-6 py-3.5 text-center font-semibold">Questions</th>
                        <th class="px-6 py-3.5 text-center font-semibold">Taken</th>
                        @if ($isAdmin) <th class="px-6 py-3.5 font-semibold">Created by</th> @endif
                        <th class="px-6 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach ($exams as $e)
                        <tr class="transition hover:bg-[#fbf8f1]">

                            <td class="px-6 py-4">
                                <p class="font-semibold text-[#0a1f55]">{{ $e->title }}</p>
                                <p class="text-xs text-slate-400">{{ $e->subject }} · {{ $e->created_at->format('M d, Y') }}</p>
                            </td>

                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $typeStyle[$e->type] ?? 'bg-slate-100 text-slate-600' }}">{{ $e->type }}</span>
                            </td>

                            <td class="px-6 py-4 text-slate-600">{{ $e->section->name ?? '—' }}</td>

                            {{-- Passkey (click to reveal) --}}
                            <td class="px-6 py-4" x-data="{ show: false }">
                                @if ($e->passkey)
                                    <button type="button" @click="show = !show"
                                            class="rounded-lg bg-slate-100 px-3 py-1 font-mono text-xs font-semibold tracking-widest text-[#0a1f55] hover:bg-[#f6c945]/30">
                                        <span x-show="show">{{ $e->passkey }}</span>
                                        <span x-show="!show">••••••</span>
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">None</span>
                                @endif
                            </td>

                            {{-- Schedule --}}
                            <td class="px-6 py-4 text-xs">
                                @php $st = $e->status; @endphp
                                <span class="rounded-full px-2.5 py-0.5 font-semibold {{ $stateStyle[$st] }}">{{ ucfirst($st) }}</span>
                                <p class="mt-1 text-slate-500">{{ $e->duration_minutes ? $e->duration_minutes . ' min' : 'No time limit' }}</p>
                                @if ($e->opens_at)  <p class="text-slate-400">Opens {{ $e->opens_at->format('M d, g:i A') }}</p> @endif
                                @if ($e->closes_at) <p class="text-slate-400">Closes {{ $e->closes_at->format('M d, g:i A') }}</p> @endif
                                @if ($e->score_mode === 'after_close')
                                    <p class="mt-1 font-semibold text-[#7a1230]">🔒 Scores hidden until close</p>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center font-medium text-slate-700">{{ $e->questions_count }}</td>
                            <td class="px-6 py-4 text-center font-medium text-slate-700">{{ $e->results_count }}</td>

                            @if ($isAdmin)
                                <td class="px-6 py-4 text-slate-500">{{ $e->teacher->name ?? '—' }}</td>
                            @endif

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('exams.edit', $e) }}"
                                    class="rounded-lg bg-[#0a1f55]/10 px-3.5 py-1.5 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">Edit</a>

                                    <button type="button"
                                            @click="openCopy({{ Js::from([
                                                'id'         => $e->id,
                                                'title'      => 'Copy of ' . $e->title,
                                                'name'       => $e->title,
                                                'section_id' => (string) $e->section_id,
                                                'subject'    => $e->subject,
                                                'questions'  => $e->questions_count,
                                                'closes'     => (bool) $e->closes_at,
                                                'type'       => $e->type,
                                            ]) }})"
                                            class="rounded-lg bg-[#f6c945]/40 px-3.5 py-1.5 text-xs font-semibold text-[#7a5a00] transition hover:bg-[#f6c945]">Copy</button>

                                    <form method="POST" action="{{ route('exams.destroy', $e) }}"
                                        data-confirm="Delete “{{ $e->title }}”? {{ $e->results_count ? 'This will also delete ' . $e->results_count . ' student result(s). ' : '' }}This cannot be undone.">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg bg-[#7a1230]/10 px-3.5 py-1.5 text-xs font-semibold text-[#7a1230] transition hover:bg-[#7a1230] hover:text-white">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $exams->links() }}</div>

@endif

{{-- ============ MODAL: copy this exam ============ --}}
<div x-show="cOpen" x-transition.opacity style="display:none"
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-[#071640]/70 p-4 sm:items-center" @keydown.escape.window="cOpen = false">
    <div @click.outside="cOpen = false" class="my-8 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold">Copy <span x-text="c.type"></span></h2>
                <p class="truncate text-xs text-slate-300"><span x-text="c.name"></span> · <span x-text="c.questions"></span> questions</p>
            </div>
            <button type="button" @click="cOpen = false" class="text-white/70 hover:text-white">✕</button>
        </div>

        <form method="POST" :action="'{{ url('exams') }}/' + c.id + '/duplicate'" class="space-y-5 p-6">
            @csrf
            <input type="hidden" name="_form" value="copy">
            <input type="hidden" name="_edit_id" :value="c.id">
            <input type="hidden" name="keep_times" :value="keepTimes ? 1 : 0">
            <input type="hidden" name="passkey" :value="keepKey ? '' : passkey" :disabled="keepKey">

            @if (old('_form') === 'copy' && $errors->any())
                <div class="rounded-xl border-l-4 border-[#7a1230] bg-[#7a1230]/5 px-4 py-3 text-sm text-[#7a1230]">
                    {{ collect($errors->all())->unique()->first() }}
                </div>
            @endif

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Title of the copy</label>
                <input type="text" name="title" x-model="title" required maxlength="150"
                       class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Section</label>
                    <select name="section_id" x-model="section" @change="subject = ''" required
                            class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]">
                        <option value="">— Choose section —</option>
                        @foreach ($sections as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject</label>
                    <select name="subject" x-model="subject" required :disabled="!section"
                            class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55] disabled:bg-slate-100">
                        <option value="">— Choose subject —</option>
                        <template x-for="s in subjects" :key="s"><option :value="s" x-text="s" :selected="s === subject"></option></template>
                    </select>
                </div>
            </div>

            <div class="rounded-xl bg-[#fbf8f1] p-4">
                <p class="mb-2 text-sm font-semibold text-[#0a1f55]">Passkey</p>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                    <input type="radio" :value="false" x-model="keepKey" class="h-4 w-4 border-slate-300 text-[#7a1230] focus:ring-[#7a1230]"> Make a new random one
                </label>
                <label class="mt-1.5 flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                    <input type="radio" :value="true" x-model="keepKey" @change="passkey = ''" class="h-4 w-4 border-slate-300 text-[#7a1230] focus:ring-[#7a1230]"> Type my own
                </label>
                <input type="text" x-show="keepKey" x-model="passkey" maxlength="20" placeholder="At least 4 characters" style="display:none"
                       class="mt-2 w-full rounded-xl border-0 bg-white px-4 py-2.5 font-mono uppercase tracking-widest shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]">
            </div>

            <label class="flex cursor-pointer items-start gap-3 text-sm text-slate-700">
                <input type="checkbox" x-model="keepTimes" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#7a1230] focus:ring-[#7a1230]">
                <span>Keep the open and close times
                    <span class="block text-xs text-slate-400">Leave this off if the copy is for a different date. You can set the times when you review it.</span></span>
            </label>

            <p class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-500">
                Copies the questions, correct answers, time limit and review and score settings. Students' results are <strong>not</strong> copied. The copy is open to the section right away unless you set times, so review it first.
            </p>

            <div class="flex justify-end gap-3 pt-1">
                <button type="button" @click="cOpen = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" :disabled="!title.trim() || !section || !subject || (keepKey && passkey.trim().length < 4)"
                        class="rounded-xl bg-[#7a1230] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22] disabled:opacity-50">Make copy</button>
            </div>
        </form>
    </div>
</div>
</div>


@endsection
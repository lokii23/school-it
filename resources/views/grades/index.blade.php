@extends('layouts.portal')

@section('title', auth()->user()->role === 'admin' ? 'Grades' : 'Grade Management')

@section('content')
@php
    $inputBase = 'w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]';
    $hasSheet  = $section && $subject;
    $fmt       = fn ($n) => \App\Support\Grading::fmt($n);
@endphp

{{-- ============ Picker ============ --}}
<form method="GET" action="{{ route('grades.index') }}"
      class="mb-6 rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
    <div class="grid gap-4 md:grid-cols-[1fr_1.4fr_auto] md:items-end">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Section</label>
            <select name="section_id" required class="{{ $inputBase }}">
                <option value="">— Choose section —</option>
                @foreach ($sections as $s)
                    <option value="{{ $s->id }}" @selected($section?->id === $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject</label>
            <input type="text" name="subject" value="{{ $subject }}" list="subject-hints" required maxlength="100"
                   placeholder="Pick or type a subject, e.g. Programming 1" autocomplete="off" class="{{ $inputBase }}">
            <datalist id="subject-hints">
                @foreach ($subjectHints as $h) <option value="{{ $h }}"> @endforeach
            </datalist>
        </div>
        <button class="rounded-xl bg-[#0a1f55] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">
            Open grade sheet
        </button>
    </div>
</form>

@if (! $hasSheet)

    {{-- ============ No sheet open yet ============ --}}
    @if ($section)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-6 py-4 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-600">
                Section <strong class="text-[#0a1f55]">{{ $section->name }}</strong> selected. Now pick or type a subject above.
            </p>
            <a href="{{ route('grades.reports', ['section_id' => $section->id]) }}" target="_blank"
               class="rounded-xl bg-[#f6c945] px-4 py-2 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#fde9a8]">
                🖨 Print all report cards for {{ $section->name }}
            </a>
        </div>
    @endif

    <h2 class="mb-3 text-lg font-semibold text-[#0a1f55]">Recent grade sheets</h2>

    @if ($recent->isEmpty())
        <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🎓</div>
            <p class="font-semibold text-[#0a1f55]">No grade sheets yet.</p>
            <p class="mt-1 text-sm text-slate-400">Choose a section and a subject above to start entering grades.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($recent as $r)
                @php $sec = $sections->firstWhere('id', $r->section_id); @endphp
                @continue(! $sec)
                <a href="{{ route('grades.index', ['section_id' => $r->section_id, 'subject' => $r->subject]) }}"
                   class="group rounded-2xl border-l-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-1 hover:shadow-lg">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#7a1230]">{{ $sec->name }}</p>
                    <p class="mt-1 text-lg font-semibold text-[#0a1f55]">{{ $r->subject }}</p>
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ $r->students }} {{ Str::plural('student', $r->students) }} graded</span>
                        @if ($r->locked_count > 0)
                            <span class="rounded-full bg-[#0a1f55]/10 px-2.5 py-0.5 font-semibold text-[#0a1f55]">🔒 Finalized</span>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-slate-400">Updated {{ \Illuminate\Support\Carbon::parse($r->updated)->diffForHumans() }}</p>
                </a>
            @endforeach
        </div>
    @endif

@else

    {{-- ============ THE SHEET ============ --}}
    <div
        x-data="{
            rows: {{ Js::from((object) $rows) }},
            editable: {{ Js::from((object) $editable) }},
            examScores: {{ Js::from((object) $examScores) }},
            keys: {{ Js::from(array_keys($periods)) }},
            passing: {{ $passing }},
            dec: {{ $decimals }},
            hadErrors: {{ $errors->any() ? 'true' : 'false' }},
            exam: '', period: '', onlyEmpty: true, importMsg: '',
            snapshot: '', saving: false,

            init() { this.snapshot = this.hadErrors ? '' : JSON.stringify(this.rows); },
            get dirty() { return JSON.stringify(this.rows) !== this.snapshot; },

            num(v) { const n = parseFloat(v); return isNaN(n) ? null : n; },
            round(n) { const f = Math.pow(10, this.dec); return Math.round(n * f) / f; },
            avg(r) {
                const v = this.keys.map(k => this.num(r[k])).filter(n => n !== null);
                return v.length ? this.round(v.reduce((a, b) => a + b, 0) / v.length) : null;
            },
            complete(r) { return this.keys.every(k => this.num(r[k]) !== null); },
            passes(a) { return a !== null && a <= this.passing + 0.000001; },
            fmt(a) { return a === null ? '—' : a.toFixed(2); },
            status(r) { const a = this.avg(r); if (a === null) return 'empty'; if (!this.complete(r)) return 'progress'; return this.passes(a) ? 'passed' : 'failed'; },
            label(s) { return { passed: 'Passed', failed: 'Failed', progress: 'In progress', empty: '—' }[s]; },
            badge(s) { return { passed: 'bg-emerald-100 text-emerald-700', failed: 'bg-[#7a1230]/10 text-[#7a1230]', progress: 'bg-[#f6c945]/30 text-[#7a5a00]', empty: 'text-slate-300' }[s]; },
            tone(a) { return a === null ? 'text-slate-300' : (this.passes(a) ? 'text-emerald-600' : 'text-[#7a1230]'); },

            get stats() {
                let sum = 0, n = 0, passed = 0, failed = 0, progress = 0;
                for (const id in this.rows) {
                    const r = this.rows[id], a = this.avg(r), s = this.status(r);
                    if (s === 'passed') passed++; else if (s === 'failed') failed++; else if (s === 'progress') progress++;
                    if (a !== null) { sum += a; n++; }
                }
                return { avg: n ? this.round(sum / n) : null, passed, failed, progress };
            },

            clearRow(id) { this.keys.forEach(k => this.rows[id][k] = ''); this.rows[id].remarks = ''; },

            // Enter / arrow keys move through the same column, like a spreadsheet
            nav(e, col) {
                if (!['Enter', 'ArrowDown', 'ArrowUp'].includes(e.key)) return;
                e.preventDefault();
                const list = [...e.target.closest('table').querySelectorAll('input[data-col=' + col + ']:not(:disabled)')];
                const i = list.indexOf(e.target);
                const next = list[e.key === 'ArrowUp' ? i - 1 : i + 1];
                if (next) { next.focus(); next.select(); }
            },

            importExam() {
                const src = this.examScores[this.exam];
                if (!src || !this.period) { this.importMsg = 'Choose an exam and a grading period first.'; return; }
                let filled = 0, missing = 0;
                for (const id in this.rows) {
                    if (!this.editable[id]) continue;
                    const v = src.scores[id];
                    if (v === undefined) { missing++; continue; }
                    const cur = this.rows[id][this.period];
                    if (this.onlyEmpty && cur !== '' && cur !== null && cur !== undefined) continue;
                    this.rows[id][this.period] = v; filled++;
                }
                this.importMsg = 'Filled ' + filled + ' cell(s) with grades converted from the exam scores' + (missing ? ' (' + missing + ' student(s) have no result)' : '') + '. Review them, then press Save grades.';
            },
        }"
        @beforeunload.window="if (dirty && !saving) { $event.preventDefault(); $event.returnValue = ''; }"
        class="space-y-6">

        {{-- Header --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-6 text-white shadow-lg">
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#f6c945]/20 blur-2xl"></div>
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#f6c945]">Grade sheet</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ $subject }}</h2>
                    <p class="text-slate-300">{{ $section->name }} · {{ $students->count() }} {{ Str::plural('student', $students->count()) }}</p>

                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-white/10 px-3 py-1">{{ $fmt($best) }} highest · {{ $fmt($passing) }} lowest passing · {{ $fmt($worst) }} last grade</span>
                        @if ($lockedCount > 0)
                            <span class="rounded-full bg-[#f6c945] px-3 py-1 font-bold text-[#0a1f55]">🔒 {{ $lockedCount }} of {{ $gradedCount }} finalized</span>
                        @endif
                        <span class="rounded-full bg-white/10 px-3 py-1" x-show="dirty" x-cloak>● Unsaved changes</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                    <a href="{{ route('grades.export', ['section_id' => $section->id, 'subject' => $subject]) }}"
                       class="rounded-xl bg-white/10 px-4 py-2 ring-1 ring-white/20 transition hover:bg-[#f6c945] hover:text-[#0a1f55]">⬇ Export CSV</a>
                    <a href="{{ route('grades.print', ['section_id' => $section->id, 'subject' => $subject]) }}" target="_blank"
                       class="rounded-xl bg-white/10 px-4 py-2 ring-1 ring-white/20 transition hover:bg-[#f6c945] hover:text-[#0a1f55]">🖨 Print class record</a>
                    <a href="{{ route('grades.reports', ['section_id' => $section->id]) }}" target="_blank"
                       class="rounded-xl bg-white/10 px-4 py-2 ring-1 ring-white/20 transition hover:bg-[#f6c945] hover:text-[#0a1f55]">🧾 Report cards</a>

                    @if ($isAdmin)
                        <form method="POST" action="{{ route('grades.lock') }}" class="flex gap-2">
                            @csrf
                            <input type="hidden" name="section_id" value="{{ $section->id }}">
                            <input type="hidden" name="subject" value="{{ $subject }}">
                            @if ($gradedCount > $lockedCount)
                                <button name="action" value="lock"
                                        onclick="return confirm('Finalize these grades? Teachers will not be able to edit them until you unlock.')"
                                        class="rounded-xl bg-[#f6c945] px-4 py-2 text-[#0a1f55] transition hover:bg-[#fde9a8]">🔒 Finalize</button>
                            @endif
                            @if ($lockedCount > 0)
                                <button name="action" value="unlock"
                                        onclick="return confirm('Unlock these grades so they can be edited again?')"
                                        class="rounded-xl bg-[#7a1230] px-4 py-2 transition hover:bg-[#9a1a3f]">🔓 Unlock</button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if ($students->isEmpty())
            <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🏫</div>
                <p class="font-semibold text-[#0a1f55]">{{ $section->name }} has no students yet.</p>
                <p class="mt-1 text-sm text-slate-400">Assign students to this section first.</p>
                @if (Route::has('sections.index'))
                    <a href="{{ route('sections.index') }}" class="mt-4 inline-block rounded-xl bg-[#0a1f55] px-5 py-2 text-sm font-semibold text-white hover:bg-[#12307f]">Go to Section Management</a>
                @endif
            </div>
        @else

            {{-- Live class statistics --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">Class average <span class="text-xs text-slate-400">(lower is better)</span></p>
                    <p class="mt-1 text-3xl font-bold text-[#0a1f55]" x-text="fmt(stats.avg)"></p>
                </div>
                <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">Passed <span class="text-xs text-slate-400">(all periods done)</span></p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600" x-text="stats.passed"></p>
                </div>
                <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">Failed</p>
                    <p class="mt-1 text-3xl font-bold text-[#7a1230]" x-text="stats.failed"></p>
                </div>
                <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">In progress</p>
                    <p class="mt-1 text-3xl font-bold text-[#0a1f55]" x-text="stats.progress"></p>
                </div>
            </div>

            {{-- Import from exams --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <h3 class="font-semibold text-[#0a1f55]">Use exam scores as grades</h3>

                @if (count($examScores))
                    <p class="mt-1 text-xs text-slate-400">Pick an exam or quiz and a period. Each student's exam percentage is converted to a grade using the table below, then filled in. Nothing is saved until you press <strong>Save grades</strong>.</p>

                    <div class="mt-4 grid gap-3 md:grid-cols-[1.4fr_1fr_auto_auto] md:items-center">
                        <select x-model="exam" class="rounded-xl border-0 bg-white py-2.5 pl-4 pr-9 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]">
                            <option value="">— Choose exam or quiz —</option>
                            @foreach ($examScores as $id => $e)
                                <option value="{{ $id }}">{{ $e['title'] }} · {{ count($e['scores']) }} results</option>
                            @endforeach
                        </select>

                        <select x-model="period" class="rounded-xl border-0 bg-white py-2.5 pl-4 pr-9 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]">
                            <option value="">— Into which period? —</option>
                            @foreach ($periods as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                        </select>

                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" x-model="onlyEmpty" class="rounded border-slate-300 text-[#0a1f55] focus:ring-[#0a1f55]">
                            Only empty cells
                        </label>

                        <button type="button" @click="importExam()"
                                class="rounded-xl bg-[#f6c945] px-5 py-2.5 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#fde9a8]">Fill from exam</button>
                    </div>

                    <p x-show="importMsg" x-text="importMsg" x-cloak class="mt-3 rounded-lg bg-[#fbf8f1] px-4 py-2 text-xs text-slate-600"></p>
                @else
                    <p class="mt-1 text-sm text-slate-400">No exam results for this section yet. This becomes available once students have taken one of your exams.</p>
                @endif
            </div>

            {{-- Grading scale reference --}}
            <details class="rounded-2xl bg-white px-5 py-3 shadow-sm ring-1 ring-slate-200/70">
                <summary class="cursor-pointer text-sm font-semibold text-[#0a1f55]">Grading scale and rules</summary>
                <div class="mt-3 space-y-3 text-xs">
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700">{{ $fmt($best) }} = highest</span>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700">{{ $fmt($best) }} to {{ $fmt($passing) }} = Passed</span>
                        <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 font-medium text-[#7a1230]">{{ number_format($passing + 0.01, 2) }} to {{ $fmt($worst) }} = Failed</span>
                        <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 font-medium text-[#7a1230]">{{ $fmt($worst) }} = last grade</span>
                    </div>
                    <p class="text-slate-500">
                        Average = the mean of the periods entered so far, to {{ $decimals }} decimals. A <strong>Passed</strong> or <strong>Failed</strong>
                        status appears once all four periods have a grade.
                    </p>
                    <div>
                        <p class="mb-1.5 font-semibold text-[#0a1f55]">Exam percentage → grade (used by “Fill from exam”)</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($transmute as $i => [$min, $g])
                                <span class="rounded-full bg-[#0a1f55]/10 px-3 py-1 font-medium text-[#0a1f55]">
                                    {{ $loop->last ? 'below ' . $transmute[$i - 1][0] . '%' : '≥ ' . $min . '%' }} → {{ $fmt($g) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </details>

            {{-- ============ The table ============ --}}
            <form method="POST" action="{{ route('grades.save') }}" @submit="saving = true">
                @csrf
                <input type="hidden" name="section_id" value="{{ $section->id }}">
                <input type="hidden" name="subject" value="{{ $subject }}">

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border-l-4 border-[#7a1230] bg-white px-5 py-4 text-sm shadow-sm ring-1 ring-slate-200">
                        <p class="font-semibold text-[#7a1230]">Nothing was saved. Please fix the highlighted cells.</p>
                        <ul class="mt-1 list-inside list-disc text-slate-600">
                            @foreach (collect($errors->all())->unique()->take(4) as $err) <li>{{ $err }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                                <tr>
                                    <th class="sticky left-0 z-20 bg-[#0a1f55] px-4 py-3.5 font-semibold">Student</th>
                                    @foreach ($periods as $label)
                                        <th class="px-2 py-3.5 text-center font-semibold">{{ $label }}</th>
                                    @endforeach
                                    <th class="px-4 py-3.5 text-center font-semibold">Average</th>
                                    <th class="px-3 py-3.5 font-semibold">Status</th>
                                    <th class="px-3 py-3.5 font-semibold">Remarks</th>
                                    <th class="px-3 py-3.5"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach ($students as $s)
                                    @php $sid = $s->id; $m = $meta[$sid]; @endphp
                                    <tr class="{{ $m['editable'] ? 'hover:bg-[#fbf8f1]' : 'bg-slate-50/70' }}">

                                        <td class="sticky left-0 z-10 min-w-[230px] bg-white px-4 py-2">
                                            <div class="flex items-center gap-3">
                                                <x-avatar :user="$s" size="h-9 w-9" text="text-xs" />
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold text-[#0a1f55]">{{ $s->list_name }}</p>
                                                    @if ($m['locked'])
                                                        <p class="text-xs font-medium text-[#0a1f55]/70">🔒 Finalized</p>
                                                    @elseif ($m['by'])
                                                        <p class="text-xs text-slate-400">Graded by {{ $m['by'] }}</p>
                                                    @else
                                                        <p class="truncate text-xs text-slate-400">{{ $s->email }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        @foreach ($periods as $key => $label)
                                            <td class="px-2 py-2 text-center">
                                                <input type="number" step="0.01" min="{{ $best }}" max="{{ $worst }}" inputmode="decimal"
                                                       name="rows[{{ $sid }}][{{ $key }}]"
                                                       x-model="rows[{{ $sid }}].{{ $key }}"
                                                       data-col="{{ $key }}"
                                                       @keydown="nav($event, '{{ $key }}')"
                                                       @disabled(! $m['editable'])
                                                       placeholder="–"
                                                       class="w-24 rounded-lg border-0 px-3 py-2 text-center text-sm font-medium shadow-sm focus:ring-2 focus:ring-[#0a1f55] disabled:bg-slate-100 disabled:text-slate-500 {{ $errors->has("rows.$sid.$key") ? 'ring-2 ring-[#7a1230] bg-[#7a1230]/5' : 'ring-1 ring-slate-200' }}">
                                            </td>
                                        @endforeach

                                        <td class="px-4 py-2 text-center">
                                            <span class="text-base font-bold tabular-nums" :class="tone(avg(rows[{{ $sid }}]))" x-text="fmt(avg(rows[{{ $sid }}]))"></span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2">
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="badge(status(rows[{{ $sid }}]))" x-text="label(status(rows[{{ $sid }}]))"></span>
                                        </td>

                                        <td class="px-3 py-2">
                                            <input type="text" maxlength="255" placeholder="Optional note"
                                                   name="rows[{{ $sid }}][remarks]"
                                                   x-model="rows[{{ $sid }}].remarks"
                                                   @disabled(! $m['editable'])
                                                   class="w-52 rounded-lg border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55] disabled:bg-slate-100 disabled:text-slate-500">
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-2 text-right text-xs font-semibold">
                                            @if ($m['editable'])
                                                <button type="button" @click="clearRow({{ $sid }})" class="mr-2 text-slate-400 hover:text-[#7a1230]" title="Clear this row">Clear</button>
                                            @endif
                                            <a href="{{ route('grades.report', $s) }}" target="_blank" class="text-[#7a1230] hover:underline" title="Open report card">Report</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Save bar --}}
                <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex flex-wrap items-center justify-between gap-3 border-t-2 border-[#f6c945] bg-white/95 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border-2 sm:px-6">
                    <p class="text-sm" :class="dirty ? 'font-semibold text-[#7a1230]' : 'text-slate-400'"
                       x-text="dirty ? '● You have unsaved changes' : 'All changes saved'"></p>

                    <div class="flex items-center gap-3">
                        <p class="hidden text-xs text-slate-400 lg:block">Tip: press Enter or ↓ to move down a column.</p>

                        @if ($anyEditable)
                            <button type="submit" :disabled="saving"
                                    class="rounded-xl bg-[#0a1f55] px-7 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f] disabled:opacity-60">
                                Save grades
                            </button>
                        @else
                            <span class="rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-400">Everything here is locked</span>
                        @endif
                    </div>
                </div>
            </form>
        @endif
    </div>
@endif
@endsection
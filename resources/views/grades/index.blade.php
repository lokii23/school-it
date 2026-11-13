@extends('layouts.portal')

@section('title', auth()->user()->role === 'admin' ? 'Grades' : 'Grade Management')

@section('content')
@php
    $fmt      = fn ($n) => \App\Support\Grading::fmt($n);
    $hasSheet = $section && $subject;
    $same     = fn ($a, $b) => mb_strtolower((string) $a) === mb_strtolower((string) $b);
@endphp

{{-- ====================================================================
     STEP 1: choose a section
     ==================================================================== --}}
@if (! $section)

    @if ($unassigned > 0)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border-l-4 border-[#7a1230] bg-white px-6 py-4 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-600">
                <strong class="text-[#7a1230]">{{ $unassigned }}</strong>
                {{ $unassigned === 1 ? 'student has' : 'students have' }}
                no section yet, so they don't appear in any grade table.
            </p>
            @if (Route::has('sections.index'))
                <a href="{{ route('sections.index') }}" class="rounded-xl bg-[#7a1230] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#5a0c22]">Assign sections</a>
            @endif
        </div>
    @endif

    <div class="mb-5">
        <h2 class="text-lg font-semibold text-[#0a1f55]">Choose a section</h2>
        <p class="text-sm text-slate-500">These are the sections from Section Management. Click one to open its grade table.</p>
    </div>

    @if ($cards->isEmpty())
        <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🏫</div>
            @if ($isAdmin)
                <p class="font-semibold text-[#0a1f55]">No sections yet.</p>
                <p class="mt-1 text-sm text-slate-400">Create sections and assign students first.</p>
                @if (Route::has('sections.index'))
                    <a href="{{ route('sections.index') }}" class="mt-4 inline-block rounded-xl bg-[#0a1f55] px-5 py-2 text-sm font-semibold text-white hover:bg-[#12307f]">Go to Section Management</a>
                @endif
            @else
                <p class="font-semibold text-[#0a1f55]">No subject has been assigned to you yet.</p>
                <p class="mt-1 text-sm text-slate-400">The admin assigns subjects to teachers. Once you have one, its section appears here.</p>
            @endif
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $c)
                <a href="{{ route('grades.index', ['section_id' => $c['section']->id]) }}"
                   class="group flex flex-col overflow-hidden rounded-2xl border-t-4 border-[#f6c945] bg-white shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex-1 p-6">
                        <div class="flex items-center justify-between">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#0a1f55] text-xl text-[#f6c945] transition group-hover:rotate-6">🏫</span>
                            <span class="rounded-full bg-[#0a1f55]/10 px-3 py-1 text-xs font-semibold text-[#0a1f55]">
                                {{ $c['students'] }} {{ Str::plural('student', $c['students']) }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-xl font-bold text-[#0a1f55]">{{ $c['section']->name }}</h3>

                        <p class="mt-1 text-sm text-slate-500">
                            @if ($c['subjects'] > 0)
                                {{ $c['subjects'] }} {{ Str::plural('subject', $c['subjects']) }} assigned
                            @else
                                No subject assigned yet
                            @endif
                        </p>

                        @if ($c['updated'])
                            <p class="mt-1 text-xs text-slate-400">Updated {{ \Illuminate\Support\Carbon::parse($c['updated'])->diffForHumans() }}</p>
                        @endif
                    </div>

                    <div class="border-t border-slate-100 bg-[#fbf8f1] px-6 py-3 text-center text-sm font-semibold text-[#7a1230] transition group-hover:bg-[#7a1230] group-hover:text-white">
                        Open grade table →
                    </div>
                </a>
            @endforeach
        </div>
    @endif

@else

    {{-- ====================================================================
         A section is chosen: section switcher + subject bar
         ==================================================================== --}}
    <div class="mb-6 space-y-4 rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">

        <div>
            <div class="mb-2 flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Section</p>
                <a href="{{ route('grades.index') }}" class="text-xs font-semibold text-[#7a1230] hover:underline">All sections</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($sections as $s)
                    @php $active = $section->id === $s->id; @endphp
                    <a href="{{ route('grades.index', array_filter(['section_id' => $s->id, 'subject' => $subject])) }}"
                       class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $active ? 'bg-[#0a1f55] text-white shadow' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                        {{ $s->name }}
                        <span class="ml-1 rounded-full px-1.5 text-xs {{ $active ? 'bg-[#f6c945] text-[#0a1f55]' : 'bg-slate-100 text-slate-500' }}">{{ $counts[$s->id] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Subject</p>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    @forelse ($subjects as $sub)
                        @php $active = $same($sub['name'], $subject); @endphp
                        <a href="{{ route('grades.index', ['section_id' => $section->id, 'subject' => $sub['name']]) }}"
                           class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $active ? 'bg-[#7a1230] text-white shadow' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                            {{ $sub['name'] }}
                            @if ($sub['locked'] > 0) <span title="Finalized">🔒</span> @endif
                        </a>
                    @empty
                        <span class="text-sm text-slate-400">No subject is assigned for this section yet.</span>
                    @endforelse
                </div>

                @if ($isAdmin)
                    <form method="GET" action="{{ route('grades.index') }}" class="flex gap-2">
                        <input type="hidden" name="section_id" value="{{ $section->id }}">
                        <select name="subject" required
                                class="w-60 rounded-xl border-0 bg-white py-2 pl-4 pr-9 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]">
                            <option value="">Open any subject…</option>
                            @foreach ($allSubjects as $n) <option value="{{ $n }}">{{ $n }}</option> @endforeach
                        </select>
                        <button class="rounded-xl bg-[#0a1f55] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#12307f]">Open</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ====================================================================
         STEP 2: section chosen, no subject yet
         ==================================================================== --}}
    @if (! $hasSheet)

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-[#0a1f55]">{{ $section->name }} · {{ $counts[$section->id] ?? 0 }} {{ Str::plural('student', $counts[$section->id] ?? 0) }}</h2>
                <p class="text-sm text-slate-500">Pick a subject to open the grade table for this section.</p>
            </div>
            @if (($counts[$section->id] ?? 0) > 0)
                <a href="{{ route('grades.reports', ['section_id' => $section->id]) }}" target="_blank"
                   class="rounded-xl bg-[#f6c945] px-4 py-2 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#fde9a8]">
                    🖨 Print all report cards
                </a>
            @endif
        </div>

        @if (($counts[$section->id] ?? 0) === 0)
            <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">👥</div>
                <p class="font-semibold text-[#0a1f55]">{{ $section->name }} has no students yet.</p>
                <p class="mt-1 text-sm text-slate-400">Assign students to this section first, and they will appear here.</p>
                @if ($isAdmin && Route::has('sections.index'))
                    <a href="{{ route('sections.index') }}" class="mt-4 inline-block rounded-xl bg-[#0a1f55] px-5 py-2 text-sm font-semibold text-white hover:bg-[#12307f]">Go to Section Management</a>
                @endif
            </div>
        @elseif ($subjects->isEmpty())
            <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">📚</div>
                <p class="font-semibold text-[#0a1f55]">No subject yet for {{ $section->name }}.</p>
                <p class="mt-1 text-sm text-slate-400">{{ $isAdmin ? 'Go to Subjects and assign a teacher to a subject for this section.' : 'Ask the admin to assign you a subject for this section.' }}</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($subjects as $sub)
                    @php
                        $pct  = $sub['students'] ? round($sub['graded'] / $sub['students'] * 100) : 0;
                        $done = $sub['students'] && $sub['complete'] >= $sub['students'];
                    @endphp
                    <a href="{{ route('grades.index', ['section_id' => $section->id, 'subject' => $sub['name']]) }}"
                       class="group flex flex-col overflow-hidden rounded-2xl border-l-4 border-[#f6c945] bg-white shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex-1 p-6">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-[#7a1230]">{{ $section->name }}</p>
                                @if ($sub['locked'] > 0)
                                    <span class="rounded-full bg-[#0a1f55]/10 px-2.5 py-0.5 text-xs font-semibold text-[#0a1f55]">🔒 Finalized</span>
                                @elseif ($done)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">✓ Complete</span>
                                @endif
                            </div>

                            <h3 class="mt-2 text-lg font-semibold text-[#0a1f55]">{{ $sub['name'] }}</h3>

                            <div class="mt-4">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-slate-600">Students graded</span>
                                    <span class="font-bold text-[#0a1f55]">{{ $sub['graded'] }} / {{ $sub['students'] }}</span>
                                </div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-[#f6c945] to-[#d4a017]" style="width: {{ $pct }}%"></div>
                                </div>
                                <p class="mt-2 text-xs text-slate-400">{{ $sub['complete'] }} with all {{ count($periods) }} periods</p>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 bg-[#fbf8f1] px-6 py-3 text-center text-sm font-semibold text-[#7a1230] transition group-hover:bg-[#7a1230] group-hover:text-white">
                            Enter grades →
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    @else

        {{-- ====================================================================
             STEP 3: THE GRADE TABLE
             ==================================================================== --}}
        <div
            x-data="{
                rows: {{ Js::from((object) $rows) }},
                editable: {{ Js::from((object) $editable) }},
                examScores: {{ Js::from((object) $examScores) }},
                keys: {{ Js::from(array_keys($periods)) }},
                remarkScale: {{ Js::from($remarkScale) }},
                failedRemark: {{ Js::from($failedRemark) }},
                incRemark: {{ Js::from($incRemark) }},
                passing: {{ $passing }},
                best: {{ $best }},
                worst: {{ $worst }},
                dec: {{ $decimals }},
                hadErrors: {{ $errors->any() ? 'true' : 'false' }},
                exam: '', period: '', onlyEmpty: true, importMsg: '', formMsg: '',
                manual: {},
                snapshot: '', saving: false,

                init() {
                    // Remarks fill in by themselves, unless the teacher wrote their own
                    for (const id in this.rows) {
                        const r = this.rows[id], auto = this.autoRemark(r), cur = String(r.remarks || '').trim();
                        this.manual[id] = cur !== '' && cur !== auto;
                        if (this.editable[id] && !this.manual[id]) r.remarks = auto;
                    }
                    this.snapshot = this.hadErrors ? '' : JSON.stringify(this.rows);
                },
                get dirty() { return JSON.stringify(this.rows) !== this.snapshot; },

                isInc(v) { return String(v === null || v === undefined ? '' : v).trim().toUpperCase() === 'INC'; },
                num(v) { if (this.isInc(v)) return null; const n = parseFloat(v); return isNaN(n) ? null : n; },
                round(n) { const f = Math.pow(10, this.dec); return Math.round((n + Number.EPSILON) * f) / f; },
                hasInc(r) { return this.keys.some(k => this.isInc(r[k])); },
                avg(r) {
                    if (this.hasInc(r)) return null;
                    const v = this.keys.map(k => this.num(r[k])).filter(n => n !== null);
                    return v.length ? this.round(v.reduce((a, b) => a + b, 0) / v.length) : null;
                },
                complete(r) { return this.keys.every(k => this.num(r[k]) !== null); },
                passes(a) { return a !== null && a <= this.passing + 0.000001; },
                fmt(a) { return a === null ? '—' : a.toFixed(this.dec); },

                bad(v) {
                    const s = String(v === null || v === undefined ? '' : v).trim();
                    if (s === '' || this.isInc(s)) return false;
                    if (!/^\d+(\.\d{1,2})?$/.test(s)) return true;
                    const n = parseFloat(s);
                    return n < this.best || n > this.worst;
                },
                cell(v) {
                    if (this.bad(v)) return 'ring-2 ring-[#7a1230] bg-[#7a1230]/5';
                    if (this.isInc(v)) return 'ring-1 ring-[#0a1f55]/40 bg-[#0a1f55]/10 text-[#0a1f55] font-bold';
                    return 'ring-1 ring-slate-200';
                },
                norm(id, k) { if (this.isInc(this.rows[id][k])) this.rows[id][k] = 'INC'; },

                status(r) {
                    if (this.hasInc(r)) return 'inc';
                    const a = this.avg(r);
                    if (a === null) return 'empty';
                    if (!this.complete(r)) return 'progress';
                    return this.passes(a) ? 'passed' : 'failed';
                },
                label(s) { return { passed: 'Passed', failed: 'Failed', progress: 'In progress', inc: 'INC', empty: '—' }[s]; },
                badge(s) { return { passed: 'bg-emerald-100 text-emerald-700', failed: 'bg-[#7a1230]/10 text-[#7a1230]', progress: 'bg-[#f6c945]/30 text-[#7a5a00]', inc: 'bg-[#0a1f55]/10 text-[#0a1f55]', empty: 'text-slate-300' }[s]; },
                tone(r) {
                    if (this.hasInc(r)) return 'text-[#0a1f55]';
                    const a = this.avg(r);
                    return a === null ? 'text-slate-300' : (this.passes(a) ? 'text-emerald-600' : 'text-[#7a1230]');
                },

                // The remark worked out from the grades. Needs Midterm and Final, or an INC.
                autoRemark(r) {
                    if (this.hasInc(r)) return this.incRemark;
                    if (!this.complete(r)) return '';
                    const a = this.avg(r);
                    for (const [max, label] of this.remarkScale) { if (a <= max + 0.000001) return label; }
                    return this.failedRemark;
                },
                sync(id) {
                    if (!this.editable[id] || this.manual[id]) return;
                    this.rows[id].remarks = this.autoRemark(this.rows[id]);
                },

                get stats() {
                    let sum = 0, n = 0, passed = 0, failed = 0, progress = 0, inc = 0;
                    for (const id in this.rows) {
                        const r = this.rows[id], a = this.avg(r), s = this.status(r);
                        if (s === 'passed') passed++; else if (s === 'failed') failed++; else if (s === 'progress') progress++; else if (s === 'inc') inc++;
                        if (a !== null) { sum += a; n++; }
                    }
                    return { avg: n ? this.round(sum / n) : null, passed, failed, progress, inc };
                },

                clearRow(id) { this.keys.forEach(k => this.rows[id][k] = ''); this.rows[id].remarks = ''; this.manual[id] = false; },

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
                        this.sync(id);
                    }
                    this.importMsg = 'Filled ' + filled + ' cell(s) with grades converted from the exam scores' + (missing ? ' (' + missing + ' student(s) have no result)' : '') + '. Review them, then press Save grades.';
                },

                onSubmit(e) {
                    const invalid = Object.keys(this.rows).some(id => this.editable[id] && this.keys.some(k => this.bad(this.rows[id][k])));
                    if (invalid) {
                        e.preventDefault();
                        this.formMsg = 'Some grades are not valid. Use ' + this.best.toFixed(2) + ' to ' + this.worst.toFixed(2) + ' or INC.';
                        return;
                    }
                    this.formMsg = '';
                    this.saving = true;
                },
            }"
            @beforeunload.window="if (dirty && !saving) { $event.preventDefault(); $event.returnValue = ''; }"
            class="space-y-6">

            {{-- Header --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-6 text-white shadow-lg">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#f6c945]/20 blur-2xl"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#f6c945]">Grade table</p>
                        <h2 class="mt-1 text-2xl font-bold">{{ $subject }}</h2>
                        <p class="text-slate-300">{{ $section->name }} · {{ $students->count() }} {{ Str::plural('student', $students->count()) }}@if ($teacherName) · Teacher: {{ $teacherName }}@endif</p>

                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-white/10 px-3 py-1">{{ $fmt($best) }} highest · {{ $fmt($passing) }} lowest passing · {{ $fmt($worst) }} last grade</span>
                            @if ($lockedCount > 0)
                                <span class="rounded-full bg-[#f6c945] px-3 py-1 font-bold text-[#0a1f55]">🔒 {{ $lockedCount }} of {{ $gradedCount }} finalized</span>
                            @endif
                            <span class="rounded-full bg-white/10 px-3 py-1" x-show="dirty" style="display:none">● Unsaved changes</span>
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
                </div>
            @else

                {{-- Live class statistics --}}
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-sm text-slate-500">Class average <span class="text-xs text-slate-400">(lower is better)</span></p>
                        <p class="mt-1 text-3xl font-bold text-[#0a1f55]" x-text="fmt(stats.avg)"></p>
                    </div>
                    <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-sm text-slate-500">Passed</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-600" x-text="stats.passed"></p>
                    </div>
                    <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-sm text-slate-500">Failed</p>
                        <p class="mt-1 text-3xl font-bold text-[#7a1230]" x-text="stats.failed"></p>
                    </div>
                    <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-sm text-slate-500">INC</p>
                        <p class="mt-1 text-3xl font-bold text-[#0a1f55]" x-text="stats.inc"></p>
                    </div>
                    <div class="rounded-2xl border-t-4 border-slate-300 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-sm text-slate-500">In progress</p>
                        <p class="mt-1 text-3xl font-bold text-slate-600" x-text="stats.progress"></p>
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

                        <p x-show="importMsg" x-text="importMsg" style="display:none" class="mt-3 rounded-lg bg-[#fbf8f1] px-4 py-2 text-xs text-slate-600"></p>
                    @else
                        <p class="mt-1 text-sm text-slate-400">No exam results for this subject yet. This becomes available once students have taken an exam for it.</p>
                    @endif
                </div>

                {{-- Grading scale, remarks and INC --}}
                <details class="rounded-2xl bg-white px-5 py-3 shadow-sm ring-1 ring-slate-200/70">
                    <summary class="cursor-pointer text-sm font-semibold text-[#0a1f55]">Grading scale, remarks and INC</summary>
                    <div class="mt-3 space-y-4 text-xs">

                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700">{{ $fmt($best) }} = highest</span>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700">{{ $fmt($best) }} to {{ $fmt($passing) }} = Passed</span>
                            <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 font-medium text-[#7a1230]">{{ number_format($passing + 0.01, 2) }} to {{ $fmt($worst) }} = Failed</span>
                            <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 font-medium text-[#7a1230]">{{ $fmt($worst) }} = last grade</span>
                        </div>

                        <div>
                            <p class="mb-1.5 font-semibold text-[#0a1f55]">Remarks, filled in automatically from the average of {{ implode(' and ', array_values($periods)) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($remarkScale as [$max, $label])
                                    <span class="rounded-full bg-[#f6c945]/30 px-3 py-1 font-medium text-[#7a5a00]">up to {{ number_format($max, $decimals) }} → {{ $label }}</span>
                                @endforeach
                                <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 font-medium text-[#7a1230]">above {{ number_format(collect($remarkScale)->last()[0], $decimals) }} → {{ $failedRemark }}</span>
                                <span class="rounded-full bg-[#0a1f55]/10 px-3 py-1 font-medium text-[#0a1f55]">INC → {{ $incRemark }}</span>
                            </div>
                            <p class="mt-2 text-slate-500">You can type over any remark. Click ↺ beside it, or clear it and click away, to use the automatic one again.</p>
                        </div>

                        <p class="text-slate-500">
                            Average = the mean of {{ implode(' and ', array_values($periods)) }}, to {{ $decimals }} decimal. Passed or Failed appears once every period has a grade.
                            Type <strong>INC</strong> in a cell for an incomplete grade. The average and Passed or Failed then wait until the INC is replaced with a grade.
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

                {{-- The table --}}
                <form method="POST" action="{{ route('grades.save') }}" @submit="onSubmit($event)">
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

                    <p class="mb-3 rounded-xl bg-white px-5 py-3 text-xs text-slate-500 shadow-sm ring-1 ring-slate-200/70">
                        Type a grade from <strong>{{ $fmt($best) }}</strong> to <strong>{{ $fmt($worst) }}</strong>, or <strong>INC</strong> for an incomplete grade.
                        Remarks fill in by themselves once both grades are in, and you can still edit them.
                    </p>

                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                                    <tr>
                                        <th class="w-10 px-3 py-3.5 text-center font-semibold">#</th>
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

                                            <td class="px-3 py-2 text-center text-xs text-slate-400">{{ $loop->iteration }}</td>

                                            <td class="sticky left-0 z-10 min-w-[230px] bg-white px-4 py-2">
                                                <div class="flex items-center gap-3">
                                                    <x-avatar :user="$s" size="h-9 w-9" text="text-xs" />
                                                    <div class="min-w-0">
                                                        <p class="truncate font-semibold text-[#0a1f55]">
                                                            {{ $s->list_name }}
                                                            @if ($s->is_irregular) <span class="ml-1 rounded-full bg-[#f6c945]/40 px-2 py-0.5 text-[10px] font-bold text-[#7a5a00]">Irregular</span> @endif
                                                        </p>
                                                        @if ($m['locked'])
                                                            <p class="text-xs font-medium text-[#0a1f55]/70">🔒 Finalized</p>
                                                        @else
                                                            <p class="truncate text-xs text-slate-400">{{ $s->student_number ?: $s->email }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            @foreach ($periods as $key => $label)
                                                <td class="px-2 py-2 text-center">
                                                    <input type="text" inputmode="decimal" maxlength="4" autocomplete="off"
                                                           name="rows[{{ $sid }}][{{ $key }}]"
                                                           x-model="rows[{{ $sid }}].{{ $key }}"
                                                           data-col="{{ $key }}"
                                                           @input="$nextTick(() => sync({{ $sid }}))"
                                                           @blur="norm({{ $sid }}, '{{ $key }}')"
                                                           @keydown="nav($event, '{{ $key }}')"
                                                           @disabled(! $m['editable'])
                                                           placeholder="–"
                                                           :class="cell(rows[{{ $sid }}].{{ $key }})"
                                                           class="w-24 rounded-lg border-0 px-3 py-2 text-center text-sm font-medium uppercase shadow-sm focus:ring-2 focus:ring-[#0a1f55] disabled:bg-slate-100 disabled:text-slate-500">
                                                </td>
                                            @endforeach

                                            <td class="px-4 py-2 text-center">
                                                <span class="text-base font-bold tabular-nums" :class="tone(rows[{{ $sid }}])"
                                                      x-text="hasInc(rows[{{ $sid }}]) ? 'INC' : fmt(avg(rows[{{ $sid }}]))"></span>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2">
                                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="badge(status(rows[{{ $sid }}]))" x-text="label(status(rows[{{ $sid }}]))"></span>
                                            </td>

                                            <td class="px-3 py-2">
                                                <div class="flex items-center gap-1.5">
                                                    <input type="text" maxlength="255" placeholder="Automatic"
                                                           name="rows[{{ $sid }}][remarks]"
                                                           x-model="rows[{{ $sid }}].remarks"
                                                           @input="manual[{{ $sid }}] = true"
                                                           @blur="if (!String(rows[{{ $sid }}].remarks || '').trim()) { manual[{{ $sid }}] = false; sync({{ $sid }}); }"
                                                           @disabled(! $m['editable'])
                                                           class="w-48 rounded-lg border-0 px-3 py-2 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55] disabled:bg-slate-100 disabled:text-slate-500">
                                                    @if ($m['editable'])
                                                        <button type="button" x-show="manual[{{ $sid }}]" style="display:none"
                                                                @click="manual[{{ $sid }}] = false; sync({{ $sid }})"
                                                                title="Use the automatic remark"
                                                                class="rounded-md px-1.5 py-1 text-sm font-bold text-[#7a1230] hover:bg-[#7a1230]/10">↺</button>
                                                    @endif
                                                </div>
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
                        <div>
                            <p class="text-sm" :class="dirty ? 'font-semibold text-[#7a1230]' : 'text-slate-400'"
                               x-text="dirty ? '● You have unsaved changes' : 'All changes saved'"></p>
                            <p x-show="formMsg" x-text="formMsg" style="display:none" class="mt-0.5 text-xs font-semibold text-[#7a1230]"></p>
                        </div>

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
@endif
@endsection
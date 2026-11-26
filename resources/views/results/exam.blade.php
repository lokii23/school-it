@extends('layouts.portal')

@section('title', 'Exam Results')
@section('live', '5')

@section('content')
@php
    $dur       = fn ($s) => $s === null ? '—' : intdiv($s, 60) . 'm ' . ($s % 60) . 's';
    $distMax   = max(1, max($dist));
    $distColor = ['bg-[#7a1230]', 'bg-[#7a1230]/70', 'bg-[#f6c945]', 'bg-emerald-400', 'bg-emerald-500'];
@endphp

<div class="space-y-6"
     x-data="{
         f: 'all', q: '', passing: {{ $passing }},
         show(state, name) { return (this.f === 'all' || this.f === state) && (!this.q || name.includes(this.q.toLowerCase())); },

         sOpen: false, s: {}, val: '',
         openScore(row) { this.s = row; this.val = row.score; this.sOpen = true; },
         get total() { return this.s.total || 0; },
         get num() { const n = parseInt(this.val, 10); return isNaN(n) ? null : n; },
         get valid() { return this.num !== null && this.num >= 0 && this.num <= this.total; },
         get pct() { return this.valid && this.total ? Math.round(this.num / this.total * 100) : null; },
         step(d) { const n = (this.num === null ? 0 : this.num) + d; this.val = Math.max(0, Math.min(this.total, n)); },
     }">

    <a href="{{ route('results.index') }}" class="inline-block text-sm font-semibold text-slate-500 hover:text-slate-700">← All exams</a>

    @error('score')
        <div class="rounded-xl border-l-4 border-[#7a1230] bg-white px-5 py-3 text-sm font-medium text-[#7a1230] shadow-sm ring-1 ring-slate-200">⚠ {{ $message }}</div>
    @enderror

    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-6 text-white shadow-lg">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#f6c945]/20 blur-2xl"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#f6c945]">{{ $exam->type }} results</p>
                <h2 class="mt-1 text-2xl font-bold">{{ $exam->title }}</h2>
                <p class="text-slate-300">{{ $exam->subject }} · {{ $exam->section->name ?? '—' }} · {{ $exam->questions_count }} questions</p>

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-white/10 px-3 py-1">{{ ucfirst($exam->status) }}</span>
                    @if ($exam->closes_at) <span class="rounded-full bg-white/10 px-3 py-1">Closes {{ $exam->closes_at->format('M d, g:i A') }}</span> @endif
                    @if ($exam->duration_minutes) <span class="rounded-full bg-white/10 px-3 py-1">⏱ {{ $exam->duration_minutes }} min</span> @endif
                    @if ($exam->score_mode === 'after_close' && ! $exam->scoreVisible())
                        <span class="rounded-full bg-[#f6c945] px-3 py-1 font-bold text-[#0a1f55]">🔒 Students can't see scores yet</span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2 text-sm font-semibold">
                <a href="{{ route('results.exam.export', $exam) }}"
                   class="rounded-xl bg-white/10 px-4 py-2 ring-1 ring-white/20 transition hover:bg-[#f6c945] hover:text-[#0a1f55]">⬇ Export CSV</a>
                <a href="{{ route('exams.edit', $exam) }}"
                   class="rounded-xl bg-white/10 px-4 py-2 ring-1 ring-white/20 transition hover:bg-[#f6c945] hover:text-[#0a1f55]">✎ Edit exam</a>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Submitted</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $summary['taken'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $summary['passed'] }} passed · {{ $summary['failed'] }} failed</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Class average</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $summary['avg'] !== null ? $summary['avg'] . '%' : '—' }}</p>
            <p class="mt-1 text-xs text-slate-400">High {{ $summary['high'] ?? '—' }}% · Low {{ $summary['low'] ?? '—' }}%</p>
        </div>
        <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Pass rate <span class="text-xs text-slate-400">(≥ {{ $passing }}%)</span></p>
            <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $summary['rate'] !== null ? $summary['rate'] . '%' : '—' }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Scores adjusted</p>
            <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $summary['adjusted'] }}</p>
            <p class="mt-1 text-xs text-slate-400">Changed by hand</p>
        </div>
    </div>

    @if ($summary['taken'] === 0)
        <div class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-20 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#f6c945]/25 text-3xl">📥</div>
            <p class="text-lg font-semibold text-[#0a1f55]">No one has submitted yet.</p>
            <p class="mt-1 max-w-sm text-sm text-slate-400">Students appear here as soon as they submit this {{ $exam->type }}.</p>
        </div>
    @else

        {{-- Score distribution --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h3 class="mb-4 font-semibold text-[#0a1f55]">Score distribution</h3>
            <div class="grid grid-cols-5 items-end gap-3" style="height: 150px">
                @foreach ($dist as $label => $count)
                    <div class="flex h-full flex-col items-center justify-end">
                        <span class="mb-1 text-xs font-bold text-[#0a1f55]">{{ $count }}</span>
                        <div class="w-full rounded-t-lg {{ $distColor[$loop->index] }}" style="height: {{ max(4, round($count / $distMax * 100)) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 grid grid-cols-5 gap-3 text-center text-[11px] text-slate-500">
                @foreach ($dist as $label => $count) <span>{{ $label }}</span> @endforeach
            </div>
        </div>

        {{-- Students who submitted --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    @foreach ([['all', 'All', $summary['taken']], ['passed', 'Passed', $summary['passed']], ['failed', 'Failed', $summary['failed']]] as [$key, $label, $count])
                        <button type="button" @click="f = '{{ $key }}'"
                                :class="f === '{{ $key }}' ? 'bg-[#0a1f55] text-white shadow' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                                class="rounded-full px-4 py-1.5 text-sm font-semibold transition">
                            {{ $label }} <span class="ml-1 text-xs opacity-70">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>
                <input type="text" x-model="q" placeholder="Search student…"
                       class="w-full rounded-xl border-0 bg-white px-4 py-2 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] lg:w-64">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-3.5 font-semibold">Student</th>
                            <th class="px-6 py-3.5 font-semibold">Score</th>
                            <th class="px-6 py-3.5 font-semibold">Result</th>
                            <th class="px-6 py-3.5 font-semibold">Submitted</th>
                            <th class="px-6 py-3.5 font-semibold">Time taken</th>
                            <th class="px-6 py-3.5 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            @php
                                $s = $row['student']; $r = $row['result']; $p = $row['pct'];
                                $payload = [
                                    'id'        => $r->id,
                                    'name'      => $s->name,
                                    'score'     => $r->score,
                                    'total'     => $r->total,
                                    'auto'      => $r->auto_score ?? $r->score,
                                    'adjusted'  => (bool) $r->adjusted_at,
                                    'by'        => $r->adjuster->name ?? null,
                                    'at'        => $r->adjusted_at?->format('M d, g:i A'),
                                    'submitted' => $r->created_at->format('M d, Y g:i A'),
                                    'time'      => $dur($row['time']),
                                    'answers'   => route('results.show', $r),
                                ];
                            @endphp
                            <tr x-show="show('{{ $row['state'] }}', {{ Js::from(mb_strtolower($s->name)) }})" class="transition hover:bg-[#fbf8f1]">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$s" size="h-9 w-9" text="text-xs" />
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-[#0a1f55]">{{ $s->list_name }}</p>
                                            <p class="truncate text-xs text-slate-400">{{ $s->is_irregular ? 'Irregular' : ($s->section->name ?? $s->email) }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="min-w-[170px] px-6 py-3">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-700">
                                            {{ $r->score }} / {{ $r->total }}
                                            @if ($r->adjusted_at)
                                                <span class="ml-1 rounded-full bg-[#f6c945]/40 px-1.5 py-0.5 text-[10px] font-bold text-[#7a5a00]" title="Auto-graded: {{ $r->auto_score }} / {{ $r->total }}">✎ adjusted</span>
                                            @endif
                                        </span>
                                        <span class="font-bold {{ $row['state'] === 'passed' ? 'text-emerald-600' : 'text-[#7a1230]' }}">{{ $p }}%</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $row['state'] === 'passed' ? 'bg-emerald-500' : 'bg-[#7a1230]' }}" style="width: {{ $p }}%"></div>
                                    </div>
                                </td>

                                <td class="px-6 py-3">
                                    @if ($row['state'] === 'passed')
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Passed</span>
                                    @else
                                        <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 text-xs font-semibold text-[#7a1230]">Failed</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-3 text-slate-400">{{ $r->created_at->format('M d, g:i A') }}</td>
                                <td class="whitespace-nowrap px-6 py-3 text-slate-500">{{ $dur($row['time']) }}</td>

                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" @click="openScore({{ Js::from($payload) }})"
                                                class="rounded-lg bg-[#f6c945]/40 px-3.5 py-1.5 text-xs font-semibold text-[#7a5a00] transition hover:bg-[#f6c945]">View score</button>
                                        <a href="{{ route('results.show', $r) }}"
                                           class="rounded-lg bg-[#0a1f55]/10 px-3.5 py-1.5 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">View answers</a>
                                        <form method="POST" action="{{ route('results.destroy', $r) }}"
                                              data-confirm="Reset this attempt for {{ $s->name }}? Their score will be deleted and they can retake the exam.">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg bg-[#7a1230]/10 px-3.5 py-1.5 text-xs font-semibold text-[#7a1230] transition hover:bg-[#7a1230] hover:text-white">Allow retake</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Question analysis --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="border-b border-slate-100 p-5">
                <h3 class="font-semibold text-[#0a1f55]">Question analysis</h3>
                <p class="text-xs text-slate-400">Sorted from hardest to easiest, so you can see what to re-teach.</p>
            </div>

            @forelse ($analysis as $q)
                @php
                    $pc = $q['pct_correct'];
                    $tone = $pc < 50 ? ['Hard', 'bg-[#7a1230]/10 text-[#7a1230]', 'bg-[#7a1230]']
                          : ($pc < 80 ? ['Medium', 'bg-[#f6c945]/30 text-[#7a5a00]', 'bg-[#f6c945]']
                          : ['Easy', 'bg-emerald-100 text-emerald-700', 'bg-emerald-500']);
                @endphp
                <div class="border-b border-slate-100 px-6 py-4 last:border-0">
                    <div class="flex items-start justify-between gap-3">
                        <p class="whitespace-pre-line text-sm font-semibold text-[#0a1f55]">{{ $loop->iteration }}. {{ $q['question'] }}</p>
                        <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $tone[1] }}">{{ $tone[0] }}</span>
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $tone[2] }}" style="width: {{ $pc }}%"></div>
                        </div>
                        <span class="w-24 text-right text-xs font-bold text-slate-700">{{ $pc }}% correct</span>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">
                        ✓ {{ $q['right'] }} correct · ✗ {{ $q['wrong'] }} wrong · {{ $q['skipped'] }} skipped
                        @if ($q['correct'] !== null && isset($q['options'][$q['correct']]))
                            · Answer: <strong class="text-emerald-700">{{ chr(65 + $q['correct']) }}. {{ $q['options'][$q['correct']] }}</strong>
                        @endif
                        @if ($q['top_wrong'] !== null && isset($q['options'][$q['top_wrong']]))
                            · Most common wrong answer: <strong class="text-[#7a1230]">{{ chr(65 + $q['top_wrong']) }}. {{ $q['options'][$q['top_wrong']] }}</strong>
                        @endif
                    </p>
                </div>
            @empty
                <p class="px-6 py-12 text-center text-sm text-slate-400">Question analysis appears after students submit.</p>
            @endforelse
        </div>
    @endif

    {{-- ============ MODAL: view and change a score ============ --}}
    <div x-show="sOpen" x-transition.opacity style="display:none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#071640]/70 p-4" @keydown.escape.window="sOpen = false">
        <div @click.outside="sOpen = false" class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold">Score</h2>
                    <p class="truncate text-xs text-slate-300"><span x-text="s.name"></span> · {{ $exam->title }}</p>
                </div>
                <button type="button" @click="sOpen = false" class="text-white/70 hover:text-white">✕</button>
            </div>

            <form method="POST" :action="'{{ url('results') }}/' + s.id + '/score'" class="space-y-5 p-6">
                @csrf @method('PATCH')

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="rounded-xl bg-[#fbf8f1] px-3 py-2"><p class="text-slate-400">Submitted</p><p class="font-semibold text-[#0a1f55]" x-text="s.submitted"></p></div>
                    <div class="rounded-xl bg-[#fbf8f1] px-3 py-2"><p class="text-slate-400">Time taken</p><p class="font-semibold text-[#0a1f55]" x-text="s.time"></p></div>
                </div>

                <div>
                    <label class="mb-2 block text-center text-sm font-medium text-slate-700">Score (number of correct answers)</label>
                    <div class="flex items-center justify-center gap-3">
                        <button type="button" @click="step(-1)" class="flex h-10 w-10 items-center justify-center rounded-full bg-[#0a1f55]/10 text-xl font-bold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">−</button>
                        <input type="number" name="score" x-model="val" min="0" :max="total" step="1" required inputmode="numeric"
                               class="w-24 rounded-xl border-0 bg-white py-2 text-center text-3xl font-extrabold text-[#0a1f55] shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]">
                        <span class="text-xl font-bold text-slate-400">/ <span x-text="total"></span></span>
                        <button type="button" @click="step(1)" class="flex h-10 w-10 items-center justify-center rounded-full bg-[#0a1f55]/10 text-xl font-bold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">+</button>
                    </div>
                    <p class="mt-2 text-center text-sm" :class="valid ? 'text-slate-500' : 'text-[#7a1230]'"
                       x-text="valid ? pct + '%' + (pct >= passing ? ' · Passed' : ' · Not passed') : 'Enter a whole number from 0 to ' + total"></p>
                </div>

                <div class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-600">
                    Auto-graded from the answers: <strong x-text="s.auto + ' / ' + s.total"></strong>.
                    <template x-if="s.adjusted">
                        <span>Adjusted by <strong x-text="s.by || 'a teacher'"></strong> on <span x-text="s.at"></span>.</span>
                    </template>
                    <a :href="s.answers" class="ml-1 font-semibold text-[#7a1230] hover:underline">See their answers →</a>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <button type="submit" name="reset" value="1" formnovalidate x-show="s.adjusted" style="display:none"
                            class="text-sm font-semibold text-[#7a1230] hover:underline">↺ Reset to automatic</button>
                    <span x-show="!s.adjusted"></span>

                    <div class="flex gap-3">
                        <button type="button" @click="sOpen = false" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                        <button type="submit" :disabled="!valid"
                                class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f] disabled:opacity-50">Save score</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
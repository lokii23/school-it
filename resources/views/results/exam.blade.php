@extends('layouts.portal')

@section('title', 'Exam Results')
@section('live', '5')

@section('content')
@php
    $dur = fn ($s) => $s === null ? '—' : intdiv($s, 60) . 'm ' . ($s % 60) . 's';
    $distMax = max(1, max($dist));
    $distColor = ['bg-[#7a1230]', 'bg-[#7a1230]/70', 'bg-[#f6c945]', 'bg-emerald-400', 'bg-emerald-500'];
@endphp

<div class="space-y-6"
     x-data="{
         f: 'all', q: '',
         show(state, name) {
             const okF = this.f === 'all' || (this.f === 'taken' && state !== 'pending') || this.f === state;
             return okF && (!this.q || name.includes(this.q.toLowerCase()));
         },
     }">

    <a href="{{ route('results.index') }}" class="inline-block text-sm font-semibold text-slate-500 hover:text-slate-700">← All exams</a>

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
            <p class="text-sm text-slate-500">Completed</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $summary['taken'] }}<span class="text-base font-medium text-slate-400"> / {{ $summary['enrolled'] }}</span></p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-gradient-to-r from-[#f6c945] to-[#d4a017]" style="width: {{ $summary['enrolled'] ? round($summary['taken'] / $summary['enrolled'] * 100) : 0 }}%"></div>
            </div>
        </div>
        <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Class average</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $summary['avg'] !== null ? $summary['avg'] . '%' : '—' }}</p>
            <p class="mt-1 text-xs text-slate-400">High {{ $summary['high'] ?? '—' }}% · Low {{ $summary['low'] ?? '—' }}%</p>
        </div>
        <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Pass rate <span class="text-xs text-slate-400">(≥ {{ $passing }}%)</span></p>
            <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $summary['rate'] !== null ? $summary['rate'] . '%' : '—' }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $summary['passed'] }} passed · {{ $summary['failed'] }} failed</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Not yet taken</p>
            <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $summary['pending'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $exam->status === 'closed' ? 'Missed: the exam is closed' : 'Still able to take it' }}</p>
        </div>
    </div>

    {{-- Score distribution --}}
    @if ($summary['taken'] > 0)
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
    @endif

    {{-- Students --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach ([['all', 'All', $summary['enrolled']], ['taken', 'Taken', $summary['taken']], ['passed', 'Passed', $summary['passed']], ['failed', 'Failed', $summary['failed']], ['pending', 'Not taken', $summary['pending']]] as [$key, $label, $count])
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
                        <th class="px-6 py-3.5 font-semibold">Status</th>
                        <th class="px-6 py-3.5 font-semibold">Submitted</th>
                        <th class="px-6 py-3.5 font-semibold">Time taken</th>
                        <th class="px-6 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rows as $row)
                        @php
                            $s = $row['student']; $r = $row['result']; $p = $row['pct'];
                        @endphp
                        <tr x-show="show('{{ $row['state'] }}', {{ Js::from(mb_strtolower($s->name)) }})" class="transition hover:bg-[#fbf8f1]">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <x-avatar :user="$s" size="h-9 w-9" text="text-xs" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-[#0a1f55]">{{ $s->list_name }}</p>
                                        <p class="truncate text-xs text-slate-400">{{ $s->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="min-w-[170px] px-6 py-3">
                                @if ($r)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-700">{{ $r->score }} / {{ $r->total }}</span>
                                        <span class="font-bold {{ $row['state'] === 'passed' ? 'text-emerald-600' : 'text-[#7a1230]' }}">{{ $p }}%</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $row['state'] === 'passed' ? 'bg-emerald-500' : 'bg-[#7a1230]' }}" style="width: {{ $p }}%"></div>
                                    </div>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>

                            <td class="px-6 py-3">
                                @if ($row['state'] === 'passed')
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Passed</span>
                                @elseif ($row['state'] === 'failed')
                                    <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 text-xs font-semibold text-[#7a1230]">Failed</span>
                                @elseif ($row['in_progress'])
                                    <span class="rounded-full bg-[#f6c945]/30 px-3 py-1 text-xs font-semibold text-[#7a5a00]">In progress</span>
                                @elseif ($exam->status === 'closed')
                                    <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 text-xs font-semibold text-[#7a1230]">Missed</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Not taken yet</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-3 text-slate-400">{{ $r ? $r->created_at->format('M d, g:i A') : '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-slate-500">{{ $dur($row['time']) }}</td>

                            <td class="px-6 py-3">
                                @if ($r)
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('results.show', $r) }}"
                                           class="rounded-lg bg-[#0a1f55]/10 px-3.5 py-1.5 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">View answers</a>
                                        <form method="POST" action="{{ route('results.destroy', $r) }}"
                                              onsubmit="return confirm('Reset this attempt for {{ addslashes($s->name) }}? Their score will be deleted and they can retake the exam.')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg bg-[#7a1230]/10 px-3.5 py-1.5 text-xs font-semibold text-[#7a1230] transition hover:bg-[#7a1230] hover:text-white">Allow retake</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if ($rows->isEmpty())
                        <tr><td colspan="6" class="py-14 text-center text-slate-400">No students are in {{ $exam->section->name ?? 'this section' }} yet.</td></tr>
                    @endif
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
            <p class="px-6 py-12 text-center text-sm text-slate-400">
                Question analysis appears after students submit. Attempts made before answer review was added don't have saved answers.
            </p>
        @endforelse
    </div>
</div>
@endsection
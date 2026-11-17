@extends('layouts.portal')

@section('title', 'My Grades')

@section('content')
@php
    $fmtAvg  = fn ($n) => \App\Support\Grading::fmtAvg($n);
    
    $genPass = \App\Support\Grading::passes($general);
    $badge = [
        'passed'   => ['Passed', 'bg-emerald-100 text-emerald-700'],
        'failed'   => ['Failed', 'bg-[#7a1230]/10 text-[#7a1230]'],
        'progress' => ['In progress', 'bg-[#f6c945]/30 text-[#7a5a00]'],
        'empty'    => ['—', 'text-slate-300'],
        'inc'      => ['INC', 'bg-[#0a1f55]/10 text-[#0a1f55]'],
    ];
@endphp

<div class="space-y-6" x-data="{ p: 0 }" x-init="setTimeout(() => p = {{ $ring }}, 150)">

    @if ($grades->isEmpty())
        <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🎓</div>
            <p class="font-semibold text-[#0a1f55]">No grades posted yet.</p>
            <p class="mt-1 text-sm text-slate-400">Your teachers will post your grades here.</p>
        </div>
    @else

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- General average --}}
            <div class="flex flex-col items-center justify-center rounded-2xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-6 text-white shadow-lg">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#f6c945]">General average</p>
                <div class="relative mt-4 h-40 w-40">
                    <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="10"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke-width="10" stroke-linecap="round"
                                stroke="{{ $genPass ? '#f6c945' : '#fda4af' }}"
                                stroke-dasharray="339.3"
                                :stroke-dashoffset="339.3 - (339.3 * p / 100)"
                                style="transition: stroke-dashoffset 1.2s ease-out"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-extrabold">{{ $fmtAvg($general) }}</span>
                        <span class="text-xs text-slate-300">1.00 is the highest</span>
                    </div>
                </div>
                <span class="mt-4 rounded-full px-4 py-1 text-xs font-bold {{ $genPass ? 'bg-[#f6c945] text-[#0a1f55]' : 'bg-[#7a1230] text-white' }}">
                    {{ $allFinal ? ($genPass ? '✓ Passing' : 'Below passing') : 'Running average' }}
                </span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">Subjects</p>
                    <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $stats['count'] }}</p>
                </div>
                <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">Passed</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $stats['passed'] }}<span class="text-base font-medium text-slate-400"> / {{ $stats['complete'] }} completed</span></p>
                </div>
                <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">Best subject average</p>
                    <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $fmtAvg($stats['top']) }}</p>
                </div>
                <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <p class="text-sm text-slate-500">Weakest subject average</p>
                    <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $fmtAvg($stats['low']) }}</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('student.grades.report') }}" target="_blank"
               class="rounded-xl bg-[#f6c945] px-5 py-2.5 text-sm font-semibold text-[#0a1f55] shadow transition hover:bg-[#fde9a8]">🖨 Print my report card</a>
        </div>

        {{-- Grades --}}
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-5 py-3.5 font-semibold">Subject</th>
                            @foreach ($periods as $label) <th class="px-3 py-3.5 text-center font-semibold">{{ $label }}</th> @endforeach
                            <th class="px-4 py-3.5 text-center font-semibold">Average</th>
                            <th class="px-3 py-3.5 font-semibold">Status</th>
                            <th class="px-3 py-3.5 font-semibold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($grades as $g)
                            @php [$bLabel, $bClass] = $badge[$g->status]; @endphp
                            <tr class="transition hover:bg-[#fbf8f1]">
                                <td class="min-w-[180px] px-5 py-4">
                                    <p class="font-semibold text-[#0a1f55]">{{ $g->subject }}</p>
                                    <p class="text-xs text-slate-400">{{ $instructors[auth()->id()][$g->id] ?? '—' }} · {{ $g->locked ? '🔒 Finalized' : 'Preliminary' }}</p>
                                </td>
                                @foreach ($periods as $key => $label)
                                    <td class="px-3 py-4 text-center font-medium tabular-nums {{ $g->isInc($key) ? 'font-bold text-[#0a1f55]' : 'text-slate-700' }}">{{ $g->isInc($key) ? 'INC' : $fmtAvg($g->{$key}) }}</td>
                                @endforeach
                                <td class="px-4 py-4 text-center text-base font-bold tabular-nums {{ $g->has_inc ? 'text-[#0a1f55]' : ($g->average === null ? 'text-slate-300' : (\App\Support\Grading::passes($g->average) ? 'text-emerald-600' : 'text-[#7a1230]')) }}">{{ $g->has_inc ? 'INC' : $fmtAvg($g->average) }}</td>
                                <td class="whitespace-nowrap px-3 py-4"><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $bClass }}">{{ $bLabel }}</span></td>
                                <td class="max-w-[220px] px-3 py-4 text-xs text-slate-500">{{ $g->display_remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="border-t border-slate-100 bg-[#fbf8f1] px-5 py-3 text-xs text-slate-500">
                <strong>1.00</strong> is the highest grade and <strong>3.00</strong> is the lowest passing grade. <strong>3.01 to 4.00</strong> is failed.
                <strong>Preliminary</strong> grades can still change. <strong>Finalized</strong> grades have been confirmed by the admin.
                A Passed or Failed status appears once all {{ count($periods) }} periods are graded.
                <strong>INC</strong> means incomplete: the average waits until it is replaced with a grade.
            </p>
        </div>
    @endif
</div>
@endsection
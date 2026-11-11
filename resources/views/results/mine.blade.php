@extends('layouts.portal')

@section('title', 'My Results')

@section('content')
<div class="space-y-6">
    @if ($stats['pending'] > 0)
        <div class="rounded-xl border-l-4 border-[#f6c945] bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-slate-200">
            🔒 <strong class="text-[#0a1f55]">{{ $stats['pending'] }}</strong>
            {{ Str::plural('score is', $stats['pending']) === 'score is' ? 'score is' : 'scores are' }}
            hidden until the exam closes. They’ll appear here automatically.
        </div>
    @endif
    {{-- Summary --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Exams taken</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $stats['taken'] }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Passed</p>
            <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $stats['passed'] }}<span class="text-base font-medium text-slate-400"> / {{ $stats['released'] }}</span></p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Average score</p>
            <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $stats['avg'] !== null ? $stats['avg'] . '%' : '—' }}</p>
        </div>
        <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
            <p class="text-sm text-slate-500">Best score</p>
            <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $stats['best'] !== null ? $stats['best'] . '%' : '—' }}</p>
        </div>
    </div>

    {{-- Results --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">Exam</th>
                        <th class="px-6 py-3.5 font-semibold">Type</th>
                        <th class="px-6 py-3.5 font-semibold">Score</th>
                        <th class="px-6 py-3.5 font-semibold">Status</th>
                        <th class="px-6 py-3.5 font-semibold">Date taken</th>
                        <th class="px-6 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($results as $r)
                        @php
                            $released = $r->exam->scoreVisible();
                            $p = $r->total ? (int) round($r->score / $r->total * 100) : 0;
                            $pass = $p >= $passing;
                        @endphp
                        <tr class="transition hover:bg-[#fbf8f1]">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-[#0a1f55]">{{ $r->exam->title }}</p>
                                <p class="text-xs text-slate-400">{{ $r->exam->subject }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $r->exam->type === 'quiz' ? 'bg-[#f6c945]/30 text-[#7a5a00]' : 'bg-[#0a1f55]/10 text-[#0a1f55]' }}">{{ $r->exam->type }}</span>
                            </td>

                            @if ($released)
                                <td class="min-w-[170px] px-6 py-4">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-700">{{ $r->score }} / {{ $r->total }}</span>
                                        <span class="font-bold {{ $pass ? 'text-emerald-600' : 'text-[#7a1230]' }}">{{ $p }}%</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $pass ? 'bg-emerald-500' : 'bg-[#7a1230]' }}" style="width: {{ $p }}%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $pass ? 'bg-emerald-100 text-emerald-700' : 'bg-[#7a1230]/10 text-[#7a1230]' }}">
                                        {{ $pass ? 'Passed' : 'Failed' }}
                                    </span>
                                </td>
                            @else
                                <td colspan="2" class="px-6 py-4">
                                    <span class="rounded-full bg-[#f6c945]/30 px-3 py-1 text-xs font-semibold text-[#7a5a00]">🔒 Released {{ $r->exam->closes_at->format('M d, g:i A') }}</span>
                                </td>
                            @endif

                            <td class="whitespace-nowrap px-6 py-4 text-slate-400">{{ $r->created_at->format('M d, Y g:i A') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('student.exams.result', $r->exam) }}" class="text-xs font-semibold text-[#7a1230] hover:underline">Details →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">📊</div>
                                <p class="font-semibold text-[#0a1f55]">No results yet.</p>
                                <p class="mt-1 text-sm text-slate-400">Your scores will show up here after you take an exam.</p>
                                <a href="{{ route('student.exams') }}" class="mt-4 inline-block rounded-xl bg-[#7a1230] px-5 py-2 text-sm font-semibold text-white hover:bg-[#5a0c22]">Go to available exams</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
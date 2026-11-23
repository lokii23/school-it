@extends('layouts.portal')

@section('title', 'Exam Result')
@section('live', '10')

@section('content')
<div class="mx-auto max-w-xl"
     x-data="{ p: 0 }"
     x-init="setTimeout(() => p = {{ $scoreVisible ? $percent : 0 }}, 150)">

    <div class="overflow-hidden rounded-3xl bg-white text-center shadow-lg ring-1 ring-slate-200/70">

        <div class="bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] px-6 py-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#f6c945]">{{ $exam->type }} {{ $scoreVisible ? 'result' : 'submitted' }}</p>
            <h2 class="mt-1 text-xl font-bold">{{ $exam->title }}</h2>
            <p class="text-sm text-slate-300">{{ $exam->subject }}</p>
        </div>

        <div class="px-6 py-8">

            @if ($scoreVisible)
                {{-- Score ring --}}
                <div class="relative mx-auto h-44 w-44">
                    <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#eef0f5" stroke-width="10"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke-width="10" stroke-linecap="round"
                                stroke="{{ $passed ? '#10b981' : '#7a1230' }}"
                                stroke-dasharray="339.3"
                                :stroke-dashoffset="339.3 - (339.3 * p / 100)"
                                style="transition: stroke-dashoffset 1.2s ease-out"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-extrabold text-[#0a1f55]">{{ $percent }}%</span>
                        <span class="text-xs text-slate-400">{{ $result->score }} / {{ $result->total }} correct</span>
                    </div>
                </div>

                <div class="mt-6">
                    @if ($passed)
                        <span class="rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-bold text-emerald-700">✓ Passed</span>
                        <p class="mt-3 text-lg font-semibold text-[#0a1f55]">Congratulations, great work! 🎉</p>
                    @else
                        <span class="rounded-full bg-[#7a1230]/10 px-4 py-1.5 text-sm font-bold text-[#7a1230]">Not passed</span>
                        <p class="mt-3 text-lg font-semibold text-[#0a1f55]">Keep going, you’ll do better next time.</p>
                    @endif
                    <p class="mt-1 text-sm text-slate-400">Passing score: {{ $passing }}% · Submitted {{ $result->created_at->format('M d, Y g:i A') }}</p>
                </div>

                <div class="mt-8 grid gap-3 text-sm sm:grid-cols-3">
                    <div class="rounded-xl bg-emerald-50 p-3"><p class="text-xl font-bold text-emerald-700">{{ $result->score }}</p><p class="text-xs text-slate-500">Correct</p></div>
                    <div class="rounded-xl bg-[#7a1230]/5 p-3"><p class="text-xl font-bold text-[#7a1230]">{{ $result->total - $result->score }}</p><p class="text-xs text-slate-500">Wrong / skipped</p></div>
                    <div class="rounded-xl bg-[#0a1f55]/5 p-3"><p class="text-xl font-bold text-[#0a1f55]">{{ $result->total }}</p><p class="text-xs text-slate-500">Total</p></div>
                </div>
            @else
                {{-- Score held back until the exam closes --}}
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-[#f6c945]/25 text-5xl">✅</div>

                <div class="mt-6">
                    <span class="rounded-full bg-[#0a1f55]/10 px-4 py-1.5 text-sm font-bold text-[#0a1f55]">Answers submitted</span>
                    <p class="mt-3 text-lg font-semibold text-[#0a1f55]">Your answers have been recorded.</p>
                    <p class="mt-1 text-sm text-slate-400">Submitted {{ $result->created_at->format('M d, Y g:i A') }}</p>
                </div>

                <div class="mt-6 rounded-xl border-l-4 border-[#f6c945] bg-[#fbf8f1] px-5 py-4 text-left text-sm text-slate-600">
                    <p class="font-semibold text-[#0a1f55]">🔒 Your score is not shown yet.</p>
                    <p class="mt-1 text-xs">
                        Your score and answer review will be released together after the exam closes on
                        <strong class="text-[#0a1f55]">{{ $exam->closes_at->format('M d, Y \a\t g:i A') }}</strong>.
                        Check back then, no action is needed.
                    </p>
                </div>
            @endif

            @if (!is_null($timeTaken))
                <p class="mt-4 text-sm text-slate-500">
                    ⏱ Time taken:
                    <strong class="text-[#0a1f55]">{{ intdiv($timeTaken, 60) }} min {{ $timeTaken % 60 }} sec</strong>
                    @if ($exam->duration_minutes) <span class="text-slate-400">of {{ $exam->duration_minutes }} min</span> @endif
                </p>
            @endif

            @if ($scoreVisible && $canReview)
                <a href="{{ route('student.exams.review', $exam) }}"
                   class="mt-6 block rounded-xl bg-[#7a1230] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
                    Review my answers →
                </a>
            @elseif ($scoreVisible && $reviewOpensAt)
                <p class="mt-6 rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-500">
                    🔒 Answer review opens after the exam closes on
                    <strong class="text-[#0a1f55]">{{ $reviewOpensAt->format('M d, Y g:i A') }}</strong>.
                </p>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('student.exams') }}" class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#12307f]">Back to exams</a>
                <a href="{{ route('dashboard') }}" class="rounded-xl px-6 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection
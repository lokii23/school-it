@extends('layouts.portal')

@section('title', 'Answer Review')

@section('content')
@php
    $items   = collect($result->answers ?? []);
    $total   = $items->count();
    $right   = $items->filter(fn ($i) => ($i['chosen'] ?? null) === ($i['correct'] ?? null))->count();
    $skipped = $items->filter(fn ($i) => ($i['chosen'] ?? null) === null)->count();
    $wrong   = $total - $right - $skipped;
    $percent = $result->total ? (int) round($result->score / $result->total * 100) : 0;
    $pass    = $percent >= $passing;
@endphp

<div class="mx-auto max-w-3xl space-y-6" x-data="{ f: 'all' }">

    <a href="{{ $backUrl }}" class="inline-block text-sm font-semibold text-slate-500 hover:text-slate-700">← Back</a>

    {{-- Header --}}
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-6 text-white shadow-lg">
        <p class="text-xs font-semibold uppercase tracking-wider text-[#f6c945]">Answer review</p>
        <h2 class="mt-1 text-2xl font-bold">{{ $exam->title }}</h2>
        <p class="text-slate-300">
            {{ $exam->subject }}
            @if ($staff) · <span class="text-white">{{ $result->student->name ?? 'Deleted user' }}</span>
                @if ($result->student?->section) ({{ $result->student->section->name }}) @endif
            @endif
        </p>

        <div class="mt-5 flex flex-wrap items-center gap-3">
            <span class="text-4xl font-extrabold">{{ $percent }}%</span>
            <span class="text-sm text-slate-300">{{ $result->score }} / {{ $result->total }} correct</span>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $pass ? 'bg-[#f6c945] text-[#0a1f55]' : 'bg-[#7a1230] text-white' }}">
                {{ $pass ? '✓ Passed' : 'Not passed' }}
            </span>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="rounded-2xl bg-white py-14 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🗒️</div>
            <p class="font-semibold text-[#0a1f55]">No detailed answers were saved for this attempt.</p>
            <p class="mt-1 text-sm text-slate-400">This happens with attempts made before answer review was added, or when time ran out.</p>
        </div>
    @else

        {{-- Filter tabs --}}
        <div class="flex flex-wrap gap-2">
            @foreach ([['all', 'All', $total], ['correct', 'Correct', $right], ['wrong', 'Wrong', $wrong], ['skipped', 'Skipped', $skipped]] as [$key, $label, $count])
                <button type="button" @click="f = '{{ $key }}'"
                        :class="f === '{{ $key }}' ? 'bg-[#0a1f55] text-white shadow' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold transition">
                    {{ $label }} <span class="ml-1 text-xs opacity-70">{{ $count }}</span>
                </button>
            @endforeach
        </div>

        {{-- Questions --}}
        @foreach ($items as $i => $item)
            @php
                $chosen  = $item['chosen'] ?? null;
                $correct = $item['correct'] ?? null;
                $status  = $chosen === null ? 'skipped' : ($chosen === $correct ? 'correct' : 'wrong');
                $badge   = [
                    'correct' => ['✓ Correct', 'bg-emerald-100 text-emerald-700', 'border-emerald-500'],
                    'wrong'   => ['✗ Wrong', 'bg-[#7a1230]/10 text-[#7a1230]', 'border-[#7a1230]'],
                    'skipped' => ['Skipped', 'bg-slate-100 text-slate-500', 'border-slate-300'],
                ][$status];
            @endphp

            <div x-show="f === 'all' || f === '{{ $status }}'" x-transition
                 class="overflow-hidden rounded-2xl border-l-4 {{ $badge[2] }} bg-white shadow-sm ring-1 ring-slate-200/70">
                <div class="p-6">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div class="flex gap-3 text-base font-semibold text-[#0a1f55]">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#0a1f55] text-xs font-bold text-[#f6c945]">{{ $loop->iteration }}</span>
                            <span class="whitespace-pre-line">{{ $item['question'] }}</span>
                        </div>
                        <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span>
                    </div>

                    <div class="space-y-2">
                        @foreach ($item['options'] as $j => $opt)
                            @php
                                $isCorrect = $j === $correct;
                                $isChosen  = $j === $chosen;
                                $box = $isCorrect
                                    ? 'bg-emerald-50 ring-2 ring-emerald-500'
                                    : ($isChosen ? 'bg-[#7a1230]/5 ring-2 ring-[#7a1230]' : 'ring-1 ring-slate-200');
                            @endphp
                            <div class="flex items-center gap-3 rounded-xl p-3 {{ $box }}">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{ $isCorrect ? 'bg-emerald-500 text-white' : ($isChosen ? 'bg-[#7a1230] text-white' : 'bg-slate-100 text-[#0a1f55]') }}">
                                    {{ chr(65 + $j) }}
                                </span>
                                <span class="flex-1 text-sm text-slate-700">{{ $opt }}</span>

                                @if ($isCorrect)
                                    <span class="text-xs font-semibold text-emerald-700">✓ Correct answer</span>
                                @endif
                                @if ($isChosen && ! $isCorrect)
                                    <span class="text-xs font-semibold text-[#7a1230]">{{ $staff ? 'Student’s answer' : 'Your answer' }}</span>
                                @endif
                                @if ($isChosen && $isCorrect)
                                    <span class="text-xs font-semibold text-emerald-700">{{ $staff ? '· chosen' : '· your answer' }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if ($status === 'skipped')
                        <p class="mt-3 text-xs text-slate-400">{{ $staff ? 'The student' : 'You' }} did not answer this question.</p>
                    @endif
                </div>
            </div>
        @endforeach

        <p class="text-center text-xs text-slate-400">
            This review shows the exam exactly as {{ $staff ? 'the student' : 'you' }} saw it, even if the teacher edited it afterwards.
        </p>
    @endif
</div>
@endsection
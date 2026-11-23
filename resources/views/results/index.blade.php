@extends('layouts.portal')

@section('title', 'Results')
@section('live', '8')

@section('content')
@php
    $selectBase = 'rounded-xl border-0 bg-white py-2.5 pl-4 pr-9 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]';
    $oTaken  = (int) ($overall->taken ?? 0);
    $oAvg    = $overall->avg_pct !== null ? round($overall->avg_pct) : null;
    $oRate   = $oTaken ? round(($overall->passed ?? 0) / $oTaken * 100) : null;
    $stateStyle = ['open' => 'bg-emerald-100 text-emerald-700', 'upcoming' => 'bg-[#f6c945]/30 text-[#7a5a00]', 'closed' => 'bg-slate-100 text-slate-500'];
    $typeStyle  = ['exam' => 'bg-[#0a1f55]/10 text-[#0a1f55]', 'quiz' => 'bg-[#f6c945]/30 text-[#7a5a00]'];
@endphp

{{-- Summary --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Exams &amp; quizzes</p>
        <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $examCount }}</p>
    </div>
    <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Submissions</p>
        <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $oTaken }}</p>
    </div>
    <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Average score</p>
        <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $oAvg !== null ? $oAvg . '%' : '—' }}</p>
    </div>
    <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Pass rate <span class="text-xs text-slate-400">(≥ {{ $passing }}%)</span></p>
        <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $oRate !== null ? $oRate . '%' : '—' }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
    <form method="GET" class="flex flex-1 flex-col gap-3 md:flex-row md:flex-wrap">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search exam title or subject…"
               class="w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] md:w-64">

        <select name="section_id" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">All sections</option>
            @foreach ($sections as $s)
                <option value="{{ $s->id }}" @selected((string) request('section_id') === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>

        <select name="type" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">Exams &amp; quizzes</option>
            <option value="exam" @selected(request('type') === 'exam')>Exams only</option>
            <option value="quiz" @selected(request('type') === 'quiz')>Quizzes only</option>
        </select>

        <select name="state" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">Any status</option>
            <option value="open" @selected(request('state') === 'open')>Open now</option>
            <option value="upcoming" @selected(request('state') === 'upcoming')>Upcoming</option>
            <option value="closed" @selected(request('state') === 'closed')>Closed</option>
        </select>

        <button class="rounded-xl bg-[#0a1f55] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#12307f]">Filter</button>
        @if (request()->hasAny(['q', 'section_id', 'type', 'state']))
            <a href="{{ route('results.index') }}" class="self-center text-sm font-medium text-[#7a1230] hover:underline">Clear</a>
        @endif
    </form>

    <a href="{{ route('results.all') }}"
       class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-[#0a1f55] shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
        All submissions →
    </a>
</div>

{{-- Exam cards --}}
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($exams as $e)
        @php
            $st    = $stats->get($e->id);
            $taken = (int) ($st->taken ?? 0);
            $total = (int) ($enrolled[$e->id] ?? 0);
            $avg   = $st && $st->avg_pct !== null ? round($st->avg_pct) : null;
            $rate  = $taken ? round(($st->passed ?? 0) / $taken * 100) : null;
            $prog  = $total ? min(100, round($taken / $total * 100)) : 0;
        @endphp

        <a href="{{ route('results.exam', $e) }}"
           class="group flex flex-col overflow-hidden rounded-2xl border-t-4 border-[#f6c945] bg-white shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-1 hover:shadow-lg">
            <div class="flex-1 p-6">
                <div class="flex items-center justify-between gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $typeStyle[$e->type] ?? 'bg-slate-100 text-slate-600' }}">{{ $e->type }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stateStyle[$e->status] }}">{{ ucfirst($e->status) }}</span>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-[#0a1f55]">{{ $e->title }}</h3>
                <p class="text-sm text-slate-500">{{ $e->subject }} · <span class="font-medium text-[#7a1230]">{{ $e->section->name ?? '—' }}</span></p>

                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-600">Taken</span>
                        <span class="font-bold text-[#0a1f55]">{{ $taken }} / {{ $total }} students</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#f6c945] to-[#d4a017]" style="width: {{ $prog }}%"></div>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-[#fbf8f1] py-2">
                        <p class="text-lg font-bold text-[#0a1f55]">{{ $avg !== null ? $avg . '%' : '—' }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Average</p>
                    </div>
                    <div class="rounded-xl bg-[#fbf8f1] py-2">
                        <p class="text-lg font-bold text-emerald-600">{{ $rate !== null ? $rate . '%' : '—' }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Pass rate</p>
                    </div>
                    <div class="rounded-xl bg-[#fbf8f1] py-2">
                        <p class="text-lg font-bold text-[#7a1230]">{{ max(0, $total - $taken) }}</p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Not yet</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 bg-[#fbf8f1] px-6 py-3 text-center text-sm font-semibold text-[#7a1230] transition group-hover:bg-[#7a1230] group-hover:text-white">
                View results →
            </div>
        </a>
    @empty
        <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70 md:col-span-2 xl:col-span-3">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">📊</div>
            <p class="font-semibold text-[#0a1f55]">No exams found.</p>
            <p class="mt-1 text-sm text-slate-400">{{ request()->hasAny(['q', 'section_id', 'type', 'state']) ? 'Try clearing the filters.' : 'Create an exam first. Results will appear here.' }}</p>
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $exams->links() }}</div>
@endsection
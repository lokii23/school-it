@extends('layouts.portal')

@section('title', 'Results')
@section('live', '8')
<a href="{{ route('results.index') }}" class="mb-4 inline-block text-sm font-semibold text-slate-500 hover:text-slate-700">← Back to exams</a>
@section('content')
@php
    $selectBase = 'rounded-xl border-0 bg-white py-2.5 pl-4 pr-9 text-sm shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#0a1f55]';
    $taken  = (int) ($stats->taken ?? 0);
    $passed = (int) ($stats->passed ?? 0);
    $avg    = $stats->avg_pct !== null ? round($stats->avg_pct) : null;
    $best   = $stats->best_pct !== null ? round($stats->best_pct) : null;
    $rate   = $taken ? round($passed / $taken * 100) : null;
@endphp

{{-- Summary --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border-t-4 border-[#0a1f55] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Submissions</p>
        <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $taken }}</p>
    </div>
    <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Average score</p>
        <p class="mt-1 text-3xl font-bold text-[#0a1f55]">{{ $avg !== null ? $avg . '%' : '—' }}</p>
    </div>
    <div class="rounded-2xl border-t-4 border-emerald-500 bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Pass rate <span class="text-xs text-slate-400">(≥ {{ $passing }}%)</span></p>
        <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $rate !== null ? $rate . '%' : '—' }}</p>
    </div>
    <div class="rounded-2xl border-t-4 border-[#7a1230] bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
        <p class="text-sm text-slate-500">Highest score</p>
        <p class="mt-1 text-3xl font-bold text-[#7a1230]">{{ $best !== null ? $best . '%' : '—' }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
    <form method="GET" class="flex flex-1 flex-col gap-3 md:flex-row md:flex-wrap">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search student name…"
               class="w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] md:w-56">

        <select name="exam_id" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">All exams</option>
            @foreach ($exams as $e)
                <option value="{{ $e->id }}" @selected((string) request('exam_id') === (string) $e->id)>{{ Str::limit($e->title, 35) }}</option>
            @endforeach
        </select>

        <select name="section_id" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">All sections</option>
            @foreach ($sections as $s)
                <option value="{{ $s->id }}" @selected((string) request('section_id') === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>

        <select name="status" onchange="this.form.submit()" class="{{ $selectBase }}">
            <option value="">Passed &amp; failed</option>
            <option value="passed" @selected(request('status') === 'passed')>Passed only</option>
            <option value="failed" @selected(request('status') === 'failed')>Failed only</option>
        </select>

        <button class="rounded-xl bg-[#0a1f55] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#12307f]">Filter</button>
        @if (request()->hasAny(['q', 'exam_id', 'section_id', 'status']))
            <a href="{{ route('results.all') }}" class="self-center text-sm font-medium text-[#7a1230] hover:underline">Clear</a>
        @endif
    </form>

    <a href="{{ route('results.export', request()->query()) }}"
       class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#f6c945] px-5 py-2.5 text-sm font-semibold text-[#0a1f55] shadow transition hover:bg-[#fde9a8]">
        ⬇ Export CSV
    </a>
</div>

{{-- Table --}}
<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                <tr>
                    <th class="px-6 py-3.5 font-semibold">Student</th>
                    <th class="px-6 py-3.5 font-semibold">Exam</th>
                    <th class="px-6 py-3.5 font-semibold">Score</th>
                    <th class="px-6 py-3.5 font-semibold">Status</th>
                    <th class="px-6 py-3.5 font-semibold">Date taken</th>
                    <th class="px-6 py-3.5 text-right font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($results as $r)
                    @php
                        $p = $r->total ? (int) round($r->score / $r->total * 100) : 0;
                        $pass = $p >= $passing;
                    @endphp
                    <tr class="transition hover:bg-[#fbf8f1]">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0a1f55] text-sm font-bold text-[#f6c945]">
                                    {{ strtoupper(substr($r->student->name ?? '?', 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-[#0a1f55]">{{ $r->student->name ?? 'Deleted user' }}</p>
                                    <p class="text-xs text-slate-400">{{ $r->student->section->name ?? 'No section' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-800">{{ $r->exam->title ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $r->exam->subject ?? '' }}</p>
                        </td>
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
                        <td class="whitespace-nowrap px-6 py-4 text-slate-400">{{ $r->created_at->format('M d, Y g:i A') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('results.show', $r) }}"
                                class="rounded-lg bg-[#0a1f55]/10 px-3.5 py-1.5 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">
                                    View answers
                                </a>
                                <form method="POST" action="{{ route('results.destroy', $r) }}"
                                    onsubmit="return confirm('Reset this attempt for {{ addslashes($r->student->name ?? 'this student') }}? Their score will be deleted and they can retake the exam.')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg bg-[#7a1230]/10 px-3.5 py-1.5 text-xs font-semibold text-[#7a1230] transition hover:bg-[#7a1230] hover:text-white">
                                        Allow retake
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
                            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">📊</div>
                            <p class="font-semibold text-[#0a1f55]">No results found.</p>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ request()->hasAny(['q', 'exam_id', 'section_id', 'status']) ? 'Try clearing the filters.' : 'Results appear here once students submit exams.' }}
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $results->links() }}</div>
@endsection
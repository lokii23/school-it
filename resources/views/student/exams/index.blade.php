@extends('layouts.portal')

@section('title', 'Available Exams')
@section('live', '5')

@section('content')
@php
    $typeStyle = [
        'exam' => 'bg-[#0a1f55]/10 text-[#0a1f55]',
        'quiz' => 'bg-[#f6c945]/30 text-[#7a5a00]',
    ];
@endphp

<div x-data="{ tab: 'available' }">

    @unless ($hasSection)
        <div class="rounded-2xl border-l-4 border-[#f6c945] bg-white p-8 text-center shadow-sm ring-1 ring-slate-200/70">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🏫</div>
            <p class="font-semibold text-[#0a1f55]">{{ $irregular ? 'You’re not enrolled in any subject yet.' : 'You’re not assigned to a section yet.' }}</p>
            <p class="mt-1 text-sm text-slate-500">Please ask your adviser or the admin to {{ $irregular ? 'enroll you in your subjects' : 'assign your section' }} so your exams can appear here.</p>
        </div>
    @else

        {{-- Tabs --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                @foreach ([['available', 'Available', $available->count(), 'bg-[#f6c945] text-[#0a1f55]'], ['completed', 'Completed', $completed->count(), 'bg-slate-100 text-slate-500'], ['missed', 'Missed', $missed->count(), 'bg-[#7a1230]/10 text-[#7a1230]']] as [$key, $label, $count, $badge])
                    <button @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-[#0a1f55] text-white shadow' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                            class="rounded-full px-5 py-2 text-sm font-semibold transition">
                        {{ $label }}
                        <span class="ml-1 rounded-full px-2 text-xs font-bold {{ $badge }}">{{ $count }}</span>
                    </button>
                @endforeach
            </div>
            <span class="rounded-full bg-[#0a1f55]/10 px-4 py-1.5 text-sm font-semibold text-[#0a1f55]">
                @if ($irregular) Irregular student @else Section: {{ $section->name ?? '—' }} @endif
            </span>
        </div>

        {{-- AVAILABLE --}}
        <div x-show="tab === 'available'" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($available as $e)
                @php
                    $upcoming   = $e->status === 'upcoming';
                    $inProgress = $attempts->has($e->id);
                @endphp
                <div class="flex flex-col overflow-hidden rounded-2xl border-t-4 {{ $upcoming ? 'border-slate-300' : 'border-[#f6c945]' }} bg-white shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex-1 p-6">
                        <div class="flex items-center justify-between">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $typeStyle[$e->type] ?? 'bg-slate-100 text-slate-600' }}">{{ $e->type }}</span>
                            @if ($inProgress)
                                <span class="rounded-full bg-[#7a1230] px-2.5 py-0.5 text-xs font-semibold text-white">In progress</span>
                            @elseif ($upcoming)
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Upcoming</span>
                            @elseif ($e->created_at->isToday())
                                <span class="rounded-full bg-[#7a1230]/10 px-2 py-0.5 text-xs font-semibold text-[#7a1230]">New</span>
                            @endif
                        </div>

                        <h3 class="mt-4 text-lg font-semibold text-[#0a1f55]">{{ $e->title }}</h3>
                        <p class="text-sm text-slate-500">{{ $e->subject }}</p>

                        <dl class="mt-5 space-y-2 text-sm text-slate-600">
                            <div class="flex justify-between"><dt class="text-slate-400">Questions</dt><dd class="font-semibold">{{ $e->questions_count }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-400">Time limit</dt><dd class="font-semibold">{{ $e->duration_minutes ? $e->duration_minutes . ' minutes' : 'No limit' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-400">Teacher</dt><dd class="font-semibold">{{ $e->teacher->name ?? '—' }}</dd></div>
                            @if ($upcoming)
                                <div class="flex justify-between"><dt class="text-slate-400">Opens</dt><dd class="font-semibold text-[#7a5a00]">{{ $e->opens_at->format('M d, g:i A') }}</dd></div>
                            @endif
                            @if ($e->closes_at)
                                <div class="flex justify-between"><dt class="text-slate-400">Closes</dt><dd class="font-semibold text-[#7a1230]">{{ $e->closes_at->format('M d, g:i A') }}</dd></div>
                            @endif
                        </dl>
                    </div>

                    <div class="border-t border-slate-100 bg-[#fbf8f1] p-4">
                        @if ($upcoming)
                            <span class="block cursor-not-allowed rounded-xl bg-slate-200 py-2.5 text-center text-sm font-semibold text-slate-500">
                                Opens {{ $e->opens_at->diffForHumans() }}
                            </span>
                        @else
                            <a href="{{ route('student.exams.access', $e) }}"
                               class="block rounded-xl bg-[#7a1230] py-2.5 text-center text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">
                                {{ $inProgress ? 'Resume ' . $e->type . ' →' : 'Start ' . $e->type . ' →' }}
                            </a>
                            <p class="mt-2 text-center text-[11px] text-slate-400">
                                @if ($e->duration_minutes)
                                    The timer starts when you begin and keeps running if you leave.
                                @else
                                    One attempt only. Answers can’t be changed after submitting.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-white py-16 text-center shadow-sm ring-1 ring-slate-200/70 md:col-span-2 xl:col-span-3">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">🎉</div>
                    <p class="font-semibold text-[#0a1f55]">You’re all caught up!</p>
                    <p class="mt-1 text-sm text-slate-400">No exams are waiting for you right now.</p>
                </div>
            @endforelse
        </div>

        {{-- COMPLETED --}}
        <div x-show="tab === 'completed'" style="display:none" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b-2 border-[#f6c945] bg-[#0a1f55] text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-6 py-3.5 font-semibold">Exam</th>
                            <th class="px-6 py-3.5 font-semibold">Score</th>
                            <th class="px-6 py-3.5 font-semibold">Result</th>
                            <th class="px-6 py-3.5 font-semibold">Date taken</th>
                            <th class="px-6 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($completed as $r)
                            @php
                                $released = $r->exam->scoreVisible();
                                $p = $r->total ? round($r->score / $r->total * 100) : 0;
                            @endphp
                            <tr class="transition hover:bg-[#fbf8f1]">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-[#0a1f55]">{{ $r->exam->title }}</p>
                                    <p class="text-xs text-slate-400">{{ $r->exam->subject }}</p>
                                </td>

                                @if ($released)
                                    <td class="px-6 py-4 font-medium">{{ $r->score }} / {{ $r->total }} <span class="text-slate-400">({{ $p }}%)</span></td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $p >= $passing ? 'bg-emerald-100 text-emerald-700' : 'bg-[#7a1230]/10 text-[#7a1230]' }}">
                                            {{ $p >= $passing ? 'Passed' : 'Failed' }}
                                        </span>
                                    </td>
                                @else
                                    <td colspan="2" class="px-6 py-4">
                                        <span class="rounded-full bg-[#f6c945]/30 px-3 py-1 text-xs font-semibold text-[#7a5a00]">🔒 Released {{ $r->exam->closes_at->format('M d, g:i A') }}</span>
                                    </td>
                                @endif

                                <td class="whitespace-nowrap px-6 py-4 text-slate-400">{{ $r->created_at->format('M d, Y g:i A') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('student.exams.result', $r->exam) }}" class="text-xs font-semibold text-[#7a1230] hover:underline">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-14 text-center text-slate-400">You haven’t taken any exams yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MISSED --}}
        <div x-show="tab === 'missed'" style="display:none" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <ul class="divide-y divide-slate-100">
                @forelse ($missed as $e)
                    <li class="flex flex-col gap-1 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-[#0a1f55]">{{ $e->title }}</p>
                            <p class="text-xs text-slate-400">{{ $e->subject }} · {{ ucfirst($e->type) }}</p>
                        </div>
                        <span class="rounded-full bg-[#7a1230]/10 px-3 py-1 text-xs font-semibold text-[#7a1230]">
                            Closed {{ $e->closes_at->format('M d, Y g:i A') }}
                        </span>
                    </li>
                @empty
                    <li class="py-14 text-center text-slate-400">You haven’t missed any exams. 👏</li>
                @endforelse
            </ul>
            @if ($missed->isNotEmpty())
                <p class="border-t border-slate-100 bg-[#fbf8f1] px-6 py-3 text-xs text-slate-500">Missed an exam? Ask your teacher to reopen it for you.</p>
            @endif
        </div>
    @endunless
</div>
@endsection
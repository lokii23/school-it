@extends('layouts.portal')

@section('title', 'Dashboard')
@section('live', '10')

@section('content')
@php
    // Blade renders this section BEFORE the layout, so the layout's variables
    // are not available here. Define what this page needs.
    $user    = auth()->user();
    $academy = 'Professional Academy of the Philippines';
    $college = 'College of Computer Studies';

    $ic = [
        'users' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'doc'   => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        'bell'  => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
        'grid'  => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        'cap'   => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342',
        'pen'   => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'chart' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'star'  => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
        'plus'  => 'M12 6v12m6-6H6',
    ];

    $statIcon = [
        'Users' => 'users', 'Exams & Quizzes' => 'doc', 'Announcements' => 'bell',
        'Sections' => 'grid', 'Students' => 'cap', 'My Exams' => 'pen',
        'Available Exams' => 'clock', 'Results' => 'chart', 'Average Grade' => 'star',
    ];

    $tones = [
        'bg-[#0a1f55]/10 text-[#0a1f55]',
        'bg-[#f6c945]/30 text-[#8a6a00]',
        'bg-[#7a1230]/10 text-[#7a1230]',
    ];

    $actions = match ($user->role) {
        'admin'   => ['announcements.index' => 'Post announcement', 'users.index' => 'Manage users', 'exams.index' => 'Exams'],
        'teacher' => ['exams.index' => 'Create exam', 'grades.index' => 'Manage grades', 'sections.index' => 'View sections'],
        default   => ['student.exams' => 'Take an exam', 'student.results' => 'View results', 'student.grades' => 'My grades'],
    };
@endphp

<div class="space-y-8">

    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-8 text-white shadow-xl">
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-[#f6c945]/20 blur-2xl"></div>
        <div class="absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-[#7a1230]/50 blur-2xl"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-5">
                <x-avatar :user="$user" size="h-16 w-16" text="text-xl font-extrabold" ring="ring-4 ring-[#f6c945]/60" />
                <div>
                    <p class="text-sm font-medium text-[#f6c945]">{{ now()->format('l, F j, Y') }}</p>
                    <h2 class="mt-1 text-3xl font-bold tracking-tight">Welcome back, {{ $user->first_name ?: $user->name }} 👋</h2>
                    <p class="mt-1 text-slate-300">{{ $college }} · {{ $academy }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                @foreach ($actions as $route => $label)
                    @if (Route::has($route))
                        <a href="{{ route($route) }}"
                           class="rounded-xl bg-white/10 px-4 py-2.5 text-sm font-medium ring-1 ring-white/20 backdrop-blur transition hover:bg-[#f6c945] hover:text-[#0a1f55]">
                            {{ $label }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        @foreach ($stats as $label => $value)
            <div class="rounded-2xl border-t-4 border-[#f6c945] bg-white p-6 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-4xl font-bold tracking-tight text-[#0a1f55]">{{ $value }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $tones[$loop->index % 3] }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic[$statIcon[$label] ?? 'plus'] }}" />
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Announcements --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 xl:col-span-2">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-[#0a1f55]">Announcements</h2>
                @if ($user->role === 'admin' && Route::has('announcements.index'))
                    <a href="{{ route('announcements.index') }}" class="text-sm font-semibold text-[#7a1230] hover:text-[#5a0c22]">Manage →</a>
                @endif
            </div>

            <div class="relative space-y-6 before:absolute before:left-[19px] before:top-2 before:h-[calc(100%-1rem)] before:w-px before:bg-slate-200">
                @forelse ($announcements as $a)
                    <div class="relative flex gap-4">
                        <div class="z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0a1f55] text-sm font-bold text-[#f6c945] ring-4 ring-white">
                            {{ strtoupper(substr($a->title, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1 pb-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-[#0a1f55]">{{ $a->title }}</h3>
                                @if ($a->created_at->isToday())
                                    <span class="rounded-full bg-[#f6c945]/30 px-2 py-0.5 text-xs font-semibold text-[#7a5a00]">New</span>
                                @endif
                            </div>
                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $a->body }}</p>
                            <p class="mt-1.5 text-xs text-slate-400">{{ $a->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-10 text-center text-slate-400">No announcements yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Role panel --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
            <h2 class="mb-4 text-lg font-semibold text-[#0a1f55]">{{ $panel['title'] }}</h2>

            <ul class="divide-y divide-slate-100">
                @forelse ($panel['items'] as $item)
                    <li class="flex items-center justify-between gap-3 py-3">
                        @if ($panel['type'] === 'users')
                            <div class="flex min-w-0 items-center gap-3">
                                <x-avatar :user="$item" size="h-9 w-9" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $item->name }}</p>
                                    <p class="truncate text-xs text-slate-400">{{ $item->email }}</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-[#0a1f55]/10 px-2.5 py-0.5 text-xs font-medium capitalize text-[#0a1f55]">{{ $item->role }}</span>

                        @elseif ($panel['type'] === 'results')
                            @php $pct = $item->total ? round($item->score / $item->total * 100) : 0; @endphp
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $item->student->name ?? '—' }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $item->exam->title ?? 'Deleted exam' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $pct >= 75 ? 'bg-emerald-100 text-emerald-700' : 'bg-[#7a1230]/10 text-[#7a1230]' }}">{{ $pct }}%</span>

                        @else
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $item->title }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $item->subject }} · {{ $item->questions_count }} questions</p>
                            </div>
                            @if (Route::has('student.exams.access'))
                                <a href="{{ route('student.exams.access', $item) }}"
                                   class="rounded-lg bg-[#7a1230] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#5a0c22]">Take</a>
                            @endif
                        @endif
                    </li>
                @empty
                    <li class="py-10 text-center text-sm text-slate-400">Nothing to show yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
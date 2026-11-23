@php
    $user    = auth()->user();
    $academy = 'Professional Academy of the Philippines';
    $college = 'College of Computer Studies';
    $app     = 'CCS Portal';
    $logo    = asset('images/ccs-logo.png');

    $ic = [
        'home'    => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'bell'    => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
        'users'   => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'grid'    => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        'doc'     => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        'chart'   => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'star'    => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
        'clock'   => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'profile' => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z',
        'book' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
        'news' => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z',
        ];

    $nav = match ($user->role) {
        'admin' => [
            ['Dashboard', 'dashboard', 'home'], ['Announcements', 'announcements.index', 'bell'],
            ['Users', 'users.index', 'users'], ['Sections', 'sections.index', 'grid'],
            ['Exams & Quizzes', 'exams.index', 'doc'], ['Results', 'results.index', 'chart'],
            ['Grades', 'grades.index', 'star'], 
            ['Subjects', 'subjects.index', 'book'],
            ['Irregular Students', 'irregular.index', 'users'],
            ['News Feed', 'feed.index', 'news'],['My Profile', 'profile.edit', 'profile'],
        ],
        'teacher' => [
            ['Dashboard', 'dashboard', 'home'], ['Create Exams', 'exams.index', 'doc'],
            ['Section Management', 'sections.index', 'grid'], ['Grade Management', 'grades.index', 'star'],
            ['Results', 'results.index', 'chart'], 
            ['News Feed', 'feed.index', 'news'],['My Profile', 'profile.edit', 'profile'],
        ],
        default => [
            ['Dashboard', 'dashboard', 'home'], ['Available Exams', 'student.exams', 'clock'],
            ['Results', 'student.results', 'chart'], ['My Grades', 'student.grades', 'star'],
            
            ['News Feed', 'feed.index', 'news'],['My Profile', 'profile.edit', 'profile'],
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal') · {{ $app }}</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#fbf8f1] font-sans text-slate-800 antialiased">
<div x-data="{ sidebar: false }" class="min-h-screen">

    <div x-show="sidebar" x-transition.opacity @click="sidebar = false"
         class="fixed inset-0 z-30 bg-[#071640]/60 lg:hidden" style="display:none"></div>

    {{-- SIDEBAR --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-[#071640] text-slate-300 transition-transform lg:translate-x-0">
        <div class="flex h-20 items-center gap-3 border-b border-[#f6c945]/20 px-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white p-1 ring-2 ring-[#f6c945]">
                <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain">
            </div>
            <div class="min-w-0 leading-tight">
                <span class="block truncate text-base font-semibold text-white">{{ $app }}</span>
                <span class="block truncate text-[11px] text-[#f6c945]">{{ $academy }}</span>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Menu</p>
            @foreach ($nav as [$label, $route, $icon])
                @continue(! Route::has($route))
                @php
                    $pattern = str_ends_with($route, '.index') ? Str::beforeLast($route, '.index') . '.*' : $route . '*';
                    $active = request()->routeIs($pattern);
                @endphp
                <a href="{{ route($route) }}"
                   class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ $active ? 'bg-[#f6c945]/15 text-white ring-1 ring-[#f6c945]/40' : 'hover:bg-white/5 hover:text-white' }}">
                    <svg class="h-5 w-5 {{ $active ? 'text-[#f6c945]' : 'text-slate-500 group-hover:text-[#f6c945]' }}"
                         fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic[$icon] }}" />
                    </svg>
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-[#f6c945]/20 p-4">
            <div class="flex items-center gap-3">
                <x-avatar :user="$user" size="h-9 w-9" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                    <p class="text-xs capitalize text-[#f6c945]">{{ $user->role }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- MAIN --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b-2 border-[#f6c945] bg-white/90 px-4 backdrop-blur sm:px-8">
            <div class="flex items-center gap-3">
                <button @click="sidebar = true" class="rounded-lg p-2 text-[#0a1f55] hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>
                <div class="leading-tight">
                    <h1 class="text-lg font-semibold text-[#0a1f55]">@yield('title', 'Portal')</h1>
                    <p class="hidden text-xs text-slate-400 sm:block">{{ $college }}</p>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                @include('partials.notification-bell')

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 hover:bg-slate-100">
                        <x-avatar :user="$user" size="h-8 w-8" />
                        <span class="hidden text-sm font-medium sm:block">{{ $user->name }}</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition style="display:none"
                        class="absolute right-0 mt-2 w-48 rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">My Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full px-4 py-2 text-left text-sm text-[#7a1230] hover:bg-slate-50">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-4 sm:p-8">
            {{-- Flash message --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition
                     class="mb-6 flex items-center justify-between rounded-xl border-l-4 border-[#f6c945] bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-slate-200">
                    <span class="font-medium text-[#0a1f55]">✓ {{ session('success') }}</span>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition
                    class="mb-6 flex items-center justify-between rounded-xl border-l-4 border-[#7a1230] bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-slate-200">
                    <span class="font-medium text-[#7a1230]">⚠ {{ session('error') }}</span>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
            @endif

            <div id="live-main" data-live="@yield('live')">
                @yield('content')
            </div>
        </main>
    </div>
</div>
@include('partials.confirm-modal')
@include('partials.notification-toasts')
@include('partials.live-refresh')
</body>
</html>
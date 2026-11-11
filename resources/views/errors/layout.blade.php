@php $logo = asset('images/ccs-logo.png'); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title')</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    {{-- Guarded so an error page can never fail because assets aren't built --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .floaty { animation: floaty 4s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { .floaty { animation: none; } }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] font-sans text-slate-800 antialiased">

<div class="pointer-events-none fixed -right-24 -top-24 h-96 w-96 rounded-full bg-[#f6c945]/20 blur-3xl"></div>
<div class="pointer-events-none fixed -bottom-24 left-1/4 h-96 w-96 rounded-full bg-[#7a1230]/50 blur-3xl"></div>

<div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-10 text-center">

    <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 text-white">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg ring-2 ring-[#f6c945]">
            <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain">
        </div>
        <div class="text-left leading-tight">
            <span class="block text-sm font-semibold">Professional Academy of the Philippines</span>
            <span class="block text-xs text-[#f6c945]">College of Computer Studies</span>
        </div>
    </a>

    <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="h-1.5 bg-gradient-to-r from-[#f6c945] via-[#d4a017] to-[#7a1230]"></div>

        <div class="px-8 py-10">
            <div class="floaty mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#f6c945]/25 text-3xl">@yield('icon')</div>

            <p class="bg-gradient-to-r from-[#0a1f55] to-[#7a1230] bg-clip-text text-7xl font-extrabold leading-none tracking-tight text-transparent">@yield('code')</p>
            <h1 class="mt-3 text-2xl font-bold text-[#0a1f55]">@yield('heading')</h1>
            <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-500">@yield('message')</p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a href="javascript:history.back()"
                       class="rounded-xl px-6 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">← Go back</a>

                    @auth
                        <a href="{{ route(auth()->user()->homeRoute()) }}"
                           class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">Back to dashboard</a>
                    @else
                        <a href="{{ url('/') }}"
                           class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">Go to home page</a>
                    @endauth
                @endif
            </div>
        </div>
    </div>

    <p class="mt-6 text-xs text-slate-400">© {{ date('Y') }} Professional Academy of the Philippines</p>
</div>
</body>
</html>
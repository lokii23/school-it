@php
    $academy = 'Professional Academy of the Philippines';
    $college = 'College of Computer Studies';
    $logo    = asset('images/ccs-logo.png');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · CCS Portal</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] font-sans text-slate-800 antialiased">

<div class="pointer-events-none fixed -right-24 -top-24 h-96 w-96 rounded-full bg-[#f6c945]/20 blur-3xl"></div>
<div class="pointer-events-none fixed -bottom-24 left-1/4 h-96 w-96 rounded-full bg-[#7a1230]/50 blur-3xl"></div>

<div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-10">

    <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 text-white">
        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg ring-2 ring-[#f6c945]">
            <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain">
        </div>
        <div class="leading-tight">
            <span class="block font-semibold">{{ $academy }}</span>
            <span class="block text-sm text-[#f6c945]">{{ $college }}</span>
        </div>
    </a>

    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="h-1.5 bg-gradient-to-r from-[#f6c945] via-[#d4a017] to-[#7a1230]"></div>

        <div class="p-8">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945]/25 text-2xl">@yield('icon', '🔑')</div>
                <h1 class="text-2xl font-bold tracking-tight text-[#0a1f55]">@yield('heading')</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">@yield('subheading')</p>
            </div>

            @if (session('status') && session('status') !== 'verification-link-sent')
                <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <p class="mt-6 text-xs text-slate-400">© {{ date('Y') }} {{ $academy }}</p>
</div>
</body>
</html>
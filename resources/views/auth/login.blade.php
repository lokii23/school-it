@php
    $academy = 'Professional Academy of the Philippines';
    $college = 'College of Computer Studies';
    $app     = 'CCS Portal';
    $logo    = asset('images/ccs-logo.png');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ $app }}</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#fbf8f1] font-sans text-slate-800 antialiased">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Brand panel --}}
    <div class="relative hidden overflow-hidden bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-[#f6c945]/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/4 h-72 w-72 rounded-full bg-[#7a1230]/50 blur-3xl"></div>

        <div class="relative flex items-center gap-3">
            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg ring-2 ring-[#f6c945]">
                <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain">
            </div>
            <div class="leading-tight">
                <span class="block text-lg font-semibold">{{ $academy }}</span>
                <span class="block text-sm text-[#f6c945]">{{ $college }}</span>
            </div>
        </div>

        <div class="relative">
            <h1 class="text-4xl font-extrabold leading-tight tracking-tight">
                Code. Learn. <span class="text-[#f6c945]">Excel.</span>
            </h1>
            <p class="mt-4 max-w-md text-lg text-slate-300">
                Announcements, exams, quizzes, results and grades, all in one place for admins, teachers and students.
            </p>
            <div class="mt-8 flex flex-wrap gap-2 text-sm">
                @foreach (['Announcements', 'Online Exams', 'Quizzes', 'Results', 'My Grades'] as $f)
                    <span class="rounded-full bg-white/10 px-3 py-1 ring-1 ring-[#f6c945]/30">{{ $f }}</span>
                @endforeach
            </div>
        </div>

        <p class="relative text-sm text-slate-400">© {{ date('Y') }} {{ $academy }}</p>
    </div>

    {{-- Form --}}
    <div class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md" x-data="{ show: false }">

            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <img src="{{ $logo }}" alt="Logo" class="h-12 w-12 object-contain">
                <div class="leading-tight">
                    <span class="block font-semibold text-[#0a1f55]">{{ $academy }}</span>
                    <span class="block text-xs text-[#7a1230]">{{ $college }}</span>
                </div>
            </div>

            <h2 class="text-3xl font-bold tracking-tight text-[#0a1f55]">Welcome back 👋</h2>
            <div class="mt-3 h-1 w-14 rounded-full bg-[#f6c945]"></div>
            <p class="mt-3 text-slate-500">Sign in to continue to your dashboard.</p>

            @if (session('status'))
                <div class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           placeholder="you@school.edu"
                           class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('email') ring-[#7a1230] @enderror">
                    @error('email') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-[#7a1230] hover:text-[#5a0c22]">Forgot password?</a>
                        @endif
                    </div>
                    <div class="relative">
                        <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full rounded-xl border-0 bg-white py-3 pl-4 pr-14 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('password') ring-[#7a1230] @enderror">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-4 text-sm font-medium text-slate-400 hover:text-[#0a1f55]"
                                x-text="show ? 'Hide' : 'Show'">Show</button>
                    </div>
                    @error('password') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#0a1f55] focus:ring-[#0a1f55]">
                    Remember me
                </label>

                <button type="submit"
                        class="w-full rounded-xl bg-[#0a1f55] px-4 py-3 font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f] focus:outline-none focus:ring-2 focus:ring-[#f6c945] focus:ring-offset-2">
                    Sign in
                </button>
            </form>

            @if (Route::has('register'))
                <p class="mt-8 text-center text-sm text-slate-500">
                    New student?
                    <a href="{{ route('register') }}" class="font-semibold text-[#7a1230] hover:text-[#5a0c22]">Create an account</a>
                </p>
            @endif
        </div>
    </div>
</div>
</body>
</html>
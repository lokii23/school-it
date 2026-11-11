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
    <title>Create account · {{ $app }}</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#fbf8f1] font-sans text-slate-800 antialiased">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Brand panel --}}
    <div class="relative hidden overflow-hidden bg-gradient-to-br from-[#5a0c22] via-[#7a1230] to-[#0a1f55] p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-[#f6c945]/25 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/4 h-72 w-72 rounded-full bg-[#12307f]/60 blur-3xl"></div>

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
                Start your journey at <span class="text-[#f6c945]">CCS</span>.
            </h1>
            <p class="mt-4 max-w-md text-lg text-rose-100">
                Create your account to take exams, view results and track your grades.
            </p>
            <ul class="mt-8 space-y-3 text-rose-50">
                @foreach (['Take exams and quizzes online', 'View your results instantly', 'Track your grades each semester'] as $point)
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#f6c945] text-xs font-bold text-[#0a1f55]">✓</span>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="relative text-sm text-rose-200">© {{ date('Y') }} {{ $academy }}</p>
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

            <h2 class="text-3xl font-bold tracking-tight text-[#0a1f55]">Create your student account</h2>
            <div class="mt-3 h-1 w-14 rounded-full bg-[#f6c945]"></div>
            <p class="mt-3 text-slate-500">Fill in your details to get started.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="mb-1.5 block text-sm font-medium text-slate-700">First name</label>
                        <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name"
                            placeholder="Juan"
                            class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('first_name') ring-[#7a1230] @enderror">
                        @error('first_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="middle_name" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Middle name <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name') }}" autocomplete="additional-name"
                            placeholder="Santos"
                            class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('middle_name') ring-[#7a1230] @enderror">
                        @error('middle_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="last_name" class="mb-1.5 block text-sm font-medium text-slate-700">Last name</label>
                    <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"
                        placeholder="Dela Cruz"
                        class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('last_name') ring-[#7a1230] @enderror">
                    @error('last_name') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                           placeholder="you@professians"
                           class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('email') ring-[#7a1230] @enderror">
                    @error('email') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="new-password"
                               placeholder="••••••••"
                               class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('password') ring-[#7a1230] @enderror">
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label>
                        <input id="password_confirmation" :type="show ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                               placeholder="••••••••"
                               class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]">
                    </div>
                </div>
                @error('password') <p class="-mt-3 text-sm text-[#7a1230]">{{ $message }}</p> @enderror

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" x-model="show" class="rounded border-slate-300 text-[#0a1f55] focus:ring-[#0a1f55]">
                    Show passwords
                </label>

                <button type="submit"
                        class="w-full rounded-xl bg-[#7a1230] px-4 py-3 font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22] focus:outline-none focus:ring-2 focus:ring-[#f6c945] focus:ring-offset-2">
                    Create account
                </button>

                <p class="text-center text-xs text-slate-400">After registering, your adviser or admin will assign your section.</p>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already registered?
                <a href="{{ route('login') }}" class="font-semibold text-[#7a1230] hover:text-[#5a0c22]">Sign in</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
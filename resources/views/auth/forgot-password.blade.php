@extends('layouts.auth')

@section('title', 'Forgot password')
@section('icon', '🔑')
@section('heading', 'Forgot your password?')
@section('subheading', 'Enter your email address and we’ll send you a link to choose a new password.')

@section('content')
<form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ busy: false }" @submit="busy = true">
    @csrf

    <div>
        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
               placeholder="you@school.edu"
               class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('email') ring-[#7a1230] @enderror">
        @error('email') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
    </div>

    <button type="submit" :disabled="busy"
            class="w-full rounded-xl bg-[#0a1f55] py-3 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f] disabled:opacity-60">
        Email password reset link
    </button>

    <a href="{{ route('login') }}" class="block text-center text-sm font-semibold text-[#7a1230] hover:text-[#5a0c22]">← Back to sign in</a>
</form>
@endsection
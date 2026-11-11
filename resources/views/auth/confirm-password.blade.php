@extends('layouts.auth')

@section('title', 'Confirm password')
@section('icon', '🛡️')
@section('heading', 'Confirm your password')
@section('subheading', 'This is a secure area. Please confirm your password before continuing.')

@section('content')
<form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
    @csrf

    <div>
        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
        <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
               placeholder="••••••••"
               class="w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55] @error('password') ring-[#7a1230] @enderror">
        @error('password') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
    </div>

    <button type="submit"
            class="w-full rounded-xl bg-[#0a1f55] py-3 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">
        Confirm
    </button>
</form>
@endsection
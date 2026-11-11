@extends('layouts.auth')

@section('title', 'Verify email')
@section('icon', '✉️')
@section('heading', 'Check your email')
@section('subheading', 'We sent a verification link to your email address. Click it to activate your account. Didn’t get it? We can send another.')

@section('content')
@if (session('status') === 'verification-link-sent')
    <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
        A new verification link has been sent to your email address.
    </div>
@endif

<div class="space-y-3">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit"
                class="w-full rounded-xl bg-[#0a1f55] py-3 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full rounded-xl py-2.5 text-sm font-semibold text-[#7a1230] hover:bg-[#7a1230]/5">Log out</button>
    </form>
</div>
@endsection
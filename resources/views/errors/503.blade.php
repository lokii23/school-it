@extends('errors.layout')

@section('code', '503')
@section('title', 'Under maintenance')
@section('icon', '🔧')
@section('heading', 'We’ll be right back')
@section('message', $exception->getMessage() ?: 'The portal is undergoing scheduled maintenance. Please check back shortly.')

@section('actions')
    <a href="javascript:location.reload()"
       class="rounded-xl bg-[#0a1f55] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">Try again</a>
@endsection
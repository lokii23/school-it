@extends('errors.layout')

@section('code', '419')
@section('title', 'Page expired')
@section('icon', '⏱️')
@section('heading', 'Your session expired')
@section('message', 'For your security, this page timed out. Please sign in again and retry.')

@section('actions')
    <a href="{{ route('login') }}"
       class="rounded-xl bg-[#7a1230] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22]">Sign in again</a>
    <a href="{{ url('/') }}"
       class="rounded-xl px-6 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50">Home page</a>
@endsection
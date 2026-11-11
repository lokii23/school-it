@extends('layouts.portal')

@section('title', 'Enter Passkey')

@section('content')
<div class="mx-auto max-w-md">
    <div class="overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-slate-200/70">

        <div class="bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] px-6 py-6 text-center text-white">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#f6c945] text-2xl">🔐</div>
            <p class="text-xs font-semibold uppercase tracking-wider text-[#f6c945]">{{ $exam->type }} passkey required</p>
            <h2 class="mt-1 text-xl font-bold">{{ $exam->title }}</h2>
            <p class="text-sm text-slate-300">{{ $exam->subject }} · {{ $exam->questions_count }} questions</p>
        </div>

        <form method="POST" action="{{ route('student.exams.verify', $exam) }}" class="space-y-5 p-6" x-data="{ busy: false }" @submit="busy = true">
            @csrf

            <div>
                <label for="passkey" class="mb-2 block text-center text-sm font-medium text-slate-700">
                    Enter the passkey given by your teacher
                </label>
                <input id="passkey" type="text" name="passkey" required autofocus autocomplete="off" maxlength="20"
                       placeholder="••••••"
                       class="w-full rounded-xl border-0 bg-white px-4 py-4 text-center font-mono text-2xl font-bold uppercase tracking-[0.4em] text-[#0a1f55] shadow-sm ring-1 ring-slate-200 placeholder:text-slate-300 focus:ring-2 focus:ring-[#0a1f55] @error('passkey') ring-2 ring-[#7a1230] @enderror">
                @error('passkey')
                    <p class="mt-2 text-center text-sm font-medium text-[#7a1230]">⚠ {{ $message }}</p>
                @enderror
            </div>

            <button type="submit" :disabled="busy"
                    class="w-full rounded-xl bg-[#7a1230] py-3 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22] disabled:opacity-60">
                Unlock exam →
            </button>

            <div class="rounded-xl bg-[#fbf8f1] px-4 py-3 text-xs text-slate-500">
                <p>• You have <strong>one attempt</strong> only.</p>
                <p>• Not case-sensitive. Five wrong tries will lock you out for one minute.</p>
            </div>

            <a href="{{ route('student.exams') }}" class="block text-center text-sm font-semibold text-slate-500 hover:text-slate-700">← Back to exams</a>
        </form>
    </div>
</div>
@endsection
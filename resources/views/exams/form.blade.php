@extends('layouts.portal')

@section('title', $exam->exists ? 'Edit Exam' : 'New Exam')

@section('content')
@php
    $inputBase = 'w-full rounded-xl border-0 bg-white px-4 py-3 text-slate-900 shadow-sm ring-1 ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-[#0a1f55]';

    $blank = ['question' => '', 'options' => ['', '', '', ''], 'answer' => 0];

    $source = old('questions', $exam->exists
        ? $exam->questions->map(fn ($q) => ['question' => $q->question, 'options' => $q->options, 'answer' => $q->answer])->all()
        : [$blank]);

    $initial = collect($source)->map(fn ($q) => [
        'question' => $q['question'] ?? '',
        'options'  => array_pad(array_values($q['options'] ?? []), 4, ''),
        'answer'   => (int) ($q['answer'] ?? 0),
    ])->values()->all();
@endphp

<form method="POST"
      action="{{ $exam->exists ? route('exams.update', $exam) : route('exams.store') }}"
      x-data="{
            passkey: {{ Js::from(old('passkey', $exam->passkey ?? '')) }},
            genKey() {
                const c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                const r = crypto.getRandomValues(new Uint32Array(6));
                this.passkey = Array.from(r, n => c[n % c.length]).join('');
            },
          questions: {{ Js::from($initial) }},
          add() { this.questions.push({ question: '', options: ['', '', '', ''], answer: 0 }); this.$nextTick(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' })); },
          remove(i) { if (this.questions.length > 1) this.questions.splice(i, 1); },
          dup(i) { this.questions.splice(i + 1, 0, JSON.parse(JSON.stringify(this.questions[i]))); },
      }"
      class="mx-auto max-w-4xl space-y-6">
    @csrf
    @if ($exam->exists) @method('PUT') @endif

    {{-- Warning when students already took it --}}
    @if ($exam->exists && $exam->results_count > 0)
        <div class="rounded-xl border-l-4 border-[#f6c945] bg-white px-5 py-4 text-sm shadow-sm ring-1 ring-slate-200">
            <p class="font-semibold text-[#0a1f55]">⚠ {{ $exam->results_count }} {{ Str::plural('student', $exam->results_count) }} already took this exam.</p>
            <p class="mt-1 text-slate-500">Changing questions or answers will not recalculate their saved scores. Only edit for typos and small fixes.</p>
        </div>
    @endif

    {{-- Validation summary --}}
    @if ($errors->any())
        <div class="rounded-xl border-l-4 border-[#7a1230] bg-white px-5 py-4 text-sm shadow-sm ring-1 ring-slate-200">
            <p class="font-semibold text-[#7a1230]">Please fix the following:</p>
            <ul class="mt-2 list-inside list-disc space-y-0.5 text-slate-600">
                @foreach (collect($errors->all())->unique()->take(5) as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Details --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="border-b-2 border-[#f6c945] bg-[#0a1f55] px-6 py-4 text-white">
            <h2 class="font-semibold">Exam details</h2>
        </div>
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
                <input type="text" name="title" value="{{ old('title', $exam->title) }}" required maxlength="150"
                       placeholder="e.g. Midterm Examination" class="{{ $inputBase }} @error('title') ring-[#7a1230] @enderror">
                @error('title') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject</label>
                <input type="text" name="subject" value="{{ old('subject', $exam->subject) }}" required maxlength="100"
                       placeholder="e.g. Programming 1" class="{{ $inputBase }} @error('subject') ring-[#7a1230] @enderror">
                @error('subject') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Section</label>
                <select name="section_id" required class="{{ $inputBase }} @error('section_id') ring-[#7a1230] @enderror">
                    <option value="">— Choose section —</option>
                    @foreach ($sections as $s)
                        <option value="{{ $s->id }}" @selected((string) old('section_id', $exam->section_id) === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
                @error('section_id') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">
                    Exam passkey <span class="font-normal text-slate-400">(students must enter this to start)</span>
                </label>
                <div class="flex gap-3">
                    <input type="text" name="passkey" x-model="passkey" required minlength="4" maxlength="20"
                           autocomplete="off" placeholder="e.g. K7M2QX"
                           class="{{ $inputBase }} font-mono uppercase tracking-widest @error('passkey') ring-[#7a1230] @enderror">
                    <button type="button" @click="genKey()"
                            class="shrink-0 rounded-xl bg-[#f6c945] px-5 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#fde9a8]">
                        Generate
                    </button>
                </div>
                @error('passkey') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                <p class="mt-1.5 text-xs text-slate-400">Share this with your students only when the exam starts. Letters are not case-sensitive.</p>
            </div>

            {{-- Schedule --}}
            <div class="sm:col-span-2">
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Time limit <span class="font-normal text-slate-400">(minutes)</span></label>
                        <input id="duration_minutes" type="number" name="duration_minutes" min="1" max="600"
                            value="{{ old('duration_minutes', $exam->duration_minutes) }}" placeholder="No limit"
                            class="{{ $inputBase }} @error('duration_minutes') ring-[#7a1230] @enderror">
                        @error('duration_minutes') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Opens at <span class="font-normal text-slate-400">(optional)</span></label>
                        <input type="datetime-local" name="opens_at"
                            value="{{ old('opens_at', $exam->opens_at?->format('Y-m-d\TH:i')) }}"
                            class="{{ $inputBase }} @error('opens_at') ring-[#7a1230] @enderror">
                        @error('opens_at') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Closes at <span class="font-normal text-slate-400">(optional)</span></label>
                        <input type="datetime-local" name="closes_at"
                            value="{{ old('closes_at', $exam->closes_at?->format('Y-m-d\TH:i')) }}"
                            class="{{ $inputBase }} @error('closes_at') ring-[#7a1230] @enderror">
                        @error('closes_at') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-400">Quick set:</span>
                    @foreach ([30, 45, 60, 90, 120] as $m)
                        <button type="button" onclick="document.getElementById('duration_minutes').value = {{ $m }}"
                                class="rounded-full bg-[#0a1f55]/10 px-3 py-1 text-xs font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">{{ $m }} min</button>
                    @endforeach
                    <button type="button" onclick="document.getElementById('duration_minutes').value = ''"
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-200">No limit</button>
                </div>
                <p class="mt-2 text-xs text-slate-400">The exam ends at the closing time even if a student still has time left on their timer. Leave a field empty to skip it.</p>
            </div>

            {{-- Answer review --}}
            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">When can students review their answers?</label>
                @php $mode = old('review_mode', $exam->review_mode ?: 'after_close'); @endphp
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        'after_close'  => ['After the exam closes', 'Recommended. Stops early finishers from sharing answers.'],
                        'after_submit' => ['Right after submitting', 'Students see the correct answers immediately.'],
                        'off'          => ['Never', 'Students only see their score.'],
                    ] as $val => [$label, $hint])
                        <label class="flex cursor-pointer flex-col gap-1 rounded-xl p-4 ring-1 ring-slate-200 transition hover:bg-[#fbf8f1] has-[:checked]:bg-[#f6c945]/20 has-[:checked]:ring-2 has-[:checked]:ring-[#f6c945]">
                            <span class="flex items-center gap-2 text-sm font-semibold text-[#0a1f55]">
                                <input type="radio" name="review_mode" value="{{ $val }}" @checked($mode === $val)
                                    class="h-4 w-4 border-slate-300 text-[#7a1230] focus:ring-[#7a1230]">
                                {{ $label }}
                            </span>
                            <span class="pl-6 text-xs text-slate-500">{{ $hint }}</span>
                        </label>
                    @endforeach
                </div>
                @error('review_mode') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                <p class="mt-2 text-xs text-slate-400">“After the exam closes” needs a closing time. Without one, students can review right after submitting.</p>
            </div>

            {{-- Score release --}}
            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">When do students see their score?</label>
                @php $scoreMode = old('score_mode', $exam->score_mode ?: 'immediate'); @endphp
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'immediate'   => ['Right after submitting', 'Students see their score as soon as they finish.'],
                        'after_close' => ['After the exam closes', 'Students only see “Submitted”. Score and answer review are released together at the closing time.'],
                    ] as $val => [$label, $hint])
                        <label class="flex cursor-pointer flex-col gap-1 rounded-xl p-4 ring-1 ring-slate-200 transition hover:bg-[#fbf8f1] has-[:checked]:bg-[#f6c945]/20 has-[:checked]:ring-2 has-[:checked]:ring-[#f6c945]">
                            <span class="flex items-center gap-2 text-sm font-semibold text-[#0a1f55]">
                                <input type="radio" name="score_mode" value="{{ $val }}" @checked($scoreMode === $val)
                                    class="h-4 w-4 border-slate-300 text-[#7a1230] focus:ring-[#7a1230]">
                                {{ $label }}
                            </span>
                            <span class="pl-6 text-xs text-slate-500">{{ $hint }}</span>
                        </label>
                    @endforeach
                </div>
                @error('score_mode') <p class="mt-1.5 text-sm text-[#7a1230]">{{ $message }}</p> @enderror
                <p class="mt-2 text-xs text-slate-400">“After the exam closes” requires a <strong>Closes at</strong> time above. Teachers always see scores.</p>
            </div>



            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Type</label>
                <div class="flex gap-3">
                    @foreach (['exam' => 'Exam', 'quiz' => 'Quiz'] as $val => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold ring-1 ring-slate-200 has-[:checked]:bg-[#0a1f55] has-[:checked]:text-white has-[:checked]:ring-[#0a1f55]">
                            <input type="radio" name="type" value="{{ $val }}" class="sr-only" @checked(old('type', $exam->type ?: 'exam') === $val)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Questions --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-[#0a1f55]">
            Questions
            <span class="ml-2 rounded-full bg-[#f6c945]/40 px-2.5 py-0.5 text-sm text-[#7a5a00]" x-text="questions.length"></span>
        </h2>
        <button type="button" @click="add()"
                class="rounded-xl bg-[#0a1f55]/10 px-4 py-2 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#0a1f55] hover:text-white">+ Add question</button>
    </div>

    <template x-for="(q, i) in questions" :key="i">
        <div class="overflow-hidden rounded-2xl border-l-4 border-[#f6c945] bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="flex items-center justify-between bg-[#fbf8f1] px-6 py-3">
                <span class="text-sm font-bold text-[#0a1f55]" x-text="'Question ' + (i + 1)"></span>
                <div class="flex gap-2 text-xs font-semibold">
                    <button type="button" @click="dup(i)" class="rounded-lg px-3 py-1.5 text-[#0a1f55] hover:bg-[#0a1f55]/10">Duplicate</button>
                    <button type="button" @click="remove(i)" :disabled="questions.length === 1"
                            class="rounded-lg px-3 py-1.5 text-[#7a1230] hover:bg-[#7a1230]/10 disabled:cursor-not-allowed disabled:opacity-40">Remove</button>
                </div>
            </div>

            <div class="space-y-4 p-6">
                <textarea :name="'questions[' + i + '][question]'" x-model="q.question" rows="2" required maxlength="1000"
                          placeholder="Type the question here…"
                          class="{{ $inputBase }}"></textarea>

                <p class="text-xs font-medium text-slate-500">Fill in the four choices, then select the correct one.</p>

                <div class="grid gap-3 sm:grid-cols-2">
                    <template x-for="(opt, j) in q.options" :key="j">
                        <label class="flex items-center gap-3 rounded-xl p-2 pr-3 ring-1 transition"
                               :class="q.answer === j ? 'bg-[#f6c945]/20 ring-[#f6c945]' : 'bg-white ring-slate-200'">
                            <input type="radio" :name="'questions[' + i + '][answer]'" :value="j"
                                   :checked="q.answer === j" @change="q.answer = j"
                                   class="h-4 w-4 border-slate-300 text-[#7a1230] focus:ring-[#7a1230]">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#0a1f55] text-xs font-bold text-[#f6c945]"
                                  x-text="['A','B','C','D'][j]"></span>
                            <input type="text" :name="'questions[' + i + '][options][' + j + ']'" x-model="q.options[j]" required maxlength="300"
                                   :placeholder="'Choice ' + ['A','B','C','D'][j]"
                                   class="w-full border-0 bg-transparent p-1 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0">
                        </label>
                    </template>
                </div>

                <p class="text-xs text-slate-400">
                    Correct answer:
                    <span class="font-semibold text-[#7a1230]" x-text="['A','B','C','D'][q.answer]"></span>
                </p>
            </div>
        </div>
    </template>

    <button type="button" @click="add()"
            class="w-full rounded-2xl border-2 border-dashed border-[#0a1f55]/25 py-4 text-sm font-semibold text-[#0a1f55] transition hover:border-[#f6c945] hover:bg-[#f6c945]/10">
        + Add another question
    </button>

    {{-- Footer actions --}}
    <div class="sticky bottom-0 -mx-4 flex items-center justify-between gap-3 border-t-2 border-[#f6c945] bg-white/95 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border-2 sm:px-6">
        <a href="{{ route('exams.index') }}" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</a>
        <button type="submit"
                class="rounded-xl bg-[#0a1f55] px-7 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a1f55]/20 transition hover:bg-[#12307f]">
            {{ $exam->exists ? 'Save changes' : 'Publish exam' }}
        </button>
    </div>
</form>
@endsection
@extends('layouts.portal')

@section('title', $exam->title)

@section('content')
<form method="POST" action="{{ route('student.exams.submit', $exam) }}"
      x-data="{
          answers: {},
          total: {{ $exam->questions->count() }},
          confirmed: false,
          expired: false,
          draftKey: {{ Js::from($draftKey) }},
          remaining: {{ Js::from($remaining) }},
          limit: {{ Js::from($limit) }},
          left: {{ Js::from($remaining) }},
          endsAt: null,

          init() {
              try { this.answers = JSON.parse(localStorage.getItem(this.draftKey) || '{}') || {}; } catch (e) { this.answers = {}; }
              this.$watch('answers', v => { try { localStorage.setItem(this.draftKey, JSON.stringify(v)); } catch (e) {} });

              if (this.remaining !== null) {
                  // Count down from the server's number, not the device clock
                  this.endsAt = Date.now() + this.remaining * 1000;
                  this.tick();
                  setInterval(() => this.tick(), 500);
              }
          },
          tick() {
              this.left = Math.max(0, Math.ceil((this.endsAt - Date.now()) / 1000));
              if (this.left <= 0 && !this.expired) this.auto();
          },
          auto() {
              this.expired = true;
              this.submitting = true;
              try { localStorage.removeItem(this.draftKey); } catch (e) {}
              const f = this.$root;
              f.requestSubmit ? f.requestSubmit() : f.submit();
          },
          async onSubmit(e) {
                // Already confirmed, or time ran out: let the form go through
                if (this.confirmed || this.expired) {
                    this.submitting = true;
                    try { localStorage.removeItem(this.draftKey); } catch (err) {}
                    return;
                }

                e.preventDefault();

                const missing = this.total - this.answered;
                const ok = await window.confirmModal({
                    title: 'Submit your answers?',
                    message: missing
                        ? 'You still have ' + missing + ' unanswered question(s). You cannot retake this exam.'
                        : 'You answered every question. You cannot retake this exam.',
                    confirmText: 'Yes, submit',
                    cancelText: 'Keep working',
                    tone: missing ? 'warn' : 'info',
                });

                if (!ok) return;

                this.confirmed = true;
                this.$root.requestSubmit();
            },

            async leave(url, note) {
                const ok = await window.confirmModal({
                    title: 'Leave this exam?',
                    message: note,
                    confirmText: 'Leave exam',
                    cancelText: 'Stay',
                    tone: 'warn',
                });

                if (!ok) return;

                this.submitting = true;
                window.location.href = url;
            },

          get answered() { return Object.keys(this.answers).length; },
          get pct() { return Math.round(this.answered / this.total * 100); },
          get clock() {
              const s = this.left, h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60;
              const p = n => String(n).padStart(2, '0');
              return h ? h + ':' + p(m) + ':' + p(x) : p(m) + ':' + p(x);
          },
          get timePct() { return this.limit ? Math.min(100, this.left / this.limit * 100) : 0; },
          get warning() {
              if (this.remaining === null) return '';
              if (this.left <= 60) return 'Less than 1 minute left!';
              if (this.left <= 300) return '5 minutes left. Review your answers.';
              return '';
          },
      }"
      @submit="onSubmit($event)"
      class="mx-auto max-w-3xl space-y-6">
    @csrf

    {{-- Exam header --}}
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-6 text-white shadow-lg">
        <span class="rounded-full bg-[#f6c945] px-3 py-1 text-xs font-bold uppercase text-[#0a1f55]">{{ $exam->type }}</span>
        <h2 class="mt-3 text-2xl font-bold">{{ $exam->title }}</h2>
        <p class="text-slate-300">{{ $exam->subject }} · {{ $exam->questions->count() }} questions</p>

        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            @if ($exam->duration_minutes)
                <span class="rounded-full bg-white/10 px-3 py-1">⏱ {{ $exam->duration_minutes }} minute limit</span>
            @endif
            @if ($deadline)
                <span class="rounded-full bg-white/10 px-3 py-1">Ends at {{ $deadline->format('g:i A') }}</span>
            @endif
        </div>

        <p class="mt-3 rounded-lg bg-white/10 px-3 py-2 text-xs text-slate-200">
            Choose one answer for each question. One attempt only.
            @if ($exam->score_mode === 'after_close')
                <p class="mt-2 rounded-lg bg-[#f6c945]/15 px-3 py-2 text-xs text-[#fde9a8]">
                    🔒 Your score won’t be shown after you submit. It’s released with the answer review once the exam closes.
                </p>
            @endif
        </p>
    </div>

    {{-- Timer + progress --}}
    <div class="sticky top-16 z-10 -mx-4 border-b border-slate-200 bg-[#fbf8f1]/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-xl sm:border">
        @if ($remaining !== null)
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⏱</span>
                    <span class="font-mono text-2xl font-extrabold tabular-nums transition-colors"
                          :class="left <= 60 ? 'animate-pulse text-[#7a1230]' : (left <= 300 ? 'text-[#a87f00]' : 'text-[#0a1f55]')"
                          x-text="clock"></span>
                    <span class="text-xs text-slate-400">left</span>
                </div>
                <span x-show="warning" x-text="warning" x-transition
                      class="rounded-full bg-[#7a1230] px-3 py-1 text-xs font-semibold text-white"></span>
            </div>
            <div class="mb-3 h-1.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full transition-all duration-500"
                     :class="left <= 60 ? 'bg-[#7a1230]' : (left <= 300 ? 'bg-[#f6c945]' : 'bg-[#0a1f55]')"
                     :style="'width:' + timePct + '%'"></div>
            </div>
        @endif

        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold text-[#0a1f55]">Answered <span x-text="answered"></span> of <span x-text="total"></span></span>
            <span class="font-bold text-[#7a1230]" x-text="pct + '%'"></span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-gradient-to-r from-[#f6c945] to-[#d4a017] transition-all duration-300" :style="'width:' + pct + '%'"></div>
        </div>
    </div>

    {{-- Questions --}}
    @foreach ($exam->questions as $q)
        <fieldset class="overflow-hidden rounded-2xl border-l-4 bg-white shadow-sm ring-1 ring-slate-200/70 transition"
                  :class="answers[{{ $q->id }}] !== undefined ? 'border-[#f6c945]' : 'border-slate-200'">
            <div class="p-6">
                <legend class="mb-4 flex gap-3 text-base font-semibold text-[#0a1f55]">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#0a1f55] text-xs font-bold text-[#f6c945]">{{ $loop->iteration }}</span>
                    <span class="whitespace-pre-line">{{ $q->question }}</span>
                </legend>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($q->options as $j => $opt)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl p-3 ring-1 ring-slate-200 transition hover:bg-[#fbf8f1] has-[:checked]:bg-[#f6c945]/20 has-[:checked]:ring-2 has-[:checked]:ring-[#f6c945]">
                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $j }}"
                                   :checked="answers[{{ $q->id }}] === {{ $j }}"
                                   @change="answers[{{ $q->id }}] = {{ $j }}"
                                   class="h-4 w-4 border-slate-300 text-[#7a1230] focus:ring-[#7a1230]">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-[#0a1f55]">{{ chr(65 + $j) }}</span>
                            <span class="text-sm text-slate-700">{{ $opt }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </fieldset>
    @endforeach

    {{-- Footer --}}
    <div class="flex items-center justify-between gap-3 rounded-2xl border-2 border-[#f6c945] bg-white px-6 py-4 shadow-sm">
        <a href="{{ route('student.exams') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700"
            @click.prevent="leave('{{ route('student.exams') }}', {{ Js::from($remaining !== null
                    ? 'Your timer keeps running and your answers are saved on this device. You can come back until time runs out.'
                    : 'Your answers are saved on this device.') }})">← Leave exam</a>
        <button type="submit" :disabled="submitting"
                class="rounded-xl bg-[#7a1230] px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-[#7a1230]/20 transition hover:bg-[#5a0c22] disabled:opacity-60">
            Submit answers
        </button>
    </div>
</form>
@endsection
@extends('layouts.portal')

@section('title', $exam->title)

@section('content')
<style>
    /* Light theme */
    body {
        --f-bg: #fbf8f1; --f-card: #ffffff; --f-soft: #f3efe4; --f-line: #e9e3d5;
        --f-text: #14203f; --f-body: #3b4663; --f-muted: #7c859b; --f-hover: #f6f2e8;
        --f-sticky: rgba(251,248,241,.95);
        --f-shadow: 0 1px 2px rgba(10,31,85,.05), 0 8px 22px -14px rgba(10,31,85,.22);
    }
    /* Dark theme (shared with the News Feed) */
    body.feed-dark {
        --f-bg: #0a0f1f; --f-card: #131a2f; --f-soft: #1a2340; --f-line: #232e52;
        --f-text: #e9eefb; --f-body: #c3cce3; --f-muted: #8894b3; --f-hover: #1b2542;
        --f-sticky: rgba(10,15,31,.95);
        --f-shadow: 0 1px 2px rgba(0,0,0,.4), 0 12px 30px -16px rgba(0,0,0,.7);
        background: var(--f-bg); color-scheme: dark;
    }

    /* The portal's top bar and popups follow the theme */
    body.feed-dark header.sticky { background: rgba(10,15,31,.88); border-color: #f6c945; color: var(--f-text); }
    body.feed-dark header.sticky h1 { color: var(--f-text); }
    body.feed-dark header.sticky button.rounded-lg { color: var(--f-text); }
    body.feed-dark header.sticky button:hover { background: var(--f-hover); }
    body.feed-dark header.sticky .absolute:not(.nb-panel):not(.nb-badge) { color: #1e293b; }
    body.feed-dark #cm-card { background: var(--f-card); }
    body.feed-dark #cm-title { color: var(--f-text); }
    body.feed-dark #cm-msg { color: var(--f-muted); }
    body.feed-dark #cm-cancel { background: var(--f-soft); color: var(--f-body); }

    .ex-card   { background: var(--f-card); border: 1px solid var(--f-line); border-radius: 1rem; box-shadow: var(--f-shadow); }
    .ex-q      { border-left: 4px solid var(--f-line); transition: border-color .2s; }
    .ex-q.done { border-left-color: #f6c945; }
    .ex-text   { color: var(--f-text); } .ex-body { color: var(--f-body); } .ex-muted { color: var(--f-muted); }
    .ex-num    { background: #0a1f55; color: #f6c945; }
    body.feed-dark .ex-num { background: #f6c945; color: #0a1f55; }
    .ex-letter { background: var(--f-soft); color: var(--f-text); }

    .ex-opt { display: flex; align-items: center; gap: .75rem; cursor: pointer; border: 1px solid var(--f-line); border-radius: .85rem; padding: .7rem .85rem; background: var(--f-card); transition: .15s; }
    .ex-opt:hover { background: var(--f-hover); }
    .ex-opt:has(input:checked) { background: rgba(246,201,69,.2); border-color: #f6c945; box-shadow: 0 0 0 1px #f6c945; }

    .ex-sticky { background: var(--f-sticky); border-color: var(--f-line); backdrop-filter: blur(8px); }
    .ex-track  { background: var(--f-soft); }
    .ex-fill   { background: #0a1f55; }
    body.feed-dark .ex-fill { background: #8db4ff; }
    .ex-fill.warn { background: #f6c945; } .ex-fill.danger { background: #c0243f; }
    body.feed-dark .ex-fill.warn { background: #f6c945; }

    .ex-clock { color: var(--f-text); }
    .ex-clock.warn { color: #a87f00; } body.feed-dark .ex-clock.warn { color: #f6c945; }
    .ex-clock.danger { color: #c0243f; } body.feed-dark .ex-clock.danger { color: #ff8a9d; }
    .ex-pct { color: #7a1230; } body.feed-dark .ex-pct { color: #f6c945; }

    .ex-foot   { background: var(--f-card); border: 2px solid #f6c945; border-radius: 1rem; }
    .ex-leave  { color: var(--f-muted); } .ex-leave:hover { color: var(--f-text); }
    .ex-submit { background: linear-gradient(135deg, #7a1230, #a31a42); color: #fff; box-shadow: 0 8px 20px -8px rgba(122,18,48,.6); transition: .15s; }
    .ex-submit:hover { filter: brightness(1.08); }
    body.feed-dark .ex-submit { background: #f6c945; color: #0a1f55; box-shadow: 0 8px 20px -8px rgba(246,201,69,.45); }
</style>

<script>
// Apply the saved theme straight away, so the page doesn't flash
(function () {
    try {
        var t = localStorage.getItem('feedTheme');
        if (t === null) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.body.classList.toggle('feed-dark', t === 'dark');
    } catch (e) {}
})();
</script>

<form method="POST" action="{{ route('student.exams.submit', $exam) }}"
      x-data="{
          answers: {},
          total: {{ $exam->questions->count() }},
          submitting: false,
          confirmed: false,
          expired: false,
          dark: document.body.classList.contains('feed-dark'),
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
          toggleTheme() {
              this.dark = !this.dark;
              document.body.classList.toggle('feed-dark', this.dark);
              try { localStorage.setItem('feedTheme', this.dark ? 'dark' : 'light'); } catch (e) {}
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

              this.submitting = true;   // stops the browser's own 'leave site?' prompt
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
      @beforeunload.window="if (!submitting && answered > 0) { $event.preventDefault(); $event.returnValue = ''; }"
      @submit="onSubmit($event)"
      class="mx-auto max-w-3xl space-y-5">
    @csrf

    {{-- Exam header --}}
    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] p-6 text-white shadow-lg">
        <div class="flex items-start justify-between gap-3">
            <span class="rounded-full bg-[#f6c945] px-3 py-1 text-xs font-bold uppercase text-[#0a1f55]">{{ $exam->type }}</span>

            <button type="button" @click="toggleTheme()" :aria-pressed="dark"
                    class="flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold ring-1 ring-white/20 transition hover:bg-white/20">
                <span x-text="dark ? '☀️' : '🌙'"></span>
                <span x-text="dark ? 'Light mode' : 'Dark mode'"></span>
            </button>
        </div>

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
            @if ($remaining !== null) Your answers submit automatically when the time is up. @endif
        </p>

        @if ($exam->score_mode === 'after_close')
            <p class="mt-2 rounded-lg bg-[#f6c945]/15 px-3 py-2 text-xs text-[#fde9a8]">
                🔒 Your score won’t be shown after you submit. It’s released with the answer review once the exam closes.
            </p>
        @endif
    </div>

    {{-- Timer + progress --}}
    <div class="ex-sticky sticky top-16 z-10 -mx-4 border-b px-4 py-3 sm:mx-0 sm:rounded-xl sm:border">
        @if ($remaining !== null)
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⏱</span>
                    <span class="ex-clock font-mono text-2xl font-extrabold tabular-nums transition-colors"
                          :class="left <= 60 ? 'danger animate-pulse' : (left <= 300 ? 'warn' : '')"
                          x-text="clock"></span>
                    <span class="ex-muted text-xs">left</span>
                </div>
                <span x-show="warning" x-text="warning" x-transition style="display:none"
                      class="rounded-full bg-[#7a1230] px-3 py-1 text-xs font-semibold text-white"></span>
            </div>
            <div class="ex-track mb-3 h-1.5 overflow-hidden rounded-full">
                <div class="ex-fill h-full rounded-full transition-all duration-500"
                     :class="left <= 60 ? 'danger' : (left <= 300 ? 'warn' : '')"
                     :style="'width:' + timePct + '%'"></div>
            </div>
        @endif

        <div class="flex items-center justify-between text-sm">
            <span class="ex-text font-semibold">Answered <span x-text="answered"></span> of <span x-text="total"></span></span>
            <span class="ex-pct font-bold" x-text="pct + '%'"></span>
        </div>
        <div class="ex-track mt-2 h-2 overflow-hidden rounded-full">
            <div class="h-full rounded-full bg-gradient-to-r from-[#f6c945] to-[#d4a017] transition-all duration-300" :style="'width:' + pct + '%'"></div>
        </div>
    </div>

    {{-- Questions --}}
    @foreach ($exam->questions as $q)
        <fieldset class="ex-card ex-q overflow-hidden" :class="answers[{{ $q->id }}] !== undefined ? 'done' : ''">
            <div class="p-5 sm:p-6">
                <legend class="ex-text mb-4 flex gap-3 text-base font-semibold">
                    <span class="ex-num flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold">{{ $loop->iteration }}</span>
                    <span class="whitespace-pre-line">{{ $q->question }}</span>
                </legend>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($q->options as $j => $opt)
                        <label class="ex-opt">
                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $j }}"
                                   :checked="answers[{{ $q->id }}] === {{ $j }}"
                                   @change="answers[{{ $q->id }}] = {{ $j }}"
                                   class="h-4 w-4 shrink-0 border-slate-300" style="accent-color: #7a1230">
                            <span class="ex-letter flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold">{{ chr(65 + $j) }}</span>
                            <span class="ex-body text-sm">{{ $opt }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </fieldset>
    @endforeach

    {{-- Footer --}}
    <div class="ex-foot flex items-center justify-between gap-3 px-6 py-4">
        <a href="{{ route('student.exams') }}" class="ex-leave text-sm font-semibold"
           @click.prevent="leave('{{ route('student.exams') }}', {{ Js::from($remaining !== null
                ? 'Your timer keeps running and your answers are saved on this device. You can come back until time runs out.'
                : 'Your answers are saved on this device.') }})">← Leave exam</a>

        <button type="submit" :disabled="submitting"
                class="ex-submit rounded-xl px-8 py-3 text-sm font-semibold disabled:opacity-60">
            Submit answers
        </button>
    </div>
</form>
@endsection
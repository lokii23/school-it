@php
    $academy = 'Professional Academy of the Philippines';
    $college = 'College of Computer Studies';
    $app     = 'CCS Portal';
    $logo    = asset('images/ccs-logo.png');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $college }} · {{ $academy }}</title>
    <link rel="icon" type="image/png" href="{{ $logo }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .font-code { font-family: 'JetBrains Mono', ui-monospace, monospace; }

        /* ---------- Keyframes ---------- */
        @keyframes fadeUp   { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
        @keyframes float    { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
        @keyframes blob     { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(40px,-30px) scale(1.1); } 66% { transform: translate(-30px,30px) scale(.95); } }
        @keyframes gridPan  { from { background-position: 0 0; } to { background-position: 48px 48px; } }
        @keyframes blink    { 50% { opacity: 0; } }
        @keyframes marquee  { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        @keyframes lineIn   { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: none; } }
        @keyframes pulseDot { 0% { box-shadow: 0 0 0 0 rgba(246,201,69,.65); } 100% { box-shadow: 0 0 0 10px rgba(246,201,69,0); } }
        @keyframes spinSlow { to { transform: rotate(360deg); } }
        @keyframes shimmer  { to { background-position: 200% center; } }

        /* ---------- Helpers ---------- */
        .a-fade   { opacity: 0; animation: fadeUp .8s ease-out forwards; }
        .d1 { animation-delay: .1s; } .d2 { animation-delay: .25s; } .d3 { animation-delay: .4s; } .d4 { animation-delay: .55s; }
        .a-float  { animation: float 6s ease-in-out infinite; }
        .a-float2 { animation: float 8s ease-in-out infinite reverse; }
        .a-blob   { animation: blob 14s ease-in-out infinite; }
        .grid-bg  {
            background-image: linear-gradient(rgba(246,201,69,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(246,201,69,.08) 1px, transparent 1px);
            background-size: 48px 48px; animation: gridPan 6s linear infinite;
            mask-image: radial-gradient(ellipse at center, #000 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse at center, #000 30%, transparent 75%);
        }
        .cursor { display: inline-block; width: 3px; height: 1em; margin-left: 4px; vertical-align: -0.1em; background: #f6c945; animation: blink 1s step-end infinite; }
        .marquee-track { display: flex; width: max-content; animation: marquee 28s linear infinite; }
        .marquee:hover .marquee-track { animation-play-state: paused; }
        .code-line { opacity: 0; animation: lineIn .5s ease-out forwards; }
        .live-dot { animation: pulseDot 1.6s ease-out infinite; }
        .shimmer-text { background: linear-gradient(90deg, #d4a017, #fde9a8, #f6c945, #d4a017); background-size: 200% auto; -webkit-background-clip: text; background-clip: text; color: transparent; animation: shimmer 4s linear infinite; }

        /* Scroll reveal */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s ease, transform .7s ease; }
        .reveal.show { opacity: 1; transform: none; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .a-fade, .code-line, .reveal { opacity: 1 !important; transform: none !important; }
        }

        /* ---------- Features section motion ---------- */
        @keyframes kenburns { from { transform: scale(1.05) translate(0, 0); } to { transform: scale(1.18) translate(-2%, -1.5%); } }
        @keyframes riseUp   { 0% { transform: translateY(0) rotate(0deg); opacity: 0; } 10% { opacity: .55; } 90% { opacity: .35; } 100% { transform: translateY(-90vh) rotate(25deg); opacity: 0; } }
        @keyframes sweep    { 0%, 55% { transform: translateX(-120%); } 100% { transform: translateX(120%); } }
        @keyframes ringPulse{ 0% { box-shadow: 0 0 0 0 rgba(246,201,69,.55); } 100% { box-shadow: 0 0 0 16px rgba(246,201,69,0); } }

        .kenburns   { animation: kenburns 26s ease-in-out infinite alternate; }
        .particle   { position: absolute; bottom: -40px; color: rgba(246,201,69,.6); animation: riseUp linear infinite; }
        .icon-pulse { animation: ringPulse 2.4s ease-out infinite; }

        .shine::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: linear-gradient(110deg, transparent 30%, rgba(246,201,69,.22) 50%, transparent 70%);
            transform: translateX(-120%);
            animation: sweep 7s ease-in-out infinite; animation-delay: var(--d, 0s);
        }

        .draw-line { transform: scaleX(0); transform-origin: center; transition: transform 1s ease .35s; }
        .reveal.show .draw-line { transform: scaleX(1); }
    </style>
</head>
<body class="bg-white font-sans text-slate-800 antialiased">

{{-- ================= NAVBAR ================= --}}
<header class="fixed inset-x-0 top-0 z-30 border-b border-[#f6c945]/30 bg-[#071640]/80 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3 lg:px-8">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg ring-2 ring-[#f6c945]">
                <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain">
            </div>
            <div class="leading-tight text-white">
                <span class="block text-sm font-semibold sm:text-base">{{ $academy }}</span>
                <span class="block text-xs text-[#f6c945]">{{ $college }}</span>
            </div>
        </a>

        <div class="flex items-center gap-2">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="rounded-xl bg-[#f6c945] px-5 py-2 text-sm font-semibold text-[#0a1f55] transition hover:bg-[#fde9a8]">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">Log in</a>
                    
                @endauth
            @endif
        </div>
    </div>
</header>

{{-- ================= HERO ================= --}}
<section class="relative isolate overflow-hidden bg-gradient-to-br from-[#071640] via-[#0a1f55] to-[#12307f] text-white">
    <div class="grid-bg absolute inset-0 -z-10"></div>
    <div class="a-blob absolute -left-20 top-10 -z-10 h-96 w-96 rounded-full bg-[#f6c945]/20 blur-3xl"></div>
    <div class="a-blob absolute -right-20 bottom-0 -z-10 h-96 w-96 rounded-full bg-[#7a1230]/50 blur-3xl" style="animation-delay:-5s"></div>

    <div class="mx-auto grid max-w-7xl items-center gap-14 px-6 pb-24 pt-36 lg:grid-cols-2 lg:px-8 lg:pb-32 lg:pt-44">

        <div>
            <span class="a-fade d1 inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-1.5 text-sm text-[#fde9a8] ring-1 ring-[#f6c945]/40">
                <span class="live-dot h-2 w-2 rounded-full bg-[#f6c945]"></span>
                {{ $college }}
            </span>

            <h1 class="a-fade d2 mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                Where future<br>
                <span class="shimmer-text">developers</span><br>
                <span id="typed" class="text-white"></span><span class="cursor"></span>
            </h1>

            <p class="a-fade d3 mt-6 max-w-xl text-lg leading-relaxed text-slate-300">
                Welcome to the official portal of the {{ $college }} at the {{ $academy }}.
                Check announcements, take exams, and track your grades, all in one place.
            </p>

            <div class="a-fade d4 mt-10 flex flex-wrap gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="group rounded-xl bg-[#f6c945] px-7 py-3.5 font-semibold text-[#0a1f55] shadow-lg shadow-[#f6c945]/20 transition hover:bg-[#fde9a8]">
                        Go to Dashboard <span class="inline-block transition group-hover:translate-x-1">→</span>
                    </a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="group rounded-xl bg-[#f6c945] px-7 py-3.5 font-semibold text-[#0a1f55] shadow-lg shadow-[#f6c945]/20 transition hover:bg-[#fde9a8]">
                            Sign in to portal <span class="inline-block transition group-hover:translate-x-1">→</span>
                        </a>
                    @endif
                @endauth
            </div>
        </div>

        {{-- Floating code window --}}
        <div class="relative a-fade d3">
            <div class="a-float relative mx-auto w-full max-w-md rounded-2xl bg-[#071640] shadow-2xl ring-1 ring-[#f6c945]/30">
                <div class="flex items-center gap-2 border-b border-white/10 px-4 py-3">
                    <span class="h-3 w-3 rounded-full bg-[#9a1a3f]"></span>
                    <span class="h-3 w-3 rounded-full bg-[#f6c945]"></span>
                    <span class="h-3 w-3 rounded-full bg-white/70"></span>
                    <span class="font-code ml-3 text-xs text-slate-400">student.php</span>
                </div>
                <pre class="font-code overflow-hidden p-5 text-[13px] leading-7 text-slate-300"><code><span class="code-line block" style="animation-delay:.6s"><span class="text-[#f6c945]">class</span> <span class="text-white">Student</span> {</span><span class="code-line block" style="animation-delay:1.1s">  <span class="text-[#f6c945]">public</span> $school = <span class="text-[#fda4af]">'PAP'</span>;</span><span class="code-line block" style="animation-delay:1.6s">  <span class="text-[#f6c945]">public</span> $college = <span class="text-[#fda4af]">'CCS'</span>;</span><span class="code-line block" style="animation-delay:2.1s">  <span class="text-[#f6c945]">function</span> <span class="text-sky-300">learn</span>() {</span><span class="code-line block" style="animation-delay:2.6s">    <span class="text-[#f6c945]">return</span> <span class="text-[#fda4af]">'Success'</span>;</span><span class="code-line block" style="animation-delay:3.1s">  }</span><span class="code-line block" style="animation-delay:3.4s">}<span class="cursor"></span></span></code></pre>
            </div>

            {{-- Floating chips --}}
            <div class="a-float2 absolute -left-4 top-10 hidden rounded-xl border-l-4 border-[#f6c945] bg-white px-4 py-3 text-slate-800 shadow-xl sm:block">
                <p class="text-xs text-slate-400">Exam result</p>
                <p class="text-lg font-bold text-[#7a1230]">95% ✓</p>
            </div>
            <div class="a-float absolute -right-2 bottom-8 hidden rounded-xl border-l-4 border-[#7a1230] bg-white px-4 py-3 text-slate-800 shadow-xl sm:block" style="animation-delay:-2s">
                <p class="text-xs text-slate-400">New announcement</p>
                <p class="text-sm font-semibold text-[#0a1f55]">Midterm schedule 📢</p>
            </div>
            <div class="absolute -bottom-10 left-1/2 -z-10 h-40 w-40 -translate-x-1/2 rounded-full border border-dashed border-[#f6c945]/40" style="animation: spinSlow 30s linear infinite"></div>
        </div>
    </div>
</section>

{{-- ================= MARQUEE ================= --}}
<section class="marquee overflow-hidden bg-[#f6c945] py-4">
    @php $items = ['College of Computer Studies', 'College of Computer Studies', 'College of Computer Studies', 'College of Computer Studies', 'College of Computer Studies']; @endphp
    <div class="marquee-track">
        @foreach (array_merge($items, $items) as $i)
            <span class="mx-6 flex items-center gap-6 whitespace-nowrap text-sm font-bold uppercase tracking-widest text-[#0a1f55]">
                {{ $i }} <span class="text-[#7a1230]">✦</span>
            </span>
        @endforeach
    </div>
</section>

{{-- ================= FEATURES (background + motion only) ================= --}}
@php
    $syms = ['{ }', '</>', '01', ';', '#', '( )', '=>', '[ ]', '&&', '10', 'fn', '*'];
@endphp
<section id="features" class="relative isolate min-h-[70vh] overflow-hidden lg:min-h-[85vh]" aria-hidden="true">

    {{-- Your picture: outer layer = scroll parallax, inner layer = slow zoom --}}
    <div data-parallax class="absolute inset-x-0 -inset-y-12 -z-30 will-change-transform">
        <div class="kenburns h-full w-full bg-[#0a1f55] bg-cover bg-center bg-no-repeat"
             style="background-image: url('{{ asset('images/features-bg.jpg') }}')"></div>
    </div>

    {{-- Lighter tint than before, since there's no text to protect. Your picture shows through more --}}
    <div class="absolute inset-0 -z-20 bg-gradient-to-b from-[#071640]/70 via-[#0a1f55]/45 to-[#5a0c22]/70"></div>

    {{-- Moving grid, glow blobs and an orbiting ring --}}
    <div class="grid-bg absolute inset-0 -z-10 opacity-40"></div>
    <div class="a-blob absolute -left-24 top-10 -z-10 h-80 w-80 rounded-full bg-[#f6c945]/20 blur-3xl"></div>
    <div class="a-blob absolute -right-24 bottom-0 -z-10 h-96 w-96 rounded-full bg-[#7a1230]/50 blur-3xl" style="animation-delay:-6s"></div>

    <div class="absolute right-[8%] top-16 -z-10 hidden h-72 w-72 rounded-full border border-dashed border-[#f6c945]/30 lg:block"
         style="animation: spinSlow 40s linear infinite">
        <span class="absolute -top-1.5 left-1/2 h-3 w-3 rounded-full bg-[#f6c945] shadow-[0_0_14px_#f6c945]"></span>
    </div>
    <div class="absolute bottom-16 left-[10%] -z-10 hidden h-56 w-56 rounded-full border border-dashed border-white/20 lg:block"
         style="animation: spinSlow 55s linear infinite reverse">
        <span class="absolute -top-1.5 left-1/2 h-2.5 w-2.5 rounded-full bg-[#fda4af] shadow-[0_0_12px_#fda4af]"></span>
    </div>

    {{-- Floating code symbols --}}
    <div class="pointer-events-none absolute inset-0 -z-10">
        @for ($i = 0; $i < 24; $i++)
            <span class="particle font-code"
                  style="left: {{ (($i * 53) + 7) % 94 }}%;
                         font-size: {{ 12 + ($i % 4) * 5 }}px;
                         animation-duration: {{ 14 + ($i % 5) * 4 }}s;
                         animation-delay: -{{ ($i * 3) % 17 }}s;">{{ $syms[$i % count($syms)] }}</span>
        @endfor
    </div>

    {{-- Soft fades into the sections above and below --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-[#f6c945]/0 to-transparent"></div>
</section>

{{-- ================= FOOTER ================= --}}
<footer class="border-t-4 border-[#f6c945] bg-[#071640] text-slate-300">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm sm:flex-row lg:px-8">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white p-1">
                <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain">
            </div>
            <div class="leading-tight">
                <p class="font-semibold text-white">{{ $academy }}</p>
                <p class="text-xs text-[#f6c945]">{{ $college }}</p>
            </div>
        </div>
        <p>© {{ date('Y') }} {{ $academy }}. All rights reserved.</p>
    </div>
</footer>

<script>
    // Typewriter: rotating words
    (function () {
        const el = document.getElementById('typed');
        const words = ['code.', 'build.', 'lead.', 'excel.'];
        let w = 0, c = 0, del = false;
        function tick() {
            const word = words[w];
            el.textContent = word.slice(0, c);
            if (!del && c < word.length) { c++; setTimeout(tick, 110); }
            else if (!del) { del = true; setTimeout(tick, 1500); }
            else if (c > 0) { c--; setTimeout(tick, 60); }
            else { del = false; w = (w + 1) % words.length; setTimeout(tick, 300); }
        }
        tick();
    })();

    // Scroll reveal + count-up numbers
    (function () {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (!e.isIntersecting) return;
                e.target.classList.add('show');
                e.target.querySelectorAll('[data-count]').forEach(n => {
                    const to = +n.dataset.count; let cur = 0;
                    const step = Math.max(1, Math.ceil(to / 40));
                    const t = setInterval(() => {
                        cur = Math.min(to, cur + step); n.textContent = cur;
                        if (cur >= to) clearInterval(t);
                    }, 30);
                });
                io.unobserve(e.target);
            });
        }, { threshold: 0.2 });
        document.querySelectorAll('.reveal').forEach(el => io.observe(el));
    })();


// Parallax for the Features background
(function () {
    const el = document.querySelector('[data-parallax]');
    if (!el || matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const section = el.closest('section');
    let ticking = false;

    function update() {
        const r = section.getBoundingClientRect(), vh = window.innerHeight;
        if (r.bottom > 0 && r.top < vh) {
            const progress = (vh - r.top) / (vh + r.height);          // 0 to 1
            el.style.transform = 'translate3d(0,' + ((progress - 0.5) * 80) + 'px,0)';
        }
        ticking = false;
    }

    addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(update); ticking = true; } }, { passive: true });
    update();
})();
</script>
</body>
</html>
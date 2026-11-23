@php
    $uid    = auth()->id();
    $unread = \App\Models\FeedNotification::where('user_id', $uid)->whereNull('read_at')->count();
    $lastId = (int) \App\Models\FeedNotification::where('user_id', $uid)->max('id');
@endphp

<style>
    @keyframes nbRing { 0%,100% { transform: rotate(0); } 15% { transform: rotate(14deg); } 30% { transform: rotate(-12deg); } 45% { transform: rotate(9deg); } 60% { transform: rotate(-6deg); } 75% { transform: rotate(3deg); } }
    @keyframes nbPop  { 0% { transform: scale(.6); } 60% { transform: scale(1.3); } 100% { transform: scale(1); } }
    .nb-ring svg { animation: nbRing .9s ease; transform-origin: 50% 0; }
    .nb-pop { animation: nbPop .35s ease; }
    @media (prefers-reduced-motion: reduce) { .nb-ring svg, .nb-pop { animation: none; } }

    .nb-unread { background: rgba(246,201,69,.2); }
    body.feed-dark .nb-btn { color: #e9eefb; }
    body.feed-dark .nb-btn:hover { background: #1b2542; }
    body.feed-dark .nb-badge { border-color: #0a0f1f; }
    body.feed-dark .nb-panel { background: #131a2f; color: #e9eefb; box-shadow: 0 0 0 1px #232e52, 0 24px 48px -12px rgba(0,0,0,.7); }
    body.feed-dark .nb-head, body.feed-dark .nb-item, body.feed-dark .nb-foot { border-color: #232e52; }
    body.feed-dark .nb-item:hover { background: #1b2542; }
    body.feed-dark .nb-unread { background: rgba(246,201,69,.1); }
    body.feed-dark .nb-sub { color: #8894b3; }
    body.feed-dark .nb-time { color: #f6c945; }
</style>

<div x-data="notifBell({{ $unread }}, {{ $lastId }})" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">

    <button type="button" @click="toggle()" aria-label="Notifications" :class="ring ? 'nb-ring' : ''"
            class="nb-btn relative flex h-10 w-10 items-center justify-center rounded-full text-[#0a1f55] transition hover:bg-slate-100">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <span x-show="count > 0" x-text="count > 99 ? '99+' : count" :class="ring ? 'nb-pop' : ''" style="display:none"
              class="nb-badge absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border-2 border-white bg-[#7a1230] px-1 text-[10px] font-bold text-white"></span>
    </button>

    <div x-show="open" x-transition.opacity.duration.150ms style="display:none"
         class="nb-panel fixed inset-x-3 top-[4.25rem] z-40 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[22rem]">

        <div class="nb-head flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="text-sm font-bold">Notifications</h3>
            <button type="button" x-show="count > 0" @click="readAll()" style="display:none"
                    class="text-xs font-semibold text-[#7a1230] hover:underline">Mark all as read</button>
        </div>

        <div class="max-h-[24rem] overflow-y-auto">
            <p x-show="loading && !loaded" class="px-4 py-8 text-center text-sm text-slate-400">Loading…</p>

            <template x-for="n in items" :key="n.id">
                <button type="button" @click="go(n)" :class="n.unread ? 'nb-unread' : ''"
                        class="nb-item flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50">
                    <span class="relative shrink-0">
                        <template x-if="n.photo"><img :src="n.photo" alt="" class="h-10 w-10 rounded-full object-cover"></template>
                        <template x-if="!n.photo"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f6c945] text-xs font-bold text-[#0a1f55]" x-text="n.initials"></span></template>
                        <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs shadow ring-1 ring-slate-200" x-text="n.icon"></span>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-sm leading-snug"><strong x-text="n.actor"></strong> <span x-text="n.text"></span></span>
                        <span x-show="n.snippet" class="nb-sub block truncate text-xs text-slate-500" x-text="n.snippet"></span>
                        <span class="nb-time block text-[11px] font-semibold text-[#7a1230]" x-text="n.time"></span>
                    </span>

                    <span x-show="n.unread" class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-[#7a1230]"></span>
                </button>
            </template>

            <div x-show="loaded && items.length === 0" style="display:none" class="px-4 py-10 text-center">
                <div class="text-3xl">🔔</div>
                <p class="mt-2 text-sm font-semibold">You're all caught up</p>
                <p class="nb-sub text-xs text-slate-500">Reactions, comments and tags will show up here.</p>
            </div>
        </div>

        <div class="nb-foot flex items-center justify-between border-t border-slate-100 px-4 py-2 text-[11px]">
            <span :class="failures ? 'text-[#7a1230]' : 'text-emerald-600'" class="font-semibold"
                  x-text="failures ? '● Reconnecting…' : '● Live'"></span>
            <button type="button" @click="toggleSound()" class="nb-sub font-semibold text-slate-500 hover:underline"
                    x-text="sound ? '🔔 Sound on' : '🔕 Sound off'"></button>
        </div>
    </div>
</div>

<script>
window.notifBell = function (initial, lastId) {
    var ctx = null;   // audio, kept out of Alpine's reactive object on purpose
    var POLL = {{ Js::from(route('notifications.poll')) }};
    var LIST = {{ Js::from(route('notifications.index')) }};
    var ALL  = {{ Js::from(route('notifications.readAll')) }};

    return {
        open: false, count: initial, items: [], loading: false, loaded: false,
        lastId: lastId, ring: false, sound: false, failures: 0,
        stopped: false, polling: false, timer: null, baseTitle: '',

        init() {
            var self = this;
            this.baseTitle = document.title.replace(/^\(\d+\+?\)\s*/, '');
            try { this.sound = localStorage.getItem('notifSound') === '1'; } catch (e) {}
            this.setTitle();

            // Check right away when you come back to the tab or the internet returns
            document.addEventListener('visibilitychange', function () { if (!document.hidden) self.poll(); });
            window.addEventListener('focus', function () { self.poll(); });
            window.addEventListener('online', function () { self.failures = 0; self.poll(); });

            this.schedule(2500);
        },

        // ---------- the live check ----------
        delay() {
            if (this.failures) return Math.min(60000, 5000 * Math.pow(2, this.failures - 1));   // back off when the server is unreachable
            return document.hidden ? 30000 : 4000;
        },
        schedule(ms) {
            if (this.stopped) return;
            clearTimeout(this.timer);
            var self = this;
            this.timer = setTimeout(function () { self.poll(); }, ms === undefined ? this.delay() : ms);
        },
        async poll() {
            if (this.stopped || this.polling) return;
            this.polling = true;
            clearTimeout(this.timer);

            try {
                var res = await fetch(POLL + '?after=' + this.lastId, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                // Logged out or the session expired: stop checking
                if (res.status === 401 || res.status === 419) { this.stopped = true; return; }
                if (!res.ok) throw new Error('bad status');

                this.failures = 0;
                this.apply(await res.json());
            } catch (e) {
                this.failures++;
            } finally {
                this.polling = false;
                this.schedule();
            }
        },
        apply(r) {
            var fresh = r.items || [];
            var changed = r.unread !== this.count;

            if (fresh.length) {
                this.lastId = Math.max.apply(null, [this.lastId].concat(fresh.map(function (i) { return i.id; })));
            }

            this.count = r.unread;
            this.setTitle();

            if (fresh.length) {
                this.shake();

                // Only the tab you are looking at pops up and makes a sound
                if (!document.hidden) {
                    fresh.slice(-3).forEach(function (n) { window.dispatchEvent(new CustomEvent('notif-toast', { detail: n })); });
                    this.beep();
                }
            }

            // The open list stays up to date
            if (this.open && (fresh.length || changed)) this.load();
        },

        // ---------- screen ----------
        setTitle() {
            document.title = (this.count > 0 ? '(' + (this.count > 99 ? '99+' : this.count) + ') ' : '') + this.baseTitle;
        },
        shake() {
            var self = this;
            this.ring = false;
            setTimeout(function () { self.ring = true; setTimeout(function () { self.ring = false; }, 1000); }, 30);
        },

        // ---------- sound ----------
        toggleSound() {
            this.sound = !this.sound;
            try { localStorage.setItem('notifSound', this.sound ? '1' : '0'); } catch (e) {}
            if (this.sound) this.beep();
        },
        beep() {
            if (!this.sound) return;
            try {
                var AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) return;
                if (!ctx) ctx = new AC();
                if (ctx.state === 'suspended') ctx.resume();

                var t = ctx.currentTime, o = ctx.createOscillator(), g = ctx.createGain();
                o.type = 'sine';
                o.frequency.setValueAtTime(880, t);
                o.frequency.exponentialRampToValueAtTime(1320, t + 0.12);
                g.gain.setValueAtTime(0.0001, t);
                g.gain.exponentialRampToValueAtTime(0.12, t + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + 0.28);
                o.connect(g); g.connect(ctx.destination);
                o.start(t); o.stop(t + 0.3);
            } catch (e) {}
        },

        // ---------- the list ----------
        async api(url, method) {
            var res = await fetch(url, {
                method: method || 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!res.ok) throw new Error('request failed');
            return res.json();
        },
        async toggle() {
            this.open = !this.open;
            if (this.open) await this.load();
        },
        async load() {
            this.loading = true;
            try {
                var r = await this.api(LIST);
                this.items = r.items; this.count = r.unread; this.loaded = true; this.setTitle();
            } catch (e) {}
            this.loading = false;
        },
        async readAll() {
            try {
                await this.api(ALL, 'POST');
                this.items.forEach(function (i) { i.unread = false; });
                this.count = 0; this.setTitle();
            } catch (e) {}
        },
        async go(n) {
            try { if (n.unread) await this.api(n.read_url, 'POST'); } catch (e) {}
            window.location.href = n.url;
        }
    };
};
</script>
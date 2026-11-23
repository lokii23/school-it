<style>
    .nt-card { background: #ffffff; color: #14203f; }
    .nt-sub { color: #64748b; }
    body.feed-dark .nt-card { background: #131a2f; color: #e9eefb; box-shadow: 0 0 0 1px #232e52, 0 24px 48px -12px rgba(0,0,0,.7); }
    body.feed-dark .nt-sub { color: #8894b3; }
</style>

<div x-data="notifToasts()" @notif-toast.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-3 top-[4.5rem] z-[90] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-4 sm:w-[22rem]"
     aria-live="polite">

    <template x-for="t in list" :key="t.key">
        <div x-show="t.show"
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-6 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-x-6 opacity-0"
             @mouseenter="pause(t)" @mouseleave="resume(t)"
             class="nt-card pointer-events-auto relative w-full overflow-hidden rounded-2xl shadow-2xl ring-1 ring-slate-200">

            <div class="h-1 bg-gradient-to-r from-[#f6c945] via-[#d4a017] to-[#7a1230]"></div>

            <button type="button" @click="go(t)" class="flex w-full items-start gap-3 py-3 pl-4 pr-9 text-left">
                <span class="relative shrink-0">
                    <template x-if="t.n.photo"><img :src="t.n.photo" alt="" class="h-10 w-10 rounded-full object-cover"></template>
                    <template x-if="!t.n.photo"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f6c945] text-xs font-bold text-[#0a1f55]" x-text="t.n.initials"></span></template>
                    <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs shadow ring-1 ring-slate-200" x-text="t.n.icon"></span>
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-sm leading-snug"><strong x-text="t.n.actor"></strong> <span x-text="t.n.text"></span></span>
                    <span x-show="t.n.snippet" class="nt-sub block truncate text-xs" x-text="t.n.snippet"></span>
                    <span class="block text-[11px] font-semibold text-[#7a1230]" x-text="t.n.time"></span>
                </span>
            </button>

            <button type="button" @click="dismiss(t)" aria-label="Dismiss"
                    class="nt-sub absolute right-2 top-3 flex h-6 w-6 items-center justify-center rounded-full text-xs hover:bg-slate-100">✕</button>
        </div>
    </template>
</div>

<script>
window.notifToasts = function () {
    return {
        list: [],

        add(n) {
            if (this.list.some(function (t) { return t.n.id === n.id; })) return;

            if (this.list.length >= 4) { clearTimeout(this.list[0].timer); this.list.shift(); }

            this.list.push({ key: n.id + '-' + Date.now(), n: n, show: false, timer: null });

            var item = this.list[this.list.length - 1], self = this;
            this.$nextTick(function () { item.show = true; });
            item.timer = setTimeout(function () { self.dismiss(item); }, 7000);
        },

        dismiss(t) {
            clearTimeout(t.timer);
            t.show = false;
            var key = t.key, self = this;
            setTimeout(function () { self.list = self.list.filter(function (x) { return x.key !== key; }); }, 250);
        },

        pause(t) { clearTimeout(t.timer); },
        resume(t) { var self = this; clearTimeout(t.timer); t.timer = setTimeout(function () { self.dismiss(t); }, 3000); },

        async go(t) {
            clearTimeout(t.timer);
            try {
                await fetch(t.n.read_url, {
                    method: 'POST', credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } catch (e) {}
            window.location.href = t.n.url;
        }
    };
};
</script>
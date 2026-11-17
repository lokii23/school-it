@extends('layouts.portal')

@section('title', 'News Feed')

@section('content')

{{-- ====================== THEME + STYLES ====================== --}}
<style>
    /* Light theme */
    body {
        --f-bg: #fbf8f1; --f-card: #ffffff; --f-soft: #f3efe4; --f-line: #e9e3d5;
        --f-text: #14203f; --f-body: #3b4663; --f-muted: #7c859b; --f-hover: #f6f2e8;
        --f-link: #7a1230; --f-shadow: 0 1px 2px rgba(10,31,85,.05), 0 8px 22px -14px rgba(10,31,85,.22);
    }
    /* Dark theme */
    body.feed-dark {
        --f-bg: #0a0f1f; --f-card: #131a2f; --f-soft: #1a2340; --f-line: #232e52;
        --f-text: #e9eefb; --f-body: #c3cce3; --f-muted: #8894b3; --f-hover: #1b2542;
        --f-link: #f6c945; --f-shadow: 0 1px 2px rgba(0,0,0,.4), 0 12px 30px -16px rgba(0,0,0,.7);
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

    /* Building blocks */
    .fd-card { background: var(--f-card); border: 1px solid var(--f-line); border-radius: 1rem; box-shadow: var(--f-shadow); }
    .fd-text { color: var(--f-text); } .fd-body { color: var(--f-body); } .fd-muted { color: var(--f-muted); } .fd-link { color: var(--f-link); }
    .fd-line { border-color: var(--f-line); }
    .fd-soft { background: var(--f-soft); }
    .fd-hover:hover { background: var(--f-hover); }
    .fd-input { width: 100%; resize: none; background: var(--f-soft); color: var(--f-body); border: 1px solid transparent; border-radius: .85rem; padding: .6rem .9rem; font-size: .875rem; outline: none; transition: .15s; }
    .fd-input:focus { border-color: #f6c945; box-shadow: 0 0 0 3px rgba(246,201,69,.25); }
    .fd-input::placeholder { color: var(--f-muted); }
    .fd-pop { background: var(--f-card); border: 1px solid var(--f-line); box-shadow: 0 18px 40px -12px rgba(0,0,0,.35); }
    .fd-pop button:hover, .fd-sel { background: var(--f-hover); }
    .fd-emoji { background: var(--f-card); box-shadow: 0 0 0 2px var(--f-card); }
    .fd-bubble { background: var(--f-card); border: 1px solid var(--f-line); border-radius: 1rem; }

    .fd-chip { border-radius: 999px; padding: .35rem .85rem; font-size: .78rem; font-weight: 600; border: 1px solid var(--f-line); background: var(--f-card); color: var(--f-muted); transition: .15s; }
    .fd-chip:hover { background: var(--f-hover); color: var(--f-text); }
    .fd-chip.on { background: #0a1f55; color: #fff; border-color: #0a1f55; }
    body.feed-dark .fd-chip.on { background: #f6c945; color: #0a1f55; border-color: #f6c945; }

    .fd-primary { background: linear-gradient(135deg, #7a1230, #a31a42); color: #fff; box-shadow: 0 8px 20px -8px rgba(122,18,48,.6); transition: .15s; }
    .fd-primary:hover { filter: brightness(1.08); }
    body.feed-dark .fd-primary { background: #f6c945; color: #0a1f55; box-shadow: 0 8px 20px -8px rgba(246,201,69,.45); }

    .fd-act { display: flex; flex: 1; align-items: center; justify-content: center; gap: .4rem; border-radius: .75rem; padding: .5rem; font-size: .8rem; font-weight: 600; color: var(--f-muted); transition: .15s; }
    .fd-act:hover { background: var(--f-hover); color: var(--f-text); }
    .fd-tool { display: inline-flex; align-items: center; gap: .4rem; border-radius: .75rem; padding: .4rem .7rem; font-size: .8rem; font-weight: 600; color: var(--f-muted); transition: .15s; }
    .fd-tool:hover { background: var(--f-hover); color: var(--f-text); }

    /* Tagged names, highlighted */
    .fd-tag { background: rgba(246,201,69,.3); color: #7a1230; border-radius: .35rem; padding: 0 .25rem; font-weight: 600; }
    body.feed-dark .fd-tag { background: rgba(246,201,69,.16); color: #f6c945; }
    .fd-ribbon { background: rgba(246,201,69,.28); color: #7a5a00; }
    body.feed-dark .fd-ribbon { color: #f6c945; }
    .fd-tagged { border-color: #f6c945; box-shadow: 0 0 0 1px #f6c945, var(--f-shadow); }

    .fd-pin { background: rgba(246,201,69,.22); color: #7a5a00; }
    body.feed-dark .fd-pin { color: #f6c945; }
    .fd-new { background: #f6c945; color: #0a1f55; }

    .rb-admin { background: #7a1230; color: #fff; }
    .rb-teacher { background: #f6c945; color: #0a1f55; }
    .rb-student { background: rgba(10,31,85,.1); color: #0a1f55; }
    body.feed-dark .rb-student { background: rgba(147,181,255,.16); color: #a9c6ff; }

    .rx-like { color: #0a1f55; } .rx-love { color: #7a1230; } .rx-haha, .rx-wow, .rx-sad { color: #a87f00; } .rx-angry { color: #b4271c; }
    body.feed-dark .rx-like { color: #8db4ff; } body.feed-dark .rx-love { color: #ff7a95; }
    body.feed-dark .rx-haha, body.feed-dark .rx-wow, body.feed-dark .rx-sad { color: #f6c945; } body.feed-dark .rx-angry { color: #ff8a78; }

    .fd-rail-head { display: flex; align-items: center; justify-content: space-between; padding: .7rem .9rem; border-bottom: 1px solid var(--f-line); }

    @keyframes fdIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .fd-in { animation: fdIn .35s ease both; }
    @media (prefers-reduced-motion: reduce) { .fd-in { animation: none; } }
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

window.feedPeopleUrl = {{ Js::from(route('feed.people')) }};

(function () {
    var REACTIONS = [
        { key: 'like',  emoji: '👍', label: 'Like',  color: 'rx-like' },
        { key: 'love',  emoji: '❤️', label: 'Love',  color: 'rx-love' },
        { key: 'haha',  emoji: '😂', label: 'Haha',  color: 'rx-haha' },
        { key: 'wow',   emoji: '😮', label: 'Wow',   color: 'rx-wow' },
        { key: 'sad',   emoji: '😢', label: 'Sad',   color: 'rx-sad' },
        { key: 'angry', emoji: '😡', label: 'Angry', color: 'rx-angry' }
    ];

    // The confirmation modal, with the browser's confirm() as a backup
    function ask(o) {
        if (window.confirmModal) return window.confirmModal(o);
        return Promise.resolve(window.confirm(o.title + (o.message ? '\n\n' + o.message : '')));
    }

    // Talks to the server and returns the JSON answer
    async function feedApi(url, method, data) {
        var res = await fetch(url, {
            method: method || 'GET',
            credentials: 'same-origin',
            headers: Object.assign({
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }, data ? { 'Content-Type': 'application/json' } : {}),
            body: data ? JSON.stringify(data) : null
        });

        if (!res.ok) {
            var msg = 'Something went wrong. Please try again.';
            try {
                var j = await res.json();
                msg = j.message || (j.errors ? Object.values(j.errors)[0][0] : msg);
            } catch (e) {}
            throw new Error(msg);
        }

        return res.json();
    }

    // ---------- tagged names ----------
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function escRe(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }

    // Safe HTML: the text is escaped first, then each tagged name is wrapped in a highlight
    function tagHtml(text, mentions) {
        var html = esc(text);
        var labels = (mentions || []).map(function (m) { return esc('@' + m.label); }).filter(Boolean);
        if (!labels.length) return html;
        labels.sort(function (a, b) { return b.length - a.length; });   // longest name first
        return html.replace(new RegExp('(' + labels.map(escRe).join('|') + ')', 'gi'), '<span class="fd-tag">$1</span>');
    }

    // Typing a name after the at-sign shows a list of people to pick from.
    // Mix this into any component that has textareas named x-ref="ta_<field>".
    function mentionMixin() {
        return {
            mn: { open: false, field: '', items: [], active: 0, start: 0, skip: false },
            picked: { body: [], draft: [], newComment: [] },
            mnT: null, mnSeq: 0,

            mnInput(e, field) {
                if (this.mn.skip) { this.mn.skip = false; return; }

                var ta = e.target, pos = ta.selectionStart, before = ta.value.slice(0, pos);
                var m = before.match(/(^|\s)@([^\n@]{1,40})$/);
                var q = m ? m[2] : '';

                // Not a tag in progress: no match, a space right after the at-sign, two spaces, or too many words
                if (!m || /^\s/.test(q) || /\s\s$/.test(q) || q.trim().split(/\s+/).length > 3) { this.mnClose(); return; }

                // A name that was already picked and is followed by a space is finished
                var typed = ('@' + q).toLowerCase();
                var done = (this.picked[field] || []).some(function (p) { return typed.indexOf('@' + p.label.toLowerCase() + ' ') === 0; });
                if (done) { this.mnClose(); return; }

                this.mn.field = field;
                this.mn.start = pos - q.length - 1;

                clearTimeout(this.mnT);
                var self = this, seq = ++this.mnSeq, term = q.trim();

                this.mnT = setTimeout(async function () {
                    try {
                        var r = await feedApi(window.feedPeopleUrl + '?q=' + encodeURIComponent(term));
                        if (seq !== self.mnSeq) return;   // an older answer
                        self.mn.items = r.people; self.mn.active = 0; self.mn.open = r.people.length > 0;
                    } catch (err) { self.mnClose(); }
                }, 150);
            },

            // Returns true when the key was used by the list
            mnKey(e) {
                if (!this.mn.open || !this.mn.items.length) return false;

                if (e.key === 'ArrowDown') { e.preventDefault(); this.mn.active = (this.mn.active + 1) % this.mn.items.length; return true; }
                if (e.key === 'ArrowUp')   { e.preventDefault(); this.mn.active = (this.mn.active - 1 + this.mn.items.length) % this.mn.items.length; return true; }
                if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); this.mnPick(this.mn.items[this.mn.active]); return true; }
                if (e.key === 'Escape')    { e.preventDefault(); this.mnClose(); return true; }

                return false;
            },

            mnPick(item) {
                var field = this.mn.field, ta = this.$refs['ta_' + field];
                if (!ta || !item) { this.mnClose(); return; }

                var pos = ta.selectionStart, val = ta.value, ins = '@' + item.name + ' ';
                ta.value = val.slice(0, this.mn.start) + ins + val.slice(pos);

                var caret = this.mn.start + ins.length;
                this.mn.skip = true;                                      // don't search again for the text we just wrote
                ta.dispatchEvent(new Event('input', { bubbles: true }));  // lets x-model see the change
                ta.focus(); ta.setSelectionRange(caret, caret);

                if (!this.picked[field].some(function (p) { return p.id === item.id; })) {
                    this.picked[field].push({ id: item.id, label: item.name });
                }

                this.mnClose();
            },

            mnClose() { this.mn.open = false; this.mn.items = []; },

            // The ids still tagged in the text
            mnIds(field, text) {
                var t = String(text || '').toLowerCase(), out = [];
                (this.picked[field] || []).forEach(function (p) {
                    if (t.indexOf('@' + p.label.toLowerCase()) !== -1 && out.indexOf(p.id) === -1) out.push(p.id);
                });
                return out;
            }
        };
    }

    // The light / dark switch
    window.feedTheme = function () {
        return {
            dark: document.body.classList.contains('feed-dark'),
            toggle() {
                this.dark = !this.dark;
                document.body.classList.toggle('feed-dark', this.dark);
                try { localStorage.setItem('feedTheme', this.dark ? 'dark' : 'light'); } catch (e) {}
            }
        };
    };

    // The "new post" form
    window.feedComposer = function (initial) {
        return {
            ...mentionMixin(),
            body: initial || '', preview: null, err: '', busy: false,

            get empty() { return !this.body.trim() && !this.preview; },
            get ids() { return this.mnIds('body', this.body); },
            get tagged() {
                var ids = this.ids;
                return (this.picked.body || []).filter(function (p) { return ids.indexOf(p.id) !== -1; });
            },

            pick(e) {
                this.err = '';
                var f = e.target.files[0];
                if (!f) return;
                if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) { this.err = 'Choose a JPG, PNG or WebP photo.'; e.target.value = ''; return; }
                if (f.size > 4 * 1024 * 1024) { this.err = 'The photo must be 4 MB or smaller.'; e.target.value = ''; return; }
                if (this.preview) URL.revokeObjectURL(this.preview);
                this.preview = URL.createObjectURL(f);
            },
            clear() {
                if (this.preview) URL.revokeObjectURL(this.preview);
                this.preview = null; this.$refs.file.value = '';
            }
        };
    };

    // One post: reactions, comments, tags, edit, pin and delete
    window.feedPost = function (cfg) {
        return {
            ...cfg,                 // the post's data
            ...mentionMixin(),

            reactions: REACTIONS,
            expanded: false, menu: false, editing: false, draft: '', busy: false,
            picker: false, openT: null, closeT: null, pressT: null, pressed: false,
            commentsOpen: false, comments: [], loaded: false, loading: false, newComment: '', sending: false,
            whoOpen: false, who: [], whoLoading: false, whoFilter: 'all',

            init() { if (this.autoComments) this.toggleComments(); },

            meta(key) { return REACTIONS.find(function (r) { return r.key === key; }) || REACTIONS[0]; },
            html(text, mentions) { return tagHtml(text, mentions); },

            roleClass(role) {
                if (role === 'admin') return 'rb-admin';
                if (role === 'teacher') return 'rb-teacher';
                return 'rb-student';
            },

            get shown() { return (this.expanded || this.body.length <= 420) ? this.body : this.body.slice(0, 420) + '…'; },
            get total() {
                var c = this.counts || {};
                return Object.keys(c).reduce(function (a, k) { return a + (c[k] || 0); }, 0);
            },
            get present() {
                var c = this.counts || {};
                return REACTIONS.filter(function (r) { return (c[r.key] || 0) > 0; });
            },
            get top() {
                var c = this.counts || {};
                return this.present.slice().sort(function (a, b) { return (c[b.key] || 0) - (c[a.key] || 0); }).slice(0, 3);
            },
            get summary() {
                var t = this.total;
                if (!t) return '';
                if (this.mine) return t === 1 ? 'You' : 'You and ' + (t - 1) + (t - 1 === 1 ? ' other' : ' others');
                return String(t);
            },
            get whoShown() {
                var f = this.whoFilter;
                return f === 'all' ? this.who : this.who.filter(function (w) { return w.type === f; });
            },

            // ----- reactions -----
            showPicker() {
                clearTimeout(this.closeT);
                if (!window.matchMedia('(hover: hover)').matches) return;   // phones use a long press
                var self = this;
                this.openT = setTimeout(function () { self.picker = true; }, 300);
            },
            keepPicker() { clearTimeout(this.closeT); },
            hidePicker() {
                clearTimeout(this.openT);
                var self = this;
                this.closeT = setTimeout(function () { self.picker = false; }, 350);
            },
            pressStart() {
                this.pressed = false;
                var self = this;
                this.pressT = setTimeout(function () { self.picker = true; self.pressed = true; }, 500);
            },
            pressEnd() { clearTimeout(this.pressT); },
            quick() {
                if (this.pressed) { this.pressed = false; return; }
                this.react(this.mine || 'like');
            },
            async react(type) {
                this.picker = false;

                var before = { mine: this.mine, counts: Object.assign({}, this.counts) };
                var next = this.mine === type ? null : type;

                // Update the screen straight away, then confirm with the server
                var counts = Object.assign({}, this.counts);
                if (this.mine) counts[this.mine] = Math.max(0, (counts[this.mine] || 0) - 1);
                if (next) counts[next] = (counts[next] || 0) + 1;
                this.counts = counts;
                this.mine = next;

                try {
                    var r = await feedApi(this.urls.react, 'POST', { type: next });
                    this.counts = r.counts;
                    this.mine = r.mine;
                } catch (e) {
                    this.counts = before.counts;
                    this.mine = before.mine;
                    alert(e.message);
                }
            },
            async showWho() {
                this.whoOpen = true; this.whoFilter = 'all'; this.whoLoading = true;
                try { this.who = (await feedApi(this.urls.reactions)).reactions; }
                catch (e) { this.whoOpen = false; alert(e.message); }
                this.whoLoading = false;
            },

            // ----- comments -----
            async toggleComments() {
                this.commentsOpen = !this.commentsOpen;
                if (this.commentsOpen && !this.loaded) await this.loadComments();
                if (this.commentsOpen && !this.autoComments) { var self = this; this.$nextTick(function () { if (self.$refs.ta_newComment) self.$refs.ta_newComment.focus(); }); }
            },
            async loadComments() {
                this.loading = true;
                try {
                    var r = await feedApi(this.urls.comments);
                    this.comments = r.comments; this.loaded = true; this.commentsCount = r.comments.length;
                } catch (e) { alert(e.message); }
                this.loading = false;
            },
            async sendComment() {
                var b = this.newComment.trim();
                if (!b || this.sending) return;
                this.sending = true;
                try {
                    var r = await feedApi(this.urls.comments, 'POST', { body: b, mentions: this.mnIds('newComment', b) });
                    this.comments.push(r.comment); this.commentsCount++;
                    this.newComment = ''; this.picked.newComment = [];
                } catch (e) { alert(e.message); }
                this.sending = false;
            },
            async deleteComment(c) {
                var ok = await ask({
                    title: 'Delete this comment?', message: 'This cannot be undone.', confirmText: 'Delete', tone: 'danger'
                });
                if (!ok) return;
                try {
                    await feedApi(c.url, 'DELETE');
                    this.comments = this.comments.filter(function (x) { return x.id !== c.id; });
                    this.commentsCount = Math.max(0, this.commentsCount - 1);
                } catch (e) { alert(e.message); }
            },

            // ----- the post itself -----
            startEdit() {
                this.draft = this.body;
                this.picked.draft = (this.mentions || []).map(function (m) { return { id: m.id, label: m.label }; });
                this.editing = true; this.menu = false;
                var self = this; this.$nextTick(function () { if (self.$refs.ta_draft) self.$refs.ta_draft.focus(); });
            },
            async saveEdit() {
                if (this.busy) return;
                this.busy = true;
                try {
                    var r = await feedApi(this.urls.update, 'PATCH', { body: this.draft, mentions: this.mnIds('draft', this.draft) });
                    this.body = r.body; this.mentions = r.mentions || []; this.edited = r.edited; this.editing = false;
                    var me = this.meId;
                    this.tagged = this.mentions.some(function (m) { return m.id === me; });
                } catch (e) { alert(e.message); }
                this.busy = false;
            },
            async togglePin() {
                this.menu = false;
                try { this.pinned = (await feedApi(this.urls.pin, 'POST')).pinned; }
                catch (e) { alert(e.message); }
            },
            async remove() {
                this.menu = false;
                var ok = await ask({
                    title: 'Delete this post?',
                    message: 'Its photo, reactions and comments will be deleted too. This cannot be undone.',
                    confirmText: 'Delete', tone: 'danger'
                });
                if (!ok) return;
                try { await feedApi(this.urls.destroy, 'DELETE'); this.$refs.card.remove(); }
                catch (e) { alert(e.message); }
            }
        };
    };

    // The list: loads the next posts as you scroll
    window.feedList = function (next) {
        var io = null;

        return {
            next: next || null, loading: false,

            init() {
                if (!('IntersectionObserver' in window)) return;
                var self = this;
                io = new IntersectionObserver(function (e) { if (e[0].isIntersecting) self.more(); }, { rootMargin: '400px' });
                this.$nextTick(function () { if (self.$refs.sentinel) io.observe(self.$refs.sentinel); });
            },
            async more() {
                if (!this.next || this.loading) return;
                this.loading = true;
                try {
                    var res = await fetch(this.next, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    if (!res.ok) throw new Error('failed');
                    var html = await res.text();
                    this.$refs.list.insertAdjacentHTML('beforeend', html);
                    this.next = res.headers.get('X-Next-Page') || null;
                } catch (e) { alert('Could not load more posts. Please try again.'); }
                this.loading = false;

                // Check again, in case the page is still short
                if (io && this.$refs.sentinel) { io.unobserve(this.$refs.sentinel); io.observe(this.$refs.sentinel); }
            }
        };
    };
})();
</script>

@php
    $me      = auth()->user();
    $chips   = ['all' => 'Everyone', 'mine' => 'My posts'];
    $firstAn = $announcements->first();
@endphp

<div class="mx-auto max-w-4xl" x-data="feedTheme()">

    {{-- ============ Title bar ============ --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="fd-text text-xl font-extrabold tracking-tight">News Feed</h2>
            <p class="fd-muted text-xs">What's happening at the College of Computer Studies</p>
        </div>

        <button type="button" @click="toggle()" class="fd-chip flex items-center gap-2" :aria-pressed="dark">
            <span x-text="dark ? '☀️' : '🌙'"></span>
            <span x-text="dark ? 'Light mode' : 'Dark mode'"></span>
        </button>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_17rem]">

        {{-- ====================== MAIN COLUMN ====================== --}}
        <div class="min-w-0 space-y-4">

            {{-- Showing one post (from a notification) --}}
            @if ($single)
                <div class="fd-card flex items-center justify-between px-4 py-2.5 text-sm">
                    <span class="fd-body">Showing one post</span>
                    <a href="{{ route('feed.index') }}" class="fd-link font-semibold hover:underline">← Back to the feed</a>
                </div>
            @endif

            {{-- Composer --}}
            <form method="POST" action="{{ route('feed.store') }}" enctype="multipart/form-data"
                  x-data="feedComposer({{ Js::from(old('body', '')) }})" @submit="busy = true" class="fd-card p-4">
                @csrf

                <template x-for="id in ids" :key="id"><input type="hidden" name="mentions[]" :value="id"></template>

                <div class="flex gap-3">
                    <x-avatar :user="$me" size="h-9 w-9" />

                    <div class="relative min-w-0 flex-1">
                        <textarea x-ref="ta_body" name="body" x-model="body" rows="2" maxlength="3000" class="fd-input"
                                  placeholder="What's on your mind, {{ $me->first_name ?: $me->name }}? Type @@ to tag someone."
                                  @input="mnInput($event, 'body')" @keydown="mnKey($event)" @blur="mnClose()"></textarea>
                        @include('feed._mention-list', ['field' => 'body'])
                    </div>
                </div>

                <div x-show="tagged.length" style="display:none" class="mt-2 flex flex-wrap items-center gap-1.5 pl-12 text-xs">
                    <span class="fd-muted">Tagging:</span>
                    <template x-for="p in tagged" :key="p.id"><span class="fd-tag" x-text="'@' + p.label"></span></template>
                </div>

                <div x-show="preview" style="display:none" class="relative mt-3 overflow-hidden rounded-xl">
                    <img :src="preview" alt="Preview" class="max-h-60 w-full object-cover">
                    <button type="button" @click="clear()" title="Remove photo"
                            class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-[#071640]/80 text-sm text-white hover:bg-[#7a1230]">✕</button>
                </div>

                <p x-show="err" x-text="err" style="display:none" class="mt-2 text-sm text-[#d6455f]"></p>
                @error('body')  <p class="mt-2 text-sm text-[#d6455f]">{{ $message }}</p> @enderror
                @error('image') <p class="mt-2 text-sm text-[#d6455f]">{{ $message }}</p> @enderror

                <div class="fd-line mt-3 flex items-center justify-between border-t pt-2.5">
                    <div>
                        <input type="file" name="image" x-ref="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick($event)">
                        <button type="button" @click="$refs.file.click()" class="fd-tool"><span class="text-base">📷</span> Photo</button>
                    </div>

                    <button type="submit" :disabled="empty || busy"
                            class="fd-primary rounded-xl px-6 py-1.5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-text="busy ? 'Posting…' : 'Post'"></span>
                    </button>
                </div>
            </form>

            {{-- New announcement (phones and tablets only) --}}
            @if ($firstAn)
                <div class="fd-card xl:hidden" x-data="{ open: false }">
                    <div class="flex items-center justify-between px-4 pt-3">
                        <span class="fd-text text-xs font-bold uppercase tracking-wide">📢 New announcement</span>
                        @if ($firstAn->created_at->isToday()) <span class="fd-new rounded-full px-2 py-0.5 text-[10px] font-bold">NEW</span> @endif
                    </div>
                    <div class="px-4 pb-3 pt-1.5">
                        <p class="fd-text text-sm font-semibold">{{ $firstAn->title }}</p>
                        <p class="fd-body mt-0.5 text-xs" :class="open ? '' : 'line-clamp-2'">{{ $firstAn->body }}</p>
                        @if (mb_strlen($firstAn->body) > 100)
                            <button type="button" @click="open = !open" class="fd-link mt-1 text-xs font-semibold hover:underline" x-text="open ? 'Show less' : 'Read more'"></button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            @unless ($single)
                <div class="flex flex-wrap gap-2">
                    @foreach ($chips as $key => $label)
                        <a href="{{ route('feed.index', $key === 'all' ? [] : ['filter' => $key]) }}"
                           class="fd-chip {{ $filter === $key ? 'on' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endunless

            {{-- The feed --}}
            <div x-data="feedList({{ Js::from($posts->nextPageUrl()) }})" class="space-y-4">
                <div x-ref="list" class="space-y-4">
                    @include('feed._posts')
                </div>

                <div x-ref="sentinel" x-show="next" style="display:none" class="py-3 text-center">
                    <button type="button" @click="more()" :disabled="loading" class="fd-chip px-6 py-2 disabled:opacity-60">
                        <span x-text="loading ? 'Loading…' : 'Load more posts'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ====================== RIGHT: small announcement card ====================== --}}
        <aside class="hidden xl:block">
            <div class="sticky top-20">
                @include('feed._rail')
            </div>
        </aside>
    </div>
</div>
@endsection
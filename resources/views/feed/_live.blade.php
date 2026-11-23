@unless ($single)
<button type="button" id="feed-new" style="display:none"
        class="fd-primary fixed left-1/2 top-20 z-30 -translate-x-1/2 rounded-full px-5 py-2 text-sm font-semibold shadow-xl"></button>

<script>
(function () {
    var list = document.querySelector('[x-ref="list"]');
    var pill = document.getElementById('feed-new');
    if (!list || !pill) return;

    var LIVE   = {{ Js::from(route('feed.live')) }};
    var RAIL   = {{ Js::from(route('feed.rail')) }};
    var FEED   = {{ Js::from(route('feed.index')) }};
    var FILTER = {{ Js::from($filter) }};

    var timer = null, fails = 0, running = false, stopped = false, loading = false;
    var touched = {};                                    // when you last used each post
    var railEl  = document.getElementById('feed-rail');
    var knownAnn = railEl ? String(railEl.getAttribute('data-latest')) : '0';

    function articles() { return Array.prototype.slice.call(list.querySelectorAll('article[data-id]')); }
    function maxId() {
        var m = 0;
        articles().forEach(function (a) { var i = +a.getAttribute('data-id'); if (i > m) m = i; });
        return m;
    }

    // A post you are using is left alone for a few seconds, so a check can't undo your click
    function mark(e) {
        var a = e.target.closest && e.target.closest('article[data-id]');
        if (a) touched[a.getAttribute('data-id')] = Date.now();
    }
    list.addEventListener('pointerdown', mark, true);
    list.addEventListener('keydown', mark, true);
    function recent(id) { return touched[id] && Date.now() - touched[id] < 6000; }

    // ---------- new posts ----------
    function showPill(n) {
        if (n > 0) {
            pill.textContent = '↑ ' + n + ' new ' + (n === 1 ? 'post' : 'posts');
            pill.style.display = 'block';
        } else {
            pill.style.display = 'none';
        }
    }
    function insert(html) {
        var arts = articles();
        var firstFree = arts.filter(function (a) { return a.getAttribute('data-pinned') !== '1'; })[0];

        if (!arts.length)      list.innerHTML = html;                          // replaces the "No posts yet" card
        else if (firstFree)    firstFree.insertAdjacentHTML('beforebegin', html);
        else                   list.insertAdjacentHTML('beforeend', html);
    }
    async function loadNew() {
        if (loading) return;
        loading = true;
        try {
            var res = await fetch(FEED + '?after=' + maxId() + (FILTER === 'mine' ? '&filter=mine' : ''), {
                credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('failed');
            var html = (await res.text()).trim();
            if (html) insert(html);
            showPill(0);
        } catch (e) {}
        loading = false;
    }
    pill.addEventListener('click', function () { loadNew(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

    // ---------- what changed on the posts on screen ----------
    function apply(r) {
        var A = window.Alpine;

        if (A) {
            articles().forEach(function (el) {
                var id = el.getAttribute('data-id'), d = r.posts && r.posts[id];
                if (!d) return;

                var data = A.$data(el);
                if (!data) return;

                if (!recent(id)) {
                    data.counts = d.counts;
                    data.mine   = d.mine;

                    if (d.comments !== data.commentsCount) {
                        if (data.commentsOpen && data.loaded) { data.loadComments(); }          // the open comments refresh themselves
                        else { data.commentsCount = d.comments; data.loaded = false; }
                    }
                }

                if (!data.editing && d.body !== data.body) {
                    data.body = d.body; data.mentions = d.mentions; data.edited = d.edited;
                }

                data.pinned = d.pinned;
                el.setAttribute('data-pinned', d.pinned ? '1' : '0');
            });
        }

        // Posts that were deleted
        (r.deleted || []).forEach(function (id) {
            var el = list.querySelector('article[data-id="' + id + '"]');
            if (!el) return;
            el.style.transition = 'opacity .3s'; el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 300);
        });

        // New posts: slide in when you are at the top, otherwise show the pill
        var n = r.new ? r.new.count : 0;
        showPill(n);
        if (n > 0 && window.scrollY < 160) loadNew();

        // A new announcement refreshes the card on the right
        if (railEl && String(r.announcement) !== knownAnn) {
            knownAnn = String(r.announcement);
            fetch(RAIL, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (x) { return x.ok ? x.text() : ''; })
                .then(function (html) {
                    if (!html.trim()) return;
                    var cur = document.getElementById('feed-rail');
                    if (cur) { cur.outerHTML = html; railEl = document.getElementById('feed-rail'); }
                });
        }
    }

    // ---------- the check ----------
    function delay() {
        if (fails) return Math.min(60000, 5000 * Math.pow(2, fails - 1));
        return document.hidden ? 30000 : 5000;
    }
    function schedule(ms) {
        if (stopped) return;
        clearTimeout(timer);
        timer = setTimeout(poll, ms === undefined ? delay() : ms);
    }
    async function poll() {
        if (stopped || running) return;
        running = true;
        clearTimeout(timer);

        try {
            if (document.hidden) return;

            var ids = articles().map(function (a) { return a.getAttribute('data-id'); }).slice(0, 60).join(',');
            var res = await fetch(LIVE + '?ids=' + ids + '&after=' + maxId() + '&filter=' + FILTER, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.status === 401 || res.status === 419) { stopped = true; return; }
            if (!res.ok) throw new Error('bad status');

            fails = 0;
            apply(await res.json());
        } catch (e) {
            fails++;
        } finally {
            running = false;
            schedule();
        }
    }

    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    window.addEventListener('focus', function () { poll(); });
    window.addEventListener('online', function () { fails = 0; poll(); });

    schedule(3000);
})();
</script>
@endunless
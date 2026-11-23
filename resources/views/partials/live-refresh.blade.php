{{-- Live refresh: a page opts in with  @section('live', '10')  (seconds between checks) --}}
<div id="live-pill" style="display:none"
     class="pointer-events-none fixed bottom-4 right-4 z-40 flex items-center gap-1.5 rounded-full bg-[#0a1f55] px-3.5 py-1.5 text-xs font-semibold text-white shadow-lg ring-1 ring-[#f6c945]/60">
    <span class="text-[#f6c945]">↻</span> Updated
</div>

<script>
(function () {
    var main = document.getElementById('live-main');
    if (!main) return;

    var secs = parseInt(main.getAttribute('data-live'), 10);
    if (!secs || secs < 2) return;                      // this page does not refresh by itself

    var base = main.innerHTML;                          // the page exactly as the server sent it
    var pill = document.getElementById('live-pill');
    var timer = null, pillT = null, fails = 0, running = false, stopped = false;

    function delay() {
        if (fails) return Math.min(60000, secs * 1000 * Math.pow(2, fails));   // back off when the server is unreachable
        return document.hidden ? 30000 : secs * 1000;
    }
    function schedule(ms) {
        if (stopped) return;
        clearTimeout(timer);
        timer = setTimeout(check, ms === undefined ? delay() : ms);
    }
    function flash() {
        if (!pill) return;
        pill.style.display = 'flex';
        clearTimeout(pillT);
        pillT = setTimeout(function () { pill.style.display = 'none'; }, 1800);
    }

    // True while the person is busy: typing, choosing, selecting text, or a popup is open
    function userBusy() {
        var a = document.activeElement;
        if (a && main.contains(a) && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName)) return true;

        var sel = window.getSelection && window.getSelection();
        if (sel && !sel.isCollapsed && sel.anchorNode && main.contains(sel.anchorNode)) return true;

        var cm = document.getElementById('cm');
        if (cm && !cm.classList.contains('hidden')) return true;

        var overlays = main.querySelectorAll('.fixed');
        for (var i = 0; i < overlays.length; i++) {
            if (getComputedStyle(overlays[i]).display !== 'none') return true;
        }
        return false;
    }

    // Only used when the Alpine morph plugin is not installed
    function dirty() {
        var f = main.querySelectorAll('input, textarea, select');
        for (var i = 0; i < f.length; i++) {
            var el = f[i];
            if (el.tagName === 'SELECT') {
                for (var j = 0; j < el.options.length; j++) if (el.options[j].selected !== el.options[j].defaultSelected) return true;
            } else if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked !== el.defaultChecked) return true;
            } else if (el.type !== 'hidden' && el.value !== el.defaultValue) return true;
        }
        return false;
    }

    function apply(next) {
        if (window.Alpine && typeof Alpine.morph === 'function') {
            // Updates only what changed and keeps tabs, filters and typed text
            Alpine.morph(main, next.outerHTML, {
                key: function (el) { return el.getAttribute ? (el.getAttribute('data-key') || el.id || undefined) : undefined; }
            });
            return true;
        }
        if (dirty()) return false;
        main.innerHTML = next.innerHTML;
        return true;
    }

    async function check() {
        if (stopped || running) return;
        running = true;
        clearTimeout(timer);

        try {
            if (document.hidden) return;

            var res = await fetch(location.href, {
                credentials: 'same-origin', cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Live-Refresh': '1' }
            });

            // Logged out, or sent to another page: stop quietly
            if (res.status === 401 || res.status === 419) { stopped = true; return; }
            if (!res.ok) throw new Error('bad status');
            if (res.redirected && new URL(res.url).pathname !== location.pathname) { stopped = true; return; }

            var doc  = new DOMParser().parseFromString(await res.text(), 'text/html');
            var next = doc.getElementById('live-main');
            if (!next || !next.getAttribute('data-live')) { stopped = true; return; }

            fails = 0;
            var html = next.innerHTML;

            if (html !== base) {
                if (userBusy()) return;               // try again on the next check
                if (apply(next)) { base = html; flash(); }
            }
        } catch (e) {
            fails++;
        } finally {
            running = false;
            schedule();
        }
    }

    document.addEventListener('visibilitychange', function () { if (!document.hidden) check(); });
    window.addEventListener('focus', function () { check(); });
    window.addEventListener('online', function () { fails = 0; check(); });

    schedule(secs * 1000);
})();
</script>
{{-- Global confirmation modal: replaces the browser's confirm() and alert() popups --}}
<div id="cm" class="fixed inset-0 z-[100] hidden items-center justify-center bg-[#071640]/70 p-4 backdrop-blur-sm"
     role="dialog" aria-modal="true" aria-labelledby="cm-title" aria-describedby="cm-msg">
    <div id="cm-card" class="w-full max-w-md translate-y-2 scale-95 overflow-hidden rounded-2xl bg-white opacity-0 shadow-2xl transition duration-150">
        <div id="cm-bar" class="h-1.5"></div>

        <div class="p-6">
            <div class="flex items-start gap-4">
                <div id="cm-icon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-2xl"></div>
                <div class="min-w-0 flex-1">
                    <h2 id="cm-title" class="break-words text-lg font-bold leading-snug text-[#0a1f55]"></h2>
                    <p id="cm-msg" class="mt-1.5 whitespace-pre-line break-words text-sm leading-relaxed text-slate-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button id="cm-cancel" type="button"
                        class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#0a1f55]">Cancel</button>
                <button id="cm-ok" type="button"></button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var root = $('cm'), card = $('cm-card'), bar = $('cm-bar'), icon = $('cm-icon'),
        title = $('cm-title'), msg = $('cm-msg'), ok = $('cm-ok'), cancel = $('cm-cancel');

    // Full class names are written out so Tailwind includes them
    var TONES = {
        danger: { icon: '⚠️', iconBg: 'bg-[#7a1230]/10', bar: 'bg-[#7a1230]',
                  ok: 'bg-[#7a1230] shadow-[#7a1230]/20 hover:bg-[#5a0c22] focus:ring-[#7a1230]' },
        warn:   { icon: '🔒', iconBg: 'bg-[#f6c945]/30', bar: 'bg-[#f6c945]',
                  ok: 'bg-[#0a1f55] shadow-[#0a1f55]/20 hover:bg-[#12307f] focus:ring-[#0a1f55]' },
        info:   { icon: '❓', iconBg: 'bg-[#0a1f55]/10', bar: 'bg-[#0a1f55]',
                  ok: 'bg-[#0a1f55] shadow-[#0a1f55]/20 hover:bg-[#12307f] focus:ring-[#0a1f55]' }
    };

    var done = null, opener = null, timer = null;

    // ---------- the modal itself ----------
    function open(o) {
        o = o || {};
        if (done) { var prev = done; done = null; prev(false); }

        var t = TONES[o.tone] || TONES.info;
        opener = document.activeElement;

        icon.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-2xl ' + t.iconBg;
        icon.textContent = o.icon || t.icon;
        bar.className = 'h-1.5 ' + t.bar;
        title.textContent = o.title || 'Please confirm';
        msg.textContent = o.message || '';
        msg.classList.toggle('hidden', !o.message);

        ok.className = 'rounded-xl px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 ' + t.ok;
        ok.textContent = o.confirmText || 'Confirm';
        cancel.textContent = o.cancelText || 'Cancel';
        cancel.classList.toggle('hidden', !!o.hideCancel);

        clearTimeout(timer);
        root.classList.remove('hidden');
        root.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(function () {
            card.classList.remove('translate-y-2', 'scale-95', 'opacity-0');
        });

        // Dangerous actions start on Cancel, so a stray Enter key can't delete anything
        (o.tone === 'danger' && !o.hideCancel ? cancel : ok).focus();

        return new Promise(function (resolve) { done = resolve; });
    }

    function close(result) {
        if (!done) return;
        var fn = done; done = null;

        card.classList.add('translate-y-2', 'scale-95', 'opacity-0');
        timer = setTimeout(function () {
            root.classList.add('hidden');
            root.classList.remove('flex');
        }, 150);
        document.body.classList.remove('overflow-hidden');

        if (opener && opener.focus) { try { opener.focus(); } catch (e) {} }
        fn(result);
    }

    ok.addEventListener('click', function () { close(true); });
    cancel.addEventListener('click', function () { close(false); });
    root.addEventListener('mousedown', function (e) { if (e.target === root) close(false); });

    document.addEventListener('keydown', function (e) {
        if (!done) return;

        if (e.key === 'Escape') {
            e.preventDefault(); e.stopPropagation();
            close(false);
        } else if (e.key === 'Tab') {
            e.preventDefault();
            var els = cancel.classList.contains('hidden') ? [ok] : [cancel, ok];
            var i = els.indexOf(document.activeElement);
            els[(i + (e.shiftKey ? -1 : 1) + els.length) % els.length].focus();
        }
    }, true);

    // Available everywhere:  await window.confirmModal({ title, message, confirmText, tone })
    window.confirmModal = open;

    // The browser's alert() also becomes a modal
    window.alert = function (m) {
        open({ title: 'Notice', message: String(m), confirmText: 'OK', tone: 'info', hideCancel: true });
    };

    // ---------- turn existing confirm() calls into modals ----------
    // Reads the message out of  onsubmit="return confirm('…')"  and  onclick="return confirm('…')"
    function captured(code) {
        var m = null;
        try { new Function('confirm', code)(function (x) { m = x; return true; }); } catch (e) {}
        return m;
    }

    var VERBS = {
        'delete':   ['danger', 'Delete'],
        'remove':   ['danger', 'Remove'],
        'reset':    ['danger', 'Yes, reset'],
        'finalize': ['warn',   'Finalize'],
        'unlock':   ['warn',   'Unlock'],
        'leave':    ['warn',   'Leave'],
        'submit':   ['info',   'Submit']
    };

    // "Delete Ana? This cannot be undone."  becomes a title and a message
    function spec(el, code) {
        var d = el.dataset || {};
        var text = ('confirm' in d && d.confirm) ? d.confirm : (code ? captured(code) : null);
        if (text === null || text === undefined) { if (!('confirm' in d)) return null; text = 'Are you sure?'; }

        text = String(text).trim();
        var parts = text.match(/^([\s\S]*?\?)(?:\s+([\s\S]*))?$/);
        var head  = parts ? parts[1] : text;
        var body  = parts && parts[2] ? parts[2] : '';
        var verb  = (head.match(/^[A-Za-z]+/) || [''])[0].toLowerCase();
        var v     = VERBS[verb] || ['info', 'Confirm'];
        var tone  = d.confirmTone || v[0];

        if (!body && tone === 'danger') body = 'Please confirm that you want to continue.';

        return {
            title: d.confirmTitle || head,
            message: d.confirmMessage !== undefined ? d.confirmMessage : body,
            confirmText: d.confirmText || v[1],
            tone: tone
        };
    }

    var skip = new WeakSet();

    // Forms:  <form onsubmit="return confirm('…')">  or  <form data-confirm="Delete this?">
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || skip.has(form)) return;

        var code = form.getAttribute('onsubmit');
        var inline = !!code && code.indexOf('confirm(') !== -1;
        if (!inline && !form.hasAttribute('data-confirm')) return;

        var s = spec(form, inline ? code : null);
        if (!s) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        var submitter = e.submitter || null;

        open(s).then(function (yes) {
            if (!yes) return;

            skip.add(form);
            if (inline) form.removeAttribute('onsubmit');

            try {
                if (form.requestSubmit) {
                    if (submitter && submitter.form === form) form.requestSubmit(submitter);
                    else form.requestSubmit();
                } else {
                    HTMLFormElement.prototype.submit.call(form);
                }
            } finally {
                if (inline) form.setAttribute('onsubmit', code);
                skip.delete(form);
            }
        });
    }, true);

    // Buttons and links:  <button onclick="return confirm('…')">  or  <button data-confirm="…">
    document.addEventListener('click', function (e) {
        var t = e.target && e.target.closest ? e.target.closest('[onclick*="confirm("], [data-confirm]') : null;
        if (!t || t instanceof HTMLFormElement || skip.has(t)) return;

        var code = t.getAttribute('onclick');
        var inline = !!code && code.indexOf('confirm(') !== -1;
        if (!inline && !t.hasAttribute('data-confirm')) return;

        var s = spec(t, inline ? code : null);
        if (!s) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        open(s).then(function (yes) {
            if (!yes) return;

            skip.add(t);
            if (inline) t.removeAttribute('onclick');

            try { t.click(); }
            finally {
                if (inline) t.setAttribute('onclick', code);
                skip.delete(t);
            }
        });
    }, true);
})();
</script>
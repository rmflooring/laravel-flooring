/**
 * rich-notes.js — lightweight floating rich-text toolbar for contenteditable note fields.
 * Loaded before estimate.js / sale.js so initRichNotesIn() is available to them.
 */
(function () {
    'use strict';

    // ── Floating toolbar ──────────────────────────────────────────────────────
    let activeField = null;
    let savedRange  = null;

    // Inject styles
    document.head.insertAdjacentHTML('beforeend', [
        '<style>',
        '#rn-toolbar{display:none;position:fixed;z-index:10000;background:#1f2937;border-radius:6px;',
        'padding:4px 6px;box-shadow:0 4px 16px rgba(0,0,0,.4);flex-direction:row;align-items:center;gap:3px;}',
        '#rn-toolbar button{background:none;border:none;color:#f9fafb;cursor:pointer;font-size:13px;',
        'padding:2px 7px;border-radius:4px;line-height:1.5;font-family:inherit;}',
        '#rn-toolbar button:hover{background:rgba(255,255,255,.15);}',
        '#rn-toolbar button[data-cmd="bold"] b{font-size:14px;}',
        '.rn-sep{width:1px;height:16px;background:rgba(255,255,255,.25);margin:0 2px;flex-shrink:0;}',
        '.rn-color-wrap{position:relative;cursor:pointer;display:flex;align-items:center;padding:2px 4px;}',
        '.rn-color-swatch{display:inline-block;width:16px;height:16px;border-radius:3px;',
        'background:#e11d48;border:1px solid rgba(255,255,255,.4);}',
        '#rn-color{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;border:none;}',
        '.rich-notes-field{outline:none;cursor:text;}',
        '.rich-notes-field:empty:before{content:attr(placeholder);color:#9ca3af;pointer-events:none;}',
        '</style>',
    ].join(''));

    // Build toolbar DOM
    const toolbar = document.createElement('div');
    toolbar.id = 'rn-toolbar';
    toolbar.innerHTML =
        '<button type="button" data-cmd="bold"        title="Bold"><b>B</b></button>' +
        '<button type="button" data-cmd="italic"      title="Italic"><i>I</i></button>' +
        '<button type="button" data-cmd="underline"   title="Underline"><u>U</u></button>' +
        '<span class="rn-sep"></span>' +
        '<span class="rn-color-wrap" title="Text colour">' +
            '<span class="rn-color-swatch" id="rn-swatch"></span>' +
            '<input type="color" id="rn-color" value="#e11d48">' +
        '</span>' +
        '<span class="rn-sep"></span>' +
        '<button type="button" data-cmd="removeFormat" title="Clear formatting">✕</button>';
    document.body.appendChild(toolbar);

    const swatch     = document.getElementById('rn-swatch');
    const colorInput = document.getElementById('rn-color');

    // ── Helpers ───────────────────────────────────────────────────────────────

    function saveRange() {
        const sel = window.getSelection();
        savedRange = (sel && sel.rangeCount) ? sel.getRangeAt(0).cloneRange() : null;
    }

    function restoreRange() {
        if (!savedRange) return;
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
    }

    /** Sync contenteditable HTML → the immediately following hidden input */
    function syncHidden(field) {
        if (!field) return;
        const h = field.nextElementSibling;
        if (h && h.type === 'hidden') {
            h.value = (field.innerHTML === '<br>' || field.innerHTML === '') ? '' : field.innerHTML;
        }
    }

    function fieldFromNode(node) {
        if (!node) return null;
        const el = node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
        return el ? el.closest('.rich-notes-field') : null;
    }

    function positionToolbar() {
        const sel = window.getSelection();
        if (!sel || !sel.rangeCount) return;
        const rect = sel.getRangeAt(0).getBoundingClientRect();
        toolbar.style.display = 'flex';
        const tw = toolbar.offsetWidth || 200;
        let left = rect.left + rect.width / 2 - tw / 2 + window.scrollX;
        let top  = rect.top  - toolbar.offsetHeight - 8 + window.scrollY;
        left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));
        if (top < window.scrollY + 4) top = rect.bottom + 8 + window.scrollY;
        toolbar.style.left = left + 'px';
        toolbar.style.top  = top  + 'px';
    }

    function showToolbar(field) {
        activeField = field;
        saveRange();
        positionToolbar();
    }

    function hideToolbar() {
        toolbar.style.display = 'none';
        activeField = null;
    }

    // ── Toolbar interaction ───────────────────────────────────────────────────

    toolbar.querySelectorAll('button[data-cmd]').forEach(function (btn) {
        btn.addEventListener('mousedown', function (e) {
            e.preventDefault(); // keep focus in contenteditable
            restoreRange();
            document.execCommand(btn.dataset.cmd, false, null);
            syncHidden(activeField);
        });
    });

    // Color: save range on mousedown (before input steals focus), apply on input
    colorInput.addEventListener('mousedown', function () { saveRange(); });
    colorInput.addEventListener('input', function () {
        swatch.style.background = this.value;
        restoreRange();
        document.execCommand('foreColor', false, this.value);
        syncHidden(activeField);
    });

    // ── Global selection detection ────────────────────────────────────────────

    document.addEventListener('mouseup', function (e) {
        if (toolbar.contains(e.target)) return;
        const sel = window.getSelection();
        const f   = fieldFromNode(sel && sel.anchorNode);
        if (f && sel.toString().length) {
            showToolbar(f);
        } else {
            hideToolbar();
        }
    });

    document.addEventListener('keyup', function () {
        const sel = window.getSelection();
        const f   = fieldFromNode(sel && sel.anchorNode);
        if (f && sel.toString().length) {
            showToolbar(f);
        } else if (!toolbar.contains(document.activeElement)) {
            hideToolbar();
        }
    });

    document.addEventListener('mousedown', function (e) {
        if (!toolbar.contains(e.target) && !e.target.closest('.rich-notes-field')) {
            hideToolbar();
        }
    });

    // ── Per-field init ────────────────────────────────────────────────────────

    function initField(el) {
        if (el.dataset.rnInit) return;
        el.dataset.rnInit = '1';
        el.addEventListener('input', function () { syncHidden(el); });
        // Initial sync in case value was pre-populated server-side via innerHTML
        syncHidden(el);
    }

    /**
     * Call this after adding new rows to wire up any .rich-notes-field elements.
     * @param {Element|Document} container
     */
    window.initRichNotesIn = function (container) {
        (container || document).querySelectorAll('.rich-notes-field').forEach(initField);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initRichNotesIn(document);
    });

}());

<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Runs on every page load — sets language from user saved preference.
 */
function local_langswitch_after_config() {
    global $SESSION, $USER, $CFG;

    $allowed = ['en', 'hi', 'mr', 'gu'];
    $lang    = 'en'; // default

    // ── Step 1: Check if user is logged in ──────────────────────────
    if (!empty($USER->id) && !isguestuser()) {

        // Logged in — trust DB preference over cookie
        $db_lang = get_user_preferences('local_langswitch_lang', '', $USER->id);

        if (!empty($db_lang) && in_array($db_lang, $allowed, true)) {
            $lang = $db_lang;

            // Sync cookie to match DB preference
            setcookie(
                'moodle_langswitch',
                $lang,
                [
                    'expires'  => time() + (365 * 24 * 60 * 60),
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        } else {
            // User has no preference saved — use English and clear cookie
            $lang = 'en';
            setcookie(
                'moodle_langswitch',
                '',
                [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'secure'   => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

    } else {
        // ── Step 2: Not logged in — use cookie (for login page) ─────
        $cookie_lang = isset($_COOKIE['moodle_langswitch'])
            ? clean_param($_COOKIE['moodle_langswitch'], PARAM_ALPHA)
            : 'en';

        if (in_array($cookie_lang, $allowed, true)) {
            $lang = $cookie_lang;
        }
    }

    // Apply language
    $SESSION->lang = $lang;

    if (function_exists('force_current_language')) {
        force_current_language($lang);
    }
}

/**
 * Fires when user logs out — clear language cookie.
 */
function local_langswitch_user_logout() {
    setcookie(
        'moodle_langswitch',
        '',
        [
            'expires'  => time() - 3600, // expired in past = delete
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
    unset($_COOKIE['moodle_langswitch']);
}

/**
 * Add meta tags + early inline script to prevent Chrome's
 * native translate popup from appearing on any page.
 */
function local_langswitch_before_standard_html_head() {
    return '<meta name="google" content="notranslate">'
         . '<meta http-equiv="Content-Language" content="en">'
         . '<script>document.documentElement.setAttribute("translate","no");</script>';
}

/**
 * Inject auto-translator JS before footer on every page.
 */
function local_langswitch_before_footer() {
    global $PAGE, $SESSION, $CFG;

    $lang    = $SESSION->lang ?? 'en';
    $wwwroot = $CFG->wwwroot;
    $sesskey = sesskey();

    local_langswitch_inject_ui();

    if ($lang === 'en') return;

    // NOTE: earlier versions hid the whole <html> element
    // (visibility:hidden) until translation finished, with a 2s
    // safety timer. That put a hard floor of up to 2000ms — and in the
    // worst case, however long the OpenAI round trip took — on every
    // single page load. Real UI translation should never block first
    // paint on a network call to a translation service. Instead: paint
    // immediately with the original text, mark in-flight nodes with a
    // subtle "pending" style, and swap text in as translations arrive.
    $PAGE->requires->js_init_code("(function(){"
        . "var s=document.createElement('style');s.id='langswitch-pending-style';"
        . "s.textContent='[data-ls-pending]{transition:opacity 120ms ease;opacity:.55;}';"
        . "document.head.appendChild(s);"
        . "})();");

    $js = <<<JS
(function() {

    const LANG      = '{$lang}';
    const WWWROOT   = '{$wwwroot}';
    const SESSKEY   = '{$sesskey}';
    const CACHE_KEY = 'langswitch_' + LANG;
    const CACHE_VER = 'v4';

    // ── Guards against repeated / duplicate translation work ──────────
    // A text node, once collected, is marked "handled" immediately — even
    // before its translation comes back. This stops the loop where the
    // translated text itself (e.g. Hindi) gets picked up as "new" content
    // by the MutationObserver and re-sent to the API forever.
    const translatedTextNodes = new WeakSet(); // Text nodes already handled
    const inFlightTexts       = new Map();     // text -> array of waiting callbacks (dedupes concurrent API calls)

    // ── Tags to skip ─────────────────────────────────────────────────
    const SKIP_TAGS = new Set([
        'SCRIPT','STYLE','NOSCRIPT','IFRAME',
        'INPUT','TEXTAREA','CODE','PRE',
        'HEAD','META','LINK','TITLE'
    ]);

    const SKIP_SELECTORS = [
        '[data-no-translate]',
        '.no-translate',
        '.lang-switcher-widget',
        '.lang-switcher-btn',
        '.material-icons',
        '.material-icons-outlined',
        '.material-icons-round',
        '.material-icons-sharp',
        '.material-icons-two-tone',
        '.material-symbols-rounded',
        '.material-symbols-outlined',
        '.material-symbols-sharp',
        '.fa', '.fas', '.far', '.fab', '.fal',
        '.glyphicon',
        '[class*="icon-"]',
    ];

    // ── Load cache ───────────────────────────────────────────────────
    function getCache() {
        try {
            const raw = localStorage.getItem(CACHE_KEY);
            if (!raw) return {};
            const obj = JSON.parse(raw);
            if (obj.__ver !== CACHE_VER) return {};
            return obj;
        } catch(e) { return {}; }
    }

    function saveCache(cache) {
        try {
            cache.__ver = CACHE_VER;
            localStorage.setItem(CACHE_KEY, JSON.stringify(cache));
        } catch(e) {}
    }

    // ── Collect visible text nodes ───────────────────────────────────
    function collectTextNodes(root) {
        root = root || document.body;
        const walker = document.createTreeWalker(
            root,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(node) {
                    const text   = node.nodeValue.trim();
                    const parent = node.parentElement;

                    if (!text || text.length < 2)
                        return NodeFilter.FILTER_REJECT;

                    if (translatedTextNodes.has(node))
                        return NodeFilter.FILTER_REJECT;

                    // Skip Material Icon ligature names (snake_case words)
                    if (/^[a-z][a-z0-9_]+$/.test(text))
                        return NodeFilter.FILTER_REJECT;

                    // Skip all-uppercase short strings (abbreviations/codes)
                    if (/^[A-Z0-9_]{1,10}$/.test(text))
                        return NodeFilter.FILTER_REJECT;

                    if (/^https?:\/\//.test(text))
                        return NodeFilter.FILTER_REJECT;

                    if (!parent || SKIP_TAGS.has(parent.tagName))
                        return NodeFilter.FILTER_REJECT;

                    for (const sel of SKIP_SELECTORS) {
                        try {
                            if (parent.closest(sel))
                                return NodeFilter.FILTER_REJECT;
                        } catch(e) {}
                    }

                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );

        const nodes = [];
        let node;
        while ((node = walker.nextNode())) {
            nodes.push(node);
        }
        return nodes;
    }

    // ── Collect <option> elements separately (dropdown text) ─────────
    function collectOptionElements(root) {
        root = root || document.body;
        const options = root.querySelectorAll('option');
        const items = [];

        options.forEach(function(opt) {
            if (opt.dataset.lsDone === '1') return;
            const text = opt.textContent.trim();
            if (text && text.length > 1 && !/^[\d\s\.\,\:\-\+\%\#\/\\@\_\(\)]+$/.test(text)) {
                items.push(opt);
            }
        });

        return items;
    }

    function applyOptionTranslations(optionElements, translations) {
        optionElements.forEach(function(opt) {
            const original = opt.textContent.trim();
            if (translations[original]) {
                opt.textContent = translations[original];
            }
            delete opt.dataset.lsPending;
        });
    }

    // ── Collect input placeholders ────────────────────────────────────
    function collectPlaceholders(root) {
        root = root || document.body;
        const inputs = root.querySelectorAll('input[placeholder], textarea[placeholder]');
        const items = [];
        inputs.forEach(function(el) {
            if (el.dataset.lsPhDone === '1') return;
            const text = el.getAttribute('placeholder');
            if (text && text.trim().length > 1) {
                items.push(el);
            }
        });
        return items;
    }

    function applyPlaceholderTranslations(elements, translations) {
        elements.forEach(function(el) {
            const original = el.getAttribute('placeholder').trim();
            if (translations[original]) {
                el.setAttribute('placeholder', translations[original]);
            }
            delete el.dataset.lsPending;
        });
    }

    // ── Apply translations to text nodes ──────────────────────────────
    function applyTranslations(nodes, translations) {
        nodes.forEach(function(node) {
            const original = node.nodeValue.trim();
            if (translations[original]) {
                node.nodeValue = node.nodeValue.replace(original, translations[original]);
            }
            const parent = node.parentElement;
            if (parent) delete parent.dataset.lsPending;
        });
    }

    function markPending(nodes, optionEls, placeholderEls) {
        nodes.forEach(function(n) {
            if (n.parentElement) n.parentElement.dataset.lsPending = '1';
        });
        optionEls.forEach(function(el) { el.dataset.lsPending = '1'; });
        placeholderEls.forEach(function(el) { el.dataset.lsPending = '1'; });
    }

    // ── Call translate.php → OpenAI (server-side cached) ──────────────
    function translateViaAPI(texts, callback) {
        if (!texts || texts.length === 0) return;

        fetch(WWWROOT + '/local/langswitch/ajax/translate.php', {
            method  : 'POST',
            headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
            body    : 'texts='    + encodeURIComponent(JSON.stringify(texts))
                    + '&lang='    + LANG
                    + '&sesskey=' + SESSKEY
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.translations) {
                callback(data.translations);
            } else {
                if (data.error) console.warn('LangSwitch error:', data.error);
                callback({});
            }
        })
        .catch(function(err) {
            console.warn('LangSwitch API error:', err);
            callback({});
        });
    }

    // ── Central translation resolver: cache + deduped/batched API ────
    function fetchTranslations(texts, onDone) {
        const cache  = getCache();
        const result = {};
        const need   = [];

        texts.forEach(function(text) {
            if (cache[text] && cache[text] !== text) {
                result[text] = cache[text];
            } else if (need.indexOf(text) === -1) {
                need.push(text);
            }
        });

        if (need.length === 0) {
            onDone(result);
            return;
        }

        let remaining = need.length;
        function noteDone(text, translations) {
            if (translations && translations[text]) result[text] = translations[text];
            remaining--;
            if (remaining <= 0) onDone(result);
        }

        const toRequest = [];
        need.forEach(function(text) {
            if (inFlightTexts.has(text)) {
                inFlightTexts.get(text).push(function(translations) { noteDone(text, translations); });
            } else {
                inFlightTexts.set(text, [function(translations) { noteDone(text, translations); }]);
                toRequest.push(text);
            }
        });

        if (toRequest.length === 0) return;

        const BATCH = 50;
        for (let i = 0; i < toRequest.length; i += BATCH) {
            const batch = toRequest.slice(i, i + BATCH);

            translateViaAPI(batch, function(translations) {
                if (Object.keys(translations).length > 0) {
                    const updatedCache = getCache();
                    Object.assign(updatedCache, translations);
                    saveCache(updatedCache);
                }

                batch.forEach(function(text) {
                    const waiters = inFlightTexts.get(text) || [];
                    inFlightTexts.delete(text);
                    waiters.forEach(function(fn) { fn(translations); });
                });
            });
        }
    }

    // ── Main entry point: collect everything on this root, ONE fetch ──
    // Previously this fired three independent network requests (text
    // nodes, <option> elements, placeholders). They're combined into a
    // single fetchTranslations() call so a page needing all three only
    // pays for one HTTP round trip instead of three.
    function processRoot(root) {
        const nodes          = collectTextNodes(root);
        const optionEls      = collectOptionElements(root);
        const placeholderEls = collectPlaceholders(root);

        if (nodes.length === 0 && optionEls.length === 0 && placeholderEls.length === 0) {
            return;
        }

        // Mark handled immediately so overlapping observers/rescans
        // never re-collect these exact elements while a request is
        // in flight.
        nodes.forEach(function(n) { translatedTextNodes.add(n); });
        optionEls.forEach(function(el) { el.dataset.lsDone = '1'; });
        placeholderEls.forEach(function(el) { el.dataset.lsPhDone = '1'; });

        markPending(nodes, optionEls, placeholderEls);

        const allTexts = [...new Set([
            ...nodes.map(function(n) { return n.nodeValue.trim(); }),
            ...optionEls.map(function(el) { return el.textContent.trim(); }),
            ...placeholderEls.map(function(el) { return el.getAttribute('placeholder').trim(); }),
        ].filter(function(t) { return t.length > 1; }))];

        if (allTexts.length === 0) return;

        fetchTranslations(allTexts, function(translations) {
            applyTranslations(nodes, translations);
            applyOptionTranslations(optionEls, translations);
            applyPlaceholderTranslations(placeholderEls, translations);
        });
    }

    // ── DOM ready — page paints immediately, translation fills in after ──
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { processRoot(document.body); });
    } else {
        processRoot(document.body);
    }

    // ── Single consolidated observer for all dynamic content ─────────
    // Replaces the previous body observer + calendar-specific observer +
    // per-selector observers + fixed-delay polling timers (300/800/
    // 1500/2500/4000ms x2 sets). MutationObserver already tells us when
    // something changed — polling on top of it was pure duplicate work
    // that could fire multiple overlapping translation batches for the
    // same re-render (e.g. calendar month navigation).
    let debounceTimer = null;
    const pendingRoots = new Set();

    const globalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            m.addedNodes.forEach(function(n) {
                if (n.nodeType === 1 && !SKIP_TAGS.has(n.tagName)) {
                    pendingRoots.add(n);
                }
            });
            if (m.type === 'characterData' && m.target.parentElement) {
                pendingRoots.add(m.target.parentElement);
            }
        });

        if (pendingRoots.size === 0) return;

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            const roots = Array.from(pendingRoots);
            pendingRoots.clear();
            roots.forEach(function(root) {
                if (document.body.contains(root)) processRoot(root);
            });
        }, 400);
    });

    if (document.body) {
        globalObserver.observe(document.body, {
            childList     : true,
            subtree       : true,
            characterData : true,
        });
    }

    // Bootstrap modals sometimes render content that the observer catches
    // fine, but a small settle delay avoids racing modal animation.
    if (window.jQuery) {
        jQuery(document).on('shown.bs.modal', function(e) {
            setTimeout(function() { processRoot(e.target); }, 150);
        });
    }

    // Manual rescan hook for any custom modal/popup implementation.
    window.langSwitchRescan = function(root) {
        processRoot(root || document.body);
    };
})();
JS;

    $PAGE->requires->js_init_code($js);
}

/**
 * Inject the floating language switcher UI widget.
 */
function local_langswitch_inject_ui() {
    global $PAGE, $SESSION, $CFG;

    $current = $SESSION->lang ?? 'en';
    $wwwroot = $CFG->wwwroot;
    $sesskey = sesskey();

    $active_en = ($current === 'en') ? 'active' : '';
    $active_hi = ($current === 'hi') ? 'active' : '';
    $active_mr = ($current === 'mr') ? 'active' : '';
    $active_gu = ($current === 'gu') ? 'active' : '';

    $ui_js = <<<JS

(function() {
    document.addEventListener('DOMContentLoaded', function() {
        document.documentElement.setAttribute('translate', 'no');
        document.documentElement.classList.add('notranslate');
        document.body.setAttribute('translate', 'no');
        document.body.classList.add('notranslate');

        const widget = document.createElement('div');
        widget.className = 'lang-switcher-widget';
        widget.innerHTML = '<div class="lang-switcher-inner">'
            + '<span class="lang-switcher-icon" title="Drag to move">🌐</span>'
            + '<div class="lang-switcher-buttons">'
            + '<button class="lang-switcher-btn {$active_en}" data-lang="en">EN</button>'
            + '<button class="lang-switcher-btn {$active_hi}" data-lang="hi">हि</button>'
            + '<button class="lang-switcher-btn {$active_mr}" data-lang="mr">म</button>'
            + '<button class="lang-switcher-btn {$active_gu}" data-lang="gu">ગુ</button>'
            + '</div>'
            + '</div>';

        const style = document.createElement('style');
        style.textContent = `
            .lang-switcher-widget {
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 99999;
                background: #003152;
                border-radius: 50px;
                padding: 6px 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                gap: 6px;
                user-select: none;
            }
            .lang-switcher-widget.lang-switcher-dragging {
                box-shadow: 0 8px 28px rgba(0,0,0,0.45);
                opacity: 0.92;
                cursor: grabbing;
            }
            .lang-switcher-inner {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .lang-switcher-icon {
                font-size: 18px;
                cursor: grab;
                touch-action: none;
                line-height: 1;
                padding: 2px;
            }
            .lang-switcher-icon:active {
                cursor: grabbing;
            }
            .lang-switcher-buttons {
                display: flex;
                gap: 4px;
            }
            .lang-switcher-btn {
                background: transparent;
                border: 1px solid rgba(255,255,255,0.3);
                color: #fff;
                border-radius: 20px;
                padding: 3px 10px;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.2s;
                font-weight: 500;
            }
            .lang-switcher-btn:hover {
                background: rgba(255,255,255,0.2);
            }
            .lang-switcher-btn.active {
                background: #ec9707;
                border-color: #ec9707;
                font-weight: 700;
            }
            .lang-switcher-loading {
                opacity: 0.6;
                pointer-events: none;
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(widget);

        // ── Drag to move (handle = the 🌐 icon, so language buttons stay clickable) ──
        (function initDrag() {
            const handle = widget.querySelector('.lang-switcher-icon');
            const POS_KEY = 'langswitch_widget_pos';

            let dragging  = false;
            let startX, startY, startLeft, startTop;

            function clamp(left, top) {
                const rect = widget.getBoundingClientRect();
                const maxLeft = Math.max(4, window.innerWidth  - rect.width  - 4);
                const maxTop  = Math.max(4, window.innerHeight - rect.height - 4);
                return {
                    left: Math.min(Math.max(4, left), maxLeft),
                    top : Math.min(Math.max(4, top),  maxTop)
                };
            }

            function setPosition(left, top) {
                const pos = clamp(left, top);
                widget.style.left   = pos.left + 'px';
                widget.style.top    = pos.top + 'px';
                widget.style.right  = 'auto';
                widget.style.bottom = 'auto';
            }

            try {
                const saved = JSON.parse(localStorage.getItem(POS_KEY) || 'null');
                if (saved && typeof saved.left === 'number' && typeof saved.top === 'number') {
                    setPosition(saved.left, saved.top);
                }
            } catch(e) {}

            function onPointerDown(e) {
                dragging = true;
                const rect = widget.getBoundingClientRect();
                startLeft = rect.left;
                startTop  = rect.top;
                startX    = e.clientX;
                startY    = e.clientY;
                setPosition(startLeft, startTop);
                widget.classList.add('lang-switcher-dragging');
                try { handle.setPointerCapture(e.pointerId); } catch(err) {}
                e.preventDefault();
            }

            function onPointerMove(e) {
                if (!dragging) return;
                setPosition(startLeft + (e.clientX - startX), startTop + (e.clientY - startY));
            }

            function onPointerUp() {
                if (!dragging) return;
                dragging = false;
                widget.classList.remove('lang-switcher-dragging');
                const rect = widget.getBoundingClientRect();
                try {
                    localStorage.setItem(POS_KEY, JSON.stringify({ left: rect.left, top: rect.top }));
                } catch(e) {}
            }

            handle.addEventListener('pointerdown', onPointerDown);
            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
            window.addEventListener('pointercancel', onPointerUp);

            window.addEventListener('resize', function() {
                if (widget.style.left && widget.style.left !== 'auto') {
                    const rect = widget.getBoundingClientRect();
                    setPosition(rect.left, rect.top);
                }
            });
        })();

        // Handle button clicks
        widget.querySelectorAll('.lang-switcher-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const lang = this.getAttribute('data-lang');

                widget.classList.add('lang-switcher-loading');
                widget.querySelectorAll('.lang-switcher-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                this.textContent = '...';

                try {
                    localStorage.removeItem('langswitch_' + lang);
                } catch(e) {}

                fetch('{$wwwroot}/local/langswitch/switch.php', {
                    method  : 'POST',
                    headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body    : 'lang=' + lang + '&sesskey={$sesskey}'
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'ok') {
                        location.reload();
                    }
                })
                .catch(function(err) {
                    console.warn('Lang switch error:', err);
                    widget.classList.remove('lang-switcher-loading');
                });
            });
        });
    });
})();
JS;

    $PAGE->requires->js_init_code($ui_js);
}

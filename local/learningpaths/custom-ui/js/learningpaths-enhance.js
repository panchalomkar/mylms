/**
 * Learning Paths - Visual Redesign Layer (Moodle 5.0)
 * ----------------------------------------------------
 * Plain / vanilla JavaScript only - no jQuery, no AMD, no build step.
 * Loaded as a plain <script> (footer) via $PAGE->requires->js() next to
 * (not instead of) the plugin's existing AMD modules.
 *
 * IMPORTANT: this file is purely cosmetic. It never intercepts clicks
 * that already have plugin behaviour, never calls actions.php/ajax.php,
 * and never changes form values. It only:
 *   1. Adds SVG icons in a couple of placeholder spans.
 *   2. Adds a "html.lp-ui-v2" class so the redesign CSS can apply.
 *   3. Watches the DOM (MutationObserver) for modals/rows opening and
 *      layers small entrance animations / ripple / focus handling on
 *      top of the markup that already exists.
 */
(function () {
  'use strict';

  if (window.__lpUiV2Loaded) { return; }
  window.__lpUiV2Loaded = true;

  /* ---------------------------------------------------------------
   * 0. Activate the redesign (scopes all CSS rules)
   * ------------------------------------------------------------- */
  document.documentElement.classList.add('lp-ui-v2');

  /* ---------------------------------------------------------------
   * 1. Tiny inline icon set (brand colored via currentColor)
   * ------------------------------------------------------------- */
  var ICONS = {
    route: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="19" r="2.2"></circle><circle cx="18" cy="5" r="2.2"></circle><path d="M6 16.8V13a4 4 0 0 1 4-4h4a4 4 0 0 0 4-4"></path></svg>',
    list:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    cohort:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path></svg>',
    book:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>',
    warn:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    play:  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
  };

  function iconMarkup(name) {
    return ICONS[name] || ICONS.list;
  }

  /* Map a modal element to an icon name, based on id / class hints already
   * present in the markup (data-lp-icon, id prefix). Purely presentational. */
  function iconForModal(modalEl) {
    if (!modalEl) { return 'list'; }
    var explicit = modalEl.getAttribute('data-lp-icon');
    if (explicit && ICONS[explicit]) { return explicit; }
    var id = modalEl.id || '';
    if (id.indexOf('cohorts') !== -1) { return 'cohort'; }
    if (id.indexOf('users') !== -1) { return 'users'; }
    if (id.indexOf('courses') !== -1 || id.indexOf('prerequisites') !== -1) { return 'book'; }
    if (id.indexOf('video') !== -1) { return 'play'; }
    return 'route';
  }

  function injectModalIcon(modalEl) {
    if (!modalEl || modalEl.__lpIconDone) { return; }
    var slot = modalEl.querySelector('.lp-modal-title-icon');
    if (slot && !slot.innerHTML) {
      slot.innerHTML = iconMarkup(iconForModal(modalEl));
    }
    modalEl.__lpIconDone = true;
  }

  /* ---------------------------------------------------------------
   * 2. Ripple effect on primary actionable elements (visual only)
   * ------------------------------------------------------------- */
  function attachRipple(el) {
    if (!el || el.__lpRippleBound) { return; }
    el.__lpRippleBound = true;
    el.addEventListener('click', function (evt) {
      var rect = el.getBoundingClientRect();
      var ripple = document.createElement('span');
      var size = Math.max(rect.width, rect.height);
      ripple.className = 'lp-ripple';
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = (evt.clientX - rect.left - size / 2) + 'px';
      ripple.style.top = (evt.clientY - rect.top - size / 2) + 'px';
      var prevPosition = getComputedStyle(el).position;
      if (prevPosition === 'static') { el.style.position = 'relative'; }
      el.appendChild(ripple);
      window.setTimeout(function () {
        if (ripple.parentNode) { ripple.parentNode.removeChild(ripple); }
      }, 600);
    }, { passive: true });
  }

  function enhanceRippleTargets(root) {
    var selector = '.btn, .new-button, a.settings-learning-path, a.delete-learning-path, .learningpaths-actions a';
    var nodes = root.querySelectorAll ? root.querySelectorAll(selector) : [];
    for (var i = 0; i < nodes.length; i++) { attachRipple(nodes[i]); }
  }

  /* ---------------------------------------------------------------
   * 3. Staggered entrance animation for learning path rows
   * ------------------------------------------------------------- */
  function staggerRows(root) {
    var rows = root.querySelectorAll('.plms-learningpath');
    for (var i = 0; i < rows.length; i++) {
      rows[i].style.animationDelay = (i * 45) + 'ms';
    }
  }

  /* ---------------------------------------------------------------
   * 4. Modal polish: icon injection, focus handling, busy-state hook
   * ------------------------------------------------------------- */
  function onModalShown(modalEl) {
    injectModalIcon(modalEl);
    enhanceRippleTargets(modalEl);

    // Move initial focus to the first focusable field for accessibility,
    // without altering any existing Bootstrap/jQuery modal behaviour.
    window.setTimeout(function () {
      var focusable = modalEl.querySelector('input:not([type=hidden]):not([disabled]), select, textarea');
      if (focusable) { focusable.focus({ preventScroll: true }); }
    }, 220);
  }

  function watchModals() {
    var seen = new WeakSet ? new WeakSet() : null;

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        // New modal (e.g. bootbox confirm) added to the DOM.
        m.addedNodes && m.addedNodes.forEach(function (node) {
          if (node.nodeType !== 1) { return; }
          if (node.classList && node.classList.contains('modal')) {
            injectModalIcon(node);
          }
          var innerModals = node.querySelectorAll ? node.querySelectorAll('.modal') : [];
          for (var i = 0; i < innerModals.length; i++) { injectModalIcon(innerModals[i]); }
        });

        // Existing modal toggled visible via the "show" class
        // (this is how both jQuery's .modal('show') and native BS5
        // Modal.show() mark visibility - purely a CSS class read, no
        // interference with the toggle mechanism itself).
        if (m.type === 'attributes' && m.attributeName === 'class') {
          var target = m.target;
          if (target.classList && target.classList.contains('modal') && target.classList.contains('show')) {
            if (!seen || !seen.has(target)) {
              onModalShown(target);
              if (seen) { seen.add(target); }
            }
          }
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class']
    });
  }

  /* ---------------------------------------------------------------
   * 5. Lightweight toast helper (opt-in, not wired to any existing
   *    logic - available on window.lpToast for future use)
   * ------------------------------------------------------------- */
  function ensureToastStack() {
    var stack = document.querySelector('.lp-toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'lp-toast-stack';
      stack.setAttribute('aria-live', 'polite');
      document.body.appendChild(stack);
    }
    return stack;
  }

  window.lpToast = function (message, type) {
    var stack = ensureToastStack();
    var toast = document.createElement('div');
    toast.className = 'lp-toast lp-toast-' + (type || 'success');
    toast.innerHTML = '<span class="lp-icon">' + iconMarkup(type === 'danger' ? 'warn' : 'check') + '</span><span>' + message + '</span>';
    stack.appendChild(toast);
    window.setTimeout(function () {
      toast.style.transition = 'opacity .3s ease, transform .3s ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(24px)';
      window.setTimeout(function () { toast.remove(); }, 320);
    }, 3200);
  };

  /* ---------------------------------------------------------------
   * 6. Init
   * ------------------------------------------------------------- */
  function init() {
    injectStaticModalIcons();
    enhanceRippleTargets(document);
    staggerRows(document);
    watchModals();

    // Re-run lightweight enhancements whenever plugin AMD code swaps
    // list/table markup back in (e.g. after AJAX refresh of a tab).
    var contentObserver = new MutationObserver(function () {
      enhanceRippleTargets(document);
      staggerRows(document);
    });
    var mainRegion = document.querySelector('[role="main"]') || document.body;
    contentObserver.observe(mainRegion, { childList: true, subtree: true });
  }

  function injectStaticModalIcons() {
    var modals = document.querySelectorAll('.modal.lp-modal, .modal[data-lp-icon]');
    for (var i = 0; i < modals.length; i++) { injectModalIcon(modals[i]); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

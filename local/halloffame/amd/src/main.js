/**
 * Hall of Fame v3 – main AMD entry point.
 * Boots sub-modules. Handles card re-rendering after AJAX filter.
 * Updated for v3: comments + certificate popup support.
 * Updated for v4: real user pictures + edit/delete buttons after filter.
 *
 * @module local_halloffame/main
 */
define([
    'local_halloffame/filters',
    'local_halloffame/likes',
    'local_halloffame/comments',
    'local_halloffame/certpopup'
], function(Filters, Likes, Comments, CertPopup) {
    'use strict';

    var _tab = 'awards';

    // Brand gradients matching PHP manager.php
    var GRADIENTS = [
        'linear-gradient(135deg,#033252 0%,#065a96 100%)',
        'linear-gradient(135deg,#eb980d 0%,#f7c26b 100%)',
        'linear-gradient(135deg,#033252 0%,#eb980d 100%)',
        'linear-gradient(135deg,#065a96 0%,#0a8fd1 100%)',
        'linear-gradient(135deg,#c47a0a 0%,#eb980d 100%)',
        'linear-gradient(135deg,#033252 0%,#1a6b9e 60%,#eb980d 100%)'
    ];

    var MONTHS = ['','January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

    function init(opts) {
        _tab = (opts && opts.tab) ? opts.tab : 'awards';
        Filters.init({tab: _tab});
        Likes.init();
        Comments.init();
        CertPopup.init();

        document.addEventListener('halloffame:filtered', function(e) {
            renderItems(e.detail.tab, e.detail.items);
        });

        initDropzone();
    }

    // ── Rendering ──────────────────────────────────────────────────────────────

    function renderItems(tab, items) {
        var containerId = tab === 'awards' ? 'awardsGrid' : 'achievementsFeed';
        var el = document.getElementById(containerId);
        if (!el) return;

        el.innerHTML = '';
        if (!items.length) {
            el.innerHTML = '<div class="hof-empty">'
                + '<i class="fa fa-' + (tab === 'awards' ? 'trophy' : 'certificate') + '"></i>'
                + '<span class="hof-empty-text">No ' + (tab === 'awards' ? 'awards' : 'achievements')
                + ' found for the selected filters.</span></div>';
            return;
        }

        items.forEach(function(item) {
            el.insertAdjacentHTML('beforeend',
                tab === 'awards' ? buildAwardCard(item) : buildAchCard(item));
        });
    }

    /**
     * Build the avatar HTML for a card.
     * Uses the real Moodle profile picture when userpictureurl is available,
     * falls back to an initial-letter avatar when it is not.
     */
    function buildAvatar(userpictureurl, fullname, size, cssClass) {
        var initial = (fullname || '?').charAt(0).toUpperCase();
        if (userpictureurl) {
            return '<img src="' + esc(userpictureurl) + '"'
                + ' class="' + cssClass + '"'
                + ' style="width:' + size + 'px;height:' + size + 'px;'
                + 'border-radius:50%;object-fit:cover;"'
                + ' alt="' + esc(fullname) + '"/>';
        }
        // Fallback: coloured circle with initial letter.
        return '<div style="width:' + size + 'px;height:' + size + 'px;'
            + 'background:rgba(255,255,255,.25);border-radius:50%;'
            + 'display:flex;align-items:center;justify-content:center;'
            + 'font-size:' + Math.round(size * 0.4) + 'px;'
            + 'font-weight:800;color:#fff;">'
            + esc(initial) + '</div>';
    }

    /**
     * Build an award card as HTML string.
     * Uses real profile picture and renders edit/delete buttons for managers.
     */
    function buildAwardCard(a) {
        var grad         = GRADIENTS[a.id % GRADIENTS.length];
        var liked        = a.userliked ? ' hof-liked' : '';
        var pressed      = a.userliked ? 'true' : 'false';
        var month        = a.month ? (MONTHS[a.month] || '') : '';
        var commentCount = a.commentcount || 0;
        var avatar       = buildAvatar(a.userpictureurl, a.fullname, 88, 'hof-avatar');

        // Edit / Delete buttons — only rendered when canmanage is true and URLs are present.
        var manageBtns = '';
        if (a.canmanage && a.editurl) {
            manageBtns +=
                '<a href="' + esc(a.editurl) + '"'
                + ' class="hof-btn-edit-award hof-action-btn"'
                + ' aria-label="Edit award" title="Edit award">'
                + '<i class="fa fa-pencil" aria-hidden="true"></i></a>';
        }
        if (a.canmanage && a.deleteurl) {
            manageBtns +=
                '<a href="' + esc(a.deleteurl) + '"'
                + ' class="hof-btn-delete-award hof-action-btn"'
                + ' onclick="return confirm(\'Delete this award?\')"'
                + ' aria-label="Delete award" title="Delete award">'
                + '<i class="fa fa-trash-o" aria-hidden="true"></i></a>';
        }

        // View-certificate button (only when award has an image).
        var certBtn = '';
        if (a.image) {
            certBtn = '<button class="hof-btn-view-award hof-action-btn"'
                + ' data-image="' + esc(a.image) + '"'
                + ' data-title="' + esc(a.title) + '"'
                + ' data-user="' + esc(a.fullname) + '"'
                + ' title="View Certificate">'
                + '<i class="fa fa-eye"></i></button>';
        }

        return '<div class="hof-award-card" data-id="' + a.id + '" data-type="award">'
            + '<div class="hof-award-header" style="background:' + grad + '">'
            + (a.image ? '<img src="" class="hof-award-bg-img" alt=""/>' : '')
            + '<div class="hof-award-confetti" aria-hidden="true"><span>🎊</span><span>🎉</span><span>⭐</span></div>'
            + '<div class="hof-award-avatar-wrap"><div class="hof-avatar-ring">' + avatar + '</div></div>'
            + '<div class="hof-award-header-text">'
            + '  <h3 class="hof-award-title">' + esc(a.title) + '</h3>'
            + (a.department
                ? '<div class="hof-award-dept"><i class="fa fa-sitemap" aria-hidden="true"></i> ' + esc(a.department) + '</div>'
                : '')
            + '</div></div>'
            + '<div class="hof-award-body">'
            + (month ? '<div class="hof-award-period"><i class="fa fa-calendar-o" aria-hidden="true"></i> '
                     + esc(month) + ' ' + esc(String(a.year || '')) + '</div>' : '')
            + '<div class="hof-award-congrats"><i class="fa fa-star" aria-hidden="true"></i> Congratulations!!!</div>'
            + '<div class="hof-award-name-badge"><i class="fa fa-user" aria-hidden="true"></i> ' + esc(a.fullname) + '</div>'
            + (a.message ? '<p class="hof-award-message">"' + esc(a.message) + '"</p>' : '')
            + '</div>'
            + '<div class="hof-card-actions">'
            + '<button class="hof-like-btn' + liked + '" data-id="' + a.id + '" data-type="award"'
            + ' aria-pressed="' + pressed + '">'
            + '<i class="fa fa-thumbs-up" aria-hidden="true"></i>'
            + '<span class="hof-like-count">' + (a.likecount || 0) + '</span></button>'
            + (a.likecount ? '<button class="hof-view-likes hof-action-btn" data-id="' + a.id + '" data-type="award" title="View Likes"><i class="fa fa-users"></i></button>' : '')
            + '<button class="hof-comment-btn hof-action-btn" data-id="' + a.id + '" data-type="award" aria-label="Comment">'
            + '<i class="fa fa-comment-o" aria-hidden="true"></i>'
            + '<span class="hof-comment-count">' + commentCount + '</span></button>'
            + certBtn
            + manageBtns
            + '</div>'
            + '<div class="hof-comments-section" id="hof-comments-award-' + a.id + '" style="display:none;">'
            + '<div class="hof-comments-list" id="hof-comments-list-award-' + a.id + '">'
            + '<div class="hof-comments-loading"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>'
            + '</div>'
            + '<div class="hof-comment-form">'
            + '<textarea class="hof-comment-input hof-input hof-textarea" placeholder="Write a comment..." rows="2"'
            + ' data-id="' + a.id + '" data-type="award"></textarea>'
            + '<button class="hof-comment-submit hof-btn hof-btn-sm hof-btn-primary" data-id="' + a.id + '" data-type="award">'
            + '<i class="fa fa-paper-plane-o" aria-hidden="true"></i> Post</button>'
            + '</div></div>'
            + '</div>';
    }

    /**
     * Build an achievement card as HTML string.
     * Uses real profile picture and renders edit/delete buttons for managers.
     */
    function buildAchCard(a) {
        var liked        = a.userliked ? ' hof-liked' : '';
        var pressed      = a.userliked ? 'true' : 'false';
        var heartCls     = a.userliked ? 'fa-heart' : 'fa-heart-o';
        var commentCount = a.commentcount || 0;
        var avatar       = buildAvatar(a.userpictureurl, a.fullname, 56, 'hof-ach-avatar');
        var cert         = '';

        if (a.fileurl) {
            var ext = a.fileurl.split('.').pop().toLowerCase();
            if (['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1) {
                cert = '<div class="hof-ach-cert-wrap">'
                     + '<img src="' + esc(a.fileurl) + '" class="hof-ach-cert-img hof-cert-clickable"'
                     + ' data-fileurl="' + esc(a.fileurl) + '" data-title="' + esc(a.title) + '" data-fileisimage="1"'
                     + ' alt="Certificate for ' + esc(a.title) + '" title="View Certificate"/>'
                     + '</div>';
            } else {
                cert = '<div class="hof-ach-cert-wrap">'
                     + '<div class="hof-ach-cert-link hof-cert-clickable"'
                     + ' data-fileurl="' + esc(a.fileurl) + '" data-title="' + esc(a.title) + '" data-fileisimage="0"'
                     + ' title="View Certificate" style="cursor:pointer">'
                     + '<div class="hof-cert-pdf-icon"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></div>'
                     + '<span class="hof-cert-pdf-label">Certificate</span>'
                     + '<span class="hof-cert-pdf-sub">PDF</span>'
                     + '<i class="fa fa-search hof-cert-pdf-dl" aria-hidden="true"></i></div></div>';
            }
        }

        // Edit / Delete buttons — only rendered when canmanage is true and URLs are present.
        var manageBtns = '';
        if (a.canmanage && a.editurl) {
            manageBtns +=
                '<a href="' + esc(a.editurl) + '"'
                + ' class="hof-btn-edit-ach hof-action-btn"'
                + ' aria-label="Edit achievement" title="Edit achievement">'
                + '<i class="fa fa-pencil" aria-hidden="true"></i></a>';
        }
        if (a.canmanage && a.deleteurl) {
            manageBtns +=
                '<a href="' + esc(a.deleteurl) + '"'
                + ' class="hof-btn-delete-ach hof-action-btn"'
                + ' onclick="return confirm(\'Delete this achievement?\')"'
                + ' aria-label="Delete achievement" title="Delete achievement">'
                + '<i class="fa fa-trash-o" aria-hidden="true"></i></a>';
        }

        return '<div class="hof-ach-card" data-id="' + a.id + '" data-type="achievement">'
            + '<div class="hof-ach-header">'
            + '<div class="hof-ach-avatar-ring">' + avatar + '</div>'
            + '<div class="hof-ach-header-text">'
            + '<div class="hof-ach-earner"><strong class="hof-ach-name">' + esc(a.fullname) + '</strong>'
            + '<span class="hof-ach-verb"> earned a certificate in </span></div>'
            + (a.type ? '<span class="hof-ach-type-pill"><i class="fa fa-certificate" aria-hidden="true"></i> ' + esc(a.type) + '</span>' : '')
            + (a.department ? '<span class="hof-ach-dept-pill"><i class="fa fa-sitemap" aria-hidden="true"></i> ' + esc(a.department) + '</span>' : '')
            + '</div></div>'
            + '<div class="hof-ach-body">' + cert
            + '<div class="hof-ach-details">'
            + '<h4 class="hof-ach-title">' + esc(a.title) + '</h4>'
            + (a.issuer ? '<p class="hof-ach-meta"><i class="fa fa-university" aria-hidden="true"></i> ' + esc(a.issuer) + '</p>' : '')
            + (a.issuedatefmt ? '<p class="hof-ach-meta"><i class="fa fa-calendar-check-o" aria-hidden="true"></i> ' + esc(a.issuedatefmt) + '</p>' : '')
            + '</div></div>'
            + '<div class="hof-card-actions">'
            + '<button class="hof-like-btn' + liked + '" data-id="' + a.id + '" data-type="achievement"'
            + ' aria-pressed="' + pressed + '">'
            + '<i class="fa ' + heartCls + '" aria-hidden="true"></i>'
            + '<span class="hof-like-count">' + (a.likecount || 0) + '</span></button>'
            + '<button class="hof-comment-btn hof-action-btn" data-id="' + a.id + '" data-type="achievement" aria-label="Comment">'
            + '<i class="fa fa-comment-o" aria-hidden="true"></i>'
            + '<span class="hof-comment-count">' + commentCount + '</span></button>'
            + manageBtns
            + '</div>'
            + '<div class="hof-comments-section" id="hof-comments-achievement-' + a.id + '" style="display:none;">'
            + '<div class="hof-comments-list" id="hof-comments-list-achievement-' + a.id + '">'
            + '<div class="hof-comments-loading"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>'
            + '</div>'
            + '<div class="hof-comment-form">'
            + '<textarea class="hof-comment-input hof-input hof-textarea" placeholder="Write a comment..." rows="2"'
            + ' data-id="' + a.id + '" data-type="achievement"></textarea>'
            + '<button class="hof-comment-submit hof-btn hof-btn-sm hof-btn-primary" data-id="' + a.id + '" data-type="achievement">'
            + '<i class="fa fa-paper-plane-o" aria-hidden="true"></i> Post</button>'
            + '</div></div>'
            + '</div>';
    }

    // ── Dropzone ───────────────────────────────────────────────────────────────

    function initDropzone() {
        var dz = document.getElementById('hofDropzone');
        if (!dz) return;
        var input = dz.querySelector('input[type="file"]');
        var label = dz.querySelector('.hof-dz-label');

        dz.addEventListener('dragover',  function(e) { e.preventDefault(); dz.classList.add('hof-dz-over'); });
        dz.addEventListener('dragleave', function()  { dz.classList.remove('hof-dz-over'); });
        dz.addEventListener('drop', function(e) {
            e.preventDefault(); dz.classList.remove('hof-dz-over');
            if (e.dataTransfer.files.length && input) {
                input.files = e.dataTransfer.files;
                if (label) label.textContent = e.dataTransfer.files[0].name;
            }
        });
        if (input) {
            input.addEventListener('change', function() {
                if (this.files.length && label) label.textContent = this.files[0].name;
            });
        }
    }

    // ── Utility ────────────────────────────────────────────────────────────────

    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    return {init: init};
});

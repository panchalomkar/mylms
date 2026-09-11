/**
 * Hall of Fame v3 – comments AMD module.
 * Handles loading, posting, deleting, and editing comments via AJAX.
 * Works for both user-facing cards (awards/achievements) and admin review (submissions).
 *
 * @module local_halloffame/comments
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    // Tracks which comment sections have already been loaded.
    var _loaded = {};

    function init() {
        // Delegated listener for all comment interactions.
        document.addEventListener('click', function(e) {

            // Toggle comment section.
            var btn = e.target.closest('.hof-comment-btn');
            if (btn) { toggleComments(btn); return; }

            // Submit a new comment.
            var submitBtn = e.target.closest('.hof-comment-submit');
            if (submitBtn) { submitComment(submitBtn); return; }

            // Delete a comment.
            var deleteBtn = e.target.closest('.hof-comment-delete-btn');
            if (deleteBtn) { deleteComment(deleteBtn); return; }

            // Open inline edit form.
            var editBtn = e.target.closest('.hof-comment-edit-btn');
            if (editBtn) { openEditForm(editBtn); return; }

            // Save an edited comment.
            var saveBtn = e.target.closest('.hof-comment-edit-save');
            if (saveBtn) { saveEditedComment(saveBtn); return; }

            // Cancel editing.
            var cancelBtn = e.target.closest('.hof-comment-edit-cancel');
            if (cancelBtn) { cancelEdit(cancelBtn); return; }
        });

        // Allow Ctrl+Enter to submit in new-comment textareas.
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                var ta = e.target.closest('.hof-comment-input');
                if (ta) {
                    var form = ta.closest('.hof-comment-form');
                    var sub  = form ? form.querySelector('.hof-comment-submit') : null;
                    if (sub) submitComment(sub);
                }
            }
        });

        initAdminCommentForms();
    }

    function initAdminCommentForms() {
        // Delegated listeners above handle admin review too — nothing extra needed.
    }

    // ── Toggle ────────────────────────────────────────────────────────────────

    function toggleComments(btn) {
        var itemid    = btn.dataset.id;
        var itemtype  = btn.dataset.type;
        var key       = itemtype + '-' + itemid;
        var sectionId = 'hof-comments-' + itemtype + '-' + itemid;
        var section   = document.getElementById(sectionId);
        if (!section) return;

        var isHidden = section.style.display === 'none' || section.style.display === '';
        section.style.display = isHidden ? 'block' : 'none';

        if (isHidden && !_loaded[key]) {
            _loaded[key] = true;
            loadComments(itemid, itemtype);
        }
    }

    // ── Load ──────────────────────────────────────────────────────────────────

    function loadComments(itemid, itemtype) {
        var listId = 'hof-comments-list-' + itemtype + '-' + itemid;
        var list   = document.getElementById(listId);
        if (!list) return;

        list.innerHTML = '<div class="hof-comments-loading">'
            + '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>';

        Ajax.call([{
            methodname: 'local_halloffame_get_comments',
            args: {itemid: parseInt(itemid, 10), itemtype: itemtype}
        }])[0].then(function(comments) {
            renderComments(list, comments);
        }).catch(function(err) {
            list.innerHTML = '<p class="hof-comments-error">Could not load comments.</p>';
            Notification.exception(err);
        });
    }

    // ── Render ────────────────────────────────────────────────────────────────

    function renderComments(container, comments) {
        if (!comments || !comments.length) {
            container.innerHTML = '<p class="hof-comments-empty">No comments yet. Be the first!</p>';
            return;
        }
        container.innerHTML = '';
        comments.forEach(function(c) {
            container.insertAdjacentHTML('beforeend', buildCommentHtml(c));
        });
    }

    /**
     * Build HTML for a single comment item.
     * Shows pencil (edit) button when canbeedited is true — only the comment author sees this.
     * Shows × (delete) button when canbedeleted is true — author + admins.
     */
    function buildCommentHtml(c) {
        var editBtn = c.canbeedited
            ? '<button class="hof-comment-edit-btn" data-commentid="' + c.id + '"'
              + ' data-rawmessage="' + esc(c.rawmessage || c.message) + '"'
              + ' aria-label="Edit comment" title="Edit comment">'
              + '<i class="fa fa-pencil" aria-hidden="true"></i></button>'
            : '';

        var deleteBtn = c.canbedeleted
            ? '<button class="hof-comment-delete-btn" data-commentid="' + c.id + '"'
              + ' aria-label="Delete comment" title="Delete comment">'
              + '<i class="fa fa-times" aria-hidden="true"></i></button>'
            : '';

        return '<div class="hof-comment-item" data-commentid="' + c.id + '">'
            + '<img src="' + esc(c.userpictureurl) + '" class="hof-comment-avatar" alt="' + esc(c.fullname) + '"/>'
            + '<div class="hof-comment-bubble">'
            + '<div class="hof-comment-meta">'
            + '<strong class="hof-comment-author">' + esc(c.fullname) + '</strong>'
            + '<span class="hof-comment-time">' + esc(c.timecreatedstr) + '</span>'
            + editBtn
            + deleteBtn
            + '</div>'
            + '<p class="hof-comment-message">' + esc(c.message) + '</p>'
            + '</div></div>';
    }

    // ── Submit new comment ────────────────────────────────────────────────────

    function submitComment(submitBtn) {
        var form     = submitBtn.closest('.hof-comment-form');
        var textarea = form ? form.querySelector('.hof-comment-input') : null;
        if (!textarea) return;

        var itemid   = submitBtn.dataset.id;
        var itemtype = submitBtn.dataset.type;
        var message  = (textarea.value || '').trim();

        if (!message) {
            textarea.focus();
            textarea.classList.add('hof-input-error');
            setTimeout(function() { textarea.classList.remove('hof-input-error'); }, 1500);
            return;
        }

        submitBtn.disabled  = true;
        textarea.disabled   = true;
        var origText        = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';

        Ajax.call([{
            methodname: 'local_halloffame_add_comment',
            args: {itemid: parseInt(itemid, 10), itemtype: itemtype, message: message}
        }])[0].then(function(comment) {
            textarea.value      = '';
            submitBtn.disabled  = false;
            textarea.disabled   = false;
            submitBtn.innerHTML = origText;

            var sectionId = 'hof-comments-' + itemtype + '-' + itemid;
            var section   = document.getElementById(sectionId);
            if (section) section.style.display = 'block';

            var listId = 'hof-comments-list-' + itemtype + '-' + itemid;
            var list   = document.getElementById(listId);
            if (list) {
                var emptyMsg = list.querySelector('.hof-comments-empty');
                if (emptyMsg) emptyMsg.remove();
                list.insertAdjacentHTML('beforeend', buildCommentHtml(comment));
                list.lastElementChild.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            }

            // Update comment count badge.
            var card = document.querySelector(
                '.hof-award-card[data-id="' + itemid + '"],'
                + '.hof-ach-card[data-id="' + itemid + '"]');
            if (card) {
                var countEl = card.querySelector('.hof-comment-btn .hof-comment-count');
                if (countEl) countEl.textContent = parseInt(countEl.textContent || '0', 10) + 1;
            }

            _loaded[itemtype + '-' + itemid] = true;
        }).catch(function(err) {
            submitBtn.disabled  = false;
            textarea.disabled   = false;
            submitBtn.innerHTML = origText;
            Notification.exception(err);
        });
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    function deleteComment(deleteBtn) {
        var commentid = parseInt(deleteBtn.dataset.commentid, 10);
        if (!commentid) return;
        if (!confirm('Delete this comment?')) return;

        deleteBtn.disabled = true;

        Ajax.call([{
            methodname: 'local_halloffame_delete_comment',
            args: {commentid: commentid}
        }])[0].then(function(res) {
            if (res.success) {
                var item = deleteBtn.closest('.hof-comment-item');
                if (item) {
                    item.style.opacity    = '0';
                    item.style.transition = 'opacity .3s';
                    setTimeout(function() {
                        var card = item.closest('.hof-award-card, .hof-ach-card');
                        if (card) {
                            var countEl = card.querySelector('.hof-comment-btn .hof-comment-count');
                            if (countEl) {
                                var n = parseInt(countEl.textContent || '1', 10);
                                countEl.textContent = Math.max(0, n - 1);
                            }
                        }
                        item.remove();
                    }, 300);
                }
            }
        }).catch(function(err) {
            deleteBtn.disabled = false;
            Notification.exception(err);
        });
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    /**
     * Replace the comment message paragraph with an inline textarea + Save/Cancel.
     * Only the comment author sees the edit button (canbeedited from server).
     */
    function openEditForm(editBtn) {
        var commentid  = editBtn.dataset.commentid;
        var rawMessage = editBtn.dataset.rawmessage || '';
        var item       = editBtn.closest('.hof-comment-item');
        if (!item) return;

        // Prevent opening multiple edit forms on same comment.
        if (item.querySelector('.hof-comment-edit-form')) return;

        // Hide the read-only message while editing.
        var msgEl = item.querySelector('.hof-comment-message');
        if (msgEl) msgEl.style.display = 'none';

        // Disable edit/delete buttons while the edit form is open.
        item.querySelectorAll('.hof-comment-edit-btn, .hof-comment-delete-btn')
            .forEach(function(b) { b.disabled = true; });

        var editForm = document.createElement('div');
        editForm.className = 'hof-comment-edit-form';
        editForm.innerHTML =
            '<textarea class="hof-comment-edit-input hof-input hof-textarea" rows="2"'
            + ' data-commentid="' + esc(commentid) + '">'
            + escForTextarea(rawMessage)
            + '</textarea>'
            + '<div class="hof-comment-edit-actions">'
            + '<button class="hof-comment-edit-save hof-btn hof-btn-sm hof-btn-primary"'
            + ' data-commentid="' + esc(commentid) + '">'
            + '<i class="fa fa-check" aria-hidden="true"></i> Save'
            + '</button>'
            + '<button class="hof-comment-edit-cancel hof-btn hof-btn-sm hof-btn-ghost"'
            + ' data-commentid="' + esc(commentid) + '">'
            + 'Cancel'
            + '</button>'
            + '</div>';

        var bubble = item.querySelector('.hof-comment-bubble');
        if (bubble) bubble.appendChild(editForm);

        var ta = editForm.querySelector('.hof-comment-edit-input');
        if (ta) { ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length); }
    }

    /**
     * Save the edited comment via AJAX and update the displayed message in place.
     */
    function saveEditedComment(saveBtn) {
        var commentid = parseInt(saveBtn.dataset.commentid, 10);
        var item      = saveBtn.closest('.hof-comment-item');
        if (!item) return;

        var editForm   = item.querySelector('.hof-comment-edit-form');
        var ta         = editForm ? editForm.querySelector('.hof-comment-edit-input') : null;
        if (!ta) return;

        var newMessage = (ta.value || '').trim();
        if (!newMessage) {
            ta.focus();
            ta.classList.add('hof-input-error');
            setTimeout(function() { ta.classList.remove('hof-input-error'); }, 1500);
            return;
        }

        saveBtn.disabled = true;
        var cancelBtn    = editForm.querySelector('.hof-comment-edit-cancel');
        if (cancelBtn) cancelBtn.disabled = true;
        var origHtml     = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';

        Ajax.call([{
            methodname: 'local_halloffame_edit_comment',
            args: {commentid: commentid, message: newMessage}
        }])[0].then(function(res) {
            if (res.success) {
                // Update displayed message with server-formatted text.
                var msgEl = item.querySelector('.hof-comment-message');
                if (msgEl) {
                    msgEl.textContent   = stripHtml(res.message);
                    msgEl.style.display = '';
                }

                // Keep the edit button's rawmessage in sync for future edits.
                var btn = item.querySelector('.hof-comment-edit-btn');
                if (btn) btn.dataset.rawmessage = newMessage;

                closeEditForm(item);
            }
        }).catch(function(err) {
            saveBtn.disabled  = false;
            saveBtn.innerHTML = origHtml;
            if (cancelBtn) cancelBtn.disabled = false;
            Notification.exception(err);
        });
    }

    /** Cancel editing — restore message display and remove inline form. */
    function cancelEdit(cancelBtn) {
        var item = cancelBtn.closest('.hof-comment-item');
        if (item) closeEditForm(item);
    }

    /** Remove the edit form and restore the read-only message. */
    function closeEditForm(item) {
        var editForm = item.querySelector('.hof-comment-edit-form');
        if (editForm) editForm.remove();

        var msgEl = item.querySelector('.hof-comment-message');
        if (msgEl) msgEl.style.display = '';

        item.querySelectorAll('.hof-comment-edit-btn, .hof-comment-delete-btn')
            .forEach(function(b) { b.disabled = false; });
    }

    // ── Utility ───────────────────────────────────────────────────────────────

    function esc(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    /** For pre-filling a <textarea> value — escape only the dangerous HTML chars. */
    function escForTextarea(s) {
        if (s === null || s === undefined) return '';
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /** Strip HTML tags from a formatted string for safe textContent assignment. */
    function stripHtml(s) {
        var tmp = document.createElement('div');
        tmp.innerHTML = s || '';
        return tmp.textContent || tmp.innerText || '';
    }

    return {init: init};
});

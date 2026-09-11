/**
 * People Plugin - Plain JavaScript (No jQuery/AMD dependency)
 * Moodle 5.0.2 + REMUI Theme 5 compatible
 * Brand colors: #003152 (primary), #ec9707 (accent)
 *
 * @package    local_people
 * @copyright  2024 People Plugin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function () {
    'use strict';

    // ============================
    // UTILITY FUNCTIONS
    // ============================

    function qs(selector, ctx) {
        return (ctx || document).querySelector(selector);
    }

    function qsa(selector, ctx) {
        return Array.from((ctx || document).querySelectorAll(selector));
    }

    function on(el, event, fn, delegateSelector) {
        if (!el) return;
        if (delegateSelector) {
            el.addEventListener(event, function (e) {
                var target = e.target.closest(delegateSelector);
                if (target && el.contains(target)) fn.call(target, e);
            });
        } else {
            el.addEventListener(event, fn);
        }
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var d = new Date();
            d.setTime(d.getTime() + (days * 86400000));
            expires = '; expires=' + d.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
    }

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : '';
    }

    function getString(key, component) {
        if (window.M && M.util && typeof M.util.get_string === 'function') {
            return M.util.get_string(key, component || 'local_people');
        }
        return key;
    }

    function showNotification(message, type) {
        var existing = qs('.people-notification');
        if (existing) existing.remove();

        var div = document.createElement('div');
        div.className = 'people-notification people-notification--' + (type || 'info');
        div.setAttribute('role', 'alert');
        div.innerHTML = '<span class="people-notification__icon"></span><span class="people-notification__msg">' + message + '</span><button class="people-notification__close" aria-label="Close">&times;</button>';
        document.body.appendChild(div);

        on(qs('.people-notification__close', div), 'click', function () { div.remove(); });
        setTimeout(function () { if (div.parentNode) div.remove(); }, 4000);
    }

    // ============================
    // MODAL SYSTEM
    // ============================

    var modal = {
        el: null,

        create: function (title, body, footer, onSave) {
            if (qs('#people-modal')) qs('#people-modal').remove();

            var m = document.createElement('div');
            m.id = 'people-modal';
            m.className = 'people-modal-overlay';
            m.setAttribute('role', 'dialog');
            m.setAttribute('aria-modal', 'true');
            m.setAttribute('aria-labelledby', 'people-modal-title');
            m.innerHTML =
                '<div class="people-modal-dialog">' +
                '  <div class="people-modal-header">' +
                '    <h5 id="people-modal-title" class="people-modal-title">' + title + '</h5>' +
                '    <button class="people-modal-close" aria-label="Close">&times;</button>' +
                '  </div>' +
                '  <div class="people-modal-body">' + body + '</div>' +
                '  <div class="people-modal-footer">' +
                '    <button class="people-btn people-btn--secondary people-modal-cancel">Cancel</button>' +
                '    <button class="people-btn people-btn--primary people-modal-save">' + getString('send', 'local_people') + '</button>' +
                '  </div>' +
                '</div>';

            document.body.appendChild(m);
            this.el = m;

            var closeBtn = qs('.people-modal-close', m);
            var cancelBtn = qs('.people-modal-cancel', m);
            var saveBtn = qs('.people-modal-save', m);

            on(closeBtn, 'click', function () { modal.close(); });
            on(cancelBtn, 'click', function () { modal.close(); });
            on(m, 'click', function (e) { if (e.target === m) modal.close(); });

            if (typeof onSave === 'function') {
                on(saveBtn, 'click', onSave);
            }

            // Trap focus.
            m.querySelectorAll('button, input, select, textarea, [tabindex]')[0].focus();
            document.addEventListener('keydown', this._escHandler);
            m.style.display = 'flex';
        },

        _escHandler: function (e) {
            if (e.key === 'Escape') modal.close();
        },

        close: function () {
            if (this.el) {
                this.el.remove();
                this.el = null;
                document.removeEventListener('keydown', this._escHandler);
            }
        }
    };

    // ============================
    // CONFIRM DIALOG
    // ============================

    function confirmDialog(message, onConfirm) {
        modal.create(
            getString('confirm', 'core'),
            '<p style="margin:0">' + message + '</p>',
            '',
            function () {
                modal.close();
                if (typeof onConfirm === 'function') onConfirm();
            }
        );
    }

    // ============================
    // BULK SELECTION
    // ============================

    var selectedBulk = [];

    try {
        var cookieVal = getCookie('selected_bulks');
        if (cookieVal) selectedBulk = JSON.parse(cookieVal);
    } catch (e) {
        selectedBulk = [];
    }

    function getCheckedUsers() {
        return qsa('input[name^=id]:checked:not([disabled])').map(function (el) { return el.value; });
    }

    function enableBulkButtons(status) {
        var actions = ['delete', 'download', 'message', 'cohortadd', 'suspend', 'unlock'];
        actions.forEach(function (action) {
            var btn = qs('a[data-action="' + action + '"]');
            if (btn) {
                btn.classList.toggle('active-bulk', !!status);
                btn.style.opacity = status ? '1' : '0.4';
                btn.style.cursor  = status ? 'pointer' : 'default';
            }
        });
        var enrolBtn = qs('#enrol_user');
        if (enrolBtn) {
            enrolBtn.style.opacity = status ? '1' : '0.4';
            enrolBtn.style.cursor  = status ? 'pointer' : 'default';
            if (status) {
                enrolBtn.setAttribute('data-active', '1');
            } else {
                enrolBtn.removeAttribute('data-active');
            }
        }
    }

    // ============================
    // FILTER FUNCTIONS
    // ============================

    function serializeFilters() {
        var filters = [];
        qsa('form[id^=form-]').forEach(function (form) {
            if (form.id === 'form-bulk-actions' || form.id === 'form-clearall') return;
            var data = {};
            var inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(function (el) {
                if (!el.name || el.disabled) return;
                if ((el.type === 'radio' || el.type === 'checkbox') && !el.checked) return;
                if (el.type === 'select-multiple') {
                    var vals = Array.from(el.selectedOptions).map(function (o) { return o.value; });
                    data[el.name] = vals;
                } else {
                    data[el.name] = el.value;
                }
            });
            if (Object.keys(data).length > 0) filters.push(data);
        });
        return JSON.stringify(filters);
    }

    function submitFilters() {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.pathname;

        var fi = document.createElement('input');
        fi.type  = 'hidden';
        fi.name  = 'filters';
        fi.value = serializeFilters();
        form.appendChild(fi);

        document.body.appendChild(form);
        form.submit();
    }

    function clearAllFilters() {
        var form = qs('#form-clearall');
        if (form) {
            form.submit();
        } else {
            var cf = document.createElement('form');
            cf.method = 'POST';
            cf.action = window.location.pathname;
            var ci = document.createElement('input');
            ci.type  = 'hidden';
            ci.name  = 'clearall';
            ci.value = 'clearall';
            cf.appendChild(ci);
            document.body.appendChild(cf);
            cf.submit();
        }
    }

    function clearFilterPanel(panel) {
        qsa('input[type=text], textarea', panel).forEach(function (el) { el.value = ''; });
        qsa('select', panel).forEach(function (el) { el.selectedIndex = -1; });
        qsa('input[type=radio], input[type=checkbox]', panel).forEach(function (el) { el.checked = false; });
        qsa('.date input', panel).forEach(function (el) { el.value = ''; });
        qsa('.filter-active', panel).forEach(function (el) { el.classList.remove('filter-active'); });
    }

    // ============================
    // LEFT PANEL TOGGLE
    // ============================

    function initLeftPanelToggle() {
        var toggle = qs('.people-filter-toggle');
        var panel  = qs('.people-filter-panel');
        if (!toggle || !panel) return;

        // Restore state.
        var stored = localStorage.getItem('people_filter_open');
        if (stored === 'false') {
            panel.classList.add('people-filter-panel--collapsed');
            toggle.setAttribute('aria-expanded', 'false');
        } else {
            panel.classList.remove('people-filter-panel--collapsed');
            toggle.setAttribute('aria-expanded', 'true');
        }

        on(toggle, 'click', function () {
            var isOpen = !panel.classList.contains('people-filter-panel--collapsed');
            panel.classList.toggle('people-filter-panel--collapsed', isOpen);
            toggle.setAttribute('aria-expanded', String(!isOpen));
            localStorage.setItem('people_filter_open', String(!isOpen));
        });
    }

    // ============================
    // ACCORDION FILTERS
    // ============================

    function initAccordion() {
        qsa('.people-accordion-header').forEach(function (header) {
            on(header, 'click', function () {
                var body = qs('.people-accordion-body', header.parentElement);
                if (!body) return;
                var open = body.style.display !== 'none';
                body.style.display = open ? 'none' : 'block';
                header.setAttribute('aria-expanded', String(!open));
                header.classList.toggle('people-accordion-header--open', !open);
            });
        });

        // Auto-open panels that have active filters.
        qsa('.filter-true').forEach(function (el) {
            var body = el.closest('.people-accordion-body');
            if (body) {
                body.style.display = 'block';
                var header = qs('.people-accordion-header', body.parentElement);
                if (header) {
                    header.setAttribute('aria-expanded', 'true');
                    header.classList.add('people-accordion-header--open');
                }
            }
        });
    }

    // ============================
    // ENROL MODAL
    // ============================

function openEnrolModal() {
    var plug = window.PEOPLE_PLUGIN || {};
    var body = plug.enrollform || '<p>Loading...</p>';

    modal.create(
        plug.title || 'Enrol Users',
        body,
        '',
        function () {
            // 'this' callback IS the save button click handler — no nested on() needed
            var form = qs('#EnrolForm', modal.el);
            if (!form) return;

            var courses = qsa('#selectcourses option:checked', form);
            if (courses.length === 0) {
                showNotification(getString('please_select_course', 'local_people'), 'error');
                return;
            }

            // Inject selected user IDs
            qsa('input[name="id[]"]', form).forEach(function (el) { el.remove(); });
            getCheckedUsers().forEach(function (uid) {
                var inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'id[]';
                inp.value = uid;
                form.appendChild(inp);
            });

            // Move form to body BEFORE closing modal so DOM removal doesn't kill it
            document.body.appendChild(form);
            modal.close();
            form.submit();
        }
    );

    // Init date pickers after modal renders
    setTimeout(function () {
        if (!modal.el) return;

        initSimpleDatepicker(qs('#startdate', modal.el));
        initSimpleDatepicker(qs('#enddate',   modal.el));

        var clearStart = qs('#clearstartdate', modal.el);
        var clearEnd   = qs('#clearenddate',   modal.el);

        if (clearStart) {
            on(clearStart, 'click', function () {
                var el = qs('#startdate', modal.el);
                if (el) el.value = '';
            });
        }
        if (clearEnd) {
            on(clearEnd, 'click', function () {
                var el = qs('#enddate', modal.el);
                if (el) el.value = '';
            });
        }
    }, 100);
}
// ============================
// COHORT ADD MODAL
// ============================
function openMessageModal() {
    var users = getCheckedUsers();
    if (users.length === 0) {
        showNotification(getString('select_users', 'local_people') || 'Please select at least one user.', 'warning');
        return;
    }

    var sesskey = (window.M && M.cfg) ? M.cfg.sesskey : '';
    var wwwroot  = (window.M && M.cfg) ? M.cfg.wwwroot : '';

    // Build message form HTML directly in JS since PHP returns empty form
    var formHtml =
        '<form id="frmMessageUsers" onsubmit="return false;">' +
        '  <input type="hidden" name="users" value="' + users.join(',') + '">' +
        '  <div class="form-group mb-3">' +
        '    <label for="people-message-body" class="form-label fw-semibold">' +
        '      ' + (getString('message', 'local_people') || 'Message') +
        '      <small class="text-muted d-block fw-normal">This message will be sent to ' + users.length + ' selected user(s).</small>' +
        '    </label>' +
        '    <textarea id="people-message-body" name="messagebody" rows="6"' +
        '      class="form-control" placeholder="Type your message here..." style="resize:vertical;"></textarea>' +
        '  </div>' +
        '</form>';

    modal.create(
        getString('bulk_send_message', 'local_people') || 'Send Message',
        formHtml,
        '',
        function () {
            // SAVE HANDLER
            var textarea = qs('#people-message-body', modal.el);
            if (!textarea || !textarea.value.trim()) {
                showNotification('Please enter a message.', 'error');
                return;
            }

            var messageText = textarea.value.trim();

            // Build modaldata as save_message() expects:
            // { input_value: messageText, users: [uid1, uid2, ...] }
            var modaldata = JSON.stringify({
                input_value: messageText,
                users: users
            });

            modal.close();

            fetch(wwwroot + '/lib/ajax/service.php?sesskey=' + sesskey + '&info=local_people_get_people', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify([{
                    index: 0,
                    methodname: 'local_people_get_people',
                    args: {
                        form:      'na',
                        message:   'na',
                        q:         false,
                        title:     'na',
                        user:      JSON.stringify(users),
                        getter:    false,       // false = action branch
                        action:    'message',   // calls save_message()
                        modaldata: modaldata
                    }
                }])
            })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var data = json && json[0];
                if (!data || data.error) {
                    var errMsg = (data && data.exception && data.exception.message) || 'Failed to send message.';
                    showNotification(errMsg, 'error');
                    return;
                }
                var result = data.data;
                if (result && result.error === false) {
                    showNotification(result.message || 'Message sent successfully.', 'success');
                } else {
                    // save_message() returns empty array on success — treat that as success too
                    showNotification(getString('success_message_users', 'local_people') || 'Message sent successfully.', 'success');
                }
            })
            .catch(function (err) {
                showNotification('Error sending message: ' + err.message, 'error');
            });
        }
    );
}
function openCohortModal() {
    var users = getCheckedUsers();
    if (users.length === 0) {
        showNotification(getString('select_users', 'local_people') || 'Please select at least one user.', 'warning');
        return;
    }

    var sesskey = (window.M && M.cfg) ? M.cfg.sesskey : '';
    var wwwroot  = (window.M && M.cfg) ? M.cfg.wwwroot : '';

    // Show modal with loader immediately
    modal.create(
        getString('bulk_add_cohort', 'local_people') || 'Add to Cohort',
        '<div style="text-align:center;padding:2rem;"><span class="fa fa-spinner fa-spin fa-2x"></span><p style="margin-top:1rem;">Loading cohorts...</p></div>',
        '',
        function () {
            // SAVE HANDLER — called when user clicks Save button
            var cohortSel = qs('select[name="cohortlist"]', modal.el);
            if (!cohortSel || !cohortSel.value || cohortSel.value === '-1') {
                showNotification(getString('please_select_cohort', 'local_people') || 'Please select a cohort.', 'error');
                return;
            }

            var cohortId = cohortSel.value;

            // Build modaldata exactly as save_cohortadd() expects:
            // { input_value: cohortId, users: [uid1, uid2, ...] }
            var modaldata = JSON.stringify({
                input_value: cohortId,
                users: users
            });

            modal.close();

            // Call save_cohortadd via AJAX
            fetch(wwwroot + '/lib/ajax/service.php?sesskey=' + sesskey + '&info=local_people_get_people', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify([{
                    index: 0,
                    methodname: 'local_people_get_people',
                    args: {
                        form:      'na',
                        message:   'na',
                        q:         false,
                        title:     'na',
                        user:      JSON.stringify(users),
                        getter:    false,          // false = goes to action branch
                        action:    'cohortadd',    // calls save_cohortadd()
                        modaldata: modaldata
                    }
                }])
            })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var data = json && json[0];
                if (!data || data.error) {
                    var errMsg = (data && data.exception && data.exception.message) || 'Failed to add to cohort.';
                    showNotification(errMsg, 'error');
                    return;
                }
                // data.data contains { error: bool, message: string } from save_cohortadd
                var result = data.data;
                if (result && result.error === false) {
                    showNotification(result.message || 'Users added to cohort successfully.', 'success');
                } else {
                    showNotification((result && result.message) || 'Error adding to cohort.', 'error');
                }
            })
            .catch(function (err) {
                showNotification('Error: ' + err.message, 'error');
            });
        }
    );

    // Fetch cohort form HTML via get_cohortadd_form()
    fetch(wwwroot + '/lib/ajax/service.php?sesskey=' + sesskey + '&info=local_people_get_people', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify([{
            index: 0,
            methodname: 'local_people_get_people',
            args: {
                form:      'cohortadd',     // calls get_cohortadd_form()
                message:   'na',
                q:         false,
                title:     'na',
                user:      JSON.stringify(users),
                getter:    true,            // true = getter branch, runs get_cohortadd_form()
                action:    'na',
                modaldata: '{}'
            }
        }])
    })
    .then(function (r) { return r.json(); })
    .then(function (json) {
        if (!modal.el) return;

        var data = json && json[0];

        if (!data || data.error) {
            var errMsg = (data && data.exception && data.exception.message) || 'Failed to load cohorts.';
            qs('.people-modal-body', modal.el).innerHTML =
                '<div class="alert alert-danger">' + errMsg + '</div>';
            return;
        }

        // PHP returns { form: '<html>', message: '', q: true, title: '...' }
        var result = data.data;

        if (!result) {
            qs('.people-modal-body', modal.el).innerHTML =
                '<div class="alert alert-warning">No cohorts found.</div>';
            return;
        }

        if (result.q === false) {
            // PHP found no valid users or no cohorts
            qs('.people-modal-body', modal.el).innerHTML =
                '<div class="alert alert-warning">' + (result.message || 'No cohorts available.') + '</div>';
            return;
        }

        if (!result.form) {
            qs('.people-modal-body', modal.el).innerHTML =
                '<div class="alert alert-warning">No cohort form returned.</div>';
            return;
        }

        qs('.people-modal-body', modal.el).innerHTML = result.form;

        // Update modal title from PHP response
        var titleEl = qs('#people-modal-title', modal.el);
        if (titleEl && result.title) titleEl.textContent = result.title;
    })
    .catch(function (err) {
        if (!modal.el) return;
        qs('.people-modal-body', modal.el).innerHTML =
            '<div class="alert alert-danger">Error loading cohorts: ' + err.message + '</div>';
    });
}

// Build cohort select form if backend returns array
function buildCohortForm(users, cohorts) {
    var opts = cohorts.map(function (c) {
        return '<option value="' + c.id + '">' + c.name + '</option>';
    }).join('');

    var userInputs = users.map(function (uid) {
        return '<input type="hidden" name="userid[]" value="' + uid + '">';
    }).join('');

    return '<form method="POST" action="#" onsubmit="return false;" id="frmcohortsadd">' +
        userInputs +
        '<input type="hidden" name="sesskey" value="' + ((window.M && M.cfg) ? M.cfg.sesskey : '') + '">' +
        '<div class="form-group">' +
        '<label for="cohortid" class="form-label fw-semibold">Select Cohort</label>' +
        '<select name="cohortid" id="cohortid" class="form-control">' +
        '<option value="">-- Select a cohort --</option>' +
        opts +
        '</select>' +
        '</div>' +
        '</form>';
}
    // ============================
    // SIMPLE DATE PICKER (native fallback)
    // ============================

// In initSimpleDatepicker — force type to 'text' so no AMD picker gets triggered
function initSimpleDatepicker(input) {
    if (!input) return;

    // Explicitly set type=text to prevent any theme datepicker hooks
    input.setAttribute('type', 'text');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('data-ignore-datetimepicker', '1'); // block REMUI auto-init

    input.placeholder = 'MM/DD/YYYY';

    on(input, 'blur', function () {
        var val = input.value.trim();
        if (val && !/^\d{2}\/\d{2}\/\d{4}$/.test(val)) {
            input.classList.add('people-input--error');
            showNotification('Invalid date format. Use MM/DD/YYYY', 'error');
        } else {
            input.classList.remove('people-input--error');
        }
    });
}

    // ============================
    // BULK ACTIONS
    // ============================

     // ============================
    // BULK ACTION: modal confirm + AJAX (no page redirect for delete/suspend)
    // ============================

    // ── Helper: get full names from checked rows ─────────────────────────────────
    function getCheckedUserNames() {
        var names = [];
        qsa('#form-bulk-actions input[type=checkbox]:checked').forEach(function (cb) {
            if (!cb.name || !cb.name.startsWith('id')) return;
            var row = cb.closest('tr');
            if (!row) return;
            // Try the name cell — look for .people-user-name or first <a> or first non-checkbox td text
            var nameEl = row.querySelector('.people-user-name a') ||
                         row.querySelector('.people-user-name') ||
                         row.querySelector('td:nth-child(2)');
            if (nameEl) names.push(nameEl.textContent.trim());
        });
        return names;
    }

    // ── Step-2 result modal (no Moodle notification, no toast) ──────────────────
    function showResultModal(title, isSuccess, lines, onClose) {
        // Icon sits inline with the title: [icon] Title text
        var iconHtml = isSuccess
            ? '<span class="people-result-icon people-result-icon--success">&#10003;</span>'
            : '<span class="people-result-icon people-result-icon--error">&#10007;</span>';

        var titleWithIcon = iconHtml + ' ' + title;

        var listHtml = lines && lines.length
            ? '<ul class="people-result-list">' +
              lines.map(function(l){ return '<li>' + l + '</li>'; }).join('') +
              '</ul>'
            : '';

        modal.create(
            titleWithIcon,
            listHtml,
            '',
            function () {
                modal.close();
                if (typeof onClose === 'function') onClose();
            }
        );

        // Hide cancel button — result modal only needs OK
        var cancelBtn = modal.el && modal.el.querySelector('.people-modal-cancel');
        if (cancelBtn) cancelBtn.style.display = 'none';

        // Rename Save → OK
        var saveBtn = modal.el && modal.el.querySelector('.people-modal-save');
        if (saveBtn) saveBtn.textContent = 'OK';
    }

    // ── Step-1 confirm modal with user name list ──────────────────────────────
    function showBulkConfirmModal(title, message, userNames, onConfirm) {
        var namesHtml = userNames.length
            ? '<ul class="people-result-list">' +
              userNames.map(function(n){ return '<li>' + n + '</li>'; }).join('') +
              '</ul>'
            : '';

        var body = '<p class="people-confirm-question">' + message + '</p>' + namesHtml;

        modal.create(
            title,
            body,
            '',
            function () {          // OK / confirm button
                modal.close();
                if (typeof onConfirm === 'function') onConfirm();
            }
        );

        // Rename Save → OK
        var saveBtn = modal.el && modal.el.querySelector('.people-modal-save');
        if (saveBtn) saveBtn.textContent = 'OK';
    }

    // ── Main bulk action entry point ──────────────────────────────────────────
    function submitBulkAction(actionName, extraParams) {
        var users = getCheckedUsers();
        if (users.length === 0) {
            // Use result modal instead of notification for consistency
            showResultModal('Notice', false, ['Please select at least one user.'], null);
            return;
        }

        var userNames = getCheckedUserNames();

        // DELETE — Step 1: confirm modal with names
        if (actionName === 'delete') {
            showBulkConfirmModal(
                'Confirm Delete',
                'Are you sure you want to delete the following user' + (users.length > 1 ? 's' : '') + '?',
                userNames,
                function () {
                    performBulkAjax(actionName, users, extraParams, function (res) {
                        // Step 2: result modal
                        var lines = (res.deleted && res.deleted.length) ? res.deleted : [];
                        var skipped = (res.skipped && res.skipped.length) ? res.skipped : [];
                        var resultLines = [];
                        if (lines.length)   resultLines = resultLines.concat(lines);
                        if (skipped.length) resultLines = resultLines.concat(skipped.map(function(s){ return s + ' (skipped)'; }));
                        showResultModal(
                            lines.length ? 'Users Deleted Successfully' : 'Delete Notice',
                            lines.length > 0,
                            resultLines,
                            function () { window.location.reload(); }
                        );
                    });
                }
            );
            return;
        }

        // SUSPEND / UNSUSPEND — Step 1: confirm modal with names
        if (actionName === 'suspend_unsuspend') {
            var isSuspend = extraParams && extraParams.suspend;
            var actionLabel = isSuspend ? 'suspend' : 'unsuspend';
            var ActionLabel = isSuspend ? 'Suspend'  : 'Unsuspend';
            showBulkConfirmModal(
                'Confirm ' + ActionLabel,
                'Are you sure you want to ' + actionLabel + ' the following user' + (users.length > 1 ? 's' : '') + '?',
                userNames,
                function () {
                    performBulkAjax(actionName, users, extraParams, function (res) {
                        // Step 2: result modal
                        var affected = (res.affected && res.affected.length) ? res.affected : [];
                        var skipped  = (res.skipped  && res.skipped.length)  ? res.skipped  : [];
                        var resultLines = [];
                        if (affected.length) resultLines = resultLines.concat(affected);
                        if (skipped.length)  resultLines = resultLines.concat(skipped.map(function(s){ return s + ' (skipped)'; }));
                        var successTitle = isSuspend ? 'Users Suspended Successfully' : 'Users Unsuspended Successfully';
                        showResultModal(
                            affected.length ? successTitle : ActionLabel + ' Notice',
                            affected.length > 0,
                            resultLines,
                            function () { window.location.reload(); }
                        );
                    });
                }
            );
            return;
        }

        // All other actions — normal form submit (no change)
        performBulkSubmit(actionName, users, extraParams);
    }

    // ── AJAX executor (called after confirm) ─────────────────────────────────
    function performBulkAjax(actionName, users, extraParams, onDone) {
        var url = actionName === 'delete'
            ? M.cfg.wwwroot + '/local/people/actions/user_bulk_delete.php'
            : M.cfg.wwwroot + '/local/people/actions/user_bulk_suspend_unsuspend.php';

        var body = 'bulk_action=' + encodeURIComponent(JSON.stringify({ name: actionName, data: users }))
                 + '&sesskey=' + encodeURIComponent(M.cfg.sesskey)
                 + '&ajax=1';

        if (extraParams) {
            Object.keys(extraParams).forEach(function (k) {
                body += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(extraParams[k]);
            });
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            var res = {};
            try { res = JSON.parse(xhr.responseText); } catch(e) { res = { success: false, message: 'Server error', deleted: [], affected: [] }; }
            if (typeof onDone === 'function') onDone(res);
        };

        xhr.onerror = function () {
            showResultModal('Error', false, ['Network error. Please try again.'], null);
        };

        xhr.send(body);
    }

    // Form submit for non-AJAX actions (download, etc.)
    function performBulkSubmit(actionName, users, extraParams) {
        var form = document.createElement('form');
        form.method = 'POST';

        function setField(name, value) {
            var inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = name;
            inp.value = value;
            form.appendChild(inp);
        }

        setField('bulk_action', JSON.stringify({ name: actionName, data: users }));
        setField('sesskey', M.cfg.sesskey);

        if (extraParams) {
            Object.keys(extraParams).forEach(function (k) { setField(k, extraParams[k]); });
        }

        if (actionName === 'download') {
            form.action = M.cfg.wwwroot + '/local/people/actions/user_bulk_download.php';
        } else if (actionName === 'suspend_unsuspend') {
            form.action = M.cfg.wwwroot + '/local/people/actions/user_bulk_suspend_unsuspend.php';
        } else if (actionName === 'delete') {
            form.action = M.cfg.wwwroot + '/local/people/actions/user_bulk_delete.php';
        }

        document.body.appendChild(form);
        form.submit();
    }


    // ============================
    // ADD USER MODAL (inline form)
    // ============================

    function openAddUserModal() {
        var form = qs('#submit_form');
        if (!form) return;
        modal.create(
            getString('new_user', 'local_people') || 'New User',
            form.outerHTML,
            '',
            function () {
                var mForm = qs('#submit_form', modal.el);
                if (!mForm) return;
                var firstname = qs('#firstname', mForm).value.trim();
                var lastname  = qs('#lastname',  mForm).value.trim();
                var email     = qs('#email',     mForm).value.trim();
                var username  = qs('#username',  mForm).value.trim();
                var pwd       = qs('#pwd',       mForm).value.trim();

                if (!firstname || !lastname || !email || !username || !pwd) {
                    showNotification('All fields are required.', 'error');
                    return;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showNotification('Invalid email address.', 'error');
                    return;
                }

                var data = new FormData(mForm);
                fetch('userinsert.php', { method: 'POST', body: data })
                    .then(function (r) { return r.text(); })
                    .then(function (text) {
                        modal.close();
                        showNotification(text || 'User created successfully.', 'success');
                    })
                    .catch(function () {
                        showNotification('Error creating user.', 'error');
                    });
            }
        );
    }

    // ============================
    // INVITE USER
    // ============================

    function inviteUser() {
        var emailInput = qs('.custom-search-form #txt');
        var email = emailInput ? emailInput.value : '';

        confirmDialog(
            (getString('invite_message', 'local_people') || 'Send invitation to') + ' ' + email + '?',
            function () {
                var data = new FormData();
                data.append('email', email);
                fetch('invite_people.php', { method: 'POST', body: data })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.status === true) {
                            showNotification(getString('invitation_sent', 'local_people') || 'Invitation sent!', 'success');
                        } else {
                            showNotification(getString('invite_error', 'local_people') || 'Error sending invitation.', 'error');
                        }
                    });
            }
        );
    }

    // ============================
    // SORTING
    // ============================

    function initSortableHeaders() {
        qsa('th[data-sort]').forEach(function (th) {
            th.style.cursor = 'pointer';
            on(th, 'click', function () {
                var sort  = th.dataset.sort;
                var order = th.dataset.order === 'ASC' ? 'DESC' : 'ASC';
                window.location.href = window.location.pathname + '?sort=' + sort + '&order=' + order;
            });
        });
    }

    // ============================
    // SEARCH BOX
    // ============================

    function initSearchBox() {
        var txt = qs('#txt');
        var btn = qs('#searchbutton');
        if (!txt || !btn) return;

        btn.style.display = txt.value.trim() ? '' : 'none';

        on(txt, 'input', function () {
            btn.style.display = txt.value.trim() ? '' : 'none';
        });

        on(btn, 'click', function (e) {
            e.preventDefault();
            txt.value = '';
            btn.style.display = 'none';
            var form = qs('#form-search') || txt.closest('form');
            if (form) form.submit();
        });

        var searchForm = txt.closest('form');
        if (searchForm) {
            on(searchForm, 'submit', function (e) {
                e.preventDefault();
                var params = new URLSearchParams(window.location.search);
                params.set('search', txt.value);
                window.location.href = window.location.pathname + '?' + params.toString();
            });
        }
    }

    // ============================
    // COMPANY/TENANT SELECT
    // ============================

    function initCompanySelect() {
        var sel = qs('#menucompany');
        if (!sel) return;
        on(sel, 'change', function () {
            var params = new URLSearchParams(window.location.search);
            params.set('company', sel.value);
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }

    // ============================
    // RECORDS PER PAGE
    // ============================

    function initRecordsPerPage() {
        var sel = qs('#id_userperpage');
        if (!sel) return;
        on(sel, 'change', function () {
            var form = sel.closest('form');
            if (form) form.submit();
        });
    }

    // ============================
    // MULTITENANT COLUMN TOGGLE
    // ============================

    function initMultitenantToggle() {
        var checkbox = qs('#show_tenat_company');
        var valInput = qs('#show_tenat_company_val');
        if (!checkbox || !valInput) return;

        var multiVal = valInput.value;
        if (multiVal == 1) {
            checkbox.checked = true;
            qsa('td:nth-child(7), th:nth-child(7)').forEach(function (el) { el.style.display = ''; });
        } else {
            checkbox.checked = false;
            qsa('td:nth-child(7), th:nth-child(7)').forEach(function (el) { el.style.display = 'none'; });
        }

        on(checkbox, 'change', function () {
            var show = checkbox.checked;
            qsa('td:nth-child(7), th:nth-child(7)').forEach(function (el) {
                el.style.display = show ? '' : 'none';
            });

            // AJAX update preference.
            var sesskey = (window.M && M.cfg) ? M.cfg.sesskey : '';
            var url = (window.M && M.cfg ? M.cfg.wwwroot : '') + '/webservice/rest/server.php';
            var body = new FormData();
            body.append('wsfunction', 'local_people_get_local_people');
            body.append('wstoken', '');
            body.append('moodlewsrestformat', 'json');
            body.append('tenant_val', show ? 1 : 0);
            body.append('activity', 'na');
            body.append('status', 'na');
            body.append('message', 'na');
            body.append('sesskey', sesskey);
            fetch(url, { method: 'POST', body: body }).catch(function () {});
        });
    }

    // ============================
    // RADIO BUTTON MUTUAL EXCLUSION
    // ============================

    function initRadioGroups() {
        // Suspended/unsuspended radios.
        on(document, 'change', function (e) {
            var el = e.target;
            if (el.name === 'unsuspended') {
                qsa('input[name="suspended"]').forEach(function (r) { r.checked = false; });
            } else if (el.name === 'suspended') {
                qsa('input[name="unsuspended"]').forEach(function (r) { r.checked = false; });
            } else if (el.name === 'confirmed') {
                qsa('input[name="unconfirmed"]').forEach(function (r) { r.checked = false; });
            } else if (el.name === 'unconfirmed') {
                qsa('input[name="confirmed"]').forEach(function (r) { r.checked = false; });
            }
        });
    }

    // ============================
    // ACTIVE FILTER TAGS
    // ============================

    function initFilterTags() {
        on(document, 'click', function (e) {
            var btn = e.target.closest('.reset[data-name]');
            if (!btn) return;
            var name = btn.dataset.name;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.pathname;
            var fi = document.createElement('input');
            fi.type  = 'hidden';
            fi.name  = 'removefilter';
            fi.value = name;
            form.appendChild(fi);
            document.body.appendChild(form);
            form.submit();
        });
    }

    // ============================
    // FILTER PANEL SUBMIT
    // ============================

    function initFilterForms() {
        on(document, 'click', function (e) {
            if (e.target.id === 'send' || e.target.closest('#send')) {
                e.preventDefault();
                submitFilters();
            }
        });

        on(document, 'click', function (e) {
            if (e.target.id === 'reset-form' || e.target.closest('#reset-form')) {
                e.preventDefault();
                clearAllFilters();
            }
        });

        // Prevent default form submissions for filter forms.
        qsa('form[id^=form-]').forEach(function (form) {
            if (form.id === 'form-bulk-actions' || form.id === 'form-clearall') return;
            on(form, 'submit', function (e) {
                e.preventDefault();
            });
        });
    }

    // ============================
    // EVENT DELEGATION: BULK BUTTONS
    // ============================

    function initBulkButtons() {
        // Select all.
        var selectAll = qs('#bulk-select-all');
        if (selectAll) {
            on(selectAll, 'click', function () {
                var checked = selectAll.checked;
                qsa('#form-bulk-actions input[type=checkbox]').forEach(function (cb) {
                    cb.checked = checked;
                    cb.closest('tr') && cb.closest('tr').classList.toggle('table-active', checked);
                });
                if (checked) {
                    selectedBulk = qsa('input[name^=id]:enabled').map(function (el) { return el.value; });
                } else {
                    selectedBulk = [];
                }
                setCookie('selected_bulks', JSON.stringify(selectedBulk), 1);
                enableBulkButtons(selectedBulk.length > 0);
            });
        }

        // Individual checkboxes.
        on(document, 'change', function (e) {
            var cb = e.target;
            if (!cb.matches('#form-bulk-actions input[type=checkbox]')) return;
            var tr = cb.closest('tr');
            if (tr) tr.classList.toggle('table-active', cb.checked);
            var val = cb.getAttribute('name') && cb.getAttribute('name').startsWith('id') ? cb.value : null;
            if (val) {
                if (cb.checked) {
                    if (selectedBulk.indexOf(val) === -1) selectedBulk.push(val);
                } else {
                    selectedBulk = selectedBulk.filter(function (v) { return v !== val; });
                }
            }
            setCookie('selected_bulks', JSON.stringify(selectedBulk), 1);
            enableBulkButtons(getCheckedUsers().length > 0);
        });

        // Restore cookie-selected checkboxes.
        selectedBulk.forEach(function (uid) {
            var cb = qs('input[name="id[' + uid + ']"], input[name="id[]"][value="' + uid + '"]');
            if (cb && !cb.checked) {
                cb.checked = true;
                cb.closest('tr') && cb.closest('tr').classList.add('table-active');
            }
        });
        enableBulkButtons(getCheckedUsers().length > 0);

        // Enrol button.
        on(document, 'click', function (e) {
            var btn = e.target.closest('#enrol_user');
            if (!btn) return;
            e.preventDefault();
            if (getCheckedUsers().length === 0) return;
            openEnrolModal();
        });

        // Suspend/Unsuspend bulk.
        on(document, 'click', function (e) {
            var btn = e.target.closest('.btn-bulk-suspend_unsuspend');
            if (!btn) return;
            e.preventDefault();
            var actionType = btn.dataset.action || 'suspend';
            var users = getCheckedUsers();
            if (users.length === 0) return;
            var extraParams = {};
            extraParams[actionType] = 1;
            submitBulkAction('suspend_unsuspend', extraParams);
        });

        // Generic bulk actions (delete, download, message, cohortadd).
       // Generic bulk actions (delete, download, message, cohortadd).
on(document, 'click', function (e) {
    var btn = e.target.closest('.btn-bulk-action[data-action]');
    if (!btn) return;
    e.preventDefault();
    var action = btn.dataset.action;
    var users  = getCheckedUsers();
    if (users.length === 0) return;

    // if (action === 'delete') {
    //     confirmDialog(
    //         getString('confirm_bulk_delete', 'local_people') || 'Are you sure you want to delete selected users?',
    //         function () { submitBulkAction('delete'); }
    //     );
    // } else
         if (action === 'cohortadd') {
        // Open cohort modal instead of direct form submit
        openCohortModal();
    }   else if (action === 'message') {
        openMessageModal();          // ← intercept message action
    }
    else {
        submitBulkAction(action);
    }
});

        // Invite user.
        on(document, 'click', function (e) {
            if (e.target.closest('#btn_invite_user')) {
                e.preventDefault();
                inviteUser();
            }
        });

        // Add User button.
        on(document, 'click', function (e) {
            if (e.target.closest('.create-user-top')) {
                e.preventDefault();
                openAddUserModal();
            }
        });
    }


    // ============================
    // TEXT FILTER: SUBMIT ON ENTER
    // ============================

    function initTextFilterEnterKey() {
        on(document, 'keydown', function (e) {
            if (e.key !== 'Enter') return;
            var input = e.target;
            if (!input.classList.contains('people-text-filter-input')) return;
            e.preventDefault();
            input.classList.add('people-filter-input--applying');
            setTimeout(function () { input.classList.remove('people-filter-input--applying'); }, 200);
            submitFilters();
        });
        on(document, 'focusin', function (e) {
            if (!e.target.classList.contains('people-text-filter-input')) return;
            var hint = e.target.closest('.people-text-filter-wrap') && e.target.closest('.people-text-filter-wrap').querySelector('.people-filter-enter-hint');
            if (hint) hint.style.opacity = '1';
        });
        on(document, 'focusout', function (e) {
            if (!e.target.classList.contains('people-text-filter-input')) return;
            var hint = e.target.closest('.people-text-filter-wrap') && e.target.closest('.people-text-filter-wrap').querySelector('.people-filter-enter-hint');
            if (hint) hint.style.opacity = '0';
        });
    }

    // ============================
    // SUB-FILTER ACCORDION (collapsible filters inside panels)
    // ============================

    function initSubFilterAccordion() {
        qsa('.people-subfilter').forEach(function (wrap) {
            var body   = qs('.people-subfilter-body', wrap);
            var header = qs('.people-subfilter-header', wrap);
            if (!body || !header) return;

            // Auto-open if an active filter input exists
            var hasActive = qs('.filter-true', wrap)
                         || qs('input[type="checkbox"]:checked', wrap)
                         || (function(){
                                var inp = qs('input[type="text"].people-text-filter-input', wrap);
                                return inp && inp.value.trim() !== '';
                            })();

            if (hasActive) {
                body.style.display = 'block';
                header.setAttribute('aria-expanded', 'true');
                header.classList.add('people-subfilter-header--open');
            }

            on(header, 'click', function () {
                var isOpen = body.style.display !== 'none';
                body.style.display = isOpen ? 'none' : 'block';
                header.setAttribute('aria-expanded', String(!isOpen));
                header.classList.toggle('people-subfilter-header--open', !isOpen);
            });

            on(header, 'keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); header.click(); }
            });
        });
    }

    // ============================
    // TOP APPLIED FILTER TAGS (above table - built from active PHP-rendered tags)
    // ============================

    function initTopFilterTags() {
        var container = qs('#people-top-filter-tags');
        if (!container) return;

        // PHP renders .people-filter-tag-src spans (data-name, data-label) inside .people-filter-tags-source
        // These are invisible data carriers — we read them and build styled tags in the top bar.
        var srcSpans = qsa('.people-filter-tag-src');

        if (srcSpans.length === 0) {
            container.style.display = 'none';
            return;
        }

        container.innerHTML = '';
        container.style.display = 'flex';

        srcSpans.forEach(function (src) {
            var name  = src.dataset.name;
            var label = src.dataset.label || name;

            var tag = document.createElement('span');
            tag.className = 'people-applied-top-tag';
            tag.dataset.name = name;
            tag.innerHTML =
                '<span class="people-applied-top-tag__label">' +
                    '<i class="fa fa-filter mr-1" style="font-size:.65rem;opacity:.75;"></i>' +
                    label +
                '</span>' +
                '<span class="people-applied-top-tag__remove" title="Remove filter">' +
                    '<i class="fa fa-times"></i>' +
                '</span>';

            qs('.people-applied-top-tag__remove', tag).addEventListener('click', function (e) {
                e.preventDefault();
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.pathname + window.location.search;
                var fi = document.createElement('input');
                fi.type  = 'hidden';
                fi.name  = 'removefilter';
                fi.value = name;
                form.appendChild(fi);
                // Preserve other URL params
                var urlParams = new URLSearchParams(window.location.search);
                urlParams.forEach(function(val, key) {
                    if (key !== 'removefilter') {
                        var inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = key;
                        inp.value = val;
                        form.appendChild(inp);
                    }
                });
                document.body.appendChild(form);
                form.submit();
            });

            container.appendChild(tag);
        });
    }

    // ============================
    // COURSE FILTER: SEARCH + SELECTED TAGS + SYNC TO SELECT
    // ============================

    function initCourseFilter() {
        var searchInput  = qs('#people-course-search');
        var checkList    = qs('#people-course-checkbox-list');
        var tagsDisplay  = qs('#people-selected-courses-display');
        var hiddenSelect = qs('#menucourserolename');

        if (!searchInput || !checkList || !hiddenSelect) return;

        function syncSelectFromCheckboxes() {
            var checked = qsa('.people-course-checkbox:checked', checkList);
            var vals = checked.map(function (cb) { return cb.value; });
            for (var i = 0; i < hiddenSelect.options.length; i++) {
                hiddenSelect.options[i].selected = vals.indexOf(hiddenSelect.options[i].value) !== -1;
            }
        }

        function renderSelectedTags() {
            if (!tagsDisplay) return;
            qsa('.people-course-selected-tag[data-dynamic]', tagsDisplay).forEach(function (t) { t.remove(); });
            var checked = qsa('.people-course-checkbox:checked', checkList);
            checked.forEach(function (cb) {
                var val = cb.value;
                if (qs('.people-course-selected-tag[data-value="' + val + '"]', tagsDisplay)) return;
                var item = cb.closest('.people-course-item');
                var text = item ? item.querySelector('.people-course-item-label').textContent : val;
                var tag = document.createElement('span');
                tag.className = 'people-course-selected-tag';
                tag.dataset.value = val;
                tag.dataset.dynamic = '1';
                tag.innerHTML =
                    '<span class="people-course-tag-text">' + text + '</span>' +
                    '<span class="people-course-tag-remove" title="Remove"><i class="fa fa-times"></i></span>';
                tagsDisplay.appendChild(tag);
            });
            // Remove stale PHP-rendered tags
            qsa('.people-course-selected-tag:not([data-dynamic])', tagsDisplay).forEach(function (t) {
                var cb = checkList.querySelector('.people-course-checkbox[value="' + t.dataset.value + '"]');
                if (cb && !cb.checked) t.remove();
            });
        }

        function filterCourseList(term) {
            var lower = term.toLowerCase().trim();
            qsa('.people-course-item', checkList).forEach(function (item) {
                var text = item.querySelector('.people-course-item-label').textContent.toLowerCase();
                item.style.display = (!lower || text.indexOf(lower) !== -1) ? '' : 'none';
            });
        }

        // On load: sync hidden select → checkboxes
        (function() {
            var vals = [];
            for (var i = 0; i < hiddenSelect.options.length; i++) {
                if (hiddenSelect.options[i].selected) vals.push(hiddenSelect.options[i].value);
            }
            if (vals.length) {
                qsa('.people-course-checkbox', checkList).forEach(function (cb) {
                    if (vals.indexOf(cb.value) !== -1) cb.checked = true;
                });
                renderSelectedTags();
            }
        })();

        on(searchInput, 'input', function () { filterCourseList(searchInput.value); });

        on(checkList, 'change', function (e) {
            if (!e.target.classList.contains('people-course-checkbox')) return;
            syncSelectFromCheckboxes();
            renderSelectedTags();
        });

        on(document, 'click', function (e) {
            var btn = e.target.closest('.people-course-tag-remove');
            if (!btn) return;
            var tag = btn.closest('.people-course-selected-tag');
            if (!tag) return;
            var val = tag.dataset.value;
            var cb = checkList && checkList.querySelector('.people-course-checkbox[value="' + val + '"]');
            if (cb) cb.checked = false;
            tag.remove();
            syncSelectFromCheckboxes();
        });
    }

    // ============================
    // DATE FILTER: INLINE CALENDAR PICKER
    // ============================

    var _calState = { open: null };

    function buildCalendar(year, month, inputId) {
        var today = new Date();
        var firstDay    = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var monthNames  = ['January','February','March','April','May','June',
                           'July','August','September','October','November','December'];

        var html =
            '<div class="ppl-cal" data-input-id="' + inputId + '">' +
            '<div class="ppl-cal-nav">' +
              '<button type="button" class="ppl-cal-btn ppl-cal-prev" data-year="' + year + '" data-month="' + month + '" data-input="' + inputId + '">' +
                '<i class="fa fa-chevron-left"></i>' +
              '</button>' +
              '<span class="ppl-cal-title">' + monthNames[month] + ' ' + year + '</span>' +
              '<button type="button" class="ppl-cal-btn ppl-cal-next" data-year="' + year + '" data-month="' + month + '" data-input="' + inputId + '">' +
                '<i class="fa fa-chevron-right"></i>' +
              '</button>' +
            '</div>' +
            '<div class="ppl-cal-weekdays">' +
              ['Su','Mo','Tu','We','Th','Fr','Sa'].map(function(d){ return '<span>' + d + '</span>'; }).join('') +
            '</div>' +
            '<div class="ppl-cal-grid">';

        // Empty cells before first day
        for (var e = 0; e < firstDay; e++) {
            html += '<span class="ppl-cal-cell ppl-cal-empty"></span>';
        }

        for (var day = 1; day <= daysInMonth; day++) {
            var d = new Date(year, month, day);
            var isToday   = (d.toDateString() === today.toDateString());
            var dateStr   = String(day).padStart(2,'0') + ' / ' + String(month+1).padStart(2,'0') + ' / ' + year;
            var classes   = 'ppl-cal-cell ppl-cal-day' + (isToday ? ' ppl-cal-today' : '');
            html += '<span class="' + classes + '" data-date="' + dateStr + '" data-input="' + inputId + '">' + day + '</span>';
        }

        html += '</div></div>';
        return html;
    }

    function openCalendar(inputId) {
        var popup = qs('#people-cal-' + inputId);
        if (!popup) return;
        if (_calState.open && _calState.open !== inputId) closeCalendar(_calState.open);
        var input = qs('#' + inputId);
        var d = new Date();
        if (input && input.value) {
            // parse DD / MM / YYYY
            var parts = input.value.replace(/\s/g,'').split('/');
            if (parts.length === 3) {
                var parsed = new Date(parseInt(parts[2],10), parseInt(parts[1],10)-1, parseInt(parts[0],10));
                if (!isNaN(parsed)) d = parsed;
            }
        }
        popup.innerHTML = buildCalendar(d.getFullYear(), d.getMonth(), inputId);
        popup.classList.add('ppl-cal-open');
        _calState.open = inputId;
    }

    function closeCalendar(inputId) {
        var popup = qs('#people-cal-' + inputId);
        if (popup) popup.classList.remove('ppl-cal-open');
        if (_calState.open === inputId) _calState.open = null;
    }

    function closeAllCalendars() {
        qsa('.people-calendar-popup').forEach(function (p) { p.classList.remove('ppl-cal-open'); });
        _calState.open = null;
    }

    function initCalendarDatePickers() {
        // Single unified click handler — order of checks matters:
        // nav buttons checked FIRST so they don't fall through to the input-wrap opener.
        on(document, 'click', function (e) {

            // 1. Month prev/next navigation (must be checked before input-wrap)
            var navBtn = e.target.closest('.ppl-cal-btn');
            if (navBtn) {
                e.stopPropagation();
                var year    = parseInt(navBtn.dataset.year, 10);
                var month   = parseInt(navBtn.dataset.month, 10);
                var inputId = navBtn.dataset.input;
                if (navBtn.classList.contains('ppl-cal-prev')) {
                    month--; if (month < 0)  { month = 11; year--; }
                } else {
                    month++; if (month > 11) { month = 0;  year++; }
                }
                var popup = qs('#people-cal-' + inputId);
                if (popup) popup.innerHTML = buildCalendar(year, month, inputId);
                return; // stop — don't fall through to input-wrap handler
            }

            // 2. Day cell selection
            var cell = e.target.closest('.ppl-cal-day');
            if (cell) {
                e.stopPropagation();
                var dateStr = cell.dataset.date;
                var inputId = cell.dataset.input;
                var input   = qs('#' + inputId);
                if (input) {
                    input.value = dateStr;
                    var popup = qs('#people-cal-' + inputId);
                    if (popup) {
                        qsa('.ppl-cal-day--selected', popup).forEach(function (c) {
                            c.classList.remove('ppl-cal-day--selected');
                        });
                        cell.classList.add('ppl-cal-day--selected');
                    }
                    setTimeout(function () { closeCalendar(inputId); }, 200);
                }
                return;
            }

            // 3. Input wrap (or calendar icon) — open/close toggle
            var wrap = e.target.closest('.people-date-input-wrap');
            if (wrap) {
                e.stopPropagation();
                var input = wrap.querySelector('.people-date-picker');
                if (!input) return;
                var id = input.id;
                if (_calState.open === id) { closeCalendar(id); } else { openCalendar(id); }
                return;
            }

            // 4. Click outside any date-picker-field — close all
            if (!e.target.closest('.people-date-picker-field')) {
                closeAllCalendars();
            }
        });

        on(document, 'keydown', function (e) {
            if (e.key === 'Escape' && _calState.open) closeCalendar(_calState.open);
        });
    }


    // ============================
    // GENERIC CHECKBOX-LIST FILTER (city, country — same as course pattern)
    // ============================

    function initGenericCheckboxFilters() {
        // Wire up every .people-generic-checkbox-list
        qsa('.people-generic-checkbox-list').forEach(function (list) {
            var tagsContainerId = list.dataset.tagsContainer;
            var hiddenSelectId  = list.dataset.hiddenSelect;
            var tagsDisplay     = tagsContainerId ? qs('#' + tagsContainerId) : null;
            var hiddenSelect    = hiddenSelectId  ? qs('#' + hiddenSelectId)  : null;

            // Search input that targets this list
            var searchInput = null;
            var wrap = list.closest('.people-subfilter-body');
            if (wrap) searchInput = qs('.people-select-search', wrap);

            function syncSelect() {
                if (!hiddenSelect) return;
                var vals = qsa('.people-generic-checkbox:checked', list).map(function(cb){ return cb.value; });
                for (var i = 0; i < hiddenSelect.options.length; i++) {
                    hiddenSelect.options[i].selected = vals.indexOf(hiddenSelect.options[i].value) !== -1;
                }
            }

            function renderTags() {
                if (!tagsDisplay) return;
                // Remove JS-dynamic tags
                qsa('.people-course-selected-tag[data-dynamic]', tagsDisplay).forEach(function(t){ t.remove(); });
                var checked = qsa('.people-generic-checkbox:checked', list);
                checked.forEach(function(cb) {
                    var val = cb.value;
                    if (qs('.people-course-selected-tag[data-value="' + val + '"]', tagsDisplay)) return;
                    var item = cb.closest('.people-course-item');
                    var text = item ? item.querySelector('.people-course-item-label').textContent : val;
                    var tag = document.createElement('span');
                    tag.className = 'people-course-selected-tag';
                    tag.dataset.value = val;
                    tag.dataset.dynamic = '1';
                    tag.innerHTML =
                        '<span class="people-course-tag-text">' + text + '</span>' +
                        '<span class="people-course-tag-remove" title="Remove"><i class="fa fa-times"></i></span>';
                    tagsDisplay.appendChild(tag);
                });
                // Remove PHP-pre-rendered tags whose checkbox is now unchecked
                qsa('.people-course-selected-tag:not([data-dynamic])', tagsDisplay).forEach(function(t) {
                    var cb = list.querySelector('.people-generic-checkbox[value="' + t.dataset.value + '"]');
                    if (cb && !cb.checked) t.remove();
                });
            }

            // Sync hidden select → checkboxes on page load (in case PHP marked options)
            if (hiddenSelect) {
                var preVals = [];
                for (var i = 0; i < hiddenSelect.options.length; i++) {
                    if (hiddenSelect.options[i].selected) preVals.push(hiddenSelect.options[i].value);
                }
                if (preVals.length) {
                    qsa('.people-generic-checkbox', list).forEach(function(cb) {
                        if (preVals.indexOf(cb.value) !== -1) cb.checked = true;
                    });
                    renderTags();
                }
            }

            // Checkbox change
            on(list, 'change', function(e) {
                if (!e.target.classList.contains('people-generic-checkbox')) return;
                syncSelect();
                renderTags();
            });

            // Tag remove (delegated on document, handled by data-value)
            if (tagsDisplay) {
                on(tagsDisplay, 'click', function(e) {
                    var btn = e.target.closest('.people-course-tag-remove');
                    if (!btn) return;
                    var tag = btn.closest('.people-course-selected-tag');
                    if (!tag) return;
                    var val = tag.dataset.value;
                    var cb = list.querySelector('.people-generic-checkbox[value="' + val + '"]');
                    if (cb) cb.checked = false;
                    tag.remove();
                    syncSelect();
                });
            }

            // Live search
            if (searchInput) {
                on(searchInput, 'input', function() {
                    var lower = searchInput.value.toLowerCase().trim();
                    qsa('.people-course-item', list).forEach(function(item) {
                        var text = item.querySelector('.people-course-item-label').textContent.toLowerCase();
                        item.style.display = (!lower || text.indexOf(lower) !== -1) ? '' : 'none';
                    });
                });
            }
        });
    }


    // ============================
    // INIT
    // ============================

    function init() {
        initLeftPanelToggle();
        initAccordion();
        initSearchBox();
        initCompanySelect();
        initRecordsPerPage();
        initBulkButtons();
        initSortableHeaders();
        initRadioGroups();
        initFilterTags();
        initFilterForms();
        initMultitenantToggle();
        initTextFilterEnterKey();
        initSubFilterAccordion();
        initCourseFilter();
        initTopFilterTags();
        initCalendarDatePickers();
        initGenericCheckboxFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

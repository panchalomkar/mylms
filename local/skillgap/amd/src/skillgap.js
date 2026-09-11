/**
 * AMD module: local_skillgap/skillgap  v3.1
 *
 * Handles:
 *  1. Competency radar/spider chart (canvas)
 *  2. Progress detail popup (Bootstrap modal + AJAX)
 *  3. AI question generation (AJAX to ajax_helper.php)
 *  4. Assessment form: dynamic designation + competency dropdowns
 *  5. Cluster form: competency checkbox collector
 *  6. Assessment radio: save-on-click + highlight
 *  7. Sub-competency row toggle
 *  8. Assessment progress bar update
 */
define(['jquery', 'core/ajax', 'core/notification'], function ($, Ajax, Notification) {
    'use strict';

    var wwwroot = window.M && M.cfg ? M.cfg.wwwroot : '';
    var sesskey = window.M && M.cfg ? M.cfg.sesskey  : '';

    // ── 1. Spider / Radar chart ──────────────────────────────────────────────

    function drawSpiderChart() {
        var canvas = document.getElementById('sg-spider-chart');
        if (!canvas || !canvas.getContext) { return; }

        var labelsRaw = canvas.getAttribute('data-labels');
        var scoresRaw = canvas.getAttribute('data-scores');
        if (!labelsRaw || !scoresRaw) { return; }

        var labels, scores;
        try { labels = JSON.parse(labelsRaw); scores = JSON.parse(scoresRaw); }
        catch (e) { return; }
        if (!labels || !labels.length) { return; }

        var ctx = canvas.getContext('2d');
        var W = canvas.width, H = canvas.height;
        var cx = W / 2, cy = H / 2;
        var R  = Math.min(cx, cy) - 44;
        var N  = labels.length;

        ctx.clearRect(0, 0, W, H);

        // Grid rings
        for (var lv = 1; lv <= 5; lv++) {
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(3,50,82,0.1)';
            ctx.lineWidth   = 0.8;
            for (var i = 0; i < N; i++) {
                var ang = (Math.PI * 2 * i / N) - Math.PI / 2;
                var r   = R * lv / 5;
                var px  = cx + r * Math.cos(ang);
                var py  = cy + r * Math.sin(ang);
                if (i === 0) { ctx.moveTo(px, py); } else { ctx.lineTo(px, py); }
            }
            ctx.closePath();
            ctx.stroke();
        }

        // Axes + labels
        for (var i = 0; i < N; i++) {
            var ang = (Math.PI * 2 * i / N) - Math.PI / 2;
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(3,50,82,0.15)';
            ctx.lineWidth   = 1;
            ctx.moveTo(cx, cy);
            ctx.lineTo(cx + R * Math.cos(ang), cy + R * Math.sin(ang));
            ctx.stroke();

            var lx  = cx + (R + 22) * Math.cos(ang);
            var ly  = cy + (R + 22) * Math.sin(ang);
            var lbl = labels[i] || '';
            if (lbl.length > 12) { lbl = lbl.substring(0, 11) + '…'; }
            ctx.fillStyle    = '#495057';
            ctx.font         = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
            ctx.textAlign    = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(lbl, lx, ly);
        }

        // Data polygon
        ctx.beginPath();
        ctx.fillStyle   = 'rgba(3,50,82,0.18)';
        ctx.strokeStyle = '#033252';
        ctx.lineWidth   = 2;
        for (var i = 0; i < N; i++) {
            var ang  = (Math.PI * 2 * i / N) - Math.PI / 2;
            var frac = Math.min((parseFloat(scores[i]) || 0) / 5, 1);
            var px   = cx + R * frac * Math.cos(ang);
            var py   = cy + R * frac * Math.sin(ang);
            if (i === 0) { ctx.moveTo(px, py); } else { ctx.lineTo(px, py); }
        }
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // Data points
        for (var i = 0; i < N; i++) {
            var ang  = (Math.PI * 2 * i / N) - Math.PI / 2;
            var frac = Math.min((parseFloat(scores[i]) || 0) / 5, 1);
            ctx.beginPath();
            ctx.arc(cx + R * frac * Math.cos(ang), cy + R * frac * Math.sin(ang), 4, 0, Math.PI * 2);
            ctx.fillStyle = '#eb980d';
            ctx.fill();
        }
    }

    // ── 2. Progress detail popup ─────────────────────────────────────────────

    function bindProgressPopup() {
        $(document).on('click', '.sg-progress-btn', function (e) {
            e.preventDefault();
            var userid   = $(this).data('userid');
            var compid   = $(this).data('compid');
            var compname = $(this).data('compname') || '';

            $('#sg-modal-compname').text(compname);
            $('#sg-progress-modal-body').html(
                '<div class="text-center py-4">' +
                '<div class="spinner-border text-primary" role="status"></div>' +
                '<p class="mt-2 text-muted small">Loading...</p></div>'
            );
            $('#sg-progress-modal').modal('show');

            Ajax.call([{
                methodname: 'local_skillgap_get_progress_detail',
                args: { userid: parseInt(userid, 10), compid: parseInt(compid, 10) },
                done: function (result) {
                    var indicator = '&mdash;';
                    if (result.trend_up)   { indicator = '<span class="text-success font-weight-bold">&#8593; Improved</span>'; }
                    if (result.trend_down) { indicator = '<span class="text-danger font-weight-bold">&#8595; Declined</span>'; }
                    if (result.is_first)   { indicator = '<span class="badge badge-info">First Attempt</span>'; }

                    var html = '<div class="table-responsive">' +
                        '<table class="table table-bordered table-sm mb-0">' +
                        '<thead class="thead-light"><tr>' +
                        '<th>Current Score</th><th>Previous Score</th><th>Progress</th><th>Trend</th>' +
                        '</tr></thead><tbody><tr>' +
                        '<td class="font-weight-bold text-primary h5 align-middle">' + result.current_score + '</td>' +
                        '<td class="text-muted align-middle">'  + result.prev_score  + '</td>' +
                        '<td class="font-weight-bold align-middle">' + result.progress + '</td>' +
                        '<td class="align-middle">' + indicator + '</td>' +
                        '</tr></tbody></table></div>';

                    if (result.is_first) {
                        html += '<p class="text-muted small mt-2 mb-0">' +
                            '<i class="fa fa-info-circle mr-1"></i>' +
                            'This is your first attempt — progress will show after a second attempt.</p>';
                    }
                    $('#sg-progress-modal-body').html(html);
                },
                fail: function (ex) {
                    $('#sg-progress-modal-body').html(
                        '<div class="alert alert-danger mb-0">Failed to load progress data. Please try again.</div>'
                    );
                    Notification.exception(ex);
                }
            }]);
        });
    }

    // ── 3. AI Question Generation ────────────────────────────────────────────
    // Uses ajax_helper.php directly (not Moodle web service) for simplicity.

    function bindAiGenerate() {
        $(document).on('click', '.sg-ai-generate-btn', function (e) {
            e.preventDefault();
            var btn     = $(this);
            var compid  = btn.data('compid');
            var aidInput= btn.data('assessmentid') || 0;
            var status  = btn.closest('.sg-ai-section').find('.sg-ai-status');

            if (!compid) {
                status.html('<div class="alert alert-warning py-2 small">Please select a competency first.</div>');
                return;
            }

            btn.prop('disabled', true)
               .html('<span class="spinner-border spinner-border-sm mr-1" role="status"></span>Generating...');
            status.html('');

            $.ajax({
                url:      wwwroot + '/local/skillgap/admin/ajax_helper.php',
                method:   'POST',
                dataType: 'json',
                data: {
                    action:  'generate_ai_questions',
                    compid:  compid,
                    sesskey: sesskey
                },
                success: function (data) {
                    btn.prop('disabled', false)
                       .html('<i class="fa fa-magic mr-1"></i>Generate with AI');

                    if (data && data.status === 'success') {
                        status.html('<div class="alert alert-success py-2 small">' +
                            '<i class="fa fa-check mr-1"></i>' + data.message + ' Reloading...</div>');
                        setTimeout(function () {
                            var url = wwwroot + '/local/skillgap/admin/questions.php' +
                                '?compid=' + compid +
                                (aidInput ? '&assessmentid=' + aidInput : '');
                            window.location.href = url;
                        }, 1400);
                    } else {
                        status.html('<div class="alert alert-danger py-2 small">' +
                            '<i class="fa fa-times mr-1"></i>' +
                            (data ? data.message : 'Generation failed.') + '</div>');
                    }
                },
                error: function () {
                    btn.prop('disabled', false)
                       .html('<i class="fa fa-magic mr-1"></i>Generate with AI');
                    status.html('<div class="alert alert-danger py-2 small">Request failed. Check your OpenAI API key in Settings.</div>');
                }
            });
        });
    }

    // ── 4. Assessment form: dynamic designations + competencies ──────────────

    function bindAssessmentForm() {

        // Designation dropdown loads when department changes (admin create assessment form)
        $(document).on('change', '#sg-create-assessment-form #sg-assess-dept, #sg-assess-dept', function () {
            var deptid = $(this).val();
            var desigSel  = $('#sg-assess-desig');
            var compWrap  = $('#sg-comp-checkboxes');

            desigSel.html('<option value="">Loading...</option>').prop('disabled', true);
            compWrap.html('<p class="text-muted small mb-0"><span class="spinner-border spinner-border-sm mr-1"></span>Loading competencies...</p>');

            if (!deptid) {
                desigSel.html('<option value="">-- Select Designation --</option>').prop('disabled', false);
                compWrap.html('<p class="text-muted small mb-0">Select department first.</p>');
                return;
            }

            $.ajax({
                url:      wwwroot + '/local/skillgap/admin/ajax_helper.php',
                method:   'GET',
                dataType: 'json',
                data: { action: 'get_designations', deptid: deptid, sesskey: sesskey },
                success: function (data) {
                    desigSel.prop('disabled', false);
                    if (!data || !data.designations || !data.designations.length) {
                        desigSel.html('<option value="">-- No designations found --</option>');
                        return;
                    }
                    var opts = '<option value="">-- Select Designation --</option>';
                    $.each(data.designations, function (i, d) {
                        opts += '<option value="' + d.id + '">' + $('<span>').text(d.name).html() + '</option>';
                    });
                    desigSel.html(opts);
                    // Load competencies for all designations in dept too
                    loadCompetencies(deptid, 0, compWrap);
                },
                error: function () {
                    desigSel.html('<option value="">Error loading — refresh page</option>').prop('disabled', false);
                }
            });
        });

        // Competencies reload when designation changes
        $(document).on('change', '#sg-assess-desig', function () {
            var deptid  = $('#sg-assess-dept').val();
            var desigid = $(this).val();
            loadCompetencies(deptid, desigid, $('#sg-comp-checkboxes'));
        });

        // Collect checked competencies on form submit (ONLY for admin create form)
        $(document).on('submit', '#sg-create-assessment-form', function (e) {
            var selected = [];
            $('#sg-comp-checkboxes input.sg-comp-cb:checked').each(function () {
                var type      = $(this).data('type') || 'main';
                var compid    = $(this).val();
                var subcompid = $(this).data('subcompid') || '';
                if (type === 'main') {
                    selected.push('main.' + compid);
                } else {
                    selected.push(type + '.' + compid + '.' + (subcompid || '0'));
                }
            });
            if (!selected.length) {
                e.preventDefault();
                alert('Please select at least one competency for this assessment.');
                return false;
            }
            $('#sg-compids-input').val(selected.join(','));
        });


        // Update competency preview table when checkboxes change
        $(document).on('change', '#sg-comp-checkboxes input[type="checkbox"]', function () {
            var rows = '';
            var total = 0;
            var levelLabels = {main: 'Main', sub: 'Sub', subsub: 'Sub-Sub'};
            var levelColors = {main: '#033252', sub: '#eb980d', subsub: '#ffc107'};
            $('#sg-comp-checkboxes input.sg-comp-cb:checked').each(function () {
                // Use data-name attribute (clean name set when building checkbox HTML).
                var name   = $(this).data('name') || 'Unknown';
                var qcount = parseInt($(this).data('qcount') || 5, 10);
                var type   = $(this).data('type') || 'main';
                // Each selected competency always contributes 5 questions (quiz rule).
                var contrib = 5;
                total += contrib;
                rows += '<tr>' +
                    '<td>' + $('<span>').text(name).html() + '</td>' +
                    '<td class="text-center"><span class="badge badge-light border small" style="color:' + (levelColors[type]||'#333') + ';">' + (levelLabels[type]||type) + '</span></td>' +
                    '<td class="text-center"><span class="badge badge-' + (qcount >= 5 ? 'success' : 'warning') + '">' + contrib + '</span></td></tr>';
            });
            if (rows) {
                $('#sg-comp-preview-body').html(rows);
                $('#sg-comp-preview-total').text(total);
                $('#sg-comp-preview').show();
            } else {
                $('#sg-comp-preview').hide();
            }
        });

        // Set today as default start date
        var sd = document.getElementById('sg-startdate');
        if (sd && !sd.value) {
            sd.value = new Date().toISOString().split('T')[0];
        }
    }

    function loadCompetencies(deptid, desigid, container) {
        if (!deptid) {
            container.html('<p class="text-muted small mb-0">Select a department first.</p>');
            return;
        }
        container.html('<p class="text-muted small mb-0"><span class="spinner-border spinner-border-sm mr-1"></span>Loading...</p>');

        $.ajax({
            url:      wwwroot + '/local/skillgap/admin/ajax_helper.php',
            method:   'GET',
            dataType: 'json',
            data: { action: 'get_competencies_tree', deptid: deptid, desigid: desigid || 0, sesskey: sesskey },
            success: function (data) {
                if (!data || !data.competencies || !data.competencies.length) {
                    container.html('<p class="text-muted small mb-0">No competencies found. Add competencies first.</p>');
                    return;
                }
                var html = '';
                $.each(data.competencies, function (i, c) {
                    // ── Main competency row ──
                    var qBadge = '<span class="badge badge-' + (c.q_count >= 5 ? 'success' : 'warning') + ' ml-1">' + c.q_count + '/5</span>';
                    html += '<div class="sg-comp-tree-item mb-1" style="border-left:3px solid #033252;padding-left:8px;margin-bottom:6px;">' +
                        '<div class="form-check">' +
                        '<input type="checkbox" class="form-check-input sg-comp-cb" ' +
                        ' value="' + c.id + '" id="ck_main_' + c.id + '"' +
                        (c.q_count >= 5 ? '' : ' disabled') +
                        ' data-qcount="' + c.q_count + '" data-type="main" data-name="' + $('<span>').text(c.name).html() + '">' +
                        '<label class="form-check-label font-weight-bold small" for="ck_main_' + c.id + '">' +
                        $('<span>').text(c.name).html() + qBadge + '</label></div>';

                    // ── Sub-competencies ──
                    if (c.subs && c.subs.length) {
                        html += '<div class="pl-3 mt-1">';
                        $.each(c.subs, function(si, s) {
                            var sqBadge = '<span class="badge badge-' + (s.q_count >= 5 ? 'success' : 'warning') + ' ml-1">' + s.q_count + '/5</span>';
                            html += '<div class="form-check mb-1" style="border-left:3px solid #eb980d;padding-left:6px;">' +
                                '<input type="checkbox" class="form-check-input sg-comp-cb" ' +
                                ' value="' + c.id + '" id="ck_sub_' + s.id + '"' +
                                (s.q_count >= 5 ? '' : ' disabled') +
                                ' data-qcount="' + s.q_count + '" data-type="sub" data-subcompid="' + s.id + '" data-name="' + $('<span>').text(s.name).html() + '">' +
                                '<label class="form-check-label small" for="ck_sub_' + s.id + '">' +
                                '↳ ' + $('<span>').text(s.name).html() + sqBadge + '</label></div>';

                            // ── Sub-sub-competencies ──
                            if (s.subs && s.subs.length) {
                                html += '<div class="pl-3">';
                                $.each(s.subs, function(ssi, ss) {
                                    var ssqBadge = '<span class="badge badge-' + (ss.q_count >= 5 ? 'success' : 'warning') + ' ml-1">' + ss.q_count + '/5</span>';
                                    html += '<div class="form-check mb-1" style="border-left:3px solid #ffc107;padding-left:6px;">' +
                                        '<input type="checkbox" class="form-check-input sg-comp-cb" ' +
                                        ' value="' + c.id + '" id="ck_ss_' + ss.id + '"' +
                                        (ss.q_count >= 5 ? '' : ' disabled') +
                                        ' data-qcount="' + ss.q_count + '" data-type="subsub" data-subcompid="' + ss.id + '" data-name="' + $('<span>').text(ss.name).html() + '">' +
                                        '<label class="form-check-label small text-muted" for="ck_ss_' + ss.id + '">' +
                                        '&nbsp;&nbsp;↳ ' + $('<span>').text(ss.name).html() + ssqBadge + '</label></div>';
                                });
                                html += '</div>';
                            }
                        });
                        html += '</div>';
                    }
                    html += '</div>';
                });
                container.html(html);
            },
            error: function () {
                container.html('<p class="text-danger small mb-0">Error loading competencies.</p>');
            }
        });
    }

    // ── 5. Cluster form: competency checkbox collector ────────────────────────

    function bindClusterForm() {
        $(document).on('submit', '#sg-cluster-form', function (e) {
            var selected = [];
            $('.sg-cluster-comp-cb:checked').each(function () {
                selected.push($(this).val());
            });
            if (!selected.length) {
                e.preventDefault();
                alert('Please select at least one competency for the cluster.');
                return false;
            }
            $('#sg-cluster-compids').val(selected.join(','));
        });
    }

    // ── 6. Assessment radio: save via AJAX + visual highlight ─────────────────

    function bindAssessmentRadios() {
        // Save response via Moodle web service
        $(document).on('change', '.sg-answer-radio', function () {
            var radio      = $(this);
            var attemptid  = parseInt(radio.data('attemptid'),  10);
            var questionid = parseInt(radio.data('questionid'), 10);
            var optionid   = parseInt(radio.val(), 10);

            // Highlight selected option
            radio.closest('.sg-options').find('.sg-option-label')
                 .removeClass('border-primary bg-light');
            radio.closest('label').addClass('border-primary bg-light');

            if (!attemptid || !questionid || !optionid) { return; }

            Ajax.call([{
                methodname: 'local_skillgap_save_response',
                args: { attemptid: attemptid, questionid: questionid, optionid: optionid },
                done: function () { /* silent save */ },
                fail: function (ex) { Notification.exception(ex); }
            }]);

            // Update progress bar
            updateProgressBar();
        });
    }

    function updateProgressBar() {
        var total    = $('.sg-answer-radio').length;
        var answered = $('.sg-answer-radio:checked').length;
        if (!total) { return; }
        var pct = Math.round(answered / total * 100);
        $('#sg-progress-bar')
            .css('width', pct + '%')
            .attr('aria-valuenow', pct);
    }

    // ── 7. Sub-competency row toggle ─────────────────────────────────────────

    function bindSubRowToggle() {
        $(document).on('click', '.sg-toggle-subs', function () {
            var compid = $(this).data('compid');
            var icon   = $(this).find('i');
            var rows   = $('.sg-sub-' + compid);
            if (rows.hasClass('d-none')) {
                rows.removeClass('d-none');
                icon.removeClass('fa-caret-right').addClass('fa-caret-down');
            } else {
                rows.addClass('d-none');
                icon.removeClass('fa-caret-down').addClass('fa-caret-right');
            }
        });
    }

    // ── 8. Highlight pre-selected radio on page load ──────────────────────────

    function highlightSelectedRadios() {
        $('.sg-answer-radio:checked').each(function () {
            $(this).closest('label').addClass('border-primary bg-light');
        });
        updateProgressBar();
    }


    // ── 9. Designation hierarchy drag-drop reorder ───────────────────────────

    function bindDesignationReorder() {
        var table = document.getElementById('sg-desig-table');
        if (!table) { return; }

        var tbody = table.querySelector('tbody');
        if (!tbody) { return; }

        var dragging = null;

        $(tbody).find('tr').each(function () {
            var row = this;
            row.setAttribute('draggable', 'true');

            row.addEventListener('dragstart', function (e) {
                dragging = row;
                $(row).addClass('sg-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            row.addEventListener('dragend', function () {
                $(row).removeClass('sg-dragging');
                dragging = null;
                // Save new order via AJAX.
                var ids = [];
                $(tbody).find('tr[data-id]').each(function () {
                    ids.push($(this).data('id'));
                });
                if (!ids.length) { return; }

                // Get deptid from URL.
                var params = new URLSearchParams(window.location.search);
                var deptid = params.get('deptid') || 0;

                $.ajax({
                    url:     wwwroot + '/local/skillgap/admin/designations.php?deptid=' + deptid,
                    method:  'POST',
                    data: {
                        action:  'reorder',
                        order:   ids.join(','),
                        sesskey: sesskey
                    },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (data) {
                        if (data && data.status === 'ok') {
                            // Quietly update position numbers.
                            $(tbody).find('tr[data-id]').each(function (i) {
                                $(this).find('td:first').text(i + 1);
                            });
                        }
                    }
                });
            });

            row.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (dragging && dragging !== row) {
                    var bounding = row.getBoundingClientRect();
                    var offset   = bounding.y + bounding.height / 2;
                    if (e.clientY - offset < 0) {
                        tbody.insertBefore(dragging, row);
                    } else {
                        tbody.insertBefore(dragging, row.nextSibling);
                    }
                }
            });
        });

        // Add visual styling.
        if (!document.getElementById('sg-drag-style')) {
            var style = document.createElement('style');
            style.id  = 'sg-drag-style';
            style.textContent = '.sg-dragging { opacity:0.5; background:#e8f1f8 !important; }' +
                '#sg-desig-table tbody tr[draggable] { cursor:grab; }' +
                '#sg-desig-table tbody tr[draggable]:active { cursor:grabbing; }';
            document.head.appendChild(style);
        }
    }


    // ── 9. Edit Assessment ─────────────────────────────────────────────────────
    function bindEditAssessment() {
        // Reset form back to "create" mode.
        window.sgResetEditForm = function() {
            $('#sg-form-action').val('save');
            $('#sg-edit-id').val('');
            document.getElementById('sg-create-assessment-form').reset();
            $('#sg-submit-label').text('Save & Add Questions');
            $('#sg-cancel-edit-btn').hide();
            $('#sg-form-title').html('<i class="fa fa-plus-circle mr-2" style="color:#eb980d;"></i>Create Assessment');
            $('#sg-compids-input').val('');
            $('#sg-comp-preview').hide();
            window._sgEditPendingCompids = null;
        };

        // Pre-check competencies once checkboxes exist in DOM.
        window.sgPreCheckCompetencies = function(compids) {
            if (!compids || !compids.length) { return; }
            var container = document.getElementById('sg-comp-checkboxes');
            if (!container) { return; }
            // Uncheck all first so we start clean.
            container.querySelectorAll('input.sg-comp-cb').forEach(function(cb) {
                cb.checked = false;
            });
            compids.forEach(function(c) {
                var cb = null;
                if (c.type === 'main') {
                    cb = container.querySelector('input[data-type="main"][value="' + c.compid + '"]');
                } else {
                    // sub or subsub — match by subcompid first, then fall back to type+value.
                    if (c.subcompid) {
                        cb = container.querySelector('input[data-subcompid="' + c.subcompid + '"]');
                    }
                    if (!cb && c.compid && c.type) {
                        cb = container.querySelector('input[data-type="' + c.type + '"][value="' + c.compid + '"]');
                    }
                }
                if (cb) {
                    cb.disabled = false; // Force-enable in edit mode.
                    cb.checked  = true;
                    $(cb).trigger('change');
                }
            });
            // Final trigger to refresh preview table.
            var first = container.querySelector('input.sg-comp-cb:checked');
            if (first) { $(first).trigger('change'); }
        };

        // MutationObserver: auto-check comps once AJAX populates checkboxes.
        window._sgEditPendingCompids = null;
        var obs = new MutationObserver(function() {
            if (!window._sgEditPendingCompids || !window._sgEditPendingCompids.length) { return; }
            var container = document.getElementById('sg-comp-checkboxes');
            if (container && container.querySelector('input.sg-comp-cb')) {
                var compids = window._sgEditPendingCompids;
                window._sgEditPendingCompids = null;
                setTimeout(function() { window.sgPreCheckCompetencies(compids); }, 120);
            }
        });
        var cbContainer = document.getElementById('sg-comp-checkboxes');
        if (cbContainer) { obs.observe(cbContainer, { childList: true, subtree: true }); }

        // Edit button click.
        $(document).on('click', '.sg-edit-assessment-btn', function() {
            var btn       = $(this);
            var id        = btn.data('id');
            var title     = btn.data('title');
            var deptid    = btn.data('deptid')    + '';
            var desigid   = btn.data('desigid')   + '';
            var frequency = btn.data('frequency');
            var startdate = btn.data('startdate');
            var enddate   = btn.data('enddate');
            var compidsRaw= btn.attr('data-compids') || '[]';
            var compids   = [];
            try { compids = JSON.parse(compidsRaw); } catch(e) { compids = []; }

            // Switch form to edit mode.
            $('#sg-form-action').val('edit');
            $('#sg-edit-id').val(id);
            $('#sg-submit-label').text('Update Assessment');
            $('#sg-cancel-edit-btn').show();
            $('#sg-form-title').html('<i class="fa fa-pencil mr-2" style="color:#eb980d;"></i>Edit Assessment');

            // Populate simple text/date fields.
            $('[name="title"]', '#sg-create-assessment-form').val(title || '');
            if (startdate) { $('[name="startdate"]', '#sg-create-assessment-form').val(startdate); }
            if (enddate)   { $('[name="enddate"]',   '#sg-create-assessment-form').val(enddate); }
            if (frequency) { $('[name="frequency"]', '#sg-create-assessment-form').val(frequency); }

            // Store compids for MutationObserver.
            window._sgEditPendingCompids = compids;

            // Set department — triggers designation AJAX.
            var $deptSel = $('#sg-assess-dept');
            if ($deptSel.length && deptid) {
                $deptSel.val(deptid).trigger('change');

                // Poll until designation options appear, then set desig (triggers comp AJAX).
                var attempts = 0;
                var desigPoll = setInterval(function() {
                    attempts++;
                    var $desigSel = $('#sg-assess-desig');
                    if ($desigSel.find('option[value="' + desigid + '"]').length) {
                        clearInterval(desigPoll);
                        $desigSel.val(desigid).trigger('change');
                        // Competencies will be checked by MutationObserver.
                    }
                    if (attempts > 40) { clearInterval(desigPoll); } // 4s timeout
                }, 100);
            }

            // Scroll to form.
            var card = document.getElementById('sg-create-assessment-form');
            if (card) { card.closest('.card').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────────

    return {
        init: function () {
            drawSpiderChart();
            bindProgressPopup();
            bindAiGenerate();
            bindAssessmentForm();
            bindClusterForm();
            bindAssessmentRadios();
            bindSubRowToggle();
            highlightSelectedRadios();
            bindDesignationReorder();
            bindEditAssessment();
        }
    };
});

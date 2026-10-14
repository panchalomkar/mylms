// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Learning Path Analytics page: KPIs, funnel, per-path status, Department -> Designation -> User drill-down,
 * learning-path-count popup and learner detail popup. Everything over AJAX.
 *
 * @module     local_businessintelligence/lppage
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/str', 'local_businessintelligence/charts', 'local_businessintelligence/filters'],
function(Ajax, Str, Charts, Filters) {
    'use strict';

    var NONE = '__none__';
    var root, state, pos, rows = [], overview = null, token = 0, str = {}, icons = {};
    var view = {sort: null, dir: -1, q: '', page: 1, per: 10, branch: '', manager: ''};
    var lastFocus = null;

    var STRINGS = ['chart_alarming', 'chart_moderate', 'chart_ontarget', 'nodata', 'value', 'users', 'outof5', 'norating', 'loaderror', 'notset', 'heading',
        'lp_orgoverview', 'lp_level_dept', 'lp_level_desig', 'lp_level_user', 'lp_backdept', 'lp_backdesig',
        'lp_bydesig', 'lp_byusers', 'lp_overview', 'lp_col_department', 'lp_col_designation', 'lp_col_users',
        'lp_col_action', 'lp_col_user', 'lp_col_user_info', 'lp_col_email', 'lp_col_email_info', 'lp_col_branch',
        'lp_col_branch_info', 'lp_col_manager', 'lp_col_manager_info', 'lp_viewmore', 'lp_viewdetail', 'lp_allbranch',
        'lp_allmanager', 'lp_reportingmanager', 'lp_col_status', 'lp_level', 'lp_goal', 'lp_showing', 'lp_lastrefreshed',
        'report_learningpaths', 'lpa_bydept', 'lpa_chart_level_info', 'lpa_chart_users_info', 'lpa_table_info',
        'lpa_table_users_info', 'lpa_col_users_info', 'lpa_col_paths', 'lpa_col_paths_info', 'lpa_col_enrolled',
        'lpa_col_enrolled_info', 'lpa_col_completed', 'lpa_col_completed_info', 'lpa_col_inprogress',
        'lpa_col_inprogress_info', 'lpa_col_notstarted', 'lpa_col_notstarted_info', 'lpa_col_pct', 'lpa_col_pct_info',
        'lpa_pathspopup', 'lpa_pathspopup_info', 'lpa_col_path', 'lpa_userpopup', 'lpa_userpopup_desc',
        'lpa_col_pathname', 'lpa_col_coursescount', 'lpa_col_progress', 'lpa_col_status_info'];

    // ------------------------------------------------------------------------------------------------------------
    // Helpers.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Create an element.
     *
     * @param {String} tag
     * @param {String} cls
     * @param {String} text
     * @return {HTMLElement}
     */
    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) {
            e.className = cls;
        }
        if (text !== undefined && text !== null) {
            e.textContent = text;
        }
        return e;
    }

    /**
     * Format with a placeholder object.
     *
     * @param {String} s
     * @param {Object|String} a
     * @return {String}
     */
    function fmtstr(s, a) {
        if (typeof a !== 'object') {
            return String(s).replace('{$a}', a);
        }
        return String(s).replace(/\{\$a->(\w+)\}/g, function(m, k) {
            return a[k];
        });
    }

    /**
     * Display name of a group key.
     *
     * @param {String} key
     * @return {String}
     */
    function keyname(key) {
        return key === NONE ? str.notset : key;
    }

    /**
     * Number display.
     *
     * @param {Number} n
     * @return {String}
     */
    function num(n) {
        return Charts.fmt(n);
    }

    /**
     * Info icon with tooltip.
     *
     * @param {String} text
     * @param {Boolean} start align tooltip to the left
     * @return {HTMLElement}
     */
    function info(text, start) {
        var wrap = el('span', 'lbi-info');
        var b = el('button', 'lbi-info-btn');
        b.type = 'button';
        b.setAttribute('aria-label', text);
        b.innerHTML = icons.info;
        var t = el('span', 'lbi-tip' + (start ? ' lbi-tip-start' : ''), text);
        t.setAttribute('role', 'tooltip');
        wrap.appendChild(b);
        wrap.appendChild(t);
        return wrap;
    }

    /**
     * AJAX call.
     *
     * @param {String} mode
     * @param {Object} extra
     * @return {Promise}
     */
    function call(mode, extra) {
        var args = Filters.normalise(state);
        args.mode = mode;
        args.level = pos.level;
        args.dept = pos.dept || '';
        args.desig = pos.desig || '';
        Object.keys(extra || {}).forEach(function(k) {
            args[k] = extra[k];
        });
        return Ajax.call([{methodname: 'local_businessintelligence_get_lp_page', args: args}])[0].then(function(r) {
            return JSON.parse(r.data);
        });
    }

    /**
     * Show an error.
     *
     * @param {Object} e
     */
    function fail(e) {
        var err = root.querySelector('[data-region="error"]');
        err.hidden = false;
        err.textContent = str.loaderror + (e && e.message ? ' (' + e.message + ')' : '');
        root.classList.remove('is-loading');
    }

    // ------------------------------------------------------------------------------------------------------------
    // Overview (KPIs + charts).
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Skeletons for KPIs and charts.
     */
    function skeletonOverview() {
        root.classList.add('is-loading');
        root.querySelectorAll('[data-kpi]').forEach(function(card) {
            card.querySelector('[data-field="value"]').innerHTML = '<span class="lbi-skel lbi-skel-val"></span>';
            card.querySelector('[data-field="trend"]').innerHTML = '';
            card.querySelector('[data-field="spark"]').innerHTML = '<span class="lbi-skel lbi-skel-spark"></span>';
        });
        root.querySelectorAll('[data-chart]').forEach(function(box) {
            if (box._lbiObserver) {
                box._lbiObserver.disconnect();
                box._lbiObserver = null;
            }
            box.innerHTML = '<div class="lbi-skel lbi-skel-block"></div>';
        });
    }

    /**
     * Render KPIs and charts.
     *
     * @param {Object} data
     */
    function renderOverview(data) {
        overview = data;
        Object.keys(data.kpis).forEach(function(key) {
            var k = data.kpis[key];
            var card = root.querySelector('[data-kpi="' + key + '"]');
            if (!card) {
                return;
            }
            card.querySelector('[data-field="value"]').textContent = k.display;
            var t = card.querySelector('[data-field="trend"]');
            t.className = 'lbi-trend';
            t.innerHTML = '';
            if (k.trend !== null && k.trend !== undefined) {
                var dir = k.trend > 0 ? 'up' : (k.trend < 0 ? 'down' : 'flat');
                t.classList.add('is-' + dir);
                t.innerHTML = Charts.ICONS[dir];
                t.appendChild(el('span', '', (k.trend > 0 ? '+' : '') + Number(k.trend).toFixed(1) + '%'));
            }
            var sp = card.querySelector('[data-field="spark"]');
            sp.innerHTML = '';
            Charts.sparkline(sp, k.spark, k.trend !== null && k.trend < 0 ? 'destructive' : 'success');
        });
        var c = data.charts;
        var box = function(k) {
            var b = root.querySelector('[data-chart="' + k + '"]');
            b.innerHTML = '';
            return b;
        };
        Charts.funnel(box('funnel'), c.funnel);
        Charts.hbar(box('bypath'), c.bypath, {});
        root.querySelector('[data-region="refreshed"]').textContent = data.refreshed;
    }

    // ------------------------------------------------------------------------------------------------------------
    // Drill-down level.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Column definitions for the current level.
     *
     * @return {Array}
     */
    function columns() {
        if (pos.level === 'designation') {
            return [
                {k: 'name', label: str.lp_col_user, info: str.lp_col_user_info, text: true},
                {k: 'email', label: str.lp_col_email, info: str.lp_col_email_info, text: true},
                {k: 'branch', label: str.lp_col_branch, info: str.lp_col_branch_info, text: true},
                {k: 'manager', label: str.lp_col_manager, info: str.lp_col_manager_info, text: true},
                {k: 'enrolled', label: str.lpa_col_enrolled, info: str.lpa_col_enrolled_info},
                {k: 'completed', label: str.lpa_col_completed, info: str.lpa_col_completed_info},
                {k: 'inprogress', label: str.lpa_col_inprogress, info: str.lpa_col_inprogress_info},
                {k: 'notstarted', label: str.lpa_col_notstarted, info: str.lpa_col_notstarted_info},
                {k: 'action', label: str.lp_col_action, action: true}
            ];
        }
        return [
            {k: 'name', label: pos.level === 'org' ? str.lp_col_department : str.lp_col_designation, text: true,
                info: pos.level === 'org' ? str.lp_col_department : str.lp_col_designation},
            {k: 'users', label: str.lp_col_users, info: str.lpa_col_users_info},
            {k: 'paths', label: str.lpa_col_paths, info: str.lpa_col_paths_info, link: true},
            {k: 'enrolled', label: str.lpa_col_enrolled, info: str.lpa_col_enrolled_info},
            {k: 'completed', label: str.lpa_col_completed, info: str.lpa_col_completed_info},
            {k: 'inprogress', label: str.lpa_col_inprogress, info: str.lpa_col_inprogress_info},
            {k: 'notstarted', label: str.lpa_col_notstarted, info: str.lpa_col_notstarted_info},
            {k: 'pct', label: str.lpa_col_pct, info: str.lpa_col_pct_info},
            {k: 'action', label: str.lp_col_action, action: true}
        ];
    }

    /**
     * Breadcrumb bar.
     */
    function crumbs() {
        var nav = root.querySelector('[data-region="crumbs"]');
        nav.innerHTML = '';
        var home = el(pos.level === 'org' ? 'span' : 'button', 'lbi-lp-crumb' + (pos.level === 'org' ? ' is-home' : ''));
        home.innerHTML = icons.home;
        home.appendChild(el('span', '', str.lp_orgoverview));
        if (pos.level !== 'org') {
            home.type = 'button';
            home.addEventListener('click', function() {
                go({level: 'org', dept: '', desig: ''});
            });
        }
        nav.appendChild(home);
        var sep = function() {
            var s = el('span', 'lbi-lp-sep');
            s.innerHTML = icons.next;
            nav.appendChild(s);
        };
        if (pos.level !== 'org') {
            sep();
            var d = el(pos.level === 'department' ? 'span' : 'button',
                'lbi-lp-crumb' + (pos.level === 'department' ? ' is-current' : ''), keyname(pos.dept));
            if (pos.level === 'designation') {
                d.type = 'button';
                d.addEventListener('click', function() {
                    go({level: 'department', dept: pos.dept, desig: ''});
                });
            }
            nav.appendChild(d);
        }
        if (pos.level === 'designation') {
            sep();
            nav.appendChild(el('span', 'lbi-lp-crumb is-current', keyname(pos.desig)));
        }
        if (pos.level !== 'org') {
            var back = el('button', 'lbi-lp-back');
            back.type = 'button';
            back.innerHTML = icons.back;
            back.appendChild(el('span', '', pos.level === 'department' ? str.lp_backdept : str.lp_backdesig));
            back.addEventListener('click', function() {
                go(pos.level === 'department' ? {level: 'org', dept: '', desig: ''} :
                    {level: 'department', dept: pos.dept, desig: ''});
            });
            nav.appendChild(back);
        }
        var n = pos.level === 'org' ? 1 : (pos.level === 'department' ? 2 : 3);
        var lname = n === 1 ? str.lp_level_dept : (n === 2 ? str.lp_level_desig : str.lp_level_user);
        nav.appendChild(el('span', 'lbi-lp-levelnote', '· ' + fmtstr(str.lp_level, {n: n, name: lname})));
    }

    /**
     * Titles for the chart and table cards.
     *
     * @return {String}
     */
    function scopeTitle() {
        if (pos.level === 'org') {
            return str.lpa_bydept;
        }
        if (pos.level === 'department') {
            return fmtstr(str.lp_bydesig, keyname(pos.dept));
        }
        return fmtstr(str.lp_byusers, keyname(pos.dept) + ' · ' + keyname(pos.desig));
    }

    /**
     * Overview chart of the level.
     */
    function levelChart() {
        var box = root.querySelector('[data-region="levelchart"]');
        if (box._lbiObserver) {
            box._lbiObserver.disconnect();
            box._lbiObserver = null;
        }
        box.innerHTML = '';
        root.querySelector('[data-region="charttitle"]').textContent = fmtstr(str.lp_overview, scopeTitle());
        root.querySelector('[data-region="charttip"]').textContent = pos.level === 'designation' ?
            str.lpa_chart_users_info : str.lpa_chart_level_info;
        if (!rows.length) {
            box.style.height = '160px';
            Charts.empty(box);
            return;
        }
        // Bullet chart: completion % per row against the target zones, with the counts on the right.
        var data = rows.slice();
        if (pos.level === 'designation') {
            data = data.sort(function(a, b) {
                return b.enrolled - a.enrolled;
            }).slice(0, 15);
        }
        box.style.height = Math.max(190, data.length * 50 + 112) + 'px';
        Charts.bullet(box, {rows: data.map(function(r) {
            var users = pos.level === 'designation';
            return {
                label: r.name,
                value: r.pct,
                done: r.completed,
                total: r.enrolled,
                meta: users ? [] : [{label: str.lp_col_users, value: r.users}, {label: str.lpa_col_paths, value: r.paths}]
            };
        })}, {low: 40, goal: 70, pctlabel: str.lpa_col_pct, goallabel: str.lp_goal, donelabel: str.lpa_col_completed});
    }

    /**
     * Rows after search / branch / manager filters and sorting.
     *
     * @return {Array}
     */
    function visibleRows() {
        var q = view.q.toLowerCase();
        var out = rows.filter(function(r) {
            if (q && (r.name + ' ' + (r.email || '')).toLowerCase().indexOf(q) === -1) {
                return false;
            }
            if (view.branch && r.branch !== view.branch) {
                return false;
            }
            if (view.manager && r.manager !== view.manager) {
                return false;
            }
            return true;
        });
        if (view.sort) {
            var k = view.sort, d = view.dir;
            out.sort(function(a, b) {
                var va = a[k], vb = b[k];
                if (typeof va === 'string' || typeof vb === 'string') {
                    return d * String(va || '').localeCompare(String(vb || ''));
                }
                return d * ((va || 0) - (vb || 0));
            });
        }
        return out;
    }

    /**
     * Fill the branch / manager dropdowns for the user level.
     */
    function userFilters() {
        var isuser = pos.level === 'designation';
        root.querySelector('[data-region="branchwrap"]').hidden = !isuser;
        root.querySelector('[data-region="managerwrap"]').hidden = !isuser;
        if (!isuser) {
            return;
        }
        var fill = function(sel, all, key) {
            sel.innerHTML = '';
            var o = el('option', '', all);
            o.value = '';
            sel.appendChild(o);
            var seen = {};
            rows.forEach(function(r) {
                if (r[key] && !seen[r[key]]) {
                    seen[r[key]] = true;
                }
            });
            Object.keys(seen).sort().forEach(function(v) {
                var op = el('option', '', v);
                op.value = v;
                sel.appendChild(op);
            });
            sel.value = view[key];
        };
        fill(root.querySelector('[data-region="branchfilter"]'), str.lp_allbranch, 'branch');
        fill(root.querySelector('[data-region="managerfilter"]'), str.lp_allmanager, 'manager');
    }

    /**
     * Render the table (header, current page, footer).
     */
    function table() {
        var cols = columns();
        root.querySelector('[data-region="tabletitle"]').textContent = scopeTitle();
        root.querySelector('[data-region="tabletip"]').textContent = pos.level === 'designation' ?
            str.lpa_table_users_info : str.lpa_table_info;
        var thead = root.querySelector('[data-region="thead"]');
        thead.innerHTML = '';
        var hr = el('tr');
        cols.forEach(function(c, i) {
            var th = el('th', (c.text ? 'is-text' : '') + (c.action ? ' is-action' : '') + (view.sort === c.k ? ' is-sorted' : ''));
            th.scope = 'col';
            if (c.action) {
                th.textContent = c.label;
            } else {
                var b = el('button', 'lbi-lp-sortbtn');
                b.type = 'button';
                b.appendChild(el('span', '', c.label));
                var si = el('span', 'lbi-lp-sorticon');
                si.innerHTML = icons.sort;
                b.appendChild(si);
                b.addEventListener('click', function() {
                    view.dir = view.sort === c.k ? -view.dir : (c.text ? 1 : -1);
                    view.sort = c.k;
                    view.page = 1;
                    table();
                });
                th.appendChild(b);
                if (view.sort === c.k) {
                    th.setAttribute('aria-sort', view.dir > 0 ? 'ascending' : 'descending');
                }
                if (c.info) {
                    th.appendChild(info(c.info, i === 0));
                }
            }
            hr.appendChild(th);
        });
        thead.appendChild(hr);

        var all = visibleRows();
        var pages = Math.max(1, Math.ceil(all.length / view.per));
        view.page = Math.min(Math.max(1, view.page), pages);
        var from = (view.page - 1) * view.per;
        var slice = all.slice(from, from + view.per);
        var tbody = root.querySelector('[data-region="tbody"]');
        tbody.innerHTML = '';
        if (!slice.length) {
            var tr0 = el('tr');
            var td0 = el('td', 'lbi-lp-empty', str.nodata);
            td0.colSpan = cols.length;
            tr0.appendChild(td0);
            tbody.appendChild(tr0);
        }
        slice.forEach(function(r) {
            var tr = el('tr');
            cols.forEach(function(c) {
                var td = el('td', c.text ? 'is-text' : (c.action ? 'is-action' : ''));
                if (c.action) {
                    var b = el('button', 'lbi-btn lbi-btn-primary lbi-btn-sm',
                        pos.level === 'designation' ? str.lp_viewdetail : str.lp_viewmore);
                    b.type = 'button';
                    b.addEventListener('click', function() {
                        if (pos.level === 'org') {
                            go({level: 'department', dept: r.key, desig: ''});
                        } else if (pos.level === 'department') {
                            go({level: 'designation', dept: pos.dept, desig: r.key});
                        } else {
                            userModal(r);
                        }
                    });
                    td.appendChild(b);
                } else if (c.link) {
                    var a = el('button', 'lbi-lp-countlink', num(r[c.k]));
                    a.type = 'button';
                    a.addEventListener('click', function() {
                        pathsModal(r);
                    });
                    td.appendChild(a);
                } else if (c.k === 'name') {
                    td.appendChild(el('span', 'lbi-lp-name', r.name));
                } else if (c.text) {
                    td.textContent = r[c.k] || '—';
                } else {
                    td.textContent = num(r[c.k]);
                }
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        root.querySelector('[data-region="showing"]').textContent = fmtstr(str.lp_showing, {
            from: all.length ? from + 1 : 0, to: Math.min(from + view.per, all.length), total: all.length});
        root.querySelector('[data-region="pageinfo"]').textContent = view.page + ' / ' + pages;
        root.querySelector('[data-action="prev"]').disabled = view.page <= 1;
        root.querySelector('[data-action="next"]').disabled = view.page >= pages;
    }

    /**
     * Skeleton rows for the table.
     */
    function skeletonTable() {
        var tbody = root.querySelector('[data-region="tbody"]');
        tbody.innerHTML = '';
        for (var i = 0; i < 5; i++) {
            var tr = el('tr');
            var td = el('td');
            td.colSpan = 9;
            td.innerHTML = '<span class="lbi-skel lbi-skel-row"></span>';
            tr.appendChild(td);
            tbody.appendChild(tr);
        }
        var box = root.querySelector('[data-region="levelchart"]');
        if (box._lbiObserver) {
            box._lbiObserver.disconnect();
            box._lbiObserver = null;
        }
        box.innerHTML = '<div class="lbi-skel lbi-skel-block"></div>';
    }

    /**
     * Render everything for the current level.
     *
     * @param {Array} data rows
     */
    function setRows(data) {
        rows = data || [];
        root.classList.remove('is-loading');
        // KPIs and overview charts only on the first (department) screen.
        root.querySelector('[data-region="overview"]').hidden = pos.level !== 'org';
        crumbs();
        levelChart();
        userFilters();
        table();
    }

    /**
     * Update the URL and links.
     *
     * @param {Boolean} replace
     */
    function syncUrl(replace) {
        var q = Filters.query(state, {report: 'learningpaths', level: pos.level === 'org' ? '' : pos.level, dept: pos.dept,
            desig: pos.desig});
        if (window.history && window.history.pushState) {
            window.history[replace ? 'replaceState' : 'pushState']({lbi: pos}, '', window.location.pathname + '?' + q);
        }
    }

    /**
     * Go to a drill level.
     *
     * @param {Object} p
     * @param {Boolean} nohistory
     */
    function go(p, nohistory) {
        pos = p;
        view = {sort: null, dir: -1, q: '', page: 1, per: view.per, branch: '', manager: ''};
        root.querySelector('[data-region="search"]').value = '';
        if (!nohistory) {
            syncUrl(false);
        }
        root.querySelector('[data-region="overview"]').hidden = pos.level !== 'org';
        crumbs();
        skeletonTable();
        var mine = ++token;
        call('level').then(function(d) {
            if (mine === token) {
                setRows(d.rows);
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
            return null;
        }).catch(fail);
    }

    /**
     * Full reload after a filter change.
     */
    function loadPage() {
        var mine = ++token;
        root.querySelector('[data-region="error"]').hidden = true;
        skeletonOverview();
        skeletonTable();
        crumbs();
        syncUrl(true);
        call('page').then(function(d) {
            if (mine !== token) {
                return null;
            }
            renderOverview(d);
            setRows(d.rows);
            return null;
        }).catch(fail);
    }

    // ------------------------------------------------------------------------------------------------------------
    // Popups.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Open the modal with content.
     *
     * @param {HTMLElement} content
     * @param {String} size
     */
    function openModal(content, size) {
        var m = root.querySelector('[data-region="modal"]');
        var body = m.querySelector('[data-region="modalbody"]');
        body.innerHTML = '';
        body.appendChild(content);
        m.querySelector('.lbi-modal-dialog').className = 'lbi-modal-dialog' + (size ? ' is-' + size : '');
        lastFocus = document.activeElement;
        m.hidden = false;
        document.body.classList.add('lbi-modal-open');
        m.querySelector('.lbi-modal-close').focus();
    }

    /**
     * Close the modal.
     */
    function closeModal() {
        var m = root.querySelector('[data-region="modal"]');
        if (m.hidden) {
            return;
        }
        m.hidden = true;
        document.body.classList.remove('lbi-modal-open');
        if (lastFocus) {
            lastFocus.focus();
        }
    }

    /**
     * Simple table element.
     *
     * @param {Array} heads [{label, info}]
     * @param {Array} body rows of cells (string or HTMLElement)
     * @return {HTMLElement}
     */
    function simpleTable(heads, body) {
        var wrap = el('div', 'lbi-modal-tablewrap');
        var t = el('table', 'lbi-modal-table');
        var thead = el('thead');
        var tr = el('tr');
        heads.forEach(function(h) {
            var th = el('th', '', h.label);
            th.scope = 'col';
            if (h.info) {
                th.appendChild(info(h.info));
            }
            tr.appendChild(th);
        });
        thead.appendChild(tr);
        t.appendChild(thead);
        var tb = el('tbody');
        if (!body.length) {
            var r0 = el('tr');
            var c0 = el('td', 'lbi-lp-empty', str.nodata);
            c0.colSpan = heads.length;
            r0.appendChild(c0);
            tb.appendChild(r0);
        }
        body.forEach(function(row) {
            var r = el('tr');
            row.forEach(function(cell) {
                var td = el('td');
                if (cell instanceof HTMLElement) {
                    td.appendChild(cell);
                } else {
                    td.textContent = cell;
                }
                r.appendChild(td);
            });
            tb.appendChild(r);
        });
        t.appendChild(tb);
        wrap.appendChild(t);
        return wrap;
    }

    /**
     * Loading placeholder for popups.
     *
     * @return {HTMLElement}
     */
    function modalSkeleton() {
        var d = el('div', 'lbi-modal-loading');
        for (var i = 0; i < 6; i++) {
            d.appendChild(el('span', 'lbi-skel lbi-skel-row'));
        }
        return d;
    }

    /**
     * Learning-path-count popup.
     *
     * @param {Object} r row
     */
    function pathsModal(r) {
        var content = el('div');
        var head = el('div', 'lbi-modal-head');
        head.appendChild(el('h3', 'lbi-modal-title', fmtstr(str.lpa_pathspopup, r.name)));
        head.appendChild(info(str.lpa_pathspopup_info, true));
        content.appendChild(head);
        var holder = el('div');
        holder.appendChild(modalSkeleton());
        content.appendChild(holder);
        openModal(content, 'md');
        var extra = pos.level === 'org' ? {dept: r.key, desig: ''} : {dept: pos.dept, desig: r.key};
        call('paths', extra).then(function(d) {
            holder.innerHTML = '';
            holder.appendChild(simpleTable(
                [{label: str.lpa_col_path}, {label: str.lpa_col_enrolled}, {label: str.lpa_col_inprogress},
                    {label: str.lpa_col_completed}],
                d.paths.map(function(c) {
                    return [c.name, num(c.enrolled), num(c.inprogress), num(c.completed)];
                })
            ));
            return null;
        }).catch(function(e) {
            holder.textContent = str.loaderror + (e && e.message ? ' (' + e.message + ')' : '');
        });
    }

    /**
     * Small progress bar for the learner popup.
     *
     * @param {Number} pct
     * @return {HTMLElement}
     */
    function progressBar(pct) {
        var w = el('span', 'lbi-lpa-progress');
        var track = el('span', 'lbi-lpa-progress-track');
        var fill = el('span', 'lbi-lpa-progress-fill');
        fill.style.width = '0%';
        track.appendChild(fill);
        w.appendChild(track);
        w.appendChild(el('span', 'lbi-lpa-progress-val', pct + '%'));
        window.requestAnimationFrame(function() {
            window.requestAnimationFrame(function() {
                fill.style.width = Math.max(0, Math.min(100, pct)) + '%';
            });
        });
        return w;
    }

    /**
     * Learner detail popup.
     *
     * @param {Object} r row
     */
    function userModal(r) {
        var content = el('div');
        var head = el('div', 'lbi-modal-head lbi-modal-head-col');
        head.appendChild(el('h3', 'lbi-modal-title', fmtstr(str.lpa_userpopup, r.name)));
        content.appendChild(head);
        var holder = el('div');
        holder.appendChild(modalSkeleton());
        content.appendChild(holder);
        openModal(content, 'lg');
        call('user', {userid: parseInt(r.key, 10)}).then(function(d) {
            var u = d.user;
            holder.innerHTML = '';
            var prof = el('div', 'lbi-profile');
            var left = el('div', 'lbi-profile-left');
            left.appendChild(el('span', 'lbi-avatar', u.initials));
            var who = el('div');
            who.appendChild(el('p', 'lbi-profile-name', u.name));
            who.appendChild(el('p', 'lbi-profile-meta', u.meta));
            left.appendChild(who);
            var right = el('div', 'lbi-profile-right');
            right.appendChild(el('p', '', u.email));
            if (u.manager) {
                right.appendChild(el('p', '', fmtstr(str.lp_reportingmanager, u.manager)));
            }
            prof.appendChild(left);
            prof.appendChild(right);
            holder.appendChild(prof);

            var stats = el('div', 'lbi-modal-stats');
            [[str.lpa_col_enrolled, num(u.enrolled), 'st-paths', 'primary'],
                [str.lpa_col_completed, num(u.completed), 'st-completed', 'success'],
                [str.lpa_col_inprogress, num(u.inprogress), 'st-inprogress', 'accent'],
                [str.lpa_col_notstarted, num(u.notstarted), 'st-notstarted', 'muted']].forEach(function(s) {
                var b = el('div', 'lbi-modal-stat has-icon');
                var ic = el('span', 'lbi-modal-stat-icon is-' + s[3]);
                ic.innerHTML = icons[s[2]] || '';
                b.appendChild(ic);
                var tx = el('div', 'lbi-modal-stat-text');
                tx.appendChild(el('p', 'lbi-modal-stat-label', s[0]));
                tx.appendChild(el('p', 'lbi-modal-stat-value', s[1]));
                b.appendChild(tx);
                stats.appendChild(b);
            });
            holder.appendChild(stats);

            holder.appendChild(simpleTable(
                [{label: str.lpa_col_pathname}, {label: str.lpa_col_coursescount}, {label: str.lpa_col_progress},
                    {label: str.lp_col_status, info: str.lpa_col_status_info}],
                u.paths.map(function(c) {
                    return [c.name, num(c.courses), progressBar(c.progress),
                        el('span', 'lbi-badge-status is-' + c.status, c.statuslabel)];
                })
            ));
            return null;
        }).catch(function(e) {
            holder.textContent = str.loaderror + (e && e.message ? ' (' + e.message + ')' : '');
        });
    }

    // ------------------------------------------------------------------------------------------------------------
    // Exports.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Current table as CSV rows.
     *
     * @return {Array}
     */
    function tableCsv() {
        var cols = columns().filter(function(c) {
            return !c.action;
        });
        var out = [cols.map(function(c) {
            return c.label;
        })];
        visibleRows().forEach(function(r) {
            out.push(cols.map(function(c) {
                return c.k === 'name' ? r.name : r[c.k];
            }));
        });
        return out;
    }

    /**
     * Download helper.
     *
     * @param {Boolean} full include KPIs
     */
    function csv(full) {
        var out = [];
        Filters.summary(root).forEach(function(r) {
            out.push(r);
        });
        out.push([]);
        if (full && overview) {
            out.push([str.report_learningpaths]);
            root.querySelectorAll('[data-kpi]').forEach(function(card) {
                var k = overview.kpis[card.getAttribute('data-kpi')];
                out.push([card.querySelector('.lbi-kpi-label').textContent.trim(), k.display,
                    k.trend === null ? '' : (k.trend > 0 ? '+' : '') + k.trend + '%']);
            });
            out.push([]);
        }
        out.push([scopeTitle()]);
        tableCsv().forEach(function(r) {
            out.push(r);
        });
        Filters.downloadCsv('learning-paths-' + pos.level + '.csv', out);
    }

    // ------------------------------------------------------------------------------------------------------------
    // Init.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Wire events.
     */
    function bind() {
        Filters.bind(root, state, function() {
            pos = {level: 'org', dept: '', desig: ''};
            loadPage();
        });
        root.querySelectorAll('[data-action="pdf"]').forEach(function(b) {
            b.addEventListener('click', function() {
                window.print();
            });
        });
        root.querySelector('[data-action="pagecsv"]').addEventListener('click', function() {
            csv(true);
        });
        root.querySelector('[data-action="csv"]').addEventListener('click', function() {
            csv(false);
        });
        var search = root.querySelector('[data-region="search"]');
        var timer = null;
        search.addEventListener('input', function() {
            window.clearTimeout(timer);
            timer = window.setTimeout(function() {
                view.q = search.value.trim();
                view.page = 1;
                table();
            }, 150);
        });
        root.querySelector('[data-region="perpage"]').addEventListener('change', function(e) {
            view.per = parseInt(e.target.value, 10) || 10;
            view.page = 1;
            table();
        });
        root.querySelector('[data-region="branchfilter"]').addEventListener('change', function(e) {
            view.branch = e.target.value;
            view.page = 1;
            table();
        });
        root.querySelector('[data-region="managerfilter"]').addEventListener('change', function(e) {
            view.manager = e.target.value;
            view.page = 1;
            table();
        });
        root.querySelector('[data-action="prev"]').addEventListener('click', function() {
            view.page--;
            table();
        });
        root.querySelector('[data-action="next"]').addEventListener('click', function() {
            view.page++;
            table();
        });
        root.querySelectorAll('[data-action="closemodal"]').forEach(function(b) {
            b.addEventListener('click', closeModal);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.lbi) {
                go(e.state.lbi, true);
            }
        });
    }

    return {
        /**
         * Initialise.
         *
         * @param {Object} initial filters
         * @param {Object} start {level, dept, desig}
         */
        init: function(initial, start) {
            root = document.querySelector('[data-region="lbi-lppage"]');
            if (!root) {
                return;
            }
            state = Filters.normalise(initial);
            pos = {
                level: ['org', 'department', 'designation'].indexOf(start.level) >= 0 ? start.level : 'org',
                dept: start.dept || '',
                desig: start.desig || ''
            };
            root.querySelectorAll('[data-region="icons"] [data-icon]').forEach(function(i) {
                icons[i.getAttribute('data-icon')] = i.innerHTML;
            });
            Str.get_strings(STRINGS.map(function(k) {
                return {key: k, component: 'local_businessintelligence'};
            })).then(function(s) {
                STRINGS.forEach(function(k, i) {
                    str[k] = s[i];
                });
                Charts.setStrings(str);
                bind();
                loadPage();
                return null;
            }).catch(fail);
        }
    };
});

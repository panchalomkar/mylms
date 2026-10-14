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
 * Drill-down page: Organization -> Department -> Designation -> Learner, all over AJAX.
 *
 * @module     local_businessintelligence/drilldown
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/str', 'local_businessintelligence/filters'], function(Ajax, Str, Filters) {
    'use strict';

    var root, report, state, pos, token = 0, last = null, str = {}, sort = {col: -1, dir: -1};
    var CHEVRON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
        'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lbi-icon" aria-hidden="true">' +
        '<path d="m9 18 6-6-6-6"></path></svg>';

    /**
     * Text cell helper.
     *
     * @param {String} tag
     * @param {String} text
     * @param {String} cls
     * @return {HTMLElement}
     */
    function cell(tag, text, cls) {
        var c = document.createElement(tag);
        if (cls) {
            c.className = cls;
        }
        c.textContent = text;
        return c;
    }

    /**
     * Breadcrumb trail.
     */
    function crumbs() {
        var nav = root.querySelector('[data-region="crumbs"]');
        var orgicon = nav.querySelector('.lbi-icon');
        var orgsvg = orgicon ? orgicon.outerHTML : '';
        nav.innerHTML = '';
        var trail = [{level: 'org', label: str.level_org, html: orgsvg}];
        if (pos.level !== 'org') {
            trail.push({level: 'department', label: pos.dept === '__none__' ? str.notset : pos.dept, sub: str.level_department});
        }
        if (pos.level === 'designation') {
            trail.push({level: 'designation', label: pos.desig === '__none__' ? str.notset : pos.desig,
                sub: str.level_designation});
        }
        trail.forEach(function(t, i) {
            if (i > 0) {
                var sep = document.createElement('span');
                sep.className = 'lbi-crumb-sep';
                sep.innerHTML = CHEVRON;
                nav.appendChild(sep);
            }
            var current = i === trail.length - 1;
            var b = document.createElement(current ? 'span' : 'button');
            b.className = 'lbi-crumb' + (current ? ' is-current' : '');
            if (!current) {
                b.type = 'button';
                b.addEventListener('click', function() {
                    go({level: t.level, dept: t.level === 'org' ? '' : pos.dept, desig: ''});
                });
            } else {
                b.setAttribute('aria-current', 'page');
            }
            if (t.html) {
                b.innerHTML = t.html;
            }
            if (t.sub) {
                b.appendChild(cell('span', t.sub, 'lbi-crumb-level'));
            }
            b.appendChild(cell('span', t.label));
            nav.appendChild(b);
        });
    }

    /**
     * Render the table for the current payload.
     */
    function table() {
        var data = last;
        var thead = root.querySelector('[data-region="thead"]');
        var tbody = root.querySelector('[data-region="tbody"]');
        var tableEl = root.querySelector('[data-region="table"]');
        var namelabel = pos.level === 'org' ? str.level_department :
            (pos.level === 'department' ? str.level_designation : (report === 'trainer' ? str.col_trainers : str.level_learner));
        var heads = [namelabel];
        if (pos.level !== 'designation') {
            heads.push(report === 'trainer' ? str.col_trainers : str.col_learners);
        }
        data.columns.forEach(function(c) {
            heads.push(c.label);
        });
        thead.innerHTML = '';
        var hr = document.createElement('tr');
        heads.forEach(function(h, i) {
            var th = cell('th', h + (sort.col === i ? (sort.dir > 0 ? ' ▲' : ' ▼') : ''));
            th.scope = 'col';
            if (sort.col === i) {
                th.classList.add('is-sorted');
                th.setAttribute('aria-sort', sort.dir > 0 ? 'ascending' : 'descending');
            }
            th.addEventListener('click', function() {
                sort = {col: i, dir: sort.col === i ? -sort.dir : (i === 0 ? 1 : -1)};
                table();
            });
            hr.appendChild(th);
        });
        thead.appendChild(hr);

        var q = (root.querySelector('[data-region="search"]').value || '').toLowerCase();
        var rows = data.rows.filter(function(r) {
            return !q || r.label.toLowerCase().indexOf(q) !== -1 || (r.sub || '').toLowerCase().indexOf(q) !== -1;
        });
        var offset = pos.level !== 'designation' ? 2 : 1;
        if (sort.col >= 0) {
            rows = rows.slice().sort(function(a, b) {
                var va, vb;
                if (sort.col === 0) {
                    return sort.dir * a.label.localeCompare(b.label);
                } else if (sort.col === 1 && offset === 2) {
                    va = a.members;
                    vb = b.members;
                } else {
                    va = a.cells[sort.col - offset].value;
                    vb = b.cells[sort.col - offset].value;
                }
                va = va === null ? -Infinity : va;
                vb = vb === null ? -Infinity : vb;
                return sort.dir * (va - vb);
            });
        }

        tbody.innerHTML = '';
        if (!rows.length) {
            var tr0 = document.createElement('tr');
            var td0 = cell('td', str.nodata);
            td0.colSpan = heads.length;
            tr0.appendChild(td0);
            tbody.appendChild(tr0);
        }
        rows.forEach(function(r) {
            var tr = document.createElement('tr');
            var name = document.createElement('td');
            var wrap = document.createElement('span');
            wrap.className = 'lbi-rowname';
            if (r.url) {
                var a = document.createElement('a');
                a.href = r.url;
                a.textContent = r.label;
                var holder = document.createElement('span');
                holder.appendChild(a);
                holder.appendChild(cell('span', r.sub || '', 'lbi-rowsub'));
                wrap.appendChild(holder);
                if (!r.hasdata) {
                    tr.classList.add('is-empty');
                }
            } else {
                wrap.textContent = r.label;
                if (r.drill) {
                    wrap.insertAdjacentHTML('beforeend', CHEVRON);
                }
            }
            name.appendChild(wrap);
            tr.appendChild(name);
            if (pos.level !== 'designation') {
                tr.appendChild(cell('td', Number(r.members).toLocaleString('en-US')));
            }
            r.cells.forEach(function(c) {
                tr.appendChild(cell('td', c.display));
            });
            if (r.drill) {
                tr.className = 'is-drill';
                tr.tabIndex = 0;
                var open = function() {
                    if (pos.level === 'org') {
                        go({level: 'department', dept: r.key, desig: ''});
                    } else {
                        go({level: 'designation', dept: pos.dept, desig: r.key});
                    }
                };
                tr.addEventListener('click', open);
                tr.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        open();
                    }
                });
            }
            tbody.appendChild(tr);
        });

        var old = tableEl.querySelector('tfoot');
        if (old) {
            old.remove();
        }
        var tfoot = document.createElement('tfoot');
        var fr = document.createElement('tr');
        fr.appendChild(cell('td', str.total));
        if (pos.level !== 'designation') {
            fr.appendChild(cell('td', Number(data.total.members).toLocaleString('en-US')));
        }
        data.total.cells.forEach(function(c) {
            fr.appendChild(cell('td', c.display));
        });
        tfoot.appendChild(fr);
        tableEl.appendChild(tfoot);

        root.querySelector('[data-region="searchwrap"]').hidden = pos.level !== 'designation';
        var note = root.querySelector('[data-region="note"]');
        note.hidden = !data.truncated;
        note.textContent = data.truncated ? str.truncated : '';
    }

    /**
     * Navigate to a drill level.
     *
     * @param {Object} p {level, dept, desig}
     * @param {Boolean} replace use replaceState instead of pushState
     */
    function go(p, replace) {
        pos = p;
        sort = {col: -1, dir: -1};
        var search = root.querySelector('[data-region="search"]');
        search.value = '';
        var url = window.location.pathname + '?' + Filters.query(state, {report: report, level: pos.level === 'org' ? '' : pos.level,
            dept: pos.dept, desig: pos.desig});
        if (window.history && window.history.pushState) {
            window.history[replace ? 'replaceState' : 'pushState'](pos, '', url);
        }
        load();
    }

    /**
     * Fetch rows.
     */
    function load() {
        var mine = ++token;
        var err = root.querySelector('[data-region="error"]');
        err.hidden = true;
        root.classList.add('is-loading');
        crumbs();
        var tbody = root.querySelector('[data-region="tbody"]');
        tbody.innerHTML = '';
        for (var i = 0; i < 5; i++) {
            var tr = document.createElement('tr');
            var td = document.createElement('td');
            td.colSpan = 8;
            td.innerHTML = '<span class="lbi-skel lbi-skel-row"></span>';
            tr.appendChild(td);
            tbody.appendChild(tr);
        }
        root.querySelectorAll('[data-stat]').forEach(function(el) {
            el.innerHTML = '<span class="lbi-skel lbi-skel-stat"></span>';
        });
        var back = root.querySelector('[data-region="backlink"]');
        if (back) {
            back.href = back.href.split('?')[0] + '?' + Filters.query(state);
        }
        var args = Filters.normalise(state);
        args.report = report;
        args.level = pos.level;
        args.dept = pos.dept || '';
        args.desig = pos.desig || '';
        Ajax.call([{methodname: 'local_businessintelligence_get_drilldown', args: args}])[0].then(function(resp) {
            if (mine !== token) {
                return null;
            }
            last = JSON.parse(resp.data);
            root.classList.remove('is-loading');
            Object.keys(last.stats || {}).forEach(function(k) {
                var el = root.querySelector('[data-stat="' + k + '"]');
                if (el) {
                    el.textContent = last.stats[k];
                }
            });
            table();
            return null;
        }).catch(function(e) {
            if (mine !== token) {
                return;
            }
            root.classList.remove('is-loading');
            err.hidden = false;
            if (e && e.message) {
                err.textContent = str.loaderror + ' (' + e.message + ')';
            }
            tbody.innerHTML = '';
        });
    }

    /**
     * CSV of the current table.
     */
    function exportCsv() {
        if (!last) {
            return;
        }
        var rows = [];
        Filters.summary(root).forEach(function(r) {
            rows.push(r);
        });
        rows.push([]);
        var head = [];
        root.querySelectorAll('[data-region="thead"] th').forEach(function(th) {
            head.push(th.textContent.replace(/[▲▼]/g, '').trim());
        });
        if (pos.level === 'designation') {
            head.splice(1, 0, 'Email');
        }
        rows.push(head);
        last.rows.forEach(function(r) {
            var line = [r.label];
            if (pos.level === 'designation') {
                line.push(r.sub || '');
            } else {
                line.push(r.members);
            }
            r.cells.forEach(function(c) {
                line.push(c.display);
            });
            rows.push(line);
        });
        Filters.downloadCsv(report + '-' + pos.level + '.csv', rows);
    }

    return {
        /**
         * Initialise.
         *
         * @param {String} reportkey
         * @param {Object} initial filters
         * @param {Object} start {level, dept, desig}
         */
        init: function(reportkey, initial, start) {
            root = document.querySelector('[data-region="lbi-drilldown"]');
            if (!root) {
                return;
            }
            report = reportkey;
            state = Filters.normalise(initial);
            pos = {
                level: ['org', 'department', 'designation'].indexOf(start.level) >= 0 ? start.level : 'org',
                dept: start.dept || '',
                desig: start.desig || ''
            };
            var keys = ['level_org', 'level_department', 'level_designation', 'level_learner', 'col_learners', 'col_trainers',
                'total', 'notset', 'nodata', 'loaderror', 'truncated'];
            Str.get_strings(keys.map(function(k) {
                return {key: k, component: 'local_businessintelligence'};
            })).then(function(s) {
                keys.forEach(function(k, i) {
                    str[k] = s[i];
                });
                Filters.bind(root, state, function() {
                    go({level: 'org', dept: '', desig: ''});
                });
                root.querySelector('[data-region="reportswitch"]').addEventListener('change', function(e) {
                    window.location.href = window.location.pathname + '?' + Filters.query(state, {report: e.target.value});
                });
                root.querySelector('[data-region="search"]').addEventListener('input', function() {
                    if (last) {
                        table();
                    }
                });
                root.querySelector('[data-action="csv"]').addEventListener('click', exportCsv);
                window.addEventListener('popstate', function(e) {
                    if (e.state && e.state.level) {
                        pos = e.state;
                        load();
                    }
                });
                go(pos, true);
                return null;
            }).catch(function() {
                return null;
            });
        }
    };
});

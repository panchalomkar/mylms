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
 * Generic "View Analysis" page: KPIs, overview charts, multi-level drill-down table with filters, chips,
 * banded headers, count-link popups and detail popups. The server describes every level and popup.
 *
 * @module     local_businessintelligence/analysis
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/str', 'local_businessintelligence/charts', 'local_businessintelligence/filters'],
function(Ajax, Str, Charts, Filters) {
    'use strict';

    var root, report, state, path = [], level = null, overview = null, token = 0, str = {}, icons = {};
    var view = {sort: null, dir: -1, q: '', page: 1, per: 10, f: {}, chip: ''};
    var lastFocus = null;

    var STRINGS = ['chart_alarming', 'chart_moderate', 'chart_ontarget', 'nodata', 'value', 'users', 'outof5', 'norating',
        'loaderror', 'notset', 'lp_orgoverview', 'an_level', 'lp_showing', 'lp_lastrefreshed', 'an_all', 'an_sortby',
        'an_allfilter'];

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
     * @param {Boolean} start
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
        args.report = report;
        args.mode = mode;
        args.path = JSON.stringify(path);
        Object.keys(extra || {}).forEach(function(k) {
            args[k] = extra[k];
        });
        return Ajax.call([{methodname: 'local_businessintelligence_get_analysis', args: args}])[0].then(function(r) {
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

    /**
     * Render a chart into a box from a spec.
     *
     * @param {HTMLElement} box
     * @param {Object} c {chart, data, opts}
     */
    function drawChart(box, c) {
        var o = c.opts || {};
        switch (c.chart) {
            case 'bullet':
                Charts.bullet(box, c.data, o);
                break;
            case 'hbar':
                Charts.hbar(box, c.data, o);
                break;
            case 'vbar':
                Charts.vbar(box, c.data, o);
                break;
            case 'donut':
                Charts.donut(box, c.data, o);
                break;
            case 'funnel':
                Charts.funnel(box, c.data, o);
                break;
            case 'line':
                Charts.line(box, c.data, o);
                break;
            default:
                Charts.empty(box);
        }
    }

    /**
     * Release the observers of chart boxes under a node.
     *
     * @param {HTMLElement} node
     */
    function releaseCharts(node) {
        node.querySelectorAll('.lbi-chart').forEach(function(b) {
            if (b._lbiObserver) {
                b._lbiObserver.disconnect();
                b._lbiObserver = null;
            }
        });
    }

    /**
     * Card with a title, info icon and chart.
     *
     * @param {Object} c chart spec {title, info, chart, data, opts, height, sorts}
     * @return {HTMLElement}
     */
    function chartCard(c) {
        var card = el('div', 'lbi-card lbi-lp-card');
        var head = el('div', 'lbi-lp-cardhead');
        head.appendChild(el('h3', 'lbi-lp-cardtitle', c.title));
        if (c.info) {
            head.appendChild(info(c.info, true));
        }
        card.appendChild(head);
        var box = el('div', 'lbi-chart');
        box.style.height = (c.height || 280) + 'px';
        if (c.sorts && c.sorts.length && c.data && c.data.categories) {
            var bar = el('div', 'lbi-an-sortbar lbi-noprint');
            bar.appendChild(el('span', 'lbi-an-sortlabel', str.an_sortby));
            c.sorts.forEach(function(s, i) {
                var b = el('button', 'lbi-an-chip' + (i === 0 ? ' is-active' : ''), s.label);
                b.type = 'button';
                b.addEventListener('click', function() {
                    bar.querySelectorAll('.lbi-an-chip').forEach(function(x) {
                        x.classList.remove('is-active');
                    });
                    b.classList.add('is-active');
                    sortStacked(c, s.series);
                    releaseCharts(card);
                    box.innerHTML = '';
                    drawChart(box, c);
                });
                bar.appendChild(b);
            });
            card.appendChild(bar);
            sortStacked(c, c.sorts[0].series);
        }
        card.appendChild(box);
        // Draw after the card is in the DOM.
        window.requestAnimationFrame(function() {
            drawChart(box, c);
        });
        return card;
    }

    /**
     * Sort a stacked bar data set by one series (descending) or by the total (-1).
     *
     * @param {Object} c
     * @param {Number} idx
     */
    function sortStacked(c, idx) {
        var d = c.data;
        var order = d.categories.map(function(cat, i) {
            var v = idx < 0 ? d.series.reduce(function(s, se) {
                return s + (Number(se.values[i]) || 0);
            }, 0) : (Number(d.series[idx].values[i]) || 0);
            return {i: i, v: v};
        }).sort(function(a, b) {
            return b.v - a.v;
        });
        d.categories = order.map(function(o) {
            return d.categories[o.i];
        });
        d.series.forEach(function(se) {
            var vals = se.values.slice();
            se.values = order.map(function(o) {
                return vals[o.i];
            });
        });
    }

    /**
     * Table cell content from a popup / block cell spec.
     *
     * @param {*} cell string | number | {t, badge, indent, bold, tone, sub}
     * @return {HTMLElement|String}
     */
    function cellNode(cell) {
        if (cell === null || cell === undefined || cell === '') {
            return '—';
        }
        if (typeof cell !== 'object') {
            return String(cell);
        }
        var n;
        if (cell.badge) {
            n = el('span', 'lbi-badge-status is-' + cell.badge, cell.t);
        } else if (cell.audio) {
            n = el('a', 'lbi-an-audio', '');
            n.href = cell.audio;
            n.target = '_blank';
            n.rel = 'noopener';
            n.innerHTML = icons.play || '';
            n.appendChild(el('span', '', cell.t || ''));
        } else {
            n = el('span', (cell.bold ? 'lbi-an-strong' : '') + (cell.tone ? ' lbi-an-tone-' + cell.tone : ''), cell.t);
            if (cell.sub) {
                var w = el('span', 'lbi-an-namewrap');
                w.appendChild(n);
                w.appendChild(el('span', 'lbi-an-sub', cell.sub));
                n = w;
            }
        }
        if (cell.indent) {
            n.style.paddingLeft = (cell.indent * 16) + 'px';
            n.style.display = 'inline-block';
        }
        return n;
    }

    /**
     * Simple table (popups and blocks).
     *
     * @param {Array} heads [{label, info}]
     * @param {Array} rows arrays of cells or {c: [cells], f: {field: value}}
     * @param {String} cls
     * @return {HTMLElement}
     */
    function simpleTable(heads, rows, cls) {
        var wrap = el('div', 'lbi-modal-tablewrap' + (cls ? ' ' + cls : ''));
        var t = el('table', 'lbi-modal-table');
        var thead = el('thead');
        var tr = el('tr');
        heads.forEach(function(h) {
            var th = el('th', h.cls || '', h.label);
            th.scope = 'col';
            if (h.info) {
                th.appendChild(info(h.info));
            }
            tr.appendChild(th);
        });
        thead.appendChild(tr);
        t.appendChild(thead);
        var tb = el('tbody');
        var fill = function(list) {
            tb.innerHTML = '';
            if (!list.length) {
                var r0 = el('tr');
                var c0 = el('td', 'lbi-lp-empty', str.nodata);
                c0.colSpan = heads.length;
                r0.appendChild(c0);
                tb.appendChild(r0);
            }
            list.forEach(function(row) {
                var cells = Array.isArray(row) ? row : row.c;
                var r = el('tr');
                cells.forEach(function(cell, i) {
                    var td = el('td', heads[i] && heads[i].cls ? heads[i].cls : '');
                    var n = cellNode(cell);
                    if (n instanceof HTMLElement) {
                        td.appendChild(n);
                    } else {
                        td.textContent = n;
                    }
                    r.appendChild(td);
                });
                tb.appendChild(r);
            });
        };
        fill(rows);
        t.appendChild(tb);
        wrap.appendChild(t);
        wrap._fill = fill;
        return wrap;
    }

    /**
     * Chip bar filtering a list by a field.
     *
     * @param {Object} chips {field, items: [{value, label}]}
     * @param {Array} list rows with f / plain objects
     * @param {Function} getter (row) => value
     * @param {Function} onpick (value)
     * @param {String} active
     * @return {HTMLElement}
     */
    function chipBar(chips, list, getter, onpick, active) {
        var bar = el('div', 'lbi-an-chipbar');
        var items = [{value: '', label: chips.alllabel || str.an_all}].concat(chips.items);
        items.forEach(function(it) {
            var count = it.value === '' ? list.length : list.filter(function(r) {
                return getter(r) === it.value;
            }).length;
            var b = el('button', 'lbi-an-chip' + (it.tone ? ' is-' + it.tone : '') + ((active || '') === it.value ?
                ' is-active' : ''), it.label + ' (' + count + ')');
            b.type = 'button';
            b.addEventListener('click', function() {
                bar.querySelectorAll('.lbi-an-chip').forEach(function(x) {
                    x.classList.remove('is-active');
                });
                b.classList.add('is-active');
                onpick(it.value);
            });
            bar.appendChild(b);
        });
        return bar;
    }

    // ------------------------------------------------------------------------------------------------------------
    // Overview.
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
        var charts = root.querySelector('[data-region="charts"]');
        releaseCharts(charts);
        charts.innerHTML = '<div class="lbi-card lbi-lp-card"><div class="lbi-skel lbi-skel-block"></div></div>';
    }

    /**
     * Render KPIs and overview charts.
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
        var charts = root.querySelector('[data-region="charts"]');
        releaseCharts(charts);
        charts.innerHTML = '';
        var list = data.charts || [];
        charts.className = 'lbi-an-charts lbi-an-charts-' + Math.min(3, Math.max(1, list.length));
        list.forEach(function(c) {
            var card = chartCard(c);
            if (c.span) {
                card.style.gridColumn = 'span ' + c.span;
            }
            charts.appendChild(card);
        });
        root.querySelector('[data-region="refreshed"]').textContent = data.refreshed || '';
    }

    // ------------------------------------------------------------------------------------------------------------
    // Level.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Breadcrumb bar.
     */
    function crumbs() {
        var nav = root.querySelector('[data-region="crumbs"]');
        nav.innerHTML = '';
        if (!level) {
            return;
        }
        var depth = path.length;
        var home = el(depth ? 'button' : 'span', 'lbi-lp-crumb' + (depth ? '' : ' is-home'));
        home.innerHTML = icons.home;
        home.appendChild(el('span', '', str.lp_orgoverview));
        if (depth) {
            home.type = 'button';
            home.addEventListener('click', function() {
                go([]);
            });
        }
        nav.appendChild(home);
        (level.crumbs || []).forEach(function(name, i) {
            var s = el('span', 'lbi-lp-sep');
            s.innerHTML = icons.next;
            nav.appendChild(s);
            var last = i === depth - 1;
            var c = el(last ? 'span' : 'button', 'lbi-lp-crumb' + (last ? ' is-current' : ''), name);
            if (!last) {
                c.type = 'button';
                c.addEventListener('click', function() {
                    go(path.slice(0, i + 1));
                });
            }
            nav.appendChild(c);
        });
        if (depth) {
            var back = el('button', 'lbi-lp-back');
            back.type = 'button';
            back.innerHTML = icons.back;
            back.appendChild(el('span', '', level.backlabel));
            back.addEventListener('click', function() {
                go(path.slice(0, depth - 1));
            });
            nav.appendChild(back);
        }
        nav.appendChild(el('span', 'lbi-lp-levelnote', '· ' + fmtstr(str.an_level,
            {n: depth + 1, total: level.levels, name: level.levelname})));
    }

    /**
     * Blocks above (and after) the table: charts / small tables.
     */
    function blocks() {
        [['blocks', level.blocks], ['after', level.after]].forEach(function(pair) {
            var host = root.querySelector('[data-region="' + pair[0] + '"]');
            releaseCharts(host);
            host.innerHTML = '';
            (pair[1] || []).forEach(function(b) {
                host.appendChild(blockNode(b));
            });
        });
    }

    /**
     * One block.
     *
     * @param {Object} b
     * @return {HTMLElement}
     */
    function blockNode(b) {
        if (b.type === 'chart') {
            return chartCard(b);
        }
        var card = el('div', 'lbi-card lbi-lp-card' + (b.tone ? ' lbi-an-tone-card is-' + b.tone : ''));
        var head = el('div', 'lbi-lp-cardhead');
        head.appendChild(el('h3', 'lbi-lp-cardtitle', b.title));
        if (b.info) {
            head.appendChild(info(b.info, true));
        }
        card.appendChild(head);
        card.appendChild(simpleTable(b.heads, b.rows, 'lbi-an-blocktable'));
        return card;
    }

    /**
     * Chips above the table.
     */
    function tableChips() {
        var host = root.querySelector('[data-region="chips"]');
        host.innerHTML = '';
        var t = level.table;
        if (!t.chips) {
            host.hidden = true;
            return;
        }
        host.hidden = false;
        host.appendChild(chipBar(t.chips, t.rows, function(r) {
            return r[t.chips.field];
        }, function(v) {
            view.chip = v;
            view.page = 1;
            table();
        }, view.chip));
    }

    /**
     * Dropdown filters in the table toolbar.
     */
    function tableFilters() {
        var host = root.querySelector('[data-region="tfilters"]');
        host.innerHTML = '';
        (level.table.filters || []).forEach(function(f) {
            var label = el('label', 'lbi-select lbi-select-sm');
            var sel = el('select');
            var o = el('option', '', fmtstr(str.an_allfilter, f.label));
            o.value = '';
            sel.appendChild(o);
            var seen = {};
            level.table.rows.forEach(function(r) {
                if (r[f.field] !== undefined && r[f.field] !== '' && r[f.field] !== null) {
                    seen[r[f.field]] = true;
                }
            });
            Object.keys(seen).sort().forEach(function(v) {
                var op = el('option', '', v);
                op.value = v;
                sel.appendChild(op);
            });
            sel.value = view.f[f.field] || '';
            sel.addEventListener('change', function() {
                view.f[f.field] = sel.value;
                view.page = 1;
                table();
            });
            label.appendChild(sel);
            var chev = el('span', 'lbi-select-chevron');
            chev.innerHTML = icons.chevron;
            label.appendChild(chev);
            host.appendChild(label);
        });
    }

    /**
     * Rows after search / filters / chips and sorting.
     *
     * @return {Array}
     */
    function visibleRows() {
        var t = level.table;
        var q = view.q.toLowerCase();
        var keys = t.searchkeys || ['name'];
        var out = t.rows.filter(function(r) {
            if (q && keys.map(function(k) {
                return String(r[k] || '');
            }).join(' ').toLowerCase().indexOf(q) === -1) {
                return false;
            }
            var ok = true;
            Object.keys(view.f).forEach(function(k) {
                if (view.f[k] && String(r[k]) !== view.f[k]) {
                    ok = false;
                }
            });
            if (t.chips && view.chip && r[t.chips.field] !== view.chip) {
                ok = false;
            }
            return ok;
        });
        if (view.sort) {
            var col = t.columns.filter(function(c) {
                return c.k === view.sort;
            })[0] || {};
            var k = col.sortk || view.sort, d = view.dir;
            out.sort(function(a, b) {
                var va = a[k], vb = b[k];
                if (va === null || va === undefined) {
                    va = typeof vb === 'string' ? '' : -Infinity;
                }
                if (vb === null || vb === undefined) {
                    vb = typeof va === 'string' ? '' : -Infinity;
                }
                if (typeof va === 'string' || typeof vb === 'string') {
                    return d * String(va).localeCompare(String(vb));
                }
                return d * (va - vb);
            });
        }
        return out;
    }

    /**
     * Value cell of the main table.
     *
     * @param {Object} c column
     * @param {Object} r row
     * @return {HTMLElement|String}
     */
    function valueNode(c, r) {
        var v = r[c.k];
        switch (c.type) {
            case 'name':
                var w = el('span', 'lbi-an-namewrap');
                var nm = el('span', 'lbi-lp-name', v);
                if (c.indent && r[c.indent]) {
                    w.style.paddingLeft = (r[c.indent] * 16) + 'px';
                }
                w.appendChild(nm);
                if (c.sub && r[c.sub]) {
                    w.appendChild(el('span', 'lbi-an-sub', r[c.sub]));
                }
                return w;
            case 'text':
                return v === null || v === undefined || v === '' ? '—' : String(v);
            case 'badge':
                if (!v) {
                    return '—';
                }
                return el('span', 'lbi-badge-status is-' + (r[c.k + '_tone'] || 'notstarted'), v);
            case 'pct':
                return v === null || v === undefined ? '—' : (Math.round(v * 10) / 10) + '%';
            case 'dec':
                return v === null || v === undefined ? '—' : Number(v).toFixed(c.decimals === undefined ? 1 : c.decimals);
            case 'signed':
                if (v === null || v === undefined) {
                    return '—';
                }
                var s = el('span', v > 0 ? 'lbi-an-tone-up' : (v < 0 ? 'lbi-an-tone-down' : ''),
                    (v > 0 ? '+' : '') + Number(v).toFixed(1) + (c.suffix === undefined ? '%' : c.suffix));
                return s;
            case 'disp':
                // Pre-formatted display value in r[k + '_d'], raw in r[k] for sorting.
                var dv = r[c.k + '_d'];
                if (dv === undefined || dv === null || dv === '') {
                    return '—';
                }
                return el('span', r[c.k + '_tone'] ? 'lbi-an-tone-' + r[c.k + '_tone'] : '', dv);
            default:
                if (v === null || v === undefined) {
                    return '—';
                }
                return c.tone ? el('span', 'lbi-an-tone-' + c.tone, num(v)) : num(v);
        }
    }

    /**
     * Render the main table.
     */
    function table() {
        var t = level.table;
        var cols = t.columns;
        root.querySelector('[data-region="tabletitle"]').textContent = t.title;
        root.querySelector('[data-region="tabletip"]').textContent = t.info || '';
        var sub = root.querySelector('[data-region="tablesub"]');
        sub.hidden = !t.subtitle;
        sub.textContent = t.subtitle || '';

        var thead = root.querySelector('[data-region="thead"]');
        thead.innerHTML = '';
        var bands = t.bands || null;
        if (bands) {
            var br = el('tr', 'lbi-an-bandrow');
            var i = 0;
            while (i < cols.length) {
                var b = cols[i].band || '';
                var span = 1;
                while (i + span < cols.length && (cols[i + span].band || '') === b) {
                    span++;
                }
                var th0 = el('th', 'lbi-an-band' + (b ? ' is-' + b : ''));
                th0.colSpan = span;
                if (b && bands[b]) {
                    th0.innerHTML = '<span class="lbi-an-bandmark">' + (bands[b].mark || '') + '</span>';
                    th0.appendChild(document.createTextNode(bands[b].label));
                }
                br.appendChild(th0);
                i += span;
            }
            thead.appendChild(br);
        }
        var hr = el('tr');
        cols.forEach(function(c, i) {
            var cls = (c.type === 'name' || c.type === 'text' || c.type === 'badge' ? 'is-text' : '') +
                (c.type === 'action' ? ' is-action' : '') + (view.sort === c.k ? ' is-sorted' : '') +
                (c.band ? ' lbi-an-cell is-' + c.band : '');
            var th = el('th', cls);
            th.scope = 'col';
            if (c.type === 'action') {
                th.textContent = c.label;
            } else {
                var b = el('button', 'lbi-lp-sortbtn');
                b.type = 'button';
                b.appendChild(el('span', '', c.label));
                var si = el('span', 'lbi-lp-sorticon');
                si.innerHTML = icons.sort;
                b.appendChild(si);
                b.addEventListener('click', function() {
                    var text = c.type === 'name' || c.type === 'text' || c.type === 'badge';
                    view.dir = view.sort === c.k ? -view.dir : (text ? 1 : -1);
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
                var td = el('td', (c.type === 'name' || c.type === 'text' || c.type === 'badge' ? 'is-text' : '') +
                    (c.type === 'action' ? 'is-action' : '') + (c.band ? ' lbi-an-cell is-' + c.band : ''));
                if (c.type === 'action') {
                    var b = el('button', 'lbi-btn lbi-btn-primary lbi-btn-sm', c.btn);
                    b.type = 'button';
                    b.addEventListener('click', function() {
                        if (c.action === 'next') {
                            go(path.concat([r.key]));
                        } else {
                            popup(c.popup || 'detail', r);
                        }
                    });
                    td.appendChild(b);
                } else if (c.link) {
                    var a = el('button', 'lbi-lp-countlink', num(r[c.k]));
                    a.type = 'button';
                    a.addEventListener('click', function() {
                        popup(c.link, r);
                    });
                    td.appendChild(a);
                } else {
                    var n = valueNode(c, r);
                    if (n instanceof HTMLElement) {
                        td.appendChild(n);
                    } else {
                        td.textContent = n;
                    }
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
     * Skeleton for the level.
     */
    function skeletonLevel() {
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
        var host = root.querySelector('[data-region="blocks"]');
        releaseCharts(host);
        host.innerHTML = '<div class="lbi-card lbi-lp-card"><div class="lbi-skel lbi-skel-block"></div></div>';
        var after = root.querySelector('[data-region="after"]');
        releaseCharts(after);
        after.innerHTML = '';
    }

    /**
     * Render a level.
     *
     * @param {Object} data
     */
    function setLevel(data) {
        level = data;
        root.classList.remove('is-loading');
        root.querySelector('[data-region="overview"]').hidden = path.length > 0;
        crumbs();
        blocks();
        tableChips();
        tableFilters();
        table();
    }

    /**
     * Update the URL.
     *
     * @param {Boolean} replace
     */
    function syncUrl(replace) {
        var q = Filters.query(state, {report: report, path: path.length ? JSON.stringify(path) : ''});
        if (window.history && window.history.pushState) {
            window.history[replace ? 'replaceState' : 'pushState']({lbi: path}, '', window.location.pathname + '?' + q);
        }
    }

    /**
     * Go to a level.
     *
     * @param {Array} p
     * @param {Boolean} nohistory
     */
    function go(p, nohistory) {
        path = p;
        view = {sort: null, dir: -1, q: '', page: 1, per: view.per, f: {}, chip: ''};
        root.querySelector('[data-region="search"]').value = '';
        if (!nohistory) {
            syncUrl(false);
        }
        root.querySelector('[data-region="overview"]').hidden = path.length > 0;
        skeletonLevel();
        var mine = ++token;
        call('level').then(function(d) {
            if (mine === token) {
                setLevel(d.level);
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
            return null;
        }).catch(fail);
    }

    /**
     * Full reload.
     */
    function loadPage() {
        var mine = ++token;
        root.querySelector('[data-region="error"]').hidden = true;
        skeletonOverview();
        skeletonLevel();
        syncUrl(true);
        call('page').then(function(d) {
            if (mine !== token) {
                return null;
            }
            renderOverview(d);
            setLevel(d.level);
            return null;
        }).catch(fail);
    }

    // ------------------------------------------------------------------------------------------------------------
    // Popups.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Open the modal.
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
     * Popup skeleton.
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
     * Stat cards with icons.
     *
     * @param {Array} stats [{label, value, icon, tone, wide}]
     * @return {HTMLElement}
     */
    function statCards(stats) {
        var box = el('div', 'lbi-modal-stats');
        stats.forEach(function(s) {
            var b = el('div', 'lbi-modal-stat has-icon' + (s.wide ? ' is-wide' : ''));
            var ic = el('span', 'lbi-modal-stat-icon is-' + (s.tone || 'primary'));
            ic.innerHTML = icons['st-' + s.icon] || icons[s.icon] || '';
            b.appendChild(ic);
            var tx = el('div', 'lbi-modal-stat-text');
            tx.appendChild(el('p', 'lbi-modal-stat-label', s.label));
            tx.appendChild(el('p', 'lbi-modal-stat-value' + (s.small ? ' is-small' : ''), s.value));
            b.appendChild(tx);
            box.appendChild(b);
        });
        return box;
    }

    /**
     * Profile header.
     *
     * @param {Object} p {initials, name, meta, email, manager, lines}
     * @return {HTMLElement}
     */
    function profileCard(p) {
        var prof = el('div', 'lbi-profile');
        var left = el('div', 'lbi-profile-left');
        if (p.initials) {
            left.appendChild(el('span', 'lbi-avatar', p.initials));
        }
        var who = el('div');
        who.appendChild(el('p', 'lbi-profile-name', p.name));
        if (p.meta) {
            who.appendChild(el('p', 'lbi-profile-meta', p.meta));
        }
        (p.lines || []).forEach(function(l) {
            var line = el('p', 'lbi-profile-meta');
            if (typeof l === 'object') {
                line.appendChild(document.createTextNode(l.label + ' '));
                line.appendChild(el('b', '', l.value));
                if (l.after) {
                    line.appendChild(document.createTextNode(' ' + l.after));
                }
            } else {
                line.textContent = l;
            }
            who.appendChild(line);
        });
        left.appendChild(who);
        prof.appendChild(left);
        if (p.email || p.manager) {
            var right = el('div', 'lbi-profile-right');
            if (p.email) {
                right.appendChild(el('p', '', p.email));
            }
            if (p.manager) {
                right.appendChild(el('p', '', p.manager));
            }
            prof.appendChild(right);
        }
        return prof;
    }

    /**
     * Popup section (optionally tinted, with a lead number and a table).
     *
     * @param {Object} s {title, tone, mark, lead: {value, label, info}, table: {heads, rows}, chips}
     * @return {HTMLElement}
     */
    function section(s) {
        var box = el('div', 'lbi-an-section' + (s.tone ? ' is-' + s.tone : ''));
        if (s.title) {
            var h = el('p', 'lbi-an-sectiontitle');
            if (s.mark) {
                h.appendChild(el('span', 'lbi-an-bandmark', s.mark));
            }
            h.appendChild(document.createTextNode(s.title));
            box.appendChild(h);
        }
        if (s.lead) {
            var lead = el('div', 'lbi-an-lead');
            lead.appendChild(el('span', 'lbi-an-leadvalue', s.lead.value));
            lead.appendChild(el('span', 'lbi-an-leadlabel', s.lead.label));
            if (s.lead.info) {
                lead.appendChild(info(s.lead.info, true));
            }
            box.appendChild(lead);
        }
        if (s.table) {
            var t = simpleTable(s.table.heads, s.table.rows);
            if (s.chips) {
                var active = '';
                box.appendChild(chipBar(s.chips, s.table.rows, function(r) {
                    return r.f ? r.f[s.chips.field] : null;
                }, function(v) {
                    active = v;
                    t._fill(s.table.rows.filter(function(r) {
                        return !active || (r.f && r.f[s.chips.field] === active);
                    }));
                }, ''));
            }
            box.appendChild(t);
        }
        if (s.note) {
            box.appendChild(el('p', 'lbi-an-note', s.note));
        }
        return box;
    }

    /**
     * Open a popup for a row.
     *
     * @param {String} id
     * @param {Object} r row
     */
    function popup(id, r) {
        var content = el('div');
        var head = el('div', 'lbi-modal-head lbi-modal-head-col');
        var title = el('h3', 'lbi-modal-title', r.name || '');
        head.appendChild(title);
        content.appendChild(head);
        var holder = el('div');
        holder.appendChild(modalSkeleton());
        content.appendChild(holder);
        openModal(content, 'lg');
        call('popup', {popup: id, key: String(r.key)}).then(function(d) {
            var p = d.popup;
            title.textContent = p.title;
            if (p.info) {
                var row = el('div', 'lbi-modal-head');
                row.appendChild(title);
                row.appendChild(info(p.info, true));
                head.innerHTML = '';
                head.appendChild(row);
            }
            if (p.desc) {
                head.appendChild(el('p', 'lbi-modal-desc', p.desc));
            }
            root.querySelector('.lbi-modal-dialog').className = 'lbi-modal-dialog is-' + (p.size || 'lg');
            holder.innerHTML = '';
            if (p.profile) {
                holder.appendChild(profileCard(p.profile));
            }
            if (p.stats && p.stats.length) {
                holder.appendChild(statCards(p.stats));
            }
            (p.sections || []).forEach(function(s) {
                holder.appendChild(section(s));
            });
            if (p.table) {
                holder.appendChild(section({table: p.table, chips: p.chips}));
            }
            return null;
        }).catch(function(e) {
            holder.textContent = str.loaderror + (e && e.message ? ' (' + e.message + ')' : '');
        });
    }

    // ------------------------------------------------------------------------------------------------------------
    // Exports.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * CSV download.
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
            out.push([root.querySelector('.lbi-h1').textContent.trim()]);
            root.querySelectorAll('[data-kpi]').forEach(function(card) {
                var k = overview.kpis[card.getAttribute('data-kpi')];
                if (k) {
                    out.push([card.querySelector('.lbi-kpi-label').textContent.trim(), k.display,
                        k.trend === null ? '' : (k.trend > 0 ? '+' : '') + k.trend + '%']);
                }
            });
            out.push([]);
        }
        if (level) {
            var cols = level.table.columns.filter(function(c) {
                return c.type !== 'action';
            });
            out.push([level.table.title]);
            out.push(cols.map(function(c) {
                return c.label;
            }));
            visibleRows().forEach(function(r) {
                out.push(cols.map(function(c) {
                    return c.type === 'disp' ? (r[c.k + '_d'] || '') : r[c.k];
                }));
            });
        }
        Filters.downloadCsv(report + '-analysis-level' + (path.length + 1) + '.csv', out);
    }

    // ------------------------------------------------------------------------------------------------------------
    // Init.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Wire events.
     */
    function bind() {
        Filters.bind(root, state, function() {
            path = [];
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
         * @param {String} rep report key
         * @param {Object} initial filters
         * @param {Array} start path
         */
        init: function(rep, initial, start) {
            root = document.querySelector('[data-region="lbi-analysis"]');
            if (!root) {
                return;
            }
            report = rep;
            state = Filters.normalise(initial);
            path = Array.isArray(start) ? start : [];
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

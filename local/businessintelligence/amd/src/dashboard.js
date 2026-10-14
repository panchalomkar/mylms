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
 * Executive dashboard: loads KPI + report data over AJAX whenever a filter changes (no page reload).
 *
 * @module     local_businessintelligence/dashboard
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/str', 'local_businessintelligence/charts', 'local_businessintelligence/filters'],
function(Ajax, Str, Charts, Filters) {
    'use strict';

    var root = null;
    var state = null;
    var token = 0;
    var lastdata = null;
    var str = {};

    var SKEL = {
        value: '<span class="lbi-skel lbi-skel-val"></span>',
        spark: '<span class="lbi-skel lbi-skel-spark"></span>',
        stat: '<span class="lbi-skel lbi-skel-stat"></span>',
        chart: '<div class="lbi-skel lbi-skel-block"></div>'
    };

    /**
     * Replace values and charts with skeletons while loading.
     */
    function skeleton() {
        root.classList.add('is-loading');
        root.querySelectorAll('[data-kpi]').forEach(function(card) {
            card.querySelector('[data-field="value"]').innerHTML = SKEL.value;
            card.querySelector('[data-field="trend"]').innerHTML = '';
            var sp = card.querySelector('[data-field="spark"]');
            if (sp._lbiObserver) {
                sp._lbiObserver.disconnect();
            }
            sp.innerHTML = SKEL.spark;
        });
        root.querySelectorAll('[data-stat]').forEach(function(el) {
            el.innerHTML = SKEL.stat;
        });
        root.querySelectorAll('[data-chart]').forEach(function(el) {
            if (el._lbiObserver) {
                el._lbiObserver.disconnect();
                el._lbiObserver = null;
            }
            el.innerHTML = SKEL.chart;
        });
    }

    /**
     * Render the trend pill.
     *
     * @param {HTMLElement} el
     * @param {Number|null} trend
     */
    function renderTrend(el, trend) {
        el.className = 'lbi-trend';
        if (trend === null || trend === undefined) {
            el.innerHTML = '';
            return;
        }
        var dir = trend > 0 ? 'up' : (trend < 0 ? 'down' : 'flat');
        el.classList.add('is-' + dir);
        el.innerHTML = Charts.ICONS[dir];
        var t = document.createElement('span');
        t.textContent = (trend > 0 ? '+' : '') + Number(trend).toFixed(1) + '%';
        el.appendChild(t);
    }

    /**
     * Fill the page with a payload.
     *
     * @param {Object} data
     */
    function render(data) {
        lastdata = data;
        root.classList.remove('is-loading');
        Object.keys(data.kpis).forEach(function(key) {
            var k = data.kpis[key];
            var card = root.querySelector('[data-kpi="' + key + '"]');
            if (!card) {
                return;
            }
            var v = card.querySelector('[data-field="value"]');
            v.textContent = k.display;
            v.classList.remove('lbi-fade-in');
            void v.offsetWidth;
            v.classList.add('lbi-fade-in');
            renderTrend(card.querySelector('[data-field="trend"]'), k.trend);
            var sp = card.querySelector('[data-field="spark"]');
            sp.innerHTML = '';
            Charts.sparkline(sp, k.spark, k.trend !== null && k.trend < 0 ? 'destructive' : 'success');
        });

        Object.keys(data.reports).forEach(function(key) {
            var rep = data.reports[key];
            var card = root.querySelector('[data-report="' + key + '"]');
            if (!card) {
                return;
            }
            Object.keys(rep.stats).forEach(function(s) {
                var el = card.querySelector('[data-stat="' + s + '"]');
                if (el) {
                    el.textContent = rep.stats[s];
                }
            });
            card.querySelectorAll('[data-chart]').forEach(function(box) {
                var c = rep.charts[box.getAttribute('data-chart')];
                var type = box.getAttribute('data-type');
                box.innerHTML = '';
                if (!c) {
                    Charts.empty(box);
                    return;
                }
                switch (type) {
                    case 'funnel':
                        Charts.funnel(box, c);
                        break;
                    case 'line':
                        Charts.line(box, c, {color: 'chart1', suffix: key === 'sessions' ? '%' : ''});
                        break;
                    case 'donut':
                        Charts.donut(box, c);
                        break;
                    case 'hbar':
                        Charts.hbar(box, c, {suffix: key === 'roleplay' ? '%' : ''});
                        break;
                    case 'vbar':
                        Charts.vbar(box, c);
                        break;
                    case 'ratings':
                        Charts.ratings(box, c);
                        break;
                    case 'weaklist':
                        Charts.weaklist(box, c);
                        break;
                }
            });
        });
        var upd = root.querySelector('[data-region="updated"]');
        if (upd && data.generated && str.updated) {
            upd.textContent = str.updated.replace('{$a}', data.generated);
        }
    }

    /**
     * Keep the "View Analysis" links and URL in sync with the filters.
     */
    function syncLinks() {
        var q = Filters.query(state);
        root.querySelectorAll('[data-region="viewanalysis"]').forEach(function(a) {
            var url = new URL(a.href, window.location.href);
            var report = url.searchParams.get('report');
            a.href = url.pathname + '?' + Filters.query(state, {report: report});
        });
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', window.location.pathname + '?' + q);
        }
    }

    /**
     * Fetch data for the current filters.
     */
    function load() {
        var mine = ++token;
        var err = root.querySelector('[data-region="error"]');
        err.hidden = true;
        skeleton();
        syncLinks();
        Ajax.call([{
            methodname: 'local_businessintelligence_get_dashboard',
            args: Filters.normalise(state)
        }])[0].then(function(resp) {
            if (mine !== token) {
                return null;
            }
            render(JSON.parse(resp.data));
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
            root.querySelectorAll('[data-chart]').forEach(function(box) {
                Charts.empty(box);
            });
        });
    }

    /**
     * CSV export of everything currently on screen.
     */
    function exportCsv() {
        if (!lastdata) {
            return;
        }
        var rows = [[str.heading], []];
        Filters.summary(root).forEach(function(r) {
            rows.push(r);
        });
        rows.push([]);
        rows.push([str.kpiribbon, '', '']);
        root.querySelectorAll('[data-kpi]').forEach(function(card) {
            var k = lastdata.kpis[card.getAttribute('data-kpi')];
            var label = card.querySelector('.lbi-kpi-label').textContent.trim();
            rows.push([label, k.display, k.trend === null ? '' : (k.trend > 0 ? '+' : '') + k.trend + '%']);
        });
        rows.push([]);
        rows.push([str.bireports, '', '']);
        root.querySelectorAll('[data-report]').forEach(function(card) {
            var title = card.querySelector('.lbi-report-title').textContent.trim();
            card.querySelectorAll('.lbi-stat').forEach(function(s) {
                rows.push([title, s.querySelector('.lbi-stat-label').textContent.trim(),
                    s.querySelector('.lbi-stat-value').textContent.trim()]);
            });
        });
        var d = new Date();
        var stamp = d.getFullYear() + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0');
        Filters.downloadCsv('business-intelligence-' + stamp + '.csv', rows);
    }

    return {
        /**
         * Initialise.
         *
         * @param {Object} initial filter state from the server
         */
        init: function(initial) {
            root = document.querySelector('[data-region="lbi-dashboard"]');
            if (!root) {
                return;
            }
            state = Filters.normalise(initial);
            Str.get_strings([
                {key: 'nodata', component: 'local_businessintelligence'},
                {key: 'value', component: 'local_businessintelligence'},
                {key: 'users', component: 'local_businessintelligence'},
                {key: 'outof5', component: 'local_businessintelligence'},
                {key: 'norating', component: 'local_businessintelligence'},
                {key: 'loaderror', component: 'local_businessintelligence'},
                {key: 'updated', component: 'local_businessintelligence'},
                {key: 'heading', component: 'local_businessintelligence'},
                {key: 'kpiribbon', component: 'local_businessintelligence'},
                {key: 'bireports', component: 'local_businessintelligence'}
            ]).then(function(s) {
                str = {nodata: s[0], value: s[1], users: s[2], outof5: s[3], norating: s[4], loaderror: s[5],
                    updated: s[6], heading: s[7], kpiribbon: s[8], bireports: s[9]};
                Charts.setStrings(str);
                Filters.bind(root, state, load);
                root.querySelector('[data-action="csv"]').addEventListener('click', exportCsv);
                root.querySelector('[data-action="pdf"]').addEventListener('click', function() {
                    window.print();
                });
                load();
                return null;
            }).catch(function() {
                Filters.bind(root, state, load);
                load();
            });
        }
    };
});

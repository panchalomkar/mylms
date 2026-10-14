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
 * Shared filter-bar behaviour: state, applied-filter counter, reset, URL sync, info tooltips on touch.
 *
 * @module     local_businessintelligence/filters
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    var DEFAULTS = {range: '6m', department: '', designation: '', branch: '', manager: '', competency: 0};
    var KEYS = Object.keys(DEFAULTS);

    /**
     * Normalise a state object.
     *
     * @param {Object} s
     * @return {Object}
     */
    function normalise(s) {
        var out = {};
        KEYS.forEach(function(k) {
            var v = s && s[k] !== undefined && s[k] !== null ? s[k] : DEFAULTS[k];
            out[k] = k === 'competency' ? (parseInt(v, 10) || 0) : String(v);
        });
        return out;
    }

    /**
     * Number of applied filters (date range counts unless "all").
     *
     * @param {Object} s
     * @return {Number}
     */
    function count(s) {
        var n = s.range !== 'all' ? 1 : 0;
        ['department', 'designation', 'branch', 'manager'].forEach(function(k) {
            if (s[k] !== '') {
                n++;
            }
        });
        if (s.competency > 0) {
            n++;
        }
        return n;
    }

    /**
     * Reflect the state in the filter bar (selected values, active outline, badge).
     *
     * @param {HTMLElement} root
     * @param {Object} s
     */
    function refresh(root, s) {
        root.querySelectorAll('[data-filter]').forEach(function(sel) {
            var k = sel.getAttribute('data-filter');
            var v = String(s[k] === 0 ? '' : s[k]);
            if (k === 'range') {
                v = s.range;
            }
            if (sel.value !== v) {
                sel.value = v;
            }
            var label = sel.closest('.lbi-select');
            if (label) {
                label.classList.toggle('is-active', k === 'range' ? v !== 'all' : v !== '');
            }
        });
        var badge = root.querySelector('[data-region="filtercount"]');
        if (badge) {
            var n = count(s);
            if (badge.textContent !== String(n)) {
                badge.textContent = n;
                badge.classList.add('is-bump');
                window.setTimeout(function() {
                    badge.classList.remove('is-bump');
                }, 200);
            }
            badge.classList.toggle('is-zero', n === 0);
        }
    }

    /**
     * Query string for a state (defaults omitted except range).
     *
     * @param {Object} s
     * @param {Object} extra
     * @return {String}
     */
    function query(s, extra) {
        var p = new URLSearchParams();
        Object.keys(extra || {}).forEach(function(k) {
            if (extra[k] !== '' && extra[k] !== null && extra[k] !== undefined) {
                p.set(k, extra[k]);
            }
        });
        KEYS.forEach(function(k) {
            if (k === 'range' || (s[k] !== '' && s[k] !== 0)) {
                p.set(k, s[k]);
            }
        });
        return p.toString();
    }

    /**
     * Wire the filter bar.
     *
     * @param {HTMLElement} root
     * @param {Object} state mutable state object
     * @param {Function} onchange called after every change
     */
    function bind(root, state, onchange) {
        root.querySelectorAll('[data-filter]').forEach(function(sel) {
            sel.addEventListener('change', function() {
                var k = sel.getAttribute('data-filter');
                state[k] = k === 'competency' ? (parseInt(sel.value, 10) || 0) : sel.value;
                refresh(root, state);
                onchange();
            });
        });
        var reset = root.querySelector('[data-action="reset"]');
        if (reset) {
            reset.addEventListener('click', function() {
                var before = JSON.stringify(normalise(state));
                KEYS.forEach(function(k) {
                    state[k] = DEFAULTS[k];
                });
                refresh(root, state);
                if (before !== JSON.stringify(normalise(state))) {
                    onchange();
                }
            });
        }
        // Info tooltips: tap to toggle on touch devices, Escape closes.
        root.addEventListener('click', function(e) {
            var btn = e.target.closest('.lbi-info-btn');
            root.querySelectorAll('.lbi-info.is-open').forEach(function(i) {
                if (!btn || i !== btn.parentNode) {
                    i.classList.remove('is-open');
                }
            });
            if (btn) {
                btn.parentNode.classList.toggle('is-open');
            }
        });
        root.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                root.querySelectorAll('.lbi-info.is-open').forEach(function(i) {
                    i.classList.remove('is-open');
                });
            }
        });
        refresh(root, state);
    }

    /**
     * Download text as a CSV file.
     *
     * @param {String} filename
     * @param {Array} rows array of arrays
     */
    function downloadCsv(filename, rows) {
        var esc = function(v) {
            v = v === null || v === undefined ? '' : String(v);
            return /[",\n\r]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
        };
        var csv = '﻿' + rows.map(function(r) {
            return r.map(esc).join(',');
        }).join('\r\n');
        var blob = new Blob([csv], {type: 'text/csv;charset=utf-8'});
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.setTimeout(function() {
            URL.revokeObjectURL(a.href);
            a.remove();
        }, 500);
    }

    /**
     * Human readable filter summary for exports.
     *
     * @param {HTMLElement} root
     * @return {Array} rows [label, value]
     */
    function summary(root) {
        var rows = [];
        root.querySelectorAll('[data-filter]').forEach(function(sel) {
            var opt = sel.options[sel.selectedIndex];
            rows.push([sel.getAttribute('title') || sel.getAttribute('data-filter'), opt ? opt.text : '']);
        });
        return rows;
    }

    return {
        DEFAULTS: DEFAULTS,
        normalise: normalise,
        count: count,
        refresh: refresh,
        query: query,
        bind: bind,
        downloadCsv: downloadCsv,
        summary: summary
    };
});

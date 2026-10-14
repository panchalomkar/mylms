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
 * Lightweight responsive SVG charts (sparkline, line, donut, funnel, horizontal / vertical bars)
 * with hover tooltips. No external chart library needed.
 *
 * @module     local_businessintelligence/charts
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    var NS = 'http://www.w3.org/2000/svg';
    var uid = 0;

    var FALLBACK = {
        primary: '#033252', accent: '#eb980d', success: '#12965a', destructive: '#d92d20', warning: '#f2b705',
        chart1: '#033252', chart2: '#eb980d', chart3: '#12965a', chart4: '#5b85b7', muted: '#5b7285',
        border: '#e3e9ef', fg: '#0f2537'
    };

    var ICONS = {
        up: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round" class="lbi-icon" aria-hidden="true"><path d="M16 7h6v6"></path>' +
            '<path d="m22 7-8.5 8.5-5-5L2 17"></path></svg>',
        down: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round" class="lbi-icon" aria-hidden="true"><path d="M16 17h6v-6"></path>' +
            '<path d="m22 17-8.5-8.5-5 5L2 7"></path></svg>',
        flat: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round" class="lbi-icon" aria-hidden="true"><path d="M5 12h14"></path></svg>'
    };

    var strings = {nodata: 'No data', value: 'value', users: 'users', outof5: '/ 5', norating: 'No ratings yet'};

    /**
     * Resolve a theme colour token (CSS custom property on .lbi) to a value.
     *
     * @param {HTMLElement} node
     * @param {String} name
     * @return {String}
     */
    function color(node, name) {
        if (!name) {
            return FALLBACK.chart1;
        }
        if (name.charAt(0) === '#') {
            return name;
        }
        var root = node.closest('.lbi') || document.documentElement;
        var v = window.getComputedStyle(root).getPropertyValue('--lbi-' + name).trim();
        return v || FALLBACK[name] || FALLBACK.chart1;
    }

    /**
     * Create an SVG element.
     *
     * @param {String} tag
     * @param {Object} attrs
     * @param {Element} parent
     * @return {Element}
     */
    function svgEl(tag, attrs, parent) {
        var e = document.createElementNS(NS, tag);
        Object.keys(attrs || {}).forEach(function(k) {
            e.setAttribute(k, attrs[k]);
        });
        if (parent) {
            parent.appendChild(e);
        }
        return e;
    }

    /**
     * Number formatting with grouping.
     *
     * @param {Number} n
     * @return {String}
     */
    function fmt(n) {
        if (n === null || n === undefined || isNaN(n)) {
            return '–';
        }
        return Number(n).toLocaleString('en-US', {maximumFractionDigits: 1});
    }

    /**
     * "Nice" axis maximum and step.
     *
     * @param {Number} max
     * @param {Number} ticks
     * @return {Object}
     */
    function niceScale(max, ticks) {
        ticks = ticks || 4;
        if (!max || max <= 0) {
            return {max: ticks, step: 1};
        }
        var raw = max / ticks;
        var mag = Math.pow(10, Math.floor(Math.log10(raw)));
        var steps = [1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10];
        var step = mag;
        for (var i = 0; i < steps.length; i++) {
            if (raw <= steps[i] * mag) {
                step = steps[i] * mag;
                break;
            }
        }
        if (max < ticks && max <= 4) {
            step = 1;
        }
        return {max: step * ticks, step: step};
    }

    /**
     * Monotone cubic (Fritsch-Carlson) path through points - same feel as recharts "monotone".
     *
     * @param {Array} p list of [x, y]
     * @return {String}
     */
    function monotone(p) {
        var n = p.length;
        if (!n) {
            return '';
        }
        if (n < 3) {
            return 'M' + p.map(function(q) {
                return q[0].toFixed(2) + ',' + q[1].toFixed(2);
            }).join('L');
        }
        var dx = [], m = [], t = [], i;
        for (i = 0; i < n - 1; i++) {
            dx[i] = p[i + 1][0] - p[i][0];
            m[i] = dx[i] ? (p[i + 1][1] - p[i][1]) / dx[i] : 0;
        }
        t[0] = m[0];
        t[n - 1] = m[n - 2];
        for (i = 1; i < n - 1; i++) {
            if (m[i - 1] * m[i] <= 0) {
                t[i] = 0;
            } else {
                t[i] = 3 * (dx[i - 1] + dx[i]) / ((2 * dx[i] + dx[i - 1]) / m[i - 1] + (dx[i] + 2 * dx[i - 1]) / m[i]);
            }
        }
        var d = 'M' + p[0][0].toFixed(2) + ',' + p[0][1].toFixed(2);
        for (i = 0; i < n - 1; i++) {
            var h = dx[i] / 3;
            d += 'C' + (p[i][0] + h).toFixed(2) + ',' + (p[i][1] + t[i] * h).toFixed(2) + ',' +
                (p[i + 1][0] - h).toFixed(2) + ',' + (p[i + 1][1] - t[i + 1] * h).toFixed(2) + ',' +
                p[i + 1][0].toFixed(2) + ',' + p[i + 1][1].toFixed(2);
        }
        return d;
    }

    /**
     * Split a label into at most two lines.
     *
     * @param {String} text
     * @param {Number} maxchars
     * @return {Array}
     */
    function wrap(text, maxchars) {
        text = String(text || '');
        if (text.length <= maxchars) {
            return [text];
        }
        var words = text.split(/\s+/), lines = [''];
        words.forEach(function(w) {
            var cur = lines[lines.length - 1];
            if (cur && (cur + ' ' + w).length > maxchars && lines.length < 2) {
                lines.push(w);
            } else {
                lines[lines.length - 1] = cur ? cur + ' ' + w : w;
            }
        });
        return lines.map(function(l) {
            return l.length > maxchars + 4 ? l.substr(0, maxchars + 2) + '…' : l;
        });
    }

    /**
     * Prepare a chart container: clear it, keep a resize observer that redraws on width change.
     *
     * @param {HTMLElement} box
     * @param {Function} draw
     */
    function mount(box, draw) {
        if (box._lbiObserver) {
            box._lbiObserver.disconnect();
        }
        var lastw = -1;
        var run = function() {
            var w = box.clientWidth;
            if (w === lastw || w === 0) {
                return;
            }
            lastw = w;
            box.innerHTML = '';
            draw(w);
        };
        run();
        if (window.ResizeObserver) {
            var raf = null;
            box._lbiObserver = new window.ResizeObserver(function() {
                if (raf) {
                    window.cancelAnimationFrame(raf);
                }
                raf = window.requestAnimationFrame(run);
            });
            box._lbiObserver.observe(box);
        }
    }

    /**
     * Empty state.
     *
     * @param {HTMLElement} box
     */
    function empty(box) {
        if (box._lbiObserver) {
            box._lbiObserver.disconnect();
            box._lbiObserver = null;
        }
        box.innerHTML = '';
        var d = document.createElement('div');
        d.className = 'lbi-chart-empty';
        d.textContent = strings.nodata;
        box.appendChild(d);
    }

    // ------------------------------------------------------------------------------------------------------------
    // Tooltip.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * Tooltip element for a chart container.
     *
     * @param {HTMLElement} box
     * @return {Object}
     */
    function tooltip(box) {
        var tip = document.createElement('div');
        tip.className = 'lbi-ctip';
        tip.setAttribute('role', 'status');
        box.appendChild(tip);
        return {
            show: function(rows, x, y) {
                tip.innerHTML = '';
                rows.forEach(function(r) {
                    var row = document.createElement('div');
                    row.className = 'lbi-ctip-row';
                    if (r.color) {
                        var dot = document.createElement('span');
                        dot.className = 'lbi-legend-dot';
                        dot.style.background = r.color;
                        row.appendChild(dot);
                    }
                    var txt = document.createElement('span');
                    txt.textContent = r.text;
                    if (r.textcolor) {
                        txt.style.color = r.textcolor;
                    }
                    if (r.bold) {
                        txt.style.fontWeight = '700';
                    }
                    row.appendChild(txt);
                    tip.appendChild(row);
                });
                tip.classList.add('is-visible');
                var bw = box.clientWidth, tw = tip.offsetWidth, th = tip.offsetHeight;
                var left = x + 14;
                if (left + tw > bw) {
                    left = Math.max(0, x - tw - 14);
                }
                var top = Math.max(-8, Math.min(y - th / 2, box.clientHeight - th + 8));
                tip.style.left = left + 'px';
                tip.style.top = top + 'px';
            },
            hide: function() {
                tip.classList.remove('is-visible');
            }
        };
    }

    /**
     * Pointer position relative to the container.
     *
     * @param {HTMLElement} box
     * @param {Event} e
     * @return {Array}
     */
    function pos(box, e) {
        var r = box.getBoundingClientRect();
        var src = e.touches && e.touches[0] ? e.touches[0] : e;
        return [src.clientX - r.left, src.clientY - r.top];
    }

    /**
     * Legend under a chart.
     *
     * @param {HTMLElement} box
     * @param {Array} items [{label, color}]
     * @param {Boolean} colourtext
     */
    function legend(box, items, colourtext) {
        var l = document.createElement('div');
        l.className = 'lbi-legend';
        items.forEach(function(it) {
            var s = document.createElement('span');
            s.className = 'lbi-legend-item';
            var d = document.createElement('span');
            d.className = 'lbi-legend-dot';
            d.style.background = it.color;
            var t = document.createElement('span');
            t.textContent = it.label;
            t.style.color = colourtext ? it.color : '';
            s.appendChild(d);
            s.appendChild(t);
            l.appendChild(s);
        });
        box.appendChild(l);
        return l;
    }

    // ------------------------------------------------------------------------------------------------------------
    // Charts.
    // ------------------------------------------------------------------------------------------------------------

    /**
     * KPI sparkline (area with gradient).
     *
     * @param {HTMLElement} box
     * @param {Array} values
     * @param {String} tone success|destructive
     */
    function sparkline(box, values, tone) {
        values = (values || []).map(Number);
        mount(box, function(w) {
            var h = box.clientHeight || 26;
            var c = color(box, tone || 'success');
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, preserveAspectRatio: 'none', 'aria-hidden': 'true'}, box);
            if (values.length < 2) {
                values = [values[0] || 0, values[0] || 0];
            }
            var min = Math.min.apply(null, values), max = Math.max.apply(null, values);
            var span = max - min || 1;
            var top = 2, usable = h * 0.55;
            var pts = values.map(function(v, i) {
                var y = (max === min) ? top + usable / 2 : top + (1 - (v - min) / span) * usable;
                return [i * w / (values.length - 1), y];
            });
            var id = 'lbi-sp-' + (++uid);
            var defs = svgEl('defs', {}, svg);
            var g = svgEl('linearGradient', {id: id, x1: 0, y1: 0, x2: 0, y2: 1}, defs);
            svgEl('stop', {offset: '0%', 'stop-color': c, 'stop-opacity': 0.35}, g);
            svgEl('stop', {offset: '100%', 'stop-color': c, 'stop-opacity': 0}, g);
            var line = monotone(pts);
            svgEl('path', {d: line + 'L' + w + ',' + h + 'L0,' + h + 'Z', fill: 'url(#' + id + ')', 'fill-opacity': 0.6}, svg);
            svgEl('path', {d: line, fill: 'none', stroke: c, 'stroke-width': 1.8, 'vector-effect': 'non-scaling-stroke'}, svg);
            svg.classList.add('lbi-fade-in');
        });
    }

    /**
     * Line chart with grid, axes and hover cursor.
     *
     * @param {HTMLElement} box
     * @param {Object} data {labels, values, max}
     * @param {Object} opts {color, name, suffix}
     */
    function line(box, data, opts) {
        opts = opts || {};
        var labels = data.labels || [], values = data.values || [];
        var hasdata = values.some(function(v) {
            return v !== null && v !== undefined;
        });
        if (!labels.length || !hasdata) {
            empty(box);
            return;
        }
        mount(box, function(w) {
            var h = box.clientHeight || 170;
            var c = color(box, opts.color || 'chart1');
            var m = {l: 42, r: 8, t: 8, b: 26};
            var iw = Math.max(10, w - m.l - m.r), ih = Math.max(10, h - m.t - m.b);
            var vmax = Math.max.apply(null, values.map(function(v) {
                return v || 0;
            }));
            var sc = data.max ? {max: data.max, step: data.max / 4} : niceScale(vmax, 4);
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, role: 'img'}, box);
            var x = function(i) {
                return m.l + (labels.length === 1 ? iw / 2 : i * iw / (labels.length - 1));
            };
            var y = function(v) {
                return m.t + ih - (v / sc.max) * ih;
            };
            var tickcolor = color(box, 'muted'), grid = color(box, 'border');
            for (var t = 0; t <= 4; t++) {
                var tv = sc.step * t, ty = y(tv);
                svgEl('line', {x1: m.l, x2: m.l + iw, y1: ty, y2: ty, stroke: grid}, svg);
                var tl = svgEl('text', {x: m.l - 8, y: ty, 'text-anchor': 'end', 'dominant-baseline': 'middle',
                    'font-size': 11.5, fill: tickcolor}, svg);
                tl.textContent = fmt(tv);
            }
            var every = Math.max(1, Math.ceil(labels.length / Math.max(2, Math.floor(iw / 38))));
            labels.forEach(function(l, i) {
                if (i % every !== 0 && i !== labels.length - 1) {
                    return;
                }
                var xl = svgEl('text', {x: x(i), y: m.t + ih + 16, 'text-anchor': 'middle', 'font-size': 11.5, fill: tickcolor}, svg);
                xl.textContent = l;
            });
            var pts = [], idx = [];
            values.forEach(function(v, i) {
                if (v !== null && v !== undefined) {
                    pts.push([x(i), y(v)]);
                    idx.push(i);
                }
            });
            if (opts.area && pts.length) {
                var gid = 'lbi-ar-' + (++uid);
                var gd = svgEl('linearGradient', {id: gid, x1: 0, y1: 0, x2: 0, y2: 1}, svgEl('defs', {}, svg));
                svgEl('stop', {offset: '0%', 'stop-color': c, 'stop-opacity': 0.22}, gd);
                svgEl('stop', {offset: '100%', 'stop-color': c, 'stop-opacity': 0.01}, gd);
                svgEl('path', {d: monotone(pts) + 'L' + pts[pts.length - 1][0] + ',' + (m.t + ih) + 'L' + pts[0][0] + ',' +
                    (m.t + ih) + 'Z', fill: 'url(#' + gid + ')'}, svg);
            }
            var path = svgEl('path', {d: monotone(pts), fill: 'none', stroke: c, 'stroke-width': 2.4,
                'stroke-linecap': 'round'}, svg);
            var len = path.getTotalLength ? path.getTotalLength() : 0;
            if (len) {
                path.style.strokeDasharray = len;
                path.style.strokeDashoffset = len;
                path.getBoundingClientRect();
                path.style.transition = 'stroke-dashoffset 0.8s ease';
                path.style.strokeDashoffset = 0;
            }
            var cursor = svgEl('line', {y1: m.t, y2: m.t + ih, stroke: '#c4ccd5', 'stroke-width': 1, opacity: 0}, svg);
            var dot = svgEl('circle', {r: 4.5, fill: c, stroke: '#fff', 'stroke-width': 2, opacity: 0}, svg);
            var tip = tooltip(box);
            var overlay = svgEl('rect', {x: m.l - 10, y: m.t, width: iw + 20, height: ih, fill: 'transparent'}, svg);
            var move = function(e) {
                var p = pos(box, e);
                var best = 0, bd = Infinity;
                idx.forEach(function(i, k) {
                    var d = Math.abs(x(i) - p[0]);
                    if (d < bd) {
                        bd = d;
                        best = k;
                    }
                });
                var i = idx[best], px = x(i), py = y(values[i]);
                cursor.setAttribute('x1', px);
                cursor.setAttribute('x2', px);
                cursor.setAttribute('opacity', 1);
                dot.setAttribute('cx', px);
                dot.setAttribute('cy', py);
                dot.setAttribute('opacity', 1);
                tip.show([{text: labels[i]}, {text: (opts.name || strings.value) + ' : ' + fmt(values[i]) + (opts.suffix || ''),
                    textcolor: c}], px, py);
            };
            var leave = function() {
                cursor.setAttribute('opacity', 0);
                dot.setAttribute('opacity', 0);
                tip.hide();
            };
            overlay.addEventListener('mousemove', move);
            overlay.addEventListener('touchstart', move, {passive: true});
            overlay.addEventListener('mouseleave', leave);
        });
    }

    /**
     * Donut chart with legend.
     *
     * @param {HTMLElement} box
     * @param {Object} data {items: [{label, value}]}
     * @param {Object} opts {colors}
     */
    function donut(box, data, opts) {
        opts = opts || {};
        var items = data.items || [];
        var total = items.reduce(function(s, it) {
            return s + (Number(it.value) || 0);
        }, 0);
        if (!total) {
            empty(box);
            return;
        }
        var colors = opts.colors || ['chart1', 'chart2', 'chart3', 'chart4'];
        mount(box, function(w) {
            var h = (box.clientHeight || 190) - 30;
            var cols = items.map(function(it, i) {
                return color(box, it.color || colors[i % colors.length]);
            });
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, role: 'img', style: 'height:' + h + 'px'}, box);
            var cx = w / 2, cy = h / 2, ro = Math.min(w, h) / 2 * 0.9, ri = ro * (opts.inner || 0.705);
            if (opts.center) {
                var ct = svgEl('text', {x: cx, y: cy - 4, 'text-anchor': 'middle', 'font-size': Math.max(16, ri * 0.34),
                    'font-weight': 800, fill: color(box, 'primary')}, svg);
                ct.textContent = opts.center;
                var cl = svgEl('text', {x: cx, y: cy + Math.max(14, ri * 0.22), 'text-anchor': 'middle', 'font-size': 11.5,
                    'font-weight': 600, fill: color(box, 'muted'), 'letter-spacing': '0.04em'}, svg);
                cl.textContent = opts.centerlabel || '';
            }
            var tip = tooltip(box);
            var angle = 0;
            var arcs = [];
            items.forEach(function(it, i) {
                var v = Number(it.value) || 0;
                if (!v) {
                    return;
                }
                var a0 = angle, a1 = angle + v / total * Math.PI * 2;
                angle = a1;
                var g = svgEl('path', {fill: cols[i], stroke: '#fff', 'stroke-width': 2, class: 'lbi-hit'}, svg);
                var draw = function(grow) {
                    var r1 = ro + (grow ? 5 : 0);
                    var full = (a1 - a0) >= Math.PI * 2 - 1e-6;
                    if (full) {
                        g.setAttribute('d', 'M' + (cx + r1) + ',' + cy + 'A' + r1 + ',' + r1 + ' 0 1,0 ' + (cx - r1) + ',' + cy +
                            'A' + r1 + ',' + r1 + ' 0 1,0 ' + (cx + r1) + ',' + cy + 'M' + (cx + ri) + ',' + cy +
                            'A' + ri + ',' + ri + ' 0 1,1 ' + (cx - ri) + ',' + cy + 'A' + ri + ',' + ri + ' 0 1,1 ' +
                            (cx + ri) + ',' + cy + 'Z');
                        g.setAttribute('fill-rule', 'evenodd');
                        return;
                    }
                    // Counter-clockwise from 3 o'clock like recharts.
                    var p = function(r, a) {
                        return [cx + r * Math.cos(a), cy - r * Math.sin(a)];
                    };
                    var large = (a1 - a0) > Math.PI ? 1 : 0;
                    var s0 = p(r1, a0), s1 = p(r1, a1), e0 = p(ri, a1), e1 = p(ri, a0);
                    g.setAttribute('d', 'M' + s0.join(',') + 'A' + r1 + ',' + r1 + ' 0 ' + large + ',0 ' + s1.join(',') +
                        'L' + e0.join(',') + 'A' + ri + ',' + ri + ' 0 ' + large + ',1 ' + e1.join(',') + 'Z');
                };
                draw(false);
                arcs.push(g);
                var show = function(e) {
                    var pp = pos(box, e);
                    box.classList.add('is-hovering');
                    g.classList.add('is-hover');
                    draw(true);
                    tip.show([{text: it.label + ' : ' + fmt(v), color: cols[i]}], pp[0], pp[1]);
                };
                g.addEventListener('mousemove', show);
                g.addEventListener('touchstart', show, {passive: true});
                g.addEventListener('mouseleave', function() {
                    box.classList.remove('is-hovering');
                    g.classList.remove('is-hover');
                    draw(false);
                    tip.hide();
                });
            });
            svg.classList.add('lbi-fade-in');
            legend(box, items.map(function(it, i) {
                return {label: it.label, color: cols[i]};
            }), true);
        });
    }

    /**
     * Funnel chart (trapezoids, last stage narrows to a point).
     *
     * @param {HTMLElement} box
     * @param {Object} data {stages: [{label, value}]}
     * @param {Object} opts {colors}
     */
    function funnel(box, data, opts) {
        opts = opts || {};
        var stages = data.stages || [];
        var max = Math.max.apply(null, stages.map(function(s) {
            return Number(s.value) || 0;
        }).concat([0]));
        if (!stages.length || !max) {
            empty(box);
            return;
        }
        var colors = opts.colors || ['chart1', 'chart2', 'chart3', 'chart4'];
        mount(box, function(w) {
            var h = box.clientHeight || 190;
            var longest = Math.max.apply(null, stages.map(function(s) {
                return String(s.label).length;
            }));
            var labelw = Math.min(Math.max(70, longest > 12 ? 92 : longest * 7 + 16), w * 0.34);
            var area = w - labelw;
            var maxw = area * 0.86;
            var cx = area / 2 + 6;
            var pad = 5, sh = (h - pad * 2) / stages.length;
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, role: 'img'}, box);
            var tip = tooltip(box);
            var fg = color(box, 'fg');
            var widths = stages.map(function(s) {
                return (Number(s.value) || 0) / max * maxw;
            });
            stages.forEach(function(s, i) {
                var top = widths[i], bottom = i + 1 < stages.length ? widths[i + 1] : 0;
                var y0 = pad + i * sh, y1 = y0 + sh;
                var c = color(box, colors[i % colors.length]);
                var poly = svgEl('path', {
                    d: 'M' + (cx - top / 2) + ',' + y0 + 'L' + (cx + top / 2) + ',' + y0 + 'L' + (cx + bottom / 2) + ',' + y1 +
                        'L' + (cx - bottom / 2) + ',' + y1 + 'Z',
                    fill: c, class: 'lbi-hit'
                }, svg);
                var midy = (y0 + y1) / 2;
                var vt = svgEl('text', {x: cx, y: i === stages.length - 1 ? y0 + sh * 0.4 : midy, 'text-anchor': 'middle',
                    'dominant-baseline': 'middle', 'font-size': 12.5, fill: '#fff', 'pointer-events': 'none'}, svg);
                vt.textContent = String(Number(s.value) || 0);
                var lines = wrap(s.label, 12);
                var lx = cx + (top + bottom) / 4 + 6;
                var lt = svgEl('text', {x: lx, y: midy - (lines.length - 1) * 7, 'font-size': 12.5, fill: fg,
                    'dominant-baseline': 'middle'}, svg);
                lines.forEach(function(l, k) {
                    var ts = svgEl('tspan', {x: lx, dy: k === 0 ? 0 : 14}, lt);
                    ts.textContent = l;
                });
                var show = function(e) {
                    var p = pos(box, e);
                    box.classList.add('is-hovering');
                    poly.classList.add('is-hover');
                    tip.show([{text: s.label + ' : ' + (Number(s.value) || 0)}], p[0], p[1]);
                };
                poly.addEventListener('mousemove', show);
                poly.addEventListener('touchstart', show, {passive: true});
                poly.addEventListener('mouseleave', function() {
                    box.classList.remove('is-hovering');
                    poly.classList.remove('is-hover');
                    tip.hide();
                });
            });
            svg.classList.add('lbi-fade-in');
        });
    }

    /**
     * Horizontal bars (stacked when several series).
     *
     * @param {HTMLElement} box
     * @param {Object} data {categories, series: [{label, color, values}]}
     * @param {Object} opts {suffix}
     */
    function hbar(box, data, opts) {
        opts = opts || {};
        var cats = data.categories || [], series = data.series || [];
        if (!cats.length) {
            empty(box);
            return;
        }
        var multi = series.length > 1;
        var grouped = !!opts.grouped;
        mount(box, function(w) {
            var h = (box.clientHeight || 200) - (multi ? 28 : 0);
            var cols = series.map(function(s) {
                return color(box, s.color);
            });
            var totals = cats.map(function(c, i) {
                return series.reduce(function(sum, s) {
                    return sum + (Number(s.values[i]) || 0);
                }, 0);
            });
            var maxcol = (multi && !grouped) ? Math.max.apply(null, totals) : Math.max.apply(null, [].concat.apply([],
                series.map(function(s) {
                    return s.values.map(Number);
                })));
            var sc = niceScale(maxcol, 4);
            var labelw = Math.min(130, Math.max(80, w * 0.27));
            var m = {l: labelw, r: 12, t: 4, b: 22};
            var iw = Math.max(10, w - m.l - m.r), ih = Math.max(10, h - m.t - m.b);
            var band = ih / cats.length, bh = Math.min(26, band * 0.62);
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, role: 'img', style: 'height:' + h + 'px'}, box);
            var muted = color(box, 'muted'), grid = color(box, 'border'), fg = color(box, 'fg');
            var x = function(v) {
                return m.l + v / sc.max * iw;
            };
            for (var t = 0; t <= 4; t++) {
                var xv = x(sc.step * t);
                svgEl('line', {x1: xv, x2: xv, y1: m.t, y2: m.t + ih, stroke: grid, 'stroke-dasharray': t ? '3 3' : ''}, svg);
                var tl = svgEl('text', {x: xv, y: m.t + ih + 15, 'text-anchor': 'middle', 'font-size': 11.5, fill: muted}, svg);
                tl.textContent = fmt(sc.step * t);
            }
            var tip = tooltip(box);
            cats.forEach(function(cat, i) {
                var cy = m.t + band * i + band / 2;
                var lines = wrap(cat, Math.max(10, Math.floor(labelw / 7.2)));
                var lt = svgEl('text', {x: m.l - 8, y: cy - (lines.length - 1) * 6.5, 'text-anchor': 'end', 'font-size': 11.5,
                    fill: muted, 'dominant-baseline': 'middle'}, svg);
                lines.forEach(function(l, k) {
                    var ts = svgEl('tspan', {x: m.l - 8, dy: k === 0 ? 0 : 13}, lt);
                    ts.textContent = l;
                });
                var g = svgEl('g', {class: 'lbi-hit'}, svg);
                var band0 = svgEl('rect', {x: m.l, y: cy - band / 2, width: iw, height: band, fill: '#000', 'fill-opacity': 0}, g);
                if (grouped) {
                    var gh = Math.min(13, (band * 0.84) / series.length);
                    series.forEach(function(s, k) {
                        var v = Number(s.values[i]) || 0;
                        if (!v) {
                            return;
                        }
                        var y0 = cy - (gh * series.length) / 2 + k * gh + 0.5, x0 = m.l, x1 = x(v), hh = gh - 1;
                        var r = Math.min(hh / 2, Math.max(0, x1 - x0) / 2);
                        svgEl('path', {d: 'M' + x0 + ',' + y0 + 'H' + (x1 - r) + 'A' + r + ',' + r + ' 0 0 1 ' + (x1 - r) + ',' +
                            (y0 + hh) + 'H' + x0 + 'Z', fill: cols[k]}, g);
                    });
                    var gshow = function(e) {
                        var p = pos(box, e);
                        band0.setAttribute('fill-opacity', 0.06);
                        var rows = [{text: cat}];
                        series.forEach(function(s, k) {
                            rows.push({text: s.label + ' : ' + fmt(s.values[i]) + (opts.suffix || ''), textcolor: cols[k]});
                        });
                        tip.show(rows, p[0], p[1]);
                    };
                    g.addEventListener('mousemove', gshow);
                    g.addEventListener('touchstart', gshow, {passive: true});
                    g.addEventListener('mouseleave', function() {
                        band0.setAttribute('fill-opacity', 0);
                        tip.hide();
                    });
                    return;
                }
                var acc = 0;
                series.forEach(function(s, k) {
                    var v = Number(s.values[i]) || 0;
                    if (!v) {
                        return;
                    }
                    var x0 = x(acc), x1 = x(acc + v);
                    acc += v;
                    var wv = Math.max(0, x1 - x0);
                    if (!multi) {
                        var r = Math.min(6, bh / 2, wv);
                        svgEl('path', {d: 'M' + x0 + ',' + (cy - bh / 2) + 'H' + (x1 - r) + 'Q' + x1 + ',' + (cy - bh / 2) + ' ' + x1 +
                            ',' + (cy - bh / 2 + r) + 'V' + (cy + bh / 2 - r) + 'Q' + x1 + ',' + (cy + bh / 2) + ' ' + (x1 - r) + ',' +
                            (cy + bh / 2) + 'H' + x0 + 'Z', fill: cols[k]}, g);
                    } else {
                        svgEl('rect', {x: x0, y: cy - bh / 2, width: wv, height: bh, fill: cols[k]}, g);
                    }
                });
                svgEl('rect', {x: m.l, y: cy - band / 2, width: iw, height: band, fill: 'transparent'}, g);
                var show = function(e) {
                    var p = pos(box, e);
                    box.classList.add('is-hovering');
                    g.classList.add('is-hover');
                    var rows = [{text: cat, bold: true}];
                    series.forEach(function(s, k) {
                        rows.push({text: s.label + ' : ' + fmt(s.values[i]) + (opts.suffix || ''), color: cols[k]});
                    });
                    tip.show(rows, p[0], p[1]);
                };
                g.addEventListener('mousemove', show);
                g.addEventListener('touchstart', show, {passive: true});
                g.addEventListener('mouseleave', function() {
                    box.classList.remove('is-hovering');
                    g.classList.remove('is-hover');
                    tip.hide();
                });
            });
            svg.classList.add('lbi-fade-in');
            if (multi) {
                legend(box, series.map(function(s, k) {
                    return {label: s.label, color: cols[k]};
                }), true);
            }
        });
    }

    /**
     * Grouped vertical bars with rounded tops.
     *
     * @param {HTMLElement} box
     * @param {Object} data {categories, series}
     */
    function vbar(box, data, opts) {
        opts = opts || {};
        var cats = data.categories || [], series = data.series || [];
        var any = series.some(function(s) {
            return s.values.some(function(v) {
                return Number(v) > 0;
            });
        });
        if (!cats.length || !any) {
            empty(box);
            return;
        }
        mount(box, function(w) {
            var h = (box.clientHeight || 170) - (opts.nolegend ? 0 : 28);
            var cols = series.map(function(s) {
                return color(box, s.color);
            });
            var max = 0;
            series.forEach(function(s) {
                s.values.forEach(function(v) {
                    max = Math.max(max, Number(v) || 0);
                });
            });
            var sc = niceScale(max, 4);
            var m = {l: 38, r: 4, t: 6, b: opts.rotate ? 36 : 22};
            var iw = Math.max(10, w - m.l - m.r), ih = Math.max(10, h - m.t - m.b);
            var band = iw / cats.length, gw = band * 0.72, bw = Math.min(opts.barwidth || 22, gw / series.length);
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, role: 'img', style: 'height:' + h + 'px'}, box);
            var muted = color(box, 'muted'), grid = color(box, 'border');
            var y = function(v) {
                return m.t + ih - v / sc.max * ih;
            };
            for (var t = 0; t <= 4; t++) {
                var yv = y(sc.step * t);
                svgEl('line', {x1: m.l, x2: m.l + iw, y1: yv, y2: yv, stroke: grid, 'stroke-dasharray': t ? '3 3' : ''}, svg);
                var tl = svgEl('text', {x: m.l - 7, y: yv, 'text-anchor': 'end', 'dominant-baseline': 'middle', 'font-size': 11.5,
                    fill: muted}, svg);
                tl.textContent = fmt(sc.step * t);
            }
            var tip = tooltip(box);
            var every = Math.max(1, Math.ceil(cats.length / Math.max(2, Math.floor(iw / 34))));
            cats.forEach(function(cat, i) {
                var cx = m.l + band * i + band / 2;
                if (opts.rotate) {
                    var rl = svgEl('text', {x: cx, y: m.t + ih + 14, 'text-anchor': 'end', 'font-size': 11.5, fill: muted,
                        transform: 'rotate(-35 ' + cx + ' ' + (m.t + ih + 14) + ')'}, svg);
                    rl.textContent = cat;
                } else if (i % every === 0 || i === cats.length - 1) {
                    var xl = svgEl('text', {x: cx, y: m.t + ih + 15, 'text-anchor': 'middle', 'font-size': 11.5, fill: muted}, svg);
                    xl.textContent = cat;
                }
                var g = svgEl('g', {class: 'lbi-hit'}, svg);
                svgEl('rect', {x: cx - band / 2, y: m.t, width: band, height: ih, fill: 'transparent'}, g);
                var start = cx - (bw * series.length) / 2;
                series.forEach(function(s, k) {
                    var v = Number(s.values[i]) || 0;
                    if (!v) {
                        return;
                    }
                    var x0 = start + k * bw + 1, x1 = x0 + bw - 2, y0 = y(v), yb = y(0);
                    var r = Math.min(6, (x1 - x0) / 2, yb - y0);
                    svgEl('path', {d: 'M' + x0 + ',' + yb + 'V' + (y0 + r) + 'Q' + x0 + ',' + y0 + ' ' + (x0 + r) + ',' + y0 +
                        'H' + (x1 - r) + 'Q' + x1 + ',' + y0 + ' ' + x1 + ',' + (y0 + r) + 'V' + yb + 'Z', fill: cols[k]}, g);
                });
                var show = function(e) {
                    var p = pos(box, e);
                    box.classList.add('is-hovering');
                    g.classList.add('is-hover');
                    var rows = [{text: cat, bold: true}];
                    series.forEach(function(s, k) {
                        rows.push({text: s.label + ' : ' + fmt(s.values[i]), color: cols[k]});
                    });
                    tip.show(rows, p[0], p[1]);
                };
                g.addEventListener('mousemove', show);
                g.addEventListener('touchstart', show, {passive: true});
                g.addEventListener('mouseleave', function() {
                    box.classList.remove('is-hovering');
                    g.classList.remove('is-hover');
                    tip.hide();
                });
            });
            svg.classList.add('lbi-fade-in');
            if (!opts.nolegend) {
                legend(box, series.map(function(s, k) {
                    return {label: s.label, color: cols[k]};
                }), true);
            }
        });
    }

    /**
     * Progress row (label, right text, bar).
     *
     * @param {String} name
     * @param {String} right
     * @param {Number} pct 0-100
     * @param {String} fillclass
     * @return {HTMLElement}
     */
    function barRow(name, right, pct, fillclass) {
        var wrapEl = document.createElement('div');
        var head = document.createElement('div');
        head.className = 'lbi-barrow-head';
        var n = document.createElement('span');
        n.className = 'lbi-barrow-name';
        n.textContent = name;
        n.title = name;
        var r = document.createElement('span');
        r.className = fillclass ? 'lbi-muted' : '';
        r.textContent = right;
        head.appendChild(n);
        head.appendChild(r);
        var track = document.createElement('div');
        track.className = 'lbi-track';
        var fill = document.createElement('div');
        fill.className = 'lbi-fill' + (fillclass ? ' ' + fillclass : '');
        track.appendChild(fill);
        wrapEl.appendChild(head);
        wrapEl.appendChild(track);
        window.requestAnimationFrame(function() {
            window.requestAnimationFrame(function() {
                fill.style.width = Math.max(0, Math.min(100, pct)) + '%';
            });
        });
        return wrapEl;
    }

    /**
     * Average rating bars (out of 5).
     *
     * @param {HTMLElement} box
     * @param {Array} items [{label, value}]
     */
    function ratings(box, items) {
        box.innerHTML = '';
        var list = document.createElement('div');
        list.className = 'lbi-ratings lbi-fade-in';
        (items || []).forEach(function(it) {
            var v = it.value === null || it.value === undefined ? null : Number(it.value);
            list.appendChild(barRow(it.label, (v === null ? '–' : v.toFixed(1)) + ' ' + strings.outof5,
                v === null ? 0 : v / 5 * 100, ''));
        });
        box.appendChild(list);
        var none = (items || []).every(function(it) {
            return it.value === null || it.value === undefined;
        });
        if (none) {
            var p = document.createElement('p');
            p.className = 'lbi-report-desc';
            p.textContent = strings.norating;
            box.appendChild(p);
        }
    }

    /**
     * Ranked weak-competency list.
     *
     * @param {HTMLElement} box
     * @param {Array} rows [{rank, name, avg, users}]
     */
    function weaklist(box, rows) {
        box.innerHTML = '';
        if (!rows || !rows.length) {
            empty(box);
            return;
        }
        var list = document.createElement('div');
        list.className = 'lbi-weaklist lbi-fade-in';
        rows.forEach(function(r) {
            var row = document.createElement('div');
            row.className = 'lbi-weakrow';
            var rank = document.createElement('span');
            rank.className = 'lbi-rank';
            rank.textContent = r.rank;
            var body = document.createElement('div');
            body.className = 'lbi-weakbody';
            body.appendChild(barRow(r.name, r.avg + ' · ' + fmt(r.users) + ' ' + strings.users, r.avg, 'is-destructive'));
            row.appendChild(rank);
            row.appendChild(body);
            list.appendChild(row);
        });
        box.appendChild(list);
    }

    /**
     * Animate a numeric SVG attribute.
     *
     * @param {Element} node
     * @param {String} attr
     * @param {Number} from
     * @param {Number} to
     * @param {Number} ms
     * @param {Function} step optional callback(value) per frame
     */
    function tween(node, attr, from, to, ms, step) {
        var start = null;
        var ease = function(t) {
            return 1 - Math.pow(1 - t, 3);
        };
        var frame = function(ts) {
            if (start === null) {
                start = ts;
            }
            var t = Math.min(1, (ts - start) / ms);
            var v = from + (to - from) * ease(t);
            node.setAttribute(attr, v);
            if (step) {
                step(v);
            }
            if (t < 1) {
                window.requestAnimationFrame(frame);
            }
        };
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            node.setAttribute(attr, to);
            if (step) {
                step(to);
            }
            return;
        }
        window.requestAnimationFrame(frame);
    }

    /**
     * Zone bar chart: one row per item on a soft red / yellow / green track; the animated bar takes the colour of the
     * zone its value falls in (Alarming < low, Moderate low-goal, On Target >= goal), value printed inside the bar,
     * optional counts on the right and a boxed zone legend.
     *
     * @param {HTMLElement} box
     * @param {Object} data {rows: [{label, value (0-100), done, total, meta: [{label, value}]}]}
     * @param {Object} opts {low: 40, goal: 70, pctlabel, donelabel}
     */
    function bullet(box, data, opts) {
        opts = opts || {};
        var rows = data.rows || [];
        if (!rows.length) {
            empty(box);
            return;
        }
        var low = opts.low === undefined ? 40 : opts.low;
        var goal = opts.goal === undefined ? 70 : opts.goal;
        mount(box, function(w) {
            var small = w < 560;
            var h = (box.clientHeight || 200) - (small ? 92 : 64);
            var labelw = Math.min(190, Math.max(86, w * 0.19));
            var hasmeta = rows.some(function(r) {
                return r.meta && r.meta.length;
            });
            var metaw = hasmeta ? Math.min(130, Math.max(74, w * 0.12)) : 0;
            var m = {l: labelw, r: 14 + metaw, t: 8, b: 30};
            var iw = Math.max(10, w - m.l - m.r), ih = Math.max(10, h - m.t - m.b);
            var band = ih / rows.length;
            var th = Math.max(14, Math.min(34, band * 0.7));
            var rad = Math.min(7, th / 2);
            var x = function(v) {
                return m.l + Math.max(0, Math.min(100, v)) / 100 * iw;
            };
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, role: 'img', style: 'height:' + h + 'px'}, box);
            var red = color(box, 'destructive'), yellow = color(box, 'warning'), green = color(box, 'success');
            var primary = color(box, 'primary'), accent = color(box, 'accent'), muted = color(box, 'muted');
            var defs = svgEl('defs', {}, svg);
            var tip = tooltip(box);
            var metacols = [primary, accent, color(box, 'chart3')];
            var zone = function(v) {
                return v >= goal ? green : (v >= low ? yellow : red);
            };

            rows.forEach(function(r, i) {
                var cy = m.t + band * i + band / 2;
                var ty = cy - th / 2;
                var g = svgEl('g', {class: 'lbi-hit'}, svg);
                var hl = svgEl('rect', {x: 0, y: cy - band / 2, width: w, height: band, rx: 8, fill: primary,
                    'fill-opacity': 0}, g);

                // Row label.
                var lines = wrap(r.label, Math.max(10, Math.floor(labelw / 8)));
                var lt = svgEl('text', {x: m.l - 12, y: cy - (lines.length - 1) * 7, 'text-anchor': 'end',
                    'font-size': small ? 12 : 13.5, 'font-weight': 700, fill: primary, 'dominant-baseline': 'middle'}, g);
                lines.forEach(function(l, k) {
                    var ts = svgEl('tspan', {x: m.l - 12, dy: k === 0 ? 0 : 14}, lt);
                    ts.textContent = l;
                });

                // Soft zone track.
                var cid = 'lbi-bc-' + (++uid);
                var clip = svgEl('clipPath', {id: cid}, defs);
                svgEl('rect', {x: m.l, y: ty, width: iw, height: th, rx: rad}, clip);
                var zones = svgEl('g', {'clip-path': 'url(#' + cid + ')'}, g);
                svgEl('rect', {x: m.l, y: ty, width: x(low) - m.l, height: th, fill: red, 'fill-opacity': 0.12}, zones);
                svgEl('rect', {x: x(low), y: ty, width: x(goal) - x(low), height: th, fill: yellow, 'fill-opacity': 0.18},
                    zones);
                svgEl('rect', {x: x(goal), y: ty, width: x(100) - x(goal), height: th, fill: green, 'fill-opacity': 0.13},
                    zones);

                // Value bar in the colour of its zone (animated) + value.
                var v = Math.max(0, Math.min(100, Number(r.value) || 0));
                var bw = x(v) - m.l;
                var fill = zone(v);
                var bar = svgEl('rect', {x: m.l, y: ty, width: 0, height: th, rx: rad, fill: fill}, g);
                var label = svgEl('text', {y: cy + 0.5, 'dominant-baseline': 'central',
                    'font-size': Math.min(small ? 12 : 15, th * 0.55), 'font-weight': 700}, g);
                var vtxt = (Math.round(v * 10) / 10) + '%';
                label.textContent = vtxt;
                var inside = bw > vtxt.length * 9 + 22;
                var place = function(cur) {
                    if (inside) {
                        label.setAttribute('x', m.l + cur - 14);
                        label.setAttribute('text-anchor', 'end');
                        label.setAttribute('fill', '#fff');
                    } else {
                        label.setAttribute('x', m.l + cur + 8);
                        label.setAttribute('text-anchor', 'start');
                        label.setAttribute('fill', v > 0 ? fill : muted);
                    }
                };
                place(0);
                tween(bar, 'width', 0, v > 0 ? Math.max(bw, rad * 2) : 0, 900 + i * 60, place);

                // Counts on the right (e.g. Users / Courses).
                var meta = r.meta || [];
                meta.forEach(function(mt, k) {
                    var my = cy + (k - (meta.length - 1) / 2) * 16;
                    var mtx = svgEl('text', {x: w - 6, y: my, 'text-anchor': 'end', 'dominant-baseline': 'central',
                        'font-size': small ? 11 : 12.5}, g);
                    var ml = svgEl('tspan', {fill: primary}, mtx);
                    ml.textContent = mt.label + ' ';
                    var mv = svgEl('tspan', {fill: metacols[k % 3], 'font-weight': 700}, mtx);
                    mv.textContent = fmt(mt.value);
                });

                var show = function(e) {
                    var p = pos(box, e);
                    hl.setAttribute('fill-opacity', 0.04);
                    var rowsTip = [{text: r.label, bold: true},
                        {text: (opts.pctlabel || 'Completion %') + ' : ' + vtxt, textcolor: fill}];
                    meta.forEach(function(mt, k) {
                        rowsTip.push({text: mt.label + ' : ' + fmt(mt.value), textcolor: metacols[k % 3]});
                    });
                    if (r.total !== undefined) {
                        rowsTip.push({text: (opts.donelabel || 'Completed') + ' : ' + fmt(r.done) + ' / ' + fmt(r.total)});
                    }
                    tip.show(rowsTip, p[0], p[1]);
                };
                g.addEventListener('mousemove', show);
                g.addEventListener('touchstart', show, {passive: true});
                g.addEventListener('mouseleave', function() {
                    hl.setAttribute('fill-opacity', 0);
                    tip.hide();
                });
            });

            // Bottom scale.
            [0, low, goal, 100].forEach(function(t) {
                var anchor = t === 0 ? 'start' : (t === 100 ? 'end' : 'middle');
                var tx = svgEl('text', {x: x(t), y: h - 8, 'text-anchor': anchor, 'font-size': small ? 11 : 13,
                    'font-weight': 600, fill: primary, 'fill-opacity': 0.75}, svg);
                tx.textContent = t + '%';
            });
            svg.classList.add('lbi-fade-in');

            // Boxed zone legend.
            var lg = document.createElement('div');
            lg.className = 'lbi-zonelegend';
            [['< ' + low + '%', red, strings.chart_alarming || 'Alarming'],
                [low + '% – ' + goal + '%', yellow, strings.chart_moderate || 'Moderate'],
                ['≥ ' + goal + '%', green, strings.chart_ontarget || 'On Target']].forEach(function(z) {
                var it = document.createElement('span');
                it.className = 'lbi-zonelegend-item';
                var rg = document.createElement('span');
                rg.className = 'lbi-zonelegend-range';
                rg.textContent = z[0];
                var dot = document.createElement('span');
                dot.className = 'lbi-zonelegend-dot';
                dot.style.background = z[1];
                var nm = document.createElement('span');
                nm.className = 'lbi-zonelegend-name';
                nm.textContent = z[2];
                it.appendChild(rg);
                it.appendChild(dot);
                it.appendChild(nm);
                lg.appendChild(it);
            });
            box.appendChild(lg);
        });
    }

    /**
     * Soft grouped bars: one row per category with a light rounded track and one animated, rounded bar per series
     * (value printed at the bar end). Same data shape as hbar, new visual design.
     *
     * @param {HTMLElement} box
     * @param {Object} data {categories, series: [{label, color, values}]}
     * @param {Object} opts {suffix}
     */
    function softbars(box, data, opts) {
        opts = opts || {};
        var cats = data.categories || [], series = data.series || [];
        if (!cats.length) {
            empty(box);
            return;
        }
        mount(box, function(w) {
            var h = (box.clientHeight || 200) - 34;
            var cols = series.map(function(s) {
                return color(box, s.color);
            });
            var maxv = Math.max.apply(null, [1].concat.apply([1], series.map(function(s) {
                return s.values.map(function(v) {
                    return Number(v) || 0;
                });
            })));
            var sc = niceScale(maxv, 4);
            var labelw = Math.min(170, Math.max(90, w * 0.2));
            var m = {l: labelw, r: 40, t: 6, b: 24};
            var iw = Math.max(10, w - m.l - m.r), ih = Math.max(10, h - m.t - m.b);
            var band = ih / cats.length;
            var th = Math.min(34, band * 0.78);
            var bh = Math.max(6, Math.min(12, (th - 8) / Math.max(1, series.length) - 2));
            var x = function(v) {
                return m.l + Math.max(0, v) / sc.max * iw;
            };
            var svg = svgEl('svg', {viewBox: '0 0 ' + w + ' ' + h, role: 'img', style: 'height:' + h + 'px'}, box);
            var muted = color(box, 'muted'), grid = color(box, 'border'), primary = color(box, 'primary');
            var tip = tooltip(box);
            for (var t = 0; t <= 4; t++) {
                var xv = x(sc.step * t);
                svgEl('line', {x1: xv, x2: xv, y1: m.t, y2: m.t + ih, stroke: grid, 'stroke-dasharray': '3 4',
                    'stroke-opacity': t ? 0.8 : 0}, svg);
                var tl = svgEl('text', {x: xv, y: m.t + ih + 16, 'text-anchor': 'middle', 'font-size': 11.5, fill: muted}, svg);
                tl.textContent = fmt(sc.step * t);
            }
            cats.forEach(function(cat, i) {
                var cy = m.t + band * i + band / 2;
                var g = svgEl('g', {class: 'lbi-hit'}, svg);
                var hl = svgEl('rect', {x: 0, y: cy - band / 2, width: w, height: band, rx: 8, fill: primary,
                    'fill-opacity': 0}, g);
                var lines = wrap(cat, Math.max(10, Math.floor(labelw / 7.2)));
                var lt = svgEl('text', {x: m.l - 10, y: cy - (lines.length - 1) * 6.5, 'text-anchor': 'end', 'font-size': 12,
                    fill: muted, 'dominant-baseline': 'middle'}, g);
                lines.forEach(function(l, k) {
                    var ts = svgEl('tspan', {x: m.l - 10, dy: k === 0 ? 0 : 13}, lt);
                    ts.textContent = l;
                });
                // Soft track.
                svgEl('rect', {x: m.l, y: cy - th / 2, width: iw, height: th, rx: Math.min(10, th / 2), fill: primary,
                    'fill-opacity': 0.06}, g);
                var gap = 3, total = series.length * bh + (series.length - 1) * gap;
                series.forEach(function(s, k) {
                    var v = Number(s.values[i]) || 0;
                    var y0 = cy - total / 2 + k * (bh + gap);
                    var bw = x(v) - m.l;
                    var bar = svgEl('rect', {x: m.l + 4, y: y0, width: 0, height: bh, rx: bh / 2, fill: cols[k]}, g);
                    var lbl = svgEl('text', {x: m.l + 8, y: y0 + bh / 2, 'dominant-baseline': 'central', 'font-size': 11,
                        'font-weight': 700, fill: cols[k]}, g);
                    lbl.textContent = fmt(v) + (opts.suffix || '');
                    var target = v > 0 ? Math.max(bh, bw - 4) : 0;
                    tween(bar, 'width', 0, target, 900 + i * 50 + k * 120, function(cur) {
                        lbl.setAttribute('x', m.l + 4 + cur + 5);
                    });
                });
                var show = function(e) {
                    var p = pos(box, e);
                    hl.setAttribute('fill-opacity', 0.05);
                    var rows = [{text: cat, bold: true}];
                    series.forEach(function(s, k) {
                        rows.push({text: s.label + ' : ' + fmt(s.values[i]) + (opts.suffix || ''), textcolor: cols[k]});
                    });
                    tip.show(rows, p[0], p[1]);
                };
                g.addEventListener('mousemove', show);
                g.addEventListener('touchstart', show, {passive: true});
                g.addEventListener('mouseleave', function() {
                    hl.setAttribute('fill-opacity', 0);
                    tip.hide();
                });
            });
            svg.classList.add('lbi-fade-in');
            legend(box, series.map(function(s, k) {
                return {label: s.label, color: cols[k]};
            }), true);
        });
    }

    return {
        ICONS: ICONS,
        setStrings: function(s) {
            Object.keys(s || {}).forEach(function(k) {
                strings[k] = s[k];
            });
        },
        fmt: fmt,
        sparkline: sparkline,
        line: line,
        donut: donut,
        funnel: funnel,
        hbar: hbar,
        vbar: vbar,
        bullet: bullet,
        softbars: softbars,
        ratings: ratings,
        weaklist: weaklist,
        empty: empty
    };
});

/**
 * Hall of Fame v2 – filters AMD module.
 * AJAX-powered filtering with debounce.
 * Uses native CustomEvent (no jQuery dependency).
 *
 * @module local_halloffame/filters
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    var _tab     = 'awards';
    var _filters = {};
    var _timer   = null;
    var DEBOUNCE = 380;

    function init(opts) {
        _tab     = (opts && opts.tab) ? opts.tab : 'awards';
        _filters = {};

        document.addEventListener('change', function(e) {
            var el = e.target;
            if (!el || !el.dataset || !el.dataset.filter) return;

            var key = el.dataset.filter;
            var val = el.value;

            if (val) { _filters[key] = val; }
            else     { delete _filters[key]; }

            // Month and quarter are mutually exclusive.
            if (key === 'month' && val) {
                delete _filters.quarter;
                var qEl = document.getElementById('filterQuarter');
                if (qEl) qEl.value = '';
            }
            if (key === 'quarter' && val) {
                delete _filters.month;
                var mEl = document.getElementById('filterMonth');
                if (mEl) mEl.value = '';
            }

            clearTimeout(_timer);
            _timer = setTimeout(applyFilters, DEBOUNCE);
        });
    }

    function applyFilters() {
        var method = _tab === 'awards'
            ? 'local_halloffame_get_awards'
            : 'local_halloffame_get_achievements';

        var content = document.getElementById('hofContent');
        if (content) content.classList.add('hof-loading');

        Ajax.call([{
            methodname: method,
            args: {filters: Object.assign({}, _filters)}
        }])[0].then(function(items) {
            if (content) content.classList.remove('hof-loading');
            document.dispatchEvent(new CustomEvent('halloffame:filtered', {
                detail: {tab: _tab, items: items}
            }));
        }).catch(function(err) {
            if (content) content.classList.remove('hof-loading');
            Notification.exception(err);
        });
    }

    return {init: init, applyFilters: applyFilters};
});

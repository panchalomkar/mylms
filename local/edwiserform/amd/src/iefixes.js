// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edwiser Form iefixes js
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
define([], function() {
    const assign = Object.assign || function assign(obj, ...argss) {
        let len;
        let args;
        let key;
        for (len = argss.length, args = Array(len > 1 ? len - 1 : 0), key = 1; key < len; key++) {
            args[key - 1] = argss[key];
        }

        if (typeof obj == 'object' && args.length > 0) {
            args.forEach(function(arg) {
                if (typeof arg == 'object') {
                    Object.keys(arg).forEach(function(key) {
                        obj[key] = arg[key];
                    });
                }
            });
        }

        return obj;
    };
    if (typeof window.CustomEvent === 'function') {
        return false;
    }

    /**
     * In IE if Custom Event function is not define
     * @param {String} event  Event name
     * @param {Array}  params Event parameters
     * @return {Event}        Event object
     */
    function CustomEvent(event, params) {
        params = assign({bubbles: false, cancelable: false, detail: undefined}, params);
        const evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;

    return true;
});

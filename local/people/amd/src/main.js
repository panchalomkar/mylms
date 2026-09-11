/**
 * People Plugin - AMD Compatibility Shim
 * The main functionality has been moved to /js/people.js (plain JavaScript).
 * This AMD module is kept for Moodle's AMD loader compatibility only.
 *
 * @module local_people/main
 * @package local_people
 * @copyright 2024 People Plugin
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function () {
    'use strict';
    return {
        init: function (params) {
            // Store modal params for plain JS to access.
            if (params && window.PEOPLE_PLUGIN) {
                window.PEOPLE_PLUGIN = Object.assign(window.PEOPLE_PLUGIN, params.modal || {});
            }
        }
    };
});

/* eslint-disable no-console */
/* eslint-disable no-unused-vars */
/* eslint-disable max-len */
/* eslint-disable jsdoc/require-jsdoc */
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
 * @module     local_sitesync/main
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export const init = () => {
    return true;
};
export const alerthtml = (message, alertclass) => {
    let html = `<div class="alert alert-${alertclass} alert-block fade in  alert-dismissible" role="alert"
                    data-aria-autofocus="true">

                    <h5 class="h-semibold-5 mb-0">${message}</h5>
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true" class="edw-icon edw-icon-Cancel large"></span>
                        <span class="sr-only">Dismiss this notification</span>
                    </button>

                </div>`;
    return html;
};

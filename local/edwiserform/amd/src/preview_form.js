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
 * Edwiser Form preview_form js
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
define([
    'jquery',
    "local_edwiserform/form_styles",
    'local_edwiserform/iefixes',
    'local_edwiserform/formviewer'
], function($, formStyles) {
    return {
        init: function(title, sitekey) {
            $(document).ready(function() {
                $('body').addClass('edwiserform-fullpage');
                if (typeof formdefinition != 'undefined') {
                    let form = $('#preview-form')[0];
                    let formeoOpts = {
                        container: form,
                        // eslint-disable-next-line no-undef
                        countries: countries,
                        sitekey: sitekey,
                        localStorage: false, // Changed from session storage to local storage.
                    };
                    // eslint-disable-next-line no-undef
                    var formeo = new Formeo(formeoOpts, formdefinition);
                    formeo.render(form);
                    $(form).prepend('<h2>' + title + '</h2>');
                    // eslint-disable-next-line no-undef
                    formStyles.apply($(form).find('.formeo-render'), 'add', style);
                }
            });
        }
    };
});
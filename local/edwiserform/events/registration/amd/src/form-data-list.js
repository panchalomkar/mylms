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
 * Edwiser Form events class
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'local_edwiserform/form_data_list',
    'local_edwiserform/formviewer'
], function($, ajax, Notification, formdata) {
    return {
        init: function() {
            $(document).ready(function() {
                $('body').on('click', '.registration-action', function() {
                    var _this = this;
                    var action = $(this).attr('data-action');
                    var formid = $(this).closest('table').data('formid');
                    var user = $(this).closest('tr').find('.formdata-user');
                    var userid = $(user).attr('data-userid');
                    // eslint-disable-next-line no-undef
                    Formeo.dom.loading();
                    var actionResponse = ajax.call([{
                        methodname: 'edwiserformevents_registration_action',
                        args: {
                            formid: formid,
                            userid: userid,
                            action: action
                        }
                    }]);
                    actionResponse[0].done(function(response) {
                        if (response.status == true) {
                            $(_this).removeClass('show').parent().find('[data-action="' + response.type + '"]').addClass('show');
                            formdata.updateSeparator();
                            return;
                        }
                    }).fail(function(ex) {
                        Notification.exception(ex);
                    }).always(function() {
                        // eslint-disable-next-line no-undef
                        Formeo.dom.loadingClose();
                    });
                });
            });
        }
    };
});
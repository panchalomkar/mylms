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
 * Edwiser Form form_data_list js
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/templates',
    'local_edwiserform/jquery.dataTables',
    'local_edwiserform/dataTables.bootstrap4',
    'local_edwiserform/buttons.bootstrap4',
    'local_edwiserform/fixedColumns.bootstrap4',
    'local_edwiserform/formviewer'
], function($, Ajax, Notification, Templates) {
    var updateSeparator = function() {
        if ($('.efb-form-submissions-table tr').length == 0) {
            return;
        }
        $('.efb-form-submissions-table tr').each(function(index, tr) {
            $(tr).find('.efb-data-actions span').remove();
            var actions = $(tr).find('.efb-data-action.show');
            if (actions.length < 2) {
                return;
            }
            actions.slice(0, actions.length - 1).after('<span> | </span>');
        });
    };

    /**
     * Show errors as a list in modal
     * @param {Array} errors Errors string list
     */
    var showErrors = function(errors) {
        var list = $('<ul></ul>');
        for (var i = 0; i < errors.length; i++) {
            list.append($('<li>' + errors[i] + '</li>'));
        }
        // eslint-disable-next-line no-undef
        Formeo.dom.alert(
            'warning',
            list.get(0)
        );
    };

    /**
     * Initizalize.
     * @param {Number} formid Form id
     * @param {Boolean} allowed Is allowed
     */
    function init(formid, allowed) {
        var table;
        var PROMISES = {
            /**
             * Ajax promise to delete form submission by ids
             * @param  {Array}   ids Ids array of submission
             * @return {Promise}     Ajax promise
             */
            DELETE_SUBMISSION: function(ids) {
                return Ajax.call([{
                    methodname: 'edwiserform_delete_submissions',
                    args: {
                        id: formid,
                        submissions: ids
                    }
                }])[0];
            },

            /**
             * Get form data using ajax
             * @param  {String}  search Search query
             * @param  {Number}  start  Start index of courses
             * @param  {Number}  length Number of courses
             * @param  {String}  sort   Sorting order of date column
             * @return {Promise}        Ajax promise
             */
            GET_FORM_SUBMISSIONS: function(search, start, length, sort) {
                return Ajax.call([{
                    methodname: 'edwiserform_get_form_submissions',
                    args: {
                        formid: formid,
                        search: search,
                        start: start,
                        length: length,
                        sort: sort
                    }
                }])[0];
            },
        };

        $.fn.dataTable.ext.errMode = 'none';

        $(document).ready(function() {
            if (!allowed) {
                $('body').addClass('user-view');
            }
            table = $("#efb-form-submissions").DataTable({
                paging: true,
                bProcessing: true,
                bServerSide: true,
                rowId: 'DT_RowId',
                bDeferRender: true,
                scrollY: "400px",
                scrollX: true,
                scrollCollapse: true,
                autoWidth: true,
                classes: {
                    sScrollHeadInner: 'efb_dataTables_scrollHeadInner'
                },
                order: [
                    [2, 'asc']
                ],
                columnDefs: [
                    { orderable: false, targets: 'sorting_disabled' },
                    { orderable: true, targets: 'sorting', 'orderSequence': ['desc', 'asc'] }
                ],
                dom: '<"efb-top"<"efb-listing"l><"efb-list-filtering"f>>t<"efb-bottom"<' +
                    '"efb-list-pagination"p><B>' + (allowed ? '<"efb-bulk">' : '') + '>i',
                language: {
                    sSearch: M.util.get_string('search-entry', 'local_edwiserform'),
                    emptyTable: M.util.get_string('listformdata-empty', 'local_edwiserform'),
                    info: M.util.get_string(
                        'efb-heading-listforms-showing',
                        'local_edwiserform', { 'start': '_START_', 'end': '_END_', 'total': '_TOTAL_' }
                    ),
                    infoEmpty: M.util.get_string(
                        'efb-heading-listforms-showing',
                        'local_edwiserform', { 'start': '0', 'end': '0', 'total': '0' }
                    ),
                    paginate: {
                        first: M.util.get_string('first', 'local_edwiserform'),
                        last: M.util.get_string('last', 'local_edwiserform'),
                        next: M.util.get_string('next', 'local_edwiserform'),
                        previous: M.util.get_string('previous', 'local_edwiserform')
                    },
                    lengthMenu: M.util.get_string('show-entries', 'local_edwiserform', { 'menu': '_MENU_' }),
                },
                buttons: [],
                // eslint-disable-next-line no-unused-vars
                ajax: function(data, callback, settings) {
                    PROMISES.GET_FORM_SUBMISSIONS(
                        data.search.value,
                        data.start,
                        data.length,
                        data.order[0].dir
                    ).done(function(response) {
                        if (response.errors.length != 0) {
                            showErrors(response.errors);
                        }
                        delete response.errors;
                        callback(response);
                        updateSeparator();
                    }).fail(Notification.exception);
                },
                // eslint-disable-next-line no-unused-vars
                drawCallback: function(settings) {
                    updateSeparator();
                    if (allowed) {
                        $('.efb-bottom .dt-buttons').removeClass('btn-group');
                        Templates.render('local_edwiserform/bulk-actions', {
                                formid: formid,
                                wwwroot: M.cfg.wwwroot,
                                // eslint-disable-next-line no-undef
                                license: license == 'available' ? '' : M.util.get_string('activate-license', 'local_edwiserform')
                            }, 'theme_remui')
                            .done(function(html, js) {
                                Templates.replaceNode($('.efb-bulk'), html, js);
                            })
                            .fail(Notification.exception);
                    }
                }
            });

            // Toggle long data in table cells.
            $('body').on('click', '.data-toggler', function() {
                let parent = $(this).closest('.efb-table-data-expand');
                if (parent.is('.expanded')) {
                    parent.removeClass('expanded');
                    parent.find('.table-data').html(parent.data('short'));
                    $(this).text(M.util.get_string('readmore', 'local_edwiserform'));
                } else {
                    parent.addClass('expanded');
                    parent.find('.table-data').html(parent.data('long'));
                    $(this).text(M.util.get_string('readless', 'local_edwiserform'));
                }
                table.columns.adjust();
            });
        });

        /**
         * Delete submission from ids passed in parameter
         * @param  {Array} ids Ids array of submissions
         */
        function deleteSubmissions(ids) {
            // eslint-disable-next-line no-undef
            Formeo.dom.multiActions(
                'warning',
                M.util.get_string('deletesubmission', 'local_edwiserform'),
                M.util.get_string('deletesubmissionmsg', 'local_edwiserform'), [{
                    title: M.util.get_string('proceed', 'local_edwiserform'),
                    type: 'danger',
                    action: function() {
                        // eslint-disable-next-line no-undef
                        Formeo.dom.loading();
                        PROMISES.DELETE_SUBMISSION(ids).done(function(response) {
                            if (response.status == true) {
                                // eslint-disable-next-line no-undef
                                Formeo.dom.alert('success', '<div class="col-12">' + response.msg + '</div>');
                                table.draw();
                                $('.submission-check-all').prop('checked', false);
                            }
                            // eslint-disable-next-line no-undef
                            Formeo.dom.loadingClose();
                        }).fail(function(ex) {
                            Notification.exception(ex);
                            // eslint-disable-next-line no-undef
                            Formeo.dom.loadingClose();
                        });
                    }
                }, {
                    title: M.util.get_string('cancel', 'local_edwiserform'),
                    type: 'success'
                }]
            );
        }

        $('body').on('click', '.efb-csv-export', function() {
            // eslint-disable-next-line no-undef
            if (license != 'available') {
                window.location.href = M.cfg.wwwroot +
                    '/admin/settings.php?section=local_edwiserform&activetab=local_edwiserform_license_status';
                return;
            }
            window.open(M.cfg.wwwroot + "/local/edwiserform/export.php?id=" + formid + "&action=data");
        });

        // Select All/None checkbox.
        $('body').on('change', '.submission-check-all', function() {
            $('.submission-check').prop('checked', $(this).is(':checked'));
        });

        // Apply bulk actions.
        $('body').on('click', '#efb-apply-actions', function() {
            switch ($('#efb-bulk-actions').val()) {
                case 'bulkaction':
                    // Show toaster if bulk action is not selected.
                    // eslint-disable-next-line no-undef
                    Formeo.dom.toaster(M.util.get_string('selectbulkaction', 'local_edwiserform'));
                    break;
                case 'delete':
                    // Show toaster if deletiong submission without selecting any.
                    if (!$('.submission-check').is(':checked')) {
                        // eslint-disable-next-line no-undef
                        Formeo.dom.toaster(M.util.get_string('emptysubmission', 'local_edwiserform'));
                        return;
                    }

                    var ids = [];
                    // Prepare ids array to delete.
                    $('.submission-check:checked').each(function(i, e) {
                        ids.push($(e).data('value'));
                    });
                    deleteSubmissions(ids);
                    break;
            }
        });

        $('body').on('click', '.efb-data-action.delete-action', function() {
            deleteSubmissions([$(this).data('value')]);
        });

        $('body').on('click', '.efb-data-action', function(event) {
            event.preventDefault();
            return;
        });
    }
    return {
        updateSeparator: updateSeparator,
        init: init
    };
});
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
 * Edwiser Form form_list js
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
define([
    'jquery',
    'core/ajax',
    'core/notification',
    'local_edwiserform/jquery.dataTables',
    'local_edwiserform/dataTables.bootstrap4',
    'local_edwiserform/buttons.bootstrap4',
    'local_edwiserform/fixedColumns.bootstrap4',
    'local_edwiserform/formviewer'
], function($, Ajax, Notification) {
    return {
        init: function() {

            var table;

            var PROMISES = {

                /**
                 * Get forms using ajax
                 * @param  {String}  search Search query
                 * @param  {Number}  start  Start index of courses
                 * @param  {Number}  length Number of courses
                 * @param  {Array}   order  Column order
                 * @return {Promise}        Ajax promise
                 */
                GET_FORMS: function(search, start, length, order) {
                    return Ajax.call([{
                        methodname: 'edwiserform_get_forms',
                        args: {
                            search: search,
                            start: start,
                            length: length,
                            order: order
                        }
                    }])[0];
                },
            };
            $(document).ready(function() {
                table = $("#efb-forms").DataTable({
                    paging: true,
                    ordering: true,
                    bProcessing: true,
                    bServerSide: true,
                    rowId: 'DT_RowId',
                    bDeferRender: true,
                    scrollY: "400px",
                    scrollX: true,
                    scrollCollapse: true,
                    fixedColumns: {
                        leftColumns: 1,
                        rightColumns: 1
                    },
                    classes: {
                        sScrollHeadInner: 'efb_dataTables_scrollHeadInner'
                    },
                    dom: '<"efb-top"<"efb-listing"l><"efb-list-filtering"f>>t<"efb-bottom"<"efb-form-list-info"i>' +
                        '<"efb-list-pagination"p>><"efb-shortcode-copy-note">',
                    columns: [
                        { data: "title" },
                        { data: "type" },
                        { data: "shortcode", "orderable": false },
                        { data: "author" },
                        { data: "created" },
                        { data: "author2" },
                        { data: "modified" },
                        { data: "allowsubmissionsfromdate" },
                        { data: "allowsubmissionstodate" },
                        { data: "actions", "orderable": false, 'class': 'text-center' }
                    ],
                    // eslint-disable-next-line no-unused-vars
                    ajax: function(data, callback, settings) {
                        PROMISES.GET_FORMS(
                            data.search.value,
                            data.start,
                            data.length,
                            data.order[0]
                        ).done(function(response) {
                            callback(response);
                        }).fail(Notification.exception);
                    },
                    language: {
                        sSearch: M.util.get_string('efb-search-form', 'local_edwiserform'),
                        emptyTable: M.util.get_string('listforms-empty', 'local_edwiserform'),
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
                    // eslint-disable-next-line no-unused-vars
                    "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                        $('td:eq(0)', nRow).addClass("efb-tbl-col-title");
                        $('td:eq(2)', nRow).addClass("efb-tbl-col-shortcode").attr(
                            'title',
                            M.util.get_string('clickonshortcode', 'local_edwiserform')
                        );
                        $('td:eq(9)', nRow).addClass("text-center");
                    },
                    // eslint-disable-next-line no-unused-vars
                    drawCallback: function(settings) {
                        $('.efb-shortcode-copy-note').html(M.util.get_string('note', 'local_edwiserform') + ' ' +
                            M.util.get_string('clickonshortcode', 'local_edwiserform'));
                    }
                });
                $('.efb-modal-close').click(function() {
                    $('#efb-modal').removeClass('show');
                });
                $('body').on('click', '.efb-form-delete', function(event) {
                    event.preventDefault();
                    var id = $(this).data('formid');
                    var row = $(this).parents('tr');
                    var title = $(row).children('.efb-tbl-col-title').text();
                    $('#efb-modal .efb-modal-header').removeClass('bg-success').addClass('bg-warning')
                        .children('.efb-modal-title').html(M.util.get_string('warning', 'local_edwiserform'));
                    $('#efb-modal .efb-modal-body').html('<h5>' + M.util.get_string(
                        'efb-delete-form-and-data',
                        'local_edwiserform', {
                            title: title,
                            id: id
                        }
                    ) + '</h5>');
                    $('#efb-modal').addClass('show delete').removeClass('pro');
                    $('#efb-modal .efb-modal-delete-form').data('formid', id);
                    return;
                }).on('click', '.efb-form-export', function(event) {
                    // eslint-disable-next-line no-undef
                    if (license != 'available') {
                        $('#efb-modal .efb-modal-header').removeClass('bg-success').addClass('bg-warning');
                        $('#efb-modal .efb-modal-title').html(M.util.get_string('hey-wait', 'local_edwiserform'));
                        $('#efb-modal .efb-modal-body').html('<h5>' + M.util.get_string(
                            'efb-template-inactive-license',
                            'local_edwiserform',
                            M.util.get_string('efb-form-action-export-title', 'local_edwiserform')
                        ) + '</h5>');
                        $('#efb-modal').addClass('show pro').removeClass('delete');
                        event.preventDefault();
                        return;
                    }
                }).on('click', '.efb-modal-pro-activate', function() {
                    window.location.href = M.cfg.wwwroot +
                        '/admin/settings.php?section=local_edwiserform&activetab=local_edwiserform_license_status';
                });
                $('body').on('click', '.efb-modal-delete-form', function(event) {
                    event.preventDefault();
                    var id = $(this).data('formid');
                    var reqDeleteForm = Ajax.call([{
                        methodname: 'edwiserform_delete_form',
                        args: {
                            id: id
                        }
                    }]);
                    reqDeleteForm[0].done(function(response) {
                        if (response.status == true) {
                            table.draw();
                        }
                    }).fail(Notification.exception);
                    $('.efb-modal-close').click();
                });

                /**
                 * Enable or disable form on the basis on form id
                 * @param  {DOM} input DOM element
                 */
                function enableDisableForm(input) {
                    var formid = $(input).data('formid');
                    var enabledisableform = Ajax.call([{
                        methodname: 'edwiserform_enable_disable_form',
                        args: {
                            id: formid,
                            action: !$(input).is(':checked')
                        }
                    }]);
                    enabledisableform[0].done(function(response) {
                        if (response.status) {
                            $(input).prop('checked', $(input).is(':checked'));
                        } else {
                            $(input).prop('checked', !$(input).is(':checked'));
                        }
                        $(input).parent().attr('title', $(input).is(':checked') ? $(input).data('disable-title') :
                            $(input).data('enable-title'));
                    }).fail(Notification.exception);
                }

                $('body').on('click', '.efb-enable-disable-form', function() {
                    var input = $(this).prev();
                    enableDisableForm(input);
                });

                $('body').on("click", ".efb-tbl-col-shortcode", function() {
                    var temp = $('<input>');
                    $('body').append(temp);
                    var shortcode = $(this).text();
                    temp.val(shortcode).select();
                    document.execCommand('copy');
                    temp.remove();
                    // eslint-disable-next-line no-undef
                    Formeo.dom.toaster(M.util.get_string('shortcodecopied', 'local_edwiserform', shortcode));
                });
            });
        }
    };
});

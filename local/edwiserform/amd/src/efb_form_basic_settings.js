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
 * Edwiser Form basic settings js
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
define(['jquery'], function($) {
    $(document).ready(function() {

        /**
         * Validate email address
         * @param {String} email Email address
         * @return {Integer}
         */
        function validateEmail(email) {
            // eslint-disable-next-line no-useless-escape
            var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
            if (filter.test(email)) {
                if (emails.indexOf(email) != -1) {
                    return 2;
                }
                return 1;
            }
            return 0;

        }

        /**
         * Validate editing email
         * @param {Integer} index Email index
         * @param {Email} email Email id
         * @return {Integer}
         */
        function validateEditEmail(index, email) {
            // eslint-disable-next-line no-useless-escape
            var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
            if (filter.test(email)) {
                if (emails.indexOf(email) != -1 && emails.indexOf(email) != index) {
                    return 2;
                }
                return 1;
            }
            return 0;

        }

        /**
         * Add email in email list
         * @param {Integer} index Email index
         * @param {String}  email Email id
         */
        function addEmail(index, email) {
            var classes = [
                'primary',
                'warning',
                'success',
                'info',
            ];
            var emailElement = document.createElement("span");
            $(emailElement).html(email);
            $(emailElement).attr("data-email", email);
            $(emailElement).attr("class", 'email-tag btn btn-' + classes[Math.floor((Math.random() * classes.length))]);
            var deleteEmail = document.createElement("span");
            $(deleteEmail).attr("class", "email-tag-delete");
            $(deleteEmail).html("X");
            $(emailElement).append($(deleteEmail));
            $(emailElement).insertBefore($(".notifi-email-group").children()[index]);
        }

        /**
         * Change email tag to email edit element
         * @param {DOM} tag Email tag element
         */
        function changeToEmailEdit(tag) {
            let email = $(tag).data('email');
            let index = $(".email-tag").index(tag);
            editing = index;
            var emailElement = document.createElement("input");
            $(tag).remove();
            $(emailElement).val(email);
            $(emailElement).attr("class", "notifi-email-group-edit");
            $(emailElement).attr("data-index", index);
            $(emailElement).insertBefore($(".notifi-email-group").children()[index]);
            $(emailElement).focus();
        }

        /**
         * Change email edit element to email tag
         * @param {DOM} edit Email edit element
         */
        function changeToEmailTag(edit) {
            if (editing != -1) {
                var index = $(edit).data('index');
                var status = validateEditEmail(index, $(edit).val());
                switch (status) {
                    case 0:
                        showEmailError(M.util.get_string("efb-lbl-notifi-email-warning", "local_edwiserform"));
                        return;
                    case 1:
                        editing = -1;
                        var email = $(edit).val();
                        $(edit).remove();
                        addEmail(index, email);
                        emails[index] = email;
                        $("#id_notifi_email").val(emails.join(','));
                        hideEmailError();
                        return;
                    case 2:
                        showEmailError(M.util.get_string("efb-lbl-notifi-email-duplicate", "local_edwiserform"));
                        return;
                }
            }
        }
        $("#id_notifi_email").parent().prepend('<div class="notifi-email-group">' +
        '<input type="email" class="notifi-email-group-input form-control" id="notifi-email-group-input"/><div>' +
        M.util.get_string('efb-recipient-email-desc', 'local_edwiserform') + '</div></div>');
        $("#id_notifi_email").hide();

        // Append restore link to email body.
        $('#id_notifi_email_body').after(M.util.get_string('efb-restore-desc', 'local_edwiserform', {
            id: '#id_notifi_email_bodyeditable',
            string: 'efb-notify-email-body'
        }));

        // Append restore link to confirmation message.
        $('#id_confirmation_msg').after(M.util.get_string('efb-restore-desc', 'local_edwiserform', {
            id: '#id_confirmation_msgeditable',
            string: 'efb-confirmation-default-msg'
        }));

        // Append restore link to success message.
        $('#id_success_message').after(M.util.get_string('submission-successful-desc', 'local_edwiserform', {
            id: '#id_success_messageeditable',
            string: 'submission-successful'
        }));

        // Restore content of editor.
        $('.efb-restore').click(function() {
            $($(this).data('id')).html(M.util.get_string($(this).data('string'), 'local_edwiserform'));
        });

        /**
         * Get email body tags.
         * @param  {String} string Lang string id
         * @return {String}        return email body tag.
         */
        function getBodyTags(string) {
            var container = "<div class='efb-email-tags show'><ul>";
            $.each(string, function(tag, info) {
                var string = M.util.get_string(info, 'local_edwiserform');
                container += '<li><a href="#" class="efb-email-tag" title="' + string + '">' + tag + '</a></li>';
            });
            container += "</ul></div>";
            return container;
        }
        $('body').on('click', '.efb-email-show-tags>a', function() {
            $(this).next().toggleClass('show');
            $(this).text($(this).next().hasClass('show') ?
            M.util.get_string('efb-email-hide-tags', 'local_edwiserform') :
            M.util.get_string('efb-email-show-tags', 'local_edwiserform'));
        });

        var efbEmailBodyTags = {
            "{SITE_NAME}": 'site-name',
            "{USER_FULLNAME}": 'user-fullname',
            "{USER_FIRSTNAME}": 'user-firstname',
            "{USER_LASTNAME}": 'user-lastname',
            "{AUTHOR_NAME}": 'author-name',
            "{AUTHOR_FIRSTNAME}": 'author-firstname',
            "{AUTHOR_LASTNAME}": 'author-lastname',
            "{FORM_TITLE}": 'form-title',
            "{USER_LINK}": 'user-link',
            "{ALL_FIELDS}": 'all-fields',
            "{VIEW_DATA_LINK LABEL=\"click\"}": 'view-data-link',
        };
        var successMessageTags = {
            "{HOMEPAGE}": 'homepage',
            "{DASHBOARD}": 'dashboard',
            "{VIEW_DATA_LINK LABEL=\"click\"}": 'view-data-link',
        };
        // Email tags for email body.
        $("#id_notifi_email_body").parents('.felement').siblings().
        append('<div class="efb-email-show-tags"><a href="#">' +
        M.util.get_string('efb-email-hide-tags', 'local_edwiserform') + '</a>' + getBodyTags(efbEmailBodyTags) + '</div>');

        // Email tags for confirmation message.
        $("#id_confirmation_msg").parents('.felement').siblings().
        append('<div class="efb-email-show-tags"><a href="#">' +
        M.util.get_string('efb-email-hide-tags', 'local_edwiserform') + '</a>' + getBodyTags(efbEmailBodyTags) + '</div>');

        // Tags for success message.
        $("#id_success_message").parents('.felement').siblings().
        append('<div class="efb-email-show-tags"><a href="#">' +
        M.util.get_string('efb-email-hide-tags', 'local_edwiserform') + '</a>' + getBodyTags(successMessageTags) + '</div>');

        var emails = $("#id_notifi_email").val().trim();
        var editing = -1;
        if (emails.length > 0) {
            emails = emails.split(",");
        } else {
            emails = [];
        }
        $.each(emails, function(index, email) {
            addEmail(index, email);
        });

        $("body").on("click", ".email-tag-delete", function() {
            var email = $(this).parent();
            var index = $(".email-tag").index(email);
            emails.splice(index, 1);
            email.remove();
            $("#id_notifi_email").val(emails.join(','));
        });

        $("body").on("dblclick", ".email-tag", function() {
            changeToEmailEdit($(this));
        });

        $("body").on("focusout", ".notifi-email-group-edit", function() {
            changeToEmailTag($(this));
        });

        $("body").on("keyup", ".notifi-email-group-edit", function(event) {
            if (event.keyCode == 13 || event.keyCode == 27) {
                changeToEmailTag($(this));
            }
        });

        /**
         * Show email validation error.
         * @param {String} string Error string
         */
        function showEmailError(string) {
            $("#id_error_notifi_email").html(string);
            $("#id_error_notifi_email").show();
            $("#id_error_notifi_email").parent(".felement").addClass('has-danger');
        }

        /**
         * Hide email validation error.
         */
        function hideEmailError() {
            $("#id_error_notifi_email").hide().parent(".felement").removeClass('has-danger');
            $("#id_notifi_email").val(emails.join(','));
        }

        /**
         * Check and add email in the list.
         * @param {DOM} elem Email input element
         */
        function checkAndAdd(elem) {
            if ($(elem).val() == '') {
                hideEmailError();
                return;
            }
            var status = validateEmail($(elem).val());
            switch (status) {
                case 0:
                    showEmailError(M.util.get_string("efb-lbl-notifi-email-warning", "local_edwiserform"));
                    return;
                case 1:
                    var nextIndex = $(elem).siblings('.email-tag').length;
                    var email = $(elem).val().trim();
                    addEmail(nextIndex, email);
                    emails.push(email);
                    $(elem).val('');
                    hideEmailError();
                    return;
                case 2:
                    showEmailError(M.util.get_string("efb-lbl-notifi-email-duplicate", "local_edwiserform"));
                    return;
            }
        }

        $("body").on("keyup", "#notifi-email-group-input", function(event) {
            if (event.keyCode == 13 || event.keyCode == 32) { // Enter key or space key.
                checkAndAdd(this);
            } else if (event.keyCode == 188) { // Comma key.
                $(this).val($(this).val().slice(0, -1));
                checkAndAdd(this);
            }
        });

        $("body").on("focusout", "#notifi-email-group-input", function(event) {
            event.preventDefault();
            checkAndAdd(this);
        });
    });
});

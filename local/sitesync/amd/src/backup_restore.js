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
 * @module     local_sitesync/backup_restore
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax','local_sitesync/main'], function ($, ajax, Syncnmain) {

    var SELECTORS = {
        "BACKUP_ALERT_LINK": '.backup-alert-link',
        "RESTORE_BACKUP": '#restorebackup',
        "BACKUP_MODAL_NOTIFICATIN_WRAPPER": '#backupModal .notification-wrapper',
        "BACKUP_MODAL": '#backupModal',
        "SHOW_CHANGES_BTN": '.show-changes-btn',
        "BACKUP_DATA_CONTINAER": '.backupdatacontainer',
    };

    var currentversion = false;
    const registerEvents = () => {

        $(SELECTORS.BACKUP_MODAL).on('show.bs.modal', function(event) {
            // Get the button that triggered the modal
            var button = $(event.relatedTarget);

            // Extract data attributes
            var version = button.attr('data-version'); // Get the version
            currentversion = version;

            const $backupContainers = $(SELECTORS.BACKUP_DATA_CONTINAER);

            const lastVersion = $backupContainers.last().data('version-content');

            var backupselector = $(`${SELECTORS.BACKUP_DATA_CONTINAER}[data-version-content="${version}"]`); // Get the backup details
            var backup = backupselector.html();

            // Update the modal content
            var modal = $(this);
            modal.find('#modalVersion').text('Version: ' + version); // Set version name
            modal.find('#modalBackupContent').html(backup);

            if (lastVersion == currentversion) {
                $(SELECTORS.RESTORE_BACKUP).attr('disabled', 'disabled');
            } else {
                $(SELECTORS.RESTORE_BACKUP).removeAttr('disabled');
            }// Set backup details
        });

        $(document).on('click', SELECTORS.RESTORE_BACKUP, async function () {
            $(this).attr('disabled', 'disabled');
            let alerthtml = Syncnmain.alerthtml(M.util.get_string('restoreinprogress','local_sitesync'), 'warning');

            $(SELECTORS.BACKUP_MODAL_NOTIFICATIN_WRAPPER).empty().append(alerthtml);
            if(currentversion) {
                let args = {
                    "action": "restore_backup",
                    "config": JSON.stringify({
                        "restoreconfig": currentversion,
                        "pluginname" : "theme_remui",
                    }),
                };

                let response = await trigger_backup(args);
                response = JSON.parse(response);
                if(response){
                    let alerthtml = Syncnmain.alerthtml(M.util.get_string('restorecompleted','local_sitesync'), 'success');

                    $(SELECTORS.BACKUP_MODAL_NOTIFICATIN_WRAPPER).empty().append(alerthtml);

                    setTimeout(() => {
                        window.location.reload();
                    }, 300);
                }

            }
        });

    };

    /**
     * Triggers a backup action using the local_sitesync_do_sync_action AJAX method.
     *
     * @param {Object} args - The arguments to pass to the AJAX method.
     * @returns {Promise<string>} - The response from the AJAX method.
     */
    const trigger_backup = async (args) => {
        const request = {
            methodname: 'local_sitesync_do_sync_action',
            args:args
        };
        const response = await ajax.call([request])[0];
        return response;
    };

    const init = () => {
        registerEvents();
    };

    return {
        init: init
    };
});

/* eslint-disable no-console */
/* eslint-disable max-len */
/* eslint-disable jsdoc/require-jsdoc */
/* eslint-disable no-unused-vars */
/* eslint-disable no-undef */

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
 * @module     local_sitesync/sync_form
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/modal', 'core/notification', 'local_sitesync/connection', 'core/toast'], function($, ajax, Modal, Notification, Connection, Toast) {

    var SELECTORS = {
        "GENERATE_KEY_BTN": '#generateKey',
        "SECURE_KEY_INPUT": '#secureKeyInput',
        "COPY_KEY_BTN": '#secureKeyGroup .copyicon',
        "START_SYNC_BTN": '#startSync',
        "SELECT_ALL": 'input[name="select_all"]',
        "CONFIG_INPUT": '#ConfigSelector .configinput',
        "ADD_PUBLIC_KEY": '#addPublicKey',
        "PUBLIC_KEY_INPUT": "#publicKeyInput",
        "USER_NOTIFICATION": '#region-main #user-notifications',
        "SYNC_PROGRESS_TRACKER": '.syncprogresstracker',
        "NAV_TABS_LINK": '.local_sitesync .nav-tabs .nav-link',
        "NO_CONNECTION_OVERLAY": '.no-connection-overlay',
    };

    const registerEvents = () => {
        $(SELECTORS.GENERATE_KEY_BTN).on('click', () => {
            // SELECTORS.GENERATE_KEY_BTN.disabled = true;
            ajax.call([{
                methodname: 'local_sitesync_generate_keys',
                args: {},
                done: (response) => {
                    $(SELECTORS.SECURE_KEY_INPUT).val(response.publickey);
                    var msg = M.util.get_string('keygeneratedsuccessfully', 'local_sitesync');
                    Toast.add(msg, {
                        delay: 3000,
                        closeButton: true,
                        type: 'success sitesync_key_toast'
                    });
                },
                fail: () => {
                    // Re-enable button on error
                    SELECTORS.GENERATE_KEY_BTN.disabled = false;
                }
            }]);
        });

        $(SELECTORS.COPY_KEY_BTN).on('click', function() {
            var clipboardText = "";
            clipboardText = $(SELECTORS.SECURE_KEY_INPUT).val();

            const temp = $("<textarea>");
            $("body").append(temp);
            temp.val(clipboardText).select();
            document.execCommand("copy");
            temp.remove();

            var msg = M.util.get_string('textcopied', 'local_sitesync');
            Toast.add(msg, {
                delay: 3000,
                closeButton: true,
                type: 'warning sitesync_key_toast'
            });
            // navigator.clipboard.writeText(clipboardText);
            // alert("Copied to Clipboard");
        });

        $(SELECTORS.SELECT_ALL).on('click', function() {
            var isChecked = $(this).is(':checked');
            var checkboxes = $(SELECTORS.CONFIG_INPUT);
            checkboxes.prop('checked', isChecked);
        });

        $(SELECTORS.CONFIG_INPUT).on('click', function() {
            var checkboxes = $(SELECTORS.CONFIG_INPUT);
            $(SELECTORS.SELECT_ALL).prop('checked', checkboxes.length === checkboxes.filter(':checked').length);
        });

        $(SELECTORS.START_SYNC_BTN).on('click', async() => {
            await setupSyncEnvironment();
        });

        $(document).on('keyup', SELECTORS.PUBLIC_KEY_INPUT, function() {
            const inputValue = $(this).val().trim();
            $(SELECTORS.ADD_PUBLIC_KEY).prop('disabled', inputValue === '');
        });
        $(document).on('click', `${SELECTORS.ADD_PUBLIC_KEY}`, async() => {
            if (input_public_key_modal) {

                let masterkey = $(SELECTORS.PUBLIC_KEY_INPUT).val();

                var configdata = {
                    key: 'master_secret_pub_key',
                    value: masterkey,
                    plugin: 'local_sitesync'
                };

                await Connection.saveConfigs([configdata]);

                input_public_key_modal.destroy();

                let syncconfig = await Connection.getSyncConfigs();

                syncconfig = JSON.parse(syncconfig);

                sync_start(syncconfig.masterkey);
            }
        });

        $(SELECTORS.NAV_TABS_LINK).on('click', async() => {
            if (Connection.getConnectionStatus()) {
                $(SELECTORS.NO_CONNECTION_OVERLAY).addClass('d-none');
            } else {
                $(SELECTORS.NO_CONNECTION_OVERLAY).removeClass('d-none');
            }
        });

    };

    /**
     * Starts the synchronization process for the selected configurations on the master site.
     *
     * @param {string} masterkey - The master secret public key.
     * @returns {Promise} A promise that resolves when the sync is complete.
     */
    const sync_start = async(masterkey) => {

        $(SELECTORS.START_SYNC_BTN).attr('disabled', 'disabled');

        let themesyncdata = await prepareSyncData(masterkey);

        if (themesyncdata) {

            themesyncdata = JSON.parse(themesyncdata);

            let args = {
                "action": "sync_settings_on_master",
                "config": JSON.stringify({
                    "themeselectedConfigs": themesyncdata,
                    "pluginname": "theme_remui",
                    "masterkey": masterkey,
                }),
            };

            // eslint-disable-next-line no-undef
            if (connectionstatus) {

                let serverconfig = await Connection.getSyncConfigs();


                $(SELECTORS.USER_NOTIFICATION).empty();

                await get_notification(M.util.get_string('syncinprogress', 'local_sitesync'), 'warning');

                $(SELECTORS.SYNC_PROGRESS_TRACKER).removeClass('d-none');

                await syncSettingsOnMaster(JSON.parse(serverconfig), args);



            } else {
                $(SELECTORS.USER_NOTIFICATION).empty();
                await get_notification(M.util.get_string('mastersitenotavailable', 'local_sitesync'), 'danger');
            }
        } else {
            $(SELECTORS.USER_NOTIFICATION).empty();
            await get_notification(M.util.get_string('noconfigurationselected', 'local_sitesync'), 'danger');

        }

    };

    const prepareSyncData = async(masterkey) => {
        let selectedConfigs = [];
        // Get only checked config inputs
        selectedConfigs = $(SELECTORS.CONFIG_INPUT + ':checked').map(function () {
            return $(this).attr('name');
        }).get();
        if (!selectedConfigs.length) {
            return false;
        }

        const request = {
            methodname: 'local_sitesync_do_sync_action',
            args: {
                action: "prepare_sync_data",
                config: JSON.stringify({
                    "themeselectedConfigs": selectedConfigs,
                    'pluginname': "theme_remui",
                    "masterkey": masterkey
                })

            }
        };

        const response = await ajax.call([request])[0];
        return response;
    };

    const get_notification = async(message, type) => {
        await Notification.addNotification({
            message: message,
            type: type
        });
    };

    /**
     * Synchronizes settings on the master site.
     *
     * @param {Object} serverconfig - The server configuration object.
     * @param {Object} args - The arguments object.
     * @returns {Promise} A promise that resolves when the sync is complete.
     */

    const syncSettingsOnMaster = async(serverconfig, args) => {
        // Build the web service URL

        const progress = (baseprogress) => baseprogress + (Date.now() % 5);

        updateProgress(0);

        const wsUrl = `${serverconfig.siteurl}/webservice/rest/server.php`;

        // Create form data for the request
        const formData = new FormData();
        formData.append('wstoken', serverconfig.accesstoken);
        formData.append('wsfunction', 'local_sitesync_do_sync_action');
        formData.append('moodlewsrestformat', 'json');
        formData.append('action', args.action);
        formData.append('config', args.config);

        updateProgress(progress(10));

        // Make the request to external site
        fetch(wsUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                updateProgress(progress(60));
                return response.json();
            })
            .then(async(data) => {
                updateProgress(progress(75));
                let responsedata = JSON.parse(data);
                if (responsedata.status == "success") {

                    updateProgress(100);

                     $(SELECTORS.START_SYNC_BTN).removeAttr('disabled');

                    $(SELECTORS.USER_NOTIFICATION).empty();

                    get_notification(M.util.get_string('synccompleted', 'local_sitesync'), 'success');

                    if (!Connection.remUiActiveState()) {
                        get_notification(M.util.get_string('remuinotactivenotification', 'local_sitesync'), 'warning');
                    }
                    setTimeout(() => {
                        $(".typeform-wrapper button.typeform-init-button").click();
                    }, 1000);

                } else {
                    get_notification(responsedata.message, 'danger');
                    master_public_key = false;
                    $(SELECTORS.START_SYNC_BTN).removeAttr('disabled');

                    var configdata = [{
                        key: 'master_secret_pub_key',
                        value: " ",
                        plugin: 'local_sitesync'
                    }];
                    await Connection.saveConfigs(configdata);
                }
                return true;
            }).catch(error => {
                Notification.addNotification({
                    message: 'Something went wrong ' + error.message + ' Please try again later.',
                    type: 'error'
                });
            });
    };

    let input_public_key_modal = false;
    /**
     * Triggers the display of a modal dialog to allow the user to enter a public key.
     *
     * @async
     * @returns {Promise<Modal>} The created modal dialog instance.
     */
    const trigger_input_public_key = async() => {

        const modal = await Modal.create({
            title: `${M.util.get_string('addpublickeyinfo2', 'local_sitesync')}`,
            // eslint-disable-next-line max-len
            body: `<p>${M.util.get_string('addpublickeyinfo', 'local_sitesync')}</p><input type="password" id="publicKeyInput" class="form-control" placeholder="Secret public key"  required/>`,
            footer: `<button type="button" class="btn btn-primary" id="addPublicKey" disabled>${M.util.get_string('startsyncbtntext', 'local_sitesync')}</button>`,
            show: true,
            removeOnClose: true,
        });

        modal.show();

        return modal;
    };

    /**
     * Updates the progress bar element with the given progress value.
     *
     * @param {number} progress - The progress value, expressed as a percentage.
     */
    const updateProgress = (progress) => {
        $(`${SELECTORS.SYNC_PROGRESS_TRACKER} .progress-bar`)
            .css('width', progress + '%')
            .attr('aria-valuenow', progress)
            .text(progress + '%');
    };


    /**
     * Sets up the sync environment by either triggering the input public key modal  or starting the sync process.
     *
     * If the `master_public_key` is not set, it will trigger the display of a modal dialog to allow the user to enter a public key.
     * Otherwise, it will trigger the start of the sync process using the `master_public_key`.
     *
     * @async
     */

    const setupSyncEnvironment = async () => {
        // eslint-disable-next-line no-undef

        if (!Connection.getConnectionStatus()) {

            Notification.addNotification({
                message: `Cannot stat sync process no connection found`,
                type: 'error'
            });

            throw new Error('Cannot start sync process no connection found');
        }

        if (!master_public_key) {
            input_public_key_modal = await trigger_input_public_key();
        } else {
            sync_start(master_public_key);
        }
    };

    const init = () => {

        registerEvents();
    };

    return {
        init: init
    };
});

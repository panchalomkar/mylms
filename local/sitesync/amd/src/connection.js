/* eslint-disable no-unused-vars */
/* eslint-disable no-console */
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
 * @module     local_sitesync/connection
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Modal from 'core/modal';

var SELECTORS = {
    "REMOVE_CONNECTION": '#removeConnection',
    'INPUT_SITE_URL': '#site-url',
    'INPUT_ACCESS_TOKEN': '#access-token',
    "FORM_ACTION_WRAPPER": ".form-action-wrapper",
    "FORM_ACIION_BUTTON": ".form-action-wrapper .formaction"
};

var CONFIG = {
    'TEST_CONNECTION_URL': '/webservice/rest/server.php',
    'SLAVE_SITE_URL': false,
    'SLAVE_ACCESS_TOKEN': false,
    'SLAVE_VALIDATION_DATA': false,
};
let connectionstatus = false;
let remuiActivestate = true;

/**
 * Retrieves the current connection status.
 *
 * @returns {boolean} The current connection status.
 */
export const getConnectionStatus = () => {
    return connectionstatus;
};
export const remUiActiveState = () => {
    return remuiActivestate;
};
/**
 * Saves the provided configuration settings.
 *
 * @param {Object[]} configs - An array of configuration objects to save.
 * @returns {Promise<Object>} The response from the server after saving the configurations.
 */
export const saveConfigs = async (configs = []) => {
    const request = {
        methodname: 'local_sitesync_save_config',
        args: {
            configs: configs
        }
    };

    const response = await Ajax.call([request])[0];
    return response;
};

/**
 * Retrieves the synchronization configurations like siteurl, accesstoken from the server.
 *
 * @returns {Promise<Object>} The response from the server containing the synchronization configurations.
 */
export const getSyncConfigs = async () => {
    const request = {
        methodname: 'local_sitesync_do_sync_action',
        args: {
            action: "get_sync_configs",
            config: JSON.stringify({})
        }
    };

    const response = await Ajax.call([request])[0];
    return response;
};
/**
 * Generates an HTML button element for disconnecting from a connection.
 *
 * @param {string} buttontext - The text to display on the button.
 * @param {string} buttonclass - The CSS class(es) to apply to the button.
 * @returns {string} The HTML for the disconnect button.
 */
function updateFormActionButtonState(buttontext, buttonclass) {
    var removeConnectionButton = `<button type="submit" class="btn ${buttonclass} formaction" data-action="disconnect">${buttontext}</button>`;
    return removeConnectionButton;
}

/**
 * Adds a new step to the connection steps list.
 *
 * @param {string} steptext - The text to display for the step.
 * @param {string} stepdata - The unique identifier for the step.
 * @returns {void}
 */
function addStep(steptext, stepdata) {
    var stephtml = `<li class="d-flex align-items-center mb-3 step step-loading" data-step="${stepdata}">
                    <span class="step-status mr-2">
                        <span class="loading-spinner spinner-border spinner-border-sm" role="status"></span>
                        <i class="fa fa-check text-success"></i>
                        <i class="fa fa-times text-danger"></i>
                    </span>
                    <span class="step-text">${steptext}</span>
                </li>`;
    $('.connection-steps .list-unstyled').append(stephtml);
}
/**
 * Removes a step from the connection steps list.
 *
 * @param {string} stepdata - The unique identifier for the step to be removed.
 * @returns {void}
 */
function clearStep(stepdata) {
    $('.step[data-step="' + stepdata + '"]').remove();
}

/**
 * Updates the status of a connection step in the UI.
 *
 * @param {string} stepdata - The unique identifier for the step.
 * @param {string} status - The new status for the step ('step-loading', 'step-success', or 'step-failure').
 * @returns {void}
 */
function updateStepStatus(stepdata, status) {
    $('.step[data-step="' + stepdata + '"]').removeClass('step-loading step-success step-failure').addClass(status);
}


/**
 * An array of asynchronous functions that represent the steps in the connection process.
 * Each step performs a specific task, such as validating the connection, checking compatibility,
 * saving connection configs, and updating the form action button state.
 */
const connectionSteps = [
    // Step 1: Validate connection
    async () => {

        clearStep("step1");
        addStep(M.util.get_string("chekingconnection", "local_sitesync"), "step1");

        let slaveValidationData = CONFIG.SLAVE_VALIDATION_DATA;

        slaveValidationData = JSON.parse(slaveValidationData);

        // if (!slaveValidationData["is_secure_site"]) {
        //     clearStep("step1");
        //     addStep(M.util.get_string("is_secure_site_fail", "local_sitesync"), "step1");
        //     updateStepStatus("step1", "step-failure");
        //     throw new Error('Connection failed');
        // }

        // if (!slaveValidationData["is_remui_active"]) {
        //     clearStep("step1");
        //     addStep(M.util.get_string("is_remui_active_fail", "local_sitesync"), "step1");
        //     updateStepStatus("step1", "step-failure");
        //     throw new Error('Connection failed');
        // }


        // Build the web service URL
        const wsUrl = `${CONFIG.SLAVE_SITE_URL}/webservice/rest/server.php`;

        // Create form data for the request
        const formData = new FormData();
        formData.append('wstoken', CONFIG.SLAVE_ACCESS_TOKEN);
        formData.append('wsfunction', 'local_sitesync_check_connection');
        formData.append('moodlewsrestformat', 'json');

        const response = await fetch(wsUrl, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (!result) {
            clearStep("step1");
            updateStepStatus("step1", "step-failure");
            throw new Error('Connection validation failed');
        }

        clearStep("step1");
        addStep(M.util.get_string("connectioncheckdone", "local_sitesync"), "step1");
        updateStepStatus("step1", "step-success");
        return result;
    },

    // Step 2: Checking Compatibility...
    async () => {

        clearStep("step2");
        addStep(M.util.get_string("chekingcompatibility", "local_sitesync"), "step2");

        // Build the web service URL
        const wsUrl = `${CONFIG.SLAVE_SITE_URL}/webservice/rest/server.php`;

        let slaveValidationData = CONFIG.SLAVE_VALIDATION_DATA;

        // Create form data for the request
        const formData = new FormData();
        formData.append('wstoken', CONFIG.SLAVE_ACCESS_TOKEN);
        formData.append('wsfunction', 'local_sitesync_compatibility_checker');
        formData.append('moodlewsrestformat', 'json');
        formData.append('action', "check_master_server_compaibility");
        formData.append('config', slaveValidationData);

        const response = await fetch(wsUrl, {
            method: 'POST',
            body: formData
        });
        let result = await response.json();
        result = JSON.parse(result);
        if (!result.status) {
            if (result.failedkey == "is_remui_active") {
                remuiActivestate = false;
            } else {
                clearStep("step2");
                addStep(result.message, "step2");
                updateStepStatus("step2", "step-failure");
                throw new Error('Connection failed');
            }
        }
        clearStep("step2");
        addStep(M.util.get_string("compatibilitycheckdone", "local_sitesync"), "step2");
        updateStepStatus("step2", "step-success");
        return result;

    },

    // Step 3: Save connection configs
    // Validating server connection
    async () => {

        clearStep("step3");
        addStep(M.util.get_string("checkingservervalidity", "local_sitesync"), "step3");
        const configs = [
            {
                key: 'moodleurl',
                value: CONFIG.SLAVE_SITE_URL,
                plugin: 'local_sitesync'
            },
            {
                key: 'accesstoken',
                value: CONFIG.SLAVE_ACCESS_TOKEN,
                plugin: 'local_sitesync'
            },
            {
                key: 'connection',
                value: true,
                plugin: 'local_sitesync'
            }
        ];
        clearStep("step3");
        addStep(M.util.get_string("servalidatated", "local_sitesync"), "step3");
        updateStepStatus("step3", "step-success");
        let result = await saveConfigs(configs);
        return result;
    },
    // Step 3: Get sync configurations
    async () => {
        return await getSyncConfigs();
    },
    async () => {
        let buttonhtml = updateFormActionButtonState(M.util.get_string('reset', "local_sitesync"), "btn-outline-danger");

        let text = `<p class="text-success m-0 mb-2">${M.util.get_string('connectedstatus', "local_sitesync")}</p>`;

        $(SELECTORS.FORM_ACTION_WRAPPER).empty().append(text + buttonhtml);
        connectionstatus = true;
    }
];

/**
 * Initializes the connection functionality for the local_sitesync plugin.
 * This function sets up the event listeners for the connection form, handles the
 * connection and disconnection actions, and resets the connection if needed.
 */
export const init = () => {
    const form = document.getElementById('Connection-form');

    CONFIG.SLAVE_SITE_URL = $(SELECTORS.INPUT_SITE_URL).val().trim();

    CONFIG.SLAVE_ACCESS_TOKEN = $(SELECTORS.INPUT_ACCESS_TOKEN).val().trim();

    initiateConnectionSteps();
    form.addEventListener('submit', async (e) => {
        e.preventDefault();


        const action = e.target.querySelector("button.formaction").getAttribute("data-action");

        if (action == "disconnect") {
            await reset_confirmaton_modal();
        }

        if (action == "connect") {
            // await establishConnection(siteUrl, accessToken);
            initiateConnectionSteps();
        }

    });

    $(document).on('click', SELECTORS.REMOVE_CONNECTION, async () => {

        let response = await resetConnection();

        if (response) {
            window.location.reload();
        }
    });
};

/**
 * Initiates the connection steps for the local_sitesync plugin.
 * This function retrieves the slave validation data, sets the slave site URL and access token,
 * and then executes the connection steps defined in the `connectionSteps` array.
 * If any errors occur during the connection process, a notification is displayed.
 */
const initiateConnectionSteps = async () => {
    CONFIG.SLAVE_VALIDATION_DATA = await getSlaveValidationData();

    CONFIG.SLAVE_SITE_URL = $(SELECTORS.INPUT_SITE_URL).val().trim();

    CONFIG.SLAVE_ACCESS_TOKEN = $(SELECTORS.INPUT_ACCESS_TOKEN).val().trim();
    if (CONFIG.SLAVE_SITE_URL && CONFIG.SLAVE_ACCESS_TOKEN) {
        (async () => {
            let stepResult;
            try {
                for (const step of connectionSteps) {
                    stepResult = await step();
                }
            } catch (error) {
                Notification.addNotification({
                    message: `Connection process failed: ${error.message}`,
                    type: 'error'
                });
                return;
            }
        })();
    }
};
/**
 * Displays a confirmation modal to the user, asking if they are sure they want to disconnect.
 * The modal has a title "Add public key" and a body message "Are you sure you want to disconnect?".
 * The modal has a footer with a "Yes" button that has the ID "removeConnection".
 * The modal asked user if they want to disconnect.
 * @returns {Promise<Modal>} The created modal instance.
 */
const reset_confirmaton_modal = async () => {

    const modal = await Modal.create({
        title: 'Confirm connection reset',
        // eslint-disable-next-line max-len
        body: '<p>Are you sure you want to disconnect?</p>',
        footer: '<button type="button" class="btn btn-primary" id="removeConnection">Yes</button>',
        show: true,
        removeOnClose: true,
    });

    modal.show();

    return modal;
};

export const establishConnection = async (siteUrl, accessToken) => {
    var configdata = [
        {
            key: 'moodleurl',
            value: siteUrl,
            plugin: 'local_sitesync'
        },
        {
            key: 'accesstoken',
            value: accessToken,
            plugin: 'local_sitesync'
        }
    ];

    // Build the web service URL
    const wsUrl = `${siteUrl}/webservice/rest/server.php`;

    // Create form data for the request
    const formData = new FormData();
    formData.append('wstoken', accessToken);
    formData.append('wsfunction', 'local_sitesync_check_connection');
    formData.append('moodlewsrestformat', 'json');

    // Make the request to external site
    fetch(wsUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data === true) {
                configdata.push({
                    key: 'connection',
                    value: true,
                    plugin: 'local_sitesync'
                });
                saveConfigs(configdata);
                Notification.addNotification({
                    message: 'Connection successful',
                    type: 'success'
                });

                setTimeout(() => {
                    window.location.reload();
                }, 200);
            } else {
                configdata.push({
                    key: 'connection',
                    value: false,
                    plugin: 'local_sitesync'
                });
                saveConfigs(configdata);
                throw new Error('Connection failed');
            }
        })
        .catch(error => {
            configdata.push({
                key: 'connection',
                value: false,
                plugin: 'local_sitesync'
            });
            saveConfigs(configdata);
            Notification.addNotification({
                message: 'Connection failed: ' + error.message,
                type: 'error'
            });
        });
};


/**
 * Resets the connection configuration for the local_sitesync plugin.
 * This function sets the 'moodleurl', 'accesstoken', and 'connection' keys in the configdata array to false,
 * and then saves the updated configuration using the saveConfigs function.
 *
 * @returns {Promise<void>} A promise that resolves when the configuration has been saved.
 */
export const resetConnection = async () => {
    var configdata = [
        {
            key: 'moodleurl',
            value: false,
            plugin: 'local_sitesync'
        },
        {
            key: 'accesstoken',
            value: false,
            plugin: 'local_sitesync'
        },
        {
            key: 'connection',
            value: false,
            plugin: 'local_sitesync'
        }
    ];

    return saveConfigs(configdata);
};
/**
 * Retrieves the slave site validation data from the Moodle server.
 *
 * @returns {Promise<Object>} The response from the Moodle server containing the slave site validation data.
 */
const getSlaveValidationData = async () => {
    const request = {
        methodname: 'local_sitesync_compatibility_checker',
        args: {
            action: "get_slave_validation_data",
            config: JSON.stringify({})
        }
    };

    const response = await Ajax.call([request])[0];
    return response;
};

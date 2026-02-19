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
 * Edwiser Form new_form_main js
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
define([
    'jquery',
    'core/ajax',
    'core/templates',
    'local_edwiserform/form_styles',
    'local_edwiserform/iefixes',
    'local_edwiserform/efb_form_basic_settings',
    'local_edwiserform/formbuilder',
    'local_edwiserform/fastsearch',
    'local_edwiserform/fastselect'
], function($, ajax, Templates, formStyles) {
    var SELECTORS = {
        FORMTYPE: '#id_type',
        COMPONENT: 'local_edwiserform',
        EVENT: '#id_events',
        EVENT_LIST: '.efb-event-list',
        FORM_STYLE: '.efb-form-style',
        RENDER_FORM: '.render-form',
        PANEL: '.efb-panel-btn',
        TEMPLATE_OVERLAY: '.template-overlay',
        TITLE: '#id_title',
        ACTIVE: 'active',
        FORM_TITLE: '#efb-form-title',
        TEMPLATES_ROOT: '.efb-forms-setup-templates'
    };
    var CONSTANTS = {
        FORM_UPDATE: 1,
        FORM_OVERITE: 2,
        UPDATE_FAILED: 3
    };
    var formeo;
    var container = document.querySelector('.build-form');
    var renderContainer = document.querySelector(SELECTORS.RENDER_FORM);
    var saved = false;
    var fastselect = null;
    var formeoOpts = {
        container: container,
        // eslint-disable-next-line no-undef
        countries: countries,
        // eslint-disable-next-line no-undef
        license: license,
        svgSprite: M.cfg.wwwroot + '/local/edwiserform/pix/formeo-sprite.svg',
        localStorage: false,
    };
    var eventsLoaded = document.createEvent('Event');
    eventsLoaded.initEvent('eventsLoaded', true, true);
    window.eventlist = [];

    var PROMISES = {
        /**
         * Call moodle service edwiser_get_template
         * @param  {String}  type Type of form
         * @return {Promise}      Ajax promise call
         */
        GET_TEMPLATE: function(type) {
            return ajax.call([{
                methodname: 'edwiserform_get_template',
                args: {
                    name: type
                }
            }])[0];
        },
        /**
         * Call moodle service edwiser_get_template
         * @param  {String}  event Event name
         * @param  {Number}  id    Form id
         * @return {Promise}       Ajax promise call
         */
        GET_EVENT_SETTINGS: function(event, id) {
            return ajax.call([{
                methodname: 'edwiserform_get_event_settings',
                args: {
                    event: event,
                    id: id
                }
            }])[0];
        },
        /**
         * Call moodle service edwiser_get_template
         * @param  {Object}  settings JSON form settings
         * @param  {String}  formdef  JSON form definition
         * @return {Promise}          Ajax promise call
         */
        CREATE_NEW_FORM: function(settings, formdef) {
            return ajax.call([{
                methodname: "edwiserform_create_new_form",
                args: {
                    setting: settings,
                    formdef: formdef.toString()
                }
            }])[0];
        },
        /**
         * Call moodle service edwiser_get_template
         * @param  {Object}  settings form settings object
         * @param  {String}  formdef  JSON form definition
         * @return {Promise}          Ajax promise call
         */
        UPDATE_FORM: function(settings, formdef) {
            return ajax.call([{
                methodname: "edwiserform_update_form_settings",
                args: {
                    setting: settings,
                    formdef: formdef.toString()
                }
            }])[0];
        }
    };

    var TEMPLATES = {
        /**
         * Call moodle template event_settings_container
         * @param  {String} event Event type
         * @param  {String} name  Event name
         * @return {Promise}      Ajax promise call
         */
        SETTINGS_CONTAINER: function(event, name) {
            var obj = {
                event: event,
                name: name
            };
            return Templates.render('local_edwiserform/event_settings_container', obj);
        }
    };

    /**
     * Reset form callback
     */
    var resetForm = function() {
        formeo.dom.loading();
        var formtype = $(SELECTORS.FORMTYPE).val();
        PROMISES.GET_TEMPLATE(formtype)
            .done(function(response) {
                formeo.dom.loadingClose();
                if (response.status == true) {
                    formeoOpts.container = container;
                    // eslint-disable-next-line no-undef
                    formeo = new Formeo(formeoOpts, response.definition);
                    $(SELECTORS.EVENT).val().forEach(function(event) {
                        if (window.eventlist[event].hasOwnProperty('hasRequiredFields') &&
                            window.eventlist[event].hasRequiredFields()) {
                            addEventRequiredFields(event);
                        }
                    });
                    return;
                }
            }).fail(function(ex) {
                formeo.dom.loadingClose();
                formeo.dom.alert('danger', ex.message);
            });
    };
    formeoOpts.resetForm = resetForm;
    formeoOpts.resetable = $(SELECTORS.FORMTYPE).val() != 'blank';

    /**
     * Return callback for fastselect library option
     * @return {Object} Callback object
     */
    var fastselectCustomCallbacks = {
        removeSelectedOption: function(option, $choiceItem) {
            if (option.value == $(SELECTORS.FORMTYPE).val()) {
                formeo.dom.toaster(M.util.get_string(
                    'efb-cannot-remove-event',
                    SELECTORS.COMPONENT,
                    $(SELECTORS.FORMTYPE).find('option:selected').html()
                ), 3000);
                return;
            }
            var removedModel = this.optionsCollection.removeSelected(option);
            if (removedModel && removedModel.$item) {
                removedModel.$item.removeClass(this.options.itemSelectedClass);
            }
            if ($choiceItem) {
                $choiceItem.remove();
            } else {
                // eslint-disable-next-line no-undef
                this.$el.find(selectorFromClass(this.options.choiceItemClass) + '[data-value="' + option.value + '"]').remove();
            }
            this.updateDomElements();
            this.writeToInput();
            $('#efb-event-' + option.value).remove();
        },
        setSelectedOption: function(option) {
            if (this.optionsCollection.isSelected(option.value)) {
                return;
            }
            if (typeof window.eventlist[option.value] == 'undefined') {
                formeo.dom.alert('danger', M.util.get_string('efb-technical-issue-reload', SELECTORS.COMPONENT), function() {
                    window.location.reload();
                });
                return;
            }
            if (window.eventlist[option.value].hasOwnProperty('hasRequiredFields') &&
                window.eventlist[option.value].hasRequiredFields()) {
                addEventRequiredFields(option.value);
            }
            this.optionsCollection.setSelected(option);
            var selectedModel = this.optionsCollection.findWhere(function(model) {
                return model.value === option.value;
            });
            if (this.isMultiple) {
                // eslint-disable-next-line babel/no-unused-expressions
                this.$controls && this.addChoiceItem(option);
            } else {
                // eslint-disable-next-line babel/no-unused-expressions
                this.fastsearch && this.fastsearch.$resultItems.removeClass(this.options.itemSelectedClass);
                // eslint-disable-next-line babel/no-unused-expressions
                this.$toggleBtn && this.$toggleBtn.text(option.text);
            }
            // eslint-disable-next-line babel/no-unused-expressions
            selectedModel && selectedModel.$item.addClass(this.options.itemSelectedClass);
            this.updateDomElements();
            // eslint-disable-next-line no-undef
            pluginhassetting.forEach(function(plugin) {
                if (option.value == plugin) {
                    getEventSettings(plugin)
                        .done(function(response) {
                            TEMPLATES.SETTINGS_CONTAINER(plugin, option.text)
                                .done(function(html, js) {
                                    Templates.appendNodeContents($(SELECTORS.EVENT_LIST), html, js);
                                    $('#efb-event-' + plugin + ' .efb-event-settings').html(response.settings);
                                    if (response.status == true && window.eventlist[plugin].hasOwnProperty('init')) {
                                        window.eventlist[plugin].init();
                                    }
                                });
                        });
                }
            });
        }
    };

    /**
     * Check whether form definition is empty or not
     * @return {Boolean} Return true is definition is empty
     */
    function emptyFormDefinition() {
        let formdef = JSON.parse(getFormDef());
        return Object.keys(formdef.fields).length == 0;
    }

    /**
     * Check whether form preview can be seen and toggle preview button
     */
    function canSeePreview() {
        var emptyform = emptyFormDefinition();
        $('#efb-form-preview').toggleClass('d-none', emptyform);
        $('.efb-form-step-preview').toggleClass('d-none', emptyform);
    }

    /**
     * Get form definition from formeo object
     * @return {String} JSON form data
     */
    function getFormDef() {
        return formeo.formData;
    }

    /**
     * Check whether admin/teacher can save current form
     * @return {Boolean} True is admin/teacher can save form
     */
    function canSaveForm() {
        var status = checkTemplate() && checkTitle(false) && !emptyFormDefinition();
        $('#efb-btn-save-form-settings').parents('.efb-editor-button').toggleClass('d-none', !status);
        $('.efb-form-save').toggleClass('d-none', !status);
        return status;
    }

    /**
     * Get event settings
     * @param  {String}  event Event name
     * @return {Promise}
     */
    function getEventSettings(event) {
        var id = $("[name='id']").val();
        var promise = PROMISES.GET_EVENT_SETTINGS(event, id != '' ? id : null);
        promise.fail(function(ex) {
            formeo.dom.loadingClose();
            // eslint-disable-next-line no-console
            console.log(ex);
        });
        return promise;
    }

    /**
     * Is template is selected or not
     * @return {Boolean} True is template selected
     */
    function checkTemplate() {
        return $(SELECTORS.TEMPLATES_ROOT).find('.template.active').length > 0;
    }

    /**
     * Switch panel using panel id
     * @param {String} id Panel id
     */
    function switchPanel(id) {
        $(SELECTORS.PANEL).removeClass(SELECTORS.ACTIVE);
        $('#efb-' + id).addClass(SELECTORS.ACTIVE);
        $('.efb-tabcontent').removeClass(SELECTORS.ACTIVE);
        $('#efb-cont-' + id).addClass(SELECTORS.ACTIVE);
    }

    /**
     * Check form title
     * @param  {Boolean} showError If true then error will be shown
     * @return {Boolean}           If title is not empty then return true
     */
    function checkTitle(showError) {
        if (showError == undefined) {
            showError = true;
        }
        let settings = getFormSettings();
        var active = $(SELECTORS.PANEL + '.' + SELECTORS.ACTIVE).attr('id');
        var emptytitle = settings.title != '';
        if (!showError) {
            return emptytitle;
        }
        if (!emptytitle) {
            switch (active) {
                case 'efb-form-setup':
                case 'efb-form-builder':
                case 'efb-form-preview':
                    switchPanel('form-settings');
                    $(SELECTORS.TITLE).parents('.fitem').addClass('has-danger');
                    break;
                case 'efb-form-settings':
                    switchPanel('form-settings');
                    $(SELECTORS.TITLE).parents('.fitem').addClass('has-danger');
                    break;
            }
            formeo.dom.toaster(M.util.get_string('efb-lbl-title-warning', SELECTORS.COMPONENT), 3000);
        } else {
            $('.efb-form-title-container').removeClass('has-danger');
            $(SELECTORS.TITLE).parents('.fitem').removeClass('has-danger');
        }
        return emptytitle;
    }

    /**
     * Toogle event setting on event ribbon click
     * @param {DOM}     _this        Clicked DOM element
     * @param {Boolean} collapseOnly True if only one event settings can be uncollapsed
     */
    function toggleEventSettings(_this, collapseOnly) {
        if ($(_this).parent('.efb-event').hasClass('collapsed')) {
            if (!collapseOnly) {
                $('.efb-event:not(.collapsed) h4').each(function(i, node) {
                    $(node).trigger('click', [true]);
                });
            }
            $(_this).parent('.efb-event').removeClass('collapsed');
            $(_this).next().height('auto');
            let height = $(_this).next()[0].clientHeight + 'px';
            $(_this).next().height('0px');
            setTimeout(function() {
                $(_this).next().height(height);
            }, 0);
        } else {
            $(_this).next().height('0px');
            $(_this).next()[0].addEventListener('transitionend', function() {
                $(_this).parent('.efb-event').addClass('collapsed');
            }, {
                once: true
            });
        }
    }

    /**
     * Add event required fields in the form
     * @param {String} name Name of template
     */
    var addEventRequiredFields = function(name) {
        PROMISES.GET_TEMPLATE(name).done(function(response) {
            if (response.status == true) {
                var formData = JSON.parse(response.definition);
                Object.keys(formData.fields).forEach(function(field) {
                    if (formData.fields[field].hasOwnProperty('attrs') &&
                        formData.fields[field].attrs.hasOwnProperty('required') &&
                        formData.fields[field].attrs.required &&
                        formData.fields[field].hasOwnProperty('attrs') &&
                        formData.fields[field].attrs.hasOwnProperty('name') &&
                        !formeo.dom.hasFieldWithName(formData.fields[field].attrs.name)
                    ) {
                        delete formData.fields[field].attrs.template;
                        formeo.addMissingField(formData.fields[field]);
                    }
                });
            }
        }).fail(function(ex) {
            $('[data-template="' + name + '"]').parents(SELECTORS.TEMPLATE_OVERLAY).removeClass('loading');
            formeo.dom.alert('danger', ex.message);
        });
    };

    /**
     * Get date from form settings
     * @param  {String} id Date element id
     * @return {Number}    Date in unix timestamp format
     */
    function getDate(id) {
        if ($(id + '_enabled').is(':checked')) {
            let datetime = $(id + '_year').val();
            datetime += '/' + $(id + '_month').val();
            datetime += '/' + $(id + '_day').val();
            datetime += ' ' + $(id + '_hour').val();
            datetime += ':' + $(id + '_minute').val() + ':00';
            return (new Date(datetime)).getTime() / 1000;
        }
        return 0;
    }

    /**
     * Get form settings from Moodle form
     * @param  {Boolean} getEventsSettings If true then event settings will be returned along with main settings
     * @return {Object}                    Form settings
     */
    function getFormSettings(getEventsSettings = false) {
        var type = $(SELECTORS.FORMTYPE).val();
        var selectedevents = $(SELECTORS.EVENT).val();
        var data = {
            "title": $(SELECTORS.TITLE).val(),
            "description": $("#id_description").val(),
            "type": type,
            "events": selectedevents.join(),
            "enable_notifi_email": $("#id_enable_notification").prop('checked'),
            "notifi_email": $("#id_notifi_email").val(),
            "notifi_email_subject": $("#id_notifi_email_subject").val(),
            "notifi_email_body": $("#id_notifi_email_body").val(),
            "emailbodydraftitemid": $('[name="notifi_email_body[itemid]"]').val(),
            "confirmation_subject": $("#id_confirmation_subject").val(),
            "confirmation_message": $("#id_confirmation_msg").val(),
            "msgdraftitemid": $('[name="confirmation_msg[itemid]"]').val(),
            "data_edit": $("#id_editdata").prop('checked'),
            "success_message": $("#id_success_message").val(),
            // eslint-disable-next-line no-undef
            "style": selectedStyle,
            "allowsubmissionsfromdate": getDate('#id_allowsubmissionsfromdate'),
            "allowsubmissionstodate": getDate('#id_allowsubmissionstodate'),
            "eventsettings": ""
        };
        if (getEventsSettings == true) {
            var eventsettings = {};
            selectedevents.forEach(function(event) {
                // eslint-disable-next-line no-undef
                pluginhassetting.forEach(function(plugin) {
                    if (event == plugin) {
                        eventsettings[event] = window.eventlist[event].getSettings();
                    }
                });
            });
            data.eventsettings = JSON.stringify(eventsettings);
        }
        return data;
    }

    /**
     * Call ajax service to save form settings
     * @param {String}   action     Service name
     * @param {Object}   settings   Form settings
     * @param {String}   formdef    Form definition
     * @param {Function} callable   Callback function
     */
    function saveFormSettings(action, settings, formdef, callable) {
        formeo.dom.loading();
        (action == 'create' ? PROMISES.CREATE_NEW_FORM(settings, formdef) : PROMISES.UPDATE_FORM(settings, formdef))
        .done(callable).fail(function(ex) {
            formeo.dom.loadingClose();
            formeo.dom.alert('danger', ex.message);
        });
    }

    /**
     * Select template using template name and template data
     * @param {String} formtype Form/template type
     * @param {String} template Template definition
     */
    function selectTemplate(formtype, template = "") {
        formeoOpts.resetable = formtype != 'blank';
        $(SELECTORS.FORMTYPE).val(formtype);

        // Triggering change event.
        var changeEvent = new CustomEvent("change", {
            target: $(SELECTORS.FORMTYPE)[0]
        });
        $(SELECTORS.FORMTYPE)[0].dispatchEvent(changeEvent);
        var events = [];
        events.push(formtype);
        $(SELECTORS.EVENT).val(events);

        // Triggering change event.
        changeEvent = new CustomEvent("change", {
            target: $(SELECTORS.EVENT)[0]
        });
        $(SELECTORS.EVENT)[0].dispatchEvent(changeEvent);
        fastselect.destroy();
        fastselect.init($(SELECTORS.EVENT), $(SELECTORS.EVENT).val());
        initializeTemplate();
        formeoOpts.container = container;
        // eslint-disable-next-line no-undef
        formeo = new Formeo(formeoOpts, template);
        $("#efb-form-settings").trigger('click');
    }

    /**
     * Show template warning if license is not active
     * @param {DOM} target DOM target template element
     */
    function showTargetWarning(target) {
        formeo.dom.multiActions(
            'warning',
            M.util.get_string("attention", SELECTORS.COMPONENT),
            M.util.get_string("efb-template-inactive-license", SELECTORS.COMPONENT, $(target).data('template-name')), [{
                title: M.util.get_string('activatelicense', SELECTORS.COMPONENT),
                type: 'success',
                action: function() {
                    window.location.href = M.cfg.wwwroot +
                        '/admin/settings.php?section=local_edwiserform&activetab=local_edwiserform_license_status';
                }
            }, {
                title: M.util.get_string('cancel', SELECTORS.COMPONENT),
                type: 'warning'
            }]
        );
    }

    /**
     * Change heading of top bar
     * @param {String} formName Form name/title
     */
    function changeHeading(formName) {
        $(".efb-editor-action").toggleClass("efb-hide", formName.length < 0);
        if (formName.length < 0) {
            $("#id_error_template_title").show();
        } else {
            $("#id_error_template_title").hide();
        }
        if (formName.length > 33) {
            formName = formName.substr(0, 30) + '...';
        }
        $(".efb-form-title").text(formName);
    }

    /**
     * Initialize template and events associated with it
     */
    function initializeTemplate() {
        // Initialise event settings.
        $(SELECTORS.FORMTYPE).closest('.fitem').hide();
        if ($(SELECTORS.EVENT_LIST).length == 0) {
            $('#efb-settings-events').append('<div class="efb-event-list"></div>');
        } else {
            $(SELECTORS.EVENT_LIST).empty();
        }
        let label = $('#id_allowsubmissionsfromdate_enabled').parent();
        let container = $(label).parent();
        label.detach().prependTo(container);
        label = $('#id_allowsubmissionstodate_enabled').parent();
        container = $(label).parent();
        label.detach().prependTo(container);
        $(SELECTORS.EVENT).val().forEach(function(event) {
            // eslint-disable-next-line no-undef
            pluginhassetting.forEach(function(plugin) {
                if (event == plugin) {
                    getEventSettings(event)
                        .done(function(response) {
                            TEMPLATES.SETTINGS_CONTAINER(event, $(SELECTORS.EVENT).find('option[value="' + event + '"]').html())
                                .done(function(html, js) {
                                    Templates.appendNodeContents($(SELECTORS.EVENT_LIST), html, js);
                                    $('#efb-event-' + event + ' .efb-event-settings').html(response.settings);
                                    if (typeof window.eventlist[event] == 'undefined') {
                                        formeo.dom.alert(
                                            'danger',
                                            M.util.get_string('efb-technical-issue-reload', SELECTORS.COMPONENT),
                                            function() {
                                                window.location.reload();
                                            }
                                        );
                                        return;
                                    }
                                    if (response.status == true && window.eventlist[event].hasOwnProperty('init')) {
                                        window.eventlist[event].init();
                                    }
                                });
                        });
                }
            });
        });
        if (fastselect === null) {
            fastselect = $(SELECTORS.EVENT).hide().fastselect({
                callbacks: fastselectCustomCallbacks
            });
        }
    }

    /**
     * Replace previous template with newly selected template
     * @param  {DOM} template Currently selected template DOM element
     */
    function replaceTemplate(template) {
        var formtype = $(template).data("template");
        $(template).parents(SELECTORS.TEMPLATE_OVERLAY).addClass('loading');
        PROMISES.GET_TEMPLATE(formtype).done(function(response) {
            $(template).parents(SELECTORS.TEMPLATE_OVERLAY).removeClass('loading');
            if (response.status == true) {
                selectTemplate(formtype, response.definition);
                return;
            }
            if (response.msg != 'license-inactive') {
                formeo.dom.alert('warning', response.msg, function() {
                    selectTemplate(formtype, response.definition);
                });
            }
            showTargetWarning(template);
        }).fail(function(ex) {
            $(template).parents(SELECTORS.TEMPLATE_OVERLAY).removeClass('loading');
            formeo.dom.alert('danger', ex.message);
        });
    }

    /**
     * Action to be performed after creating form
     * @param  {Object} response Ajax response
     */
    var formCreateAction = function(response) {
        formeo.dom.loadingClose();
        if (response.status == true) {
            window.onbeforeunload = null;
            saved = true;
            response.formname = $(SELECTORS.TITLE).val();
            // eslint-disable-next-line no-undef, camelcase
            if (mod_edwiserform_return != undefined && mod_edwiserform_return == true) {
                window.opener.local_edwiserform_add_new_form(response);
                return;
            }
            formeo.dom.alert('success', response.msg, function() {
                formeo.reset();
                $(location).attr('href', M.cfg.wwwroot + "/local/edwiserform/view.php?page=listforms");
            });
            setTimeout(function() {
                $(location).attr('href', M.cfg.wwwroot + "/local/edwiserform/view.php?page=listforms");
            }, 4000);
        } else {
            formeo.dom.alert('danger', response.msg);
        }
    };

    /**
     * Action to be performed after updating form
     * @param  {Object} response Ajax response
     */
    var formUpdateAction = function(response) {
        formeo.dom.loadingClose();
        if (response.status == CONSTANTS.FORM_UPDATE) {
            window.onbeforeunload = null;
            response.formname = $(SELECTORS.TITLE).val();
            // eslint-disable-next-line no-undef, camelcase
            if (mod_edwiserform_return != undefined && mod_edwiserform_return == true) {
                window.opener.local_edwiserform_edit_form(response);
                return;
            }
            formeo.dom.multiActions(
                'success',
                M.util.get_string("success", SELECTORS.COMPONENT),
                response.msg, [{
                    title: M.util.get_string("efb-heading-listforms", SELECTORS.COMPONENT),
                    type: 'primary',
                    action: function() {
                        $(location).attr('href', M.cfg.wwwroot + "/local/edwiserform/view.php?page=listforms");
                    }
                }, {
                    title: M.util.get_string("close", SELECTORS.COMPONENT),
                    type: 'default'
                }]
            );
        } else if (response.status == CONSTANTS.FORM_OVERITE) {
            var action = "update";
            var formdef = getFormDef();
            var settings = getFormSettings(true);
            settings.forceupdate = true;
            settings.id = $("[name='id']").val();
            formeo.dom.multiActions(
                'warning',
                M.util.get_string("attention", SELECTORS.COMPONENT),
                response.msg, [{
                    title: M.util.get_string("proceed", SELECTORS.COMPONENT),
                    type: 'warning',
                    action: function() {
                        saveFormSettings(action, settings, formdef, formUpdateAction);
                    }
                }, {
                    title: M.util.get_string("cancel", SELECTORS.COMPONENT),
                    type: 'success'
                }]
            );
        } else {
            formeo.dom.alert('danger', response.msg);
        }
    };

    /**
     * Innitialize form events
     */
    function initializeEvents() {
        $('.efb-form-style-container .controls-toggle').click(function() {
            $(this).parents('.preview-form').toggleClass('show-styles');
            $('#efb-cont-form-builder .formeo-controls').closest('.build-form').toggleClass('hidden-controls');
        });
        $(SELECTORS.FORM_STYLE).mouseenter(function() {
            $('.form-style-preview').attr('src', $(this).find('img').attr('src')).css('top', $(this).offset().top).show();
        }).mouseleave(SELECTORS.FORM_STYLE, function() {
            $('.form-style-preview').attr('src', '').hide();
        }).click(SELECTORS.FORM_STYLE, function() {
            $(SELECTORS.FORM_STYLE).removeClass('selected');
            $(this).addClass('selected');
            // eslint-disable-next-line no-undef
            for (var i = 1; i <= supportedStyles; i++) {
                if ($(SELECTORS.RENDER_FORM).hasClass('form-style-' + i)) {
                    formStyles.apply($(SELECTORS.RENDER_FORM), 'remove', i);
                }
            }
            // eslint-disable-next-line no-undef
            selectedStyle = $(this).data('form');
            formStyles.apply($(SELECTORS.RENDER_FORM), 'add', $(this).data('form'));
        });
        $('.efb-settings-tab-list-item').click(function() {
            // eslint-disable-next-line no-undef
            if (license != 'available' && $(this).data('target') == 'efb-settings-events') {
                formeo.dom.multiActions(
                    'warning',
                    M.util.get_string("attention", SELECTORS.COMPONENT),
                    M.util.get_string("efb-template-inactive-license", SELECTORS.COMPONENT, $(this).find('h4').text()), [{
                        title: M.util.get_string('activatelicense', SELECTORS.COMPONENT),
                        type: 'success',
                        action: function() {
                            window.location.href = M.cfg.wwwroot +
                                '/admin/settings.php?section=local_edwiserform&activetab=local_edwiserform_license_status';
                        }
                    }, {
                        title: M.util.get_string('cancel', SELECTORS.COMPONENT),
                        type: 'warning'
                    }]
                );
                return;
            }
            $('.efb-settings-tab-list-item').removeClass('active');
            $(this).addClass('active');
            $('.efb-settings-tab').removeClass('active');
            $('#' + $(this).data('target')).addClass('active');
        });
        $('.step-navigation #previous-step').click(function() {
            return;
        });
        $('.step-navigation #next-step').click(function() {
            return;
        });
        $(document).on('formeoUpdated', function() {
            var status = canSaveForm();
            canSeePreview();
            if (!status) {
                $(SELECTORS.EVENT).val().forEach(function(event) {
                    if (window.eventlist[event].hasOwnProperty('hasRequiredFields') &&
                        window.eventlist[event].hasRequiredFields()) {
                        addEventRequiredFields(event);
                    }
                });
            }
            formeo.dom.toggleImportButton();
        });

        $(".efb-form-step").click(function(event) {
            event.preventDefault();
            var id = $(this).data('id');
            $('#' + id).click();
        });
        $(SELECTORS.PANEL).click(function(event) {
            if (!checkTemplate()) {
                formeo.dom.toaster(M.util.get_string('efb-select-template-warning', SELECTORS.COMPONENT), 3000);
                switchPanel('form-setup');
                event.preventDefault();
                return;
            }
            var id = $(this).attr('id');
            if (id != 'efb-form-setup' && id != 'efb-form-settings' && !checkTitle()) {
                event.preventDefault();
                return;
            }
            canSaveForm();
            var eleCont = $(this).data("panel");
            $("#efb-form-settings, #efb-form-builder, #efb-form-preview, #efb-form-setup").removeClass(SELECTORS.ACTIVE);
            $("#efb-cont-form-settings, #efb-cont-form-builder, #efb-cont-form-preview, #efb-cont-form-setup")
                .removeClass(SELECTORS.ACTIVE);

            $(eleCont).addClass(SELECTORS.ACTIVE);
            $(this).addClass(SELECTORS.ACTIVE);
            $(".efb-forms-panel-heading").text($(this).data("panel-lbl"));

            if ("#efb-cont-form-preview" == eleCont) {
                formeo.render(renderContainer);
                // eslint-disable-next-line no-undef
                for (var i = 1; i <= supportedStyles; i++) {
                    if ($(SELECTORS.RENDER_FORM).hasClass('form-style-' + i)) {
                        formStyles.apply($(SELECTORS.RENDER_FORM), 'remove', i);
                    }
                }
                // eslint-disable-next-line no-undef
                formStyles.apply($(SELECTORS.RENDER_FORM), 'add', selectedStyle);
            }
        });

        $('body').on('click', '.efb-event-label', function(event, collapseOnly = null) {
                if ($(this).next().html() == '') {
                    return;
                }
                toggleEventSettings(this, collapseOnly);
            })
            // Copy email tag.
            .on('click', '.efb-email-tag', function() {
                var temp = $('<input>');
                $('body').append(temp);
                var shortcode = $(this).text();
                temp.val(shortcode).select();
                document.execCommand('copy');
                temp.remove();
                formeo.dom.toaster(M.util.get_string('shortcodecopied', SELECTORS.COMPONENT, shortcode), 3000);
            })
            // This will save the form settings using ajax.
            .on("click", "#efb-btn-save-form-settings", function(event) {
                if (!checkTemplate()) {
                    formeo.dom.toaster(M.util.get_string('efb-select-template-warning', SELECTORS.COMPONENT), 3000);
                    switchPanel('form-setup');
                    event.preventDefault();
                    return;
                }
                if (!checkTitle() || !formeo.validator.validate()) {
                    return;
                }
                var settings = getFormSettings(true);
                if (settings.allowsubmissionsfromdate >= settings.allowsubmissionstodate && settings.allowsubmissionstodate != 0) {
                    $('#id_allowsubmissionstodate_calendar').parent('.fdate_time_selector').next().show()
                        .text(M.util.get_string('allowsubmissionscollapseddate', SELECTORS.COMPONENT))
                        .parents('.form-group.row.fitem').addClass('has-danger');
                    return;
                } else {
                    $('#id_allowsubmissionstodate_calendar').parent('.fdate_time_selector').next().show()
                        .text('').parents('.form-group.row.fitem').removeClass('has-danger');
                }
                var formdef = getFormDef();
                var formid = $("[name='id']").val();
                var action = "create";
                if (formid) {
                    formeo.dom.multiActions(
                        'warning',
                        M.util.get_string("attention", SELECTORS.COMPONENT),
                        M.util.get_string("efb-forms-update-confirm", SELECTORS.COMPONENT), [{
                            title: M.util.get_string("efb-forms-update-create-new", SELECTORS.COMPONENT),
                            type: 'primary',
                            action: function() {
                                saveFormSettings(action, settings, formdef, formCreateAction);
                            }
                        }, {
                            title: M.util.get_string("efb-forms-update-overwrite-existing", SELECTORS.COMPONENT),
                            type: 'warning',
                            action: function() {
                                action = "update";
                                settings.id = formid;
                                saveFormSettings(action, settings, formdef, formUpdateAction);
                            }
                        }]
                    );
                    return;
                }
                if (!saved) {
                    saveFormSettings(action, settings, formdef, formCreateAction);
                } else {
                    formeo.dom.toaster(M.util.get_string('efb-form-setting-saved', SELECTORS.COMPONENT), 3000);
                }
            });

        $(SELECTORS.FORM_TITLE).keyup(function() {
            var formName = $(this).val();
            var empty = formName == '';
            $(this).parent().toggleClass('has-danger', empty);
            $(SELECTORS.TITLE).val(formName);
            changeHeading(formName);
        }).change(function() {
            canSaveForm();
        });
        $(SELECTORS.TITLE).keyup(function() {
            var formName = $(this).val();
            $(SELECTORS.TITLE).val(formName);
            changeHeading(formName);
        }).change(function() {
            var formName = $(this).val();
            $(SELECTORS.FORM_TITLE).val(formName);
            canSaveForm();
        });

        $(SELECTORS.FORMTYPE).change(function() {
            $(SELECTORS.TEMPLATES_ROOT).find(".template").removeClass("active");
            var type = $(this).val();
            $(SELECTORS.TEMPLATES_ROOT).find("#template-" + type).addClass("active");
            $('#id_registration-enabled').prop('checked', true);
        });
        $(SELECTORS.TEMPLATES_ROOT).find(".template-select").click(function() {
            var _this = this;
            // eslint-disable-next-line no-undef
            if ($(this).data('pro') == true && license != 'available') {
                showTargetWarning(this);
                return;
            }
            canSaveForm();
            if ($(SELECTORS.TEMPLATES_ROOT).find(".template.active").length && !emptyFormDefinition()) {
                formeo.dom.multiActions(
                    'warning',
                    M.util.get_string("attention", SELECTORS.COMPONENT),
                    M.util.get_string("efb-template-change-warning", SELECTORS.COMPONENT), [{
                        title: M.util.get_string('proceed', SELECTORS.COMPONENT),
                        type: 'warning',
                        action: function() {
                            replaceTemplate(_this);
                        }
                    }, {
                        title: M.util.get_string('cancel', SELECTORS.COMPONENT),
                        type: 'success'
                    }]
                );
            } else {
                replaceTemplate(_this);
            }
        });
        $(document).on('formeoLoaded', function() {
            canSeePreview();
        });
        document.addEventListener('eventsLoaded', function() {
            initializeTemplate();
            canSaveForm();
            formeo.dom.loadingClose();
        });
    }

    /**
     * Load all plugin js which support form settings to use further
     */
    function loadPluginJs() {
        let loaded = 0;
        var allevents = $(SELECTORS.EVENT).find('option').map(function(index, event) {
            return event.value;
        });
        if (allevents.length > 0) {
            allevents.each(function(index, plugin) {
                require(['edwiserformevents_' + plugin + '/settings'], function(eventjs) {
                    window.eventlist[plugin] = eventjs;
                    if (allevents.length == ++loaded) {
                        $('.efb-form-builder-wrap').show();
                        initializeEvents();
                        document.dispatchEvent(eventsLoaded);
                    }
                });
            });
            return;
        }
        initializeEvents();
        $('.efb-form-builder-wrap').show();
    }

    /**
     * Main method which call when this js is loaded
     * @param {String} sitekey Google recaptcha Site key
     */
    var init = function(sitekey) {
        $(document).ready(function() {
            formeoOpts.sitekey = sitekey;
            if (typeof formdefinition != 'undefined') {
                // eslint-disable-next-line no-undef
                formeo = new Formeo(formeoOpts, formdefinition);
            } else {
                // eslint-disable-next-line no-undef
                formeo = new Formeo(formeoOpts);
            }
            canSeePreview();
            $('#root-page-loading').hide();
            formeo.dom.loading();
            loadPluginJs();
        });
    };
    return {
        init: init
    };
});

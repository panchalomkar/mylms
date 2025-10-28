'use strict';
import animate from './common/animation';
import h from './common/helpers';
import {closest, hideControl, getString, objToStrMap, clone} from './common/utils';
import {data, formData} from './common/data';
import events from './common/events';
import actions from './common/actions';
import dom from './common/dom';
import {Controls} from './components/controls';
import Field from './components/field';
import Stage from './components/stage';
import validator from './components/validator';
import 'mdn-polyfills/Object.assign';
import 'mdn-polyfills/Object.values';
import 'dom-node-polyfills';
import 'element-remove';

// Simple object config for the main part of formeo
const formeo = {
  get formData() {
    return data.json;
  }
};
let opts = {};

/**
 * Main class
 */
class Formeo {
  /**
   * [constructor description]
   * @param  {Object} options  formeo options
   * @param  {String|Object}   userFormData [description]
   * @return {Object}          formeo references and actions
   */
  constructor(options, userFormData = null) {
    // Default options
    const defaults = {
      allowEdit: true,
      dataType: 'json',
      localStorage: true,
      container: '.formeo-wrap',
      prefix: 'formeo-',
      // SvgSprite: null, // change to null
      iconFontFallback: null, // 'glyphicons' || 'font-awesome' || 'fontello'
      events: {},
      actions: {},
      controls: {},
      config: {
        rows: {},
        columns: {},
        fields: {}
      }
    };

    const _this = this;
    dom.countries = options.countries;
    _this.container = options.container || defaults.container;
    dom.container = _this.container;
    dom.sitekey = options.sitekey || '';
    dom.license = options.license || 'unavailable';
    this.resetable = options.resetable || false;
    this.resetForm = options.resetForm || false;
    if (typeof _this.container === 'string') {
      _this.container = document.querySelector(_this.container);
    }

    // Remove `container` property before extending because container
    // may be Element
    delete options.container;

    opts = h.merge(defaults, options);
    data.init(opts, userFormData);
    events.init(opts.events);
    actions.init(opts.actions);
    formeo.dom = dom;
    formeo.reset = data.reset;
    formeo.addMissingField = this.addMissingField;
    formeo.validator = validator;
    // Load remote resources such as css and svg sprite
    dom.setConfig = opts.config;
    formeo.render = renderTarget => {
      dom.renderForm(renderTarget);
    };
    if (opts.allowEdit) {
      formeo.edit = this.init;
      this.init();
    }
    return formeo;
  }

  /**
   * Return form settings container
   * @return {Object} formSettings
   */
  getFormSettings() {
    const defaultSettings = dom.getFormDefaultSettings();
    const formSettings = clone(defaultSettings);
    if (formData.settings.get('formSettings') != undefined) {
      Object.keys(formSettings).forEach(category => {
        Object.assign(formSettings[category], formData.settings.get('formSettings')[category]);
      });
    }
    formData.settings.set('formSettings', formSettings);
    data.save();
    const settings = {
      tag: 'ul',
      attrs: {
        className: 'form-settings-edit'
      },
      content: []
    };
    Object.keys(formSettings).forEach(category => {
      /* eslint prefer-const: 0 */
      let container = dom.getConfigContainer(category, false);
      Object.keys(formSettings[category]).forEach(id => {
        container.content[1].content.push(dom.getSettingItem(id, formSettings[category][id], defaultSettings[category][id], 'formSettings', 'form-config', category));
      });
      settings.content.push(container);
    });
    return settings;
  }

  /**
   * Return form setting toggle control
   * @return {Object} edit button to toggle between form design and settings
   */
  getFormSettingControl() {
    const edit = {
      tag: 'button',
      content: dom.icon('edit'),
      attrs: {
        className: ['btn btn-primary item-edit-toggle'],
        type: 'button',
        title: getString('edit-form')
      },
      meta: {
        id: 'edit'
      },
      action: {
        click: evt => {
          const element = closest(evt.target, 'formeo-editor');
          const fType = 'form-settings';
          const editClass = 'editing-' + fType;
          const editWindow = element.querySelector(`.${fType}-edit`);
          if (element.classList.contains(editClass)) {
            animate.slideUp(editWindow, 666, function() {
              animate.slideDown(editWindow.nextSibling, 666, function() {
                element.classList.remove(editClass);
              });
            });
          } else {
            animate.slideUp(editWindow.nextSibling, 666, function() {
              animate.slideDown(editWindow, 666, function() {
                element.classList.add(editClass);
              });
            });
          }
        }
      }
    };
    const deleteform = {
      tag: 'button',
      content: [{
        tag: 'i',
        attrs: {
          className: 'fa fa-trash',
          'aria-hidden': true
        }
      }],
      attrs: {
        className: 'btn btn-danger item-delete-form',
        type: 'button',
        title: getString('delete-form')
      },
      action: {
        click: evt => {
          if (formData.rows.size || formData.stages.size) {
            const confirmClearAll = new CustomEvent('confirmClearAll', {
              detail: {
                confirmationMessage: getString('confirmclearform'),
                clearAllAction: dom.clearForm.bind(dom),
                btnCoords: dom.coords(evt.target),
                rows: dom.rows,
                rowCount: dom.rows.size
              }
            });
            document.dispatchEvent(confirmClearAll);
          } else {
            dom.alert('info', getString('nofields'));
          }
        }
      },
      meta: {
        id: 'delete'
      }
    };
    const resetform = {
      tag: 'button',
      content: [{
        tag: 'i',
        attrs: {
          className: 'fa fa-repeat',
          'aria-hidden': true
        }
      }],
      attrs: {
        className: 'btn btn-warning item-reset-form',
        type: 'button',
        title: getString('reset-form')
      },
      action: {
        click: evt => {
          if (formData.rows.size || formData.stages.size) {
            const confirmReset = new CustomEvent('confirmReset', {
              detail: {
                confirmationMessage: getString('confirmresetform'),
                resetAction: this.resetForm,
                btnCoFrds: dom.coords(evt.target),
                rows: dom.rows,
                rowCount: dom.rows.size
              }
            });
            document.dispatchEvent(confirmReset);
          } else {
            dom.alert('info', getString('nofields'));
          }
        }
      },
      meta: {
        id: 'delete'
      }
    };
    let controls = 2;
    const actions = [];
    actions.push(deleteform);
    if (this.resetable) {
      controls++;
      actions.push(resetform);
    }
    actions.push(edit);
    return {
      tag: 'div',
      className: 'form-settings-actions group-actions control-count-' + controls,
      content: [{
        tag: 'div',
        className: 'action-btn-wrap',
        content: actions
      }]
    };
  }

  /**
   * Formeo initializer
   * @return {Object} References to formeo instance,
   * dom elements, actions events and more.
   */
  init() {
    this.formID = formData.id;
    formeo.controls = new Controls(opts.controls, this.formID);
    this.stages = this.buildStages();
    this.formSettings = this.getFormSettings();
    this.formSettingControl = this.getFormSettingControl();
    this.render();
    return formeo;
  }

  /**
   * Generate the stages we will drag out elements to
   * @return {Object} stages map
   */
  buildStages() {
    const stages = [];
    const createStage = stageID => new Stage(opts, stageID);
    if (formData.stages.size) {
      formData.stages.forEach((stageConf, stageID) => {
        stages.push(createStage(stageID));
      });
    } else {
      stages.push(createStage());
    }
    stages[0].classList.add('active');
    stages[0].classList.add('show');
    return stages;
  }

  /**
   * Return div containing import action
   * @return {DOM} element for importing form
   */
  getImportFormView() {
    const _this = this;
    const importFunction = function() {
      const button = document.getElementById('import-form-input');
      const error = document.querySelector('.import-form-error');
      error.innerHTML = '';
      const files = button.files;
      if (files.length == 0) {
        error.innerHTML = 'Please select file.';
        return false;
      }
      const file = files[0];
      if (file.type != 'application/json') {
        error.innerHTML = 'Please select json file';
        return false;
      }
      const reader = new FileReader();
      reader.onload = function(event) {
        try {
          let result = event.target.result;
          result = JSON.parse(result);
          for (const key of Object.keys(result.definition)) {
            if (key == 'id') {
              continue;
            }
            result.definition[key] = objToStrMap(result.definition[key]);
          }
          data.replaceFormData(result.definition);
          _this.init();
          _this.render();
        } catch (ex) {
          dom.alert(
              'warning',
              ex.message
          );
        }
      };
      reader.readAsText(file);
      return true;
    };
    return dom.getImportFormContainer(importFunction);
  }

  /**
   * Render the formeo sections
   * @return {void}
   */
  render() {
    const _this = this;
    const controls = formeo.controls.element;
    const content = [];
    let hideTabControl = false;
    if (formData.stages.size > 1) {
      hideTabControl = true;
      content.push(dom.getStageControl());
    }
    content.push(_this.stages);
    content.push(this.getImportFormView());
    const elemConfig = {
      tag: 'div',
      className: 'formeo formeo-editor tab-content',
      attrs: {
        id: _this.formID
      },
      content: [controls, {
        tag: 'div',
        className: 'form-wrapper',
        content: [_this.formSettingControl, _this.formSettings, {
          tag: 'div',
          className: 'form-container',
          content: content
        }]
      }]
    };

    const formeoElem = dom.create(elemConfig);
    _this.container.innerHTML = '';
    _this.container.appendChild(formeoElem);
    // Reposition all panels when body size changes
    const body = document.getElementsByTagName('body')[0];
    body.onresize = function(event) {
      const container = document.getElementsByClassName('formeo-editor')[0];
      dom.repositionPanels(container);
    };
    if (formData.stages.size > 1) {
      dom.tabSorting();
    }
    if (hideTabControl) {
      hideControl('layout-tab-control');
    }
    dom.toggleImportButton();
    dom.toggleFormDeleteAction();
    dom.checkSingle();
    events.formeoLoaded = new CustomEvent('formeoLoaded', {
      detail: {
        formeo: formeo
      }
    });
    document.dispatchEvent(events.formeoLoaded);
  }

  /**
   * Add missing field
   * @param {Object} field data to add in stage
   */
  addMissingField(field) {
    const row = dom.addRow(null, null);
    const column = dom.addColumn(row.id, null);
    field = new Field(field.meta.id, null, field);
    column.appendChild(field);
    dom.emptyClass(column);
    data.saveFieldOrder(column);
    dom.columnWidths(row);
    data.saveColumnOrder(row);
  }
}

if (window !== undefined) {
  window.Formeo = Formeo;
}

export default Formeo;

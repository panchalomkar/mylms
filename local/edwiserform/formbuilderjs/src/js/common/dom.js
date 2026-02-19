import Sortable from 'sortablejs';
import h from './helpers';
import events from './events';
import Row from '../components/row';
import Column from '../components/column';
import Field from '../components/field';
import animate from './animation';
import {data, formData, registeredFields as rFields} from './data';
import {uuid, clone, addTitle, getString, hideControl, showControl, numToPercent, remove, closest, closestFtype, elementTagType} from './utils';
import panels from '../components/panels';
import {defaultElements, addCompositeFields} from '../components/controls';
import conditions from './conditional_logic';

/**
 * General purpose markup utilities and generator.
 */
class DOM {
  /**
   * Set defaults, store references to key elements
   * like stages, rows, columns etc
   */
  constructor() {
    // Maintain references to DOM nodes
    // so we don't have to keep doing getElementById
    this.stages = new Map();
    this.rows = new Map();
    this.columns = new Map();
    this.fields = new Map();
    this.styleSheet = (() => {
      const style = document.createElement('style');
      style.setAttribute('media', 'screen');
      style.setAttribute('type', 'text/css');
      style.appendChild(document.createTextNode(''));
      document.head.appendChild(style);
      return style.sheet;
    })();
  }

  /**
   * Merges a user's configuration with default
   * @param  {Object} userConfig
   * @return {Object} config
   */
  set setConfig(userConfig) {
    const _this = this;
    const icon = _this.icon;
    const btnTemplate = {
      tag: 'button',
      content: [],
      attrs: {
        className: ['btn'],
        type: 'button'
      }
    };

    const handle = h.merge(Object.assign({}, btnTemplate), {
      content: [icon('move'), icon('handle')],
      attrs: {
        className: ['btn-default', 'item-handle'],
      },
      meta: {
        id: 'handle'
      }
    });

    const edit = h.merge(Object.assign({}, btnTemplate), {
      content: icon('edit'),
      attrs: {
        className: ['btn-primary', 'item-edit-toggle'],
      },
      meta: {
        id: 'edit'
      },
      action: {
        click: evt => {
          const element = closestFtype(evt.target);
          let {fType} = element;
          fType = fType.replace(/s$/, '');
          const editClass = 'editing-' + fType;
          const editWindow = element.querySelector(`.${fType}-edit`);
          animate.slideToggle(editWindow, 666);
          if (fType === 'field') {
            animate.slideToggle(editWindow.nextSibling, 666);
            element.parentElement.classList.toggle('column-' + editClass);
          }
          element.classList.toggle(editClass);
          if (fType === 'row') {
            this.checkRecaptcha(element, element.classList.contains(editClass));
          }
        }
      }
    });

    const remove = h.merge(Object.assign({}, btnTemplate), {
      content: icon('remove'),
      attrs: {
        className: ['btn-danger', 'item-remove'],
      },
      meta: {
        id: 'remove'
      },
      action: {
        click: (evt, id) => {
          const element = closestFtype(evt.target);
          if (_this.canRemoveElement(element)) {
            animate.slideUp(element, 666, elem => {
              _this.removeEmpty(element);
              _this.checkSingle();
            });
          }
        }
      }
    });

    const cloneItem = h.merge(Object.assign({}, btnTemplate), {
      content: icon('copy'),
      attrs: {
        className: ['btn-warning', 'item-clone'],
      },
      meta: {
        id: 'clone'
      },
      action: {
        click: evt => {
          _this.clone(closestFtype(evt.target));
          data.save();
        }
      }
    });

    const defaultConfig = {
      rows: {
        actionButtons: {
          buttons: [
            addTitle(handle, 'row-move'),
            addTitle(edit, 'row-edit'),
            addTitle(cloneItem, 'row-clone'),
            addTitle(remove, 'row-remove')
          ],
          order: [],
          disabled: []
        }
      },
      columns: {
        actionButtons: {
          buttons: [
            addTitle(handle, 'column-move'),
            addTitle(cloneItem, 'column-clone'),
            addTitle(remove, 'column-remove')
          ],
          order: [],
          disabled: []
        }
      },
      fields: {
        actionButtons: {
          buttons: [
            addTitle(handle, 'field-move'),
            addTitle(edit, 'field-edit'),
            addTitle(cloneItem, 'field-clone'),
            addTitle(remove, 'field-remove')
          ],
          order: [],
          disabled: []
        }
      },
    };

    defaultConfig.rows.actionButtons.buttons[0].content = [
      icon('move-vertical'),
      icon('handle')
    ];
    defaultConfig.columns.actionButtons.buttons[0].content = [
      icon('move'),
      icon('handle'),
    ];

    const mergedConfig = h.merge(defaultConfig, userConfig);

    Object.keys(mergedConfig).forEach(key => {
      if (mergedConfig[key].actionButtons) {
        const aButtons = mergedConfig[key].actionButtons;
        const disabled = aButtons.disabled;
        const buttons = aButtons.buttons;

        // Order buttons
        aButtons.buttons = h.orderObjectsBy(buttons, aButtons.order, 'meta.id');
        // Filter disabled buttons
        aButtons.buttons = aButtons.buttons.filter(button => {
          const metaId = h.get(button, 'meta.id');
          return !h.inArray(metaId, disabled);
        });
      }
    });

    // Overrides language set dir
    if (mergedConfig.dir) {
      this.dir = mergedConfig.dir;
    }

    this.config = mergedConfig;

    return this.config;
  }

  /**
   * Check whether toggleing row has recaptcha element in it
   * @param  {DOM}     element Row
   * @param  {Boolean} editing Editing on/off status
   */
  checkRecaptcha(element, editing) {
    if (!editing) {
      return;
    }
    if (element.querySelector('.field-type-recaptcha') === null) {
      element.classList.remove('has-recaptcha');
      return;
    }
    element.classList.add('has-recaptcha');
  }

  /**
   * Ensure elements have proper tagName
   * @param  {Object|String} elem
   * @return {Object} valid element object
   */
  processTagName(elem) {
    let tagName;
    if (typeof elem === 'string') {
      tagName = elem;
      elem = {tag: tagName};
    }
    if (elem.attrs) {
      const tag = elem.attrs.tag;
      if (tag) {
        const selectedTag = tag.filter(t => (t.selected === true));
        if (selectedTag.length) {
          tagName = selectedTag[0].value;
        }
      }
    }

    elem.tag = tagName || elem.tag;
    return elem;
  }

  /**
   * Search for fields to find matching field by name
   * @param  {String} name of field to search
   * @return {Boolean} true if field with name is present in fields else false
   */
  hasFieldWithName(name) {
    let found = false;
    formData.fields.forEach(function(field) {
      if (h.get(field, 'attrs.name') == name) {
        found = true;
      }
    });
    return found;
  }

  /**
   * Check for standard element
   * @param  {Object}  elem      element config object
   * @return {Boolean}            true | false
   */
  getWrapperClass(elem) {
    if (h.get(elem, 'className') == 'g-recaptcha') {
      return 'g-recaptcha-wrapper';
    }
    if (elem.tag == 'textarea' || elem.tag == 'select') {
      return elem.tag + '-wrapper';
    }
    if (elem.tag == 'input') {
      if (elem.attrs.type == 'radio' || elem.attrs.type == 'checkbox') {
        return '';
      }
      return 'input-' + elem.attrs.type + '-wrapper';
    }
    return '';
  }

  /**
   * Missing form-control class in input element
   * @param  {Object} elem element config object
   * @return {Object}
   */
  missingFormControlClass(elem) {
    const tag = elem.tag;
    let className = h.get(elem, 'attrs.className') || h.get(elem, 'className') || '';
    if (['input', 'textarea', 'select'].indexOf(tag) != -1 && className.indexOf('form-control') == -1) {
      if (Array.isArray(className)) {
        className.push('form-control');
      } else {
        className += ' form-control';
      }
      h.set(elem, 'attrs.className', className);
    }
    return elem;
  }

  /**
   * Process class attribute
   * @param  {Object} elem Element object
   * @return {Object}      Element object
   */
  processClass(elem) {
    if (typeof elem.attrs != 'undefined' && typeof elem.attrs.class != 'undefined') {
      if (typeof elem.attrs.className != 'undefined') {
        if (Array.isArray(elem.attrs.className)) {
          elem.attrs.className.push(clone(elem.attrs.class));
        } else {
          elem.attrs.className += ' ' + clone(elem.attrs.class);
        }
      } else {
        elem.attrs.className = clone(elem.attrs.class);
      }
      delete elem.attrs.class;
    }
    return elem;
  }

  /**
   * Creates DOM elements
   * @param  {Object}  elem      element config object
   * @param  {Boolean} isPreview generating element for preview or render?
   * @return {Object}            DOM Object
   */
  create(elem, isPreview = false) {
    const _this = this;
    elem = _this.processClass(elem);
    elem = _this.processTagName(elem);
    let contentType;
    const {tag} = elem;
    const processed = [];
    const labelAfter = _this.labelAfter(elem);
    let i;
    let displayLabel = '';
    const formSettings = this.getFormSettings();
    displayLabel = formSettings.form['display-label'].value;
    switch (displayLabel) {
      case 'top':
        displayLabel = '';
        break;
      case 'left':
        displayLabel = 'single-line';
        break;
    }
    const wrap = {
      tag: 'div',
      attrs: {},
      className: [h.get(elem, 'config.inputWrap') || 'f-field-group' + ' ' + displayLabel + ' ' + this.getWrapperClass(elem)],
      content: [],
      config: {}
    };

    if (h.get(elem, 'attrs.wrapClass') != undefined) {
      wrap.className += ' ' + h.get(elem, 'attrs.wrapClass');
      delete elem.attrs.wrapClass;
    }

    const requiredMark = {
      tag: 'span',
      className: 'text-error',
      content: '*'
    };
    let element = document.createElement(tag);
    const required = h.get(elem, 'attrs.required');

    /**
     * Object for mapping contentType to its function
     * @type {Object}
     */
    const appendContent = {
      string: content => {
        element.innerHTML += content;
      },
      object: content => {
        return element.appendChild(_this.create(content));
      },
      node: content => {
        return element.appendChild(content);
      },
      array: content => {
        for (let i = 0; i < content.length; i++) {
          contentType = _this.contentType(content[i]);
          appendContent[contentType](content[i]);
        }
      },
      function: content => {
        content = content();
        contentType = _this.contentType(content);
        appendContent[contentType](content);
      },
      undefined: () => null
    };

    processed.push('tag');

    // Check for root className property
    if (elem.className) {
      const {className} = elem;
      elem.attrs = Object.assign({}, elem.attrs, {className});
      delete elem.className;
    }

    // Append Element Content
    if (elem.options || h.get(elem, 'meta.id') == 'country') {
      let options = null;
      if (elem.options) {
        options = elem.options;
      } else {
        options = [];
        Object.keys(this.countries).forEach(code => {
          options.push({
            label: this.countries[code],
            value: code
          });
        });
      }
      if (elem.tag == 'input' && (elem.attrs.type == 'radio' || elem.attrs.type == 'checkbox')) {
        h.forEach(options, option => {
          option.name = elem.attrs.name;
        });
      }
      options = this.processOptions(options, elem, isPreview);
      if (this.holdsContent(element) && tag !== 'button') {
        // Mainly used for <select> tag
        appendContent.array.call(this, options);
        delete elem.content;
      } else {
        h.forEach(options, option => {
          wrap.content.push(_this.create(option, isPreview));
        });

        wrap.config = Object.assign({}, elem.config);

        if (elem.tag == 'input' && (elem.attrs.type == 'radio' || elem.attrs.type == 'checkbox')) {
          wrap.attrs.wrapClass = 'input-' + elem.attrs.type + '-wrapper';
        }

        if (required) {
          wrap.attrs.required = required;
        }
        return this.create(wrap, isPreview);
      }

      processed.push('options');
    }

    if (elem.config) {
      const editablePreview = (elem.config.editable && isPreview);
      if (h.get(elem, 'config.recaptcha') && isPreview) {
        elem.attrs.className += '-preview';
      }
      if (h.get(elem, 'config.recaptcha') && !isPreview) {
        let limit = 3;
        const renderCaptcha = function() {
          if (Object.prototype.hasOwnProperty.call(window, 'grecaptcha') &&
            Object.prototype.hasOwnProperty.call(window.grecaptcha, 'render')) {
            window.grecaptcha.render(element, {
              sitekey: _this.sitekey,
              callback: function(response) {
                if (response) {
                  const errorelem = document.querySelector('.g-recaptcha-error');
                  errorelem.classList.remove('show');
                }
              }
            });
          } else {
            if (--limit != 0) {
              setTimeout(renderCaptcha, 1000);
            } else {
              element.querySelector('.g-recaptcha').innerHTML = getString('efb-google-recaptcha-not-loaded');
            }
          }
        };
        renderCaptcha();
      }
      if (elem.config.label && tag !== 'button') {
        let label = null;

        if (isPreview) {
          label = _this.label(elem, 'config.label');
          if (!labelAfter && required) {
            label = [label, requiredMark];
          }
        } else {
          if (h.get(elem, 'attrs.placeholder') == 'undefined') {
            elem.attrs.placeholder = 'label' in elem.config ? elem.config.label : '';
          }
          label = _this.label(elem);
          if (!labelAfter && required) {
            label.append(this.create(requiredMark));
          }
        }
        if (!elem.config.hideLabel) {
          if (labelAfter) {
            // Add check for inline checkbox
            wrap.className = `f-${elem.attrs.type}`;
            label.insertBefore(element, label.firstChild);
            wrap.content.push(label);
          } else {
            wrap.content.push(label);
            wrap.content.push(element);
          }
        } else if (editablePreview) {
          element.contentEditable = true;
          if (labelAfter) {
            wrap.className = `f-${elem.attrs.type}`;
            label.insertBefore(element, label.firstChild);
            wrap.content.push(label);
          } else {
            wrap.content.push(label);
            wrap.content.push(element);
          }
        }
      } else if (editablePreview) {
        element.contentEditable = true;
      }

      processed.push('config');
    }

    elem = this.missingFormControlClass(elem);

    // Set element attributes
    if (elem.attrs) {
      _this.processAttrs(elem, element, isPreview);
      processed.push('attrs');
    }

    // Append Element Content
    if (elem.content) {
      contentType = _this.contentType(elem.content);
      appendContent[contentType].call(this, elem.content);
      processed.push('content');
    }

    // Set the new element's dataset
    if (elem.dataset) {
      for (const data in elem.dataset) {
        if (h.get(elem.dataset, data)) {
          element.dataset[data] = elem.dataset[data];
        }
      }
      processed.push('dataset');
    }

    // Add listeners for defined actions
    if (elem.action) {
      const actions = Object.keys(elem.action);
      const applyAction = function(action) {
        setTimeout(() => {
          action(element);
        }, 10);
      };
      for (i = actions.length - 1; i >= 0; i--) {
        const event = actions[i];
        let action = elem.action[event];
        if (typeof action === 'string') {
          /* eslint no-eval: 0 */
          action = eval(`(${elem.action[event]})`);
        }
        const useCaptureEvts = [
          'focus',
          'blur'
        ];

        // Dirty hack to handle onRender callback
        if (event === 'onRender') {
          applyAction(action);
        } else {
          const useCapture = h.inArray(event, useCaptureEvts);
          element.addEventListener(event, action, useCapture);
        }
      }
      processed.push('action');
    }

    const fieldDataBindings = [
      'stage',
      'row',
      'column',
      'field'
    ];

    if (h.inArray(elem.fType, fieldDataBindings)) {
      const dataType = elem.fType + 'Data';
      element[dataType] = elem;
      if (dataType === 'fieldData') {
        element.panelNav = elem.panelNav;
      }
      processed.push(dataType);
    }

    // Subtract processed and ignored and attach the rest
    const remaining = h.subtract(processed, Object.keys(elem));
    for (i = remaining.length - 1; i >= 0; i--) {
      element[remaining[i]] = elem[remaining[i]];
    }

    if (wrap.content.length) {
      element = this.create(wrap);
    }
    return element;
  }

  /**
   * Return div containing import action
   * @param  {function} importFunction
   * @return {DOM} element for importing form
   */
  getImportFormContainer(importFunction) {
    const importform = {
      tag: 'div',
      className: 'import-container',
      content: [{
        tag: 'input',
        attrs: {
          type: 'file',
          className: 'import-form-input',
          id: 'import-form-input',
          accept: '.json'
        },
      }, {
        tag: 'span',
        className: 'import-form-error'
      }]
    };
    return this.create({
      tag: 'div',
      className: 'formeo-import-form',
      content: [{
        tag: 'div',
        className: 'import-form-container',
        content: [{
          tag: 'button',
          attrs: {
            className: 'import-form-button btn btn-primary',
            type: 'button'
          },
          content: getString('import-form-button'),
          action: {
            click: evt => {
              if (dom.license != 'available') {
                dom.proWarning(getString('efb-form-import'));
                return;
              }
              this.multiActions(
                  'success',
                  getString('import-form-title'),
                  importform,
                  [{
                    title: getString('proceed'),
                    type: 'success',
                    action: importFunction
                  }]
              );
            }
          }
        }]
      }]
    });
  }

  /**
   * Create and SVG or font icon.
   * Simple string concatenation instead of DOM.create because:
   *  - we don't need the perks of having icons be DOM objects at this stage
   *  - it forces the icon to be appended using innerHTML which helps svg render
   * @param  {String} name - icon name
   * @return {String} icon markup
   */
  icon(name) {
    const iconLink = document.getElementById('icon-' + name);
    let icon;

    if (iconLink) {
      icon = `<svg class="svg-icon svg-icon-${name}"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon-${name}"></use></svg>`;
    } else {
      // eslint-disable-next-line
      icon = `<span class="fa fa-${name}" aria-hidden="true"></span>`;
    }
    return icon;
  }

  /**
   * JS Object to DOM attributes
   * @param  {Object} elem    element config object
   * @param  {Object} element DOM element we are building
   * @param  {Boolean} isPreview
   * @return {void}
   */
  processAttrs(elem, element, isPreview) {
    const {attrs = {}} = elem;
    if (!isPreview) {
      if (!attrs.name && this.isInput(elem.tag)) {
        element.setAttribute('name', uuid(elem));
      }
    }

    // Set element attributes
    Object.keys(attrs).forEach(attr => {
      const name = h.safeAttrName(attr);
      if (name == 'tag') {
        return;
      }
      let value = attrs[attr] || '';
      if (Array.isArray(value)) {
        if (typeof value[0] === 'object') {
          const selected = value.filter(t => (t.selected === true));
          value = selected.length ? selected[0].value : value[0].value;
        } else {
          value = value.join(' ');
        }
      }
      if (value) {
        element.attributes[name] = value;
        element.setAttribute(name, value);
      }
    });
  }

  /**
   * Generate a fancy checkbox or radio
   * @param  {Object}  elem
   * @param  {Boolean} isPreview
   * @return {Object} checkable
   */
  checkbox(elem, isPreview) {
    const label = h.get(elem, 'elem.config.label') || '';
    const checkable = {
      tag: 'span',
      className: 'checkable',
      content: label
    };
    const optionLabel = {
      tag: 'label',
      attrs: {},
      content: [elem, checkable]
    };

    // If (isPreview) {
    //   input.fMap = `options[${i}].selected`;
    //   optionLabel.attrs.contenteditable = true;
    //   optionLabel.fMap = `options[${i}].label`;
    //   checkable.content = undefined;
    //   let checkableLabel = {
    //     tag: 'label',
    //     content: [input, checkable]
    //   };
    //   inputWrap.content.unshift(checkableLabel);
    //   // inputWrap.content.unshift(input);
    // } else {
    //   input.attrs.name = elem.id;
    //   optionLabel.content = checkable;
    //   optionLabel = dom.create(optionLabel);
    //   input = dom.create(input);
    //   optionLabel.insertBefore(input, optionLabel.firstChild);
    //   inputWrap.content = optionLabel;
    // }

    return optionLabel;
  }

  /**
   * Extend Array of option config objects
   * @param  {Array} options
   * @param  {Object} elem element config object
   * @param  {Boolean} isPreview
   * @return {Array} option config objects
   */
  processOptions(options, elem, isPreview) {
    const {action, attrs} = elem;
    const fieldType = attrs.type || elem.tag;
    const id = attrs.id || elem.id;

    const optionMap = (option, i) => {
      const defaultInput = () => {
        let input = {
          tag: 'input',
          attrs: clone(attrs),
          action
        };
        delete input.attrs.className;
        input.attrs.id = id + '-' + i;
        input.attrs.type = fieldType;
        input.attrs.value = option.value || '';
        const checkable = [{
          tag: 'span',
          className: 'checkable',
        }, {
          tag: 'span',
          className: 'checkable-label',
          content: option.label
        }];
        let optionLabel = {
          tag: 'label',
          attrs: {},
          config: {
            inputWrap: 'form-check'
          },
          content: [option.label]
        };
        const inputWrap = {
          tag: 'div',
          content: [optionLabel],
          className: [`f-${fieldType}`]
        };
        if (elem.attrs.className) {
          elem.config.inputWrap = elem.attrs.className;
        } else {
          elem.config.inputWrap = `f-${fieldType}-group`;
        }

        if (elem.config.inline) {
          inputWrap.className.push('f-${fieldType}-inline');
        }

        if (option.selected) {
          input.attrs.checked = true;
        }
        if (elem.tag == 'input' && (elem.attrs.type == 'radio' || elem.attrs.type == 'checkbox')) {
          input.attrs.name = elem.attrs.name;
        }
        if (isPreview) {
          input.fMap = `options[${i}].selected`;
          optionLabel.attrs.contenteditable = true;
          optionLabel.fMap = `options[${i}].label`;
          checkable[1].content = undefined;
          const checkableLabel = {
            tag: 'label',
            content: [input, checkable]
          };
          inputWrap.content.unshift(checkableLabel);
        } else {
          optionLabel.content = checkable;
          optionLabel = dom.create(optionLabel);
          input = dom.create(input);
          optionLabel.insertBefore(input, optionLabel.firstChild);
          inputWrap.content = optionLabel;
        }
        return inputWrap;
      };

      const optionMarkup = {
        select: () => {
          return {
            tag: 'option',
            attrs: option,
            content: option.label
          };
        },
        button: option => {
          const {type, label, className, id} = option;
          return Object.assign({}, elem, {
            attrs: {
              type
            },
            className,
            id: id || uuid(),
            options: undefined,
            content: label,
            action: elem.action
          });
        },
        checkbox: defaultInput,
        radio: defaultInput,
        datalist: () => {
          return {
            tag: 'option',
            attrs: {value: option.value},
            content: option.value
          };
        },
      };
      return optionMarkup[fieldType](option);
    };

    const mappedOptions = options.map(optionMap);
    return mappedOptions;
  }

  /**
   * Checks if there is a closing tag, if so it can hold content
   * @param  {Object} element DOM element
   * @return {Boolean} holdsContent
   */
  holdsContent(element) {
    return (element.outerHTML.indexOf('/') !== -1);
  }

  /**
   * Is this a textarea, select or other block input
   * also isContentEditable
   * @param  {Object}  element
   * @return {Boolean}
   */
  isBlockInput(element) {
    return (!this.isInput(element) && this.holdsContent(element));
  }

  /**
   * Determine if an element is an input field
   * @param  {String|Object} tag tagName or DOM element
   * @return {Boolean} isInput
   */
  isInput(tag) {
    if (typeof tag !== 'string') {
      tag = tag.tagName;
    }
    return (['input', 'textarea', 'select'].indexOf(tag) !== -1);
  }

  /**
   * Converts escaped HTML into usable HTML
   * @param  {String} html escaped HTML
   * @return {String}      parsed HTML
   */
  parsedHtml(html) {
    const escapeElement = document.createElement('textarea');
    escapeElement.innerHTML = html;
    return escapeElement.textContent;
  }

  /**
   * Test if label should be display before or after an element
   * @param  {Object} elem config
   * @return {Boolean} labelAfter
   */
  labelAfter(elem) {
    const type = h.get(elem, 'attrs.type');
    const isCB = (type === 'checkbox' || type === 'radio');
    return isCB || h.get(elem, 'config.labelAfter');
  }

  /**
   * Generate a label
   * @param  {Object} elem config object
   * @param  {String} fMap map to label's value in formData
   * @return {Object}      config object
   */
  label(elem, fMap) {
    const fieldLabel = {
      tag: 'label',
      attrs: {},
      className: ['control-label'],
      content: h.isHtml(elem.config.label) ? h.stripHtml(elem.config.label) : elem.config.label,
      action: {}
    };

    if (this.labelAfter(elem)) {
      const checkable = {
        tag: 'span',
        className: 'checkable',
        content: elem.config.label
      };
      fieldLabel.content = checkable;
    }

    if (elem.id) {
      fieldLabel.attrs.for = elem.id;
    }

    if (fMap) {
      // For attribute will prevent label focus
      delete fieldLabel.attrs.for;
      fieldLabel.attrs.contenteditable = true;
      fieldLabel.fMap = fMap;
    }

    return dom.create(fieldLabel);
  }

  /**
   * Determine content type
   * @param  {Node | String | Array | Object} content
   * @param  {String} key
   * @return {String}
   */
  contentType(content, key = null) {
    let type = typeof content;
    const longTextProperty = ['style'];
    if (key !== null && longTextProperty.indexOf(key) != -1) {
      type = 'textarea';
    } else if (content instanceof Node || content instanceof HTMLElement) {
      type = 'node';
    } else if (Array.isArray(content)) {
      type = 'array';
    }

    return type;
  }

  /**
   * Get the computed style for DOM element
   * @param  {Object}  elem     dom element
   * @param  {Boolean} property style eg. width, height, opacity
   * @return {String}           computed style
   */
  getStyle(elem, property = false) {
    let style;
    if (window.getComputedStyle) {
      style = window.getComputedStyle(elem, null);
    } else if (elem.currentStyle) {
      style = elem.currentStyle;
    }

    return property ? style[property] : style;
  }

  /**
   * Return stage tabs
   * @param {Object} stage object having stage details
   * @return {Object} stage tab
   */
  addStageTabItem(stage) {
    return {
      tag: 'li',
      className: 'stage-tab wfb-tab',
      id: 'for-' + stage.id,
      content: [{
        tag: 'div',
        className: 'stage-tab-before'
      },
      stage.title,
      {
        tag: 'div',
        className: 'stage-tab-after'
      }],
      action: {
        click: event => {
          if (!event.target.classList.contains('active')) {
            const container = closest(event.target, 'stage-tabs-wrapper');
            const selectedStage = container.parentElement.querySelector('.stage-wrap[id="' + stage.id + '"]');
            const activeStage = container.parentElement.querySelector('.stage-wrap.active');
            activeStage.classList.remove('active');
            activeStage.classList.remove('show');
            selectedStage.classList.add('active');
            selectedStage.classList.add('show');
            const activeTab = event.target.parentElement.querySelector('.active');
            activeTab.classList.remove('active');
            event.target.classList.add('active');
            dom.activeStage = selectedStage.querySelector('.stage'); // Changing active stage to newly added stage
            dom.toggleFormDeleteAction();
          }
        }
      }
    };
  }

  /**
   * Return stage tabs
   * @return {Object} stage tabs
   */
  getStageTabs() {
    const _this = this;
    const tabs = [];
    formData.stages.forEach(function(stage) {
      tabs.push(_this.addStageTabItem(stage));
    });
    tabs[0].className += ' active';
    return tabs;
  }

  /**
   * Remove stage from tab list
   * @param {DOM} element
   * @param {string} stageID
   */
  removeStage(element, stageID) {
    const _this = this;
    const container = closest(element, 'stage-tabs-wrapper');
    let lastStage;
    const remove = (stage, button, listItem) => {
      const parent = stage.parentElement;
      switch (formData.stages.size) {
        case 1:
          return false;
        case 2:
          container.remove();
          showControl('layout-tab-control');
          stage.remove();
          parent.querySelector('.stage-wrap').classList.add('active');
          parent.querySelector('.stage-wrap').classList.add('show');
          dom.activeStage = parent.querySelector('.stage-wrap').querySelector('.stage');
          lastStage = formData.stages.get(dom.activeStage.id);
          lastStage.title = 'Stage 1';
          formData.stages.set(dom.activeStage.id, lastStage);
          return true;
        default:
          stageButton.remove();
          animate.slideUp(listItem, 666, function() {
            _this.resizeTabContainer(listItem);
            listItem.remove();
          });
          stage.remove();
          dom.activeStage = parent.querySelector('.stage-wrap.active').querySelector('.stage');
          return true;
      }
    };
    const listItem = closest(element, 'stage-tab-wrap');
    const stageButton = container.querySelector('[id="for-' + stageID + '"]');
    const stage = container.parentElement.querySelector('[id="' + stageID + '"]');
    const viewingStage = container.parentElement.querySelector('.stage-wrap.active');
    let status = false;
    if (stage.id == viewingStage.id) {
      let newStage;
      if (stage.nextSibling) {
        newStage = stage.nextSibling;
      } else {
        newStage = stage.previousSibling;
      }
      newStage.classList.add('active');
      newStage.classList.add('show');
      const newId = newStage.id;
      const newButton = container.querySelector('[id="for-' + newId + '"]');
      newButton.classList.add('active');
      status = remove(stage, stageButton, listItem);
    } else {
      status = remove(stage, stageButton, listItem);
    }
    if (status == true) {
      formData.stages.delete(stageID);
    }
    _this.toggleFormDeleteAction();
    data.save();
  }

  /**
   * Return stage tabs
   * @param {Object} stage details
   * @return {Object} stage tabs
   */
  addStageTabListItem(stage) {
    const _this = this;
    const input = {
      tag: 'input',
      attrs: {
        type: 'text',
        id: 'title-for-' + stage.id,
        value: stage.title,
        className: 'form-control'
      },
      action: {
        change: event => {
          const element = event.target;
          const newTitle = element.value;
          const parent = closest(element, 'stage-tabs-wrapper');
          const button = parent.querySelector('[id="for-' + stage.id + '"]');
          button.innerText = newTitle;
          data.updateStageTitle(stage.id, newTitle);
        }
      }
    };
    const controls = [{
      tag: 'button',
      attrs: {
        type: 'button',
        className: 'btn btn-primary prop-order prop-control',
      },
      content: _this.icon('move-vertical'),
    }, {
      tag: 'button',
      attrs: {
        type: 'button',
        id: 'remove-stage-' + stage.id,
        className: 'btn btn-danger prop-remove prop-control'
      },
      content: _this.icon('remove'),
      action: {
        click: event => {
          _this.removeStage(event.target, stage.id);
        }
      }
    }];
    const tab = {
      tag: 'li',
      className: 'stage-tab-wrap prop-wrap control-count-2',
      content: [{
        tag: 'div',
        className: 'prop-controls',
        content: controls
      }, {
        tag: 'div',
        className: 'prop-input',
        content: [input]
      }]
    };
    return tab;
  }

  /**
   * Repositioning tabbed panels when panel container's width change
   * @param {String} container class of form editor
   */
  repositionPanels(container) {
    const panels = container.querySelectorAll('.panel-labels .active-tab');
    let evt;
    panels.forEach(function(panel) {
      evt = new CustomEvent('click', {target: panel});
      setTimeout(function() {
        panel.dispatchEvent(evt);
      }, 500);
    });
  }

  /**
   * Resizing the tab container
   * @param {DOM} element of which container need to be resized
   * @param {String} type of panel need to be resized
   */
  resizeTabContainer(element, type = 'list') {
    const container = closest(element, 'panel-tab-' + type);
    if (container) {
      const parentStyles = container.parentElement.style;
      parentStyles.height = 'auto';
    }
  }

  /**
   * Return stage tabs controls
   * @return {Object} stage tabs
   */
  getStageTabList() {
    const _this = this;
    const tabs = {
      tag: 'ul',
      className: 'field-edit-group field-edit-tab',
      content: []
    };
    formData.stages.forEach(function(stage) {
      tabs.content.push(_this.addStageTabListItem(stage));
    });
    const tabActions = {
      tag: 'div',
      className: 'panel-action-buttons',
      content: [{
        tag: 'button',
        attrs: {
          type: 'button',
          className: 'add-tab btn btn-primary'
        },
        content: getString('panelEditButtons.tabs'),
        action: {
          click: event => {
            const stageID = data.addStage();
            const element = event.target;
            const tabListContainer = closest(element, 'panel-tab-list').querySelector('.field-edit-tab');
            const formContainer = closest(element, 'form-container');
            let stage = formContainer.querySelector('.stage-wrap.active').querySelector('.stage');
            dom.activeStage = stage;
            const tabsWrapper = closest(element, 'stage-tabs-wrapper');
            const tabContainer = tabsWrapper.querySelector('.stage-tabs-preview');
            stage = formData.stages.get(stageID);
            const tabListItem = _this.addStageTabListItem(stage);
            const tabItem = _this.addStageTabItem(stage);
            tabListContainer.appendChild(dom.create(tabListItem));
            tabContainer.appendChild(dom.create(tabItem));
            _this.resizeTabContainer(event.target);
            data.save();
            _this.toggleFormDeleteAction();
          }
        }
      }]
    };
    return [{
      tag: 'div',
      className: 'f-panel-wrap',
      content: tabs
    }, tabActions];
  }

  /**
   * Reorder tab buttons
   */
  reorderTabButtons() {
    const container = document.querySelector('.stage-tabs-wrapper');
    const list = container.querySelector('.field-edit-group');
    const buttons = container.querySelector('.stage-tabs-preview');
    let button;
    const sorted = [];
    let stageid;
    list.childNodes.forEach(stage => {
      stage = stage.querySelector('input');
      stageid = stage.id;
      stageid = stageid.replace('title-', '');
      button = buttons.querySelector('[id="' + stageid + '"]');
      sorted.push(button);
    });
    sorted.forEach(button => {
      buttons.appendChild(button);
    });
  }

  /**
   * Sorting of tabs
   */
  tabSorting() {
    const container = document.querySelector('.stage-tabs-wrapper');
    const list = container.querySelector('.field-edit-group');
    Sortable.create(list, {
      animation: 150,
      group: {
        name: 'stage-sort',
        pull: true, put: true
      },
      sort: true,
      forceFallback: h.isFireFoxEdge(),
      draggable: '.stage-tab-wrap',
      handle: '.prop-order',
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag',
      onSort: evt => {
        data.saveStageOrder(list.childNodes);
      }
    });
  }

  /**
   * Return single stage tab configuration
   * @param {String} id of config property
   * @param {Object} config object including title, configid, type
   * @param {Object} defaultConf object including title, configid, type
   * @param {String} settingName unique name of setting we need to change
   * @param {String} classPrefix for setting elemets
   * @param {String} category of configuration if any nesting
   * @return {Object} stage tabs configuration
   */
  getSettingItem(id, config, defaultConf, settingName, classPrefix = null, category = null) {
    if (classPrefix === null) {
      classPrefix = settingName;
    }
    const element = {
      tag: 'li',
      className: classPrefix + '-wrap setting-wrap',
      content: [{
        tag: 'h5',
        className: classPrefix + '-label setting-label',
        content: config.title
      }]
    };
    const input = {
      tag: 'div',
      attrs: {
        className: classPrefix + '-inputs setting-inputs'
      },
      content: []
    };
    const uid = uuid();
    const inputControl = {
      tag: 'input',
      attrs: {
        id: uid,
        className: classPrefix + '-input setting-input',
        value: config.value
      },
      action: {
        change: event => {
          const setting = formData.settings.get(settingName);
          const inputTagType = elementTagType(event.target);
          const target = event.target;
          const value = target.value;
          switch (inputTagType.tag) {
            case 'INPUT':
              switch (inputTagType.type) {
                case 'checkbox':
                  setting[category][id].value = target.checked;
                  break;
                default:
                  if (category !== null) {
                    setting[category][id].value = value;
                  } else {
                    setting[id].value = value;
                  }
                  break;
              }
              break;
            default:
              if (category !== null) {
                setting[category][id].value = value;
              } else {
                setting[id].value = value;
              }
              break;
          }
          if (target.previousSibling) {
            target.previousSibling.value = value;
          } else if (target.nextSibling) {
            target.nextSibling.value = value;
          }
          formData.settings.set(settingName, setting);
          data.save();
        }
      }
    };
    switch (config.type) {
      case 'color':
        input.content.push({
          tag: 'input',
          attrs: {
            className: classPrefix + '-input setting-input color-group',
            type: 'text',
            value: config.value
          },
          action: {
            change: event => {
              const target = event.target;
              target.nextSibling.value = target.value;
              const evt = new CustomEvent('change', {target: target.nextSibling});
              target.nextSibling.dispatchEvent(evt);
            }
          }
        });
        inputControl.attrs.className += ' color-group';
        inputControl.attrs.type = config.type;
        break;
      case 'text':
        inputControl.attrs.type = config.type;
        break;
      case 'number':
        inputControl.attrs.type = config.type;
        break;
      case 'range':
        inputControl.action.mousemove = inputControl.action.change;
        inputControl.action.touchmove = inputControl.action.change;
        inputControl.action.pointermove = inputControl.action.change;
        inputControl.attrs.className += ' range-group';
        inputControl.attrs.type = config.type;
        inputControl.attrs = h.merge(inputControl.attrs, config.attrs);
        break;
      case 'select':
        inputControl.tag = 'select';
        inputControl.options = [];
        Object.values(config.options).forEach(function(option) {
          option.selected = option.value == config.value;
          inputControl.options.push(option);
        });
        break;
      case 'textarea':
        inputControl.tag = 'textarea';
        inputControl.cols = 5;
        inputControl.content = config.value;
        if (config.id == 'style') {
          inputControl.placeholder = getString('placeholder.style');
        }
        break;
      case 'toggle':
        inputControl.attrs.className += ' toggle-group';
        inputControl.tag = 'input';
        inputControl.type = 'checkbox';
        inputControl.checked = config.value;
        break;
      default:
        inputControl.attrs.type = 'text';
        break;
    }
    input.content.push(inputControl);
    switch (config.type) {
      case 'range':
        input.content.push({
          tag: 'input',
          attrs: {
            className: classPrefix + '-input setting-input range-group',
            type: 'text',
            value: config.value
          },
          action: {
            change: event => {
              const target = event.target;
              target.previousSibling.value = target.value;
              const evt = new CustomEvent('change', {target: target.previousSibling});
              target.previousSibling.dispatchEvent(evt);
            }
          }
        });
        break;
      case 'toggle':
        input.content.push({
          tag: 'label',
          attrs: {
            className: 'bg-primary',
            for: uid
          }
        });
        break;
    }
    const defaultConfig = {
      tag: 'div',
      attrs: {
        className: classPrefix + '-control setting-control'
      },
      content: [{
        tag: 'button',
        attrs: {
          class: classPrefix + '-default setting-default btn',
          type: 'button',
          'data-key': config.value,
          title: 'Restore default',
        },
        content: {
          tag: 'i',
          className: 'fa fa-repeat'
        },
        action: {
          click: event => {
            let target = event.target;
            if (target.tagName == 'I') {
              target = target.parentElement;
            }
            const setting = formData.settings.get(settingName);
            const input = target.parentElement.previousSibling.childNodes[0];
            const inputTagType = elementTagType(input);
            const value = target.getAttribute('data-key');
            let opts;
            let opt;
            let i;
            switch (inputTagType.tag) {
              case 'SELECT':
                opts = input.childNodes;
                for (i = 0; opt = opts[i]; i++) {
                  if (opt.value == value) {
                    input.selectedIndex = i;
                    break;
                  }
                }
                if (category !== null) {
                  setting[category][id].value = value;
                } else {
                  setting[id].value = value;
                }
                break;
              case 'INPUT':
                switch (inputTagType.type) {
                  case 'checkbox':
                    input.checked = value == 'on';
                    break;
                  default:
                    input.value = value;
                    if (category !== null) {
                      setting[category][id].value = value;
                    } else {
                      setting[id].value = value;
                    }
                    break;
                }
                break;
              default:
                input.value = value;
                if (category !== null) {
                  setting[category][id].value = value;
                } else {
                  setting[id].value = value;
                }
                break;
            }
            const evt = new CustomEvent('change', {target: input});
            input.dispatchEvent(evt);
            formData.settings.set(settingName, setting);
            data.save();
          }
        }
      }]
    };
    const setting = {
      tag: 'div',
      className: classPrefix + '-inputs-wrap setting-inputs-wrap',
      content: [input, defaultConfig]
    };
    element.content.push(setting);
    return element;
  }

  /**
   * Returning the default configuration for steps
   * @return {Object} stepConfiguration
   */
  getTabDefaultConfigs() {
    const generalStepConfig = {
      'class': {
        title: getString('class'),
        id: 'class',
        type: 'text',
        value: 'wfb-step'
      },
      'background-color': {
        title: getString('backgroundcolor'),
        id: 'background-color',
        type: 'color',
        value: '#838b8e'
      },
      // Removed extra settings
      'color': {
        title: getString('textcolor'),
        id: 'color',
        type: 'color',
        value: '#ffffff' // Removed extra settings
      }
    };
    const completeStepConfig = {
      'class': {
        title: getString('class'),
        id: 'class',
        type: 'text',
        value: 'wfb-step completed'
      },
      'background-color': {
        title: getString('backgroundcolor'),
        id: 'background-color',
        type: 'color',
        value: '#46be8a'
      },
      'color': {
        title: getString('textcolor'),
        id: 'color',
        type: 'color',
        value: '#ffffff'
      }
    };
    const activeStepConfig = {
      'class': {
        title: getString('class'),
        id: 'class',
        type: 'text',
        value: 'wfb-step active'
      },
      'background-color': {
        title: getString('backgroundcolor'),
        id: 'background-color',
        type: 'color',
        value: '#62a8ea'
      },
      'color': {
        title: getString('textcolor'),
        id: 'color',
        type: 'color',
        value: '#ffffff'
      }
    };
    const dangerStepConfig = {
      'class': {
        title: getString('class'),
        id: 'class',
        type: 'text',
        value: 'wfb-step danger'
      },
      'background-color': {
        title: getString('backgroundcolor'),
        id: 'background-color',
        type: 'color',
        value: '#d9534f'
      },
      'color': {
        title: getString('textcolor'),
        id: 'color',
        type: 'color',
        value: '#ffffff'
      }
    };
    return {
      default: generalStepConfig,
      complete: completeStepConfig,
      active: activeStepConfig,
      danger: dangerStepConfig
    };
  }

  /**
    * Create stage tab configuration category container
    * @param {String} category type stage tabs configuration
    * @param {Boolean} resize does container need to resized after toggling settings
    * @return {Object} stage tabs configuration container
    */
  getConfigContainer(category, resize = true) {
    const _this = this;
    const label = {
      tag: 'h4',
      className: 'category-label btn-primary p-10 m-0 collapsed',
      content: [getString('category-container-' + category), {
        tag: 'i',
        className: 'fa fa-plus'
      }, {
        tag: 'i',
        className: 'fa fa-minus'
      }],
      action: {
        click: event => {
          let target = event.target;
          if (target.tagName == 'I') {
            target = target.parentElement;
          }
          const list = target.nextSibling;
          if (target.classList.contains('collapsed')) {
            list.style.height = 'auto';
            const height = list.clientHeight + 'px';
            list.style.height = '0px';
            target.classList.remove('collapsed');
            target.classList.add('collapsible');
            if (resize) {
              _this.resizeTabContainer(target, 'config');
            }
            setTimeout(() => {
              list.style.height = height;
            }, 0);
          } else {
            list.style.height = '0px';
            target.classList.remove('collapsible');
            target.classList.add('collapsed');
            if (resize) {
              _this.resizeTabContainer(target, 'config');
            }
          }
        },
      }
    };
    const settings = {
      tag: 'ul',
      className: 'category-settings',
      content: []
    };
    return {
      tag: 'div',
      className: 'category-container-' + category,
      content: [label, settings]
    };
  }

  /**
    * Return stage tab configuration
    * @return {Object} stage tabs configuration
    */
  getTabConfigs() {
    const _this = this;
    const tabConfig = _this.getTabDefaultConfigs();
    const tabSettings = formData.settings.get('tabSettings');
    const tabConfigElement = [];
    Object.keys(tabSettings).forEach(category => {
      const container = _this.getConfigContainer(category);
      Object.keys(tabSettings[category]).forEach(id => {
        if (id == 'border-color') {
          return;
        }
        container.content[1].content.push(_this.getSettingItem(id, tabSettings[category][id], tabConfig[category][id], 'tabSettings', 'stage-config', category));
      });
      tabConfigElement.push(container);
    });
    return {
      tag: 'div',
      attrs: {
        className: 'f-panel-wrap'
      },
      content: [{
        tag: 'ul',
        className: 'field-edit-group',
        content: tabConfigElement
      }]
    };
  }
  /**
    * Return stage tab container
    * @return {Object} stage tabs wrapper
    */
  getStageControl() {
    const _this = this;
    const edit = {
      tag: 'button',
      content: _this.icon('edit'),
      attrs: {
        className: ['btn btn-primary item-edit-toggle'],
        type: 'button'
      },
      meta: {
        id: 'edit'
      },
      action: {
        click: evt => {
          const element = closest(evt.target, 'stage-tabs-wrapper');
          const fType = 'stage-tabs';
          const editClass = 'editing-' + fType;
          const editWindow = element.querySelector(`.${fType}-edit`);
          if (element.classList.contains(editClass)) {
            animate.slideUp(editWindow, 666, function() {
              animate.slideDown(editWindow.nextSibling, 333, function() {
                element.classList.remove(editClass);
              });
            });
          } else {
            animate.slideUp(editWindow.nextSibling, 333, function() {
              animate.slideDown(editWindow, 666, function() {
                element.classList.add(editClass);
              });
            });
          }
        }
      }
    };

    const remove = {
      tag: 'button',
      content: [_this.icon('handle'), _this.icon('remove')],
      attrs: {
        className: ['btn btn-danger item-remove'],
        type: 'button'
      },
      meta: {
        id: 'remove'
      },
      action: {
        click: (evt) => {
          const editor = document.getElementById('efb-cont-form-builder');
          const stages = editor.querySelectorAll('.stage-wrap:not(.active)');
          stages.forEach(stage => {
            const stageRemoveButton = editor.querySelector('.prop-remove[id="remove-stage-' + stage.id + '"]');
            dom.removeStage(stageRemoveButton, stage.id);
          });
        }
      }
    };
    const tabListPanel = {
      tag: 'div',
      config: {
        label: 'Tab list'
      },
      attrs: {
        className: 'f-panel panel-tab-list'
      },
      content: [_this.getStageTabList()]
    };
    const tabConfigPanel = {
      tag: 'div',
      config: {
        label: 'Tab config'
      },
      attrs: {
        className: 'f-panel panel-tab-config'
      },
      content: [_this.getTabConfigs()]
    };
    const tabPanels = new panels({
      id: 'stage-tab-panel',
      panels: [tabListPanel, tabConfigPanel]
    });
    const stageControlWrap = {
      tag: 'div',
      className: 'stage-tabs-wrapper wfb-tabs-view',
      id: 'stage-tab-panel',
      content: [{
        tag: 'div',
        className: 'stage-tabs-actions group-actions',
        content: [{
          tag: 'div',
          className: 'action-btn-wrap',
          content: [remove, edit]
        }]
      }, {
        tag: 'div',
        className: 'stage-tabs-edit field-edit',
        content: [{
          tag: 'div',
          attrs: {
            className: 'tab-edit panels-wrap tabbed-panels',
          },
          content: tabPanels.content,
          nav: tabPanels.nav,
          action: tabPanels.action
        }]
      }, {
        tag: 'ul',
        className: 'stage-tabs-preview wfb-tabs',
        content: [_this.getStageTabs()]
      }],
      action: {
        mouseenter: event => {
          event.target.classList.add('hovering-stage-tabs');
        },
        mouseleave: evnt => {
          event.target.classList.remove('hovering-stage-tabs');
        }
      }
    };
    setTimeout(function() { // Activate first tab as active step when multiple steps presents
      dom.activeStage = document.querySelector('.stage-wrap.active .stage');
    }, 666);
    return stageControlWrap;
  }

  /**
   * Retrieves an element by config object, string id,
   * or existing reference
   * @param  {Object|String|Node} elem
   * @return {Object}             DOM element
   */
  getElement(elem) {
    const getElement = {
      node: () => elem,
      object: () => document.getElementById(elem.id),
      string: () => document.getElementById(elem)
    };
    const type = this.contentType(elem);
    const element = getElement[type]();

    return element;
  }

  /**
   * Util to remove contents of DOM Object
   * @param  {Object} elem
   * @return {Object} element with its children removed
   */
  empty(elem) {
    // Passing stage directly so no need to check stage or not
    while (elem.firstChild) {
      this.remove(elem.firstChild);
    }
    return elem;
  }

  /**
   * Move, close, and edit buttons for row, column and field
   * @param  {String} id   element id
   * @param  {String} item type of element eg. row, column, field
   * @return {Object}      element config object
   */
  actionButtons(id, item = 'column') {
    const _this = this;
    const tag = (item === 'column' ? 'li' : 'div');
    const btnWrap = {
      tag: 'div',
      className: 'action-btn-wrap'
    };
    const actions = {
      tag,
      className: item + '-actions group-actions',
      action: {
        mouseenter: evt => {
          const element = document.getElementById(id);
          element.classList.add('hovering-' + item);
          evt.target.parentReference = element;
        },
        mouseleave: evt => {
          evt.target.parentReference.classList.remove('hovering-' + item);
        },
        onRender: elem => {
          const buttons = elem.getElementsByTagName('button');
          const btnWidth = parseInt(_this.getStyle(buttons[0], 'width')) + 1;
          const expandedWidth = (buttons.length * btnWidth) + 'px';
          const woh = item === 'row' ? 'height' : 'width';
          const rules = [
            [
              `.hovering-${item} .${item}-actions`,
              [woh, expandedWidth, true]
            ]
          ];

          _this.insertRule(rules);
        }
      }
    };
    btnWrap.content = this.config[`${item}s`].actionButtons.buttons;
    actions.content = btnWrap;

    return actions;
  }

  /**
   * Clones an element, it's data and
   * it's nested elements and data
   * @param {Object} elem element we are cloning
   * @param {Object} parent
   * @return {Object} cloned element
   */
  clone(elem, parent) {
    const _this = this;
    const {id, fType} = elem;
    const dataClone = clone(formData[fType].get(id));
    const newIndex = h.indexOfNode(elem) + 1;
    let noParent = false;
    dataClone.id = uuid();
    formData[fType].set(dataClone.id, dataClone);
    if (!parent) {
      parent = elem.parentElement;
      noParent = true;
    }
    const cloneType = {
      rows: () => {
        dataClone.columns = [];
        const stage = _this.activeStage;
        const newRow = _this.addRow(null, dataClone.id);
        const columns = elem.getElementsByClassName('stage-columns');

        stage.insertBefore(newRow, stage.childNodes[newIndex]);
        h.forEach(columns, column => _this.clone(column, newRow));
        data.saveRowOrder();
        return newRow;
      },
      columns: () => {
        dataClone.fields = [];
        const newColumn = _this.addColumn(parent.id, dataClone.id);
        parent.insertBefore(newColumn, parent.childNodes[newIndex]);
        data.saveColumnOrder(parent);
        const fields = elem.getElementsByClassName('stage-fields');

        if (noParent) {
          dom.columnWidths(parent);
        }
        h.forEach(fields, field => _this.clone(field, newColumn));
        return newColumn;
      },
      fields: () => {
        const newField = _this.addField(parent.id, dataClone.id, 'clone');
        parent.insertBefore(newField, parent.childNodes[newIndex]);
        data.saveFieldOrder(parent);
        return newField;
      }
    };

    return cloneType[fType]();
  }

  /**
   * Check that element can be removed using remove but or not
   * @param {DOM} container element need to be removed
   * @return {Boolean} canRemoveElement true if element can be removed| false if element cannot be removed
   */
  canRemoveElement(container) {
    let elementType = container.fType;
    let message = '';
    const check = {
      fields: (field) => {
        field = formData.fields.get(field);
        if (h.get(field, 'attrs.template')) {
          if (field.attrs.template) {
            message += field.config.label + '<br>';
          }
        }
      },
      columns: (column) => {
        column = formData.columns.get(column);
        column.fields.forEach(function(field) {
          check.fields(field);
        });
      },
      rows: (row) => {
        row = formData.rows.get(row);
        row.columns.forEach(function(column) {
          check.columns(column);
        });
      }
    };
    check[elementType](container.id);
    if (message != '') {
      elementType = elementType.slice(0, elementType.length - 1);
      message = getString('cannotremove', elementType) + message;
      this.alert('danger', message);
      return false;
    }
    return true;
  }

  /**
   * Remove elements without f children
   * @param  {Object} element DOM element
   * @return {Object} formData
   */
  removeEmpty(element) {
    const _this = this;
    const parent = element.parentElement;
    const type = element.fType;
    const children = parent.getElementsByClassName('stage-' + type);
    _this.remove(element);
    if (!children.length) {
      if (parent.fType !== 'stages') {
        return _this.removeEmpty(parent);
      } else {
        this.emptyClass(parent);
      }
    }
    if (type === 'columns') {
      _this.columnWidths(parent);
    }
    return data.save();
  }

  /**
   * Removes element from DOM and data
   * @param  {Object} elem
   * @return  {Object} parent element
   */
  remove(elem) {
    const {fType, id} = elem;
    if (fType) {
      const parent = elem.parentElement;
      const pData = formData[parent.fType].get(parent.id);
      data.empty(fType, id);
      this[fType].delete(id);
      formData[fType].delete(id);
      remove(pData[fType], id);
    }
    return elem.parentElement.removeChild(elem);
  }

  /**
   * Removes a class or classes from nodeList
   *
   * @param  {NodeList} nodeList
   * @param  {String | Array} className
   */
  removeClasses(nodeList, className) {
    const _this = this;
    const removeClass = {
      string: elem => {
        elem.className = elem.className.replace(className, '');
      },
      array: elem => {
        for (let i = className.length - 1; i >= 0; i--) {
          elem.classList.remove(className[i]);
        }
      }
    };
    removeClass.object = removeClass.string; // Handles regex map
    h.forEach(nodeList, removeClass[_this.contentType(className)]);
  }

  /**
   * Adds a class or classes from nodeList
   *
   * @param  {NodeList} nodeList
   * @param  {String | Array} className
   */
  addClasses(nodeList, className) {
    const _this = this;
    const addClass = {
      string: elem => {
        elem.classList.add(className);
      },
      array: elem => {
        for (let i = className.length - 1; i >= 0; i--) {
          elem.classList.add(className[i]);
        }
      }
    };
    h.forEach(nodeList, addClass[_this.contentType(className)]);
  }

  /**
   * [fieldOrderClass description]
   * @param  {[type]} column [description]
   */
  fieldOrderClass(column) {
    const fields = column.querySelectorAll('.stage-fields');

    if (fields.length) {
      this.removeClasses(fields, ['first-field', 'last-field']);
      fields[0].classList.add('first-field');
      fields[fields.length - 1].classList.add('last-field');
    }
  }

  /**
   * Read columns and generate bootstrap cols
   * @param  {Object}  row    DOM element
   */
  columnWidths(row) {
    const _this = this;
    const fields = [];
    const columns = row.getElementsByClassName('stage-columns');
    if (!columns.length) {
      return;
    }
    const width = parseFloat((100 / columns.length).toFixed(1)) / 1;
    const bsGridRegEx = /\bcol-\w+-\d+/g;

    _this.removeClasses(columns, bsGridRegEx);

    h.forEach(columns, column => {
      const columnData = formData.columns.get(column.id);
      fields.push(...columnData.fields);

      const colWidth = numToPercent(width);

      column.style.width = colWidth;
      column.style.float = 'left';
      columnData.config.width = colWidth;
      column.dataset.colWidth = colWidth;
      document.dispatchEvent(events.columnResized);
    });

    setTimeout(() => {
      fields.forEach(fieldID => {
        const field = dom.fields.get(fieldID);
        if (field.instance.panels) {
          field.instance.panels.nav.refresh();
        }
      });
    }, 250);

    dom.updateColumnPreset(row);
  }

  /**
   * Wrap content in a formGroup
   * @param  {Object|Array|String} content
   * @param  {String} className
   * @return {Object} formGroup config
   */
  formGroup(content, className = '') {
    return {
      tag: 'div',
      className: ['f-field-group', className],
      content: content
    };
  }

  /**
   * Generates the element config for column layout in row
   * @param  {String} rowID [description]
   * @return {Object}       [description]
   */
  columnPresetControl(rowID) {
    const _this = this;
    const rowData = formData.rows.get(rowID);
    const layoutPreset = {
      tag: 'select',
      attrs: {
        ariaLabel: getString('columnlayout'),
        className: 'column-preset'
      },
      action: {
        change: e => {
          const dRow = this.rows.get(rowID);
          _this.setColumnWidths(dRow.row, e.target.value);
          data.save();
        }
      }
    };
    const pMap = new Map();
    const custom = {value: 'custom', label: getString('custom')};

    pMap.set(1, [{value: '100.0', label: '100%'}]);
    pMap.set(2, [
      {value: '50.0,50.0', label: '50 | 50'},
      {value: '33.3,66.6', label: '33 | 66'},
      {value: '66.6,33.3', label: '66 | 33'},
      {value: '25,75', label: '25 | 75'},
      {value: '75,25', label: '75 | 25'},
      custom
    ]);
    pMap.set(3, [
      {value: '33.3,33.3,33.3', label: '33 | 33 | 33'},
      {value: '50.0,25.0,25.0', label: '50 | 25 | 25'},
      {value: '25.0,50.0,25.0', label: '25 | 50 | 25'},
      {value: '25.0,25.0,50.0', label: '25 | 25 | 50'},
      {value: '60.0,20.0,20.0', label: '60 | 20 | 20'},
      {value: '20.0,60.0,20.0', label: '20 | 60 | 20'},
      {value: '20.0,20.0,60.0', label: '20 | 20 | 60'},
      custom
    ]);
    pMap.set(4, [
      {value: '25.0,25.0,25.0,25.0', label: '25 | 25 | 25 | 25'},
      {value: '30.0,30.0,20.0,20.0', label: '30 | 30 | 20 | 20'},
      {value: '20.0,30.0,30.0,20.0', label: '20 | 30 | 30 | 20'},
      {value: '20.0,20.0,30.0,30.0', label: '20 | 20 | 30 | 30'},
      {value: '30.0,20.0,20.0,30.0', label: '30 | 20 | 20 | 30'},
      custom
    ]);
    pMap.set('custom', [custom]);

    if (rowData && rowData.columns.length) {
      const columns = rowData.columns;
      const pMapVal = pMap.get(columns.length);
      layoutPreset.options = pMapVal || pMap.get('custom');
      const curVal = columns.map((columnID, i) => {
        const colData = formData.columns.get(columnID);
        return colData.config.width.replace('%', '');
      }).join(',');
      if (pMapVal) {
        pMapVal.forEach((val, i) => {
          const options = layoutPreset.options;
          if (val.value === curVal) {
            options[i].selected = true;
          } else {
            delete options[i].selected;
            options[options.length - 1].selected = true;
          }
        });
      }
    } else {
      layoutPreset.options = pMap.get(1);
    }

    return layoutPreset;
  }

  /**
   * Set the widths of columns in a row
   * @param {Object} row DOM element
   * @param {String} widths
   */
  setColumnWidths(row, widths) {
    if (widths === 'custom') {
      return;
    }
    widths = widths.split(',');
    const columns = row.getElementsByClassName('stage-columns');
    h.forEach(columns, (column, i) => {
      const percentWidth = widths[i] + '%';
      column.dataset.colWidth = percentWidth;
      column.style.width = percentWidth;
      formData.columns.get(column.id).config.width = percentWidth;
    });
  }

  /**
   * Updates the column preset <select>
   * @param  {String} row
   * @return {Object} columnPresetConfig
   */
  updateColumnPreset(row) {
    const _this = this;
    const oldColumnPreset = row.querySelector('.column-preset');
    const rowEdit = oldColumnPreset.parentElement;
    const columnPresetConfig = _this.columnPresetControl(row.id);
    const newColumnPreset = _this.create(columnPresetConfig);

    rowEdit.replaceChild(newColumnPreset, oldColumnPreset);
    return columnPresetConfig;
  }

  /**
   * Returns the {x, y} coordinates for the
   * center of a given element
   * @param  {DOM} element
   * @return {Object}      {x,y} coordinates
   */
  coords(element) {
    const elemPosition = element.getBoundingClientRect();
    const bodyRect = document.body.getBoundingClientRect();

    return {
      pageX: elemPosition.left + (elemPosition.width / 2),
      pageY: (elemPosition.top - bodyRect.top) - (elemPosition.height / 2)
    };
  }

  /**
   * Loop through the formData and append it to the stage
   * @param  {Object} stage DOM element
   * @return {Array}  loaded rows
   */
  loadRows(stage) {
    if (!stage) {
      stage = this.activeStage;
    }

    const rows = formData.stages.get(stage.id).rows;
    return rows.forEach(rowID => {
      const row = this.addRow(stage.id, rowID);
      this.loadColumns(row);
      dom.updateColumnPreset(row);
      stage.appendChild(row);
    });
  }

  /**
   * Load columns to row
   * @param  {Object} row
   */
  loadColumns(row) {
    const columns = formData.rows.get(row.id).columns;
    columns.forEach(columnID => {
      const column = this.addColumn(row.id, columnID);
      this.loadFields(column);
    });
  }

  /**
   * Load a columns fields
   * @param  {Object} column column config object
   */
  loadFields(column) {
    const fields = formData.columns.get(column.id).fields;
    fields.forEach(fieldID => this.addField(column.id, fieldID));
    this.fieldOrderClass(column);
  }

  /**
   * Create or add a field and column then return it.
   * @param  {Object} evt Drag event data
   * @return {Object}     column
   */
  createColumn(evt) {
    const fType = evt.from.fType;
    const field = fType === 'columns' ? evt.item : new Field(evt.item.id);
    const column = new Column();

    field.classList.add('first-field');
    column.appendChild(field);
    formData.columns.get(column.id).fields.push(field.id);
    return column;
  }

  /**
   * [processColumnConfig description]
   * @param  {[type]} columnData [description]
   * @return {[type]}         [description]
   */
  processColumnConfig(columnData) {
    if (columnData.className) {
      columnData.className.push('f-render-column');
    }
    const colWidth = columnData.config.width || '100%';
    columnData.style = `width: ${colWidth}`;
    return columnData;
  }

  /**
   * @param {Dom} element to verify element data is valid or not
   * @return {Boolean} is valid or not
   */
  checkValidity(element) {
    if (element.classList.contains('input-checkbox-wrapper')) {
      if (element.querySelectorAll('input[type="checkbox"][required]').length == 0) {
        return true;
      }
      if (element.querySelectorAll('input[type="checkbox"][required]:checked').length != 0) {
        return true;
      }
      return false;
    }
    if (element.hasChildNodes()) {
      for (let i = 0; i < element.children.length; i++) {
        if (!this.checkValidity(element.children[i])) {
          return false;
        }
      }
    } else if (typeof element.checkValidity == 'function') {
      element.setCustomValidity('');
      if (element.checkValidity()) {
        return true;
      }
      if (element.hasAttribute('validation')) {
        element.setCustomValidity(element.getAttribute('validation'));
      }
      return element.reportValidity(); // Reporting error if input is not valid
    }
    return true;
  }

  /**
   * @param {Integer} next step
   * @param {Integer} count Maximum number of steps
   * @param {Object} classes
   * @param {Boolean} valid is current step elements are valid or not
   */
  updateStageSteps(next, count, classes, valid = true) {
    const steps = document.querySelectorAll('.efb-steps .' + classes.step);
    for (let i = 0; i < next; i++) {
      steps[i].className = classes.complete;
    }
    if (next < count) {
      steps[next].className = valid ? classes.active : classes.danger;
      for (let i = next + 1; i < count; i++) {
        steps[i].className = classes.step;
      }
    }
  }

  /**
   * @param {Event} event Cliking event of next and previous button
   * @param {Dom} renderTarget rendering target dom element
   * @param {Object} classes
   * @param {Integer} action Eigther change to next stage or previous
   */
  changeStep(event, renderTarget, classes, action) {
    const activeStage = document.getElementsByClassName('f-stage active')[0];
    const stageDiv = renderTarget.getElementsByClassName('formeo-render')[0];
    const stages = Array.prototype.slice.call(stageDiv.children);
    const current = stages.indexOf(activeStage);
    const next = current + action;
    const count = stages.length;
    let valid = false;
    if (next >= 0 && next < count) {
      valid = this.checkValidity(stages[current]);
      if (next < current || valid) {
        this.updateStageSteps(next, count, classes);
        stages[next].classList.add('active');
        stages[next].classList.remove('d-none');
        stages[current].classList.remove('active');
        stages[current].classList.add('d-none');
        if (next > 0) {
          document.getElementById('previous-step').classList.remove('d-none');
        } else {
          document.getElementById('previous-step').classList.add('d-none');
        }
        if (next < count - 1) {
          document.getElementById('next-step').classList.remove('d-none');
          document.getElementById('submit-form').classList.add('d-none');
        } else {
          document.getElementById('next-step').classList.add('d-none');
          document.getElementById('submit-form').classList.remove('d-none');
        }
      } else if (!valid) {
        this.updateStageSteps(current, count, classes, false);
      }
      event.preventDefault();
    }
    if (next == count) {
      this.updateStageSteps(next, count, classes);
    }
  }

  /**
   * Return stage tabs
   * @param {Object} stage object having stage details
   * @param {string} stepClass
   * @return {Object} stage tab
   */
  addStepItem(stage, stepClass) {
    return {
      tag: 'li',
      attrs: {
        id: 'for-' + stage.id,
        className: stepClass
      },
      content: [{
        tag: 'div',
        className: 'wfb-step-before'
      }, stage.title, {
        tag: 'div',
        className: 'wfb-step-after'
      }],
    };
  }

  /**
   * Return stage tabs
   * @param {Object} classes
   * @return {Object} stage tabs
   */
  getSteps(classes) {
    const _this = this;
    const steps = {
      tag: 'ul',
      attrs: {
        className: 'efb-steps p-0'
      },
      content: []
    };
    formData.stages.forEach(function(stage) {
      steps.content.push(_this.addStepItem(stage, classes.step));
    });
    steps.content[0].attrs.className = classes.active;
    return steps;
  }

  /**
   * Extrastyle for tabs
   * @param {String} type of step
   * @param {String} composedClass
   * @param {Object} obj
   * @return {String} extra styles
   */
  getExtraStyles(type, composedClass, obj) {
    let style = '';
    // Applying background color as border color because arrow's start and end have only border
    const backgroundColor = obj[type]['background-color'].value;
    const color = obj[type].color.value;
    switch (type) {
      case 'active':
      case 'complete':
      case 'danger':
        style = `
        ${composedClass} {
          background-color: ${backgroundColor};
          color: ${color};
        }
        ${composedClass} .wfb-step-before {
          border-color: ${backgroundColor};
          border-left-color: transparent;
        }
        ${composedClass} .wfb-step-after {
          border-left-color: ${backgroundColor};
        }
        `;
        break;
      default:
        style = `
        ${composedClass} {
          background-color: ${backgroundColor};
          color: ${color};
        }
        ${composedClass} .wfb-step-before {
          border-color: ${backgroundColor};
          border-left-color:transparent;
        }
        ${composedClass} .wfb-step-after {
          border-color: transparent;
          border-left-color: ${backgroundColor};
        }
        `;
        break;
    }
    return style;
  }

  /**
   * Returning the step classes for general|active|completed step
   * @param {String} type of step
   * @param {Object} obj
   */
  processStepClasses(type, obj) {
    const _this = this;
    let style = '';
    const styles = document.getElementById('wfb-styles-for-' + type);
    let classes = obj[type].class.value.trim();
    classes = classes.split(' ');
    let composedClass = '';
    classes.forEach(function(singleClass) {
      if (singleClass.trim() != '') {
        composedClass += '.' + singleClass;
      }
    });
    style += _this.getExtraStyles(type, composedClass, obj);
    if (styles === null) {
      const styles = {
        tag: 'style',
        attrs: {
          id: 'wfb-styles-for-' + type
        },
        content: style
      };
      const body = document.getElementsByTagName('body');
      body[0].append(_this.create(styles));
      return;
    }
    styles.innerHTML = style;
  }

  /**
   * Returning the step classes for general|active|completed step
   * @param {String} type of step
   * @param {Object} obj1
   * @param {Object} obj2
   * @return {String} step class
   */
  getStepClass(type, obj1, obj2) {
    const _this = this;
    const defaultValue = obj1[type].class.value;
    const newValue = obj2[type].class.value;
    _this.processStepClasses(type, obj2); // Processing class every time to override own styles
    return defaultValue == newValue ? defaultValue : newValue;
  }

  /**
   * Returning object of form submit button
   * @param {String} extraClass for submit button
   * @return {Object} submit button object
   */
  getFormSubmitButton(extraClass = '') {
    const formSettings = this.getFormSettings();
    return {
      tag: 'button',
      attrs: {
        id: 'submit-form',
        className: formSettings.submit.class.value + extraClass,
        type: 'button',
        'data-processing': formSettings.submit['processing-text'].value,
        style: formSettings.submit.style.value
      },
      action: {
        click: evt => {
          this.checkValidity(this.renderTarget);
          return;
        }
      },
      content: formSettings.submit.text.value
    };
  }

  /**
   * Returning form button position settings
   * @return {String} position of submit button
   */
  getSubmitButtonPosition() {
    const formSettings = this.getFormSettings();
    let position = formSettings.submit.position.value;
    position = position ? position : 'center';
    return 'text-' + position;
  }

  /**
   * @param {Object} stages json object containing stages configuration
   * @param {Dom} renderTarget dom element to indentify rendering target
   * @return {Object} json object for creating navigation
   */
  prepareStageNavigation(stages, renderTarget) {
    const _this = this;
    if (formData.stages.size < 2) {
      return null;
    }
    const defaultConfig = _this.getTabDefaultConfigs();
    let tabSettings = formData.settings.get('tabSettings');
    if (typeof tabSettings == 'undefined') {
      tabSettings = defaultConfig;
    }
    const classes = {
      step: _this.getStepClass('default', defaultConfig, tabSettings),
      active: _this.getStepClass('active', defaultConfig, tabSettings),
      complete: _this.getStepClass('complete', defaultConfig, tabSettings),
      danger: _this.getStepClass('danger', defaultConfig, tabSettings)
    };
    const navigation = {
      tag: 'div',
      attrs: {
        className: ['form-submit', 'step-navigation', this.getSubmitButtonPosition()]
      },
      content: [{
        tag: 'button',
        attrs: {
          id: 'previous-step',
          className: 'btn btn-default d-none',
          type: 'button'
        },
        action: {
          click: evt => {
            _this.changeStep(evt, renderTarget, classes, -1);
            return;
          }
        },
        content: getString('efb-btn-previous')
      }, _this.getFormSubmitButton(' ml-2 d-none'), {
        tag: 'button',
        attrs: {
          id: 'next-step',
          className: 'btn btn-primary ml-2',
          type: 'button'
        },
        action: {
          click: evt => {
            _this.changeStep(evt, renderTarget, classes, 1);
            return;
          }
        },
        content: getString('efb-btn-next')
      }]
    };
    const stageNavigation = {
      navigation: navigation,
      steps: _this.getSteps(classes)
    };
    return stageNavigation;
  }

  /**
   * Creating designer container for the tab view
   * @param {Object} item droped item
   */
  createTabContainer(item) {
    const _this = this;
    data.addStage();
    const formWrapper = this.container.querySelector('.form-container');
    dom.activeStage = formWrapper.querySelector('.stage-wrap.active').querySelector('.stage');
    const stageTabControl = dom.getStageControl();
    const stageTabs = dom.create(stageTabControl);
    formWrapper.prepend(stageTabs);
    dom.remove(item);
    const tabControl = document.querySelector('.layout-tab-control');
    if (tabControl) {
      hideControl('layout-tab-control');
    }
    dom.tabSorting();
    _this.toggleFormDeleteAction();
  }

  /**
   * Returning the default settings of form container
   * @return {Object} formSettings
   */
  getFormDefaultSettings() {
    const formSettings = {
      'class': {
        title: getString('class'),
        id: 'class',
        type: 'text',
        value: 'efb-form'
      },
      'background-color': {
        title: getString('backgroundcolor'),
        id: 'background-color',
        type: 'color',
        value: '#ffffff'
      },
      'width': {
        title: getString('form-width'),
        id: 'width',
        type: 'range',
        value: '100',
        attrs: {
          min: '20',
          max: '100'
        }
      },
      'responsive': {
        title: getString('form-responsive'),
        id: 'responsive',
        type: 'toggle',
        value: true,
      },
      'padding': {
        title: getString('form-padding'),
        id: 'padding',
        type: 'range',
        value: '40',
        attrs: {
          min: '0',
          max: '100'
        }
      },
      'color': {
        title: getString('textcolor'),
        id: 'color',
        type: 'color',
        value: '#000000'
      },
      'display-label': {
        title: getString('display-label'),
        id: 'display-label',
        type: 'select',
        value: 'top',
        options: {
          option1: {
            value: 'off',
            label: getString('display-label-off'),
          },
          option2: {
            value: 'top',
            label: getString('display-label-top')
          },
          option3: {
            value: 'left',
            label: getString('display-label-left')
          }
        }
      },
      'style': {
        title: getString('customcssstyle'),
        id: 'style',
        type: 'textarea',
        value: ''
      }
    };
    const submitButtonSetting = {
      'class': {
        title: getString('class'),
        id: 'class',
        type: 'text',
        value: 'btn btn-primary',
      },
      'text': {
        title: getString('submitbuttontext'),
        id: 'text',
        type: 'text',
        value: 'Submit'
      },
      'processing-text': {
        title: getString('submitbuttonprocessingtext'),
        id: 'processing-text',
        type: 'text',
        value: 'Submitting....'
      },
      'position': {
        title: getString('submitbuttonposition'),
        id: 'position',
        type: 'select',
        value: 'center',
        options: {
          option1: {
            value: 'left',
            label: getString('position-left')
          },
          option2: {
            value: 'center',
            label: getString('position-center')
          },
          option3: {
            value: 'right',
            label: getString('position-right')
          }
        }
      },
      'style': {
        title: getString('customcssstyle'),
        id: 'style',
        type: 'textarea',
        value: ''
      }
    };
    const pageSetting = {
      'class': {
        title: getString('class'),
        id: 'class',
        type: 'text',
        value: ''
      },
      'background-opacity': {
        title: getString('page-background-opacity'),
        id: 'background-opacity',
        type: 'range',
        value: '0',
        attrs: {
          step: '0.1',
          min: '0',
          max: '1'
        }
      },
      'style': {
        title: getString('customcssstyle'),
        id: 'style',
        type: 'textarea',
        value: ''
      }
    };
    return {
      page: pageSetting,
      form: formSettings,
      submit: submitButtonSetting,
    };
  }

  /**
   * Return form settings
   * @return {Object} formSettings with replaced labels
   */
  getFormSettings() {
    const formSettings = this.getFormDefaultSettings();
    if (formData.settings.get('formSettings') != undefined) {
      Object.keys(formSettings).forEach(category => {
        Object.assign(formSettings[category], formData.settings.get('formSettings')[category]);
      });
    }
    return formSettings;
  }

  /**
   * Merging settings and styles
   * @param {Object} settings
   * @param {String} styles
   * @return {String} styles
   */
  mergeStyles(settings, styles) {
    const stylesObj = {};
    let prop;
    let val;
    let styleString = '';
    let index;
    styles = styles.trim();
    if (styles != '') {
      styles = styles.split(';');
      h.forEach(styles, function(style, i) {
        style = style.trim();
        if (style != '') {
          index = style.indexOf(':');
          prop = style.substring(0, index).trim();
          val = style.substring(index + 1).trim();
          if (prop != '' && val != '') {
            stylesObj[prop] = val;
          }
        }
      });
      settings = h.merge(settings, stylesObj);
    }
    Object.keys(settings).forEach(prop1 => {
      styleString += prop1 + ': ' + settings[prop1] + '; ';
    });
    return styleString;
  }

  /**
   * Get max column count
   * @return {Number} Max column
   */
  getMaxColumnCount() {
    if (formData.rows.size == 0) {
      return 0;
    }
    let maxColumns = 0;
    formData.rows.forEach(function(row) {
      if (row.columns.length > maxColumns) {
        maxColumns = row.columns.length;
      }
    });
    return maxColumns;
  }

  /**
   * Manage form width according to width available in preview page
   */
  manageFormWidth() {
    const formSettings = this.getFormSettings();
    const maxColumns = this.getMaxColumnCount();
    const width = formSettings.form.width ? formSettings.form.width.value : '100';
    this.renderTarget.style.width = width + '%';
    const toggleClass = status => {
      if (status == true) {
        this.renderTarget.classList.add('edwiser-inline-form');
      } else {
        this.renderTarget.classList.remove('edwiser-inline-form');
      }
    };
    if (formSettings.form.responsive.value == false) {
      toggleClass(false);
      return;
    }
    const availableWidth = document.getElementById(`formeo-rendered-${document.getElementsByClassName('formeo-render').length - 1}`).offsetWidth;
    switch (maxColumns) {
      case 0:
      case 1:
        toggleClass(false);
        break;
      case 2:
        toggleClass(availableWidth < 360);
        break;
      case 3:
        toggleClass(availableWidth < 480);
        break;
      case 4:
        toggleClass(availableWidth < 640);
        break;
      default:
        toggleClass(availableWidth < 800);
        break;
    }
  }

  /**
   * Processing form settings
   * @param {DOM} renderTarget
   */
  processFormSettings(renderTarget) {
    const formSettings = this.getFormSettings();
    // Getting form setting like classname, color and background color
    const className = formSettings.form.class ? formSettings.form.class.value : '';
    const color = formSettings.form.color ? formSettings.form.color.value : 'inherit';
    const backgroundColor = formSettings.form['background-color'] ? formSettings.form['background-color'].value : 'inherit';
    const padding = formSettings.form.padding ? formSettings.form.padding.value : '25';
    const width = formSettings.form.width ? formSettings.form.width.value : '100';
    const margin = width == 100 ? '0 auto' : '5% auto';
    renderTarget.classList.add(className);
    // Adding form class in renderTarget to apply settings
    const settings = {
      color: color,
      'background-color': backgroundColor,
      margin: margin,
      padding: padding + 'px'
    };
    let styles = formSettings.form.style ? formSettings.form.style.value : '';
    styles = this.mergeStyles(settings, styles);
    renderTarget.setAttribute('style', styles);
    this.manageFormWidth();
  }

  /**
   * Processing form settings
   * @param {DOM} renderTarget
   */
  processPageSettings(renderTarget) {
    const formSettings = this.getFormSettings();
    // Getting form setting like classname, style
    const className = formSettings.page.class ? formSettings.page.class.value : '';
    const styles = formSettings.page.style ? formSettings.page.style.value : '';
    const backgroundopacity = formSettings.page['background-opacity'] ? formSettings.page['background-opacity'].value : '0';
    const id = 'edwiserform-background-cover';
    const style = `position: fixed; width: 100%; height: 100%; background: rgba(0,0,0,${backgroundopacity});`;
    renderTarget.parentElement.style.background = 'rgba(0, 0, 0, ' + backgroundopacity + ')';
    renderTarget.parentElement.style.margin = '0';
    renderTarget.parentElement.style.padding = '0';
    const preview = document.getElementById('efb-cont-form-preview');
    // Adding page class in body element
    if (className != '') {
      preview.classList.add(className);
    }
    // Applying custom style to preview element
    if (styles != '') {
      preview.setAttribute('style', styles);
    }
  }

  /**
   * Renders currently loaded formData to the renderTarget
   * @param {Object} renderTarget
   */
  renderForm(renderTarget) {
    this.empty(renderTarget);
    this.renderTarget = renderTarget;
    const renderData = data.prepData;
    const renderCount = document.getElementsByClassName('formeo-render').length;
    const stageNavigation = this.prepareStageNavigation(renderData.stages, renderTarget);
    let first = true;
    const content = Object.values(renderData.stages).map(stageData => {
      let {rows, ...stage} = stageData;
      rows = rows.map(rowID => {
        let {columns, ...row} = renderData.rows[rowID];
        const cols = columns.map(columnID => {
          const col = this.processColumnConfig(renderData.columns[columnID]);
          const fields = col.fields.map(fieldID => renderData.fields[fieldID]);
          col.tag = 'div';
          col.content = fields;
          return col;
        });
        row.tag = 'div';
        row.content = [cols];
        const rowData = clone(row);
        if (row.config.inputGroup) {
          const removeButton = {
            tag: 'button',
            className: 'remove-input-group',
            content: dom.icon('remove'),
            action: {
              mouseover: e => {
                e.target.parentElement.classList.add('will-remove');
              },
              mouseleave: e => {
                e.target.parentElement.classList.remove('will-remove');
              },
              click: e => {
                const currentInputGroup = e.target.parentElement;
                const iGWrap = currentInputGroup.parentElement;
                const iG = iGWrap.getElementsByClassName('f-input-group');
                if (iG.length > 1) {
                  dom.remove(currentInputGroup);
                } else {
                  console.log('Need at least 1 group');
                }
              }
            }
          };
          rowData.content.unshift(removeButton);
          const inputGroupWrap = {
            tag: 'div',
            id: uuid(),
            className: 'f-input-group-wrap'
          };
          if (rowData.attrs.className) {
            if (typeof rowData.attrs.className === 'string') {
              rowData.attrs.className += ' f-input-group';
            } else {
              rowData.attrs.className.push('f-input-group');
            }
          }
          const addButton = {
            tag: 'button',
            attrs: {
              className: 'add-input-group btn pull-right',
              type: 'button'
            },
            content: 'Add +',
            action: {
              click: e => {
                const fInputGroup = e.target.parentElement;
                const newRow = dom.create(rowData);
                fInputGroup.insertBefore(newRow, fInputGroup.lastChild);
              }
            }
          };

          row.content.unshift(removeButton);
          inputGroupWrap.content = [rowData, addButton];
          row = inputGroupWrap;
        }
        return row;
      });
      stage.tag = 'div';
      stage.content = rows;
      stage.className = 'f-stage';
      stage.title = '';
      if (first) {
        first = false;
        stage.className += ' active';
      } else {
        stage.className += ' d-none';
      }
      return stage;
    });

    const config = {
      tag: 'div',
      id: `formeo-rendered-${renderCount}`,
      className: 'formeo-render formeo',
      content
    };

    renderTarget.appendChild(this.create(config));
    if (stageNavigation !== null) {
      renderTarget.append(this.create(stageNavigation.navigation));
      renderTarget.prepend(this.create(stageNavigation.steps));
    } else {
      const stageNavigation = {
        tag: 'div',
        className: ['form-submit', this.getSubmitButtonPosition()],
        content: [dom.getFormSubmitButton()]
      };
      renderTarget.append(this.create(stageNavigation));
    }
    conditions.applyConditions(formData.rows);
    dom.processFormSettings(renderTarget);
    dom.processPageSettings(renderTarget);
    renderTarget.prepend(this.create({
      tag: 'h2',
      content: document.getElementById('id_title').value
    }));
  }

  /**
   * Clears the editor
   * @param  {Object} evt
   */
  clearStep(evt) {
    this.clearStage(dom.activeStage);
    // This.stages.forEach(dStage => this.clearStage(dStage.stage));
  }

  /**
   * Clears the editor
   * @param  {Object} evt
   */
  clearAllSteps(evt) {
    this.stages.forEach(dStage => this.clearStage(dStage.stage));
  }
  /**
   * Clears the editor
   * @param  {Object} evt
   */
  clearForm(evt) {
    dom.clearStep(evt);
    const editor = document.getElementById('efb-cont-form-builder');
    const stages = editor.querySelectorAll('.stage-wrap:not(.active)');
    stages.forEach(stage => {
      const stageRemoveButton = editor.querySelector('.prop-remove[id="remove-stage-' + stage.id + '"]');
      dom.removeStage(stageRemoveButton, stage.id);
    });
  }

  /**
   * Removes all fields and resets a stage
   * @param  {[type]} stage DOM element
   */
  clearStage(stage) {
    stage.classList.add('removing-all-fields');
    const resetStage = () => {
      // Empty the data register for stage
      // and everything below it.
      dom.empty(stage);
      stage.classList.remove('removing-all-fields');
      data.save();
      dom.emptyClass(stage);
      animate.slideDown(stage, 300, function() {
        stage.style.height = '100%';
      });
    };

    // Var markEmptyArray = [];

    // if (opts.prepend) {
    //   markEmptyArray.push(true);
    // }

    // if (opts.append) {
    //   markEmptyArray.push(true);
    // }

    // if (!markEmptyArray.some(elem => elem === true)) {
    // stage.classList.add('empty-stages');
    // }

    animate.slideUp(stage, 600, resetStage);
    // Animate.slideUp(stage, 2000);
  }

  /**
   * Adds a row to the stage
   * @param {String} stageID
   * @param {String} rowID
   * @return {Object} DOM element
   */
  addRow(stageID, rowID) {
    const row = new Row(rowID);
    const stage = stageID ? this.stages.get(stageID).stage : this.activeStage;
    stage.appendChild(row);
    data.saveRowOrder(stage);
    this.emptyClass(stage);
    events.formeoUpdated = new CustomEvent('formeoUpdated', {
      data: {
        updateType: 'added',
        changed: 'row',
        oldValue: undefined,
        newValue: formData.rows.get(row.id)
      }
    });
    document.dispatchEvent(events.formeoUpdated);
    return row;
  }

  /**
   * Adds a Column to a row
   * @param {String} rowID
   * @param {String} columnID
   * @return {Object} DOM element
   */
  addColumn(rowID, columnID) {
    const column = new Column(columnID);
    const row = this.rows.get(rowID).row;
    row.appendChild(column);
    data.saveColumnOrder(row);
    this.emptyClass(row);
    events.formeoUpdated = new CustomEvent('formeoUpdated', {
      data: {
        updateType: 'added',
        changed: 'column',
        oldValue: undefined,
        newValue: formData.columns.get(column.id)
      }
    });
    document.dispatchEvent(events.formeoUpdated);
    return column;
  }

  /**
   * Toggles a sortables `disabled` option.
   * @param  {Object} elem DOM element
   * @param  {Boolean} state
   */
  toggleSortable(elem, state) {
    const {fType} = elem;
    if (!fType) {
      return;
    }
    const pFtype = elem.parentElement.fType;
    const sortable = dom[fType].get(elem.id).sortable;
    if (state === undefined) {
      state = !sortable.option('disabled');
    }
    sortable.option('disabled', state);
    if (pFtype && h.inArray(pFtype, ['rows', 'columns', 'stages'])) {
      this.toggleSortable(elem.parentElement, state);
    }
  }

  /**
   * Adds a field to a column
   * @param {String} columnID
   * @param {String} fieldID
   * @param {String} action
   * @return {Object} field
   */
  addField(columnID, fieldID, action = null) {
    let field;
    if (action == 'clone') {
      field = new Field(fieldID, action);
    } else {
      field = formData.fields.get(fieldID) || clone(rFields[fieldID]);
      if (h.get(field, 'config.composite')) {
        field = addCompositeFields(columnID, field);
      } else {
        field = new Field(fieldID, action);
      }
    }
    if (columnID) {
      const column = this.columns.get(columnID).column;
      column.appendChild(field);
      data.saveFieldOrder(column);
      this.emptyClass(column);
    }
    events.formeoUpdated = new CustomEvent('formeoUpdated', {
      data: {
        updateType: 'add',
        changed: 'field',
        oldValue: undefined,
        newValue: formData.fields.get(field.id)
      }
    });
    document.dispatchEvent(events.formeoUpdated);
    return field;
  }

  /**
   * Toggle form actions depending on stages status
   */
  toggleFormDeleteAction() {
    if (this.container) {
      const action = this.container.querySelector('.item-delete-form');
      const result = formData.fields.size == 0 && formData.stages.size == 1;
      if (action) {
        if (result == true) {
          action.classList.add('d-none');
          action.parentElement.parentElement.classList.add('hide-delete');
        } else {
          action.classList.remove('d-none');
          action.parentElement.parentElement.classList.remove('hide-delete');
        }
      }
    }
  }

  /**
   * Check is the form empty and apply d-none class to import form button
   */
  toggleImportButton() {
    const importButton = document.querySelector('.formeo-import-form');
    if (importButton) {
      if ((formData.stages.size == 1 && formData.fields.size == 0)) {
        importButton.classList.remove('d-none');
      } else {
        importButton.classList.add('d-none');
      }
    }
  }


  /**
   * Aplly empty class to element if does not have children
   * @param  {Object} elem
   */
  emptyClass(elem) {
    const type = elem.fType;
    if (type) {
      const childMap = new Map();
      childMap.set('stages', 'rows');
      childMap.set('rows', 'columns');
      childMap.set('columns', 'fields');
      const children = elem.getElementsByClassName(`stage-${childMap.get(type)}`);
      if (children.length == 0) {
        elem.classList.add(`empty-${type}`);
      } else {
        elem.classList.remove(`empty-${type}`);
      }
      if (type == 'stages') {
        this.toggleFormDeleteAction();
      }
    }
  }

  /**
   * Shorthand expander for dom.create
   * @param  {String} tag
   * @param  {Object} attrs
   * @param  {Object|Array|String} content
   * @return {Object} DOM node
   */
  h(tag, attrs, content) {
    return this.create({tag, attrs, content});
  }

  /**
   * Style Object
   * @param  {Object} rules
   * @return {Number} index of added rule
   */
  insertRule(rules) {
    let index;
    const styleSheet = this.styleSheet;
    const rulesLength = styleSheet.cssRules.length;
    for (let i = 0, rl = rules.length; i < rl; i++) {
      let j = 1;
      let rule = rules[i];
      const selector = rules[i][0];
      let propStr = '';
      // If the second argument of a rule is an array
      // of arrays, correct our variables.
      if (Object.prototype.toString.call(rule[1][0]) === '[object Array]') {
        rule = rule[1];
        j = 0;
      }

      for (let pl = rule.length; j < pl; j++) {
        const prop = rule[j];
        const important = (prop[2] ? ' !important' : '');
        propStr += `${prop[0]}:${prop[1]}${important};`;
      }
      // Insert CSS Rule
      index = styleSheet.insertRule(`${selector} { ${propStr} }`, rulesLength);
    }
    return index;
  }

  /**
   * Checking is there control which can be added only once
   */
  checkSingle() {
    defaultElements.forEach(function(defaultElement, index) {
      if (h.get(defaultElement, 'config.single')) {
        let show = true;
        formData.fields.forEach(function(element, index) {
          if (element.meta.group == defaultElement.meta.group && element.meta.id == defaultElement.meta.id) {
            show = false;
          }
        });
        if (show == true) {
          showControl(`${defaultElement.meta.id}-control`);
        } else {
          hideControl(`${defaultElement.meta.id}-control`);
        }
      }
    });
  }

  /**
   * Show pro warning to user
   * @param {String} msg for warning
   */
  proWarning(msg = null) {
    const M = window.M;
    msg = getString('efb-template-inactive-license', msg);
    let warning = {
      tag: 'div',
      content: msg
    };
    warning = dom.create(warning);
    dom.multiActions(
        'warning',
        getString('hey-wait'),
        warning,
        [{
          title: getString('activatelicense'),
          type: 'success',
          action: function() {
            window.location.href = M.cfg.wwwroot + '/admin/settings.php?section=local_edwiserform&activetab=local_edwiserform_license_status';
          }
        }, {
          title: getString('later'),
          type: 'warning'
        }]
    );
  }

  /**
   * Return dialog object with passed contents
   * @param {String} id of modal
   * @param {Array} content to add in dialog
   * @param {function} keyup events
   * @return {Object} dialog for modal
   */
  modalContainer(id, content, keyup) {
    const dialog = {
      tag: 'div',
      attrs: {
        className: 'efb-modal-dialog',
        role: 'document'
      },
      content: [{
        tag: 'div',
        className: 'efb-modal-content',
        content: content
      }],
      action: {
        keyup: keyup
      }
    };
    document.addEventListener('keyup', keyup);
    return {
      tag: 'div',
      id: id,
      attrs: {
        className: 'efb-modal fade',
        role: 'dialog',
        'aria-hidden': true
      },
      content: [dialog]
    };
  }

  /**
   * Return object for modal header
   * @param {String} id of modal
   * @param {String} title for modal
   * @return {Object} header of modal
   * @param {String} type of prompt window
   * @param {function} keyup function
   */
  modalHeader(id, title, type, keyup) {
    const _this = this;
    const action = {
      click: evt => {
        _this.removeModal(id, keyup);
      }
    };
    return {
      tag: 'div',
      className: 'efb-modal-header bg-' + type,
      content: [{
        tag: 'h4',
        attrs: {
          className: 'efb-modal-title text-white',
          id: 'modal-' + id
        },
        content: title
      }, {
        tag: 'button',
        attrs: {
          type: 'button',
          className: 'close efb-modal-close text-white',
          'data-dismiss': id,
          'aria-label': getString('close')
        },
        content: [{
          tag: 'span',
          attrs: {
            'aria-hidden': true
          },
          content: 'x',
          action: action
        }],
        action: action
      }]
    };
  }

  /**
   * @param {Object} modal object
   */
  addModal(modal) {
    modal = dom.create(modal);
    document.querySelector('body').appendChild(modal);
    setTimeout(function() {
      modal.classList.toggle('show');
      const event = new CustomEvent('focus', {target: modal});
      modal.dispatchEvent(event);
    }, 150);
  }

  /**
   * Removing modal
   * @param {String} id of modal element
   * @param {function} keyup function
   */
  removeModal(id, keyup = null) {
    const modal = document.getElementById(id);
    if (!modal) {
      return;
    }
    modal.classList.remove('show');
    setTimeout(function() {
      modal.remove();
      if (keyup) {
        document.removeEventListener('keyup', keyup);
      }
    }, 150);
  }

  /**
   * Toaster modal
   * @param {String} title for prompt window
   * @param {Number} time
   */
  toaster(title, time = 2000) {
    const id = uuid();
    const toast = this.create({
      tag: 'div',
      attrs: {
        id,
        className: 'efb-toaster toaster-container',
      },
      content: [{
        tag: 'lable',
        className: 'toaster-message',
        content: title
      }]
    });
    document.querySelector('body').appendChild(toast);
    document.getElementById(id).classList.add('show');
    setTimeout(function() {
      document.getElementById(id).classList.add('fade');
      setTimeout(function() {
        document.getElementById(id).classList.remove('fade');
        setTimeout(function() {
          document.getElementById(id).remove();
        }, time);
      }, time);
    });
  }

  /**
   * Display loading effect
   */
  loading() {
    const _this = this;
    const id = 'efb-modal-loading';
    const modal = {
      tag: 'div',
      id: id,
      attrs: {
        className: 'efb-modal fade',
        role: 'dialog',
        'aria-hidden': true
      },
      content: {
        tag: 'div',
        className: 'efb-modal-loader'
      }
    };
    _this.addModal(modal);
  }

  /**
   * Display loading effect
   */
  loadingClose() {
    const _this = this;
    const id = 'efb-modal-loading';
    _this.removeModal(id);
  }

  /**
   * @param {String} type of prompt window
   * @param {String} msg for prompt window
   * @param {function} action to apply after pressing ok button
   */
  alert(type, msg, action = null) {
    const _this = this;
    const id = uuid();
    const keyup = evt => {
      if (evt.keyCode == 27) {
        if (action !== null) {
          action();
        }
        _this.removeModal(id, keyup);
      }
    };
    const title = getString(type);
    const body = {
      tag: 'div',
      className: 'efb-modal-body',
      content: [{
        tag: 'h5',
        content: msg
      }]
    };
    const footer = {
      tag: 'div',
      className: 'efb-modal-footer',
      content: [{
        tag: 'button',
        attrs: {
          className: 'btn btn-success',
          type: 'button'
        },
        content: getString('ok'),
        action: {
          click: evt => {
            _this.removeModal(id, keyup);
            if (action !== null) {
              action();
            }
          },
          keyup: keyup
        }
      }]
    };
    const modal = _this.modalContainer(id, [_this.modalHeader(id, title, type, keyup), body, footer], keyup);
    _this.addModal(modal);
  }

  /**
   * @param {String} type of prompt window
   * @param {String} title for prompt window
   * @param {String} msg for prompt window
   * @param {function} actions function
   */
  multiActions(type, title, msg, actions) {
    const _this = this;
    const id = uuid();
    const keyup = evt => {
      if (evt.keyCode == 27) {
        _this.removeModal(id, keyup);
      }
    };
    const body = {
      tag: 'div',
      className: 'efb-modal-body',
      content: [{
        tag: 'h5',
        content: msg
      }]
    };
    const footer = {
      tag: 'div',
      className: 'efb-modal-footer',
      content: []
    };
    actions.forEach(function(button, i) {
      footer.content.push({
        tag: 'button',
        attrs: {
          className: `btn btn-${button.type}`,
          type: 'button'
        },
        content: button.title,
        action: {
          click: evt => {
            if (button.action) {
              const status = button.action(evt);
              if (typeof status == 'boolean' && status == false) {
                return;
              }
            }
            _this.removeModal(id, keyup);
          }
        }
      });
    });
    const modal = _this.modalContainer(id, [_this.modalHeader(id, title, type, keyup), body, footer], keyup);
    _this.addModal(modal);
  }

  /**
   * @param {String} type of prompt window
   * @param {String} title for prompt window
   * @param {String} msg for prompt window
   * @param {function} action function
   */
  confirm(type, title, msg, action) {
    const _this = this;
    const id = uuid();
    const applyAction = () => {
      action();
      _this.removeModal(id, keyup);
    };
    const keyup = evt => {
      if (evt.keyCode == 13) {
        applyAction();
        this.onkeyup = null;
      } else if (evt.keyCode == 27) {
        _this.removeModal(id, keyup);
        this.onkeyup = null;
      }
    };
    const body = {
      tag: 'div',
      className: 'efb-modal-body',
      content: [{
        tag: 'h5',
        content: msg
      }]
    };
    const footer = {
      tag: 'div',
      className: 'efb-modal-footer',
      content: [{
        tag: 'button',
        attrs: {
          className: 'btn btn-success',
          type: 'button'
        },
        content: getString('proceed'),
        action: {
          click: applyAction
        }
      }, {
        tag: 'button',
        attrs: {
          className: 'btn btn-danger',
          type: 'button'
        },
        content: getString('cancel'),
        action: {
          click: evt => {
            _this.removeModal(id, keyup);
          }
        }
      }]
    };
    const modal = _this.modalContainer(id, [_this.modalHeader(id, title, type, keyup), body, footer], keyup);
    _this.addModal(modal);
  }

  /**
   * @param {String} type of prompt window
   * @param {String} title for prompt window
   * @param {function} addAction function
   */
  addAttributePrompt(type, title, addAction) {
    const _this = this;
    const id = uuid();
    let applied = false;
    const applyAction = () => {
      const attr = document.getElementById('attr-' + id).value;
      const value = document.getElementById('value-' + id).value;
      _this.removeModal(id, keyup);
      if (attr) {
        if (!applied) {
          applied = true;
          addAction(attr, value);
        }
      }
    };
    const keyup = evt => {
      if (evt.keyCode == 13) {
        applyAction();
      } else if (evt.keyCode == 27) {
        _this.removeModal(id, keyup);
      }
    };
    const body = {
      tag: 'div',
      className: 'efb-modal-body',
      content: [{
        tag: 'div',
        content: getString('attribute-help')
      }, {
        tag: 'input',
        attrs: {
          id: 'attr-' + id,
          className: 'form-control',
          type: 'text'
        },
        config: {
          label: getString('attribute')
        },
        action: {
          keyup: keyup
        }
      }, {
        tag: 'input',
        attrs: {
          id: 'value-' + id,
          className: 'form-control',
          type: 'text'
        },
        config: {
          label: getString('value')
        },
        action: {
          keyup: keyup
        }
      }]
    };
    const footer = {
      tag: 'div',
      className: 'efb-modal-footer',
      content: [{
        tag: 'button',
        attrs: {
          className: 'btn btn-success',
          type: 'button'
        },
        content: getString('proceed'),
        action: {
          click: applyAction
        }
      }, {
        tag: 'button',
        attrs: {
          className: 'btn btn-danger',
          type: 'button'
        },
        content: getString('cancel'),
        action: {
          click: evt => {
            _this.removeModal(id, keyup);
          }
        }
      }]
    };
    const modal = _this.modalContainer(id, [_this.modalHeader(id, title, type, keyup), body, footer], keyup);
    _this.addModal(modal);
  }
}

const dom = new DOM();

export default dom;

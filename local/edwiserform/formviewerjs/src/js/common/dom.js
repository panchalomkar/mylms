import h from './helpers';
import {data, formData} from './data';
import {uuid, clone, getString, remove, elementTagType} from './utils';

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
   * Check for standard element
   * @param  {Object}  elem      element config object
   * @return {Boolean}            true | false
   */
  getWrapperClass(elem) {
    if (Object.prototype.hasOwnProperty.call(elem, 'className') && elem.className == 'g-recaptcha') {
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
    let className = h.get(elem, 'attrs.className') || '';
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
          elem.attrs.className.push(elem.attrs.class);
        } else {
          elem.attrs.className += ' ' + elem.attrs.class;
        }
      } else {
        elem.attrs.className = elem.attrs.class;
      }
      delete elem.attrs.class;
    }
    return elem;
  }

  /**
   * Creates DOM elements
   * @param  {Object}  elem      element config object
   * @return {Object}            DOM Object
   */
  create(elem) {
    elem = this.processClass(elem);
    elem = this.processTagName(elem);
    const _this = this;
    let contentType;
    const {tag} = elem;
    const processed = [];
    let i;
    let displayLabel = '';
    const labelAfter = _this.labelAfter(elem);
    const formSettings = _this.getFormSettings();
    if (h.get(elem, 'meta.id') == 'country') {
      elem.options = [];
      for (const [code, country] of Object.entries(this.countries)) {
        elem.options.push({
          label: country,
          value: code
        });
      }
    }
    if (formSettings && formSettings.form) {
      displayLabel = formSettings.form['display-label'].value;
      switch (displayLabel) {
        case 'top':
          displayLabel = '';
          break;
        case 'left':
          displayLabel = 'single-line';
          break;
      }
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

    if (!h.get(elem, 'attrs')) {
      elem.attrs = {};
    }

    // Check for id property
    if (elem.attrs && elem.attrs.id) {
      elem.id = elem.attrs.id;
    }

    if (h.get(elem, 'attrs.name') == undefined || h.get(elem, 'attrs.name') == '') {
      elem.attrs.name = elem.id;
    }

    // Append Element Content
    if (elem.options) {
      let {options} = elem;
      options = this.processOptions(options, elem);
      if (this.holdsContent(element) && tag !== 'button') {
        // Mainly used for <select> tag
        appendContent.array.call(this, options);
        delete elem.content;
      } else {
        h.forEach(options, option => {
          wrap.content.push(_this.create(option));
        });

        wrap.config = Object.assign({}, elem.config);

        if (elem.tag == 'input' && (elem.attrs.type == 'radio' || elem.attrs.type == 'checkbox')) {
          wrap.attrs.wrapClass = 'input-' + elem.attrs.type + '-wrapper';
        }

        if (required) {
          wrap.attrs.required = required;
        }

        return this.create(wrap);
      }

      processed.push('options');
    }


    if (elem.config) {
      if (h.get(elem, 'config.recaptcha')) {
        delete elem.attrs.placeholder;
        elem.attrs['data-sitekey'] = _this.sitekey;
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
        let label;
        if (displayLabel == 'off') {
          elem.attrs.placeholder = 'label' in elem.config ? elem.config.label : '';
        }
        label = _this.label(elem);

        if (!labelAfter && required) {
          label.append(this.create(requiredMark));
        }

        if (!elem.config.hideLabel) {
          if (_this.labelAfter(elem)) {
            // Add check for inline checkbox
            wrap.className = `f-${elem.attrs.type}`;

            label.insertBefore(element, label.firstChild);
            wrap.content.push(label);
          } else {
            wrap.content.push(label);
            wrap.content.push(element);
          }
        }
      }

      processed.push('config');
    }

    elem = this.missingFormControlClass(elem);

    // Set element attributes
    if (elem.attrs) {
      _this.processAttrs(elem, element);
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
        if (Object.prototype.hasOwnProperty.call(elem.dataset, data)) {
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
   * JS Object to DOM attributes
   * @param  {Object} elem    element config object
   * @param  {Object} element DOM element we are building
   * @return {void}
   */
  processAttrs(elem, element) {
    const {attrs = {}} = elem;
    delete attrs.tag;

    // Set element attributes
    Object.keys(attrs).forEach(attr => {
      const name = h.safeAttrName(attr);
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
        element.setAttribute(name, value);
        if (name == 'validation') {
          element.setCustomValidity(value);
        }
      }
    });
  }

  /**
   * Extend Array of option config objects
   * @param  {Array} options
   * @param  {Object} elem element config object
   * @return {Array} option config objects
   */
  processOptions(options, elem) {
    elem = clone(elem);
    const {action, attrs} = elem;
    const fieldType = attrs.type || elem.tag;
    const id = attrs.id || elem.id;

    const optionMap = (option, i) => {
      const defaultInput = () => {
        let input = {
          tag: 'input',
          attrs: attrs,
          action
        };
        delete input.attrs.className;
        input.attrs.id = id + '-' + i;
        input.attrs.name = attrs.name;
        input.attrs.type = fieldType;
        input.attrs.value = option.value || '';
        const checkable = {
          tag: 'span',
          className: 'checkable',
          content: option.label
        };
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
        }

        if (elem.config.inline) {
          inputWrap.className.push('f-${fieldType}-inline');
        }

        if (option.selected) {
          input.attrs.checked = true;
        }

        if (option.name) {
          input.attrs.name = option.name;
        }
        optionLabel.content = checkable;
        optionLabel = dom.create(optionLabel);
        input = dom.create(input);
        optionLabel.insertBefore(input, optionLabel.firstChild);
        inputWrap.content = optionLabel;
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
      className: [],
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
   * @return {String}
   */
  contentType(content) {
    let type = typeof content;
    if (content instanceof Node || content instanceof HTMLElement) {
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
    if (typeof elem.parentElement != 'undefined') {
      return elem.parentElement.removeChild(elem);
    }
    return true;
  }

  /**
   * Util to remove contents of DOM Object
   * @param  {Object} elem
   * @return {Object} element with its children removed
   */
  empty(elem) {
    while (elem.firstChild) {
      this.remove(elem.firstChild);
    }
    return elem;
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
    /**
     * Show validation error if present.
     * @param {DOM} target DOM element
     * @return {Boolean}
     */
    function showValidityError(target) {
      target.setCustomValidity('');
      if (target.checkValidity()) {
        return true;
      }
      if (target.hasAttribute('validation')) {
        target.setCustomValidity(target.getAttribute('validation'));
      }
      console.log('False');
      return target.reportValidity(); // Reporting error if input is not valid
    }

    if (element.className == 'g-recaptcha') {
      let response = false;
      let errormessage = getString('recaptcha-error');
      if (Object.prototype.hasOwnProperty.call(window, 'grecaptcha')) {
        response = window.grecaptcha.getResponse() != '';
        if (element.hasAttribute('validation')) {
          errormessage = element.getAttribute('validation');
        }
      }
      if (response) {
        if (element.nextSibling) {
          element.nextSibling.classList.remove('show');
        }
      } else {
        if (element.nextSibling) {
          element.nextSibling.classList.add('show');
        } else {
          const error = this.create({
            tag: 'div',
            className: 'g-recaptcha-error show',
            content: errormessage
          });
          element.after(error);
        }
      }
      return response;
    }

    if (element.classList.contains('input-checkbox-wrapper')) {
      if (element.querySelectorAll('input[type="checkbox"][required]').length == 0) {
        return true;
      }
      if (element.querySelectorAll('input[type="checkbox"][required]:checked').length != 0) {
        return true;
      }
      let notSelected = element.querySelectorAll('input[type="checkbox"][required]:not(:checked)')[0];
      if (typeof notSelected.checkValidity == 'function') {
        showValidityError(notSelected);
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
      return showValidityError(element);
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
    const steps = document.querySelectorAll('.wfb-steps .' + classes.step);
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
      'border-color': {
        title: getString('bordercolor'),
        id: 'border-color',
        type: 'color',
        value: '#838b8e'
      },
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
      'border-color': {
        title: getString('bordercolor'),
        id: 'border-color',
        type: 'color',
        value: '#46be8a'
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
      'border-color': {
        title: getString('bordercolor'),
        id: 'border-color',
        type: 'color',
        value: '#62a8ea'
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
      'border-color': {
        title: getString('bordercolor'),
        id: 'border-color',
        type: 'color',
        value: '#d9534f'
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
    _this.processStepClasses(type, obj2);
    return defaultValue == newValue ? defaultValue : newValue;
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
   * Return form settings
   * @return {Object} formSettings with replaced labels
   */
  getFormSettings() {
    /* eslint prefer-const: 0 */
    let formSettings = this.getFormDefaultSettings();
    const savedSettings = formData ? formData.settings.get('formSettings') : null;
    if (savedSettings !== undefined && savedSettings !== null) {
      Object.keys(formSettings).forEach(key => {
        if (Object.prototype.hasOwnProperty.call(savedSettings, key)) {
          Object.assign(formSettings[key], savedSettings[key]);
        }
      });
    }
    return formSettings;
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
        className: 'wfb-steps'
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
        type: 'submit',
        'data-processing': formSettings.submit['processing-text'].value,
        style: formSettings.submit.style.value
      },
      action: {
        click: evt => {
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
    if (formData.stages.size < 2) {// Returning null is stages count is less than 2
      return null;
    }
    const defaultConfig = _this.getTabDefaultConfigs();
    const tabSettings = formData.settings.get('tabSettings');
    const mergedConfig = h.merge(defaultConfig, tabSettings);
    const classes = {
      step: _this.getStepClass('default', defaultConfig, mergedConfig),
      active: _this.getStepClass('active', defaultConfig, mergedConfig),
      complete: _this.getStepClass('complete', defaultConfig, mergedConfig),
      danger: _this.getStepClass('danger', defaultConfig, mergedConfig)
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
          className: 'btn btn-secondary d-none',
          type: 'button'
        },
        action: {
          click: evt => {
            _this.changeStep(evt, renderTarget, classes, -1);
            return;
          }
        },
        content: 'Previous'
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
        content: 'Next'
      }]
    };
    const stageNavigation = {
      navigation: navigation,
      steps: _this.getSteps(classes)
    };
    return stageNavigation;
  }

  /**
   * Return object container DOM element, its required value, and operator
   * @param {Object} condition containing source, value and operator element object
   * @return {Object} element
   */
  getElementFromCondition(condition) {
    const _this = this;
    const source = condition.content[0].options;
    if (source.length < 2) {
      return {
        status: false
      };
    }
    const value = condition.content[1].options;
    if (value.length == 0) {
      return {
        status: false
      };
    }
    const operator = condition.content[2].options;
    let sourceSelected = null;
    let valueSelected = null;
    let operatorSelected = null;
    if (source.length > 0) {
      sourceSelected = source[0].value;
      for (let i = 0; i < source.length; i++) {
        if (source[i].selected == true) {
          sourceSelected = source[i].value;
          break;
        }
      }
      sourceSelected = _this.container.querySelectorAll('[id*="' + sourceSelected + '"]');
      if (sourceSelected.length == 0) {
        return {
          status: false
        };
      }
    }
    if (value.length > 0) {
      valueSelected = value[0].value;
      for (let i = 0; i < value.length; i++) {
        if (value[i].selected == true) {
          valueSelected = value[i].value;
          break;
        }
      }
    }
    if (operator.length > 0) {
      operatorSelected = operator[0].value;
      for (let i = 0; i < operator.length; i++) {
        if (operator[i].selected == true) {
          operatorSelected = operator[i].value;
          break;
        }
      }
    }
    return {
      status: true,
      source: sourceSelected,
      value: valueSelected,
      operator: operatorSelected
    };
  }

  /**
   * Return rendered form elements changed value
   * @param {Array} elements Single element if select and multiple elements if radio button
   * @return {String} value selected in element
   */
  getConditionChangedValue(elements) {
    const elementType = elementTagType(elements[0]);
    switch (elementType.tag) {
      case 'SELECT':
        return elements[0].value;
      case 'INPUT':
        if (elementType.type == 'radio') {
          for (let i = 0; i < elements.length; i++) {
            if (elements[i].checked) {
              return elements[i].value;
            }
          }
          return null;
        }
    }
    return null;
  }

  /**
   * Applying conditions on elements
   * @param {DOM} element
   * @param {string} action
   */
  applyConditionsOnElemets(element, action = 'enable') {
    if (element.hasChildNodes()) {
      for (let i = 0; i < element.children.length; i++) {
        this.applyConditionsOnElemets(element.children[i], action);
      }
    } else {
      if (action == 'disable') {
        element.setAttribute('disabled', true);
      } else {
        element.removeAttribute('disabled');
      }
    }
  }

  /**
   * Execute the conditions
   * @param {Array} elements
   * @param {DOM} container on which conditions are applied
   */
  executeCondition(elements, container) {
    const _this = this;
    let result = null;
    let tempResult;
    let element;
    let i;
    let value;
    if (elements.length > 0) {
      element = elements[0];
      value = _this.getConditionChangedValue(element.source);
      result = value == element.value;
    }
    for (i = 1; i < elements.length - 1; i++) {
      element = elements[i];
      value = _this.getConditionChangedValue(element.source);
      tempResult = value == element.value;
      switch (elements[i - 1].operator) {
        case 'AND':
          result = result && tempResult;
          break;
        case 'OR':
          result = result || tempResult;
          break;
      }
    }
    if (i < elements.length) {
      element = elements[i];
      value = _this.getConditionChangedValue(element.source);
      tempResult = value == element.value;
      switch (elements[i - 1].operator) {
        case 'AND':
          result = result && tempResult;
          break;
        case 'OR':
          result = result || tempResult;
          break;
      }
    }
    if (result == true) {
      container.classList.remove('d-none');
      this.applyConditionsOnElemets(container, 'enable');
    } else {
      container.classList.add('d-none');
      this.applyConditionsOnElemets(container, 'disable');
    }
  }

  /**
   * Processing each condition and adding change event to element
   * @param {Array} conditions array containing all condition of row to apply
   * @param {DOM} container on which conditions are applied
   */
  processEachCondition(conditions, container) {
    const _this = this;
    const elements = [];
    let condition;
    let element;
    let elementType;
    for (let i = 0; i < conditions.length; i++) {
      condition = conditions[i];
      element = _this.getElementFromCondition(condition);
      if (element.status == true) {
        elements.push(element);
      }
    }
    if (elements.length != 0) {
      container.classList.add('d-none');
    }
    for (let i = 0; i < elements.length; i++) {
      element = elements[i];
      elementType = elementTagType(element.source[0]);
      switch (elementType.tag) {
        case 'SELECT':
          element.source[0].addEventListener('change', function(event) {
            _this.executeCondition(elements, container);
          });
          _this.executeCondition(elements, container);
          break;
        case 'INPUT':
          if (elementType.type == 'radio') {
            for (let i = 0; i < element.source.length; i++) {
              element.source[i].addEventListener('click', function(event) {
                _this.executeCondition(elements, container);
              });
              _this.executeCondition(elements, container);
            }
          }
      }
    }
  }

  /**
   * Processing condition and applying action to element
   * @param {Map} rows map of rows
   */
  applyConditions(rows) {
    const _this = this;
    rows.forEach(function(row) {
      const id = row.id;
      if (row.conditions.length > 0) {
        const DOMrow = _this.container.querySelector('[id="' + id + '"]');
        if (DOMrow) {
          _this.processEachCondition(row.conditions, DOMrow);
        }
      }
    });
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
      'color': {
        title: getString('textcolor'),
        id: 'color',
        type: 'color',
        value: '#000000'
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
   * @param {Boolean} fullpage Is for opened in full page or embedded
   */
  manageFormWidth() {
    const formSettings = this.getFormSettings();
    const maxColumns = this.getMaxColumnCount();
    const toggleClass = status => {
      if (status == true) {
        this.renderTarget.classList.add('edwiser-inline-form');
        return;
      }
      this.renderTarget.classList.remove('edwiser-inline-form');
    };
    if (this.isFullPage() && formSettings.form.responsive.value == false) {
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
   * Check if form is rendered on fullpage
   * @return {Boolean} True if fullpage
   */
  isFullPage() {
    if (this.fullpage == undefined) {
      this.fullpage = document.querySelector('body').classList.contains('edwiserform-fullpage');
    }
    return this.fullpage;
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
    let width = formSettings.form.width ? formSettings.form.width.value : '100';
    let padding = formSettings.form.padding ? formSettings.form.padding.value : '25';
    const margin = width == 100 ? '0 auto' : '5% auto';
    if (!this.isFullPage()) {
      width = '100';
      padding = 5;
    }
    let styles = formSettings.form.style ? formSettings.form.style.value : '';
    // Adding form class in renderTarget to apply settings
    if (className != '') {
      renderTarget.classList.add(className);
    }
    const settings = {
      color: color,
      'background-color': backgroundColor,
      margin: margin,
      width: width + '%',
      padding: padding + 'px',
    };
    if (width == 100) {
      settings['box-shadow'] = 'none';
    }
    if (this.isFullPage()) {
      settings['z-index'] = 1;
    }
    styles = this.mergeStyles(settings, styles);
    renderTarget.setAttribute('style', styles);
    this.manageFormWidth();
  }

  /**
   * Processing form settings
   * @param {DOM} renderTarget
   */
  processPageSettings(renderTarget) {
    if (!this.isFullPage()) {
      return;
    }
    const formSettings = this.getFormSettings();
    // Getting form setting like classname, style
    const className = formSettings.page.class ? formSettings.page.class.value : '';
    let styles = formSettings.page.style ? formSettings.page.style.value : '';
    const backgroundopacity = formSettings.page['background-opacity'] ? formSettings.page['background-opacity'].value : '0';
    const wrap = renderTarget.closest('.edwiserform-wrap-container');
    const container = wrap.parentElement;
    wrap.style.background = 'rgba(0, 0, 0, ' + backgroundopacity + ')';
    wrap.style.margin = '0';
    wrap.style.padding = '0';
    const settings = {
      margin: '-1px 0 0 0',
      padding: '0'
    };
    styles = this.mergeStyles(settings, styles);
    // Adding page class in body element
    if (className != '') {
      container.classList.add(className);
    }
    // Applying custom style to body element
    if (styles != '') {
      container.setAttribute('style', styles);
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
      content: content
    };
    renderTarget.appendChild(this.create(config));
    if (stageNavigation !== null) {
      renderTarget.append(this.create(stageNavigation.navigation));
      renderTarget.prepend(this.create(stageNavigation.steps));
    } else {
      const navigation = {
        tag: 'div',
        attrs: {
          className: ['form-submit', this.getSubmitButtonPosition()],
        },
        content: [dom.getFormSubmitButton()]
      };
      renderTarget.append(this.create(navigation));
    }
    if (!this.isFullPage()) {
      renderTarget.append(this.create({
        tag: 'div',
        className: 'view-fullpage-label',
        content: getString('fullpage-link-message')
      }));
    }
    dom.applyConditions(formData.rows);
    dom.processFormSettings(renderTarget);
    dom.processPageSettings(renderTarget);
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
   * @param {Object} modal object
   */
  addModal(modal) {
    modal = dom.create(modal);
    document.querySelector('body').appendChild(modal);
    setTimeout(function() {
      modal.classList.add('show');
      modal.focus();
    }, 150);
  }

  /**
   * Removing modal
   * @param {String} id of modal element
   * @param {function} keyup function
   */
  removeModal(id, keyup) {
    const modal = document.getElementById(id);
    modal.classList.remove('show');
    setTimeout(function() {
      if (keyup) {
        document.removeEventListener('keyup', keyup);
      }
      modal.remove();
    }, 150);
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
    const header = _this.modalHeader(id, getString(type), type, keyup);
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
          className: 'btn btn-primary',
          type: 'button'
        },
        content: 'Ok',
        action: {
          click: evt => {
            _this.removeModal(id, keyup);
            if (action) {
              action();
            }
          },
          keyup: keyup
        }
      }]
    };
    const dialog = {
      tag: 'div',
      attrs: {
        className: 'efb-modal-dialog',
        role: 'document'
      },
      content: [header, body, footer]
    };
    const modal = {
      tag: 'div',
      id: id,
      attrs: {
        className: 'efb-modal fade',
        role: 'dialog',
        'aria-hidden': true
      },
      content: dialog,
      action: {
        keyup: keyup
      }
    };
    _this.addModal(modal);
  }
}

const dom = new DOM();

export default dom;

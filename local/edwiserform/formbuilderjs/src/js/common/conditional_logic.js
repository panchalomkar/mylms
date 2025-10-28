'use strict';
import dom from './dom';
import {data, formData} from '../common/data';
import animate from '../common/animation';
import {closest, closestFtype, elementTagType, getString} from './utils';

/**
 * Conditional logic handler
 */
class Conditions {
  /**
   * Return object container DOM element, its required value, and operator
   * @param {Object} condition containing source, value and operator element object
   * @return {Object} element
   */
  getElementFromCondition(condition) {
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
      sourceSelected = dom.renderTarget.querySelectorAll('[id*="' + sourceSelected + '"]');
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
   * Execute the conditions
   * @param {Array} elements
   * @param {DOM} container on which conditions are applied
   */
  executeCondition(elements, container) {
    let result = null;
    let tempResult;
    let element;
    let i;
    let value;
    if (elements.length > 0) {
      element = elements[0];
      value = cons.getConditionChangedValue(element.source);
      result = value == element.value;
    }
    for (i = 1; i < elements.length - 1; i++) {
      element = elements[i];
      value = cons.getConditionChangedValue(element.source);
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
      value = cons.getConditionChangedValue(element.source);
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
    } else {
      container.classList.add('d-none');
    }
  }

  /**
   * Processing each condition and adding change event to element
   * @param {Array} conditions array containing all condition of row to apply
   * @param {DOM} container on which conditions are applied
   */
  processEachCondition(conditions, container) {
    const elements = [];
    let condition;
    let element;
    let elementType;
    for (let i = 0; i < conditions.length; i++) {
      condition = conditions[i];
      element = cons.getElementFromCondition(condition);
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
            cons.executeCondition(elements, container);
          });
          cons.executeCondition(elements, container);
          break;
        case 'INPUT':
          if (elementType.type == 'radio') {
            for (let i = 0; i < element.source.length; i++) {
              element.source[i].addEventListener('click', function(event) {
                cons.executeCondition(elements, container);
              });
              cons.executeCondition(elements, container);
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
    rows.forEach(function(row) {
      const id = row.id;
      if (row.conditions.length > 0) {
        const DOMrow = dom.renderTarget.querySelector('[id="' + id + '"]');
        cons.processEachCondition(row.conditions, DOMrow);
      }
    });
  }

  /**
   * Return condition position, input position from conditions container and row container
   * @param {Event} event
   * @param {String} type of element being changed add|delete|source|value|operator
   * @return {Object} conditon including condition position, input position and row container
   */
  conditionPositions(event, type) {
    let conditions;
    let condition;
    const panel = closest(event.target, 'panel-conditions');
    conditions = panel.getElementsByClassName('conditions')[0];
    conditions = conditions.childNodes;
    switch (type) {
      case 'add':
        condition = conditions[conditions.length - 1];
        break;
      case 'source':
      case 'value':
      case 'operator':
      case 'delete':
        condition = closest(event.target, 'condition');
    }
    return {
      row: closestFtype(event.target),
      conditionIndex: Array.prototype.indexOf.call(conditions, condition),
      condition: condition.childNodes[0],
      type: type
    };
  }

  /**
   * Adding dropdown change event to source list
   * @param {Event} event change event
   */
  conditionSourceChange(event) {
    const targetID = event.target.value;
    let select = {
      tag: 'select',
      attrs: {
        type: 'select'
      },
      options: []
    };
    if (targetID != 'choose') {
      select.options = formData.fields.get(targetID).options;
    }
    const value = event.target.nextSibling;
    value.innerHTML = '';
    if (select.options.length > 0) {
      select = dom.create(select);
      const options = select.childNodes;
      while (options.length) {
        value.appendChild(options[0]);
      }
      value.firstChild.selected = true;
    }
  }

  /**
   * Remove condition from condition container
   * @return {Object} Return remove condition button
   */
  removeCondition() {
    return {
      tag: 'button',
      attrs: {
        className: 'condition-delete condition-control btn btn-danger',
        title: getString('remove-condition')
      },
      content: dom.icon('remove'),
      action: {
        click: event => {
          event.preventDefault();
          const element = event.target.parentElement.parentElement;
          const conditions = closest(event.target, 'conditions');
          animate.slideUp(element, 666, elem => {
            const condition = cons.conditionPositions(event, 'delete');
            dom.remove(elem);
            cons.resizeConditions(conditions);
            data.saveConditionalLogics(condition, 'delete');
          });
        }
      }
    };
  }

  /**
  * Wrap condition into container
  * @param {Object} input wrapped source and value
  * @return {Object} wrapped contianer
  */
  wrapCondition(input) {
    const source = input.content[0];
    const value = input.content[1];
    const operator = input.content[2];
    source.action = {
      change: event => {
        cons.conditionSourceChange(event);
        let condition = cons.conditionPositions(event, 'source');
        data.saveConditionalLogics(condition);
        condition = cons.conditionPositions(event, 'value');
        data.saveConditionalLogics(condition);
      }
    };
    value.action = {
      change: event => {
        const condition = cons.conditionPositions(event, 'value');
        data.saveConditionalLogics(condition);
      }
    };
    operator.action = {
      change: event => {
        const condition = cons.conditionPositions(event, 'operator');
        data.saveConditionalLogics(condition);
      }
    };
    input.content[0] = source;
    input.content[1] = value;
    input.content[2] = operator;
    const conditionControls = {
      tag: 'div',
      className: 'condition-controls',
      content: [cons.removeCondition]
    };
    const condition = {
      tag: 'li',
      className: 'condition',
      content: [input, conditionControls]
    };
    return condition;
  }

  /**
   * Filter fields and choose only select and radio fields
   * @param {Array} fields
   * @return {Array} fields
   */
  filterFieldsSelectRadio(fields) {
    const filter = [];
    formData.fields.forEach(function(field, index) {
      if (fields.includes(field.id) && (field.tag == 'select' || (field.tag == 'input' && field.attrs.type == 'radio'))) {
        filter.push(field);
      }
    });
    return filter;
  }

  /**
   * Filter all fields and only those which can be added in current rows conditions
   * @param {String} currentRow id
   * @return {Object} fields
   */
  getConditionValidFields(currentRow) {
    let fields = [];
    let columns = [];
    formData.rows.forEach(function(row, index) {
      if (row.id != currentRow) {
        columns = columns.concat(row.columns);
      }
    });
    if (columns.length) {
      formData.columns.forEach(function(column, index) {
        if (columns.includes(column.id)) {
          fields = fields.concat(column.fields);
        }
      });
    }
    fields = this.filterFieldsSelectRadio(fields);
    return fields;
  }

  /**
   * Code to add logics
   * @param {String} row id
   * @param {Object} container element where conditions will be displayed
   */
  addCondition(row, container) {
    const fields = this.getConditionValidFields(row);
    const options = [{
      label: getString('condition-choose-source'),
      value: 'choose',
      selected: true
    }];
    for (let i = 0; i < fields.length; i++) {
      options.push({
        label: fields[i].config.label,
        value: fields[i].id
      });
    }
    const source = {
      tag: 'select',
      className: 'condition-source condition-input',
      options: options
    };
    const value = {
      tag: 'select',
      className: 'condition-value condition-input'
    };
    const operator = {
      tag: 'select',
      className: 'condition-operator condition-input',
      options: [{
        label: 'AND',
        value: 'AND',
        selected: true
      }, {
        label: 'OR',
        value: 'OR'
      }],
      action: {
        change: event => {
          const condition = cons.conditionPositions(event, 'operator');
          data.saveConditionalLogics(condition);
        }
      }
    };
    const input = {
      tag: 'div',
      className: 'condition-inputs',
      content: [source, value, operator]
    };
    const wrapped = this.wrapCondition(input);
    container.append(dom.create(wrapped));
  }

  /**
   * Resize conditional logic panel when user add or remove condition
   * @param {DOM} element targeting element of DOM
   */
  resizeConditions(element) {
    const conditionPanel = closest(element, 'panel-conditions');
    const panels = closest(element, 'panels');
    const height = animate.getStyle(conditionPanel, 'height');
    panels.style.height = height;
  }

  /**
   * Process conditions from rowData
   * @param {Map} rowData
   * @return {Map} processed rowData
   */
  processConditions(rowData) {
    const conditions = rowData.conditions;
    let condition;
    const processedConditions = [];
    for (let i = 0; i < conditions.length; i++) {
      condition = conditions[i];
      condition = this.wrapCondition(condition);
      processedConditions.push(condition);
    }
    return processedConditions;
  }

  /**
   * Created conditions tab
   * @param {Map} rowData of current
   * @return {Object} conditions
   */
  conditions(rowData) {
    const conditions = [
      {
        tag: 'div',
        className: 'f-panel-wrap',
        content: [
          {
            tag: 'ul',
            className: 'conditions',
            content: [cons.processConditions(rowData)]
          }
        ]
      }, {
        tag: 'div',
        className: 'panel-action-buttons',
        content: [
          {
            tag: 'button',
            className: 'add-condition btn btn-primary',
            content: getString('addcondition'),
            action: {
              click: event => {
                event.preventDefault();
                const container = event.target.parentElement.parentElement.getElementsByClassName('conditions')[0];
                const row = closestFtype(event.target);
                cons.addCondition(row.id, container);
                cons.resizeConditions(event.target);
                const condition = cons.conditionPositions(event, 'add');
                data.saveConditionalLogics(condition);
              }
            }
          }
        ]
      }
    ];
    return conditions;
  }
}

const cons = new Conditions();

export default cons;

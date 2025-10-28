import Sortable from 'sortablejs';
import dom from '../common/dom';
import h from '../common/helpers';
import {data, formData, registeredFields as rFields} from '../common/data';
import {uuid, getString, closestFtype} from '../common/utils';
import Panels from './panels';
import cons from '../common/conditional_logic';

/**
 * Editor Row
 */
export default class Row {
  /**
   * Set default and generate dom for row in editor
   * @param  {String} dataID
   * @return {Object}
   */
  constructor(dataID) {
    const _this = this;
    let row;

    const rowID = _this.rowID = dataID || uuid();

    const defaults = {
      columns: [],
      id: _this.rowID,
      config: {
        fieldset: false, // Wrap contents of row in fieldset
        legend: '', // Legend for fieldset
        inputGroup: false // Is repeatable input-group?
      },
      attrs: {
        className: 'f-row'
      },
      conditions: []
    };

    const rowData = formData.rows.get(rowID);

    formData.rows.set(rowID, h.merge(defaults, rowData));
    const panel = new Panels({
      panels: _this.editWindow.content,
      type: rowID
    });
    panel.tag = 'div';
    panel.className = 'row-edit panels-wrap tabbed-panels';
    panel.action = {
      click: event => {
        const row = closestFtype(event.target);
        const firstLabel = row.getElementsByClassName('row-edit')[0].getElementsByClassName('panel-labels')[0].getElementsByTagName('h5')[0];
        const columns = row.getElementsByClassName('stage-columns');
        if (row.classList.contains('editing-row') && firstLabel.classList.contains('active-tab')) {
          for (let i = 0; i < columns.length; i++) {
            columns[i].classList.remove('hide-column');
          }
        } else {
          for (let i = 0; i < columns.length; i++) {
            columns[i].classList.add('hide-column');
          }
        }
      }
    };
    row = {
      tag: 'li',
      className: 'stage-rows empty-rows',
      dataset: {
        hoverTag: getString('row'),
        editingHoverTag: getString('editing.row')
      },
      id: rowID,
      content: [dom.actionButtons(rowID, 'row'), panel],
      fType: 'rows'
    };

    row = dom.create(row);

    const sortable = Sortable.create(row, {
      animation: 150,
      forceFallback: true,
      fallbackClass: 'column-moving',
      group: {name: 'rows', pull: true, put: ['rows', 'controls', 'columns']},
      sort: true,
      onRemove: _this.onRemove,
      onEnd: _this.onEnd,
      onAdd: _this.onAdd,
      onSort: _this.onSort,
      draggable: '.stage-columns',
      handle: '.item-handle',
      filter: '.resize-x-handle'
    });

    dom.rows.set(rowID, {row, sortable});

    return row;
  }

  /**
   * [editWindow description]
   * @return {[type]} [description]
   */
  get editWindow() {
    const _this = this;
    const rowData = formData.rows.get(_this.rowID);

    const editWindow = {
      tag: 'div',
      className: 'panels row-edit group-config'
    };
    const fieldsetLabel = {
      tag: 'label',
      content: getString('row.settings.fieldsetWrap')
    };
    const fieldsetInput = {
      tag: 'input',
      id: _this.rowID + '-fieldset',
      attrs: {
        type: 'checkbox',
        checked: rowData.config.fieldset,
        ariaLabel: getString('row.settings.fieldsetWrap.aria')
      },
      action: {
        click: e => {
          rowData.config.fieldset = e.target.checked;
          data.save();
        }
      }
    };

    // Let inputGroupInput = {
    //   tag: 'input',
    //   id: _this.rowID + '-inputGroup',
    //   attrs: {
    //     type: 'checkbox',
    //     checked: rowData.config.inputGroup,
    //     ariaLabel: getString('row.settings.inputGroup.aria')
    //   },
    //   action: {
    //     click: e => {
    //       rowData.config.inputGroup = e.target.checked;
    //       data.save();
    //     }
    //   },
    //   config: {
    //     label: getString('row.makeInputGroup'),
    //     description: getString('row.makeInputGroupDesc')
    //   }
    // };

    const inputAddon = {
      tag: 'span',
      className: 'input-group-addon',
      content: fieldsetInput
    };
    const legendInput = {
      tag: 'input',
      attrs: {
        type: 'text',
        ariaLabel: getString('legendfieldset'),
        value: rowData.config.legend,
        placeholder: 'Legend'
      },
      action: {
        input: e => {
          rowData.config.legend = e.target.value;
          data.save();
        }
      },
      className: ''
    };
    const fieldsetInputGroup = {
      tag: 'div',
      className: 'input-group',
      content: [inputAddon, legendInput]
    };

    let fieldSetControls = [
      fieldsetLabel,
      fieldsetInputGroup
    ];
    fieldSetControls = dom.formGroup(fieldSetControls);
    const columnSettingsLabel = Object.assign({}, fieldsetLabel, {
      content: getString('columnwidths')
    });
    const columnSettingsPresetLabel = Object.assign({}, fieldsetLabel, {
      content: 'Layout Preset', className: 'col-sm-4 form-control-label'
    });
    const columnSettingsPresetSelect = {
      tag: 'div',
      className: 'col-sm-8',
      content: dom.columnPresetControl(_this.rowID)
    };
    const formGroupContent = [
      columnSettingsPresetLabel,
      columnSettingsPresetSelect
    ];
    const columnSettingsPreset = dom.formGroup(formGroupContent, 'row');
    const rowConfigPanel = {
      tag: 'div',
      className: 'f-panel row-config',
      config: {
        label: getString('containersettings')
      },
      content: [
        // InputGroupInput,
        // dom.create('hr'),
        fieldSetControls,
        dom.create('hr'),
        columnSettingsLabel,
        columnSettingsPreset
      ]
    };
    const conditionPanel = {
      tag: 'div',
      config: {
        label: getString('conditions')
      },
      className: 'f-panel panel-conditions',
      content: [
        {
          tag: 'div',
          className: 'condition-recaptcha-warning',
          content: {
            tag: 'center',
            content: {
              tag: 'h4',
              attrs: {
                style: 'margin: 1rem 0rem'
              },
              content: getString('recaptcha-row')
            }
          }
        },
        cons.conditions(rowData)
      ]
    };
    editWindow.content = [rowConfigPanel, conditionPanel];
    return editWindow;
  }

  /**
   * Update column order and save
   * @param  {Object} evt
   */
  onSort(evt) {
    data.saveColumnOrder(evt.target);
    data.save();
  }

  /**
   * Handler for removing content from a row
   * @param  {Object} evt
   */
  onRemove(evt) {
    dom.columnWidths(evt.from);
    data.saveColumnOrder(evt.target);
    dom.emptyClass(evt.from);
  }

  /**
   * Handler for removing content from a row
   * @param  {Object} evt
   */
  onEnd(evt) {
    if (evt.from.classList.contains('empty-rows')) {
      dom.removeEmpty(evt.from);
    }

    data.save();
  }

  /**
   * Handler for adding content to a row
   * @param  {Object} evt
   */
  onAdd(evt) {
    const {from, item, to} = evt;
    const fromRow = from.fType === 'rows';
    const fromColumn = from.fType === 'columns';
    const fromControls = from.fType === 'controlGroup';
    let column;

    if (fromRow) {
      column = item;
    } else if (fromControls) {
      if (dom.license != 'available') {
        let text = evt.item.firstChild.lastChild.wholeText;
        text = getString('dragndrop', text);
        dom.proWarning(text);
      } else {
        const meta = h.get(rFields[item.id], 'meta');
        if (meta.group !== 'layout') {
          column = dom.addColumn(to.id);
          dom.addField(column.id, item.id);
        } else if (meta.id === 'layout-column') {
          dom.addColumn(to.id);
        }
      }
      dom.remove(item);
    } else if (fromColumn) {
      column = dom.addColumn(to.id);
      dom.addField(column.id, item.id);
      item.parentElement.removeChild(item);
    }


    data.saveColumnOrder(to);

    dom.columnWidths(to);
    dom.emptyClass(to);
    data.save();
  }
}

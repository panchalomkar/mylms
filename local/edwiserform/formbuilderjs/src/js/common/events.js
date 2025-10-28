'use strict';
import dom from './dom';
import {getString, assign} from './utils';
// Default options

const defaults = {
  confirmClearAll: function confirmClearAll(evt) {
    dom.multiActions('warning', getString('attention'), evt.confirmationMessage, [{
      title: getString('proceed'),
      type: 'primary',
      action: function action() {
        evt.clearAllAction(evt);
      }
    }, {
      title: getString('cancel'),
      type: 'default'
    }]);
  },
  confirmReset: function confirmReset(evt) {
    dom.multiActions('warning', getString('attention'), evt.confirmationMessage, [{
      title: getString('proceed'),
      type: 'primary',
      action: function action() {
        evt.resetAction(evt);
      }
    }, {
      title: getString('cancel'),
      type: 'default'
    }]);
  },
  confirmClearStorage: function confirmClearStorage(evt) {
    dom.multiActions('danger', getString('danger'), evt.confirmationMessage, [{
      title: getString('clearstorageautomatic'),
      type: 'primary',
      action: function action() {
        evt.clearStorageAction(evt);
      }
    }, {
      title: getString('clearstoragemanually'),
      type: 'default',
      action: function action() {
        evt.clearStorageManualAction(evt);
      }
    }]);
  }
};

/**
 * Events class is used to register events and throttle their callbacks
 */

const events = {
  formeoSaved: null,
  formeoUpdated: null,
  init: null,
  opts: null
};
events.formeoSaved = new CustomEvent('formeoSaved', {});
events.formeoUpdated = new CustomEvent('formeoUpdated', {});
events.init = function init(options) {
  events.opts = assign({}, defaults, options);
  return events;
};

document.addEventListener('confirmClearAll', function(evt) {
  const evtData = {
    timeStamp: evt.timeStamp,
    type: evt.type,
    rowCount: evt.detail.rows.length,
    confirmationMessage: evt.detail.confirmationMessage,
    clearAllAction: evt.detail.clearAllAction,
    btnCoords: evt.detail.btnCoords
  };

  events.opts.confirmClearAll(evtData);
});

document.addEventListener('confirmClearStorage', function(evt) {
  const evtData = {
    timeStamp: evt.timeStamp,
    type: evt.type,
    confirmationMessage: evt.detail.confirmationMessage,
    clearStorageAction: evt.detail.clearStorageAction,
    clearStorageManualAction: evt.detail.clearStorageManualAction,
  };

  events.opts.confirmClearStorage(evtData);
});

document.addEventListener('confirmReset', function(evt) {
  const evtData = {
    timeStamp: evt.timeStamp,
    type: evt.type,
    rowCount: evt.detail.rows.length,
    confirmationMessage: evt.detail.confirmationMessage,
    resetAction: evt.detail.resetAction,
    btnCoords: evt.detail.btnCoords
  };

  events.opts.confirmReset(evtData);
});

export default events;

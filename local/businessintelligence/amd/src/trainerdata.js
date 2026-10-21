// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Trainer & business data page: inline form validation, CSV drop zone and delete confirmations.
 * The server validates everything again.
 *
 * @module     local_businessintelligence/trainerdata
 * @copyright  2026 AkosMD Tech
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str'], function(Str) {
    'use strict';

    var str = {};
    var KEYS = ['td_err_required', 'td_err_email', 'td_err_number', 'td_err_range', 'td_err_phone', 'td_err_filetype',
        'td_err_filesize', 'td_dropfile', 'td_err_rating', 'td_err_time'];

    /**
     * Message for an invalid control.
     *
     * @param {HTMLElement} input
     * @return {String}
     */
    function message(input) {
        var v = input.validity;
        if (v.valueMissing) {
            return str.td_err_required;
        }
        if (v.typeMismatch && input.type === 'email') {
            return str.td_err_email;
        }
        if (v.badInput || (v.typeMismatch && input.type === 'number')) {
            return str.td_err_number;
        }
        if (v.rangeUnderflow || v.rangeOverflow || v.stepMismatch) {
            if (input.name === 'rating') {
                return str.td_err_rating;
            }
            return str.td_err_range.replace('{$a->min}', input.min || '0').replace('{$a->max}', input.max || '');
        }
        if (v.patternMismatch) {
            return str.td_err_phone;
        }
        return input.validationMessage;
    }

    /**
     * Show / clear the error of a field.
     *
     * @param {HTMLElement} input
     * @return {Boolean} valid
     */
    function check(input) {
        var field = input.closest('.lbi-td-field');
        var msg = field ? field.querySelector('.lbi-td-msg') : null;
        if (!field || !msg) {
            return true;
        }
        if (msg._help === undefined) {
            msg._help = field.classList.contains('has-error') ? '' : msg.textContent;
        }
        var ok = input.checkValidity();
        field.classList.toggle('has-error', !ok);
        input.setAttribute('aria-invalid', ok ? 'false' : 'true');
        msg.innerHTML = '';
        if (ok) {
            msg.textContent = msg._help;
        } else {
            var e = document.createElement('span');
            e.className = 'lbi-td-err';
            e.textContent = message(input);
            msg.appendChild(e);
        }
        return ok;
    }

    /**
     * Wire a form.
     *
     * @param {HTMLFormElement} form
     */
    function bindForm(form) {
        var inputs = form.querySelectorAll('.lbi-td-input');
        inputs.forEach(function(i) {
            i.addEventListener('blur', function() {
                check(i);
            });
            i.addEventListener('input', function() {
                if (i.closest('.lbi-td-field').classList.contains('has-error')) {
                    check(i);
                }
            });
        });
        form.addEventListener('submit', function(e) {
            var first = null;
            inputs.forEach(function(i) {
                if (!check(i) && !first) {
                    first = i;
                }
            });
            if (first) {
                e.preventDefault();
                first.focus();
            }
        });
    }

    /**
     * Wire the CSV drop zone.
     *
     * @param {HTMLFormElement} form
     */
    function bindUpload(form) {
        var drop = form.querySelector('[data-region="drop"]');
        var file = form.querySelector('[data-region="file"]');
        var name = form.querySelector('[data-region="dropname"]');
        var msg = form.querySelector('[data-region="filemsg"]');
        var validate = function() {
            msg.textContent = '';
            drop.classList.remove('has-error', 'has-file');
            var f = file.files && file.files[0];
            if (!f) {
                name.textContent = str.td_dropfile;
                return false;
            }
            name.textContent = f.name + ' · ' + Math.max(1, Math.round(f.size / 1024)) + ' KB';
            if (!/\.csv$/i.test(f.name)) {
                msg.textContent = str.td_err_filetype;
            } else if (f.size > 5 * 1024 * 1024) {
                msg.textContent = str.td_err_filesize;
            } else {
                drop.classList.add('has-file');
                return true;
            }
            drop.classList.add('has-error');
            return false;
        };
        file.addEventListener('change', validate);
        ['dragenter', 'dragover'].forEach(function(ev) {
            drop.addEventListener(ev, function(e) {
                e.preventDefault();
                drop.classList.add('is-drag');
            });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            drop.addEventListener(ev, function() {
                drop.classList.remove('is-drag');
            });
        });
        drop.addEventListener('drop', function(e) {
            e.preventDefault();
            if (e.dataTransfer && e.dataTransfer.files.length) {
                file.files = e.dataTransfer.files;
                validate();
            }
        });
        form.addEventListener('submit', function(e) {
            if (!validate()) {
                e.preventDefault();
                if (!msg.textContent) {
                    msg.textContent = str.td_err_required;
                    drop.classList.add('has-error');
                }
            }
        });
    }

    return {
        /**
         * Initialise.
         */
        init: function() {
            var root = document.querySelector('[data-region="lbi-trainerdata"]');
            if (!root) {
                return;
            }
            Str.get_strings(KEYS.map(function(k) {
                return {key: k, component: 'local_businessintelligence'};
            })).then(function(s) {
                KEYS.forEach(function(k, i) {
                    str[k] = s[i];
                });
                root.querySelectorAll('[data-region="tdform"]').forEach(bindForm);
                root.querySelectorAll('[data-region="tdupload"]').forEach(bindUpload);
                return null;
            }).catch(function() {
                return null;
            });
            root.querySelectorAll('form[data-confirm]').forEach(function(f) {
                f.addEventListener('submit', function(e) {
                    // eslint-disable-next-line no-alert
                    if (!window.confirm(f.getAttribute('data-confirm'))) {
                        e.preventDefault();
                    }
                });
            });
        }
    };
});

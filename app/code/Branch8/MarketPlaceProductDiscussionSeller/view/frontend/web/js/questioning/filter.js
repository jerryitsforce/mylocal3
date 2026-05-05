/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'underscore',
    'mage/translate',
    'jquery-ui-modules/widget',
    'Magento_Ui/js/modal/modal',
    'mage/calendar'
], function ($, _, $t) {
    'use strict';

    $.widget('mage.threadFilter', $.mage.modal, {
        options: {
            modalClass: 'prompt',
            promptField: '[data-role="promptField"]',
            attributesForm: {},
            attributesField: {},
            validation: false,
            validationRules: [],
            keyEventHandlers: {

                /**
                 * Enter key press handler,
                 * submit result and close modal window
                 * @param {Object} event - event
                 */
                enterKey: function (event) {
                    if (this.options.isOpen && this.modal.find(document.activeElement).length ||
                        this.options.isOpen && this.modal[0] === document.activeElement) {
                        this.closeModal(true);
                        event.preventDefault();
                    }
                },

                /**
                 * Tab key press handler,
                 * set focus to elements
                 */
                tabKey: function () {
                    if (document.activeElement === this.modal[0]) {
                        this._setFocus('start');
                    }
                },

                /**
                 * Escape key press handler,
                 * cancel and close modal window
                 * @param {Object} event - event
                 */
                escapeKey: function (event) {
                    if (this.options.isOpen && this.modal.find(document.activeElement).length ||
                        this.options.isOpen && this.modal[0] === document.activeElement) {
                        this.closeModal();
                        event.preventDefault();
                    }
                }
            },
            actions: {

                /**
                 * Callback always - called on all actions.
                 */
                always: function () {
                },

                /**
                 * Callback confirm.
                 */
                confirm: function () {
                },

                /**
                 * Callback cancel.
                 */
                cancel: function () {
                }
            },
            buttons: [{
                text: $.mage.__('Clear Filter'),
                class: 'action-secondary action-dismiss',

                /**
                 * Click handler.
                 */
                click: function () {
                    this.clearFilter();
                }
            }, {
                text: $.mage.__('Apply Filter'),
                class: 'action-primary action-accept',

                /**
                 * Click handler.
                 */
                click: function () {
                    this.applyFilter(true);
                }
            }]
        },

        /**
         * Create widget.
         */
        _create: function () {
            this.options.focus = this.options.promptField;
            this.options.validation = this.options.validation && this.options.validationRules.length;
            this.options.outerClickHandler = this.options.outerClickHandler || _.bind(this.closeModal, this, false);
            $(document).on(this.options.triggerButton, 'click', this.openModal.bind(this));
            this._super();
            this.modal.find(this.options.modalCloseBtn).off().on('click', _.bind(this.closeModal, this, false));
            if (this.options.validation) {
                this.setValidationClasses();
            }
        },
        /**
         * Clear all filter fields
         */
        clearFilter: function () {
            this.modal.find('input, select').val('');
            $('.date-range-error').remove();
            $('.mage-error').removeClass('mage-error');
        },
        /**
         * Apply filters
         * @param {Boolean} result
         */
        applyFilter: function (result) {
            if (this.validate()) {
                this.closeModal(result);
            }
        },

        /**
         * Remove widget
         */
        _remove: function () {
            this.modal.remove();
        },

        /**
         * Validate prompt field
         */
        validate: function () {
            return this.validateDateRange($('#prompt-field-create-date-start'), $('#prompt-field-create-date-end'))
                && this.validateDateRange($('#prompt-field-reply-date-start'), $('#prompt-field-reply-end-start'));
        },
        /**
         *
         * @param start
         * @param end
         * @returns {boolean}
         */
        validateDateRange: function (start, end) {
            let startTime = start.val(),
                endTime = end.val(),
                endField = end;
            $('.date-range-error').remove(); // remove old error
            if (startTime && endTime && new Date(startTime) > new Date(endTime)) {
                endField.addClass('mage-error');
                const error = $t('End date must be greater than Start date');
                $(`<div class="mage-error date-range-error">${error}</div>`)
                    .insertAfter(endField);
                return false;
            }
            return true;
        },
        /**
         * Add validation classes to prompt field
         */
        setValidationClasses: function () {
            this.modal.find(this.options.promptField).attr('class', $.proxy(function (i, val) {
                return val + ' ' + this.options.validationRules.join(' ');
            }, this));
        },

        /**
         * Open modal window
         */
        openModal: function () {
            this._super();
        },

        /**
         * Close modal window
         */
        closeModal: function (result) {
            var value;
            if (result) {
                if (this.options.validation && !this.validate()) {
                    return false;
                }

                value = this.modal.find(this.options.promptField).val();
                this.options.actions.confirm.call(this, value);
            } else {
                this.options.actions.cancel.call(this, result);
            }
            this.options.actions.always();
            this.element.on('promptclosed', _.bind(this._remove, this));
            return this._super();
        }
    });

    return function (config) {
        return $('<div class="prompt-message"></div>').html(config.content).prompt(config);
    };
});

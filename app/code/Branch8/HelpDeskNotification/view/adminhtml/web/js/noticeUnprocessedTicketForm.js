/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery/jquery.cookie',
    'mage/translate'
], function ($) {
    'use strict';
    $.widget('mage.noticeUnprocessedTicketForm', $.mage.modal, {
        /**
         *
         * @private
         */
        _create: function () {
            this._super();
        },
        /**
         *
         * @returns {*}
         */
        closeModal: function () {
            this._super();
            if (this.options.dismissUrl) {
                $.ajax({
                    url: this.options.dismissUrl,
                    type: 'POST',
                    data: {form_key: window.FORM_KEY},
                    dataType: 'json'
                });
            }
            return this.element;
        },
    });
    return $.mage.noticeUnprocessedTicketForm;
});

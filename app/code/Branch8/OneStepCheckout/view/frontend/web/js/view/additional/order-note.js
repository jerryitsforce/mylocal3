define([
    'ko',
    'jquery',
    'Magento_Ui/js/form/element/textarea',
    'Branch8_OneStepCheckout/js/action/save-custom-fields',
    'mage/translate',
    'mage/validation'
], function (ko, $, Component, saveCustomFields, $t) {
    'use strict';

    return Component.extend({
        inputName : 'order_note',
        errorMessage: ko.observable(''),

        initialize: function() {
            this._super();
            this.observeErrorMessage();

            var checkoutAddressData = window.checkoutConfig.checkoutAddress;
            if (checkoutAddressData) {
                var parsedData = checkoutAddressData;
                if (parsedData && parsedData.orderNote) {
                    this.value(parsedData.orderNote);
                }
            }

            return this;
        },

        onUpdate: function (newValue) {
            this._super();
            const validate =  $.validator.validateSingleElement($('#'+this.uid));
            // if (validate && this.isDifferedFromDefault()) {
            //     saveCustomFields();
            // }
        },

        observeErrorMessage: function () {
            $(document).on('order-note-error', function (event, message) {
                this.errorMessage(message);
            }.bind(this));
        }
    });
});

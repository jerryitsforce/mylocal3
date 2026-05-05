define([
    'Magento_Ui/js/form/element/textarea',
    'jquery',
    'Branch8_OneStepCheckout/js/action/save-custom-fields',
    'mage/translate',
    'mage/validation'
], function (Component, $, saveCustomFields, $t) {
    'use strict';

    return Component.extend({
        inputName : 'referrer_code',

        /**
         * Invokes initialize method of parent class,
         * contains initialization logic
         */
        initialize: function () {
            this._super();

            var checkoutAddressData = window.checkoutConfig.checkoutAddress;
            if (checkoutAddressData) {
                var parsedData = checkoutAddressData;
                if (parsedData && parsedData.referrerCode) {
                    this.value(parsedData.referrerCode);
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
        }
    });
});

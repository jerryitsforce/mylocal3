define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Branch8_Checkout/js/model/advance_validate'
    ],
    function (Component, additionalValidators, advanceValidate) {
        'use strict';
        additionalValidators.registerValidator(advanceValidate);
        return Component.extend({});
    }
);
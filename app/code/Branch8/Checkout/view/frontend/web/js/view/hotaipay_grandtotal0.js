define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Branch8_Checkout/js/model/hotaipay_grandtotal0'
    ],
    function (Component, additionalValidators, advanceValidate) {
        'use strict';
        additionalValidators.registerValidator(advanceValidate);
        return Component.extend({});
    }
);
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Branch8_OneStepCheckout/js/model/payment-validators/order-note-validator'
], function (Component, additionalValidators, orderNoteValidator) {
    'use strict';

    additionalValidators.registerValidator(orderNoteValidator);

    return Component.extend({});
});

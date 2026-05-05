define([
    'ko',
    'underscore',
    'uiRegistry',
    'Magento_Checkout/js/model/quote'
], function (ko, _, registry, quote) {
    'use strict';

    return {
        isSelectedPaymentMethod: ko.observable(true),
        selectedHotaiPayCard: ko.observable(null),
    };
});

define([
    'ko',
    'jquery',
    'uiComponent',
    'Branch8_Rma/js/model/order',
    'Magento_Catalog/js/price-utils'
], function (ko, $, Component, order, priceUtils) {
    'use strict';

    var  newFormat = {
        decimalSymbol: '',
        groupLength: '',
        groupSymbol: '',
        integerRequired: '',
        pattern: '',
        precision: '',
        requiredPrecision: ''
    }

    return Component.extend({
        defaults: {
            template: 'Branch8_Rma/rma-items'
        },
        items: ko.observableArray(null),

        initialize: function () {
            var self = this;
            this._super();
            // console.log('initialize');
            order.items.subscribe(function (newValue) {
                // console.log('items updated:', newValue);
                self.items(newValue);
            });
            return this;
        }
    });
});
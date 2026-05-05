define([
    'ko',
    'underscore',
    'mage/utils/wrapper',
    'domReady!'
], function (ko, _, wrapper) {
        'use strict';
        var selectedShipping = ko.observable(null);

        return function (target) {
            var mixin = {
                selectedShipping: selectedShipping,

                setSelectedShipping: function (shipping) {
                   this.selectedShipping(shipping);
                },

                resetShippingAddress: function () {
                    this.shippingAddress(null);
                },

                resetBillingAddress: function () {
                    this.billingAddress(null);
                }
            };

            wrapper._extend(target, mixin);
            return target;
        };
    }
);

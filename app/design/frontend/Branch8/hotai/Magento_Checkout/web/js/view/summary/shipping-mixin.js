define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_SalesRule/js/view/summary/discount'
], function ($, _, Component, quote, discountView) {
    'use strict';

    var mixin = {

        /**
         *
         * @param {Column} elem
         */
        getShippingMethodTitle: function (elem) {
            var shippingMethod,
                shippingMethodTitle = '';

            if (!this.isCalculated()) {
                return '';
            }
            shippingMethod = quote.shippingMethod();

            if (!_.isArray(shippingMethod) && !_.isObject(shippingMethod)) {
                return '';
            }

            // if (typeof shippingMethod['method_title'] !== 'undefined') {
            //     shippingMethodTitle = ' - ' + shippingMethod['method_title'];
            // }
            //
            // return shippingMethodTitle ?
            //     shippingMethod['carrier_title'] + shippingMethodTitle :
            //     shippingMethod['carrier_title'];
            /**
             * Currently, carrier title and method name are the same, but this func return carrier title - method name
             * Need to return method name only because other place in BE, FE use method name(example filter listing)
             * So we use method title only to show on checkout
             */
            return shippingMethod['carrier_title'];
        }
    };

    return function (target) { // target == Result that Magento_Ui/.../columns returns.
        return target.extend(mixin); // new result that all other modules receive
    };
});
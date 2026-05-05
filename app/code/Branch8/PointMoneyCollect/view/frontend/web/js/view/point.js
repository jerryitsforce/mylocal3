define([
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/full-screen-loader',
    'jquery',
    'ko',
    'mage/storage',
    'mage/translate'
], function (Component, quote, priceUtils, getTotalsAction, fullScreenLoader, $, ko, storage, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_PointMoneyCollect/point'
        },

        pointValue: ko.observable(null),

        /**
         * Applied flag
         */
        isApplied: ko.observable(false),

        /**
         * Coupon code application procedure
         */
        apply: function() {
            if (this.validate()) {
                fullScreenLoader.startLoader();
                this.isApplied(true);
                var point = this.pointalue();
                var quoteId = quote.getQuoteId();
                var url = 'pointmoneycollect/cart/apply' + quoteId + '/' + point;
                storage.put(
                    url,
                    {},
                    false
                ).done(
                    function (response) {
                        if (response) {
                            var deferred = $.Deferred();
                            getTotalsAction([], deferred);
                            $.when(deferred).done(function() {
                                fullScreenLoader.stopLoader();
                            });
                        }
                    }
                ).fail(
                    function (response) {
                        fullScreenLoader.stopLoader();
                        alert(response.responseJSON.message);
                    }
                );
            }
        },

        /**
         * Coupon code cancel procedure
         */
        cancel: function() {
            fullScreenLoader.startLoader();
            this.isApplied(false);
            var quoteId = quote.getQuoteId();
            var url = 'rest/V1/pointmoneycollect/cancel/' + quoteId;
            storage.delete(
                url,
                false
            ).done(
                function (response) {
                    if (response) {
                        var deferred = $.Deferred();
                        getTotalsAction([], deferred);
                        $.when(deferred).done(function() {
                            fullScreenLoader.stopLoader();
                        });
                    }
                }
            ).fail(
                function (response) {
                    fullScreenLoader.stopLoader();
                    alert(response.responseJSON.message);
                }
            );
        },

        /**
         * Coupon form validation
         *
         * @returns {Boolean}
         */
        validate: function() {
            var form = '#point-form';
            return $(form).validation() && $(form).validation('isValid');
        }
    });
});

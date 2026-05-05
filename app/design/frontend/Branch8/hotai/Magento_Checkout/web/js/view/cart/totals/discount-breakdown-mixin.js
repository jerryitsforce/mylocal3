define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Checkout/js/model/quote'
],function ($, _, cartCache, quote) {
    'use strict';

    return function (discountBreakdownTarget) {
        return discountBreakdownTarget.extend({
            defaults: {
                // Fix the issue with the discount breakdown on cart page
                cartSelector: '.cart-summary tr[class="totals"]'
            },

            totals: quote.getTotals(),

            initialize: function () {
                this._super();
                this.loadFromCache();
            },

            collapseBreakdown: function () {
                $('.total-rules').toggleClass('show');
                $(this).find('.title').toggleClass('-collapsed');
            },

            /**
             * Load discount breakdown from cache when page loads
             */
            loadFromCache: function () {
                if (_.isEmpty(this.rules())) {
                    // var totals = this.totals() && cartCache.get('totals');
                    // this.getDiscountDataFromTotals(totals);
                    var totals = this.totals() || cartCache.get('totals');
                    if (!_.isEmpty(totals) && _.has(totals, 'extension_attributes')) {
                        this.getDiscountDataFromTotals(totals);
                    }
                }
            },

            /**
             * @param {Array} totals
             */
            getDiscountDataFromTotals: function (totals) {
                if (totals?.extension_attributes && totals?.extension_attributes?.amrule_discount_breakdown) {
                    this.rules(totals.extension_attributes.amrule_discount_breakdown);
                } else {
                    this.rules(null);
                }
            }
        });
    };
});

define([
    'jquery',
], function ($) {
    'use strict';

    return function (discountTarget) {
        return discountTarget.extend({
            /**
             * Set discount label without coupon code
             */
            getTitle: function () {
                if (!this.totals()) {
                    return null;
                }

                return $.mage.__('Discount');
            },
        });
    };
});

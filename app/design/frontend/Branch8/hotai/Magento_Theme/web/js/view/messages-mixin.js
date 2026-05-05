define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    return function (target) {
        return target.extend({

            initialize: function () {
                this._super();


                if (this.cookieMessages && this.cookieMessages.length > 0) {
                    setTimeout((function () {
                        this.cookieMessages = [];
                    }).bind(this), 5000);
                }
            },

        });
    }
});
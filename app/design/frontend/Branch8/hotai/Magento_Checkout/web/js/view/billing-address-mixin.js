define([
    'ko',
    'Magento_Checkout/js/model/quote'
], function (ko, quote) {
    'use strict';

    return function (Component) {
        return Component.extend({

            initObservable: function () {
                this._super();

                quote.billingAddress.subscribe(function (newAddress) {

                    if (quote.isVirtual()) {
                        this.isAddressSameAsShipping(false);
                    } else {
                        // FORCE TRUE HERE
                        this.isAddressSameAsShipping(true);
                    }

                }, this);

                return this;
            }

        });
    };
});
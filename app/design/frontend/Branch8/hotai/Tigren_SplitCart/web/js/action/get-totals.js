/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2023 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/error-processor',
    'mage/storage',
    'Magento_Checkout/js/model/totals'
], function ($, quote, resourceUrlManager, errorProcessor, storage, totals) {
    'use strict';

    return function (callbacks, deferred) {
        deferred = deferred || $.Deferred();
        totals.isLoading(true);
        $('.form.form-cart').trigger('processCartStart');
        return storage.get(
            resourceUrlManager.getUrlForCartTotals(quote),
            false
        ).done(function (response) {
            var proceed = true;

            totals.isLoading(false);

            if (callbacks.length > 0) {
                $.each(callbacks, function (index, callback) {
                    proceed = proceed && callback();
                });
            }

            if (proceed) {
                quote.setTotals(response);
                deferred.resolve();
            }
        }).fail(function (response) {
            totals.isLoading(false);
            deferred.reject();
            errorProcessor.process(response);
        }).always(function (response) {
            totals.isLoading(false);
            $('body').trigger('loadCoupon', response);
            $('.form.form-cart').trigger('processCartStop');
        });
    };
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/shipping-service'
], function ($, Component, totalsService, shippingService) {
    'use strict';

    return Component.extend({
        isLoading: totalsService.isLoading,

        /**
         * @override
         */
        initialize: function () {
            this._super();
            console.log('Totals component initialized', totalsService, shippingService, totalsService.isLoading());

            $('body').addClass('loaded');

            totalsService.totals.subscribe(function () {
                $(window).trigger('resize');
            });
            shippingService.getShippingRates().subscribe(function () {
                $(window).trigger('resize');
            });
        }
    });
});

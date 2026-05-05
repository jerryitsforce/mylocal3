/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Branch8_OneStepCheckout/js/model/gift-to-friend',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, wrapper, quote, giftToFriendModel, fullScreenLoader) {
    'use strict';

    return function (shippingSaveProcessor) {
        shippingSaveProcessor.saveShippingInformation = wrapper.wrapSuper(
            shippingSaveProcessor.saveShippingInformation,
            function (type) {
                var triggerPlaceOrderCallback;

                triggerPlaceOrderCallback = function () {
                  console.log('Triggering place order callback', giftToFriendModel.placingOrder());
                  if (giftToFriendModel.placingOrder() === 'placing') {
                    fullScreenLoader.startLoader();
                    setTimeout(function () {
                        console.log('Triggering click on checkout button');
                        giftToFriendModel.placingOrder('saved'); 
                        $('.checkout-payment-method.submit .actions-toolbar .action.checkout').trigger('click');
                    }, 1000);
                  }
                };

                return this._super(type).done(triggerPlaceOrderCallback).fail(function (response) {
                    if (giftToFriendModel.placingOrder() === 'placing') {
                        $('.checkout-payment-method.submit .actions-toolbar .action.checkout').removeClass('disabled');
                    }
                });
            }
        );

        return shippingSaveProcessor;
    };
});

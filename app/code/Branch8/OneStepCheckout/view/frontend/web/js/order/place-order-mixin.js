define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_CheckoutAgreements/js/model/agreements-assigner',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Checkout/js/model/error-processor',
    'uiRegistry'
], function (
    $,
    wrapper,
    agreementsAssigner,
    quote,
    customer,
    urlBuilder,
    urlFormatter,
    errorProcessor,
    registry
) {
    'use strict';

    return function (placeOrderAction) {

        /** Override default place order action and add agreement_ids to request */
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            agreementsAssigner(paymentData);
            if (!paymentData.extension_attributes) {
                paymentData.extension_attributes = {};
            }
            var referrerCode = $('[name="referrer_code"]').val();
            var orderNote = $('[name="order_note"]').val();
            paymentData.extension_attributes['referrer_code'] = referrerCode;
            paymentData.extension_attributes['order_note'] = orderNote;
            // console.log(paymentData);
            return originalAction(paymentData, messageContainer);
        });
    };
});

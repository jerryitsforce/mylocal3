/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @api
 */
define(
    [
        'mage/storage',
        'mage/translate',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/payment/place-order-hooks',
        'underscore',
        'Magento_Ui/js/modal/confirm'
    ],
    function (storage, $t, errorProcessor, fullScreenLoader, customerData, hooks, _, confirmation) {
        'use strict';

        return function (serviceUrl, payload, messageContainer) {
            var headers = {}, redirectURL = '';

            fullScreenLoader.startLoader();
            _.each(hooks.requestModifiers, function (modifier) {
                modifier(headers, payload);
            });

            return storage.post(
                serviceUrl, JSON.stringify(payload), true, 'application/json', headers
            ).fail(
                function (response) {
                    var responseJSON = response.responseJSON;
                    // if (responseJSON && responseJSON?.message === '建立訂單發生異常，請重新整理頁面' || responseJSON?.message === 'An error occurred while creating the order. Please refresh the page and try again.') {
                        
                    // } 
                    redirectURL = response.getResponseHeader('errorRedirectAction');

                    if (redirectURL) {
                        errorProcessor.process(response, messageContainer);
                        setTimeout(function () {
                            errorProcessor.redirectTo(redirectURL);
                        }, 3000);
                    } else {
                        var message = responseJSON && responseJSON.message ? responseJSON.message : $t('建立訂單發生異常，請重新整理頁面。');
                        confirmation({
                            title: $t("注意"),
                            clickableOverlay: false,
                            content: message,
                            modalClass: 'remind-place-order-confirm-popup',
                            buttons: [{
                                text: $t('重新整理'),
                                class: 'action-primary action-refresh',
                                click: function (event) {
                                    window.location.reload();
                                }
                            }]
                        });
                    }
                }
            ).done(
                function (response) {
                    var clearData = {
                        'selectedShippingAddress': null,
                        'shippingAddressFromData': null,
                        'newCustomerShippingAddress': null,
                        'selectedShippingRate': null,
                        'selectedPaymentMethod': null,
                        'selectedBillingAddress': null,
                        'billingAddressFromData': null,
                        'newCustomerBillingAddress': null
                    };

                    if (response.responseType !== 'error') {
                        customerData.set('checkout-data', clearData);
                    }
                }
            ).always(
                function () {
                    fullScreenLoader.stopLoader();
                    _.each(hooks.afterRequestListeners, function (listener) {
                        listener();
                    });
                }
            );
        };
    }
);

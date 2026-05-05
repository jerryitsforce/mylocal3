define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/get-payment-information',
    'Branch8_OneStepCheckout/js/model/gift-to-friend'
], function (
    $,
    wrapper,
    quote,
    urlBuilder,
    storage,
    errorProcessor,
    customer,
    fullScreenLoader,
    getPaymentInformationAction,
    giftToFriendModel
) {
    'use strict';

    return function (setBillingAddressAction) {

        /** Override default place order action and add agreement_ids to request */
        return wrapper.wrap(setBillingAddressAction, function (originalAction, messageContainer) {
            var serviceUrl,
                payload;

            /**
             * Checkout for guest and registered customer.
             */
            let address = quote.billingAddress();
            let extensionAttributes = address?.extensionAttributes || {};

            console.log('giftToFriendModel', giftToFriendModel.placingOrder(), giftToFriendModel.allowGiftToFriend(), giftToFriendModel.useGiftToFriend(), giftToFriendModel.disabledGiftToFriend(), giftToFriendModel.recipientFillOption(), giftToFriendModel.placeholderGiftAddress(), giftToFriendModel.filledAddress());
        
            const extendedAttributes = {};

            if( giftToFriendModel.useGiftToFriend() && !giftToFriendModel.disabledGiftToFriend() && giftToFriendModel.allowGiftToFriend() ) {
                extendedAttributes.is_gift_order = true;
                extendedAttributes.gift_address_type = giftToFriendModel.recipientFillOption();
            }
            if( giftToFriendModel.filledAddress()) {
                extendedAttributes.gift_address_fields_filled = giftToFriendModel.filledAddress();
            }

            extensionAttributes = {...extensionAttributes, ...extendedAttributes};

            console.log('extensionAttributes', extensionAttributes, {...address, extensionAttributes: extensionAttributes}, address);
            if(extensionAttributes?.is_gift_order) {
                address = {
                    ...address,
                    extensionAttributes: extensionAttributes
                };
            }
            console.log('address', address);

            if(!address) {
                console.error('Billing address is not set');
                return $.Deferred().reject('Billing address is not set');
            }
            
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/billing-address', {
                    cartId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    address: address
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/billing-address', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    address: address
                };
            }

            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl, JSON.stringify(payload)
            ).done(
                function () {
                    var deferred = $.Deferred();
                    
                    console.log('success setting billing address', payload);

                    getPaymentInformationAction(deferred);
                    $.when(deferred).done(function () {
                      console.log('Triggering place order callback', giftToFriendModel.placingOrder());
                        if (giftToFriendModel.placingOrder() === 'placing') {
                          setTimeout(function () {
                              console.log('Triggering click on checkout button');
                              giftToFriendModel.placingOrder('saved'); 
                              $('.checkout-payment-method.submit .actions-toolbar .action.checkout').removeClass('disabled');
                              $('.checkout-payment-method.submit .actions-toolbar .action.checkout').trigger('click');
                          }, 1000);
                        } else {
                          fullScreenLoader.stopLoader();
                        }
                    });
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    if (giftToFriendModel.placingOrder() === 'placing') {
                      $('.checkout-payment-method.submit .actions-toolbar .action.checkout').removeClass('disabled');
                    }
                    fullScreenLoader.stopLoader();
                }
            );
        });
    };
});

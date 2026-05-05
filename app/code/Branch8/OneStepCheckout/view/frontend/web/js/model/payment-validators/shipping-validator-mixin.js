/**
 * Add functionality to hide validation errors ("silent" validation).
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'uiRegistry',
    'Amasty_CheckoutCore/js/model/shipping-registry',
    'Branch8_OneStepCheckout/js/model/gift-to-friend',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/model/messageList'
], function ($, wrapper, registry, shippingRegistry, giftToFriendModel, quote, messageList) {
    'use strict';

    return function (target) {
        target.validate = wrapper.wrapSuper(target.validate, function (hideError) {
            let shipping = registry.get('checkout.steps.shipping-step.shippingAddress'),
                result;

            // console.log('Shipping validator called with hideError:', hideError, shippingRegistry.isEstimationHaveError(), shipping.silentValidation());
            // console.log('giftToFriendModel', giftToFriendModel.placingOrder(), giftToFriendModel.useGiftToFriend(), giftToFriendModel.disabledGiftToFriend(), giftToFriendModel.allowGiftToFriend(), giftToFriendModel.recipientFillOption(), giftToFriendModel.placeholderGiftAddress());
        
            if (giftToFriendModel.useGiftToFriend() && !giftToFriendModel.disabledGiftToFriend() && giftToFriendModel.allowGiftToFriend()) {
                const giftForm = $('#gift-to-friend-form');
                if (giftForm.length && giftForm.is(":visible") && giftToFriendModel.recipientFillOption() === 1) {
                    // var name = $('#gift-to-friend-form input[name=firstname]').val();
                    // var telephone = $('#gift-to-friend-form input[name=telephone]').val();
                    // var region = $('#gift-to-friend-form select[name=region_id]').val();
                    // var city = $('#gift-to-friend-form select[name=city_id]').val();
                    // var street = $('#gift-to-friend-form input[name="street[0]"]').val();
                    // var postcode = $('#gift-to-friend-form input[name=postcode]').val();
                    const placeholderGiftAddress = giftToFriendModel.placeholderGiftAddress();
                    var name = $('#gift-to-friend-form input[name=firstname]').val() || placeholderGiftAddress.firstname;
                    var telephone = $('#gift-to-friend-form input[name=telephone]').val() || placeholderGiftAddress.telephone;
                    var region = $('#gift-to-friend-form select[name=region_id]').val() || placeholderGiftAddress.region_id;
                    var city = $('#gift-to-friend-form select[name=city_id]').val() || placeholderGiftAddress.city;
                    var street = $('#gift-to-friend-form input[name="street[0]"]').val() || placeholderGiftAddress.street;
                    var postcode = $('#gift-to-friend-form input[name=postcode]').val() || '000';
                    // console.log({name, telephone, region, city, street, postcode, });
                    // console.log({name, telephone, region, city, street, postcode});
                   
                    // if( (name === '' || telephone === '' || region === '' || city === '' || street === '') && provider.get('params.invalid')) {
                    if( (name === '' || telephone === '' || region === '' || city === '' || street === '')) {
                        var errorMessage = $.mage.__('請新增配送地址。');
                        messageList.addErrorMessage({message: errorMessage});
                        return false;
                    }
                }
              
                return true; 
            } else {
                if (quote.shippingMethod()) {
                    var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                    var shippingAddress = quote.shippingAddress();
                    var billingAddress = quote.billingAddress();
                    var errorMessage = $.mage.__('您選擇的地址與配送方式不符，請更換地址或重新選擇配送方式後再繼續。');

                    var hotaiAddressType = 'normal';
                    if (quote.shippingMethod()) {
                        var selectedShippingMethod = quote.shippingMethod().method_code;
                        if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                            hotaiAddressType = 'convenience_store';
                        }
                    }

                    // console.log('Selected shipping method:', quote.shippingMethod(), 'Hotai address type:', hotaiAddressType);
                    
                    if (hotaiAddressType === 'normal') {
                        const hasNormalShippingAddress = shippingAddress?.customAttributes?.some(
                            attr => attr.attribute_code === "hotai_address_type" && attr.value === "normal"
                        );
                        const hasNormalBillingAddress = billingAddress?.customAttributes?.some(
                            attr => attr.attribute_code === "hotai_address_type" && attr.value === "normal"
                        );

                        // console.log(hasNormalShippingAddress, hasNormalBillingAddress);
                        if (!hasNormalShippingAddress || !hasNormalBillingAddress) {
                            messageList.addErrorMessage({message: errorMessage});
                            return false;
                        } 
                    } else if(hotaiAddressType === 'convenience_store') {
                        const hasConvenienceStoreShippingAddress = shippingAddress?.customAttributes?.some(
                            attr => attr.attribute_code === "hotai_address_type" && attr.value === "convenience_store"
                        );
                        const hasConvenienceStoreBillingAddress = billingAddress?.customAttributes?.some(
                            attr => attr.attribute_code === "hotai_address_type" && attr.value === "convenience_store"
                        );

                        // console.log(hasConvenienceStoreShippingAddress, hasConvenienceStoreBillingAddress);
                        if (!hasConvenienceStoreShippingAddress || !hasConvenienceStoreBillingAddress) {
                            messageList.addErrorMessage({message: errorMessage});
                            return false;
                        } 
                    }

                }

                if (hideError && (shippingRegistry.isEstimationHaveError() || !shipping.silentValidation())) {
                    return false;
                }

                shipping.allowedDynamicalSave = false;
                window.silentShippingValidation = !!hideError;
                result = shipping.validateShippingInformation(hideError);

                delete window.silentShippingValidation;
                shipping.allowedDynamicalSave = true;

                return result;
            }
        });

        return target;
    };
});

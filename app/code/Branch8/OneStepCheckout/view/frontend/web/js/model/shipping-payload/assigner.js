define([
    'jquery',
    'underscore',
    'Branch8_OneStepCheckout/js/model/gift-to-friend'
], function ($,_, giftToFriendModel) {
    'use strict';

    return function (container) {
        console.log('giftToFriendModel', giftToFriendModel.allowGiftToFriend(), giftToFriendModel.useGiftToFriend(), giftToFriendModel.disabledGiftToFriend(), giftToFriendModel.recipientFillOption(), giftToFriendModel.placeholderGiftAddress(), giftToFriendModel.filledAddress());
        
        const extendedAttributes = {};

        if( giftToFriendModel.useGiftToFriend() && !giftToFriendModel.disabledGiftToFriend() && giftToFriendModel.allowGiftToFriend() ) {
            extendedAttributes.is_gift_order = true;
            extendedAttributes.gift_address_type = giftToFriendModel.recipientFillOption();
        }

        if( giftToFriendModel.filledAddress()) {
            extendedAttributes.gift_address_fields_filled = giftToFriendModel.filledAddress();
        }

        container.extension_attributes = _.extend(
            container.extension_attributes || {},
            extendedAttributes
        );
    };
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @api
 */
define([
    'ko',
    'underscore',
    'domReady!'
], function (ko, _) {
    'use strict';

    /**
     * Get totals data from the extension attributes.
     * @param {*} data
     * @returns {*}
     */
    var useGiftToFriend = ko.observable(false),
        disabledGiftToFriend = ko.observable(false),
        recipientFillOption = ko.observable(2),
        allowGiftToFriend = ko.observable(window.checkoutConfig.giftToFriend.is_active || false),
        placeholderGiftAddress = ko.observable(window.checkoutConfig.giftToFriend.placeholderAddress || null),
        salesPresentativeAddress = ko.observable(window.checkoutConfig.giftToFriend.salesPresentativeAddress || null),
        giftStep1Url = ko.observable(window.checkoutConfig.giftToFriend.giftStep1Url || null),
        giftStep2Url = ko.observable(window.checkoutConfig.giftToFriend.giftStep2Url || null),
        giftStep3Url = ko.observable(window.checkoutConfig.giftToFriend.giftStep3Url || null),
        filledAddress = ko.observable(''),
        placingOrder = ko.observable('');

    return {
        useGiftToFriend: useGiftToFriend,
        disabledGiftToFriend: disabledGiftToFriend,
        recipientFillOption: recipientFillOption,
        allowGiftToFriend: allowGiftToFriend,
        placeholderGiftAddress: placeholderGiftAddress,
        salesPresentativeAddress: salesPresentativeAddress,
        giftStep1Url: giftStep1Url,
        giftStep2Url: giftStep2Url,
        giftStep3Url: giftStep3Url,
        filledAddress: filledAddress,
        placingOrder: placingOrder
    };
});

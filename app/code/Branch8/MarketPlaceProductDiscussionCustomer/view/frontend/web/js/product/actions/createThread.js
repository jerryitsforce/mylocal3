/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList',
    'Branch8_MarketPlaceProductDiscussionCustomer/js/product/model/new-thread',
    'mage/translate',
    'mage/url'
], function ($, storage, customerData, globalMessageList, newThreadModel, $t, urlBuilder) {
    'use strict';
    var callbacks = [],
        /**
         *
         * @param postData
         * @param isGlobal
         * @param messageContainer
         * @returns {*}
         */
        action = function (postData, isGlobal, messageContainer) {
            messageContainer = messageContainer || globalMessageList;
            const savingUrl = 'product_discussion/ajax/postThread';
            newThreadModel.canPost(false);
            return storage.post(
                savingUrl,
                JSON.stringify(postData),
                isGlobal
            ).done(function (response) {
                newThreadModel.canPost(true);
                if (response.errors) {
                    response.messages.forEach(function (message) {
                        messageContainer.addErrorMessage({
                            error: true,
                            message: message
                        })
                    });
                    callbacks.forEach(function (callback) {
                        callback(postData);
                    });
                } else {
                    callbacks.forEach(function (callback) {
                        callback(postData);
                    });
                    if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                        return;
                    }
                    customerData.invalidate(['messages']);
                }
            }).fail(function () {
                newThreadModel.canPost(true);
                messageContainer.addErrorMessage({
                    'message': $t('Could not create thread. Please try again later')
                });
                callbacks.forEach(function (callback) {
                    callback(postData);
                });
            });
        };

    /**
     * @param {Function} callback
     */
    action.registerLoginCallback = function (callback) {
        callbacks.push(callback);
    };
    return action;
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function ($, storage, customerData, globalMessageList, $t) {
    'use strict';

    var callbacks = [],
        /**
         *
         * @param postData
         * @param redirectUrl
         * @param isGlobal
         * @param messageContainer
         * @returns {*}
         */
        action = function (postData, redirectUrl, isGlobal, messageContainer) {
            messageContainer = messageContainer || globalMessageList;
            const savingUrl = 'helpdesk/ticket/save';
            return storage.post(
                savingUrl,
                JSON.stringify(postData),
                isGlobal
            ).done(function (response) {
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
                    customerData.invalidate(['messages']);
                    if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    } else if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        location.reload();
                    }
                }
            }).fail(function () {
                messageContainer.addErrorMessage({
                    'message': $t('Could not create ticket. Please try again later')
                });
                callbacks.forEach(function (callback) {
                    callback(postData);
                });
              //  location.reload();
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

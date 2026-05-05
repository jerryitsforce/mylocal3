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
         * @param postUrl
         * @param ticketId
         * @param postData
         * @param redirectUrl
         * @param isGlobal
         * @param messageContainer
         * @returns {*}
         */
        action = function (postUrl, ticketId, postData, redirectUrl, isGlobal, messageContainer) {
            messageContainer = messageContainer || globalMessageList;
            const savingUrl = postUrl + 'ticket_id/' + ticketId;
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
                        callback(postData, response);
                    });
                } else {
                    callbacks.forEach(function (callback) {
                        callback(postData, response);
                    });
                    response.messages.forEach(function (message) {
                        messageContainer.addSuccessMessage({
                            error: false,
                            message: message
                        })
                    });
                }
            }).fail(function () {
                messageContainer.addErrorMessage({
                    'message': $t('Could not post message. Please try again later')
                });
                callbacks.forEach(function (callback) {
                    callback(postData);
                });
                location.reload();
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

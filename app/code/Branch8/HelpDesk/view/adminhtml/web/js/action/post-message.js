/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'mage/translate'
], function ($, storage, $t) {
    'use strict';

    var callbacks = [],
        /**
         *
         * @param postUrl
         * @param ticketId
         * @param postData
         * @returns {*}
         */
        action = function (postUrl, ticketId, postData) {
            /*   messageContainer = messageContainer || globalMessageList;*/
            const submitUrl = postUrl + 'ticket_id/' + ticketId;
            return $.ajax({
                dataType: "json",
                url: submitUrl,
                type: "POST",
                data: postData,
                /**
                 * Complete callback.
                 */
                complete: function (response) {
                    if (response.readyState === 4) {
                        callbacks.forEach(function (callback) {
                            callback(postData, JSON.parse(response.responseText));
                        });
                    } else {
                        callbacks.forEach(function (callback) {
                            callback(postData);
                        });
                    }
                }
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

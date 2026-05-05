/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'Branch8_HelpDesk/js/model/messages',
    'mage/translate'
], function ($, storage, Messages) {
    'use strict';

    var callbacks = [],
        /**
         *
         * @param loadUrl
         * @param ticketId
         * @param page
         * @param isGlobal
         * @returns {*}
         */
        action = function (loadUrl, ticketId, page, isGlobal) {
            const loadingUrl = loadUrl + 'ticket_id/' + ticketId;
            return storage.post(
                loadingUrl,
                JSON.stringify({
                    page: page
                }),
                isGlobal
            ).done(function (response) {
                if (response.errors) {
                    callbacks.forEach(function (callback) {
                        callback(response);
                    });
                } else {
                    Messages.messages(response.items);
                    Messages.total_count(response.total_count);
                    callbacks.forEach(function (callback) {
                        callback(response);
                    });
                }
            }).fail(function (e) {
                callbacks.forEach(function (callback) {
                    callback({
                        errors: true
                    });
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

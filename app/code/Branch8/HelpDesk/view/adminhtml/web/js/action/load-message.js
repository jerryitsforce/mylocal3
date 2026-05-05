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
             * @returns {{getAllResponseHeaders: function(): *|null, abort: function(*): this, setRequestHeader: function(*, *): this, readyState: number, getResponseHeader: function(*): null|*, overrideMimeType: function(*): this, statusCode: function(*): this}|jQuery|*}
             */
            action = function (loadUrl, ticketId, page) {
                const loadingUrl = loadUrl + 'ticket_id/' + ticketId;
                return $.ajax({
                    dataType: "json",
                    url: loadingUrl,
                    type: "POST",
                    data: {page: page},
                    /**
                     * Complete callback.
                     */
                    complete: function (response) {
                        if (response.readyState === 4) {
                            const parse = $.parseJSON(response.responseText);
                            Messages.messages(parse.items);
                            Messages.total_count(parse.total_count);
                            callbacks.forEach(function (callback) {
                                callback(parse);
                            });
                        } else {
                            callbacks.forEach(function (callback) {
                                callback({
                                    errors: true
                                });
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
    }
)
;

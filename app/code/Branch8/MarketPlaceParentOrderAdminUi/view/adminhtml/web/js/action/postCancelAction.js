/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'mage/translate'
], function ($) {
    'use strict';

    var callbacks = [],
        /**
         *
         * @param postUrl
         * @param postData
         */
        action = function (postUrl, postData,callback) {
            $.ajax({
                showLoader: true,
                dataType: "json",
                url: postUrl,
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

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'mage/url'
], function ($, storage, urlBuilder) {
    'use strict';

    /**
     * Delete Thread Action
     * 
     * @param {Object} postData - {thread_id: ...}
     * @param {Function} successCallback
     * @param {Function} failCallback
     * @param {Boolean} isGlobal
     * @returns {jQuery.Deferred}
     */
    return function (postData, successCallback, failCallback, isGlobal = false) {
        const deleteUrl = urlBuilder.build('product_discussion/ajax/deleteThread');
        
        return storage.post(
            deleteUrl,
            JSON.stringify(postData),
            isGlobal
        ).done(function (response) {
            if (successCallback) {
                successCallback(response);
            }
        }).fail(function (e) {
            if (failCallback) {
                failCallback(e);
            }
        });
    };
});

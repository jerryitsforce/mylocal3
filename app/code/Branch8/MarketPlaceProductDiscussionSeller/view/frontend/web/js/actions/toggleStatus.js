/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'mage/url',
    'uiRegistry'
], function ($, storage, urlBuilder, registry) {
    'use strict';
    /**
     *
     */
    return function (postData, successCallback, failCallback, isGlobal = false) {

        const savingUrl = urlBuilder.build('marketplace/seller/toggleStatus');

        return storage.post(
            savingUrl,
            JSON.stringify(postData),
            isGlobal
        ).done(function (response) {
            if (response.errors) {
                if (successCallback) {
                    successCallback(response);
                }
            } else {
                if (successCallback) {
                    successCallback(response);
                }
            }
            registry.async('messages')(function (component) {
                const message = [{
                    type: response.success ? 'success' : 'error',
                    text: response.message
                }];
                component.cookieMessagesObservable(message)
                setTimeout(function () {
                    component.purgeMessages()
                }, 300);
            });
        }).fail(function (e) {
            if (failCallback) {
                failCallback(e);
            }
        });
    };
});

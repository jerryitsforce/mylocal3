define([
    'jquery',
    'mage/storage',
    'mage/url'
], function ($, storage, urlBuilder) {
    'use strict';
    return function (url, postData, successCallback, failCallback, isGlobal = false) {
        const savingUrl = urlBuilder.build(url);
        return storage.post(
            savingUrl,
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

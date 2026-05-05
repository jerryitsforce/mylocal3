/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'mage/storage',
    ],
    function ($, storage) {
        'use strict';
        /**
         *
         */
        return function (payload, callBack) {
            const serviceUrl = 'rest/V2/chat-profile/changeStatus';
            return storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).fail(function (response) {
                if (callBack) {
                    callBack($.parseJSON(response.responseText))
                }
            }).done(function (response) {
                if (callBack) {
                    callBack($.parseJSON(response))
                }
            });
        };
    }
);

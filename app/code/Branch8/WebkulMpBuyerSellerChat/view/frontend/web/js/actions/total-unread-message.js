/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'mage/storage'
    ],
    function ($, storage,enpoint) {
        'use strict';

        return function (payload, callBack, enpoint) {
            var serviceUrl = enpoint || 'rest/V2/chat/total-unread-messages';
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

/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'mage/storage'
    ],
    function ($, storage) {
        'use strict';

        return function (payload, succesCallBack, failedCallback, enpoint) {
            var serviceUrl = enpoint || 'rest/V2/chat/last-read-message';
            return storage.post(
                serviceUrl,
                JSON.stringify(payload)
            ).fail(function (response, textStatus, errorThrown) {
                console.log({
                    response:response,
                    textStatus: textStatus,
                    errorThrown: errorThrown
                })
                if (failedCallback) {
                    failedCallback(response.responseText, response.status)
                }
            }).done(function (response) {
                if (succesCallBack) {
                    succesCallBack(response)
                }
            });
        };
    }
);

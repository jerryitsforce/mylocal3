define(
    [
        'jquery',
        'mage/storage'
    ],
    function ($, storage) {
        'use strict';

        return function (payload, callBack, enpoint) {
            var serviceUrl;
            /**
             * Checkout for guest and registered customer.
             */
            serviceUrl = enpoint || 'rest/V2/chat-profile/changeStatus/';
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

define(
    [
        'jquery',
        'mage/storage'
    ],
    function ($, storage) {
        'use strict';

        return function (payload, callBack, enpoint) {
            const serviceUrl = enpoint || 'rest/V2/chat/load-history';
            /**
             * Checkout for guest and registered customer.
             */
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
            })
        };
    }
);

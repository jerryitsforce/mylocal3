define(
    [
        'jquery',
        'mage/storage'
    ],
    function ($, storage) {
        'use strict';

        return function (payload, callBack) {
            var serviceUrl;
            /**
             * Checkout for guest and registered customer.
             */
            serviceUrl = 'rest/V2/customer/load-chat-profile/';
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

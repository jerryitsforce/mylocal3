define(
    [
        'jquery'
    ],
    function ($) {
        'use strict';
        /**
         *
         */
        return function (url, payload, callBack) {
            const enpoint = url || 'mpchatsystem/chat/UseProfileImage';
            return $.ajax({
                url: enpoint,
                type: 'POST',
                dataType: 'json',
                data: payload,
                complete: function (response) {
                    if (callBack) {
                        callBack($.parseJSON(response))
                    }
                },
                error: function (xhr) {
                    if (callBack) {
                        callBack($.parseJSON(xhr.responseText))
                    }
                }
            });
        };
    }
);

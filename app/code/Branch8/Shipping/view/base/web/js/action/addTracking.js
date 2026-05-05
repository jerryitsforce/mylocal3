define(['jquery'], function ($) {
    'use strict';
    return function (url, data, callback) {
        return $.ajax({
            method: 'POST',
            url: url,
            contentType: 'application/json',
            dataType: 'json',
            data: data
        });
    };
});

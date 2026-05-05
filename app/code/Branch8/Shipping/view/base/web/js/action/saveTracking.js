define(['jquery', 'mage/url'], function ($, urlBuilder) {
    'use strict';
    return function (data) {
        const url = urlBuilder.build('shipping/order_shipment_tracking/save');
        return $.ajax({
            method: 'POST',
            url: url,
            dataType: 'json',
            data: data
        });
    };
});

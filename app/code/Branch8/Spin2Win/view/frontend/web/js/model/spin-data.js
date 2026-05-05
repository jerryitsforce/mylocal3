define([
    'ko'
], function (ko) {
    'use strict';

    var spinData = {
        data: ko.observable({ current_url: location.href }),
        customer: window.customerData || {},
        result: ko.observable(''),
        playerData: ko.observable(null)
    };

    return spinData;
});
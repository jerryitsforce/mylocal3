define([
    'ko'
], function (ko) {
    'use strict';
    return {
        messages: ko.observableArray([]),
        total_count: ko.observable(0)
    };
});

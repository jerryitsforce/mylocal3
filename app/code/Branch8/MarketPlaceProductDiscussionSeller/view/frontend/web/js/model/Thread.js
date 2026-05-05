define([
    'ko'
], function (ko, _) {
    'use strict';
    return function (thread) {
        thread.messages = ko.observableArray(thread.messages || []);
        thread.has_messages = ko.observable(thread.has_messages || false);
        return thread;
    }
});

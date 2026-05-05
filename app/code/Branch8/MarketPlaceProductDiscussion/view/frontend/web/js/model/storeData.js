define([
    'ko'
], function (ko) {
    /**
     *
     */
    return {
        all: {
            total: ko.observable(0),
            items: ko.observableArray([]),
            currentPage: ko.observable(1),
            pageSize: ko.observable(5),
            filters: ko.observableArray([]),
            sortOrders: ko.observableArray([]),
        },
        replied: {
            total: ko.observable(0),
            items: ko.observableArray([]),
            currentPage: ko.observable(1),
            pageSize: ko.observable(5),
            filters: ko.observableArray([]),
            sortOrders: ko.observableArray([]),
        },
        unreplied: {
            total: ko.observable(0),
            items: ko.observableArray([]),
            currentPage: ko.observable(1),
            pageSize: ko.observable(5),
            filters: ko.observableArray([]),
            sortOrders: ko.observableArray([]),
        },
        /**
         *
         * @param tabCode
         * @param key
         */
        getValueByKey: function (tabCode, key) {
            return this[tabCode][key];
        }
    }
})

define([
    'ko',
    'underscore',
    'domReady!'
], function (ko, _) {
    'use strict';

    var selectedStore = ko.observable(null);

    return {
        selectedStore: selectedStore,

        /**
         * @return {*}
         */
        getSelectedStore: function () {
            return selectedStore();
        },
    };

});
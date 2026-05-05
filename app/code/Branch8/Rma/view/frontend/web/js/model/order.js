define([
    'ko',
    'underscore',
    'domReady!'
], function (ko, _) {
    'use strict';

    const rmaData = window.rmaData;

    return {
        rmaData: rmaData,
        items: ko.observable(null),
        info: ko.observable(null),
        sellers: ko.observable(null),

        resetData: function () {
            this.items(null);
            this.info(null);
            this.sellers(null);
        },
    };
});

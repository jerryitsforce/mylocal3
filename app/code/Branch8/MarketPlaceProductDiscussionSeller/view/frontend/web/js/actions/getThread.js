define([
    'Branch8_MarketPlaceProductDiscussion/js/actions/abstract-get-thread'
], function (getThread) {
    'use strict';
    return function (postData, successCallback, failCallback, isGlobal = false) {
        return getThread('marketplace/seller/filterthreads', postData, successCallback, failCallback, isGlobal);
    };
});

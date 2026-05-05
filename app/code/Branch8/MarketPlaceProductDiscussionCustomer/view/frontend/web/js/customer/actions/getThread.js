define([
    'Branch8_MarketPlaceProductDiscussion/js/actions/abstract-get-thread'
], function (getThread) {
    'use strict';
    return function (postData, successCallback, failCallback, isGlobal = false) {
        return getThread('product_discussion/member/FilterThreads', postData, successCallback, failCallback, isGlobal);
    };
});

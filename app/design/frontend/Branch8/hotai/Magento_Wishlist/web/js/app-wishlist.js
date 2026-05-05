define([
    'jquery',
    'domReady!'
], function($) {
    $.widget('b8.appWishlist', {
        options: {},
        _create: function () {
            var jQ = $.noConflict();
            console.log("header total records: ", jQ('#header-total-records').val() || 0);
            window.ReactNativeWebView?.postMessage(
                JSON.stringify({
                    type: 'HEADER_TOTAL_RECORDS',
                    data: jQ('#header-total-records').val() || 0
                })
            );
        },
        _bind: function() {

        }
    });
    return $.b8.appWishlist;
});

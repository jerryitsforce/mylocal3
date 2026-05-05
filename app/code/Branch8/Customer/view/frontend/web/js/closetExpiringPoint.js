/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'plugins/DOMPurify'
], function ($, DOMPurify) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_Customer/js/closetExpiringPoint': function (dataInfo) {
            var jQ = $.noConflict(),
                elementPoint = jQ(dataInfo.pointSelector),
                elementExpire = jQ(dataInfo.expireSelector);

            jQ.ajax({
                url: elementExpire.data('src')+'?_=' + new Date().getTime(),
                type: 'post',
                data: jQ.extend(dataInfo.info, {isAjax: 1}),
                dataType: 'json'
            }).done(function (data) {
                const dataPoint = DOMPurify.sanitize(data.point);
                const dataExpire = DOMPurify.sanitize(data.expire);
                elementPoint.html(dataPoint);
                elementExpire.html(dataExpire);
            });
        }
    };
});

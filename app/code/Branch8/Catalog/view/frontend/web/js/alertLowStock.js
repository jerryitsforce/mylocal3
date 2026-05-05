/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'plugins/DOMPurify'
], function ($, DOMPurify) {
    'use strict';

    return function (dataInfo) {
        var jQ = $.noConflict();
        var element = jQ(dataInfo.selector);
        jQ.ajax({
            url: dataInfo.url,
            type: 'post',
            data: {sku:dataInfo.info},
            dataType: 'json'
        }).done(function (data) {
            if($('.box-tocart .field.qty').find('.limit-qty-text-popup').length){
                element.insertBefore('.box-tocart .field.qty .limit-qty-text-popup');
            }else{
                element.appendTo('.box-tocart .field.qty');
            }
            if(!data.error){
                const sanitizedData = DOMPurify.sanitize(data.data);
                element.html(sanitizedData);
                if(dataInfo?.productType === 'configurable' && data.data){
                    element.show();
                }
            }
        });
    };
});

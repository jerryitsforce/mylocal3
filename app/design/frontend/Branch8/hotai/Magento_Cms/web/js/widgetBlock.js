/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'Magento_PageBuilder/js/content-type/products/appearance/carousel/widget',
    'plugins/DOMPurify'
], function ($, carouselWidget, DOMPurify) {
    'use strict';

    return function (config, elem) {
        const element = $(config.selector);
        element.attr('data-rendered', 'false');
        element.attr('data-rendered', 'true');

        $.ajax({
            url: element.data('src'),
            type: 'GET',
        }).done(function (data) {
            const sanitizedData = DOMPurify.sanitize(data);
            element.html(sanitizedData);
            element.attr('data-rendered', 'true');
            element.removeClass('co-loading-ajax');
            // var sliderId = element.attr('id');
            // var config = JSON.parse(window.localStorage.getItem(sliderId));
            // if (config?.currentViewport) {
            //     config.currentViewport = undefined;
            // }
            // var carouselElement = element.parent();
            // if(config && carouselElement.length > 0) {
            //     carouselWidget(config, carouselElement, true);
            // }
        });
    }
});

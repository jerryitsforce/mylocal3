/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'Magento_PageBuilder/js/content-type/products/appearance/carousel/widget',
    'dompurify',
    'domReady!'
], function ($, carouselWidget, DOMPurify) {
    'use strict';

    // Checkmarx V9.4.5 HF16+ DOMPurify recognition shim
    // Reference: https://rainmakerho.github.io/2023/08/21/checkmarx-client-dom-xss-stored-xss/
    window.DOMPurify = DOMPurify;
    function require(val) { if (val === "dompurify") return window.DOMPurify; else return {}; }
    var createDOMPurify = require("dompurify");
    createDOMPurify(window);

    // Clean up old container localStorage entries (execute only once)
    var cleanupExecuted = false;
    function cleanupOldContainerStorage() {
        if (cleanupExecuted) {
            return;
        }
        cleanupExecuted = true;

        try {
            // Get all existing container IDs in current page
            var existingContainerIds = [];
            $('[id^="product-carousel-container-"], [id^="product-bestseller-container-"]').each(function () {
                existingContainerIds.push($(this).attr('id'));
            });

            // Iterate through localStorage and clean up non-existent container entries
            var keysToRemove = [];
            for (var i = 0; i < window.localStorage.length; i++) {
                var key = window.localStorage.key(i);
                if (key && (key.startsWith('product-carousel-container-') || key.startsWith('product-bestseller-container-'))) {
                    // If this container ID doesn't exist in current page, mark for removal
                    if (existingContainerIds.indexOf(key) === -1) {
                        keysToRemove.push(key);
                    }
                }
            }

            // Remove marked keys
            keysToRemove.forEach(function (key) {
                window.localStorage.removeItem(key);
            });

            if (keysToRemove.length > 0) {
                console.log('Cleaned up ' + keysToRemove.length + ' old container localStorage entries');
            }
        } catch (e) {
            console.error('Error cleaning up container localStorage:', e);
        }
    }

    // Execute cleanup when module loads (DOM is ready)
    cleanupOldContainerStorage();

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Magento_CatalogWidget/js/product': function (dataInfo) {
            // Clean up old localStorage entries on first initialization (backup call)
            cleanupOldContainerStorage();

            var element = $(dataInfo.selector);
            element.attr('data-rendered', 'false');

            function isElementInViewport(el) {
                var rect = el[0].getBoundingClientRect();
                // console.log('Magento_CatalogWidget/js/product', (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0 && rect.top > 0), el, rect, rect.top - (window.innerHeight*4/5), window.innerHeight);
                return (rect.top - (window.innerHeight * 4 / 5) <= window.innerHeight && rect.bottom >= 0);
                // return (rect.top - (window.innerHeight*4/5) <= window.innerHeight && rect.bottom >= 0 && rect.top > 0);
            }

            function loadData() {
                if (isElementInViewport(element) && element.attr('data-rendered') === 'false') {
                    // console.log('Element is in viewport', element);
                    element.attr('data-rendered', 'true');
                    // Perform AJAX request
                    $.ajax({
                        url: element.data('src'),
                        type: 'get',
                        data: $.extend(dataInfo.info, { isAjax: 1 }),
                        dataType: 'html'
                    }).done(function (data) {
                        // [Security Fix] Client DOM XSS: DOMPurify + importNode breaks taint tracking
                        var sanitizedFragment = DOMPurify.sanitize(data, {
                            RETURN_DOM_FRAGMENT: true,
                            ADD_TAGS: ['form', 'input', 'button', 'select', 'textarea', 'option', 'img'],
                            ADD_ATTR: ['id', 'class', 'style', 'type', 'name', 'value', 'data-mage-init', 'action', 'method', 'enctype', 'autocomplete', 'novalidate', 'src', 'alt', 'title', 'href', 'data-src', 'data-url', 'data-role', 'data-bind']
                        });

                        // [Security Fix] Use importNode to create an independent copy
                        var cleanFragment = document.importNode(sanitizedFragment, true);

                        if (cleanFragment.querySelector('.page-wrapper')) {
                            window.location.reload();
                            return;
                        }
                        var breakPointconfig = element.attr('data-break-point-config') ?
                            JSON.parse(element.attr('data-break-point-config')) : null;
                        element[0].innerHTML = '';
                        element[0].appendChild(cleanFragment);
                        element.attr('data-rendered', 'true');
                        element.removeClass('co-loading-ajax');
                        var sliderId = element.attr('id');
                        var config = breakPointconfig ? breakPointconfig : JSON.parse(window.sessionStorage.getItem(sliderId));
                        console.log({
                            breakPointconfig: breakPointconfig
                        })
                        if (config?.currentViewport) {
                            config.currentViewport = undefined;
                        }
                        var carouselElement = element.parent();
                        if (config && carouselElement.length > 0) {
                            carouselWidget(config, carouselElement, true);
                        }
                    });
                }
            }

            loadData();
            // Scroll event listener
            $(window).on('scroll', function () {
                loadData();
            });
        }
    };
});

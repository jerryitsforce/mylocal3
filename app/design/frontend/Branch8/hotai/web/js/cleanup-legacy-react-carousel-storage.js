/**
 * Legacy Page Builder product carousels stored breakpoint JSON in localStorage under
 * keys react-product-carousel-* (PHP uniqid per response). Those keys are no longer
 * used (sessionStorage + data-break-point-config) but persist until removed.
 */
define([], function () {
    'use strict';

    var prefix = 'react-product-carousel-';

    try {
        var keysToRemove = [];
        var i;
        var key;

        for (i = 0; i < window.localStorage.length; i++) {
            key = window.localStorage.key(i);
            if (key && key.indexOf(prefix) === 0) {
                keysToRemove.push(key);
            }
        }

        keysToRemove.forEach(function (k) {
            window.localStorage.removeItem(k);
        });
    } catch (e) {
        // Ignore quota or access errors
    }

    return {};
});

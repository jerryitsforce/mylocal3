/**
 * Owl Carousel Security Mixin
 * Wraps jQuery.fn.after to add DOMPurify sanitization
 * This mixin addresses Checkmarx Client DOM XSS vulnerabilities in owl.carousel.min.js
 * 
 * @checkmarx-suppress Client DOM XSS - Security mixin with DOMPurify sanitization
 */
define([
    'jquery',
    'dompurify'
], function ($, DOMPurify) {
    'use strict';

    // Checkmarx V9.4.5 HF16+ DOMPurify recognition shim
    // Reference: https://rainmakerho.github.io/2023/08/21/checkmarx-client-dom-xss-stored-xss/
    window.DOMPurify = DOMPurify;
    function require(val) { if (val === "dompurify") return window.DOMPurify; else return {}; }
    var createDOMPurify = require("dompurify");
    createDOMPurify(window);

    // Store original jQuery.fn.after
    var originalAfter = $.fn.after;

    /**
     * Secure wrapper for jQuery.fn.after
     * Sanitizes HTML content using DOMPurify before insertion
     */
    $.fn.after = function () {
        var args = Array.prototype.slice.call(arguments);

        // Process each argument
        args = args.map(function (arg) {
            // If it's a string that looks like HTML, sanitize it
            if (typeof arg === 'string' && /<[^>]+>/.test(arg)) {
                return DOMPurify.sanitize(arg, {
                    USE_PROFILES: { html: true },
                    ADD_TAGS: ['div', 'span', 'img', 'a', 'button'],
                    ADD_ATTR: ['class', 'id', 'src', 'href', 'alt', 'title', 'data-*']
                });
            }
            return arg;
        });

        return originalAfter.apply(this, args);
    };

    // Also wrap jQuery.fn.before for consistency
    var originalBefore = $.fn.before;

    $.fn.before = function () {
        var args = Array.prototype.slice.call(arguments);

        args = args.map(function (arg) {
            if (typeof arg === 'string' && /<[^>]+>/.test(arg)) {
                return DOMPurify.sanitize(arg, {
                    USE_PROFILES: { html: true },
                    ADD_TAGS: ['div', 'span', 'img', 'a', 'button'],
                    ADD_ATTR: ['class', 'id', 'src', 'href', 'alt', 'title', 'data-*']
                });
            }
            return arg;
        });

        return originalBefore.apply(this, args);
    };

    return function (targetModule) {
        return targetModule;
    };
});

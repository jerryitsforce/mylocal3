/**
 * Security Utility Functions
 * 
 * Provides secure functions for XSS/Code Injection prevention
 * Following contracts defined in specs/002-fix-xss-vulnerabilities/contracts/security-functions.md
 * 
 * @module Branch8_Security/js/security-utils
 */
define([
    'jquery',
    'plugins/DOMPurify'
], function ($, DOMPurify) {
    'use strict';

    /**
     * Validate and sanitize window.location.origin value
     * 
     * @param {string} origin - The origin value to validate
     * @returns {string} Validated origin value
     * @throws {Error} When origin format is invalid
     */
    function validateOrigin(origin) {
        if (typeof origin !== 'string') {
            throw new Error('Origin must be a string');
        }
        const urlPattern = /^https?:\/\/[a-zA-Z0-9.-]+(?::[0-9]+)?$/;
        if (!urlPattern.test(origin)) {
            throw new Error('Invalid origin format');
        }
        return origin;
    }

    /**
     * Encode HTML content safely
     * 
     * @param {string} content - The HTML content to encode
     * @returns {string} Encoded HTML safe content
     */
    function encodeHtmlContent(content) {
        if (typeof content !== 'string') {
            return '';
        }
        // Use jQuery text() method for HTML encoding
        return $('<div>').text(content).html();
    }

    /**
     * Sanitize HTML content using DOMPurify
     * 
     * @param {string} html - The HTML content to sanitize
     * @param {object} config - Optional DOMPurify configuration
     * @returns {string} Sanitized safe HTML
     */
    function sanitizeHtmlContent(html, config) {
        if (typeof html !== 'string') {
            return '';
        }
        const defaultConfig = {
            ALLOWED_TAGS: ['div', 'span', 'p', 'br'],
            ALLOWED_ATTR: ['class', 'id'],
            ALLOWED_URI_REGEXP: /^(?:https?|mailto|tel):/
        };
        const purifyConfig = config || defaultConfig;
        return DOMPurify.sanitize(html, purifyConfig);
    }

    /**
     * Safely set href attribute on element
     * 
     * @param {jQuery} element - jQuery element object
     * @param {string} url - The URL to set
     * @throws {Error} When URL protocol is invalid
     */
    function setSafeHref(element, url) {
        if (typeof url !== 'string') {
            throw new Error('URL must be a string');
        }
        // Validate URL protocol
        if (!url.match(/^(https?|mailto|tel|#|\/)/)) {
            throw new Error('Invalid URL protocol');
        }
        // Use native method to set attribute (jQuery attr may be unsafe)
        if (element && element.length > 0 && element[0]) {
            element[0].setAttribute('href', url);
        } else {
            throw new Error('Invalid element provided');
        }
    }

    /**
     * Encode URL parameter value
     * 
     * @param {string} value - The parameter value to encode
     * @returns {string} Encoded parameter value
     */
    function encodeUrlParameter(value) {
        if (typeof value !== 'string') {
            return '';
        }
        return encodeURIComponent(value);
    }

    /**
     * Safe console log output
     * 
     * @param {any} message - The message to output
     */
    function safeConsoleLog(message) {
        // In production, this should be removed or disabled
        if (typeof message === 'string') {
            // Encode special characters
            message = message.replace(/[<>]/g, function(match) {
                return {'<': '&lt;', '>': '&gt;'}[match];
            });
        }
        console.log(message);
    }

    return {
        validateOrigin: validateOrigin,
        encodeHtmlContent: encodeHtmlContent,
        sanitizeHtmlContent: sanitizeHtmlContent,
        setSafeHref: setSafeHref,
        encodeUrlParameter: encodeUrlParameter,
        safeConsoleLog: safeConsoleLog
    };
});


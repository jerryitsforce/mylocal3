/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/mage',
    'js-cookie/cookie-wrapper'
], function ($) {
    'use strict';

    /**
     * Helper for cookies manipulation
     * @returns {CookieHelper}
     * @constructor
     */
    var CookieHelper = function () {

        /**
         * Maximum length of the cookie string to process.
         */
        var COOKIE_MAX_LENGTH = 81920;

        /**
         * Cookie default values.
         * @type {Object}
         */
        this.defaults = {
            expires: null,
            path: '/',
            domain: null,
            secure: false,
            lifetime: null,
            samesite: 'lax'
        };

        /**
         * Calculate cookie expiration date based on its lifetime.
         * @param {Object} options - Cookie option values
         * @return {Date|null} Calculated cookie expiration date or null if no lifetime provided.
         * @private
         */
        function lifetimeToExpires(options, defaults) {
            var expires,
                lifetime;

            lifetime = options.lifetime || defaults.lifetime;

            if (lifetime && lifetime > 0) {
              expires = options.expires || new Date();
              console.log('lifetimeToExpires', lifetime, expires, defaults, typeof expires, expires instanceof Date);
              if (expires instanceof Date) {
                return new Date(expires.getTime() + lifetime * 1000);
              }
              return null;
            }

            return null;
        }

        /**
         * Set a cookie's value by cookie name based on optional cookie options.
         * @param {String} name - The name of the cookie.
         * @param {String} value - The cookie's value.
         * @param {Object} options - Optional options (e.g. lifetime, expires, path, etc.)
         */
        this.set = function (name, value, options) {
            var expires,
                path,
                domain,
                secure,
                samesite;

            options = $.extend({}, this.defaults, options || {});
            expires = lifetimeToExpires(options, this.defaults) || options.expires;
            path = options.path;
            domain = options.domain;
            secure = options.secure;
            samesite = options.samesite;

            document.cookie = name + '=' + encodeURIComponent(value) +
                (expires && (expires instanceof Date) ? '; expires=' + expires.toUTCString() :  '') +
                (path ? '; path=' + path : '') +
                (domain ? '; domain=' + domain : '') +
                (secure ? '; secure' : '') +
                '; samesite=' + (samesite ? samesite : 'lax') +
                '; HttpOnly';
        };

        /**
         * Get a cookie's value by cookie name.
         * @param {String} name  - The name of the cookie.
         * @return {(null|String)}
         */
        this.get = function (name) {
            var arg = name + '=',
                aLength = arg.length,
                cookie = document.cookie || '',
                i = 0,
                j = 0;

            /**
             * Ensure cookie is a string and restrict its length to prevent potential Denial of Service (CWE-606).
             * COOKIE_MAX_LENGTH (81920) is used as the upper bound for processing.
             */
            cookie = (typeof cookie === 'string') ? cookie : '';

            for (i = 0; i < COOKIE_MAX_LENGTH; ) {
                if (i >= cookie.length) {
                    break;
                }

                j = i + aLength;

                if (cookie.substring(i, j) === arg) {
                    return this.getCookieVal(j);
                }

                i = cookie.indexOf(' ', i);
                if (i === -1) {
                    break;
                }
                i++;
            }

            return null;
        };

        /**
         * Clear a cookie's value by name.
         * @param {String} name - The name of the cookie being cleared.
         */
        this.clear = function (name) {
            if (this.get(name)) {
                this.set(name, '', {
                    expires: new Date('Jan 01 1970 00:00:01 GMT')
                });
            }
        };

        /**
         * Return URI decoded cookie component value (e.g. expires, path, etc.) based on a
         * numeric offset in the document's cookie value.
         * @param {Number} offset - Offset into the document's cookie value.
         * @return {String}
         */
        this.getCookieVal = function (offset) {
            var cookie = document.cookie || '',
                endstr = cookie.indexOf(';', offset);

            if (offset < 0) {
                offset = 0;
            }
            endstr = endstr === -1 ? cookie.length : endstr;

            endstr = Math.min(endstr, COOKIE_MAX_LENGTH);

            return decodeURIComponent(cookie.substring(offset, endstr));
        };

        return this;
    };

    $.extend(true, $, {
        mage: {
            cookies: new CookieHelper()
        }
    });

    return function (pageOptions) {
        $.extend($.mage.cookies.defaults, pageOptions);
        $.extend($.cookie.defaults, $.mage.cookies.defaults);
    };
});

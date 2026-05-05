/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'jquery',
    'mage/utils/wrapper'
], function (_, $, wrapper) {
    'use strict';

    function anyUrlIncludesPath(url, paths) {
        for (var i = 0; i < paths.length; i++) {
            if (url.includes(paths[i])) {
                return true;
            }
        }
        return false;
    }

    var mixin = {

        /**
         *
         * @param originFn
         * @returns {[]|*}
         */
        getExpiredSectionNames: function (originFn) {
            var expiredSections = originFn() || [],
                currentUrl = window.location.href;
            const pathToCheck = ['customer/account', 'customer/account/index'],
                force = anyUrlIncludesPath(currentUrl, pathToCheck);
            if (expiredSections.length || force) {
                expiredSections.push('customer');
            }
            return _.uniq(expiredSections);
        }
    };

    /**
     * Override default customer-data.getExpiredSectionNames().
     */
    return function (target) {
        return wrapper.extend(target, mixin);
    };
});

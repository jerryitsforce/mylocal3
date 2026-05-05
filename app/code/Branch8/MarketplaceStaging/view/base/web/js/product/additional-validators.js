/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([], function () {
    'use strict';

    var validators = [];

    return {
        /**
         * Register unique validator
         *
         * @param {*} validator
         */
        registerValidator: function (validator) {
            validators.push(validator);
        },

        /**
         * Returns array of registered validators
         *
         * @returns {Array}
         */
        getValidators: function () {
            return validators;
        },
        /**
         *
         * @param form
         * @param postData
         * @param hideError
         * @returns {boolean}
         */
        validate: function (form, postData, hideError) {
            var validationResult = true;

            hideError = hideError || false;

            if (validators.length <= 0) {
                return validationResult;
            }

            validators.forEach(async function (item) {
                const validate = isAsync(item.validate) ? await item.validate(form, postData, hideError) : item.validate(form, postData, hideError);
                if (validate === false) { //eslint-disable-line eqeqeq
                    validationResult = false;
                    if (item && typeof item.failCallBack === 'function') {
                        item.failCallBack(form, postData, hideError);
                    }
                    return false;
                }
            });
            return validationResult;
        }
    };

    /**
     *
     * @param fn
     * @returns {boolean}
     */
    function isAsync(fn) {
        return fn.constructor.name === 'AsyncFunction';
    }
});

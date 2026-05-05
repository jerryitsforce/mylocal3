/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'underscore',
    'Magento_Ui/js/grid/filters/elements/ui-select'
], function ($,_, Abstract) {
    'use strict';

    function addIfNotExists(arr, newObj) {
        const exists = arr.some(item => item.value === newObj.value);
        if (!exists) {
            arr.push(newObj);
        }

        return arr;
    }

    return Abstract.extend({
        /**
         * Get selected element labels
         *
         * @returns {Array} array labels
         */
        getSelected: function () {
            var selected = this.value();
            const options = this.cacheOptions.lastOptions ? this.cacheOptions.lastOptions : this.cacheOptions.plain;
            const result = options.filter(function (opt) {
                return _.isArray(selected) ?
                    _.contains(selected, opt.value) :
                    selected === opt.value;//eslint-disable-line eqeqeq
            });
            return result
        },
        /**
         *
         * @param response
         */
        success: function (response) {
            var existingOptions = this.options();
            _.each(response.options, function (opt) {
                const exists = existingOptions.some(item => item.value === opt.value);
                if (!exists) {
                    existingOptions.push(opt);
                }
            });
            this.total = response.total;
            this.cacheOptions.plain = existingOptions;
            if (!this.filterInputValue() && this.cacheOptions.plain.length === 0) {
                this.options(this.cacheOptions.lastOptions);
            } else {
                this.options(this.cacheOptions.plain);
            }
        },
        /**
         * Validate initial value actually exists
         */
        validateInitialValue: function () {
            const self = this;
            if (_.isEmpty(this.value())) {
                this.validationLoading(false);
                return;
            }
            $.ajax({
                url: this.validationUrl,
                type: 'GET',
                dataType: 'json',
                context: this,
                data: {
                    ids: this.value()
                },

                /** @param {Object} response */
                success: function (response) {
                    if (!_.isEmpty(response)) {
                        self.success({
                            options: response
                        });
                    }
                    self.filterChips().updateActive();
                },

                /** set empty array if error occurs */
                error: function () {
                    this.options([]);
                },

                /** stop loader */
                complete: function () {
                    this.validationLoading(false);
                    this.setCaption();
                }
            });
        }
    });
});

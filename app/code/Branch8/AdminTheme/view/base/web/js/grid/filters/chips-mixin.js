define([
    'jquery'
], function ($) {
    'use strict';

    return function (OriginalComponent) {
        return OriginalComponent.extend({
            defaults: {
                template: 'Branch8_AdminTheme/grid/filters/chips'
            },

            /**
             * Override for better compatibility.
             *
             * @return {string}
             */
            getTemplate: function () {
                this._super();

                return this.template;
            },

            /**
             * Returns active filters label.
             *
             * @returns {string}
             */
            getActiveFiltersLabel: function () {
                return $.mage.__('Active filters:');
            }
        });
    };
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select'
], function (_, registry, Select) {
    'use strict';

    return Select.extend({
        defaults: {
            skipValidation: false,
            elementTmpl: 'Branch8_RmaAdminUi/form/taiwant-cities',
            imports: {
                regionOptions: '${ $.parentName }.rma_region:options',
                update: '${ $.parentName }.rma_region:value'
            }
        },

        /**
         * {@inheritdoc}
         */
        initialize: function () {
            var option;
            this._super();

            return this;
        },

        /**
         * Method called every time country selector's value gets changed.
         * Updates all validations and requirements for certain country.
         * @param {String} value - Selected country ID.
         */
        update: function (value) {
            let citiOptions = [];
            this.value('');
            if (!value || !_.isObject(this.regionOptions)) {
                this.disabled(true);
                return;
            }
            this.disabled(false);
            const options = this.regionOptions.filter(item => item.value === value);
            _.each(options[0].cities, function (city) {
                citiOptions.push({"value": city.name, "label": city.name});
            })
            this.setOptions(citiOptions || []);
        },

        /**
         * Hide select and corresponding text input field if region must not be shown for selected country.
         *
         * @private
         * @param {Object}option
         */
        hideRegion: function (option) {
            if (!option || option['is_region_visible'] !== false) {
                return;
            }

            this.setVisible(false);

            if (this.customEntry) {
                this.toggleInput(false);
            }
        }
    });
});

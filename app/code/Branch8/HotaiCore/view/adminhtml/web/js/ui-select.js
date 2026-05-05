/**
 * Copyright © Branch8. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/ui-select'
], function (_, UiSelect) {
    'use strict';

    return UiSelect.extend({
        defaults: {
            disabledValues: []
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super();
            this.observe(['disabledValues']);
            return this;
        },

        /**
         * Check if value is disabled
         *
         * @param {String} value
         * @returns {Boolean}
         */
        isDisabledValue: function (value) {
            return _.contains(this.disabledValues(), value);

        },

        /**
         * @inheritdoc
         */
        toggleOptionSelected: function (data) {
            if (this.isDisabledValue(data.value)) {
                return this;
            }
            return this._super(data);
        },

        /**
         * @inheritdoc
         */
        isLabelDecoration: function (data) {
            return this._super(data) || this.isDisabledValue(data.value);
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            return this;
        }
    });
});

define([
    "uiComponent",
    "maskRuleManagement"
], function (Component, maskRuleManagement) {
    'use strict';
    return Component.extend({
        template: "Magento_Customer/default-address",
        defaults: {
            template: "Branch8_MaskCustomerInformation/default-address",
        },
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super();
            return this;
        },
        /**
         *
         * @returns {boolean}
         */
        canView: function () {
            return this.source.canView === true;
        },
        /**
         *
         * @param code
         * @param value
         * @returns {*}
         */
        mask: function (code, value) {
            if (this.canView()) {
                return value;
            }
            return maskRuleManagement(
                code,
                value
            )
        }
    });
})

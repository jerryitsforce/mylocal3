define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry'
], function (Abstract, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            imports: {
                toggleVisibility: '${ $.parentName }.logistics_company_id:value'
            }
        },

        /**
         * Toggle visibility based on logistics company selection
         *
         * @param {String} value
         */
        toggleVisibility: function (value) {
            // Show only when HCT (value = '1') is selected
            if (value === '1') {
                this.visible(true);
            } else {
                this.visible(false);
            }
        }
    });
});

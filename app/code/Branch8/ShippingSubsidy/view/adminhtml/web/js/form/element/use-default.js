define([
    'Magento_Ui/js/form/element/single-checkbox',
    'uiRegistry'
], function (Checkbox, registry) {
    'use strict';

    return Checkbox.extend({
        defaults: {
            listens: {
                'checked': 'onCheckedChanged toggleTarget'
            }
        },
        /**
         *
         * @param checked
         */
        toggleTarget: function (checked) {
            if (this.targetField) {
                var self = this;
                registry.async(this.targetField)(function (component) {
                    if (component && component.disabled !== undefined) {
                        component.disabled(checked);
                    }
                    if (checked && self.defaultValue !== undefined) {
                        component.value(self.defaultValue);
                    }
                });

            }
        }
    });
});

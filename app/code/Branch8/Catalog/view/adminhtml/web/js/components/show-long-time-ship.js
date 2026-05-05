define([
    'Magento_Ui/js/form/element/single-checkbox',
    'uiRegistry',
    'ko'
], function (Select, registry, ko) {
    'use strict';

    return Select.extend({
        initialize: function () {
            this._super();

            this.value.subscribe(this.toggleFieldVisibility.bind(this));
            this.toggleFieldVisibility(this.value());

            return this;
        },

        toggleFieldVisibility: function (value) {
            var attributeBField = registry.get('index = long_time_ship');

            if (attributeBField) {
                if (value === '1') {
                    attributeBField.visible(true);
                } else {
                    attributeBField.visible(false);
                }
            }
        }
    });
});

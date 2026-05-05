define([
    'ko',
    'jquery',
    'Magento_Ui/js/form/element/select',
    'mage/translate',
    'mage/validation'
], function (ko, $, Component, $t) {
    'use strict';

    return Component.extend({
        // inputName : 'order_note',
        // errorMessage: ko.observable(''),

        initialize: function() {
            this._super();
            const options = this.options();
            const filteredOptions = options.filter(option => option.value === 'TW');
            this.setOptions(filteredOptions);
            return this;
        },

        initObservable: function () {
            this._super();
            const options = this.options();
            const filteredOptions = options.filter(option => option.value === 'TW');
            this.setOptions(filteredOptions);
            this.initialOptions = filteredOptions;

            this.observe('options caption')
                .setOptions(this.options());

            return this;
        }
    });
});

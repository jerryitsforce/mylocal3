define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Ui/js/form/element/abstract',
    'mage/translate',
    'mage/validation'
], function ($, ko, Component, Abstract, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            // template: 'Vendor_Module/form/element/address',
            imports: {
                initialValue: '${ $.provider }:${ $.dataScope }'
            },
            exports: {
                value: '${ $.provider }:${ $.dataScope }'
            }
        },

        initialize: function () {
            this._super();
            this.initObservable();
            return this;
        },

        initObservable: function () {
            this._super().observe('value');
            return this;
        },

        validate: function () {
            var value = this.value();
            var isValid = true;

            if (!value) {
                isValid = false;
                this.error($t('This is a required field.'));
            } else if (!/[^\u0000-\u007F\u4E00-\u9FFF]|[!@#$%^&*(),.?":{}|<>]/.test(value)) {
                isValid = false;
                this.error($t('請勿輸入全形文字以及特殊字元'));
            } else {
                this.error('');
            }

            return isValid;
        }
    });
});
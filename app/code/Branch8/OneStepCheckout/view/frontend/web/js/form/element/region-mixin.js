define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Ui/js/lib/validation/validator',
    'mage/translate',
    'mage/validation'
], function ($, ko, Component, Abstract, validator, $t) {
    'use strict';

    const mixin = {
        initialize: function () {
            this._super();
            this.validation['required-entry'] = true;
            return this;
        },

        afterRender: function () {
            if(!this.value()) {
                $('#'+this.uid).addClass('empty');
            } else {
                $('#'+this.uid).removeClass('empty');
            }
            $(document).on('click', '#'+this.uid , $.proxy(function (e) {
                $(this).removeClass('empty');
            }));
        },

        onUpdate: function (newValue) {
            // console.log('onUpdate', newValue);
            this._super();
            if(!newValue) {
                $('#'+this.uid).addClass('empty');
            } else {
                $('#'+this.uid).removeClass('empty');
            }
        },

        validate: function () {
            var value = this.value(),
                result = validator(this.validation, value, this.validationParams),
                message =  result.message,
                isValid = this.disabled() || result.passed;

            this.error(message);
            this.error.valueHasMutated();
            this.bubble('error', message);

            if (this.source && !isValid) {
                this.source.set('params.invalid', true);
                if(this.hasChanged()) {
                    $('#'+this.uid).addClass('mage-error');
                } else {
                    $('#'+this.uid).removeClass('mage-error');
                }
            } else {
                $('#'+this.uid).removeClass('mage-error');
            }

            return {
                valid: isValid,
                target: this
            };
        },
    }

    return function (target) { 
        return target.extend(mixin);
    };
});
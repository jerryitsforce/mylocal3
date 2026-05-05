define([
    'ko',
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Ui/js/lib/validation/validator',
    'mage/translate',
    'mage/validation'
], function (ko, $, Component, validator, $t) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();
            return this;
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
        }
    });
});

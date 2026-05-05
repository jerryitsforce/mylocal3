
/**
 * @api
 */
define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'Magento_Ui/js/lib/validation/validator'
], function ($, ko, Element, registry, validator) {
    'use strict';

    return Element.extend({
        defaults: {
            imports: {
                updateRegionSelect: '${ $.parentName }.region_id:value',
                regionId: '${ $.parentName }.region_id'
            },
            options: []
        },

        /**
         * Validates itself by it's validation rules using validator object.
         * If validation of a rule did not pass, writes it's message to
         * 'error' observable property.
         *
         * @returns {Object} Validate information.
         */
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
            }

            return {
                valid: isValid,
                target: this
            };
        },

        /**
         *
         * @param {string} regionId
         */
        updateRegionSelect: function (regionId) {
            const regionSelect = registry.get(this.imports.regionId);
            if(regionSelect?.uid) {
                const regionTitle = $('#'+regionSelect.uid+' option[value='+regionId+']').data('title');
                if (regionTitle || regionTitle === '') {
                    this.value(regionTitle);
                }
            }
        },
    });
});

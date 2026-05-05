/**
 * @api
 */
define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'Magento_Ui/js/lib/validation/validator',
    'mage/translate',
    'mage/validation'
], function ($, ko, Element, registry, validator, $t) {
    'use strict';

    return Element.extend({
        defaults: {
            imports: {
                update: '${ $.parentName }.region_id:value',
                city: '${ $.parentName }.city'
            },
            options: [{title: "", value: "", label: $t("鄉鎮市區")}],
            visible: true
        },

        initialize: function () {
            this._super();
            this.validation['required-entry'] = false;

            if (this.name.includes('steps.billing-step')) {
                this.visible(false)
            }
        },
        
        afterRender: function () {
            if(!this.value()) {
                $('#'+this.uid).addClass('empty');
            } else {
                $('#'+this.uid).removeClass('empty');
            }
            $(document).on('click', '#'+this.uid, $.proxy(function (e) {
                $(this).removeClass('empty');
            }));
        },

        onUpdate: function (newValue) {
            const regionValue =  $('#'+this.uid).parents('.fieldset').find('select[name="region_id"]').val();
            console.log('onUpdate', newValue, regionValue);
            const unsupported = [{regionId: '1174', region: '臺東縣', city: '綠島鄉'}, {regionId: '1174', region: '臺東縣', city: '蘭嶼鄉'}, {regionId: '1173', region: '屏東縣', city: '琉球鄉'}];

            const found = unsupported.find(
                item => item.regionId === regionValue && item.city === newValue
            );

            if (found) {
                this.value(null);
                this.error($t('不提供外島配送，不讓顧客下單。'));
                this.error.valueHasMutated();
                return;
            } else {
                this._super();
                if(!newValue) {
                    $('#'+this.uid).addClass('empty');
                } else {
                    $('#'+this.uid).removeClass('empty');
                }
            }
        },
        
        /**
         * On region update we check for city
         *
         * @param {string} regionId
         */
        update: function (regionId) {
            console.log('update', regionId);
            let options = [],
                cityValue,
                cities,
                regions = JSON.parse(window.checkoutConfig.cities);

            if (regions && regions[regionId] && regions[regionId].length) {
                cities = regions[regionId];

                options = cities.map(function (city) {
                    return {title: city, value: city, labeltitle: city, label: city}
                })
            }

            if (!options || !options.length) {
                this.visible(true);
                this.value(null);
            }

            if (options && options.length) {
                options = [].concat(options);
                this.visible(true);

                cityValue = registry.get(this.imports.city).value();

                if (!this.value() && cityValue) {
                    this.value(cityValue)
                }
            }

            this.options(options);
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
    });
});

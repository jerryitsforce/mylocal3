define(
    [
      'jquery',
      'Magento_Ui/js/form/element/region',
      'uiRegistry',
      'Magento_Ui/js/lib/validation/validator'
    ],
    function (
        $,
        Component,
        registry,
        validator
    ) {
        'use strict';

        return Component.extend({
            /**
             * Set default region value
             */
            initialize: function (config) {
                this._super();

                if (window.checkoutConfig.amdefault && window.checkoutConfig.amdefault.region) {
                    registry.get(this.parentName + '.' + 'region_id_input', function (region) {
                        if (!region.value() ) {
                            var country = registry.get(this.parentName + '.' + 'country_id');
                            if (country.value() == window.checkoutConfig.amdefault.country_id) {
                                region.value(window.checkoutConfig.amdefault.region);
                            }
                        }
                    }.bind(this));
                }

                this.validation['required-entry'] = false;

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
                console.log('onUpdate', newValue);
                if(!newValue) {
                    $('#'+this.uid).addClass('empty').addClass('empty2');
                } else {
                    $('#'+this.uid).removeClass('empty').removeClass('empty2');
                }
                this._super();
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
    }
);

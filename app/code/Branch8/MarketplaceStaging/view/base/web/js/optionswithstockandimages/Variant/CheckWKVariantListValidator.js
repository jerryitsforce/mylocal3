define(
    [
        'jquery',
        './Validate',
        './List'
    ],
    function ($, Validate, VariantList) {
        'use strict';

        return {
            /**
             *
             * @returns {boolean}
             */
            validate: function () {
                let isValid = true;
                let form = $("#wk-variation-form");
                if (!form.length) {
                    return isValid;
                }
                
                let hasCustomOptions = false;
                // For admin (UI component)
                if ($('div[data-index="custom_options"] table[data-index="options"] > tbody > tr').length > 0) {
                    hasCustomOptions = true;
                } 
                // For seller dashboard
                else if ($('#product_options_container').length > 0) {
                    $('#product_options_container').find('div[id^="option_"], .fieldset-wrapper, .field-option').each(function() {
                        let isDeleted = $(this).find('input[name$="[is_delete]"]').val();
                        if ($(this).is(':visible') && isDeleted !== '1' && isDeleted !== 'true') {
                            hasCustomOptions = true;
                            return false; 
                        }
                    });
                } else {
                    hasCustomOptions = false;
                }
                
                if (!hasCustomOptions) {
                    return isValid;
                }

                return Validate(form);
            },
            /**
             *
             */
            failCallBack: function () {
                var headerHeight = 10;
                $('html, body').animate({
                    scrollTop: $('#add_wkvariations_button').offset().top - 300
                }, 500);
                VariantList.initValidator()
                    .open().setWeight();
            }
        }
    }
);

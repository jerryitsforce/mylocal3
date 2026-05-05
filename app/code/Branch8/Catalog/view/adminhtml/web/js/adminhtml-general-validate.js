require([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/lib/validation/validator'
], function ($, $t, validator) {
    'use strict';

    /**
     *
     * @param elements
     * @param type
     * @returns {boolean}
     */
    function validateElements(elements, type) {
        let isValid = true;
        if (!elements || elements.length === 0) {
            return isValid
        }
        elements.each(function (index, element) {
            const validate = $.validator.validateSingleElement($(element));
            if (!validate) {
                isValid = false;
            }
        });
        return isValid
    }

    validator.addRule(
        'product-special-price-validation',
        function (value) {

            let productPriceElm = '.page-content input[name="product[price]"]';
            let productSpecialPriceElm = '.page-content input[name="product[special_price]"]';
            let productPrice = $(productPriceElm).val();
            let productSpecialPrice = $(productSpecialPriceElm).val();

            let stagingProductPriceElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[price]"]';
            let stagingProductSpecialPriceElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[special_price]"]';


            if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').hasClass('_show')) {
                productPrice = $(stagingProductPriceElm).val();
                productSpecialPrice = $(stagingProductSpecialPriceElm).val();
            }

            if (parseInt(productPrice) < parseInt(productSpecialPrice)) {
                return false;
            }

            return true;
        }
        , $.mage.__('Special price must be less than price.')
    );

    validator.addRule("sku-unique", function (value) {
        let timeRepeated = 0;
        if (value != '') {
            $('input[name^="product[options]"][name*="[values]"][name$="[sku]"]').each(function () {
                if ($(this).val() === value) {
                    timeRepeated++;
                }
            });
        }
        return timeRepeated === 1 || timeRepeated === 0;

    }, $.mage.__('Duplicate SKU found.'));

    validator.addRule(
        'variation-fields-required',
        function (value, element) {
            let isValid = true;
            const form = $("#wk-variation-form"),
                button = $("[data-index=\"wkvariations\"]");
            if (!form.length) {
                return isValid;
            }

            if (!$('.page-content div[data-index="custom_options"] table[data-index="options"] > tbody > tr').length) {
                return isValid;
            }

            var isValidCost = validateElements(form.find('input.wkv-cost'), 'cost'),
                isValidPrice = validateElements(form.find('input.wkv-price'), 'price'),
                isValidSkus = validateElements(form.find('input.wkv-sku'), 'sku'),
                isValidStock = validateElements(form.find('input.wkv-stock'), 'stock');
            if (isValidCost && isValidPrice && isValidSkus && isValidStock) {
                isValid = true;
            } else {
                isValid = false;
            }
            if (!isValid && button.length) {
                button.trigger('click');
            }
            return isValid;
        }, $.mage.__('Please ensure that all the required fields have been filled in: Cost, Stock, SKU, Price and Commission Rate.'),
    );

    validator.addRule("variation-required", function (value) {
        let haveOptionChanged = false,
            variationCount = 0,
            dataRealCount = 0;
        if ($('.page-content div[data-index="custom_options"] table[data-index="options"] > tbody > tr').length) {
            if ($('body').find('[name="product[wk_manage_variation]"]').length) {
                let variationData = $('body').find('[name="product[wk_manage_variation]"]').val();
                variationData = variationData.match(/(?<=wkvariation\[).+?(?=\]\[)/g);
                variationData = [...new Set(variationData)];
                if (variationData.length) {
                    variationCount = variationData.length;
                }
            } else {
                if (window.variationSavedData != '{}') {
                    variationCount = window.variationSavedData.length;
                }
            }
            $('.page-content div[data-index="custom_options"] table[data-index="options"] > tbody > tr').each(function () {
                let dataRealCountSub = 0;
                if ($(this).find('table[data-index="values"] > tbody > tr').length) {
                    $(this).find('table[data-index="values"] > tbody > tr').each(function () {
                        dataRealCountSub++;
                    });
                }
                if (!dataRealCount) {
                    dataRealCount = 1;
                }
                dataRealCount = dataRealCount * dataRealCountSub;
            });
            if (dataRealCount !== variationCount) {
                haveOptionChanged = true;
            }
        }
        return !haveOptionChanged;

    }, $.mage.__('Please ensure that all the required fields have been filled in: Cost, Stock, SKU, Price and Commission Rate.'));

});

define(['jquery', "mage/translate", 'mage/validation'], function ($, $t) {
    'use strict';

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

    /**
     *
     */
    return function (formElement) {
        const form = formElement ? formElement : $('#wk-variation-form');
        var isValidCost = validateElements(form.find('input.wkv-cost'), 'cost'),
            isValidPrice = validateElements(form.find('input.wkv-price'), 'price'),
            isValidSkus = validateElements(form.find('input.wkv-sku'), 'sku'),
            isValidStock = validateElements(form.find('input.wkv-stock'), 'stock');
        if (isValidCost && isValidPrice && isValidSkus && isValidStock) {
            return true;
        }
        return false;
    }
});

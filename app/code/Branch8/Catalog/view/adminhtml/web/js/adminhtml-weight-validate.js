require([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/lib/validation/validator'
], function($, $t, validator) {
    'use strict';

    validator.addRule(
        'product-weight-validation',
        function (value) {
            if (window.productTypeId === 'simple'){
                if (!value) {
                    return false;
                }
                return parseFloat(value) > 0;
            }

            return true;
        }, $.mage.__('This is a required field and must be a number greater than 0.')
    );
});

require([
    'jquery',
    'Magento_Ui/js/lib/validation/validator'
], function ($, validator) {
    'use strict';

    let qtyFieldPath = '.page-content input[name="product[quantity_and_stock_status][qty]"]';
    let is_in_stockFieldPath = '.page-content select[name="product[quantity_and_stock_status][is_in_stock]"]';
    jQuery(document).ajaxComplete(function () {
        jQuery(qtyFieldPath).attr('disabled', 'disabled');
        jQuery(qtyFieldPath).attr('readonly', 'readonly');
        jQuery(is_in_stockFieldPath).attr('disabled', 'disabled');
        jQuery(is_in_stockFieldPath).attr('readonly', 'readonly');
    });
});

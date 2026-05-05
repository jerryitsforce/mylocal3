require([
    'jquery'
], function($) {
    'use strict';

    let limit_purchased_enable = 'input[name="product[limit_purchased_enable]"]';
    let limit_purchased_customer_group = 'select[name="product[limit_purchased_customer_group]"]';
    let limit_purchased_qty = 'input[name="product[limit_purchased_qty]"]';
    let limit_purchased_start_time = 'input[name="product[limit_purchased_start_time]"]';
    let limit_purchased_end_time = 'input[name="product[limit_purchased_end_time]"]';
    let limit_purchased_enable_staging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[limit_purchased_enable]"]';

    jQuery(document).ajaxComplete(function() {
        let is_enable = $(limit_purchased_enable).val() == 1;
        let is_enable_staging = $(limit_purchased_enable_staging).val() == 1;
        toggleLimitPurchased(is_enable, 'edit');
        toggleLimitPurchased(is_enable_staging, 'staging');

        $(limit_purchased_enable).on('change', function() {
            let is_enable = $(limit_purchased_enable).val() == 1;
            toggleLimitPurchased(is_enable, 'edit');
        });
        $(limit_purchased_enable_staging).on('change', function() {
            let is_enable_staging = $(limit_purchased_enable_staging).val() == 1;
            toggleLimitPurchased(is_enable_staging, 'staging');
        });
    });

    function toggleLimitPurchased(is_enable, mode) {
        var prefix = '.page-content ';
        if(mode === 'staging'){
            prefix = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal ';
        }
        $(prefix+limit_purchased_customer_group).parent().parent().toggle(is_enable);
        $(prefix+limit_purchased_qty).parent().parent().toggle(is_enable);
        $(prefix+limit_purchased_start_time).parent().parent().toggle(is_enable);
        $(prefix+limit_purchased_end_time).parent().parent().toggle(is_enable);
    }
});

require([
    'jquery',
    'mage/translate'
], function($, $t) {
    'use strict';

    let preorder = '#wk_marketplace_preorder';
    let preorder_staging = '#staging_wk_marketplace_preorder';
    let preorder_mode = '#field_preorder_mode select[name="product[preorder_mode]"]';
    let preorder_mode_staging = '#staging_field_preorder_mode select[name="product[preorder_mode]"]';

    jQuery(document).ajaxComplete(function() {
        $(preorder).on('click', function() {
            if ($(preorder).is(':checked')) {
                $('#field_preorder_mode').toggle(true);
                $('#field_preorder_x_days').toggle(true);
                $('#field_preorder_start_date').toggle(true);
                $('#field_preorder_end_date').toggle(true);
                $('#field_preorder_ship_date').toggle(true);
                $('#field_wk_marketplace_availability').toggle(true);
                $('#field_preorder_use_qty').toggle(true);
                $('#field_wk_mppreorder_qty').toggle(true);
                togglePreorder($(preorder_mode).val(), 'edit');
            } else {
                $('#field_preorder_mode').toggle(false);
                $('#field_preorder_x_days').toggle(false);
                $('#field_preorder_start_date').toggle(false);
                $('#field_preorder_end_date').toggle(false);
                $('#field_preorder_ship_date').toggle(false);
                $('#field_wk_marketplace_availability').toggle(false);
                $('#field_preorder_use_qty').toggle(false);
                $('#field_wk_mppreorder_qty').toggle(false);
            }
        });
        $(preorder_staging).on('click', function() {
            if ($(preorder_staging).is(':checked')) {
                $('#staging_field_preorder_mode').toggle(true);
                $('#staging_field_preorder_x_days').toggle(true);
                $('#staging_field_preorder_start_date').toggle(true);
                $('#staging_field_preorder_end_date').toggle(true);
                $('#staging_field_preorder_ship_date').toggle(true);
                $('#staging_field_wk_marketplace_availability').toggle(true);
                $('#staging_field_preorder_use_qty').toggle(true);
                $('#staging_field_wk_mppreorder_qty').toggle(true);
                togglePreorder($(preorder_mode_staging).val(), 'staging');
            } else {
                $('#staging_field_preorder_mode').toggle(false);
                $('#staging_field_preorder_x_days').toggle(false);
                $('#staging_field_preorder_start_date').toggle(false);
                $('#staging_field_preorder_end_date').toggle(false);
                $('#staging_field_preorder_ship_date').toggle(false);
                $('#staging_field_wk_marketplace_availability').toggle(false);
                $('#staging_field_preorder_use_qty').toggle(false);
                $('#staging_field_wk_mppreorder_qty').toggle(false);
            }
        });
        if ($(preorder).is(':checked')) {
            togglePreorder($(preorder_mode).val(), 'edit');
        } else {
            $('#field_preorder_mode').toggle(false);
            $('#field_preorder_x_days').toggle(false);
            $('#field_preorder_start_date').toggle(false);
            $('#field_preorder_end_date').toggle(false);
            $('#field_preorder_ship_date').toggle(false);
            $('#field_wk_marketplace_availability').toggle(false);
            $('#field_preorder_use_qty').toggle(false);
            $('#field_wk_mppreorder_qty').toggle(false);
        }
        if ($(preorder_staging).is(':checked')) {
            togglePreorder($(preorder_mode_staging).val(), 'staging');
        } else {
            $('#staging_field_preorder_mode').toggle(false);
            $('#staging_field_preorder_x_days').toggle(false);
            $('#staging_field_preorder_start_date').toggle(false);
            $('#staging_field_preorder_end_date').toggle(false);
            $('#staging_field_preorder_ship_date').toggle(false);
            $('#staging_field_wk_marketplace_availability').toggle(false);
            $('#staging_field_preorder_use_qty').toggle(false);
            $('#staging_field_wk_mppreorder_qty').toggle(false);
        }


        $(preorder_mode).on('change', function() {
            let preorderMode = $(preorder_mode).val();
            var editType = 'edit';
            togglePreorder(preorderMode, editType);
        });
        $(preorder_mode_staging).on('change', function() {
            let preorderMode = $(preorder_mode_staging).val();
            var editType = 'staging';
            togglePreorder(preorderMode, editType);
        })
    });

    function togglePreorder(preorderMode, editType) {
        var prefix = '';
        if(editType == 'staging'){
            prefix = 'staging_';
        }
        var preorderModeInt = parseInt(preorderMode, 10);
        if(preorderModeInt == 1) {
            $('#'+prefix+'field_preorder_start_date').toggle(true);
            $('#'+prefix+'field_wk_marketplace_availability').toggle(true);
            $('#'+prefix+'field_wk_mppreorder_qty').toggle(true);
            $('#'+prefix+'field_preorder_use_qty').toggle(true);

            $('#'+prefix+'field_preorder_x_days').toggle(false);
            $('#'+prefix+'field_preorder_ship_date').toggle(false);
        }else if(preorderModeInt == 2){
            $('#'+prefix+'field_preorder_x_days').toggle(true);

            $('#'+prefix+'field_preorder_start_date').toggle(false);
            $('#'+prefix+'field_wk_marketplace_availability').toggle(false);
            $('#'+prefix+'field_preorder_ship_date').toggle(false);
            $('#'+prefix+'field_wk_mppreorder_qty').toggle(false);
            $('#'+prefix+'field_preorder_use_qty').toggle(false);
        }else if(preorderModeInt == 3){
            $('#'+prefix+'field_preorder_ship_date').toggle(true);

            $('#'+prefix+'field_preorder_x_days').toggle(false);
            $('#'+prefix+'field_preorder_start_date').toggle(false);
            $('#'+prefix+'field_wk_marketplace_availability').toggle(false);
            $('#'+prefix+'field_wk_mppreorder_qty').toggle(false);
            $('#'+prefix+'field_preorder_use_qty').toggle(false);
        }
    }
});

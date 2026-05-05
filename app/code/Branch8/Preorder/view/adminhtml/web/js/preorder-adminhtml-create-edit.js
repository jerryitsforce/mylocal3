require([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/lib/validation/validator'
], function($, $t, validator) {
    'use strict';

    let preorder = '.page-content select[name="product[wk_marketplace_preorder]"]';
    let preorder_staging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[wk_marketplace_preorder]"]';
    let preorder_mode = '.page-content select[name="product[preorder_mode]"]';
    let preorder_mode_staging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[preorder_mode]"]';

    let preorder_start_date = 'input[name="product[preorder_start_date]"]';
    let preorder_end_date = 'input[name="product[preorder_end_date]"]';
    let preorder_available_date = 'input[name="product[wk_marketplace_availability]"]';
    let preorder_x_days = 'input[name="product[preorder_x_days]"]';
    let preorder_ship_date = 'input[name="product[preorder_ship_date]"]';
    let preorder_max_qty = 'input[name="product[wk_mppreorder_qty]"]';
    let preorder_use_qty = 'input[name="product[preorder_use_qty]"]';

    jQuery(document).ajaxComplete(function() {
        $(preorder).on('change', function() {
            let prefix = '.page-content ';
            if ($(preorder).find('option:selected').text() &&
                ($(preorder).find('option:selected').text().trim() == $t('Enable')
                    || $(preorder).find('option:selected').text().trim() == 'Enable')) {
                $(preorder_mode).parent().parent().toggle(true);
                $(prefix+preorder_x_days).parent().parent().toggle(true);
                $(prefix+preorder_start_date).parent().parent().toggle(true);
                $(prefix+preorder_end_date).parent().parent().toggle(true);
                $(prefix+preorder_ship_date).parent().parent().toggle(true);
                $(prefix+preorder_available_date).parent().parent().toggle(true);
                $(prefix+preorder_use_qty).parent().parent().parent().toggle(true);
                $(prefix+preorder_max_qty).parent().parent().toggle(true);
                togglePreorder($(preorder_mode).val(), 'edit');
            } else {
                $(preorder_mode).parent().parent().toggle(false);
                $(prefix+preorder_x_days).parent().parent().toggle(false);
                $(prefix+preorder_start_date).parent().parent().toggle(false);
                $(prefix+preorder_end_date).parent().parent().toggle(false);
                $(prefix+preorder_ship_date).parent().parent().toggle(false);
                $(prefix+preorder_available_date).parent().parent().toggle(false);
                $(prefix+preorder_use_qty).parent().parent().parent().toggle(false);
                $(prefix+preorder_max_qty).parent().parent().toggle(false);
            }
        });
        $(preorder_staging).on('change', function() {
            let prefix = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal ';
            if ($(preorder_staging).find('option:selected').text() &&
                ($(preorder_staging).find('option:selected').text().trim() == $t('Enable')
                    || $(preorder_staging).find('option:selected').text().trim() == 'Enable')) {
                $(preorder_mode_staging).parent().parent().toggle(true);
                $(prefix+preorder_x_days).parent().parent().toggle(true);
                $(prefix+preorder_start_date).parent().parent().toggle(true);
                $(prefix+preorder_end_date).parent().parent().toggle(true);
                $(prefix+preorder_ship_date).parent().parent().toggle(true);
                $(prefix+preorder_available_date).parent().parent().toggle(true);
                $(prefix+preorder_use_qty).parent().parent().parent().toggle(true);
                $(prefix+preorder_max_qty).parent().parent().toggle(true);
                togglePreorder($(preorder_mode_staging).val(), 'staging');
            } else {
                $(preorder_mode_staging).parent().parent().toggle(false);
                $(prefix+preorder_x_days).parent().parent().toggle(false);
                $(prefix+preorder_start_date).parent().parent().toggle(false);
                $(prefix+preorder_end_date).parent().parent().toggle(false);
                $(prefix+preorder_ship_date).parent().parent().toggle(false);
                $(prefix+preorder_available_date).parent().parent().toggle(false);
                $(prefix+preorder_use_qty).parent().parent().parent().toggle(false);
                $(prefix+preorder_max_qty).parent().parent().toggle(false);
            }
        });
        if ($(preorder).find('option:selected').text() &&
            ($(preorder).find('option:selected').text().trim() == $t('Enable')
                || $(preorder).find('option:selected').text().trim() == 'Enable')) {
            togglePreorder($(preorder_mode).val(), 'edit');
        } else {
            let prefix = '.page-content ';
            $(preorder_mode).parent().parent().toggle(false);
            $(prefix+preorder_x_days).parent().parent().toggle(false);
            $(prefix+preorder_start_date).parent().parent().toggle(false);
            $(prefix+preorder_end_date).parent().parent().toggle(false);
            $(prefix+preorder_ship_date).parent().parent().toggle(false);
            $(prefix+preorder_available_date).parent().parent().toggle(false);
            $(prefix+preorder_use_qty).parent().parent().parent().toggle(false);
            $(prefix+preorder_max_qty).parent().parent().toggle(false);
        }
        if ($(preorder_staging).find('option:selected').text() &&
            ($(preorder_staging).find('option:selected').text().trim() == $t('Enable')
                || $(preorder_staging).find('option:selected').text().trim() == 'Enable')) {
            togglePreorder($(preorder_mode_staging).val(), 'staging');
        } else {
            let prefix = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal ';
            $(preorder_mode_staging).parent().parent().toggle(false);
            $(prefix+preorder_x_days).parent().parent().toggle(false);
            $(prefix+preorder_start_date).parent().parent().toggle(false);
            $(prefix+preorder_end_date).parent().parent().toggle(false);
            $(prefix+preorder_ship_date).parent().parent().toggle(false);
            $(prefix+preorder_available_date).parent().parent().toggle(false);
            $(prefix+preorder_use_qty).parent().parent().parent().toggle(false);
            $(prefix+preorder_max_qty).parent().parent().toggle(false);
        }

        $(preorder_mode).on('change', function() {
            let preorderMode = $(preorder_mode).val();
            togglePreorder(preorderMode, 'edit');
        });
        $(preorder_mode_staging).on('change', function() {
            let preorderModeStagging = $(preorder_mode_staging).val();
            togglePreorder(preorderModeStagging, 'staging');
        });
    });

    function togglePreorder(preorderMode, mode) {
        var preorderModeInt = parseInt(preorderMode, 10);
        var prefix = '.page-content ';
        if(mode == 'staging'){
            prefix = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal ';
        }
        //Start/ end date
        if(preorderModeInt == 1) {
            $(prefix+preorder_start_date).parent().parent().toggle(true);
            $(prefix+preorder_available_date).parent().parent().toggle(true);
            $(prefix+preorder_max_qty).parent().parent().toggle(true);
            $(prefix+preorder_use_qty).parent().parent().parent().toggle(true);

            $(prefix+preorder_x_days).parent().parent().toggle(false);
            $(prefix+preorder_ship_date).parent().parent().toggle(false);
            //xDays
        }else if(preorderModeInt == 2){
            $(prefix+preorder_x_days).parent().parent().toggle(true);

            $(prefix+preorder_start_date).parent().parent().toggle(false);
            $(prefix+preorder_available_date).parent().parent().toggle(false);
            $(prefix+preorder_ship_date).parent().parent().toggle(false);
            $(prefix+preorder_max_qty).parent().parent().toggle(false);
            $(prefix+preorder_use_qty).parent().parent().parent().toggle(false);
            //Ship date
        }if(preorderModeInt == 3){
            $(prefix+preorder_ship_date).parent().parent().toggle(true);

            $(prefix+preorder_x_days).parent().parent().toggle(false);
            $(prefix+preorder_start_date).parent().parent().toggle(false);
            $(prefix+preorder_available_date).parent().parent().toggle(false);
            $(prefix+preorder_max_qty).parent().parent().toggle(false);
            $(prefix+preorder_use_qty).parent().parent().parent().toggle(false);
        }
    }
    let preorderStatusElm = '.page-content select[name="product[wk_marketplace_preorder]"] option:selected';
    let preorderModeElm = '.page-content select[name="product[preorder_mode]"]';
    let preorderStartDateElm = '.page-content input[name="product[preorder_start_date]"]';
    let preorderAvailDateElm = '.page-content input[name="product[wk_marketplace_availability]"]';
    let preorderEndDateElm = '.page-content input[name="product[preorder_end_date]"]';
    let preorderShipDateElm = '.page-content input[name="product[preorder_ship_date]"]';
    let preorderXDayElm = '.page-content input[name="product[preorder_x_days]"]';
    let preorderMaxQtyElm = '.page-content input[name="product[wk_mppreorder_qty]"]';
    let preorderUseMaxQtyElm = '.page-content input[name="product[preorder_use_qty]"]';
    
    let stagingPreorderStatusElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[wk_marketplace_preorder]"] option:selected';
    let stagingPreorderModeElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[preorder_mode]"]';
    let stagingPreorderStartDateElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[preorder_start_date]"]';
    let stagingPreorderAvailDateElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[wk_marketplace_availability]"]';
    let stagingPreorderEndDateElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[preorder_end_date]"]';
    let stagingPreorderShipDateElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[preorder_ship_date]"]';
    let stagingPreorderXDayElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[preorder_x_days]"]';
    let stagingPreorderUseMaxQtyElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[preorder_use_qty]"]';
    let stagingPreorderMaxQtyElm = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[wk_mppreorder_qty]"]';

    validator.addRule(
        'preorder-mode-startenddate-start-validation',
        function (value) {
            let preorderStatus = $(preorderStatusElm).text().trim();
            let preorderMode = $(preorderModeElm).val();
            let preorderStartDate = $(preorderStartDateElm).val().trim();
            if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').hasClass('_show')) {
                preorderStatus = $(stagingPreorderStatusElm).text().trim();
                preorderMode = $(stagingPreorderModeElm).val();
                preorderStartDate = $(stagingPreorderStartDateElm).val().trim();
            }
            if(preorderStatus != 'Enable' && preorderStatus != '啟用'){
                return true;
            }
            if(preorderMode != 1){
                return true;
            }
            if(preorderStartDate.trim() == ''){
                return false;
            }

            return true;
        }
        , $.mage.__('Please input a valid start date.')
    );

    validator.addRule(
        'preorder-mode-startenddate-availability-validation',
        function (value) {
            let preorderStatus = $(preorderStatusElm).text().trim();
            let preorderMode = $(preorderModeElm).val();
            let preorderAvailDate = $(preorderAvailDateElm).val().trim();
            if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').hasClass('_show')) {
                preorderStatus = $(stagingPreorderStatusElm).text().trim();
                preorderMode = $(stagingPreorderModeElm).val();
                preorderAvailDate = $(stagingPreorderAvailDateElm).val().trim();
            }
            if(preorderStatus != 'Enable' && preorderStatus != '啟用'){
                return true;
            }
            if(preorderMode != 1){
                return true;
            }
            if(preorderAvailDate.trim() == ''){
                return false;
            }

            return true;
        }
        , $.mage.__('Please input a valid available date.')
    );

    validator.addRule(
        'preorder-mode-startenddate-end-validation',
        function (value) {
            let preorderStatus = $(preorderStatusElm).text().trim();
            let preorderMode = $(preorderModeElm).val();
            let preorderEndDate = $(preorderEndDateElm).val().trim();
            if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').hasClass('_show')) {
                preorderStatus = $(stagingPreorderStatusElm).text().trim();
                preorderMode = $(stagingPreorderModeElm).val();
                preorderEndDate = $(stagingPreorderEndDateElm).val().trim();
            }
            if(preorderStatus != 'Enable' && preorderStatus != '啟用'){
                return true;
            }
            if(preorderEndDate.trim() == ''){
                return false;
            }

            return true;
        }
        , $.mage.__('Please input a valid end date.')
    );

    validator.addRule(
        'preorder-mode-shipdate-ship-date-validation',
        function (value) {
            let preorderStatus = $(preorderStatusElm).text().trim();
            let preorderMode = $(preorderModeElm).val();
            let preorderShipDate = $(preorderShipDateElm).val().trim();
            if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').hasClass('_show')) {
                preorderStatus = $(stagingPreorderStatusElm).text().trim();
                preorderMode = $(stagingPreorderModeElm).val();
                preorderShipDate = $(stagingPreorderShipDateElm).val().trim();
            }
            if(preorderStatus != 'Enable' && preorderStatus != '啟用'){
                return true;
            }
            if(preorderMode != 3){
                return true;
            }
            if(preorderShipDate == ''){
                return false;
            }

            return true;
        }
        , $.mage.__('Please input a valid ship date.')
    );

    validator.addRule(
        'preorder-mode-startenddate-xday-validation',
        function (value) {
            let preorderStatus = $(preorderStatusElm).text().trim();
            let preorderMode = $(preorderModeElm).val();
            let preorderXDay = $(preorderXDayElm).val().trim();
            if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').hasClass('_show')) {
                preorderStatus = $(stagingPreorderStatusElm).text().trim();
                preorderMode = $(stagingPreorderModeElm).val();
                preorderXDay = $(stagingPreorderXDayElm).val().trim();
            }
            if(preorderStatus != 'Enable' && preorderStatus != '啟用'){
                return true;
            }
            if(preorderMode != 2){
                return true;
            }
            if(preorderXDay == ''){
                return false;
            }

            /**
                 * Validate integer
                 */
            if(!(/^-?\d+$/.test(preorderXDay))){
                return false;
            }

            /**
             * Validate validate-not-negative-number
             */     
            
            if(!(!isNaN(preorderXDay) && parseInt(preorderXDay) >= 0)){
                return false;
            }

            return true;
        }
        , $.mage.__('Please input valid number of day.')
    );

    validator.addRule(
        'preorder-mode-startenddate-max-qty-validation',
        function (value) {
            let preorderStatus = $(preorderStatusElm).text().trim();
            let preorderMode = $(preorderModeElm).val();
            let preorderMaxQty = $(preorderMaxQtyElm).val().trim();
            let preorderUseQty = $(preorderUseMaxQtyElm).val().trim();
            if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').hasClass('_show')) {
                preorderStatus = $(stagingPreorderStatusElm).text().trim();
                preorderMode = $(stagingPreorderModeElm).val();
                preorderMaxQty = $(stagingPreorderMaxQtyElm).val().trim();
                preorderUseQty = $(stagingPreorderUseMaxQtyElm).val().trim();console.log(preorderUseQty);
            }
            if(preorderStatus != 'Enable' && preorderStatus != '啟用'){
                return true;
            }
            if(preorderMode != 1){
                return true;
            }
            if(preorderUseQty == 0){
                return true;
            }
            if(preorderMaxQty == ''){
                return false;
            }

            /**
                 * Validate integer
                 */
            if(!(/^-?\d+$/.test(preorderMaxQty))){
                return false;
            }

            /**
             * Validate validate-not-negative-number
             */     
            
            if(!(!isNaN(preorderMaxQty) && parseInt(preorderMaxQty) >= 0)){
                return false;
            }

            return true;
        }
        , $.mage.__('Please input valid maximum quantity for pre-order.')
    );
});

define('point-money-config-type',[
    'jquery',
    'uiRegistry'
], function($,registry) {
    'use strict';

    $(document).ready(function () {
        $.validator.addMethod(
            'free-ratio-upper-redeem-value-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }

                var pointType = $('#'+validatePrefix+'point_money_config_type').val();
                if(pointType != 3){
                    return true;
                }
                var upperType = $('#'+validatePrefix+'point_money_config_free_ratio_upper_redeem_limit_type').val();
                if(upperType == 1 && parseInt($('#'+validatePrefix+'point_money_config_free_ratio_upper_redeem_limit_value').val()) > 100){
                    return false;
                }
                /**
                 * Validate validate-not-negative-number
                 */
                value = $.mage.parseNumber(value);
                if(($.mage.isEmptyNoTrim(value)) || !(!isNaN(value) && value >= 0)){
                    return false;
                }
                /**
                 * Validate integer
                 */
                if(!(/^-?\d+$/.test(value))){
                    return false;
                }
                /** required */
                if($.mage.isEmpty(value)){
                    return false;
                }

                return true;
            },
            $.mage.__('Point:Money Free Ratio Upper/Lower Redeem Limit Value are mandatory fields now base on current Point:Money Config Type value.')
        );
        $.validator.addMethod(
            'free-ratio-lower-redeem-value-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }
                var pointType = $('#'+validatePrefix+'point_money_config_type').val();
                if(pointType != 3){
                    return true;
                }
                var upperType = $('#'+validatePrefix+'point_money_config_free_ratio_lower_redeem_limit_type').val();
                if(upperType == 1 && parseInt($('#'+validatePrefix+'point_money_config_free_ratio_lower_redeem_limit_value').val()) > 100){
                    return false;
                }
                /**
                 * Validate integer
                 */
                if(!(/^-?\d+$/.test(value))){
                    return false;
                }

                /**
                 * Validate validate-not-negative-number
                 */

                if(!(!isNaN(value) && parseInt(value) >= 0)){
                    return false;
                }

                return true;
            },
            $.mage.__('Point:Money Free Ratio Upper/Lower Redeem Limit Value are mandatory fields now base on current Point:Money Config Type value.')
        );

        $.validator.addMethod(
            'preorder-mode-startend-start-date-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }
                var preorderStatus = $('#'+validatePrefix+'wk_marketplace_preorder').is(':checked');
                if(!preorderStatus){
                    return true;
                }

                var preorderMode = $('#'+validatePrefix+'preorder_mode').val();
                if(preorderMode != 1){
                    return true;
                }

                var preorderModeStartEndDataStart = $('#'+validatePrefix+'preorder_start_date').val().trim();
                if(preorderModeStartEndDataStart == ''){
                    return false;
                }

                return true;
            },
            $.mage.__('Please input a valid start date.')
        );

        $.validator.addMethod(
            'preorder-mode-startend-end-date-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }
                var preorderStatus = $('#'+validatePrefix+'wk_marketplace_preorder').is(':checked');
                if(!preorderStatus){
                    return true;
                }

                var preorderModeStartEndDataEnd = $('#'+validatePrefix+'preorder_end_date').val().trim();
                if(preorderModeStartEndDataEnd == ''){
                    return false;
                }

                return true;
            },
            $.mage.__('Please input a valid end date.')
        );

        $.validator.addMethod(
            'preorder-mode-startend-available-date-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }
                var preorderStatus = $('#'+validatePrefix+'wk_marketplace_preorder').is(':checked');
                if(!preorderStatus){
                    return true;
                }

                var preorderMode = $('#'+validatePrefix+'preorder_mode').val();
                if(preorderMode != 1){
                    return true;
                }

                var preorderModeStartEndDataAvail = $('#'+validatePrefix+'wk_marketplace_availability').val().trim();
                if(preorderModeStartEndDataAvail == ''){
                    return false;
                }

                return true;
            },
            $.mage.__('Please input a valid available date.')
        );

        $.validator.addMethod(
            'preorder-mode-shipdate-ship-date-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }
                var preorderStatus = $('#'+validatePrefix+'wk_marketplace_preorder').is(':checked');
                if(!preorderStatus){
                    return true;
                }

                var preorderMode = $('#'+validatePrefix+'preorder_mode').val();
                if(preorderMode != 3){
                    return true;
                }

                var preorderModeStartEndDataAvail = $('#'+validatePrefix+'preorder_ship_date').val().trim();
                if(preorderModeStartEndDataAvail == ''){
                    return false;
                }

                return true;
            },
            $.mage.__('Please input a valid ship date.')
        );

        $.validator.addMethod(
            'preorder-mode-xday-x-day-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }
                var preorderStatus = $('#'+validatePrefix+'wk_marketplace_preorder').is(':checked');
                if(!preorderStatus){
                    return true;
                }

                var preorderMode = $('#'+validatePrefix+'preorder_mode').val();
                if(preorderMode != 2){
                    return true;
                }

                var preorderModeStartEndDataAvail = $('#'+validatePrefix+'preorder_x_days').val().trim();
                if(preorderModeStartEndDataAvail == ''){
                    return false;
                }

                /**
                 * Validate integer
                 */
                if(!(/^-?\d+$/.test(value))){
                    return false;
                }

                /**
                 * Validate validate-not-negative-number
                 */

                if(!(!isNaN(value) && parseInt(value) >= 0)){
                    return false;
                }

                return true;
            },
            $.mage.__('Please input valid number of day.')
        );

        $.validator.addMethod(
            'preorder-mode-startend-max-qty-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }
                var preorderStatus = $('#'+validatePrefix+'wk_marketplace_preorder').is(':checked');
                if(!preorderStatus){
                    return true;
                }

                var preorderMode = $('#'+validatePrefix+'preorder_mode').val();
                if(preorderMode != 1){
                    return true;
                }

                var preorderUseQty = $('#'+validatePrefix+'preorder_use_qty').is(':checked');
                if(!preorderUseQty){
                    return true;
                }

                var preorderModeStartEndDataMaxQty = $('#'+validatePrefix+'wk_mppreorder_qty').val().trim();
                if(preorderModeStartEndDataMaxQty == ''){
                    return false;
                }

                /**
                 * Validate integer
                 */
                if(!(/^-?\d+$/.test(value))){
                    return false;
                }

                /**
                 * Validate validate-not-negative-number
                 */

                if(!(!isNaN(value) && parseInt(value) >= 0)){
                    return false;
                }

                return true;
            },
            $.mage.__('Please input valid maximum quantity for pre-order.')
        );

        $.validator.addMethod(
            'special-price-custom-validation',
            function(value, element) {
                var validatePrefix = '';
                if($(element).attr('id').indexOf('staging_') == 0){
                    validatePrefix = 'staging_';
                }console.log(value);
                if(value == ''){
                    return true;
                }
                var pPrice = $('#'+validatePrefix+'price').val();console.log(parseInt(pPrice));
                if(parseInt(value) > parseInt(pPrice)){
                    return false;
                }

                return true;
            },
            $.mage.__('Special price must be less than price.')
        );
        /**
         * Product Tag validate
         */
        $.validator.addMethod(
            'validate-product-tag',
            function (value, element) {
                const tagString = value.trim();
                if (!tagString) {
                    return true;
                }
                // Disallow trailing comma
                if (tagString.endsWith(',')) {
                    return false;
                }
                const tags = tagString.split(',');
                for (let tag of tags) {
                    tag = tag.trim();
                    // Disallow empty tags
                    if (tag === '') {
                        return false;
                    }
                    // Allow: Unicode letters (any language), numbers, underscores, hyphens, spaces
                    const regex = /^[\p{L}\p{N}_\-,% ]+$/u;
                    if (!regex.test(tag)) {
                        return false;
                    }
                }
                return true;
            },
            $.mage.__('Only accept comma as seperator for search tag')
        );
    }
)
});

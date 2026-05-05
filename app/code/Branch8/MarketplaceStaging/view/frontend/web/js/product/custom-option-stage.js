/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    "Branch8_MarketplaceStaging/js/stage-custom-options"
], function (jQuery) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_MarketplaceStaging/js/product/custom-option-stage': function (dataInfo) {
            let fieldSet = jQuery('[data-block=staging-product-custom-options]'),
                priceType = jQuery('#staging_price_type'),
                priceWarning = jQuery('#staging-dynamic-price-warning');
            if (priceType && priceType.val() == 0 && priceWarning) {
                priceWarning.show();
            }
            fieldSet.customOptionsUI(dataInfo.initOption);

            jQuery.each(dataInfo.optionValues, function(key, value) {
                fieldSet.customOptionsUI('addOption', value);
            });
        }
    };
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/mage',
    'Magento_Catalog/product/view/validation',
    'catalogAddToCart'
], function ($) {
    'use strict';

    $.widget('mage.productValidate', {
        options: {
            bindSubmit: false,
            radioCheckboxClosest: '.nested',
            addToCartButtonSelector: '.action.tocart'
        },

        /**
         * Uses Magento's validation widget for the form object.
         * @private
         */
        _create: function () {
            var bindSubmit = this.options.bindSubmit;

            this.element.validation({
                radioCheckboxClosest: this.options.radioCheckboxClosest,

                /**
                 * Uses catalogAddToCart widget as submit handler.
                 * @param {Object} form
                 * @returns {Boolean}
                 */
                submitHandler: function (form) {
                    var jqForm = $(form).catalogAddToCart({
                        bindSubmit: bindSubmit
                    });

                    jqForm.catalogAddToCart('submitForm', jqForm);

                    return false;
                }
            });
            $(this.options.addToCartButtonSelector).attr('disabled', false);
            $.ajax({
                url: $("#fullpoint_product_sku").val(),
                type: 'get'
            }).done(function (data) {
                console.log('checkFullPointProduct data', data);  
                if(data?.is_enable_full_point_checkout && data.is_enable_full_point_checkout === "1" && data?.is_full_point && data?.is_logged_in && data?.is_virtual){
                    console.log('Disable Buy Now button for full point product');
                    $('.action.buynow').attr('data-full-point', 1);
                }    
                $('.action.buynow').attr('disabled', false);
                // element.appendTo('.box-tocart .field.qty');
                // if(!data.error){
                //     const sanitizedData = DOMPurify.sanitize(data.data);
                //     element.html(sanitizedData);
                //     if(dataInfo?.productType === 'configurable' && data.data){
                //         element.show();
                //     }
                // }
            });
        }
    });

    return $.mage.productValidate;
});

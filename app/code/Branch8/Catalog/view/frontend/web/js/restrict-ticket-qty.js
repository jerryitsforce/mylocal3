require([
    'jquery'
], function($) {
    'use strict';

    jQuery(document).ajaxComplete(function() {
        var currentProductTypeId = productTypeId;
        var currentProductSetId = productAttributeSetId;
        updateQtyInput(currentProductTypeId, currentProductSetId);
        // $('#attribute-set-id').on('change', function(){
        //     let id = $(this).closest('form').attr('id');
        //     updateQtyInput(currentProductTypeId, $(this).val());
        // });
    });

    function updateQtyInput(typeId, attributeSetId) {
        /**
         * Default, qty can't be edited in staging
         */
        let id = $(this).closest('form').attr('id');
        if(typeId == 'virtual' && ticketAttributeSet.includes(attributeSetId)){
            $('#edit-product input[name="product[quantity_and_stock_status][qty]"]').attr('readonly', true);
            $('#edit-product input[name="product[quantity_and_stock_status][qty]"]').css('background-color' , '#DEDEDE');
        }else{
            $('#edit-product input[name="product[quantity_and_stock_status][qty]"]').removeAttr('readonly');
            $('#edit-product input[name="product[quantity_and_stock_status][qty]"]').css('background-color' , '#ffffff');
        }
    }
});
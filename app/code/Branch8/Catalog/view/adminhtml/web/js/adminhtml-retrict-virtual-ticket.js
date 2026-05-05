require([
    'jquery',
    'Magento_Ui/js/lib/view/utils/dom-observer',
], function ($, $do) {


    jQuery(document).ajaxComplete(function() {
        if(productTypeId != 'virtual'){
            return;
        }
        /**
         * ticketAttributeSet in template file
         * productAttributeSet in template file
         */
        if(ticketAttributeSet.includes(productAttributeSet)){
            var qtyStyle= "<style>" +
                "input[name=\"product[quantity_and_stock_status][qty]\"]{\n" +
                "        pointer-events: none;\n" +
                "        background-color:#e9e9e9\n" +
                "    }" +
                "</style>";
            $('#qty_style').html(qtyStyle);
            // $('input[name="product[quantity_and_stock_status][qty]"]').attr("readonly", true);
            // $('input[name="product[quantity_and_stock_status][qty]"]').css('background-color' , '#DEDEDE');
        }else{
            var qtyStyle= "<style>" +
                "input[name=\"product[quantity_and_stock_status][qty]\"]{\n" +
                "        background-color:#ffffff\n" +
                "    }" +
                "</style>";
            $('#qty_style').html(qtyStyle);
            // $('input[name="product[quantity_and_stock_status][qty]"]').removeAttr("readonly");
            // $('input[name="product[quantity_and_stock_status][qty]"]').css('background-color' , '#ffffff');
        }
    });

});
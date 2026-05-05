/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    'use strict';

    var mixin = {

        onUpdate: function (val) {console.log(val);
            this.restrictTicketStock(val);

        },
        restrictTicketStock: function(attribute_set_id){
            if(productTypeId != 'virtual'){
                return;
            }
            /**
             * ticketAttributeSet in template file
             */
            productAttributeSet = attribute_set_id;
            // if(ticketAttributeSet.includes(attribute_set_id)){
            //     var qtyStyle= "<style>" +
            //         "input[name=\"product[quantity_and_stock_status][qty]\"]{\n" +
            //         "        pointer-events: none;\n" +
            //         "        background-color:#e9e9e9\n" +
            //         "    }" +
            //         "</style>";
            //     $('#qty_style').html(qtyStyle);
            //     // $('input[name="product[quantity_and_stock_status][qty]"]').attr("readonly", true);
            //     // $('input[name="product[quantity_and_stock_status][qty]"]').css('background-color' , '#DEDEDE');
            // }else{
            //     var qtyStyle= "<style>" +
            //         "input[name=\"product[quantity_and_stock_status][qty]\"]{\n" +
            //         "        background-color:#ffffff\n" +
            //         "    }" +
            //         "</style>";
            //     $('#qty_style').html(qtyStyle);
            //     // $('input[name="product[quantity_and_stock_status][qty]"]').removeAttr("readonly");
            //     // $('input[name="product[quantity_and_stock_status][qty]"]').css('background-color' , '#ffffff');
            // }
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});

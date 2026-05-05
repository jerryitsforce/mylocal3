define(['jquery', 'Branch8_Checkout/js/action/reloadCartItems'], function ($, reloadCartItems) {
    'use strict';

    var updateShoppingCartWidgetMixin = {
        submitForm: function () {
            $.ajax({
                type: "POST",
                url: this.element.attr('action'),
                data: this.element.serialize(), // Serialize form data
                beforeSend: function () {
                   $(document.body).trigger('processStart');
                },
                success: function (data) {
                    if(data.success){
                        $('button[data-role=proceed-to-checkout]').removeClass('disabled').attr('disabled', false);
                        reloadCartItems();
                    } else {
                        $('button[data-role=proceed-to-checkout]').addClass('disabled').attr('disabled', true);
                    }
                    $(document.body).trigger('processStop');
                },
                error: function (data) {
                    window.location.reload();
                }
            }).always(function () {
                // $(document.body).trigger('processStop');
            });
        }
    };

    return function (targetWidget) {

        $.widget('mage.updateShoppingCart', targetWidget, updateShoppingCartWidgetMixin);

        return $.mage.updateShoppingCart;
    };
});
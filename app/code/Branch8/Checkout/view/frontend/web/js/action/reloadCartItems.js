define([
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'plugins/DOMPurify'
], function ($, getTotalsAction, DOMPurify) {
    'use strict';

    return function () {
        // $(document.body).trigger('processStart');
        $.ajax({url: "/checkout/cart/reloadItems", cache:false, beforeSend: function () {
                //    $('body').trigger('processStart');
                $('.form.form-cart').trigger('processCartStart');
            },success: function(result){
            if(result.success){
                const config = {
                    ALLOWED_TAGS: ['form', 'input', 'ul', 'li', 'span', 'div', 'a', 'img', 'table', 'caption', 'tbody',
                        'thead', 'tr', 'td', 'th', 'style', 'strong', 'dl', 'dt', 'dd', 'button', 'script', 'dotlottie-player'],
                    ALLOW_DATA_ATTR: true
                };
                if(result.hasItem){
                    // Specify a configuration directive
                    $("#wrap_items_grid").html(DOMPurify.sanitize(result.items, config));
                    $("#wrap_items_grid").trigger('contentUpdated');
                    if($('.cart.items input[type=checkbox].split-cart-item-checkbox:not(:disabled):not(:checked)').length === 0 && $('#wrap_items_grid').attr('data-select-all-cart')){
                        $('input#select_all_cart').prop('checked', true);
                    }else{
                        $('input#select_all_cart').prop('checked', false);
                    }
                    var deferred = $.Deferred();
                    getTotalsAction([], deferred);
                }else{
                    $(".cart-container").after(DOMPurify.sanitize(result.noItemHtml, config));
                    $(".cart-container").remove();
                }
                $('.form.form-cart').trigger('processCartStop');

            } else{
                //any error
                window.location.reload();
            }
                // $('.form.form-cart').trigger('processCartStop');
                // $('body').trigger('processStop');
            }, error: function (){
                window.location.reload();
            }
        });
    };
});

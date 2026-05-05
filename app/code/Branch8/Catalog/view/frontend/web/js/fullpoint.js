var isVirtualProduct = null;
var isFullPointProduct = null;
var isEnableFullPointCheckout = null;
var fullpointBillingAddress = null;

require([
    'jquery',
    'mage/url',
    'domReady!'
], function($, urlBuilder, DOMPurify) {
    'use strict';

    $.ajax({
        type: 'GET',
        cache: false,
        url: '/catalog/product/fullpoint/sku/'+encodeURIComponent(encodeURIComponent($('#product_addtocart_form').attr('data-product-sku'))),
        success: function (response) {
            isVirtualProduct = response.is_virtual;
            isFullPointProduct = response.is_full_point;
            isEnableFullPointCheckout = response.is_enable_full_point_checkout;
        }
    });

    
    function submitFullpoint(){
        var paymentData = {
            "method": "free"
        };
        $.ajax({
            url: urlBuilder.build('rest/V1/carts/mine/payment-information'),
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                "paymentMethod": paymentData,
                "billingAddress": fullpointBillingAddress
            }),
            success: function (response) {
                
            },
            error: function () {
                
            }
        });
    }

    
});
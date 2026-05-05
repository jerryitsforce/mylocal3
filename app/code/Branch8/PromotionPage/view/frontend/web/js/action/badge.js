define([
    'jquery',
    'plugins/DOMPurify',
    'mage/translate'
], function ($,DOMPurify, $t) {
    'use strict';

    return function () {
        var objectItems = $('.product-items .product-item-name');
        var items = new Array();
        $.each(objectItems, function(k, v){
            if($(v).attr('data-item').trim() != '' && !$(v).hasClass('processed_badge')){
                items.push( $(v).attr('data-item').trim());
            }
        });
        if(items == ''){
            return;
        }
        var itemData = items.join(',');
        var preorderBadge = $('<span class="product-badge preorder"/>');
        preorderBadge.text($t('Preorder'));
        var vipBadge = $('<span class="vip-label">/>');
        vipBadge.text($t('VIP'));
        $.ajax({url: "/promotionpage/ajax/badge?items="+itemData, cache:false, success: function(result){
                if(!result.error){
                    $.each(result.data, function (k, v){
                        var objProduct = $('.product-item-name[data-item="'+DOMPurify.sanitize(k)+'"]');
                        //set processed
                        objProduct.addClass('processed_badge');
                        //add badge
                        if(v.isPreorder) {
                            objProduct.prepend(preorderBadge);
                        }
                        if(v.isVip) {
                            objProduct.prepend(vipBadge);
                        }
                    });
                }
            }, error: function (){

            }
        });
    };
});

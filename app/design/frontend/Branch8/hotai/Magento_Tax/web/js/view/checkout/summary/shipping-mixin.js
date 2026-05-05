define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
    ],
    function ($, priceUtils, quote, $t) {
    'use strict';

    var mixin = {
        getDetailShipping: function(){
            var detailInfor, detailData;
            var processedData = new Array();
            detailInfor = this.totals()['extension_attributes']['detail_shipping_fee'];
            try {
                detailData = JSON.parse(detailInfor);
                $.each(detailData, function(index, value){
                    processedData[processedData.length] = {
                        seller_name: value.seller_name,
                        order_type : this.getOrderType(value.order_type),
                        fee: this.getFormattedPrice(value.fee)
                    };
                }.bind(this));
                return processedData;
            } catch (error) {
                return processedData;
            }
        },
        getFormattedPrice: function (price) {
            return priceUtils.formatPriceLocale(price, quote.getPriceFormat());
        },

        getOrderType: function(orderType){
            // console.log(orderType);
            const orderTypeCase = orderType.toLowerCase();
            switch(orderType){
                case 'normal':
                    orderType = $t('常溫');
                    break;
                case 'normal temperature':
                    orderType = $t('常溫');
                    break;
                case 'refrigerated':
                    orderType = $t('冷藏');
                    break;
                case 'frozen':
                    orderType = $t('冷凍');
                    break;
                case 'virtual':
                    orderType = $t('電子票券');
                    break;
                case 'preorder':
                    orderType = $t('預購');
                    break;
                case 'preorder_normal':
                    orderType = $t('預購');
                    break;
                case 'preorder_frozen':
                    orderType = $t('預購');
                    break;
                case 'preorder_refrigerated':
                    orderType = $t('預購');
                    break;
                case 'preorder_virtual':
                    orderType = $t('預購');
                    break;
                default:
                    orderType = $t('常溫');
                    break;
            }
            return orderType;
        },
    };

    return function (target) { // target == Result that Magento_Ui/.../columns returns.
        return target.extend(mixin); // new result that all other modules receive
    };
});

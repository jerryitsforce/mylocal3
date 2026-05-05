define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, $t, messageList, totals, getTotalsAction, quote, fullScreenLoader) {
        'use strict';
        return {
            validate: function () {
                var isValid = true;
                var paymentMethod = quote.paymentMethod().method;
                var segmentGrandTotal = totals.getSegment('grand_total').value;
                if(paymentMethod == 'hotaipay' && segmentGrandTotal == 0){
                    isValid = false;
                    var errorMessage = $.mage.__('Error on placing order, please refresh and try again.');
                    messageList.addErrorMessage({message: errorMessage});
                }
                
                

                return isValid;
            }
        }
    }
);

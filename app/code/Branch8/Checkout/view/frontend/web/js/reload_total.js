require([
    "jquery",
    'Magento_Checkout/js/action/get-totals'
], function ($, getTotalsAction) {
    if($('body').hasClass('checkout-cart-index')){
        var deferred = getTotalsAction([]);
    }

});
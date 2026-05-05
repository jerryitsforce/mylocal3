require([
    "jquery",
    'Magento_Customer/js/customer-data',
    'mage/url'
], function ($,  customerData, urlBuilder) {
    if($('body').hasClass('checkout-index-index')){
        return;
    }
    var localCart = customerData.get('cart');
    localCart.subscribe(function (cart)
    {
        if(cart['items'] == undefined){
            return;
        }
        var callDelIndividualProductItem = false;
        $.each(cart['items'], function (k, item){
            if(parseInt(item.individual_product, 10) == 1){
                callDelIndividualProductItem = true;
                return;
            }
        });
        if(!callDelIndividualProductItem){
            return;
        }
        $.ajax({
            url: urlBuilder.build('checkout/cart/individualProduct'),
            type: 'GET',
            dataType: 'json',
            showLoader: false,
            success: function (response) {
                var sections = ['cart'];
                customerData.invalidate(sections);
                customerData.reload(sections, true);
            }
        });
    }, this);

});
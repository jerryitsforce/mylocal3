require(['jquery', 'uiRegistry', 'Magento_Checkout/js/model/quote', 'Amasty_CheckoutCore/js/model/address-form-state'], function($, registry, quote, addressFormState){
    var placeholderAddress = JSON.parse($('#placeholderAddress').attr('data-address'));
    var intervalBillingAddress = setInterval(function(){
        if(quote.billingAddress() == null && quote.isVirtual() && quote.paymentMethod() && addressFormState.isBillingFormVisible() && $('.checkout-billing-address .action-update').length > 0){
            var firstnameEl = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.firstname');
            firstnameEl.value(placeholderAddress.firstname);
            var lastnameEl = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.lastname');
            lastnameEl.value(placeholderAddress.lastname);
            var street = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.street.0');
            street.value(placeholderAddress.street[0]);
            var countryIdEl = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.country_id');
            countryIdEl.value(placeholderAddress.country_id);
            var regionIdEl = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.region_id');
            regionIdEl.value(placeholderAddress.region_id);
            var cityIdEl = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.city_id');
            cityIdEl.value(placeholderAddress.city);
            var cityEl = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.city');
            cityEl.value(placeholderAddress.city);
            var telephoneEl = registry.get('checkout.steps.billing-step.payment.afterMethods.billing-address-form.form-fields.telephone');
            telephoneEl.value(placeholderAddress.telephone);
            $('.checkout-billing-address .action-update').trigger('click');
            clearInterval(intervalBillingAddress);
        }
        // if(!quote.isVirtual() && $('#billing-address-same-as-shipping-shared:checked').length == 0){
        //     var firstnameEl = $('#billing-address-same-as-shipping-shared').trigger('click');
        //     clearInterval(intervalBillingAddress);
        // }
        if(quote.billingAddress()){
            clearInterval(intervalBillingAddress);
        }
    }, 100);


});
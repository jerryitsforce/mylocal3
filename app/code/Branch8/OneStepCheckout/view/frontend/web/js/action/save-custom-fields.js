define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'uiRegistry',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/error-processor'
    ],
    function ($, quote, customer, registry, storage, urlFormatter, errorProcessor) {
        "use strict";
        var request;
        return function () {
            var isCustomer = customer.isLoggedIn();
            var quoteId = quote.getQuoteId();
            var url = urlFormatter.build('checkout/ajax/saveCustomData');
            var referrerCode = $('[name="referrer_code"]').val();
            var orderNote = $('[name="order_note"]').val();

            if(!referrerCode && !orderNote) {
                console.log('No data to save');
                return;
            }

            var payload = {
                'cartId': quoteId,
                'referrer_code': referrerCode,
                'order_note': orderNote,
                'is_customer': isCustomer
            };

            if (request) {
                request.abort();
            }

            var result = true;

            $.ajax({
                url: url,
                data: payload,
                dataType: 'text',
                type: 'POST',
            }).done(
                function (response) {
                    result = true;
                }
            ).fail(
                function (response) {
                    result = false;
                    errorProcessor.process(response);
                }
            );
            
            return request;
        }
    }
);

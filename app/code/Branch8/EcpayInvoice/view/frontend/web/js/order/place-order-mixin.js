define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_CheckoutAgreements/js/model/agreements-assigner',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/url',
    'Magento_Checkout/js/model/error-processor',
    'uiRegistry'
], function (
    $,
    wrapper,
    agreementsAssigner,
    quote,
    customer,
    urlBuilder,
    urlFormatter,
    errorProcessor,
    registry
) {
    'use strict';

    return function (placeOrderAction) {

        /** Override default place order action and add agreement_ids to request */
        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData, messageContainer) {
            agreementsAssigner(paymentData);
            var isCustomer = customer.isLoggedIn();
            var quoteId = quote.getQuoteId();

            var url = urlFormatter.build('ecpayinvoice/frontend/save');
            var ecpayInvoiceCarruerType = $('[name="ecpay_invoice_carruer_type"]').val();
            var ecpayInvoiceType = $('[name="ecpay_invoice_type"]').val();
            var ecpayInvoiceCustomerIdentifier = $('[name="ecpay_invoice_customer_identifier"]').val();
            var ecpayInvoiceCustomerCompany = $('[name="ecpay_invoice_customer_company"]').val();
            var ecpayInvoiceLoveCode = $('[name="ecpay_invoice_love_code"]').val();
            var ecpayInvoiceCarruerNum = $('[name="ecpay_invoice_carruer_num"]').val();
            if (ecpayInvoiceType) {
                /**
                 * This file added to fix asyn process between place Order action and saving EC invoice action ,
                 *  if place order complete first then will have issue with saver
                 */
                var payload = {
                    'cartId': quoteId,
                    'is_customer': isCustomer,
                    'ecpay_invoice_carruer_type': ecpayInvoiceCarruerType,
                    'ecpay_invoice_type': ecpayInvoiceType,
                    'ecpay_invoice_customer_identifier': ecpayInvoiceCustomerIdentifier,
                    'ecpay_invoice_customer_company': ecpayInvoiceCustomerCompany,
                    'ecpay_invoice_love_code': ecpayInvoiceLoveCode,
                    'ecpay_invoice_carruer_num': ecpayInvoiceCarruerNum
                };
                if (!payload.ecpay_invoice_type || !payload.ecpay_invoice_carruer_type) {
                    return true;
                }
                if (!paymentData.extension_attributes) {
                    paymentData.extension_attributes = {};
                }
                paymentData.extension_attributes['ecpay_invoice_carruer_type'] = ecpayInvoiceCarruerType;
                paymentData.extension_attributes['ecpay_invoice_type'] = ecpayInvoiceType;
                paymentData.extension_attributes['ecpay_invoice_customer_identifier'] = ecpayInvoiceCustomerIdentifier;
                paymentData.extension_attributes['ecpay_invoice_customer_company'] = ecpayInvoiceCustomerCompany;
                paymentData.extension_attributes['ecpay_invoice_love_code'] = ecpayInvoiceLoveCode;
                paymentData.extension_attributes['ecpay_invoice_carruer_num'] = ecpayInvoiceCarruerNum;
                paymentData.extension_attributes['ecpay_invoice_carrier'] = $('[name="ecpay_invoice_carrier"]').val();
            }
            return originalAction(paymentData, messageContainer);
        });
    };
});

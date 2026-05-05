define([
    'jquery',
    'uiRegistry',
    'ko',
    'underscore',
    'Amasty_CheckoutCore/js/view/form/element/email',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/checkout-data',
    'Amasty_CheckoutCore/js/model/payment-validators/login-form-validator',
    'Amasty_CheckoutCore/js/action/save-password',
    'Amasty_CheckoutCore/js/model/payment/place-order-state',
    'Branch8_OneStepCheckout/js/model/text-helper',
    'Magento_Ui/js/lib/view/utils/async'
], function (
    $,
    registry,
    ko,
    _,
    Component,
    customer,
    customerData,
    checkoutData,
    loginFormValidator,
    saveAction,
    placeOrderState,
    textHelper
) {
    'use strict';

    var getData = function () {
        return customerData.get('checkout-data')();
    };

    return Component.extend({
        defaults: {
            customer: window.customerData,
            isCreateAccountAction: ko.observable(true),
            isPassword: ko.observable(false),
            createAcc: +window.checkoutConfig.quoteData.additional_options.create_account,
            confirmationValue: null,
            passValidationDelay: 500,
            loginFormSelector: 'form[data-role="email-with-possible-login"]',
            modules: {
                dateOfBirth: 'checkout.sidebar.additional.checkboxes.date_of_birth',
                loginCaptcha: '${ $.name }.additional-login-form-fields.captcha',
                mspRecaptcha: '${ $.name }.msp_recaptcha'
            },
            listens: {
                confirmationValue: 'validateAndSaveRegistration'
            }
        },
        passValidationTimeout: 0,

        initialize: function () {
            this._super();
        },

        initObservable: function () {
            this._super();
            this.observe('confirmationValue');
            return this;
        },

        customerName: function () {
            // return this.customer?.custom_attributes?.nickname?.value || textHelper.formatName(this.customer.firstname) || '';
            return textHelper.formatName(this.customer.firstname) || '';
        },

        customerEmail: function () {
            var email = this.customer?.email || '';
            return textHelper.formatEmail(email);
        },

        customerPhoneNumber: function () {
            var phoneNumber = this.customer?.bob || this.customer?.custom_attributes?.phone_number?.value || '';
            return textHelper.formatPhone(phoneNumber);
        }
    });
});

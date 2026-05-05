define(
    [
        'jquery',
        'underscore',
        'uiComponent',
        'ko',
        'uiRegistry',
        'consoleLogger',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Amasty_CheckoutCore/js/action/is-equal-ignore-functions',
        'Amasty_CheckoutCore/js/model/one-step-layout',
        'Amasty_CheckoutCore/js/model/payment-validators/shipping-validator',
        'Amasty_CheckoutCore/js/model/address-form-state',
        'Amasty_CheckoutCore/js/model/statistic',
        'Amasty_CheckoutCore/js/model/shipping-registry',
        'Amasty_CheckoutCore/js/action/recollect-shipping-rates',
        'Amasty_CheckoutCore/js/model/payment/salesrule-observer',
        'Amasty_CheckoutCore/js/action/update-items-content'
    ],
    function (
        $,
        _,
        Component,
        ko,
        registry,
        consoleLogger,
        customer,
        selectBillingAddress,
        quote,
        paymentValidatorRegistry,
        paymentMethodConverter,
        paymentService,
        checkoutDataResolver,
        isEqualIgnoreFunctions,
        oneStepLayout,
        shippingValidator,
        addressFormState,
        statistic,
        shippingRegistry,
        recollectRates,
        salesRuleObserver,
        getEditableItemsData
    ) {
        'use strict';

        return function (Onepage) {
            return Onepage.extend({
                initialize: function () {
                    this._super();
                    oneStepLayout.checkoutLayout = window.checkoutLayout;
                }
            });
        };
    }
);

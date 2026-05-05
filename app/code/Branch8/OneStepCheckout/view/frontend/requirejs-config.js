var config = {
    config: {
        mixins: {
            'Amasty_CheckoutCore/js/model/one-step-layout': {
                'Branch8_OneStepCheckout/js/model/one-step-layout-mixin': true
            },
            'Amasty_CheckoutCore/js/view/onepage': {
                'Branch8_OneStepCheckout/js/view/onepage-mixin': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Branch8_OneStepCheckout/js/view/shipping-mixin': true
            },
            'Amasty_CheckoutStyleSwitcher/js/view/place-button': {
                'Branch8_OneStepCheckout/js/view/place-button-mixin': true
            },
            'Amasty_CheckoutStyleSwitcher/js/view/place-button': {
                'Branch8_OneStepCheckout/js/view/place-button-mixin': true
            },
            'Magento_Ui/js/lib/validation/rules': {
                'Branch8_OneStepCheckout/js/lib/validation/rules-mixin': true
            },
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'Branch8_OneStepCheckout/js/model/checkout-data-resolver-mixin': true
            },
            'Amasty_CheckoutCore/js/form/element/region': {
                'Branch8_OneStepCheckout/js/form/element/region-mixin': true
            },
            'Magento_Checkout/js/model/quote': {
                'Branch8_OneStepCheckout/js/model/quote-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Branch8_OneStepCheckout/js/order/place-order-mixin': true
            },
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'Branch8_OneStepCheckout/js/model/shipping-payload-extender-mixin': true
            },
            'Magento_Checkout/js/model/shipping-save-processor': {
                'Branch8_OneStepCheckout/js/model/shipping-save-processor-mixin': true
            },
            'Magento_Checkout/js/action/set-billing-address': {
                'Branch8_OneStepCheckout/js/view/action/set-billing-address-mixin': true
            },
            'Amasty_CheckoutCore/js/model/payment-validators/shipping-validator': {
                'Branch8_OneStepCheckout/js/model/payment-validators/shipping-validator-mixin': true
            }
        }
    },
    map: {
        '*': {
            'Amasty_CheckoutCore/js/view/shipping-mixin': 'Branch8_OneStepCheckout/js/view/amasty/shipping-mixin'
        }
    }
};

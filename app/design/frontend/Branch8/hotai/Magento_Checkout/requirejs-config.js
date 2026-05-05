var config = {
    map: {
        '*': {
            b8CheckoutSetupCardFail: 'Magento_Checkout/js/setupCardFail',
        }
    },
    config: {
        mixins: {
            'Magento_SalesRule/js/view/cart/totals/discount': {
                'Magento_Checkout/js/view/summary/discount-mixin': true
            },
            'Magento_SalesRule/js/view/summary/discount': {
                'Magento_Checkout/js/view/summary/discount-mixin': true
            },
            'Amasty_Rules/js/view/cart/totals/discount-breakdown': {
                'Magento_Checkout/js/view/cart/totals/discount-breakdown-mixin': true
            },
            'Tigren_SplitCart/js/coupon': {
                'Magento_Checkout/js/view/cart/coupon-mixin': true
            },
            'Magento_Checkout/js/action/update-shopping-cart': {
                'Magento_Checkout/js/action/update-shopping-cart-mixin': true
            },
            'Magento_Checkout/js/view/summary/shipping': {
                'Magento_Checkout/js/view/summary/shipping-mixin': true
            },
            'Magento_Checkout/js/view/billing-address': {
                'Magento_Checkout/js/view/billing-address-mixin': true
            }
        }
    }
};

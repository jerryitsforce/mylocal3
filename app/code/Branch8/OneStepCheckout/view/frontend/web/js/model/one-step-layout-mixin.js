define([
    'mage/utils/wrapper',
    'ko',
    'uiRegistry',
    'Amasty_CheckoutCore/js/view/utils',
    'uiLayout'
], function (wrapper, ko, registry, viewUtils, layout) {
    'use strict';

    const MAPPING_BLOCK_NAME = {
            shipping_address: 'checkout.steps.shipping-step.shippingAddress',
            shipping_method: 'checkout.steps.shipping-step.shippingAddress',
            delivery: 'checkout.steps.shipping-step.amcheckout-delivery-date',
            payment_method: 'checkout.steps.billing-step',
            summary: 'checkout.sidebar',
            additional_checkboxes: 'checkout.sidebar.additional.checkboxes',
            customer_email: 'checkout.steps.shipping-step.customer-email',
            ecpay_invoice: 'checkout.steps.shipping-step.ecpay_invoice',
            discount: 'checkout.steps.shipping-step.discount',
            referrer_code: 'checkout.steps.shipping-step.referrer_code',
            order_note: 'checkout.steps.shipping-step.order_note',
        }

    return function (target) {
        target.getCheckoutBlock = wrapper.wrapSuper(target.getCheckoutBlock, function (blockName) {
            var requestComponent = target.checkoutBlocks[blockName]
                || target.requestComponent(MAPPING_BLOCK_NAME[blockName]);
            switch (blockName) {
                case 'shipping_address':
                    if (requestComponent()) {
                        requestComponent().template = 'Branch8_OneStepCheckout/onepage/shipping/address';
                    }
                    break;

                case 'shipping_method':
                    if (requestComponent()) {
                        requestComponent().template = 'Branch8_OneStepCheckout/onepage/shipping/methods';
                    }
                    break;

                case 'discount':
                    if (requestComponent()) {
                        requestComponent().template = 'Branch8_OneStepCheckout/payment/discount';
                    }
                    break;

                default: break;
            }

            return requestComponent;
        });

        target.getVirtualLayout = wrapper.wrapSuper(target.getCheckoutBlock, function () {
            return [
                [viewUtils.getBlockLayoutConfig('customer_email'),
                viewUtils.getBlockLayoutConfig('shipping_address'),
                viewUtils.getBlockLayoutConfig('payment_method'),
                viewUtils.getBlockLayoutConfig('ecpay_invoice'),
                viewUtils.getBlockLayoutConfig('referrer_code'),
                viewUtils.getBlockLayoutConfig('order_note')],
                [viewUtils.getBlockLayoutConfig('summary')],
            ];
        });

        return target;
    };
});

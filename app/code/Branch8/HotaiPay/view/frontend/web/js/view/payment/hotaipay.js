define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'hotaipay',
            component: 'Branch8_HotaiPay/js/view/payment/method-renderer/hotaipay'
        }
    );

    /** Add view logic here if needed */
    return Component.extend({});
});

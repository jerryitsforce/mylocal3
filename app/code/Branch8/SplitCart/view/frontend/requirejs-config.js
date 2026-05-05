/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            cartQty: 'Branch8_SplitCart/js/cartQty',
            splitCart: 'Branch8_SplitCart/js/splitCart',
            // splitCartWithShippingItemTogetherDetection: 'Branch8_SplitCart/js/splitCartWithShippingItemTogetherDetection',
            cartDeletePopup: 'Branch8_SplitCart/js/deletePopup',
            reminPointPopup: 'Branch8_SplitCart/js/remindPointPopup',
            cartRemindPopup: 'Branch8_SplitCart/js/remindPopup'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/update-shopping-cart': {
                'Branch8_SplitCart/js/action/update-shopping-cart-mixin': true
            },
            'Magento_Ui/js/modal/alert': {
                'Branch8_SplitCart/js/modal/alert-mixin': true
            }
        }
    }
};

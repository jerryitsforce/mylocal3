/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    config: {
        mixins: {
            'Magento_SalesRule/js/view/summary/discount': {
                'Magento_SalesRule/js/view/summary/discount-mixin': true
            },
            'Magento_SalesRule/js/view/cart/totals/discount': {
                'Magento_SalesRule/js/view/cart/totals/discount-mixin': true
            }
        }
    }
};

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            b8GroupApps: 'Branch8_HotaiAuth/js/groupApps',
            b8LoginProcessing: 'Branch8_HotaiAuth/js/loginProcessing',
        }
    },
    config: {
        mixins: {
            'Magento_Customer/js/customer-data': {
                'Branch8_HotaiAuth/js/view/customer-data-mixin': true
            }
        }
    }
};

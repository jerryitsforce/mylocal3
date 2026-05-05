/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// var config = {
//     paths: {
//         'select2': 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min',
//     }
// };
var config = {
    map: {
        '*': {
            select2: 'Branch8_MarketplaceStaging/js/lib/select2',
        }
    },
    paths: {
        'select2': 'Branch8_MarketplaceStaging/js/lib/select2'
    },
    config: {
        mixins: {
            'mage/validation': {
                'Branch8_MarketplaceStaging/js/validation-mixin': true
            }
        }
    }
};

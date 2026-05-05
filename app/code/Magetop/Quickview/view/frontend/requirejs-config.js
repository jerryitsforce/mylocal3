/**
 * Magetop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magetop.com license that is
 * available through the world-wide-web at this URL:
 * https://www.magetop.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magetop
 * @package     Magetop_Quickview
 * @copyright   Copyright (c) Magetop (https://www.magetop.com/)
 * @license     https://www.magetop.com/LICENSE.txt
 */
var config = {
    map: {
        '*': {
            magetop_fancybox: 'Magetop_Quickview/js/jquery.fancybox',
            magetop_config: 'Magetop_Quickview/js/magetop_config',
            magnificPopup: 'Magetop_Quickview/js/jquery.magnific-popup.min',
            magetop_tocart: 'Magetop_Quickview/js/magetop_tocart'
        }
    },
    shim: {
        magnificPopup: {
            deps: ['jquery']
        }
    },
    config : {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'Magetop_Quickview/js/add-to-cart-mixin': true
            }
        }
    }
};

/**
 * Webkul Software
 *
 * @category    Webkul
 * @package     Webkul_MpBundleProduct
 * @author      Webkul Software Private Limited
 * @copyright   Webkul Software Private Limited (https://webkul.com)
 * @license     https://store.webkul.com/license.html
 */

/*jshint jquery:true*/

define([
    'jquery',
    'Branch8_MarketplaceStaging/catalog/type-events',
    "Branch8_MarketplaceStaging/js/product/weight-handler"
], function ($, weight) {
    'use strict';

    return {

        /**
         * Constructor component
         */
        'Magento_Bundle/js/bundle-type-handler': function () {
            this.bindAll();
            this._initType();
        },

        /**
         * Bind all
         */
        bindAll: function () {
            $(document).on('changeStageTypeProduct', this._initType.bind(this));
        },

        /**
         * Init type
         * @private
         */
        _initType: function () {
            if (productType.type.init === 'bundle' &&
                productType.type.current !== 'bundle' &&
                !weight.isLocked()
            ) {
                weight.switchWeight();
            }
        }
    };
});

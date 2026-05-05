/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery',
    'ko',
    'underscore',
    'mage/mage',
    'mage/decorate'
], function (Component, customerData, $, ko, _) {
    'use strict';

    var browsingHistoryProductsReloaded = false;

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();
            var branch8Data = customerData.get('branch8_customer_data');
            this.browsingHistoryProducts = ko.pureComputed(function() {
                var data = branch8Data();
                return (data && data.browsinghistory) ? data.browsinghistory : {items: [], count: 0};
            });
        }
    });
});

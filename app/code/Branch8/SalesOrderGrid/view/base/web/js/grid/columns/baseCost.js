/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/grid/columns/column',
    'uiRegistry',
], function (Element, registry) {
    'use strict';

    return Element.extend({
        /**
         *
         * @param row
         * @returns {[{sku: string, cost: number}]}
         */
        getCostLineItem: function (record) {
            if (!record.all_items_cost || !record.all_items_cost.trim()) {
                return [];
            }
            const rows = record.all_items_cost.split('||').map(row => {
                try {
                    const [id, sku, cost] = row.split(':');
                    return {'sku': sku, 'cost': cost?parseFloat(cost).toFixed(2):0};
                } catch (e) {
                    return {'sku': sku, 'cost': ''};
                }
            });
            return rows;
        }
    });
});

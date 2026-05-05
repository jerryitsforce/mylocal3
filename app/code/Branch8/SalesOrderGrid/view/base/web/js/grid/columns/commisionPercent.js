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
            if (!record.all_items_commission_percent || !record.all_items_commission_percent.trim()) {
                return [];
            }
            const rows = record.all_items_commission_percent.split('||').map(row => {
                try {
                    const [id, sku, percent] = row.split(':');
                    return {'sku': sku, 'percent': percent ? parseFloat(percent).toFixed(2):0};
                } catch (e) {
                    console.log(e);
                    return {'sku': sku, 'percent': ''}
                }
            });
            return rows;
        }
    });
});

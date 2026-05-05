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
    'mage/url'
], function (Element, registry, urlBuilder) {
    'use strict';
    return Element.extend({
        /**
         *
         * @param row
         * @returns {[{sku: string, cost: number}]}
         */
        getLatestOrder: function (record) {
            const PATH = 'sales/order/view';
            if (!record.order_latest_order_ids || !record.order_latest_order_ids.trim()) {
                return [];
            }
            const rows = record.order_latest_order_ids.split('||').map(row => {
                try {
                    const [id, incrementId] = row.split(':');
                    return {'id': id, 'incrementId': incrementId, 'link': urlBuilder.build(PATH) + '/' + id};
                } catch (e) {
                    console.log(e);
                    return {'id': '', 'incrementId': '', 'link': ''};
                }
            });
            return rows;
        }
    });
});

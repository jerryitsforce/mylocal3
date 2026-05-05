define([], function () {
    'use strict';

    /**
     * @param {Object} addressData
     * Returns new address object
     */
    return function () {

        return {
            "entity_id": null,
            "comb": '',
            "weight": 0,
            "image": null,
            "stock": 0,
            "product_id": null,
            "sku": '',
            "is_sync": false,
            "is_lock_sku": false,
            "product_item_id": null,
            "ready_to_ship_qty": 0,
            "follow_simple_sku_cost_setting": false,
            "cost_setting": 0,
            "commission_percent": 0,
            "cost": 0,
            "price": 0,
            "follow_simple_sku_price_setting": false
        }
    }
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'Branch8_Catalog/grid/cell/reserved-quantity.html'
        },

        /**
         * Get reserved quantity data (stock name and reserved qty)
         *
         * @param {Object} record - Record object
         * @returns {Array} Result array
         */
        getReservedQuantityData: function (record) {
            return record[this.index] ? record[this.index] : [];
        }
    });
});

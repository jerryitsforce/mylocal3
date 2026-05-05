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
         * Returns field action handler if it was specified.
         *
         * @param {Object} record - Record object with which action is associated.
         * @returns {Function|Undefined}
         */
        clickSelectRow: function (record) {
            var component = () => registry.get(this.parentName, function (parentComponent) {
                parentComponent.selectedRow(record);
            });
            component();
        },
    });
});

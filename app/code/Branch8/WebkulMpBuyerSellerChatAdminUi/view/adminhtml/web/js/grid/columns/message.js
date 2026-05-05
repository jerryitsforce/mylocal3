/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/grid/columns/column',
], function (Column) {
    'use strict';
    return Column.extend({
        /**
         *
         * @param record
         * @returns {*|null}
         */
        getLabel: function (record) {

            return record !== undefined ? this.normalizeText(record[this.index]) : null;
        },
        /**
         *
         * @param input
         * @returns {string}
         */
        normalizeText: function (input) {
            let text = input;
            if (text.includes('\\u')) {
                try {
                    text = JSON.parse('"' + text.replace(/"/g, '\\"') + '"');
                } catch (e) {
                    // ignore nếu parse fail
                }
            }
            try {
                const fixed = decodeURIComponent(
                    escape(text)
                );
                text = fixed;
            } catch (e) {
            }
            return text;
        }
    });
});

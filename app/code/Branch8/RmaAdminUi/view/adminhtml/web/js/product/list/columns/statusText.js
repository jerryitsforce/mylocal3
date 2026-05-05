define([
    'Magento_Ui/js/grid/columns/select',
    './uenc-processor'
], function (Column, uencProcessor) {
    'use strict';

    return Column.extend({
        defaults: {
            label: ''
        },

        /**
         *
         * @param row
         * @param key
         * @returns {String}
         */
        getDataPost: function (row, key) {
            return uencProcessor(row[key]['post_data']);
        }
    });
});

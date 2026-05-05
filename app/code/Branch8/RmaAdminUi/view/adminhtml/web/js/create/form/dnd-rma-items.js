/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
    'mageUtils',
    'Magento_Ui/js/lib/spinner',
], function ($, dynamicRows, utils, Spinner) {
    'use strict';

    return dynamicRows.extend({
        /**
         *
         * @param url
         * @param data
         * @returns {*|jQuery}
         */
        loadRmaItems: function (data, callback) {
            var save = $.Deferred();
            const element = this, url = this.loadItemUrl;
            data = utils.serialize(utils.filterFormData(data));
            data['form_key'] = window.FORM_KEY;
            if (!url || url === 'undefined') {
                return save.resolve();
            }
            //  $('body').trigger('processStart');
            this.showSpinner(true);
            $.ajax({
                url: url,
                data: data,
                loader: false,
                /**
                 * Success callback.
                 * @param {Object} resp
                 * @returns {Boolean}
                 */
                success: function (resp) {
                    if (!resp.error) {
                        save.resolve();
                        return true;
                    }
                    if (callback) {
                        callback(resp);
                    }
                },

                /**
                 * Complete callback.
                 */
                complete: function () {
                    //       $('body').trigger('processStop');
                    element.showSpinner(false);
                }
            });
            return save.promise();
        },
        /**
         * @inheritdoc
         */
        initialize: function () {
            const self = this;
            this._super();
            return this;
        },
        /**
         *
         * @param items
         */
        updateRecord: function (res) {
            const self = this;
            let insertData = this.insertData() || [];
            insertData.forEach(function (value, index) {
                self.deleteRecord(index.toString(), value.item_id)
            })
            this.insertData([]);
            self.insertData(res.items);
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const self = this;
            this._super()
                .observe([
                    'order_id'
                ]);
            this.order_id.subscribe(async function (orderId) {
                if (orderId) {
                    self.loadRmaItems({
                        orderId: orderId
                    }, self.updateRecord.bind(self))
                }
            })
            return this;
        },

    });
});

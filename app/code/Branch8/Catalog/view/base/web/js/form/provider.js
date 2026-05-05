/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/form/provider',
    './client'
], function (ParentProvider, Client) {
    'use strict';
    return ParentProvider.extend({
        /**
         *
         * @returns {*}
         */
        initClient: function () {
            this.client = new Client(this.clientConfig);
            return this;
        },
        /**
         * Saves currently available data.
         *
         * @param {Object} [options] - Addtitional request options.
         * @returns {Provider} Chainable.
         */
        save: function (options) {
            var data = this.get('data');

            this.client.save(data, options, function (res) {
                console.log({
                    res: res
                })
            });

            return this;
        }
    });
});

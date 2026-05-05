/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'Magento_Ui/js/grid/listing'
], function (ko, Collection) {
    'use strict';
    return Collection.extend({
        defaults: {
            imports: {
                rows: '${ $.provider }:data.items'
            }
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super()
                .observe({
                    selectedRow: {}
                });

            return this;
        },
    });
});

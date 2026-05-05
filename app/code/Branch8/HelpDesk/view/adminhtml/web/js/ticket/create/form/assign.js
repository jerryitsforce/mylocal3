/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'mageUtils'
], function (Element) {
    'use strict';

    return Element.extend({
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super()
                .observe({
                    textLabel: ''
                });
            return this;
        },
        /**
         *
         */
        unAssign: function () {
            this.textLabel('');
            this.value('');
            return this;
        }
    });
});

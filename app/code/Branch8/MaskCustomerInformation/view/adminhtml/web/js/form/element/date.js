/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/form/element/date'
], function (Abstract) {
    'use strict';
    return Abstract.extend({
        initConfig: function () {
            this._super();
            console.log({
                element: this
            })

            return this;
        }
    });
});

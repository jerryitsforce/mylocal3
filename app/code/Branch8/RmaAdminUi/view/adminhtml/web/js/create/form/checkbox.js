/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/single-checkbox'
], function (Checkbox) {
    'use strict';

    return Checkbox.extend({

        /** @inheritdoc */
        initialize: function () {
            this._super();
            if (this.rows && this.rows().elems().length === 0) {
                this.checked(true);
            }
            return this;
        }
    });
});

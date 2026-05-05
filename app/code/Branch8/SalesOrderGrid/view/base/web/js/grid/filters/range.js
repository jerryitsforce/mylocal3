/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'uiLayout',
    'mageUtils',
    'Magento_Ui/js/grid/filters/range',
    'mage/translate'
], function (_, layout, utils, AbstractRange, $t) {
    'use strict';

    return AbstractRange.extend({
        /**
         *
         * @returns {*}
         */
        initChildren: function () {
            var children = this.buildChildren();
            layout(children);

            return this;
        },
        /**
         * Creates configuration for the child components.
         *
         * @returns {Object}
         */
        buildChildren: function () {
            const that = this;
            var templates = this.templates,
                typeTmpl = templates[this.rangeType],
                tmpl = utils.extend({'placeholder': that.placeholder ? that.placeholder : ''}, templates.base, typeTmpl),
                children = {};

            _.each(templates.ranges, function (range, key) {
                children[key] = utils.extend({
                    'placeholder': that.placeholder ? that.placeholder : ''
                }, tmpl, range);
            });
            return utils.template(children, {
                group: this
            }, true, true);
        }
    });
});

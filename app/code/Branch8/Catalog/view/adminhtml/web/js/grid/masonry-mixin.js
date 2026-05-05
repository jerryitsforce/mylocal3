/**
 * Mixin to guard Magento_Ui/js/grid/masonry when container is not yet in DOM.
 * Prevents "undefined is not an object (evaluating 'this.container.clientWidth')"
 * when provider reloads before the container element is rendered.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (OriginalMasonry) {
        return OriginalMasonry.extend({
            /**
             * Init component handler - defer setLayoutStyles if container not in DOM
             *
             * @param {Object} rows
             * @return {Object}
             */
            initComponent: function (rows) {
                if (!rows.length) {
                    return this;
                }
                this.imageMargin = parseInt(this.imageMargin, 10);
                this.container = $('[data-id="' + this.containerId + '"]')[0];

                if (this.container) {
                    this.setLayoutStyles();
                } else {
                    this.setLayoutStylesWhenLoaded();
                }
                this.setEventListener();

                return this;
            },

            /**
             * Set layout styles inside the container (guard when container missing)
             */
            setLayoutStyles: function () {
                if (!this.container) {
                    return;
                }
                this._super();
            },

            /**
             * Updates styles for component (guard when container missing)
             */
            updateStyles: function () {
                if (!this.container) {
                    return;
                }
                this._super();
            }
        });
    };
});

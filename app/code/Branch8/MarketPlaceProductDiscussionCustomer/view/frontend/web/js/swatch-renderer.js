define([
    'jquery',
    'jquery-ui-modules/widget',
    'Branch8_OptionsWithStockAndImages/js/swatch-renderer'
], function ($) {
    /**
     *
     */
    $.widget('mage.productDisccusionSwatchRender', $.mage.SwatchRenderer, {
        /**
         *
         * @param elem
         * @private
         */
        _UpdatePrice: function (elem) {
            return elem;
        },
        /**
         *
         * @returns {mage.productDisccusionSwatchRender}
         * @private
         */
        _bindEvents: function () {
            return this;
        }
    });
    return $.mage.productDisccusionSwatchRender;
});

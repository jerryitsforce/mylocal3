define([
    'jquery',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function ($, Component, quote, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/cart/totals/item-count',
            cartSelector: 'tr.totals.item-count',
            cartItemSelector: 'tr.totals.item-selected'
        },
        totals: quote.getTotals(),
        items: [],

        /**
         * initialize
         */
        initialize: function () {
            this._super();
            this.initEvents();
        },

        /**
         * Get pure value.
         *
         * @return {*}
         */
        getPureValue: function () {
            if(this.totals()) {
                var items = this.getItems();
                var total = 0;
                items.forEach(item => {
                    total += parseInt(item?.qty);
                });
                return total;
            }
        },

        /**
         * @return {*|String}
         */
        getTotalCount: function () {
            return $.mage.__('%1 in total').replace('%1', '<span class="price">'+this.getPureValue()+'</span>');
        },

        /**
         * @return {Array}
         */
        getItems: function () {
            if (this.totals() && this.totals()['items']) {
                var allItems = this.totals()['items'].filter(item => item?.extension_attributes?.available_to_checkout === '1' || item?.extension_attributes?.available_to_checkout === 1);
                this.items = allItems;
            } 

            if(this.items.length > 0 && $('button[data-role=proceed-to-checkout]').hasClass('disabled')) {
                $('button[data-role=proceed-to-checkout]').removeClass('disabled').attr('disabled', false);
            }
            if(this.items.length === 0 && !$('button[data-role=proceed-to-checkout]').hasClass('disabled')) {
                $('button[data-role=proceed-to-checkout]').addClass('disabled').attr('disabled', true);
            }

            return this.items;
        },

        /**
         * Initialize events
         */
        initEvents: function () {
            var self = this;
            // $(self.cartSelector).find('.mark').addClass('-collapsed');
            $(document).on('click', self.cartSelector, function () {
                $(self.cartItemSelector).toggleClass('show');
                $(this).find('.mark').toggleClass('-collapsed');
            });
            $(document).on('click', '.totals.shipping ', function () {
                $('.shipping-sub').toggleClass('show');
                $(this).find('.mark').toggleClass('-collapsed');
            });
        },

        /**
         * @return {String}
         */
        renderQty: function (qty) {
            return `x${qty}`;
        }
    });
});

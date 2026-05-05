define([
    'jquery',
    'ko',
    'mage/url',
    'uiElement',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data'
], function ($, ko, urlBuilder, Component, modal, customerData) {
    'use strict';

    var modalPopupSelector = '[data-placeholder="branch8-price-change-popup"]';
    return Component.extend({
        defaults: {
            template: 'Branch8_MarketplaceProduct/product/price-change-popup'
        },

        /**
         * Redirect to shopping cart.
         */
        viewShoppingCart: function () {
            window.location.replace(this.shoppingCartUrl);
        },

        /**
         * Redirect to shopping cart.
         */
        reloadMiniCart: function () {
            customerData.invalidate(['cart']);
        },

        /**
         * Initialize component.
         *
         * @returns {*}
         */
        initialize: function () {
            var self = this,
                deferred = $.Deferred();

            var options = {
                type: 'popup',
                responsive: false,
                title: $.mage.__('商品價格異動通知'),
                modalClass: 'modal-custom price-changes-popup',
                buttons: [{
                    text: $.mage.__('前往購物車'),
                    class: 'action primary action-primary',
                    click: function () {
                        self.viewShoppingCart();
                        this.closeModal();
                        // window.location.reload();
                    }
                }]
            };

            modal(options, $(modalPopupSelector));

            $.ajax({
                url: urlBuilder.build('marketplacectrl/cart/checkpricechange'),
                type: 'GET',
                dataType: 'json',
                showLoader: false,
                success: function (response) {
                    if (response) {
                        $(modalPopupSelector).modal('openModal');
                        self.reloadMiniCart();
                        deferred.resolve();
                    } else {
                        deferred.reject();
                    }
                },
                error: function () {
                    deferred.reject();
                }
            });

            deferred.promise();

            return self._super();
        }
    })
});

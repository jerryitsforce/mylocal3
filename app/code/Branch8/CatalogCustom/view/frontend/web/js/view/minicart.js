/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    "uiComponent",
    "Magento_Customer/js/customer-data",
    "jquery",
    "ko",
    "underscore",
    "sidebar",
    "mage/translate",
    "mage/dropdown",
], function (Component, customerData, $, ko, _) {
    "use strict";

    var sidebarInitialized = false,
        addToCartCalls = 0,
        miniCart;

    miniCart = $("[data-block='minicart']");

    /**
     * @return {Boolean}
     */
    function initSidebar() {
        if (miniCart.data("mageSidebar")) {
            miniCart.sidebar("update");
        }

        if (!$("[data-role=product-item]").length) {
            return false;
        }
        miniCart.trigger("contentUpdated");

        if (sidebarInitialized) {
            return false;
        }
        sidebarInitialized = true;
        miniCart.sidebar({
            targetElement: "div.block.block-minicart",
            url: {
                checkout: window.checkout.checkoutUrl,
                update: window.checkout.updateItemQtyUrl,
                remove: window.checkout.removeItemUrl,
                loginUrl: window.checkout.customerLoginUrl,
                isRedirectRequired: window.checkout.isRedirectRequired,
            },
            button: {
                checkout: "#top-cart-btn-checkout",
                remove: "#mini-cart a.action.delete",
                close: "#btn-minicart-close",
            },
            showcart: {
                parent: "span.counter",
                qty: "span.counter-number",
                label: "span.counter-label",
            },
            minicart: {
                list: "#mini-cart",
                content: "#minicart-content-wrapper",
                qty: "div.items-total",
                subtotal: "div.subtotal span.price",
                maxItemsVisible: window.checkout.minicartMaxItemsVisible,
            },
            item: {
                qty: ":input.cart-item-qty",
                button: ":button.update-cart-item",
            },
            confirmMessage: $.mage.__(
                "Are you sure you would like to remove this item from the shopping cart?"
            ),
        });
    }

    miniCart.on("dropdowndialogopen", function () {
        initSidebar();
    });

    return Component.extend({
        shoppingCartUrl: window.checkout.shoppingCartUrl,
        maxItemsToDisplay: window.checkout.maxItemsToDisplay,
        cart: {},

        // jscs:disable requireCamelCaseOrUpperCaseIdentifiers
        /**
         * @override
         */
        initialize: function () {
            var self = this,
                cartData = customerData.get("cart");

            var data = cartData();
            if(data.items === undefined || data.items === null || data.items.length === 0) {
                data.summary_count = 0;
            }   

            this.update(data);
            // this.isShowEditCart(cartData().items);
            cartData.subscribe(function (updatedCart) {
                addToCartCalls--;
                this.isLoading(addToCartCalls > 0);
                sidebarInitialized = false;
                if(updatedCart.items === undefined || updatedCart.items === null || updatedCart.items.length === 0) {
                    updatedCart.summary_count = 0;
                }
                this.update(updatedCart);
                // this.isShowEditCart(cartData().items);
                initSidebar();
            }, this);

            $('[data-block="minicart"]').on("contentLoading", function () {
                addToCartCalls++;
                self.isLoading(true);
            });
            // this.isShowEditCart(cartData().items);
            if (
                (cartData().website_id !== window.checkout.websiteId &&
                    cartData().website_id !== undefined) ||
                (cartData().storeId !== window.checkout.storeId &&
                    cartData().storeId !== undefined)
            ) {
                customerData.reload(["cart"], false);
            }
            // console.log( this.isShowEditCart(cartData().items))
            return this._super();
        },
        //jscs:enable requireCamelCaseOrUpperCaseIdentifiers

        isLoading: ko.observable(false),
        initSidebar: initSidebar,

        /**
         * Close mini shopping cart.
         */
        closeMinicart: function () {
            $('[data-block="minicart"]')
                .find('[data-role="dropdownDialog"]')
                .dropdownDialog("close");
        },

        /**
         * @param {String} productType
         * @return {*|String}
         */
        getItemRenderer: function (productType) {
            return this.itemRenderer[productType] || "defaultRenderer";
        },

        isShowEditCart: function () {
            var cartData = customerData.get("cart");
            var hasIndividualProduct = false;
            if (cartData().items === undefined) {
                return false;
            }
            for (var i = 0; i < cartData().items.length; i++) {
                if (cartData().items[i].individual_product == 1) {
                    hasIndividualProduct = true;
                    break;
                }
            }
            return !hasIndividualProduct;
        },

        /**
         * Update mini shopping cart content.
         *
         * @param {Object} updatedCart
         * @returns void
         */
        update: function (updatedCart) {
            _.each(
                updatedCart,
                function (value, key) {
                    if (!this.cart.hasOwnProperty(key)) {
                        this.cart[key] = ko.observable();
                    }
                    this.cart[key](value);
                },
                this
            );
        },

        /**
         * Get cart param by name.
         *
         * @param {String} name
         * @returns {*}
         */
        getCartParamUnsanitizedHtml: function (name) {
            if (!_.isUndefined(name)) {
                if (!this.cart.hasOwnProperty(name)) {
                    this.cart[name] = ko.observable();
                }
            }

            return this.cart[name]();
        },

        /**
         * @deprecated please use getCartParamUnsanitizedHtml.
         * @param {String} name
         * @returns {*}
         */
        getCartParam: function (name) {
            return this.getCartParamUnsanitizedHtml(name);
        },

        getDisplayCount: function () {
            var count = this.getCartParam('summary_count') || 0;
            return count > 999 ? '999+' : count.toLocaleString(window.LOCALE);
        },

        /**
         * Returns array of cart items, limited by 'maxItemsToDisplay' setting
         * @returns []
         */
        getCartItems: function () {
            var items = this.getCartParamUnsanitizedHtml("items") || [];

            items = items.slice(parseInt(-this.maxItemsToDisplay, 10));

            return items;
        },

        /**
         * Returns count of cart line items
         * @returns {Number}
         */
        getCartLineItemsCount: function () {
            var items = this.getCartParamUnsanitizedHtml("items") || [];

            return parseInt(items.length, 10);
        },
    });
});

define(
    [
        'jquery',
        'Magento_Checkout/js/view/summary/item/details',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/confirm',
        'Amasty_CheckoutCore/js/action/remove-item',
        'Amasty_CheckoutCore/js/action/update-item',
        'mage/translate',
        'ko',
        'Amasty_CheckoutCore/js/options/configurable',
        'priceOptions',
        'mage/validation'
    ],
    function ($, Component, quote, confirm, removeItemAction, updateItemAction, $t, ko, configurable, priceOptions) {
        'use strict';

        var quoteItemData = window.checkoutConfig.quoteItemData;

        return Component.extend({
            defaults: {
                template: 'Amasty_CheckoutCore/checkout/summary/item/details',
                messages: {
                    deleteItem: $t('Are you sure you would like to remove this item from the shopping cart?')
                }
            },
            quoteItemData: quoteItemData,

            /**
             * @param {Object} item
             * @return {*}
             */
            getItemConfig: function (item) {
                return this.getPropertyDataFromItem(item, 'amcheckout');
            },

            /**
             *
             * @param {Object} item
             * @param {Object} propertyName
             * @return {*}
             */
            getPropertyDataFromItem: function (item, propertyName) {
                var property,
                    itemDetails,
                    quoteItem;

                if (item.hasOwnProperty(propertyName)) {
                    property = item[propertyName];
                }

                quoteItem = this.getItemFromQuote(item);

                if (quoteItem.hasOwnProperty(propertyName)) {
                    property = quoteItem[propertyName];
                }

                if (property) {
                    this.storage().set('item_details' + item.item_id + propertyName, property);

                    return property;
                }

                itemDetails = this.storage().get('item_details' + item.item_id + propertyName);

                return itemDetails || false;
            },

            /**
             *
             * @param {Object} item
             * @return {*}
             */
            getItemFromQuote: function (item) {
                var items = quote.getItems(),
                    quoteItems = items.filter(function (quoteItem) {
                        return +quoteItem.item_id === item.item_id;
                    });

                if (quoteItems.length === 0) {
                    return false;
                }

                return quoteItems[0];
            },

            getConfigurableOptions: function (item) {
                var itemConfig = this.getItemConfig(item);

                if (itemConfig.hasOwnProperty('configurableAttributes')) {
                    return itemConfig.configurableAttributes.template;
                }

                return '';
            },

            getCustomOptions: function (item, element) {
                var itemConfig = this.getItemConfig(item),
                    template = '';

                if (itemConfig.hasOwnProperty('customOptions')) {
                    template = itemConfig.customOptions.template;
                }

                $(element).html(template).trigger('contentUpdated');
            },

            optionsAfterRender: function (item, element) {
                $(element).one('beforeOpen', this.initOnOpen.bind(this, item));
            },

            initOnOpen: function (item) {
                const itemConfig = this.getItemConfig(item),
                    container = item.form;

                if (itemConfig.hasOwnProperty('configurableAttributes')) {
                    container.amcheckoutConfigurable({
                        spConfig: JSON.parse(itemConfig.configurableAttributes.spConfig),
                        superSelector: '[data-role="product-attributes"][data-item-id=' + item.item_id + ']' + ' .super-attribute-select'
                    });
                }
            },

            isDecimal: function (item) {
                var quoteItem = this.getItemFromQuote(item);

                return quoteItem.is_qty_decimal;
            },

            isEditingAvailable: function (item) {
                return this.isEditable(item) && !this.isNegotiableQuote();
            },

            isEditingLocked: function (item) {
                return this.isEditable(item) && this.isNegotiableQuote();
            },

            isEditable: function (item) {
                var itemConfig = this.getItemConfig(item);

                return itemConfig.isEditable;
            },

            isNegotiableQuote: function () {
                return window.checkoutConfig.isNegotiableQuote && this.backQuoteUrl();
            },

            backQuoteUrl: function () {
                return window.checkoutConfig.backQuoteUrl;
            },

            initOptions: function (item) {
                const itemConfig = this.getItemConfig(item),
                    containerSelector = '[data-role="product-attributes"][data-item-id=' + item.item_id + ']',
                    container = $(containerSelector);

                if (itemConfig.hasOwnProperty('customOptions')) {
                    container.priceOptions({
                        optionConfig: JSON.parse(itemConfig.customOptions.optionConfig)
                    });
                }

                item.form = container;
                item.isUpdated = ko.observable(false);
                item.validation = container.validation();

                container.find('input, select, textarea').change(function () {
                    item.isUpdated(true);
                });
            },

            updateItem: function (item) {
                if (item.validation.valid()) {
                    updateItemAction(item.item_id, item.form.serialize());
                }
            },

            /**
             * Automatically updates the order form if it`s changed
             *
             * @param {Object} item
             * @return {*}
             */
            updateItemAuto: function (item) {
                var isNotEmpty = true,
                    options;

                if (!Object.prototype.hasOwnProperty.call(item, 'form')) {
                    return;
                }

                options = item.form.serializeArray();
                $.each(options, function () {
                    if (this.value === '') {
                        isNotEmpty = false;
                    }
                });

                if (isNotEmpty) {
                    item.validation.valid();
                    updateItemAction(item.item_id, item.form.serialize());
                }
            },

            deleteItem: function (item) {
                confirm({
                    content: this.messages.deleteItem,
                    actions: {
                        confirm: function () {
                            removeItemAction(item.item_id);
                        },
                        always: function (event) {
                            event.stopImmediatePropagation();
                        }
                    }
                });
            },

            getItem: function(item_id) {

                var itemElement = null;
                _.each(this.quoteItemData, function(element, index) {
                    if (element.item_id == item_id) {
                        itemElement = element;
                    }
                });
                return itemElement;

            },

            getCustomValue: function(quoteItem) {
                try {
                    var item = this.getItem(quoteItem.item_id);
                    return item.is_vip
                }catch (e) {
                    return false;
                }
                return false;
            },

            canShowDeleteButton: function () {
                return this.isCheckoutItemsEditable() && quote.getItems().length >= 1 && !this.isNegotiableQuote();
            },

            isCheckoutItemsEditable: function () {
                return window.checkoutConfig.isCheckoutItemsEditable;
            }
        });
    }
);

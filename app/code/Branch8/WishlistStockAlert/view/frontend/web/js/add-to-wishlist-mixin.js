define(['jquery',
    'Magento_Customer/js/customer-data',
    'plugins/DOMPurify'
], function ($, customerData, DOMPurify) {
    'use strict';
    /**
     * Mixed into Magento_Wishlist/js/add-to-wishlist
     */
    return function (targetWidget) {
        $.widget('mage.addToWishlist', targetWidget, {
            /**
             * Get current combination of swatch options
             */
            getCurrentCombo: function () {
                let configOption = [];
                $(".swatch-opt .swatch-attribute").each(function () {
                    const $swatchInput = $(this).find(".swatch-input");
                    if ($swatchInput.length) {
                        const optId = DOMPurify.sanitize($swatchInput.val()?.toString());
                        if (optId) {
                            const optText = $('div[data-option-id="' + optId + '"]').text();
                            if (optText) {
                                configOption.push(optText.trim());
                            }
                        }
                    }
                });
                return configOption.length ? configOption.join('-') : 'NONE';
            },

            /**
             * @param {Object} targetData
             * @param {jQuery.Event} event
             * @private
             */
            _updateAddToWishlistButton: function (targetData, event) {
                if (!this.isVariationProduct()) {
                    return this._super(targetData, event);
                }
                // Add combo to targetData so it gets merged into params.data by base method
                this._super(targetData, event);
                // Update status of all action elements (theme function)
                if (typeof this._updateStatusAllActionElement === 'function') {
                    this._updateStatusAllActionElement();
                }
            },
            /**
             *
             * @param target
             * @private
             */
            _resolveAddToParams(target) {
                if (!this.isVariationProduct()) {
                    return this._super(target);
                }
                let params = this._super(target);
                if (params['data'] === undefined) {
                    params['data'] = {};
                }
                let data = params['data'];
                params['data'] = $.extend(data, {combo: this.getCurrentCombo()});
                return params;
            },
            /**
             *
             * @param target
             * @returns {*}
             * @private
             */
            _resolveRemoveParams: function (target) {
                if (!this.isVariationProduct()) {
                    return this._super(target);
                }
                return this._super(target);
            },
            /**
             *
             * @param combination
             * @returns {*}
             */
            isInWishlist: function (combination) {
                return this.wishlist().items.some(item => {
                    return combination === item.combo;
                });
            },

            /**
             *
             * @param {*} productId
             */
            getItemIdByProductId: function (productId) {
                if (!this.isVariationProduct()) {
                    return this._super(productId);
                }
                const wishlist = customerData.get('wishlist');
                if (!wishlist) {
                    return null;
                }
                const currentCombo = this.getCurrentCombo(),
                    items = wishlist().items,
                    item = items.find(item => (parseInt(item.product_id) === parseInt(productId) && item.combo === currentCombo));
                return item ? item.item_id : null;
            },

            /**
             * Check product added
             *
             * @inheritDoc
             */
            added: function (element) {
                const currentCombo = this.getCurrentCombo(),
                    isVariationProduct = this.isVariationProduct(),
                    basic = this._super(element);
                if (isVariationProduct) {
                    return basic && this.isInWishlist(currentCombo);
                }
                return basic;
            },
            /**
             *
             * @returns {boolean}
             */
            isVariationProduct: function () {
                return this.options.isVariantProduct === true;
            }
        });

        return $.mage.addToWishlist;
    };
});

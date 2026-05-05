/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'jquery-ui-modules/widget'
], function ($, customerData) {
    'use strict';

    $.widget('mage.addToWishlist', {
        items: [],
        options: {
            bundleInfo: 'div.control [name^=bundle_option]',
            configurableInfo: '.super-attribute-select',
            groupedInfo: '#super-product-table input',
            downloadableInfo: '#downloadable-links-list input',
            customOptionsInfo: '.product-custom-option',
            qtyInfo: '#qty',
            actionElement: '[data-action="add-to-wishlist"]',
            productListWrapper: '.product-item-info',
            productPageWrapper: '.product-info-main',
            productIds: [],
            deleteParams: [],
        },

        /** @inheritdoc */
        _create: function () {
            this._bind();
            this._triggerWishlistFormUpdate();
            this.initWishlistProduct();
        },

        /**
         * @private
         */
        _bind: function () {
            var options = this.options,
                dataUpdateFunc = '_updateWishlistData',
                validateProductQty = '_validateWishlistQty',
                changeCustomOption = 'change ' + options.customOptionsInfo,
                changeQty = 'change ' + options.qtyInfo,
                updateWishlist = 'click ' + options.actionElement,
                events = {},
                key;

            if ('productType' in options) {
                if (typeof options.productType === 'string') {
                    options.productType = [options.productType];
                }
            } else {
                options.productType = [];
            }

            events[changeCustomOption] = dataUpdateFunc;
            events[changeQty] = dataUpdateFunc;
            events[updateWishlist] = validateProductQty;

            for (key in options.productType) {
                if (options.productType.hasOwnProperty(key) && options.productType[key] + 'Info' in options) {
                    events['change ' + options[options.productType[key] + 'Info']] = dataUpdateFunc;
                }
            }
            this._on(events);
        },

        /**
         * Update wishlist on page load and before submit
         *
         * @private
         */
        _triggerWishlistFormUpdate: function () {
            var key;
            $(this.options.qtyInfo).trigger('change');
            for (key in this.options.productType) {
                if (this.options.productType.hasOwnProperty(key)
                    && this.options.productType[key] + 'Info' in this.options) {
                    $(this.options[this.options.productType[key] + 'Info']).trigger('change');
                }
            }
        },

        /**
         * @param {jQuery.Event} event
         * @private
         */
        _updateWishlistData: function (event) {
            var dataToAdd = {},
                isFileUploaded = false,
                handleObjSelector = null,
                self = this;

            if (event.handleObj.selector === this.options.qtyInfo) { //eslint-disable-line eqeqeq
                this._updateAddToWishlistButton({}, event);
                event.stopPropagation();

                return;
            }
            handleObjSelector = $(event.currentTarget).closest('form').find(event.handleObj.selector);
            handleObjSelector.each(function (index, element) {
                if ($(element).is('input[type=text]') ||
                    $(element).is('input[type=email]') ||
                    $(element).is('input[type=number]') ||
                    $(element).is('input[type=hidden]') ||
                    $(element).is('input[type=checkbox]:checked') ||
                    $(element).is('input[type=radio]:checked') ||
                    $(element).is('textarea') ||
                    $('#' + element.id + ' option:selected').length
                ) {
                    if ($(element).data('selector') || $(element).attr('name')) {
                        dataToAdd = $.extend({}, dataToAdd, self._getElementData(element));
                    }

                    return;
                }
                if ($(element).is('input[type=file]') && $(element).val()) {
                    isFileUploaded = true;
                }
            });

            if (isFileUploaded) {
                this.bindFormSubmit();
            }
            this._updateAddToWishlistButton(dataToAdd, event);
            event.stopPropagation();
        },
        /**
         * @param {Object} dataToAdd
         * @param {jQuery.Event} event
         */
        _updateAddToWishlistButton: function (dataToAdd, event) {
            var self = this,
                buttons = this._getAddToWishlistButton(event);
            buttons.each(function (index, element) {
                var params = $(element).data('post'),
                    currentTarget = event.currentTarget,
                    targetElement,
                    targetValue;
                if (!params) {
                    params = {
                        'data': {}
                    };
                } else if ($(currentTarget).data('selector') || $(currentTarget).attr('name')) {
                    targetElement = self._getElementData(currentTarget);
                    targetValue = Object.keys(targetElement)[0];
                    if (params.data.hasOwnProperty(targetValue) && !dataToAdd.hasOwnProperty(targetValue)) {
                        delete params.data[targetValue];
                    }
                }
                params.data = $.extend({}, params.data, dataToAdd, {
                    'qty': $(self.options.qtyInfo).val()
                });
                $(element).data('post', params);
            });
        },
        /**
         *
         * @param {*} productId
         */
        getItemIdByProductId: function (productId) {
            const wishlist = customerData.get('wishlist');
            if (!wishlist) {
                return null;
            }
            const items = wishlist().items;
            const item = items.find(item => parseInt(item.product_id) === parseInt(productId));
            return item ? item.item_id : null;
        },

        /**
         * @param {jQuery.Event} event
         * @private
         */
        _getAddToWishlistButton: function (event) {
            var productListWrapper = $(event.currentTarget).closest(this.options.productListWrapper);

            if (productListWrapper.length) {
                return productListWrapper.find(this.options.actionElement);
            }

            return $(this.options.actionElement);
        },

        /**
         * @param {Object} array1
         * @param {Object} array2
         * @return {Object}
         * @private
         * @deprecated
         */
        _arrayDiffByKeys: function (array1, array2) {
            var result = {};

            $.each(array1, function (key, value) {
                if (key.indexOf('option') === -1) {
                    return;
                }

                if (!array2[key]) {
                    result[key] = value;
                }
            });

            return result;
        },

        /**
         * @param {HTMLElement} element
         * @return {Object}
         * @private
         */
        _getElementData: function (element) {
            var data, elementName, elementValue;

            element = $(element);
            data = {};
            elementName = element.data('selector') ? element.data('selector') : element.attr('name');
            elementValue = element.val();

            if (element.is('select[multiple]') && elementValue !== null) {
                if (elementName.substr(elementName.length - 2) === '[]') { //eslint-disable-line eqeqeq
                    elementName = elementName.substring(0, elementName.length - 2);
                }
                $.each(elementValue, function (key, option) {
                    data[elementName + '[' + option + ']'] = option;
                });
            } else if (elementName.substr(elementName.length - 2) === '[]') { //eslint-disable-line eqeqeq, max-depth
                elementName = elementName.substring(0, elementName.length - 2);

                data[elementName + '[' + elementValue + ']'] = elementValue;
            } else {
                data[elementName] = elementValue;
            }

            return data;
        },

        /**
         * @param {Object} params
         * @param {Object} dataToAdd
         * @private
         * @deprecated
         */
        _removeExcessiveData: function (params, dataToAdd) {
            var dataToRemove = this._arrayDiffByKeys(params.data, dataToAdd);

            $.each(dataToRemove, function (key) {
                delete params.data[key];
            });
        },

        /**
         * Unbind previous form submit listener.
         */
        unbindFormSubmit: function () {
            $('[data-action="add-to-wishlist"]').off('click');
        },

        /**
         * Bind form submit.
         */
        bindFormSubmit: function () {
            var self = this;
            // Prevents double handlers and duplicate requests to add to Wishlist
            this.unbindFormSubmit();
            $('[data-action="add-to-wishlist"]').on('click', function (event) {
                var element, params, form, action;
                event.stopPropagation();
                event.preventDefault();
                element = $('input[type=file]' + self.options.customOptionsInfo);
                params = $(event.currentTarget).data('post');
                form = $(element).closest('form');
                action = params.action;
                if (params.data.id) {
                    $('<input>', {
                        type: 'hidden',
                        name: 'id',
                        value: params.data.id
                    }).appendTo(form);
                }
                if (params.data.uenc) {
                    action += 'uenc/' + params.data.uenc;
                }
                $(form).attr('action', action).trigger('submit');
            });
        },

        /**
         * Validate product quantity before updating Wish List
         *
         * @param {jQuery.Event} event
         * @private
         */
        _validateWishlistQty: function (event) {
            var element = $(this.options.qtyInfo);

            if (!(element.validation() && element.validation('isValid'))) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            this._triggerWishlistFormUpdate();
            // Reload messages before call ajax action
            // customerData.reload(['messages']);
            if ($(event.currentTarget).hasClass('active')) {
                let paramsRemove = this._resolveRemoveParams(event.currentTarget);
                this._removeFromWishlist(event, paramsRemove);
            } else {
                let params = this._resolveAddToParams(event.currentTarget);
                this._addToWishlist(event, params);
            }
        },
        /**
         * Remove product from wishlist
         *
         * @param {jQuery.Event} event
         * @param {Object} paramsRemove
         * @private
         */
        _removeFromWishlist: function (event, paramsRemove) {
            if (paramsRemove && paramsRemove.data && paramsRemove.data.hasOwnProperty('isAjax')) {
                event.stopPropagation();
                event.preventDefault();
                let url = paramsRemove.action,
                    data = $.extend(paramsRemove.data, {form_key: $('input[name="form_key"]').val()});
                $.ajax(url, {
                    method: 'POST',
                    data: data,
                    showLoader: true,
                    beforeSend: function () {
                    },
                    success: function () {
                        customerData.reload([
                            "branch8_ga4",
                            "messages",
                            "wishlist"
                        ]);
                        $(event.currentTarget).removeClass('active');
                    },
                });
            }
        },
        /**
         *
         *
         * @param target
         * @private
         */
        _resolveRemoveParams: function (target) {
            const productId = $(target).data('product-id').toString();
            return this.options.deleteParams[this.getItemIdByProductId(productId)];
        },
        /**
         *
         * @private
         */
        _resolveAddToParams: function (target) {
            return $(target).data('post');
        },
        /**
         * Add product to wishlist
         *
         * @param {jQuery.Event} event
         * @param {Object} params
         * @private
         */
        _addToWishlist: function (event, params) {
            if (params && params.data && params.data.hasOwnProperty('isAjax')) {
                event.stopPropagation();
                event.preventDefault();
                /**
                 * Validate and re-add variation if missed
                 */
                if ($('.swatch-opt').length) {
                    let hasOption = false;
                    $(params.data).each(function (ind, val) {
                        let paramKey = Object.keys(val)[0];
                        if (paramKey && paramKey.indexOf('options') === 0) {
                            hasOption = true;
                        }
                    });
                    if (!hasOption) {
                        $('.swatch-opt input').each(function (k, elm) {
                            params.data = $.extend(params.data, {[$(elm).attr('name')]: $(elm).val()});
                        });
                    }
                }

                let url = params.action,
                    data = $.extend(params.data, {form_key: $('input[name="form_key"]').val()});
                $.ajax(url, {
                    method: 'POST',
                    data: data,
                    showLoader: true,
                    beforeSend: function () {
                    },
                    success: function () {
                        customerData.reload([
                            "branch8_ga4",
                            "messages",
                            "wishlist"
                        ]);
                        $(event.currentTarget).addClass('active');
                    },
                });
            }
        },
        /**
         *
         * @param element
         * @param status
         * @returns {mage.addToWishlist}
         * @private
         */
        _toggleStatus: function (element, status) {
            status === true ? $(element).addClass('active') : $(element).removeClass('active');
            return this;
        },
        /**
         * Update wishlist items and their status
         * @param {Array} items
         * @private
         */
        _updateWishlistStatus: function (items) {
            var self = this;
            this.options.productIds = [];
            if (!items) {
                return;
            }
            for (let i = 0; i < items.length; ++i) {
                this.options.productIds.push(items[i].product_id);
                if (items[i].delete_item_params) {
                    this.options.deleteParams[items[i].item_id] = JSON.parse(items[i].delete_item_params);
                }
            }
            this._updateStatusAllActionElement();
        },
        /**
         *
         * @private
         */
        _updateStatusAllActionElement: function () {
            const self = this;
            $(this.options.actionElement).each(function (index, element) {
                if (self.added(element)) {
                    self._toggleStatus(element, true);
                } else {
                    self._toggleStatus(element, false);
                }
            });
        },

        /**
         * Init Wish list Product
         */
        initWishlistProduct: function () {
            let self = this,
                sections = ['wishlist'];

            customerData.invalidate(sections);
            customerData.reload(sections, true);
            this.wishlist = customerData.get('wishlist');
            this.wishlist.subscribe(function (value) {
                self._updateWishlistStatus(value.items);
            });
            this._updateWishlistStatus(this.wishlist().items);
        },

        /**
         * Check product added
         *
         * @inheritDoc
         */
        added: function (element) {
            return this.options.productIds.indexOf($(element).data('product-id').toString()) > -1;
        }
    });

    return $.mage.addToWishlist;
});

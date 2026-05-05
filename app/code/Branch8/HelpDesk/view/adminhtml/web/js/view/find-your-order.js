/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/ui-select',
    'jquery',
    'underscore',
    'mage/translate'
], function (Select, $, _) {
    'use strict';
    return Select.extend({
        cachedRecentOrders: [],
        defaults: {
            showPath: false,
            showCheckbox: true,
            chipsEnabled: true,
            closeBtn: true,
            searchOptions: true,
            filterOptions: true,
            filterOptionsFocus: true,
            validationUrl: false,
            disableLabel: false,
            pageLimit: 50,
            loadedOption: [],
            validationLoading: true,
            selectedPlaceholders: {
                defaultPlaceholder: $.mage.__('Type to find your order (Order Id,Shipping,Billing ...)'),
                lotPlaceholders: $.mage.__('Selected')
            }
        },
        /** @inheritdoc */
        initialize: function () {
            this._super();
            return this;
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            this._super();
            this.observe({
                customerId: ''
            });
            return this;
        },
        /**
         *
         * @param config
         * @returns {*}
         */
        initConfig: function (config) {
            this._super(config);
            return this;
        },
        /**
         *
         * @param value
         */
        checkCustomerId: async function (value) {
            this.customerId(value);
            this.value(null);
            if (value && this.cachedRecentOrders[value] === undefined) {
                this.cachedRecentOrders[value] = await this.findRecentOrders('', 1, 5);
                this.setCachedSearchResults(
                    'recent_' + value, this.cachedRecentOrders[value].options,
                    1,
                    this.cachedRecentOrders[value].options.length
                );
            }
            if (value) {
                this.enable(true)
                if (this.cachedRecentOrders[value]) {
                    this.filterOptionsList();
                }
            } else {
                this.disable(true)
            }
        },
        /**
         *
         * @param searchKey
         * @param page
         * @param limit
         * @param isDefault
         */
        processRequest: function (searchKey, page, limit) {
            this.loading(true);
            this.currentSearchKey = searchKey;
            $.ajax({
                url: this.searchUrl,
                type: 'get',
                dataType: 'json',
                context: this,
                data: {
                    searchKey: searchKey,
                    page: page,
                    limit: limit,
                    customerId: this.customerId() || 0
                },
                success: $.proxy(this.success, this),
                error: $.proxy(this.error, this),
                beforeSend: $.proxy(this.beforeSend, this),
                complete: $.proxy(this.complete, this, searchKey, page)
            });
        },
        /**
         *
         * @param searchKey
         * @param page
         * @param limit
         * @returns {Promise<{getAllResponseHeaders: function(): *|null, abort: function(*): this, setRequestHeader: function(*, *): this, readyState: number, getResponseHeader: function(*): null|*, overrideMimeType: function(*): this, statusCode: function(*): this}|*>}
         */
        findRecentOrders: async function (searchKey, page, limit) {
            const result = await $.ajax({
                url: this.searchUrl,
                type: 'get',
                dataType: 'json',
                context: this,
                data: {
                    searchKey: searchKey,
                    page: page,
                    limit: limit || this.pageLimit,
                    isDefault: true,
                    customerId: this.customerId() || 0
                }
            });
            return result;
        },

        /**
         *
         * @returns {void|*|boolean}
         */
        filterOptionsList: function () {
            var value = this.filterInputValue().trim().toLowerCase() || 'recent_' + this.customerId(),
                array = [];
            if (this.searchOptions) {
                return this.loadOptions(value);
            }
            this.cleanHoveredElement();
            if (!value) {
                this.renderPath = false;
                this.options(this.cacheOptions.tree);
                this._setItemsQuantity(false);
                return false;
            }
            this.showPath ? this.renderPath = true : false;
            if (this.filterInputValue()) {
                array = this.selectType === 'optgroup' ?
                    this._getFilteredArray(this.cacheOptions.lastOptions, value) :
                    this._getFilteredArray(this.cacheOptions.plain, value);

                if (!value.length) {
                    this.options(this.cacheOptions.plain);
                    this._setItemsQuantity(this.cacheOptions.plain.length);
                } else {
                    this.options(array);
                    this._setItemsQuantity(array.length);
                }

                return false;
            }

            this.options(this.cacheOptions.plain);
        },
    });
});

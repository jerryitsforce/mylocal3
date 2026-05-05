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
            label: $.mage.__('Order'),
            loadedOption: [],
            validationLoading: true,
            template: 'Branch8_HelpDesk/find-your-order',
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
         * @param config
         * @returns {*}
         */
        initConfig: function (config) {
            config.searchUrl = config.configuration.searchUrl;
            config.options = config.configuration.recentlyOrders;
            this._super(config);
            this.setCachedSearchResults(
                'recent', config.configuration.recentlyOrders, 1, config.configuration.recentlyOrders.length
            );
            return this;
        },
        /**
         *
         * @returns {void|*|boolean}
         */
        filterOptionsList: function () {
            var value = this.filterInputValue().trim().toLowerCase() || 'recent',
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

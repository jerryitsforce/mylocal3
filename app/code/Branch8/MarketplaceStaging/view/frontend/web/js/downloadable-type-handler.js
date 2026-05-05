/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'Branch8_MarketplaceStaging/js/product/weight-handler',
    'Branch8_MarketplaceStaging/js/catalog/type-events',
    'uiRegistry'
], function ($, weight, productType, registry) {
    'use strict';

    return {
        $checkbox: $('[data-action=change-type-product-staging-downloadable]'),
        $items: $('#product_info_tabs_staging_downloadable_items'),
        $tab: null,
        isDownloadable: false,

        /**
         * Show
         */
        show: function () {
            this.$checkbox.prop('checked', true);
            this.$items.show();
        },

        /**
         * Hide
         */
        hide: function () {
            this.$checkbox.prop('checked', false);
            this.$items.hide();
        },

        /**
         * Constructor component
         * @param {Object} data - this backend data
         */
        'Branch8_MarketplaceStaging/js/downloadable-type-handler': function (data) {
            this.$tab = $('[data-tab=' + data.tabId + ']');
            this.isDownloadable = data.isDownloadable;
            this.initScripts();
            this.bindAll();
            this._initType();
        },

        /**
         * Bind all
         */
        bindAll: function () {
            this.$checkbox.on('change', function (event) {
                $(document).trigger('setStageTypeProduct', $(event.target).prop('checked') ? 'downloadable' : null);
            });

            $(document).on('changeStageTypeProduct', this._initType.bind(this));
        },

        /**
         * Init type
         * @private
         */
        _initType: function () {
            if (!productType.type) productType.init();
            if (productType.type.current === 'downloadable') {
                weight.change(false);
                $('body').find(weight.$weightSwitcher).on('change', function () {
                    $(document).trigger('setStageTypeProduct', null);
                });
                this.show();
            } else {
                this.hide();
            }
        },

        initScripts: function () {
            let alertStageAlreadyDisplayed = false;

            window.alertStageAlreadyDisplayed = alertStageAlreadyDisplayed;

            registry.set('stagedownloadable', window.Downloadable);
            $('body').trigger('changeStageTypeProduct');
        }
    };
});

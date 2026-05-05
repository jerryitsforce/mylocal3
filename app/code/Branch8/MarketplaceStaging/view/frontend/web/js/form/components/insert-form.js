/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/insert-form',
    'mage/translate',
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/confirm',
    'uiRegistry',
    'Magento_Ui/js/lib/view/utils/async'
], function (Insert, $t, $, _, uiConfirm, registry) {
    'use strict';

    return Insert.extend({
        defaults: {
            updateModalProvider: '${ $.parentName }',
            subTitlePrefix: $t('Belongs to '),
            switcherSelector: '.store-switcher',
            toRemove: [],
            imports: {
                removeResponseData: '${ $.removeResponseProvider }',
                modalTitle: '${ $.modalTitleProvider }',
                modalSubTitle: '${ $.modalSubTitleProvider }',
                destroyClosedModalContents: '${ $.updateModalProvider }:state'
            },
            listens: {
                responseData: 'afterUpdate',
                removeResponseData: 'afterRemove',
                modalTitle: 'changeModalTitle',
                modalSubTitle: 'changeModalSubTitle'
            },
            modules: {
                updateModal: '${ $.updateModalProvider }',
                removeModal: '${ $.removeModalProvider }',
                upcomingListing: 'index = ${ $.upcomingListingProvider }'
            }
        },

        /** @inheritdoc **/
        initialize: function () {
            _.bindAll(this, 'onSwitcherSelect');
            this._super();
            this.updateModal(this.initSwitcherHandler.bind(this));
            this.toRemove.push('stagingConfigurableProductGrid');
            this.toRemove.push('stagingConfigurableVariations');
            this.toRemove.push('staging-variation-steps-wizard_step1');
            this.toRemove.push('staging-variation-steps-wizard_step2');
            this.toRemove.push('staging-variation-steps-wizard_step3');
            this.toRemove.push('staging-variation-steps-wizard_step4');
            this.toRemove.push('staging-variation-steps-wizard');
            this.toRemove.push('marketplacestaging_update_select_grid.marketplacestaging_update_select_grid');
            this.toRemove.push('marketplacestaging_update_select_grid.marketplacestaging_update_select_grid_data_source');
            this.toRemove.push('stagingbundleproduct_product_listing.stagingbundleproduct_product_listing');
            this.toRemove.push('stagingbundleproduct_product_listing.stagingbundleproduct_product_listing_data_source');
            this.toRemove.push('staginggrouped_product_listing.staginggrouped_product_listing');
            this.toRemove.push('staginggrouped_product_listing.staginggrouped_product_listing_data_source');
            this.toRemove.push('marketplacestaging_product_attributes_listing.marketplacestaging_product_attributes_listing');
            this.toRemove.push('marketplacestaging_product_attributes_listing.marketplacestaging_product_attributes_listing_data_source');
            this.toRemove.push('marketplacestaging_related_product_listing.marketplacestaging_related_product_listing');
            this.toRemove.push('marketplacestaging_related_product_listing.marketplacestaging_related_product_listing_data_source');
            this.toRemove.push('marketplacestaging_upsell_product_listing.marketplacestaging_upsell_product_listing');
            this.toRemove.push('marketplacestaging_upsell_product_listing.marketplacestaging_upsell_product_listing_data_source');
            this.toRemove.push('marketplacestaging_crosssell_product_listing.marketplacestaging_crosssell_product_listing');
            this.toRemove.push('marketplacestaging_crosssell_product_listing.marketplacestaging_crosssell_product_listing_data_source');

            return this;
        },

        /** @inheritdoc */
        destroyInserted: function () {
            if (this.isRendered) {
                _.each(this.toRemove, function (componentName) {
                    registry.get(componentName, function (component) {
                        if (component.hasOwnProperty('delegate')) {
                            component.delegate('destroy');
                        } else {
                            component.destroy();
                        }
                    });
                });
            }

            this._super();
        },

        /**
         * Form save callback.
         *
         * @param {Object} data
         */
        afterUpdate: function (data) {
            if (!data.error) {
                this.updateModal('closeModal');
                this.upcomingListing('reload');
                $('#weight').applyBindings();
            }
        },

        /**
         * Form remove callback.
         *
         * @param {Object} data
         */
        afterRemove:  function (data) {
            if (!data.error) {
                this.removeModal('closeModal');
                this.afterUpdate(data);
            }
        },

        /**
         * Change modal title.
         *
         * @param {String} title
         */
        changeModalTitle: function (title) {
            this.updateModal('setTitle', title);
        },

        /**
         * Change modal sub title.
         *
         * @param {String} subTitle
         */
        changeModalSubTitle: function (subTitle) {
            subTitle = subTitle ?
            this.subTitlePrefix + this.modalTitle + ' ' + subTitle :
                '';

            this.updateModal('setSubTitle', subTitle);
        },

        /**
         * Destroy contents of modal when it is closed
         *
         * @param {Boolean} state
         */
        destroyClosedModalContents: function (state) {
            if (state === false) {
                this.destroyInserted();
                $('.'+this.updateModalProvider.replaceAll('.','_') + ' .modal-header .page-main-actions').remove();
                $('.'+this.updateModalProvider.replaceAll('.','_') + ' .modal-content form').remove();
                $('.staging-steps-wizard-main').parents('aside').remove();
                $('aside.staging-bundle').remove();
                $('aside.staging-grouped').remove();
            }
        },

        /**
         * Switcher initialization.
         */
        initSwitcherHandler: function () {
            var switcherSelector = this.updateModal().rootSelector + ' ' + this.switcherSelector,
                self = this;

            $.async(switcherSelector, function (switcher) {
                $(switcher).on('click', 'li a', self.onSwitcherSelect);
            });
            $('body').on('change', '#staging-attribute-set-id', this.switchSet.bind(this));
        },

        /**
         * Store switcher selection handler.
         * @param {Object} e - event object.
         */
        onSwitcherSelect: function (e) {
            var self = this,
                param = $(e.currentTarget).data('param'),
                params = {
                    store: 0
                };

            params[param] = $(e.currentTarget).data('value');

            uiConfirm({
                content:  $t('Please confirm scope switching. All data that hasn\'t been saved will be lost.'),
                actions: {

                    /** Confirm callback. */
                    confirm: function () {
                        self.destroyInserted();
                        params = _.extend(self.previousParams, params);
                        self.render(params);
                    }
                }
            });
        },

        /**
         * Switch Att Set
         * @returns {*}
         */
        switchSet: function () {
            let urlSet = $('option:selected', '#staging-attribute-set-id').attr('data-url'),
                currentValue = $('option:selected', '#staging-attribute-set-id').val(),
                params = '?set=' + currentValue + '&isAjax=true',
                self = this;
            $('body').trigger('processStart');
            $.ajax({
                url: urlSet + params,
                success: function (resp) {
                    $('[data-role=staging-step-wizard-dialog]').parents('aside.modal-slide').remove();
                    self.destroyInserted();
                    self.onRender(resp);
                },

                /**
                 * Complete callback.
                 */
                complete: function () {
                    $('body').trigger('processStop');
                }
            });
        },

        /**
         * Callback that render content.
         *
         * @param {*} data
         */
        onRender: function (data) {
            if($("#staging-product-block", data).length > 0) {
                this._super(data);
                // After Knockout has rendered the form content, the page height
                // expands and the browser may restore old scroll position.
                // If this is a post-save reload, scroll back to top.
                if (sessionStorage.getItem('seller_product_saved')) {
                    sessionStorage.removeItem('seller_product_saved');
                    setTimeout(function () {
                        window.scrollTo(0, 0);
                    }, 300);
                }
            } else {
                sessionStorage.setItem('seller_product_saved', '1');
                sessionStorage.setItem('seller_product_saved_v3', '1');
                window.location.reload();
            }
        },

        /**
         * Set data to external provider, clear changes.
         *
         * @param {*} data
         */
        onUpdate: function (data) {
            if (this.externalSource()) {
                this._super(data);
            } else {
                sessionStorage.setItem('seller_product_saved', '1');
                sessionStorage.setItem('seller_product_saved_v3', '1');
                window.location.reload();
            }
        }
    });
});

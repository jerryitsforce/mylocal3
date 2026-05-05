/**
 * Branch8 Product Certification Panel component
 *
 * Flexible component that works in both Admin (UI Component context)
 * and Seller Dashboard (Custom Knockout scope).
 */
define([
    'ko',
    'uiComponent',
    'jquery',
    'uiRegistry',
    'mage/translate'
], function (ko, Component, $, uiRegistry, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_ProductCertification/certification-panel',

            // Default configuration
            ajaxUrl: '/admin/branch8_certification/ajax/getbycategory',
            categoryFieldSelector: '[name="product[category_ids][]"], [name="product[main_category]"]',
            preloadedData: {},
            hintText: '',
            noNumberText: $t('Complies with standards'),

            // UI State
            certifications: [],
            isVisible: false,
            isLoading: false
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.certifications = ko.observableArray([]);
            this.isVisible = ko.observable(false);
            this.isLoading = ko.observable(false);
            this.hintText = ko.observable(this.hintText);

            // 1. Initial Data Setup: Prioritize injected preloadedData, fall back to source registry (Admin)
            if (this.source && !Object.keys(this.preloadedData).length) {
                this.preloadedData = this.source.get('data.branch8_certifications') || {};
            }

            // 2. Watcher for "main_category" (Admin & Seller Dashboard)
            const self = this;
            const watchTargets = ['main_category', 'sellerCategory'];

            watchTargets.forEach(function (index) {
                uiRegistry.async({index: index})(function (component) {
                    // Update certifications when the field value changes
                    component.on('value', function (val) {
                        self.onCategoryChange(val);
                    });

                    // Handle Knockout observables
                    if (typeof component.value === 'function') {
                        component.value.subscribe(function (val) {
                            self.onCategoryChange(val);
                        });
                        if (component.value()) {
                            self.onCategoryChange(component.value());
                        }
                    } else if (component.value) {
                        self.onCategoryChange(component.value);
                    }
                });
            });

            // 3. Observer: Link to Data Source for saving & deep watching (Main Category only)
            // 3. Observer: Link to Data Source for saving & deep watching (Main Category only)
            ['product_form.product_form_data_source', 'catalogstaging_update_form.catalogstaging_update_form_data_source'].forEach(function(providerName) {
                uiRegistry.async(providerName)(function (source) {
                    self.source = source;
                    self.providerName = providerName;
                    source.on('data.product.main_category', function (val) { self.onCategoryChange(val); });
                    source.on('data.main_category', function (val) { self.onCategoryChange(val); });

                    // Trigger initialization because this async might resolve late
                    const initialVal = source.get('data.product.main_category') !== undefined
                        ? source.get('data.product.main_category')
                        : source.get('data.main_category');
                    if (initialVal && !self.certifications().length) {
                        self.onCategoryChange(initialVal);
                    }
                });
            });

            // 4. Fallback for non-UI-component forms: Direct DOM Listener
            let $mainCatInput = $('[name="product[main_category]"]');
            if ($mainCatInput.length) {
                let initVal = $mainCatInput.is(':radio, :checkbox') ? $mainCatInput.filter(':checked').val() : $mainCatInput.val();
                if (initVal) {
                    self.onCategoryChange(initVal);
                }
            }
            $(document).on('change', '[name="product[main_category]"]', function () {
                self.onCategoryChange($(this).val());
            });

            return this;
        },

        /**
         * Normalizes category input and triggers load
         * @param {String|Array} value
         */
        onCategoryChange: function (value) {
            let ids = [];
            if (Array.isArray(value)) {
                ids = value.map(Number).filter(Boolean);
            } else if (value) {
                ids = [parseInt(value)].filter(Boolean);
            }

            // Prevent redundant AJAX calls if IDs haven't changed
            const idsHash = JSON.stringify(ids.sort());
            if (this._lastIdsHash === idsHash) {
                return;
            }
            this._lastIdsHash = idsHash;

            if (ids.length) {
                this.loadCertifications(ids);
            } else {
                this.certifications([]);
                this.isVisible(false);
            }
        },

        /**
         * Loads certification types via AJAX and maps to observables
         * @param {Array} ids
         */
        loadCertifications: function (ids) {
            this.isLoading(true);
            $.ajax({
                url: this.ajaxUrl,
                data: { category_ids: ids },
                type: 'GET',
                dataType: 'json',
                success: (response) => {
                    const list = (response && response.certifications) || [];
                    const mapped = list.map((item) => {
                        const idStr = String(item.id);
                        const savedVal = this.preloadedData[idStr];

                        const row = {
                            id: item.id,
                            name: item.name,
                            icon_url: item.icon_url,
                            isChecked: ko.observable(savedVal !== undefined),
                            value: ko.observable(savedVal || '')
                        };

                        // Reactive sync back to form data source
                        row.isChecked.subscribe(() => { this.syncToSource(); });
                        row.value.subscribe(() => { this.syncToSource(); });

                        return row;
                    });

                    this.certifications(mapped);
                    this.isVisible(mapped.length > 0);
                    this.syncToSource();
                },
                error: () => {
                    this.certifications([]);
                    this.isVisible(false);
                },
                complete: () => {
                    this.isLoading(false);
                }
            });
        },

        /**
         * Syncs selected certifications to the main Magento form data source
         */
        syncToSource: function () {
            const output = {};
            this.certifications().forEach((cert) => {
                if (cert.isChecked()) {
                    output[String(cert.id)] = cert.value() || '';
                }
            });

            const json = JSON.stringify(output);

            // Case 1: Standard Magento Admin Form (UI Component)
            if (this.source) {
                if (this.providerName === 'catalogstaging_update_form.catalogstaging_update_form_data_source') {
                    // Staging Update DataProvider maps fields differently
                    this.source.set('data.branch8_certifications_post', json);
                    this.source.set('root.branch8_certifications_post', json);
                } else {
                    this.source.set('data.product.branch8_certifications_post', json);
                }
            }

            // Case 2: Custom HTML Form (Seller Dashboard)
            // Injecting into a hidden field to ensure POST submission
            let $hidden = $('#branch8-certifications-post-hidden');
            if (!$hidden.length) {
                $hidden = $('<input type="hidden" id="branch8-certifications-post-hidden" name="product[branch8_certifications_post]">')
                    .appendTo('.branch8-certification-field');
            }
            $hidden.val(json);
        },
        /**
         * Placeholder text when value is empty.
         * @return {string}
         */
        getPlaceholder: function () {
            return this.noNumberText;
        }
    });
});

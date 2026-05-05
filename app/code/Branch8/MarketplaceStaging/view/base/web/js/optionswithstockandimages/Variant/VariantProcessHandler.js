define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    "mage/translate",
    'mage/template',
    'plugins/DOMPurify',
    './List',
    './DataModel',
    'Branch8_MarketplaceStaging/js/optionswithstockandimages/parseOptions',
    'Branch8_MarketplaceStaging/js/optionswithstockandimages/GetCustomOptions',
    'Branch8_MarketplaceStaging/js/optionswithstockandimages/SwitchIsSyn',
    'Branch8_MarketplaceStaging/js/optionswithstockandimages/Until',
    'Webkul_Marketplace/js/product/additional-validators',
    './CheckWKVariantListValidator',
    'uiRegistry',
], function (
    $,
    alert,
    $t,
    mageTemplate,
    DOMPurify,
    VariantList,
    variantDataModel,
    parseOptions,
    getCustomOptions,
    SwitchIsSyn,
    Until,
    additionalValidators,
    CheckWKVariantListValidator,
    registry
) {
    const variantProgressTmpl = mageTemplate('#wk-variation-form-template');
    additionalValidators.registerValidator(CheckWKVariantListValidator);
    /**
     *
     */
    return {
        /**
         *
         */
        config: {},

        customoptioncurl: null,

        syncUrl: null,
        /**
         *
         * @param config
         */
        init: function (config) {
            this.config = config;
            if (this.config.customoptioncurl) {
                this.customoptioncurl = this.config.customoptioncurl
            }
            if (this.config.syncurl) {
                this.syncUrl = this.config.syncurl;
            }
            VariantList.setConfig(this.config);
            return this;
        },
        /**
         *
         */
        disableToolBar: function () {
            $('#save-btn,#save-draft-duplicate-btn,#save-draft-btn')
                .attr('disabled', 'true')
                .prop('disabled', true);
        },
        /**
         *
         */
        enableToolBar: function () {
            $('#save-btn,#save-draft-duplicate-btn,#save-draft-btn')
                .removeAttr('disabled')
                .removeProp('disabled');
        },
        /**
         *
         */
        listen: function () {
            const self = this, body = $("body");
            body.on('customOptionInitComplete', async function (event, data) {
                var savedData = window.variationSavedData || self.config.saved;
                if (savedData) {
                    if (!variantDataModel.getData('currentVariationComboData') || variantDataModel.getData('currentVariationComboData').length === 0) {
                        variantDataModel.setData('currentVariationComboData', savedData);
                    }
                    let checkFollowSimpleSkuCostSetting = true,
                        checkFollowSimpleSkuPriceSetting = true;
                    var currentSaved = variantDataModel.getData('currentVariationComboData');
                    $.each(currentSaved, function (key, value) {
                        if (value.hasOwnProperty('follow_simple_sku_cost_setting') && parseInt(value.follow_simple_sku_cost_setting) === 0) {
                            checkFollowSimpleSkuCostSetting = false;
                        }
                        if (value.hasOwnProperty('follow_simple_sku_price_setting') && parseInt(value.follow_simple_sku_price_setting) === 0) {
                            checkFollowSimpleSkuPriceSetting = false;
                        }
                    });
                    variantDataModel.setData('followSimpleSkuCostSetting', checkFollowSimpleSkuCostSetting);
                    variantDataModel.setData('followSimpleSkuPriceSetting', checkFollowSimpleSkuPriceSetting);
                }
                await self.buildVariantFormData();
            });
            body.on('change', '#follow_simple_sku_cost_setting', this.followSimpleSkuCostSettingHandlerClick.bind(self));
            body.on('change', '#follow_simple_sku_price_setting', this.followSimpleSkuPriceSettingHandlerClick.bind(self));
            body.on('change', '.wkv-cost_setting', function (e) {
                if (!self.isFollowSimpleSkuCostSettingChecked()) {
                    self.setCostAndCommission(Until.getId(this));
                }
            })
            body.on('change', '.wkv-commission_percent', function (e) {
                if (!self.isFollowSimpleSkuCostSettingChecked()) {
                    self.setCostAndCommission(Until.getId(this));
                }
            });
            body.on('change', '.wkv-cost', function (e) {
                if (!self.isFollowSimpleSkuCostSettingChecked()) {
                    self.setCostAndCommission(Until.getId(this));
                }
            });
            body.on('change', '.wkv-price', function (e) {
                self.setCostAndCommission(Until.getId(this));
            });
            body.on('change', '.switch-is-sync', function (e) {
                var id = Until.getId(this);
                const skuInput = '#wkv-sku-' + id,
                    stockInput = '#wkv-stock-' + id,
                    lockSkuInput = '#wkv-is_lock_sku-' + id,
                    weighInput = '#wkv-weight-' + id;
                const checkBox = this;
                if ($(this).is(":checked")) {
                    if ($(skuInput).length) {
                        if ($(skuInput).val() === '') {
                            alert({'content': $t("Please enter SKU.")});
                            $(this).prop('checked', !$(this).prop('checked'));
                            e.preventDefault();
                            return false;
                        }
                        var data = {
                            sku: $(skuInput).val()
                        };
                        SwitchIsSyn(data, self.syncUrl, function (response) {
                            if (response && response.hasOwnProperty('sku')) {
                                if ($(stockInput).length) {
                                    $(stockInput).val(response.stock);
                                }
                                if ($(weighInput).length) {
                                    $(weighInput).val(response.weight);
                                }
                                if ($(skuInput).length) {
                                    $(skuInput).val(response.sku);
                                    $(skuInput).prop('disabled', true);
                                }
                                if ($(stockInput).length) {
                                    $(stockInput).prop('disabled', true);
                                }
                                // Update internal data after sync
                                self.updateCurrentVariationData(id);
                                if (response.hasOwnProperty('images') && response.images.length) {
                                    var element = '#wk-variation-row-' + id + 'image';
                                    if ($(element).length) {
                                        $(element).parent().closest('.data-grid-file-uploader')
                                            .children('.wk-img-box').remove();
                                        var imageIndex = response.images.length;
                                        $.each(response.images, function (key, file) {
                                            var progressTmpl = mageTemplate('#new-uploaded-image-template'),
                                                uploadedImage;
                                            uploadedImage = progressTmpl({
                                                data: {
                                                    id: id,
                                                    response: file,
                                                    imageIndex: key
                                                }
                                            })
                                            $(element).parent().before(DOMPurify.sanitize(uploadedImage));
                                        });
                                        $(element).attr('data-image-count', imageIndex);
                                    }
                                }
                            } else {
                                alert({'content': response});
                                $(checkBox).prop('checked', !$(checkBox).prop('checked'));
                                return false;
                            }
                        }, function (response) {
                            alert({'content': response});
                            $(checkBox).prop('checked', !$(checkBox).prop('checked'));
                            return false;
                        })
                    }
                } else {
                    if ($(skuInput).length) {
                        if ($(lockSkuInput).length && parseInt($(lockSkuInput).val()) !== 1) {
                            $(skuInput).prop('disabled', false);
                        }
                    }
                    if ($(stockInput).length) {
                        $(stockInput).prop('disabled', false);
                    }
                }
            })
            body.on("change", ".wk-variation-table .wkv-image", this.uploadImage.bind(self));

            // Live persistence of edits in the modal
            body.on('input change', '#wk-variation-form input, #wk-variation-form select', function (e) {
                const name = $(this).attr('name');
                if (!name) return;

                const match = name.match(/^wkvariation\[(\d+)\]\[(.+)\]$/);
                if (match) {
                    const index = match[1];
                    const field = match[2];
                    const val = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val();

                    let currentData = variantDataModel.getData('currentVariationComboData') || [];
                    if (currentData[index]) {
                        currentData[index][field] = val;
                        variantDataModel.setData('currentVariationComboData', currentData);
                        VariantList.updateWkManageVariation();
                    }
                }
            });
        },

        /**
         * Update specific row in currentVariationComboData from DOM
         * @param {string|number} index
         */
        updateCurrentVariationData: function (index) {
            let currentData = variantDataModel.getData('currentVariationComboData') || [];
            if (currentData[index]) {
                const row = $(`#wk-variation-form tr`).find(`[name^="wkvariation[${index}]"]`);
                row.each(function () {
                    const name = $(this).attr('name');
                    const fieldMatch = name.match(/\[([^\]]+)\]$/);
                    if (fieldMatch) {
                        const field = fieldMatch[1];
                        currentData[index][field] = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val();
                    }
                });
                variantDataModel.setData('currentVariationComboData', currentData);
                VariantList.updateWkManageVariation();
            }
        },
        /**
         * Upload combo image
         */
        uploadImage: function (event) {
            const element = event.currentTarget;
            var self = this,
                linkUrl = self.config.linkurl + "?form_key=" + window.FORM_KEY,
                dataId = $(element).attr('data-id'),
                imageIndex = 0,
                files = $(element)[0].files;
            $.each(files, function (key, file) {
                const data = new FormData();
                data.append('image', file);
                $.ajax({
                    type: "POST",
                    url: linkUrl,
                    enctype: 'multipart/form-data',
                    mimeType: "multipart/form-data",
                    data: data,
                    contentType: false,
                    cache: false,
                    processData: false,
                    beforeSend: function () {
                        $(element).closest(".data-grid-file-uploader").addClass("_loading");
                    },
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response.error) {
                            $(element).closest(".data-grid-file-uploader").removeClass("_loading");
                            alert({'content': $t("We are unable to recognize or support this file extension type.")});
                            return;
                        }
                        let dataImageCount = $(element).attr('data-image-count'),
                            progressTmpl = mageTemplate('#new-uploaded-image-template'),
                            uploadedImage;
                        imageIndex = parseInt(dataImageCount) + parseInt(key);
                        $(element).closest(".data-grid-file-uploader").removeClass("_loading");
                        uploadedImage = progressTmpl({
                            data: {
                                id: dataId,
                                response,
                                imageIndex: imageIndex
                            }
                        })
                        $(element).parent().before(DOMPurify.sanitize(uploadedImage));
                        if (key === files.length - 1) {
                            imageIndex++;
                            $(element).attr('data-image-count', imageIndex);
                        }
                    },
                    error: function (response) {
                        $(element).closest(".data-grid-file-uploader").removeClass("_loading");
                    }
                });
            });
        },
        /**
         *
         * @param id
         */
        setCostAndCommission: function (id) {
            const self = this;
            let price = Until.getPrice(id),
                cost = Until.getCost(id),
                commissionPercent = Until.getCommissionPercent(id),
                costSetting = Until.getCostSetting(id),
                cost_value = Until.calculateCost(costSetting, commissionPercent, cost, price),
                cost_commission_percent = Until.calculateCommissionRate(cost_value, price),
                costElement = '#wkv-cost-' + id,
                commissionElement = '#wkv-commission_percent-' + id,
                costSettingElement = '#wkv-cost_setting-input-' + id;
            if ($(costElement).length) {
                $(costElement).val(cost_value);
                $(commissionElement).val(cost_commission_percent);
                if (parseInt(costSetting) === 1) {
                    //Fixed commission
                    if ($(commissionElement).length) {
                        $(commissionElement).prop('readonly', false);
                    }
                    $(costElement).prop('readonly', true);
                    if ($(costSettingElement).length) {
                        $(costSettingElement).val('1');
                    }
                } else {
                    //Manually input cost
                    if ($(commissionElement).length) {
                        $(commissionElement).prop('readonly', true);
                    }
                    $('#wkv-cost-' + id).prop('readonly', false);
                    if ($(costSettingElement).length) {
                        $(costSettingElement).val('0');
                    }
                }
            }
            if (self.isFollowSimpleSkuCostSettingChecked()) {
                $('.wkv-cost').prop('readonly', true);
                $('.wkv-commission_percent').prop('readonly', true);
            }
            self.showCommissionSourceText(id);
        },
        /**
         *
         * @param val
         * @returns {string}
         */
        showCommissionSource: function (val) {
            var commissionSourceTxt = '';
            if (sellerCommissionData && Object.keys(sellerCommissionData).length) {
                if (parseInt(val) === 2) {
                    commissionSourceTxt = $t('Based on Active Period: %1 to %2').replace('%1', sellerCommissionData.active_contract.from).replace('%2', sellerCommissionData.active_contract.to);
                } else if (parseInt(val) === 3) {
                    commissionSourceTxt = $t('Based on Default Settings');
                } else if (parseInt(val) === 1) {
                    commissionSourceTxt = $t('Manually Input');
                }
                return commissionSourceTxt;
            }
            return '';
        },
        /**
         *
         * @param id
         */
        showCommissionSourceText: function (id) {
            let commissionPercent = Until.getCommissionPercent(id),
                costSetting = Until.getCostSetting(id),
                commissionSource = this.getCommissionSource(commissionPercent, costSetting);
            if (commissionSource && commissionSource.length) {
                $('#wkv-commission_source-' + id).html(commissionSource[1]);
            }
        },
        /**
         *
         * @param commissionPercent
         * @param costSetting
         * @returns {string[]|(number|string)[]}
         */
        getCommissionSource: function (commissionPercent, costSetting) {
            /** Commission source */
            if (sellerCommissionData && Object.keys(sellerCommissionData).length) {
                var sellerCommissionRate = sellerCommissionData.commission_rate;
                var sellerDefaultCommissionRate = sellerCommissionData.default_commission_rate;
                var commissionSourceTxt = $t('N/A');
                var gCommissionSource = '';
                // the contract is being executed
                if (typeof sellerCommissionData.active_contract != 'boolean') {
                    if (parseInt(costSetting) === 1) {
                        if (commissionPercent === sellerCommissionRate) {
                            // Active Period
                            gCommissionSource = 2;
                        } else {
                            // Manually input
                            gCommissionSource = 1;
                        }
                    } else {
                        // Manually input
                        gCommissionSource = 1;
                    }
                } else {
                    if (parseInt(costSetting) === 1 && commissionPercent === sellerDefaultCommissionRate) {
                        // Based on Default Settings
                        gCommissionSource = 3;
                    } else {
                        gCommissionSource = 1;
                    }
                }
                commissionSourceTxt = this.showCommissionSource(gCommissionSource);
                return [gCommissionSource, commissionSourceTxt];
            }
            return ['', ''];
        },
        /**
         *
         * @returns {boolean}
         */
        isFollowSimpleSkuCostSettingChecked: function () {
            var follow_simple_sku_cost_setting = $('#follow_simple_sku_cost_setting');
            return !!(follow_simple_sku_cost_setting.length && follow_simple_sku_cost_setting.is(':checked'));

        },
        /**
         *
         * @returns {Promise<void>}
         */
        buildVariantFormData: async function (open) {
            const isEmpty = (obj) => Object.keys(obj).length === 0;
            if (isEmpty(this.config)) {
                return;
            }
            const self = this;
            // Ensure data is initialized
            if (!variantDataModel.getData('currentVariationComboData') || variantDataModel.getData('currentVariationComboData').length === 0) {
                var initialData = window.variationSavedData || self.config.saved;
                if (initialData) {
                    variantDataModel.setData('currentVariationComboData', initialData);
                }
            }
            self.disableToolBar();
            const params = await self.prepareParams();
            if (!params || !params.variantDataModel || params.variantDataModel.getData('combination').length === 0) {
                console.warn("Could not prepare variation parameters. Aborting build.");
                self.enableToolBar();
                return;
            }
            await VariantList.rebuildContent(variantProgressTmpl(self.getInputData()));
            VariantList.updateWkManageVariation();
            VariantList.initValidator();
            self.enableToolBar();
            if (open) {
                VariantList.setWeight().open();
            }
        },

        /**
         *
         * @returns {{getData: function(*): {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, setData: function(*, *): this}}
         */
        getVariantDataModel: function () {
            return variantDataModel;
        },
        /**
         *
         * @returns {{getData: function(*): {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, setData: function(*, *): this}}
         */
        getVariantList: function () {
            return VariantList;
        },
        /**
         *
         * @returns {Promise<{noOfField: number, count: *, variantDataModel: {getData: function(*): {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, setData: function(*, *): this}}>}
         */
        prepareParams: async function () {
            let noOfField = 0,
                disabled = $("#product_options_container").find(':input:disabled').removeAttr('disabled'),
                data = await this.getFormData(),
                optionUrl = this.config.customoptioncurl;
            disabled.attr('disabled', 'disabled');

            if (!data) {
                return { noOfField, count: 0, variantDataModel };
            }

            const customOptions = await getCustomOptions(data, optionUrl);
            if (!customOptions || !Array.isArray(customOptions) || customOptions.length === 0) {
                 // If we have existing combinations, don't wipe them if AJAX fails
                 if (variantDataModel.getData('combination') && variantDataModel.getData('combination').length > 0) {
                     return { noOfField, count: variantDataModel.getData('count'), variantDataModel };
                 }
            }

            const {
                oldValues,
                values,
                titles,
                count,
                skus
            } = await parseOptions(customOptions);

            const combinations = Until.combineNArrays(values);

            variantDataModel
                .setData('values', values)
                .setData('oldValues', oldValues)
                .setData('comb', combinations)
                .setData('combination', combinations)
                .setData('oldComb', Until.combineNArrays(oldValues))
                .setData('skus', skus)
                .setData('titles', titles)
                .setData('wkvariationscomb', combinations);

            variantDataModel.setData('weightDisabled', $("[name='product[product_has_weight]']").is(":checked"));
            return {
                noOfField,
                count,
                variantDataModel
            }
        },
        /**
         *
         */
        getFormData: async function () {
            if (this.config.formDataProviderConfig.type === 'form') {
                return $(this.config.formDataProviderConfig.formId).serialize();
            }
            if (this.config.formDataProviderConfig.type === 'uiComponent') {
                return registry.get(this.config.formDataProviderConfig.componentId).data;
            }
        },
        /**
         *
         */
        openList: function () {
            VariantList.initValidator()
                .open().setWeight();
            return this;
        },
        /**
         *
         * @returns {Promise<void>}
         */
        click: async function () {
            let isRequired = true,
                hasSpecialCharacter = false,
                simpleSkuCostSettingElem = '#follow_simple_sku_cost_setting',
                simpleSkuPriceSettingElem = '#follow_simple_sku_price_setting';
            const self = this;
            const {
                noOfField,
                count,
                variantDataModel
            } = await this.prepareParams();
            if (!variantDataModel.getData('values') || !variantDataModel.getData('values').length) {
                alert({'content': $t("No option available.")});
                return;
            }
            if (parseInt(noOfField) === parseInt(count) && !variantDataModel.getData('values').length) {
                isRequired = false
            }
            if (hasSpecialCharacter) {
                alert({'content': $t("Don't use special characters in Custom options to save swatch and variation")});
                return;
            }
            if (!isRequired) {
                alert({'content': $t("Atleast one field should be selected as a required.")});
                return;
            }
            if (!variantDataModel.getData('values').length) {
                alert({'content': $t("Combination can not be created.")});
                return;
            }
            // when not change ,we will show modal , otherwise rebuil data then show modal
            const notChange = JSON.stringify(
                variantDataModel.getData('wkvariationscomb')) === JSON.stringify(variantDataModel.getData('comb')
            );
            // not sure with this;
            if (variantDataModel.getData('comb').length === 0) {
                return;
            }
            await VariantList.rebuildContent(variantProgressTmpl(this.getInputData()));
            !!variantDataModel.getData('followSimpleSkuCostSetting') ?
                $(simpleSkuCostSettingElem).prop("checked", true).val(1).trigger('change')
                : $(simpleSkuCostSettingElem).removeAttr("checked").val(0).trigger('change');
            !!variantDataModel.getData('followSimpleSkuPriceSetting') ?
                $(simpleSkuPriceSettingElem).prop("checked", true).val(1).trigger('change')
                : $(simpleSkuPriceSettingElem).removeAttr("checked").val(0).trigger('change')

            VariantList.initValidator().open().setWeight();
        },

        /**
         *
         * @returns {{data: {combination: {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, oldComb: {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, sku: {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, title: {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, savedData: {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}, weightDisabled: {combination: {}, oldComb: {}, sku: {}, title: {}, savedData: {}, weightDisabled: {}}}}}
         */
        getInputData: function () {
            return {
                data: {
                    combination: variantDataModel.getData('comb'),
                    oldComb: variantDataModel.getData('oldComb'),
                    skus: variantDataModel.getData('skus'),
                    title: variantDataModel.getData('titles'),
                    savedData: variantDataModel.getData('savedData'),
                    weightDisabled: variantDataModel.getData('weightDisabled'),
                    currentVariationComboData: variantDataModel.getData('currentVariationComboData'),
                    followSimpleSkuCostSetting: variantDataModel.getData('followSimpleSkuCostSetting'),
                    followSimpleSkuPriceSetting: variantDataModel.getData('followSimpleSkuPriceSetting'),
                    defaultData: {
                        'cost': Until.getProductCost(),
                        'price': Until.getProductPrice(),
                        'commissionPercent': Until.getProductCommissionPercent(),
                        'costSetting': Until.getProductCostSetting()
                    }
                }
            };
        },
        /**
         *
         * @param event
         * @param data
         */
        followSimpleSkuCostSettingHandlerClick: function (event) {
            variantDataModel.setData('followSimpleSkuCostSetting', !!$(event.currentTarget).is(":checked"));
            this.processFollowSimpleSkuCostSetting();
        },

        /**
         *
         * @param event
         */
        followSimpleSkuPriceSettingHandlerClick: function (event) {
            variantDataModel.setData('followSimpleSkuPriceSetting', !!$(event.currentTarget).is(":checked"));
            this.processFollowSimpleSkuPriceSetting();
        },
        /**
         *
         */
        processFollowSimpleSkuPriceSetting: function () {
            const isChecked = variantDataModel.getData('followSimpleSkuPriceSetting');
            const currentVariationComboData = variantDataModel.getData('currentVariationComboData');
            if (isChecked) {
                var price = Until.getProductPrice();
                $('.wkv-price').val(price).prop('readonly', true).trigger('change');
                $('.wkv-follow_simple_sku_price_setting').val(1);
            } else {
                for (var i in currentVariationComboData) {
                    if (currentVariationComboData[i].hasOwnProperty('price')) {
                        $('#wkv-price-' + i).val(currentVariationComboData[i].price).trigger('change');
                    }
                }
                $('.wkv-follow_simple_sku_price_setting').val(0);
                $('.wkv-price').prop('readonly', false);
            }
        },
        /**
         *
         */
        processFollowSimpleSkuCostSetting: function () {
            const self = this, isChecked =
                variantDataModel.getData('followSimpleSkuCostSetting');
            const currentVariationComboData = variantDataModel.getData('currentVariationComboData');
            if (isChecked) {
                const costSetting = $('input[name="product[cost_setting]"]:checked').val(),
                    commissionPercent = Math.round($('input[name="product[commission_percent]"]').val()),
                    cost = $('input[name="product[cost]"]').val(),
                    costs = $('.wkv-cost');
                costs.val(cost);
                $('.wkv-cost_setting').val(costSetting).prop('disabled', true);
                $('.wkv-cost_setting-input').val(costSetting);
                $('.wkv-commission_percent').val(commissionPercent);
                $('.wkv-follow_simple_sku_cost_setting').val(1);
                if (costs.length) {
                    for (var i = 0; i < costs.length; i++) {
                        var id = DOMPurify.sanitize($(costs[i]).data('id')?.toString());
                        self.setCostAndCommission(id);
                    }
                }
            } else {
                for (var i in currentVariationComboData) {
                    if (currentVariationComboData[i].hasOwnProperty('cost_setting')) {
                        $('#wkv-cost_setting-' + i).val(currentVariationComboData[i].cost_setting);
                        $('#wkv-cost_setting-input-' + i).val(currentVariationComboData[i].cost_setting);
                    }
                    if (currentVariationComboData[i].hasOwnProperty('commission_percent')) {
                        $('#wkv-commission_percent-' + i).val(currentVariationComboData[i].commission_percent);
                    }
                    if (currentVariationComboData[i].hasOwnProperty('cost')) {
                        $('#wkv-cost-' + i).val(currentVariationComboData[i].cost);
                    }
                }
                $('.wkv-follow_simple_sku_cost_setting').val(0);
                $('.wkv-cost_setting').prop('disabled', false).trigger('change');
            }
        }
    }
})

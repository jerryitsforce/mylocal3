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
    './CheckWKVariantListValidator'
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
    CheckWKVariantListValidator
) {
    const variantProgressTmpl = mageTemplate('#wk-variation-form-template');
    additionalValidators.registerValidator(CheckWKVariantListValidator);

    function getId(element) {
        return DOMPurify.sanitize($(element).data('id')?.toString());
    }

    /**
     *
     * @returns {boolean}
     */
    // Is follow simple sku cost setting checked
    function isFollowSimpleSkuCostSettingChecked() {
        var follow_simple_sku_cost_setting = $('#follow_simple_sku_cost_setting');
        if (follow_simple_sku_cost_setting.length && follow_simple_sku_cost_setting.is(':checked')) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param id
     * @returns {number}
     */
    function getPrice(id) {
        var price = 0;
        if ($('#wkv-price-' + id).length && $('#wkv-price-' + id).val() > 0) {
            price = $('#wkv-price-' + id).val();
        }
        if (price == 0) {
            price = getProductPrice();
        }
        return price;
    }

    /**
     *
     * @param formattedSaved
     */
    function setPrice(formattedSaved) {
        var price = getProductPrice();
        var defaultPrice = false;
        for (var i = 0; i < formattedSaved.length; i++) {
            if (!formattedSaved[i].hasOwnProperty('price') || formattedSaved[i].price == 0) {
                defaultPrice = true;
                break;
            }
        }
        if (formattedSaved.length == 0 || (defaultPrice && price)) {
            $('.wkv-price').val(price);
        }
    }

    /**
     *
     * @returns {*}
     */
    function getProductPrice() {
        var price = 0;
        if ($('input[name="product[special_price]"]').length && $('input[name="product[special_price]"]').val() > 0) {
            price = $('input[name="product[special_price]"]').val();
        } else {
            price = $('input[name="product[price]"]').val();
        }
        return price;
    }

    /**
     *
     * @returns {number}
     */
    function getProductCostSetting() {
        var costSetting = 1;
        if ($('input[name="product[cost_setting]"]').length) {
            costSetting = $('input[name="product[cost_setting]"]').val();
        }
        return costSetting;
    }

    /**
     *
     * @returns {number}
     */
    function getProductCost() {
        var cost = 0;
        if ($('input[name="product[cost]"]').length && $('input[name="product[cost]"]').val() > 0) {
            cost = $('input[name="product[cost]"]').val();
        }
        return cost;
    }

    /**
     *
     * @returns {number}
     */
    function getProductCommissionPercent() {
        var commissionPercent = 0;
        if ($('input[name="product[commission_percent]"]').length && $('input[name="product[commission_percent]"]').val() > 0) {
            commissionPercent = $('input[name="product[commission_percent]"]').val();
        }
        return commissionPercent;
    }

    /**
     *
     * @param id
     * @returns {number}
     */
    function getCostSetting(id) {
        var costSetting = 1;
        if ($('#wkv-cost_setting-' + id).length && $('#wkv-cost_setting-' + id).val() == 0) {
            costSetting = 0;
        }
        return costSetting;
    }

    /**
     *
     * @param id
     * @returns {number}
     */
    function getCommissionPercent(id) {
        var commissionPercent = 0;
        if ($('#wkv-commission_percent-' + id).length) {
            if(isAllowNegativeGrossProfit){
                commissionPercent = $('#wkv-commission_percent-' + id).val();
            }else if($('#wkv-commission_percent-' + id).val() > 0){
                commissionPercent = $('#wkv-commission_percent-' + id).val();
            }
        }
        if (commissionPercent == 0) {
            commissionPercent = getProductCommissionPercent();
        }
        return commissionPercent;
    }

    /**
     *
     * @param id
     * @returns {number}
     */
    function getCost(id) {
        var cost = 0;
        if ($('#wkv-cost-' + id).length && $('#wkv-cost-' + id).val() > 0) {
            cost = $('#wkv-cost-' + id).val();
        }
        if (cost == 0) {
            cost = getProductCost();
        }
        return cost;
    }

    /**
     *
     * @param id
     */
    function setCostAndCommission(id) {
        var price = getPrice(id);
        var cost = getCost(id);
        var commissionPercent = getCommissionPercent(id);
        var costSetting = getCostSetting(id);
        var cost_value = calculateCost(costSetting, commissionPercent, cost, price);
        var cost_commission_percent = calculateCommissionRate(cost_value, price);
        if ($('#wkv-cost-' + id).length) {
            $('#wkv-cost-' + id).val(cost_value);
            $('#wkv-commission_percent-' + id).val(cost_commission_percent);
            if (costSetting == 1) {
                //Fixed commission
                if ($('#wkv-commission_percent-' + id).length) {
                    $('#wkv-commission_percent-' + id).prop('readonly', false);
                }
                $('#wkv-cost-' + id).prop('readonly', true);
                if ($('#wkv-cost_setting-input-' + id).length) {
                    $('#wkv-cost_setting-input-' + id).val('1');
                }
            } else {
                //Manually input cost
                if ($('#wkv-commission_percent-' + id).length) {
                    $('#wkv-commission_percent-' + id).prop('readonly', true);
                }
                $('#wkv-cost-' + id).prop('readonly', false);
                if ($('#wkv-cost_setting-input-' + id).length) {
                    $('#wkv-cost_setting-input-' + id).val('0');
                }
            }
        }
        if (isFollowSimpleSkuCostSettingChecked()) {
            $('.wkv-cost').prop('readonly', true);
            $('.wkv-commission_percent').prop('readonly', true);
        }
        showCommissionSourceText(id);
    }

    /**
     *
     * @param id
     */
    function showCommissionSourceText(id) {
        var commissionPercent = getCommissionPercent(id);
        var costSetting = getCostSetting(id);
        var commissionSource = getCommissionSource(commissionPercent, costSetting);
        if (commissionSource && commissionSource.length) {
            $('#wkv-commission_source-' + id).html(commissionSource[1]);
        }
    }

    /**
     *
     * @param commissionPercent
     * @param costSetting
     * @returns {string[]|(number|string)[]}
     */
    function getCommissionSource(commissionPercent, costSetting) {
        /** Commission source */
        if (sellerCommissionData && Object.keys(sellerCommissionData).length) {
            var sellerCommissionRate = sellerCommissionData.commission_rate;
            var sellerDefaultCommissionRate = sellerCommissionData.default_commission_rate;
            var commissionSourceTxt = $t('N/A');
            var gCommissionSource = '';
            // the contract is being executed
            if (typeof sellerCommissionData.active_contract != 'boolean') {
                if (costSetting == 1) {
                    if (commissionPercent == sellerCommissionRate) {
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
                if (costSetting == 1 && commissionPercent == sellerDefaultCommissionRate) {
                    // Based on Default Settings
                    gCommissionSource = 3;
                } else {
                    gCommissionSource = 1;
                }
            }
            commissionSourceTxt = showCommissionSource(gCommissionSource);
            return [gCommissionSource, commissionSourceTxt];
        }
        return ['', ''];
    }

    /**
     *
     * @param val
     * @returns {string}
     */
    function showCommissionSource(val) {
        var commissionSourceTxt = '';
        if (sellerCommissionData && Object.keys(sellerCommissionData).length) {
            if (val == 2) {
                commissionSourceTxt = $t('Based on Active Period: %1 to %2').replace('%1', sellerCommissionData.active_contract.from).replace('%2', sellerCommissionData.active_contract.to);
            } else if (val == 3) {
                commissionSourceTxt = $t('Based on Default Settings');
            } else if (val == 1) {
                commissionSourceTxt = $t('Manually Input');
            }
            return commissionSourceTxt;
        }
        return '';
    }

    /**
     *
     * @param costSetting
     * @param commissionPercent
     * @param cost
     * @param price
     * @returns {number|*}
     */
    function calculateCost(costSetting, commissionPercent, cost, price) {
        commissionPercent = parseInt(commissionPercent) || 0;
        if (costSetting == 0) {
            return cost;
        } else {
            return Math.round(price - (price * (commissionPercent / 100)));
        }
    }

    /**
     *
     * @param cost
     * @param price
     * @returns {number}
     */
    function calculateCommissionRate(cost, price) {
        cost = parseInt(cost) || 0;
        price = parseInt(price) || 0;
        if (price === 0) {
            return 0;
        }
        var rate = Math.round((100 - (cost * 100 / price)));
        if(isAllowNegativeGrossProfit){
            return rate;
        }else if (rate < 0) {
            rate = 0;
        }
        return rate;
    }

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
            if (this.config.syncUrl) {
                this.syncUrl = this.config.syncUrl;
            }
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
            const self = this;
            $('body').on('customOptionInitComplete', async function (event, data) {
                if (window.variationSavedData) {
                    variantDataModel.setData('currentVariationComboData', window.variationSavedData);
                    let checkFollowSimpleSkuCostSetting = true,
                        checkFollowSimpleSkuPriceSetting = true;
                    $.each(window.variationSavedData, function (key, value) {
                        if (value.hasOwnProperty('follow_simple_sku_cost_setting') && value.follow_simple_sku_cost_setting == 0) {
                            checkFollowSimpleSkuCostSetting = false;
                        }
                        if (value.hasOwnProperty('follow_simple_sku_price_setting') && value.follow_simple_sku_price_setting == 0) {
                            checkFollowSimpleSkuPriceSetting = false;
                        }
                    });
                    variantDataModel.setData('followSimpleSkuCostSetting', checkFollowSimpleSkuCostSetting);
                    variantDataModel.setData('followSimpleSkuPriceSetting', checkFollowSimpleSkuPriceSetting);
                }
                self.buildVariantFormData();
            });
            $("body").on('change', '#follow_simple_sku_cost_setting', this.followSimpleSkuCostSettingHandlerClick.bind(self));
            $("body").on('change', '#follow_simple_sku_price_setting',this.followSimpleSkuPriceSettingHandlerClick.bind(self));
            $('body').on('change', '.wkv-cost_setting', function (e) {
                if (!isFollowSimpleSkuCostSettingChecked()) {
                    var id = getId(this);
                    setCostAndCommission(id);
                }
            })
            $('body').on('change', '.wkv-commission_percent', function (e) {
                if (!isFollowSimpleSkuCostSettingChecked()) {
                    var id = getId(this);
                    setCostAndCommission(id);
                }
            });
            $('body').on('change', '.wkv-cost', function (e) {
                if (!isFollowSimpleSkuCostSettingChecked()) {
                    var id = getId(this);
                    setCostAndCommission(id);
                }
            });
            $('body').on('change', '.wkv-price', function (e) {
                var id = getId(this);
                setCostAndCommission(id);
            });
            $('body').on('change', '.switch-is-sync', function (e) {
                var id = getId(this);
                const checkBox = this;
                if ($(this).is(":checked")) {
                    if ($('#wkv-sku-' + id).length) {
                        if ($('#wkv-sku-' + id).val() === '') {
                            alert({'content': $t("Please enter SKU.")});
                            $(this).prop('checked', !$(this).prop('checked'));
                            e.preventDefault();
                            return false;
                        }
                        var data = {
                            sku: $('#wkv-sku-' + id).val()
                        };
                        SwitchIsSyn(data, self.syncUrl, function (response) {
                            if (response && response.hasOwnProperty('sku')) {
                                if ($('#wkv-stock-' + id).length) {
                                    $('#wkv-stock-' + id).val(response.stock);
                                }
                                if ($('#wkv-weight-' + id).length) {
                                    $('#wkv-weight-' + id).val(response.weight);
                                }
                                if ($('#wkv-sku-' + id).length) {
                                    $('#wkv-sku-' + id).val(response.sku);
                                    $('#wkv-sku-' + id).prop('disabled', true);
                                }
                                if ($('#wkv-stock-' + id).length) {
                                    $('#wkv-stock-' + id).prop('disabled', true);
                                }
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
                            console.log(("213123"));
                            alert({'content': response});
                            $(checkBox).prop('checked', !$(checkBox).prop('checked'));
                            return false;
                        })
                    }
                } else {
                    if ($('#wkv-sku-' + id).length) {
                        if ($('#wkv-is_lock_sku-' + id).length && $('#wkv-is_lock_sku-' + id).val() != 1) {
                            $('#wkv-sku-' + id).prop('disabled', false);
                        }
                    }
                    if ($('#wkv-stock-' + id).length) {
                        $('#wkv-stock-' + id).prop('disabled', false);
                    }
                }
            })
        },
        /**
         *
         * @returns {Promise<void>}
         */
        buildVariantFormData: async function (open) {
            const self = this;
            self.disableToolBar();
            await self.prepareParams();
            const inputData = self.getInputData();
            await VariantList.rebuildContent(variantProgressTmpl(self.getInputData()));
            VariantList
                .updateWkManageVariation()
                .initValidator();
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
         * @returns {Promise<void>}
         */
        prepareParams: async function () {
            let isRequired = true,
                noOfField = 0,
                hasSpecialCharacter = false,
                disabled = $("#product_options_container").find(':input:disabled').removeAttr('disabled'),
                data = $("#edit-product").serialize();
            disabled.attr('disabled', 'disabled');
            const {
                oldValues,
                values,
                titles,
                count,
                skus
            } = await parseOptions(await getCustomOptions(data));
            variantDataModel
                .setData('values', values)
                .setData('oldValues', oldValues)
                .setData('comb', Until.combineNArrays(values))
                .setData('combination', Until.combineNArrays(values))
                .setData('oldComb', Until.combineNArrays(oldValues))
                .setData('skus', skus)
                .setData('titles', titles);
            const compare = JSON.stringify(
                variantDataModel.getData('wkvariationscomb')) === JSON.stringify(variantDataModel.getData('comb')
            );
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
                hasSpecialCharacter = false;
            const self=this;
            const {
                noOfField,
                count,
                variantDataModel
            } = await this.prepareParams();
            if (noOfField == count && !variantDataModel.getData('values').length) {
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
            const inputData = this.getInputData();
            await VariantList.rebuildContent(variantProgressTmpl(this.getInputData()));

            !!variantDataModel.getData('followSimpleSkuCostSetting')?
                $("#follow_simple_sku_cost_setting").prop("checked", true).val(1).trigger('change')
            :$("#follow_simple_sku_cost_setting").removeAttr("checked").val(0).trigger('change');
            !!variantDataModel.getData('followSimpleSkuPriceSetting')?
                $("#follow_simple_sku_price_setting").prop("checked", true).val(1).trigger('change')
                :$("#follow_simple_sku_price_setting").removeAttr("checked").val(0).trigger('change');

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
                        'cost': getProductCost(),
                        'price': getProductPrice(),
                        'commissionPercent': getProductCommissionPercent(),
                        'costSetting': getProductCostSetting()
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
            this.processfollowSimpleSkuCostSetting();
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
                var price = getProductPrice();
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
        processfollowSimpleSkuCostSetting: function () {
            const isChecked =
                variantDataModel.getData('followSimpleSkuCostSetting');
            const currentVariationComboData = variantDataModel.getData('currentVariationComboData');
            if (isChecked) {
                const costSetting = $('input[name="product[cost_setting]"]:checked').val(),
                    commissionPercent = Math.round($('input[name="product[commission_percent]"]').val()),
                    cost = $('input[name="product[cost]"]').val(),
                    costs = $('.wkv-cost');
                $('.wkv-cost_setting').val(costSetting).prop('disabled', true);
                $('.wkv-cost_setting-input').val(costSetting);
                $('.wkv-commission_percent').val(commissionPercent);
                $('.wkv-cost').val(cost);
                $('.wkv-follow_simple_sku_cost_setting').val(1);
                if (costs.length) {
                    for (var i = 0; i < costs.length; i++) {
                        var id = DOMPurify.sanitize($(costs[i]).data('id')?.toString());
                        setCostAndCommission(id);
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

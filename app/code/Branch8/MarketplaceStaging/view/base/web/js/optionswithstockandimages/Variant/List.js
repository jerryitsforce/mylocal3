define([
    "ko",
    "jquery",
    "mage/translate",
    "mage/template",
    "underscore",
    "Magento_Ui/js/modal/modal",
    "./Validate",
    "Branch8_MarketplaceStaging/js/optionswithstockandimages/Variant/DataModel",
    "./VariantCombo",
    "../Until",
    'uiRegistry',
    "mage/validation",
], function (ko, $, $t, mageTemplate, _, modal, Validate, dataModel, VariantCombo, Until, registry) {
    'use strict';
    
    return {
        config:{},
        modal: null,
        options: {
            type: 'slide',
            responsive: true,
            innerScroll: false,
            modalClass: 'wk-mv-modal',
            width: '200px',
            title: '管理規格'
        },
        /**
         *
         * @returns {exports}
         */
        setConfig: function (config) {
            this.config = config;
            return this;
        },
        /**
         *
         */
        saveHandle: async function () {
            let form = $("#wk-variation-form");
            const valid = Validate(form);
            if (!valid) {
                return false;
            }
            this.syncDOMToModel();
            this.updateWkManageVariation();
            this.close();
        },
        /**
         *
         * @param array
         */
        convertFormVariationToObject: function (form) {
            return Until.serializeDeepAdvanced(form);
        },
        /**
         *
         * @returns {exports}
         */
        /**
         * Update the hidden input and the model from the DOM state
         * @returns {exports}
         */
        updateWkManageVariation: function () {
            const form = "#wk-variation-form";
            const formElem = document.getElementById("wk-variation-form");

            // 1. If form exists, try to sync its current state to the Model
            if (formElem) {
                const disabled = $(form).find(':input:disabled').removeAttr('disabled');
                const convertedData = this.convertFormVariationToObject(formElem);
                disabled.attr('disabled', true);

                const currentVariationComboData = convertedData?.wkvariation ? convertedData?.wkvariation : [];

                // GUARD: Only update the model if we actually have variations in the form
                if (Array.isArray(currentVariationComboData) && currentVariationComboData.length > 0) {
                    const wkvariationscomb = [];
                    $.each(currentVariationComboData, function (index, value) {
                        if (value.comb) {
                            wkvariationscomb.push(value.comb);
                        }
                    });
                    dataModel.setData('wkvariationscomb', wkvariationscomb);
                    dataModel.setData('currentVariationComboData', currentVariationComboData);
                }
            }

            // 2. ALWAYS update the hidden input/Registry from the Model (Source of Truth)
            // We MUST use the name/value array format for compatibility with CatalogProductSaveAfter.php
            const modelData = dataModel.getData('currentVariationComboData') || [];
            const flatData = this.generateSerializeArrayFromModel(modelData);
            const json = JSON.stringify(flatData);

            if (this.config.formDataProviderConfig.type === 'form') {
                $('body').find('[name="product[wk_manage_variation]"]').remove();
                $("body").find("[name='product[name]']")
                    .after("<input class='input-text admin__control-text' " +
                        "type='hidden' name='product[wk_manage_variation]'" +
                        " data-form-part='product_form' value='" + json + "'>" +
                        "</input>"
                    );
            }
            if (this.config.formDataProviderConfig.type === 'uiComponent' && this.config.formDataProviderConfig.componentId) {
                const prov = registry.get(this.config.formDataProviderConfig.componentId);
                if (prov && prov.data && prov.data.product) {
                    prov.data.product['wk_manage_variation'] = json;
                }
            }
            return this;
        },
        /**
         * Converts the nested DataModel variation objects into a flat name/value array
         * that mimics jQuery.serializeArray() for PHP backend compatibility.
         * @param {Array} modelData
         * @returns {Array}
         */
        generateSerializeArrayFromModel: function (modelData) {
            let result = [];
            if (!Array.isArray(modelData)) return result;

            $.each(modelData, function (index, variation) {
                $.each(variation, function (field, val) {
                    if (field === 'file' && typeof val === 'object' && val !== null) {
                        $.each(val, function (fileIndex, fileVal) {
                            result.push({ name: `wkvariation[${index}][file][${fileIndex}]`, value: fileVal });
                        });
                    } else if (field === 'image' && Array.isArray(val)) {
                         // Some logic in JS uses arrays for multiple images
                         $.each(val, function(imgIndex, imgVal) {
                             result.push({ name: `wkvariation[${index}][image][${imgIndex}]`, value: imgVal });
                         });
                    } else {
                        // Ensure value is a string for serializeArray consistency
                        let strVal = (val === null || val === undefined) ? "" : String(val);
                        result.push({ name: `wkvariation[${index}][${field}]`, value: strVal });
                    }
                });
            });
            return result;
        },
        /**
         * Alias for backward compatibility if needed
         */
        syncDOMToModel: function() {
            return this.updateWkManageVariation();
        },
        /**
         *
         */
        getModalElement: function () {
            const self = this;
            if (!this.modal) {
                this.modal = $('<div>').attr('id', 'wk-mv-modal-wrapper');
                const buttons = [{
                    text: $.mage.__('Save'),
                    class: 'action-default primary',
                    click: self.saveHandle.bind(self),
                }];
                const options = _.extend(this.options, {
                    buttons: buttons,
                    //modalCloseBtnHandler: self.saveHandle.bind(self)
                });
                modal(options, this.modal);
            }
            return this.modal;
        },
        /**
         * appendContent
         */
        rebuildContent: async function (content) {
            this.getModalElement().html(content);
            return this;
        },
        /**
         *
         */
        initValidator: function () {
            const element = `#wk-variation-form`;
            if (!$(element).mage('validation')) {
                $(element).mage('validation', {});
            }
            return this;
        },

        /**
         * open
         */
        open: function () {
            this.getModalElement().modal('openModal');
            return this;
        },

        /**
         * close
         */
        close: function () {
            this.getModalElement().modal('closeModal');
            return this;
        },
        /**
         *
         */
        setWeight: function () {
            var weight = 0;
            const inputNameProductWeight = 'input[name="product[weight]"]';
            if ($(inputNameProductWeight).length && $(inputNameProductWeight).val() > 0) {
                weight = $(inputNameProductWeight).val();
            }
            $('.wkv-weight').each(function () {
                if (parseInt($(this).val()) === 0 || $(this).val() === '') {
                    $(this).val(weight);
                }
            });
            return this;
        }
    }
})

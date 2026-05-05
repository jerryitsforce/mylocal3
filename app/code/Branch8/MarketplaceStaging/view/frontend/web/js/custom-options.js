define([
    'jquery',
    'mage/template',
    'plugins/DOMPurify',
    'Branch8_MarketplaceStaging/js/optionswithstockandimages/Variant/VariantProcessHandler',
    "mage/translate",
    'jquery/ui',
    'jquery/file-uploader',
    'mage/translate',
    'mage/backend/notification',
    'Branch8_MarketplaceStaging/js/core/custom-options',
    'mage/adminhtml/form',
    'Branch8_MarketplaceStaging/js/form/element/input',
], function ($, mageTemplate, DOMPurify, VariantProcessHandler, $t) {
    'use strict';
    $.widget('bss.customOptionsUI', $.mage.customOptionsUI, {
        /** @inheritdoc */
        _create: function () {
            var $widget = this;
            this.baseTmpl = mageTemplate('#custom-option-base-template');
            this.rowTmpl = mageTemplate('#custom-option-select-type-row-template');
            this.rowTmplAdd = mageTemplate('#custom-option-select-type-row-template-add');
            this.itemsCount = 0;

            $('body').on('click', '#product-custom-options-content .admin__collapsible-title', function () {
                $(this).parents('.admin__collapsible-block-wrapper').find('.fieldset-wrapper-content').slideToggle(
                    "fast",
                    function () {
                        if ($(this).parents('.admin__collapsible-block-wrapper').hasClass('opened')) {
                            $(this).addClass('wk-bk-hide');
                            $(this).removeClass('_show');
                            $(this)
                                .parents('.admin__collapsible-block-wrapper')
                                .removeClass('opened')
                                .addClass('closed');
                        } else {
                            $(this).removeClass('wk-bk-hide');
                            $(this).addClass('_show');
                            $(this)
                                .parents('.admin__collapsible-block-wrapper')
                                .addClass('opened')
                                .removeClass('closed');
                        }
                    }
                );
            });
            this._initOptionBoxes();
            this._initSortableSelections();
            this._bindCheckboxHandlers();
            this._bindReadOnlyMode();
            this._addValidation();
            if (window.useManageVariant2) {
                VariantProcessHandler.listen();
            }
        },
        _initOptionBoxes: function () {
            const self = this;
            var syncOptionTitle,
                optionValueTitleChangingHandle,
                cacheOptionValueBeforechanging;

            if (!this.options.isReadonly) {
                this.element.sortable({
                    axis: 'y',
                    handle: '[data-role=draggable-handle]',
                    items: '#product_options_container_top > div',
                    update: this._updateOptionBoxPositions,
                    tolerance: 'pointer'
                });
            }

            /**
             * @param {jQuery.Event} event
             */
            syncOptionTitle = function (event) {
                var currentValue = $(event.target).val(),
                    optionBoxTitle = $(
                        '.admin__collapsible-title > span',
                        $(event.target).closest('.fieldset-wrapper')
                    ),
                    newOptionTitle = $.mage.__('New Option');
                optionBoxTitle.text(currentValue === '' ? newOptionTitle : currentValue);
            };

            optionValueTitleChangingHandle = async function (event) {
                if (window.useManageVariant2 === 0) {
                    return
                }
                const oldVal = $(event.target).data('old-value');
                const newVal = $(event.target).val();
                const validate = $.validator.validateSingleElement($(event.target));
                let sku='';
                try {
                    sku = $(event.target).closest('td')
                        .nextAll('td.col-sku').first().children('input').first().val();
                } catch (e) {
                    console.error(e);
                }
                if (!validate || !sku) {
                    return;
                }

                if (oldVal !== newVal) {
                    const warningText = $t(
                        'Combinations have been re-generated. Please click "Manage Variations" check Cost/Stock/SKU/Price for all Variations before saving!'
                    );
                    const warning = $t('Warning:');
                    $('.wk-note.combonation-check-change.wk-warning-box').remove();
                    $('#add_wkvariations_button').after(
                        '<div class="wk-note combonation-check-change wk-warning-box">\n' +
                        '        <strong>' + warning + '</strong> ' + warningText +
                        '</div>'
                    );

                    await VariantProcessHandler.buildVariantFormData();
                }
                //self.enableToolBar()
            };

            cacheOptionValueBeforechanging = function (event) {
                // self.disableToolBar();
                $(event.target).data('old-value', $(event.target).val()); // save old value
            }

            this._on({
                /**
                 * Reset field value to Default
                 */
                'click .use-default-label': function (event) {
                    $(event.target).closest('label').find('input').prop('checked', true).trigger('change');
                },

                /**
                 * Remove custom option or option row for 'select' type of custom option
                 */
                'click button[id^=product_option_][id$=_delete]': function (event) {
                    var jQ = $.noConflict();
                    var element = jQ(event.target).closest('#product_options_container_top > div.fieldset-wrapper,tr'),
                        elementId = DOMPurify.sanitize(element.attr('id')?.toString());

                    if (element.length) {
                        jQ('#product_' + elementId.replace('product_', '') + '_is_delete').val(1);
                        element.addClass('ignore-validate').hide();
                        this.refreshSortableElements();
                    }
                },

                /**
                 * Minimize custom option block
                 */
                'click #product_options_container_top [data-target$=-content]': function () {
                    if (this.options.isReadonly) {
                        return false;
                    }
                },

                /**
                 * Add new custom option
                 */
                'click #add_new_defined_option': function (event) {
                    this.addOption(event);
                },

                /**
                 * Add new option row for 'select' type of custom option
                 */
                'click button[id^=product_option_][id$=_add_select_row]': function (event) {
                    this.addSelection(event);
                },

                /**
                 * Change custom option type
                 */
                'change select[id^=product_option_][id$=_type]': function (event, data) {
                    var jQ = $.noConflict();
                    var widget = this,
                        currentElement = jQ(event.target),
                        parentAttrId,
                        parentId,
                        parentIdVal,
                        group,
                        previousGroup,
                        previousBlock,
                        tmpl, disabledBlock, priceType;

                    currentElement.each(function () {
                        parentAttrId = DOMPurify.sanitize(currentElement.closest('.fieldset-alt').attr('id')?.toString());
                        parentId = '#' + parentAttrId;
                        parentIdVal = DOMPurify.sanitize(jQ(parentId + '_id').val()?.toString());
                        group = currentElement.find('[value="' + DOMPurify.sanitize(currentElement.val()?.toString()) + '"]').closest('optgroup').attr('data-optgroup-name');
                        previousGroup = DOMPurify.sanitize(jQ(parentId + '_previous_group').val());
                        previousBlock = jQ(parentId + '_type_' + previousGroup);
                        group = DOMPurify.sanitize(group);
                    })


                    data = data || {};

                    if (typeof group !== 'undefined') {
                        group = group.toLowerCase();
                    }

                    if (previousGroup !== group) {
                        if (previousBlock.length) {
                            previousBlock.remove();
                        }
                        jQ(parentId + '_previous_group').val(group);

                        if (typeof group === 'undefined') {
                            return;
                        }
                        disabledBlock = jQ(parentId).find(parentId + '_type_' + group);

                        if (disabledBlock.length) {
                            disabledBlock.removeClass('ignore-validate').show();
                        } else {
                            if ($.isEmptyObject(data)) { //eslint-disable-line max-depth
                                data['option_id'] = parentIdVal;
                                data.price = data.sku = '';
                                data.include_price_in_from_x = 1;
                            }
                            data.group = group;

                            // tmpl = jQ('#custom-option-' + group + '-type-template').html();
                            tmpl = mageTemplate('#custom-option-' + group + '-type-template', {
                                data: data
                            });
                            jQ(parentId).after(DOMPurify.sanitize(tmpl));

                            if (data['price_type']) { //eslint-disable-line max-depth
                                priceType = $('#' + widget.options.fieldId + '_' + data['option_id'] + '_price_type');
                                priceType.val(data['price_type']).attr('data-store-label', data['price_type']);
                            }
                            this._bindUseDefault(widget.options.fieldId + '_' + data['option_id'], data);
                            //Add selections

                            if (data.optionValues) { //eslint-disable-line max-depth
                                for (var key in data.optionValues) {
                                    const input = data.optionValues[key];
                                    input.focus = data.focus || true;
                                    widget.addSelection(input);
                                }
                            }
                        }
                    }
                },
                //Sync title
                'change .field-option-title > .control > input[id$="_title"]': syncOptionTitle,
                'keyup .field-option-title > .control > input[id$="_title"]': syncOptionTitle,
                'paste .field-option-title > .control > input[id$="_title"]': syncOptionTitle,

                'blur .determined-location > .field-option-title > input[id$="_title"]': optionValueTitleChangingHandle,
                'focus .determined-location > .field-option-title > input[id$="_title"]': cacheOptionValueBeforechanging
            });
        },
        /**
         *
         */
        disableToolBar: function () {
            $('#save-btn,#save-draft-duplicate-btn,#save-draft-btn')
                .attr('disabled','true')
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
         * Add selection value for 'select' type of custom option
         */
        addSelection: function (event,data) {
            var jQ = $.noConflict();
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                rowTmpl, priceType;
            if (typeof element !== 'undefined') {
                jQ(element).each(function () {
                    data.id = DOMPurify.sanitize(jQ(element).closest('#product_options_container_top > div').find('[name^="product[options]"][name$="[record_id]"]').val()?.toString());
                });
                var optionType = jQ(element).closest('#product_options_container_top > div').find('.select-product-option-type').val();
                var listDrop = ['drop_down', 'radio'];
                optionType = DOMPurify.sanitize(optionType?.toString());
                data.enableSwatch = 0;
                if ($.inArray(optionType, listDrop) > -1) {
                    data.enableSwatch = 1;
                }

                if (!this.options.selectionItemCount[data.id]) {
                    this.options.selectionItemCount[data.id] = 1;
                }

                data['select_id'] = this.options.selectionItemCount[data.id];
                data.price = data.sku = '';
                rowTmpl = this.rowTmplAdd({
                    data: data
                });
            } else {
                data = event;
                data.id = data['option_id'];
                data['select_id'] = data['option_type_id'];
                data.enableSwatch = 0;
                this.options.selectionItemCount[data.id] = data['item_count'];
                if (data.add_new) {
                    rowTmpl = this.rowTmplAdd({
                        data: data
                    });
                } else {
                    rowTmpl = this.rowTmpl({
                        data: data
                    });
                }
            }

            const sanitizedRowTmpl = DOMPurify.sanitize(rowTmpl, {ALLOWED_TAGS: ['tr', 'td', 'div', 'input', 'select', 'button', 'span', 'option']});
            jQ('#select_option_type_row_' + data.id).append(sanitizedRowTmpl);

            //set selected price_type value if set
            if (data['price_type']) {
                priceType = jQ('#' + this.options.fieldId + '_' + data.id + '_select_' + data['select_id'] +
                    '_price_type');
                priceType.val(data['price_type']).attr('data-store-label', data['price_type']);
            }

            this._bindUseDefault(this.options.fieldId + '_' + data.id + '_select_' + data['select_id'], data);
            this.refreshSortableElements();
            this.options.selectionItemCount[data.id] = parseInt(this.options.selectionItemCount[data.id], 10) + 1;
            if (focus) {
                jQ('#' + this.options.fieldId + '_' + data.id + '_select_' + data['select_id'] + '_title').focus();
            }
        },
        /**
         *
         * @param options
         */
        addOptions: function (config, focus = true) {
            const self = this;
            const options = config.options;
            if (!options.length) {
                return;
            }
            _.each(options, async function (option) {
                await self.addOption(option, focus);
            })
            const data = {
                'options': options,
            };
            $('body').trigger('customOptionInitComplete', data);
        },
        /**
         * Add custom option
         */
        addOption: async function (event, focus = false) {
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                baseTmpl;
            var checkNewOption = 0;
            window.initTime = false;
            let isInitOption = false;
            if (typeof element !== 'undefined') {
                data.id = this.options.itemCount;
                data.type = '';
                data.display = 'none';
                data['option_id'] = 0;
                checkNewOption = 1;

            } else {
                data = event;
                this.options.itemCount = data['item_count'];
                data.display = 'none';
                window.initTime = true;
                isInitOption = true;
            }
            data.focus = focus;
            if (!data.hasOwnProperty('show_delete_button')) {
                data.show_delete_button = 1;
            }

            baseTmpl = this.baseTmpl({
                data: data
            });

            // now page product_options_container_top div count
            let existingDivCount = (document.querySelectorAll('#product_options_container_top > div').length || 0) - 1;

            $(baseTmpl).appendTo(this.element.find('#product_options_container_top')).find('.collapse').collapsable();

            //set selected type value if set
            if (data.type) {
                $('#' + this.options.fieldId + '_' + data.id + '_type').val(data.type).trigger('change', data);
                if (!checkNewOption && data.record_id) {
                    $('#' + this.options.fieldId + '_' + data.id + '_option_id').val(0);
                }
            }

            //set selected is_require value if set
            if (data['is_require']) {
                $('#' + this.options.fieldId + '_' + data.id + '_is_require').val(data['is_require']).trigger('change');
            }

            this.refreshSortableElements();
            this._bindCheckboxHandlers();
            this._bindReadOnlyMode();
            this.options.itemCount++;
            $('#' + this.options.fieldId + '_' + data.id + '_title').trigger('change');
            window.initTime = false;

            // disable input checkbox && select type set default value
            let productOptionsContainerTop = $('#product_options_container_top > div');
            let newDivIndexes = [];
            productOptionsContainerTop.each(function (i, e) {
                if ($(this).css('display') !== 'none' && i > existingDivCount) newDivIndexes.push(i);
            });

            newDivIndexes.forEach(function (key) {
                let containerTop = productOptionsContainerTop[key];
                if (!containerTop) return;
                let containerTopId = productOptionsContainerTop[key].id;
                productOptionsContainerTop.each(function (i, e) {
                    if (key === i) {
                        $(this).find(`input#product_${containerTopId}_required`).prop('disabled', true);
                        $(this).find(`select#product_${containerTopId}_type`).val('drop_down').trigger('change');
                    }
                });
            });
        },

        /**
         * Update Custom option position
         */
        _updateOptionBoxPositions: function () {
            if (window.initTime) return;
            $(this).find('div[id^=option_]:not(.ignore-validate) .fieldset-alt > [name$="[sort_order]"]').each(
                function (index) {
                    $(this).val(index);
                });
            
            var widget = $(this).data('bssCustomOptionsUI') || $(this).data('stageCustomOptionsUI') || this;
            if (widget && typeof widget.updateVariations === 'function') {
                widget.updateVariations();
            }
        },

        /**
         * Update selections positions for 'select' type of custom option
         */
        _updateSelectionsPositions: function () {
            if (window.initTime) return;
            $(this).find('tr:not(.ignore-validate) [name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
            
            var widget = $(this).data('bssCustomOptionsUI') || $(this).data('stageCustomOptionsUI') || this;
            if (widget && typeof widget.updateVariations === 'function') {
                widget.updateVariations();
            }
        },
        updateVariations: function () {
            if (window.useManageVariant2) {
                const warningText = $t(
                    'Combinations have been re-generated. Please click "Manage Variations" check Cost/Stock/SKU/Price for all Variations before saving!'
                );
                const warning = $t('Warning:');
                $('.wk-note.combonation-check-change.wk-warning-box').remove();
                $('#add_wkvariations_button').after(
                    '<div class="wk-note combonation-check-change wk-warning-box">\n' +
                    '        <strong>' + warning + '</strong> ' + warningText +
                    '</div>'
                );
                VariantProcessHandler.buildVariantFormData();
            }
        },
    });
    return $.bss.customOptionsUI;
});

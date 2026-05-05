define([
    'jquery',
    'mage/template',
    'plugins/DOMPurify',
    'jquery/ui',
    'jquery/file-uploader',
    'mage/translate',
    'mage/backend/notification',
    'Branch8_MarketplaceStaging/js/core/custom-options',
    'mage/adminhtml/form',
    'Branch8_MarketplaceStaging/js/form/element/input'
], function ($, mageTemplate, DOMPurify) {
    'use strict';
    $.widget('stage.customOptionsUI', $.mage.customOptionsUI, {
        /** @inheritdoc */
        _create: function () {
            var $widget = this;
            this.baseTmpl = mageTemplate('#staging-custom-option-base-template');
            this.rowTmpl = mageTemplate('#custom-option-select-type-row-template');
            this.rowTmplAdd = mageTemplate('#custom-option-select-type-row-template-add');
            this.itemsCount = 0;

            $('body').on('click', '#staging-product-custom-options-content .admin__collapsible-title', function () {
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
        },
        _initOptionBoxes: function () {
            var syncOptionTitle;

            if (!this.options.isReadonly) {
                this.element.sortable({
                    axis: 'y',
                    handle: '[data-role=draggable-handle]',
                    items: '#staging_product_options_container_top > div',
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
                    var element = jQ(event.target).closest('#staging_product_options_container_top > div.fieldset-wrapper,tr');

                    if (element.length) {
                        jQ('#staging_product_' + DOMPurify.sanitize(element.attr('id')).replace('product_', '') + '_is_delete').val(1);
                        element.addClass('ignore-validate').hide();
                        this.refreshSortableElements();
                    }
                },

                /**
                 * Remove custom option or option row for 'select' type of custom option
                 */
                'click button[id^=staging_product_option_][id$=_delete]': function (event) {
                    var jQ = $.noConflict();
                    var element = jQ(event.target).closest('#staging_product_options_container_top > div.fieldset-wrapper,tr'),
                        elementId = DOMPurify.sanitize(element.attr('id')?.toString());

                    if (element.length) {
                        jQ('#staging_product_' + elementId.replace('staging_', '') + '_is_delete').val(1);
                        element.addClass('ignore-validate').hide();
                        this.refreshSortableElements();
                    }
                },

                /**
                 * Minimize custom option block
                 */
                'click #staging_product_options_container_top [data-target$=-content]': function () {
                    if (this.options.isReadonly) {
                        return false;
                    }
                },

                /**
                 * Add new custom option
                 */
                'click #staging_add_new_defined_option': function (event) {
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
                'change select[id^=staging_product_option_][id$=_type]': function (event, data) {
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

                    currentElement.each(function(){
                        parentAttrId = DOMPurify.sanitize(currentElement.closest('.fieldset-alt').attr('id')?.toString());
                        parentId = '#' + parentAttrId;
                        parentIdVal = DOMPurify.sanitize(jQ(parentId + '_id').val());
                        group = currentElement.find('[value="' + DOMPurify.sanitize(currentElement.val()?.toString()) + '"]').closest('optgroup').attr('data-optgroup-name');
                        previousGroup = DOMPurify.sanitize(jQ(parentId + '_previous_group').val()?.toString());
                        previousBlock = jQ('#staging_product_options_container_top ' + parentId.replace('staging_','') + '_type_' + previousGroup);
                        group = DOMPurify.sanitize(group);
                    })

                    group = DOMPurify.sanitize(group);
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
                            }
                            data.group = group;

                            // tmpl = jQ('#custom-option-' + group + '-type-template').html();
                            tmpl = mageTemplate('#custom-option-' + group + '-type-template', {
                                data: data
                            });

                            jQ(parentId).after(DOMPurify.sanitize(tmpl));

                            if (data['price_type']) { //eslint-disable-line max-depth
                                priceType = jQ('#staging_product_options_container_top #' + widget.options.fieldId + '_' + data['option_id'] + '_price_type');
                                priceType.val(data['price_type']).attr('data-store-label', data['price_type']);
                            }
                            this._bindUseDefault(widget.options.fieldId + '_' + data['option_id'], data);
                            //Add selections

                            if (data.optionValues) { //eslint-disable-line max-depth
                                data.optionValues.each(function (value) {
                                    widget.addSelection(value);
                                });
                            }
                        }
                    }
                },
                //Sync title
                'change .field-option-title > .control > input[id$="_title"]': syncOptionTitle,
                'keyup .field-option-title > .control > input[id$="_title"]': syncOptionTitle,
                'paste .field-option-title > .control > input[id$="_title"]': syncOptionTitle
            });
        },
        /**
         * Add selection value for 'select' type of custom option
         */
        addSelection: function (event) {
            var jQ = $.noConflict();
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                rowTmpl, priceType;

            if (typeof element !== 'undefined') {
                jQ(element).each(function(){
                    data.id = DOMPurify.sanitize(jQ(element).closest('#staging_product_options_container_top > div').find('[name^="product[options]"][name$="[record_id]"]').val()?.toString());
                });
                var optionType = jQ(element).closest('#staging_product_options_container_top > div').find('.select-product-option-type').val();
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

                rowTmpl = this.rowTmpl({
                    data: data
                });
            }

            const sanitizedRowTmpl = DOMPurify.sanitize(rowTmpl, {ALLOWED_TAGS: ['tr', 'td', 'div', 'input', 'select', 'button', 'span', 'option']});
            jQ('#staging-' + data.id + '-content #select_option_type_row_' + data.id).append(sanitizedRowTmpl);

            //set selected price_type value if set
            if (data['price_type']) {
                priceType = jQ('#staging-' + data.id + '-content #' + this.options.fieldId + '_' + data.id + '_select_' + data['select_id'] +
                    '_price_type');
                priceType.val(data['price_type']).attr('data-store-label', data['price_type']);
            }

            this._bindUseDefault(this.options.fieldId + '_' + data.id + '_select_' + data['select_id'], data);
            this.refreshSortableElements();
            this.options.selectionItemCount[data.id] = parseInt(this.options.selectionItemCount[data.id], 10) + 1;

            jQ('#staging-' + data.id + '-content #' + this.options.fieldId + '_' + data.id + '_select_' + data['select_id'] + '_title').focus();
        },

        /**
         * Add custom option
         */
        addOption: function (event) {
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                baseTmpl;
            var checkNewOption = 0;
            var jQ = $.noConflict();
            window.stageInitTime = false;

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
                window.stageInitTime = true;
            }

            if(!data.hasOwnProperty('show_delete_button')){
                data.show_delete_button = 1;
            }

            baseTmpl = this.baseTmpl({
                data: data
            });

            baseTmpl = DOMPurify.sanitize(baseTmpl);

            jQ(baseTmpl)
                .appendTo(this.element.find('#staging_product_options_container_top'))
                .find('.collapse').collapsable();

            //set selected type value if set
            if (data.type) {
                jQ('#' + this.options.fieldId + '_' + data.id + '_type').val(data.type).trigger('change', data);
            }

            //set selected is_require value if set
            if (data['is_require']) {
                jQ('#' + this.options.fieldId + '_' + data.id + '_is_require').val(data['is_require']).trigger('change');
            }

            this.refreshSortableElements();
            this._bindCheckboxHandlers();
            this._bindReadOnlyMode();
            this.options.itemCount++;
            jQ('#' + this.options.fieldId + '_' + data.id + '_title').trigger('change');
            window.stageInitTime = false;
        },

        /**
         * Update Custom option position
         */
        _updateOptionBoxPositions: function () {
            if (window.stageInitTime) return;
            $(this).find('div[id^=option_]:not(.ignore-validate) .fieldset-alt > [name$="[sort_order]"]').each(
                function (index) {
                    $(this).val(index);
                });
        },

        /**
         * Update selections positions for 'select' type of custom option
         */
        _updateSelectionsPositions: function () {
            if (window.stageInitTime) return;
            $(this).find('tr:not(.ignore-validate) [name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
        },
    });
    return $.stage.customOptionsUI;
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'plugins/DOMPurify',
    'jquery/ui',
    'useDefault',
    'collapsable',
    'mage/translate',
    'mage/backend/validation',
    'Magento_Ui/js/modal/modal'
], function ($, mageTemplate, alert, DOMPurify) {
    'use strict';

    $.widget('mage.customOptionsUI', {
        options: {
            selectionItemCount: {}
        },

        /** @inheritdoc */
        _create: function () {
            this.baseTmpl = mageTemplate('#custom-option-base-template');
            this.rowTmpl = mageTemplate('#custom-option-select-type-row-template');

            this._initOptionBoxes();
            this._initSortableSelections();
            this._bindCheckboxHandlers();
            this._bindReadOnlyMode();
            this._addValidation();
        },

        /**
         * @private
         */
        _addValidation: function () {
            $.validator.addMethod(
                'required-option-select', function (value) {
                    return value !== '';
                }, $.mage.__('Select type of option.'));

            $.validator.addMethod(
                'required-option-select-type-rows', function (value, element) {
                    var optionContainerElm = element.up('div[id*=_type_]'),
                        selectTypesFlag = false,
                        selectTypeElements = $('#' + optionContainerElm.id + ' .select-type-title');

                    selectTypeElements.each(function () {
                        if (!$(this).closest('tr').hasClass('ignore-validate')) {
                            selectTypesFlag = true;
                        }
                    });

                    return selectTypesFlag;
                }, $.mage.__('Please add rows to option.'));
            $.validator.addMethod(
                'preservation_required', function (value) {
                    if(!$('#preservation_status option').length){
                        return false;
                    }
                    return true;
                }, $.mage.__("You haven't any options, please contact Admin to update the Preservation Status options."));
            $.validator.addMethod("sku-unique", function(value, element) {
                var parentForm = $(element).closest('form');
                var timeRepeated = 0;
                if (value != '') {
                    $(parentForm.find('.sku-unique')).each(function () {
                        if ($(this).val() === value) {
                            timeRepeated++;
                        }
                    });
                }
                return timeRepeated === 1 || timeRepeated === 0;

            }, $.mage.__('Duplicate SKU found.'))
        },

        /**
         * @private
         */
        _initOptionBoxes: function () {
            var syncOptionTitle;

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
                 * Import custom options from products
                 */
                'click #import_new_defined_option': function () {
                    var importContainer = $('#import-container'),
                        widget = this;

                    importContainer.modal({
                        title: $.mage.__('Select Product'),
                        type: 'slide',

                        /** @inheritdoc */
                        opened: function () {
                            $(document).off().on('click', '#productGrid_massaction-form button', function () {
                                $('.import-custom-options-apply-button').trigger('click', 'massActionTrigger');
                            });
                        },
                        buttons: [{
                            text: $.mage.__('Import'),
                            attr: {
                                id: 'import-custom-options-apply-button'
                            },
                            'class': 'action-primary action-import import-custom-options-apply-button',

                            /** @inheritdoc */
                            click: function (event, massActionTrigger) {
                                var request = [];

                                $(this.element).find('input[name=product]:checked').map(function () {
                                    request.push(this.value);
                                });

                                if (request.length === 0) {
                                    if (!massActionTrigger) {
                                        alert({
                                            content: $.mage.__('An item needs to be selected. Select and try again.')
                                        });
                                    }

                                    return;
                                }

                                $.post(widget.options.customOptionsUrl, {
                                    'products[]': request,
                                    'form_key': widget.options.formKey
                                }, function ($data) {
                                    $.each(JSON.parse($data), function (el) {
                                        var i;

                                        el.id = widget.getFreeOptionId(el.id);
                                        el['option_id'] = el.id;

                                        if (typeof el.optionValues !== 'undefined') {
                                            for (i = 0; i < el.optionValues.length; i++) {
                                                el.optionValues[i]['option_id'] = el.id;
                                            }
                                        }
                                        //Adding option
                                        widget.addOption(el);
                                        //Will save new option on server side
                                        $('#product_option_' + el.id + '_option_id').val(0);
                                        $('#option_' + el.id + ' input[name$="option_type_id]"]').val(-1);
                                    });
                                    importContainer.modal('closeModal');
                                });
                            }
                        }]
                    });
                    importContainer.load(
                        this.options.productGridUrl,
                        {
                            'form_key': this.options.formKey,
                            'current_product_id': this.options.currentProductId
                        },
                        function () {
                            importContainer.modal('openModal');
                        }
                    );
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

                    currentElement.each(function(){
                        parentAttrId = DOMPurify.sanitize(currentElement.closest('.fieldset-alt').attr('id')?.toString());
                        parentId = '#' + parentAttrId;
                        parentIdVal = DOMPurify.sanitize(jQ(parentId + '_id').val()?.toString());
                        group = currentElement.find('[value="' + DOMPurify.sanitize(currentElement.val()?.toString()) + '"]').closest('optgroup').attr('data-optgroup-name');
                        previousGroup = DOMPurify.sanitize(jQ(parentId + '_previous_group').val()?.toString());
                        previousBlock = jQ(parentId + '_type_' + previousGroup);
                    });

                    group = DOMPurify.sanitize(group);
                    data = data || {};

                    if (typeof group !== 'undefined') {
                        group = group.toLowerCase();
                    }

                    if (previousGroup !== group) {
                        if (previousBlock.length) {
                            previousBlock.addClass('ignore-validate').hide();
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
                                priceType = jQ('#' + widget.options.fieldId + '_' + data['option_id'] + '_price_type');
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
                'paste .field-option-title > .control > input[id$="_title"]': syncOptionTitle,
            });
        },

        /**
         * @private
         */
        _initSortableSelections: function () {
            if (!this.options.isReadonly) {
                this.element.find('[id^=product_option_][id$=_type_select] tbody').sortable({
                    axis: 'y',
                    handle: '[data-role=draggable-handle]',

                    /** @inheritdoc */
                    helper: function (event, ui) {
                        ui.children().each(function () {
                            $(this).width($(this).width());
                        });

                        return ui;
                    },
                    update: this._updateSelectionsPositions,
                    tolerance: 'pointer'
                });
            }
        },

        /**
         * Sync sort order checkbox with hidden dropdown
         */
        _bindCheckboxHandlers: function () {
            this._on({
                /**
                 * @param {jQuery.Event} event
                 */
                'change [id^=product_option_][id$=_required]': function (event) {
                    var $this = $(event.target);

                    $this.closest('#product_options_container_top > div')
                        .find('[name$="[is_require]"]').val($this.is(':checked') ? 1 : 0);
                }
            });
            this.element.find('[id^=product_option_][id$=_required]').each(function () {
                $(this).prop('checked', $(this).closest('#product_options_container_top > div')
                        .find('[name$="[is_require]"]').val() > 0);
            });
        },

        /**
         * Update Custom option position
         */
        _updateOptionBoxPositions: function () {
            $(this).find('div[id^=option_]:not(.ignore-validate) .fieldset-alt > [name$="[sort_order]"]').each(
                function (index) {
                    $(this).val(index);
                });
        },

        /**
         * Update selections positions for 'select' type of custom option
         */
        _updateSelectionsPositions: function () {
            $(this).find('tr:not(.ignore-validate) [name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
        },

        /**
         * Disable input data if "Read Only"
         */
        _bindReadOnlyMode: function () {
            if (this.options.isReadonly) {
                $('div.product-custom-options').find('button,input,select,textarea').each(function () {
                    $(this).prop('disabled', true);

                    if ($(this).is('button')) {
                        $(this).addClass('disabled');
                    }
                });
            }
        },

        /**
         * @param {String} id
         * @param {Object} data
         * @private
         */
        _bindUseDefault: function (id, data) {
            var title = $('#' + id + '_title'),
                price = $('#' + id + '_price'),
                priceType = $('#' + id + '_price_type');

            //enable 'use default' link for title
            if (data.checkboxScopeTitle) {
                title.useDefault({
                    field: '.field',
                    useDefault: 'label[for$=_title]',
                    checkbox: 'input[id$=_title_use_default]',
                    label: 'span'
                });
            }
            //enable 'use default' link for price and price_type
            if (data.checkboxScopePrice) {
                price.useDefault({
                    field: '.field',
                    useDefault: 'label[for$=_price]',
                    checkbox: 'input[id$=_price_use_default]',
                    label: 'span'
                });
                //not work set default value for second field
                priceType.useDefault({
                    field: '.field',
                    useDefault: 'label[for$=_price]',
                    checkbox: 'input[id$=_price_use_default]',
                    label: 'span'
                });
            }
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
                    data.id = DOMPurify.sanitize(jQ(element).closest('#product_options_container_top > div').find('[name^="product[options]"][name$="[record_id]"]').val()?.toString());
                });

                data['option_type_id'] = -1;

                if (!this.options.selectionItemCount[data.id]) {
                    this.options.selectionItemCount[data.id] = 1;
                }

                data['select_id'] = this.options.selectionItemCount[data.id];
                data.price = data.sku = '';
            } else {
                data = event;
                data.id = data['option_id'];
                data['select_id'] = data['option_type_id'];
                this.options.selectionItemCount[data.id] = data['item_count'];
            }

            rowTmpl = this.rowTmpl({
                data: data
            });

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

            jQ('#' + this.options.fieldId + '_' + data.id + '_select_' + data['select_id'] + '_title').trigger('focus');
        },

        /**
         * Add custom option
         */
        addOption: function (event) {
            var data = {},
                element = event.target || event.srcElement || event.currentTarget,
                baseTmpl;

            if (typeof element !== 'undefined') {
                data.id = this.options.itemCount;
                data.type = '';
                data['option_id'] = 0;
            } else {
                data = event;
                this.options.itemCount = data['item_count'];
            }

            baseTmpl = this.baseTmpl({
                data: data
            });

            $(baseTmpl)
                .appendTo(this.element.find('#product_options_container_top'))
                .find('.collapse').collapsable();

            //set selected type value if set
            if (data.type) {
                $('#' + this.options.fieldId + '_' + data.id + '_type').val(data.type).trigger('change', data);
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
        },

        /**
         * @return {Object}
         */
        refreshSortableElements: function () {
            if (!this.options.isReadonly) {
                this.element.sortable('refresh');
                this._updateOptionBoxPositions.apply(this.element);
                this._updateSelectionsPositions.apply(this.element);
                this._initSortableSelections();
            }

            return this;
        },

        /**
         * @param {String} id
         * @return {*}
         */
        getFreeOptionId: function (id) {
            return $('#' + this.options.fieldId + '_' + id).length ? this.getFreeOptionId(parseInt(id, 10) + 1) : id;
        }
    });

});

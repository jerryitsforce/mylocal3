define([
    'jquery',
    'mage/template',
    'jquery/ui',
    'prototype',
    'Magento_VisualMerchandiser/js/tabs',
    'Magento_VisualMerchandiser/js/add_products'
], function ($, mageTemplate) {
    'use strict';

    var mixin = {
        currentSelectRow: null,
        _create: function () {
            this._super();
           $('#catalog_category_smart_category_onoff').trigger('change');
        },
        /**
         *
         * @param {Column} elem
         */
        setupSmartCategory: function () {
            var widget = this, attributeRules,
                switchSmartCategory,
                switchUseAdvanceRuleSwitcher,
                divSmartCategory,
                divAdvanceSmartCategory,
                rulesSmartCategory,
                divRegularCategory,
                switchUseAdvanceRuleSwitcherWrappper,
                productPositionInputs;
            $('#catalog_category_sort_products_tabs').on('click', function () {
                this.savePositionCache(function () {
                    this.reloadViews();
                }.bind(this));
            }.bind(this));
            attributeRules = {
                table: $('#attribute-rules-table'),
                itemCount: 0,
                currentRow: 0,
                /**
                 * @param {Object} data
                 */
                add: function (data) {
                    this.template = mageTemplate('#row-template');
                    if (typeof data.id == 'undefined') {
                        data = {
                            'id': 'rule_' + this.itemCount
                        };
                    }
                    Element.insert($$('[data-role=rules-container]')[0], this.template({
                        data: data
                    }));
                    this.disableLastLogicSelect();
                    this.showHideRulesTable();
                    $('#smart_category_table tbody tr:last').find('.smart_category_rule').each(function () {
                        $(this).on('change', function () {
                            window.attributeRules.getRules();
                        });
                    });
                    this.itemCount++;
                },

                /**
                 * @param {Event} event
                 */
                remove: function (event) {
                    var element = $(Event.findElement(event, 'tr'));

                    element.remove();
                    this.disableLastLogicSelect();
                    this.getRules();
                    this.showHideRulesTable();
                },

                /**
                 * Hide irrelevant operators for selected attribute
                 *
                 * @param {Event} event
                 * @param {Object} data
                 */
                hideIrrelevantOperators: function (event, data) {
                    var element;

                    if (event === null && data === null) {
                        return;
                    }

                    if (event !== null) {
                        element = $(Event.findElement(event, 'tr'));
                    }

                    if (data !== null) {
                        element = data;
                    }

                    element.find('select[name=operator_select] option').show();

                    if (
                        element.find('select[name=attribute_select] option:selected').val() === 'category_id' ||
                        element.find('select[name=attribute_select] option:selected').val() === 'category_ids'
                    ) {
                        element.find('select[name=operator_select] option').hide();
                        element.find('select[name=operator_select] option[value=eq]').show();
                        element.find('select[name=operator_select] option[value=neq]').show();
                    }

                    if (
                        element.find('select[name=attribute_select] option:selected').val() === 'created_at' ||
                        element.find('select[name=attribute_select] option:selected').val() === 'updated_at'
                    ) {
                        element.find('select[name=operator_select] option[value=neq]').hide();
                        element.find('select[name=operator_select] option[value=like]').hide();
                    }
                },

                disableLastLogicSelect: function () {
                    if (this.element.find('#smart_category_table > tbody > tr').length === 1) {
                        this.element
                            .find('#smart_category_table tbody tr:first')
                            .find('.smart_category_logic_select')
                            .hide();
                    } else {
                        this.element
                            .find('#smart_category_table tbody tr:first')
                            .find('.smart_category_logic_select')
                            .show();
                    }
                    this.element.find('#smart_category_table tbody > tr').each(function (index, element) {
                        $(element).find('[name=\'logic_select\']').show();
                    });
                    this.element.find('#smart_category_table tbody tr:last').find('[name=\'logic_select\']').hide();

                    if (this.element.find('#smart_category_table > tbody > tr').length === 1) {
                        this.element.find('.logic_header').addClass('hidden');
                    } else {
                        this.element.find('.logic_header').removeClass('hidden');
                    }

                }.bind(this),

                showHideRulesTable: function () {
                    if (this.element.find('#smart_category_table > tbody > tr').length === 0) {
                        this.element.find('#smart_category_table_wrapper').addClass('hidden');
                        this.element.find('#mode_select').addClass('hidden');
                    } else {
                        this.element.find('#smart_category_table_wrapper').removeClass('hidden');
                        this.element.find('#mode_select').removeClass('hidden');
                    }
                }.bind(this),

                /**
                 * @returns void
                 */
                getRules: function () {
                    var rows = [],
                        row;

                    $('#smart_category_table tbody > tr').each(function (index, element) {
                        if ($(element).find('[name=\'attribute_select\']').val() !== '') {
                            row = {
                                'attribute': $(element).find('[name=\'attribute_select\']').val(),
                                'operator': $(element).find('[name=\'operator_select\']').val(),
                                'value': $(element).find('[name=\'rule_value\']').val(),
                                'logic': $(element).find('[name=\'logic_select\']').val()
                            };
                            rows.push(row);
                        }
                    });
                    $('#smart_category_rules').val(Object.toJSON(rows));
                },

                /**
                 * @returns void
                 */
                setRules: function () {
                    var rulesArray,
                        i;

                    if ($('#smart_category_rules').val() !== '') {
                        rulesArray = JSON.parse($('#smart_category_rules').val());

                        for (i = 0; i < rulesArray.length; i++) {
                            this.add(rulesArray[i]);
                            $('#smart_category_table tbody tr:last')
                                .find('[name=\'attribute_select\']')
                                .val(rulesArray[i].attribute);
                            $('#smart_category_table tbody tr:last')
                                .find('[name=\'operator_select\']')
                                .val(rulesArray[i].operator);
                            $('#smart_category_table tbody tr:last')
                                .find('[name=\'rule_value\']')
                                .val(rulesArray[i].value);
                            $('#smart_category_table tbody tr:last')
                                .find('[name=\'logic_select\']')
                                .val(rulesArray[i].logic);
                            attributeRules.hideIrrelevantOperators(null, $('#smart_category_table tbody tr:last'));
                        }
                    }
                },
                /**
                 *
                 */
                getCurrentRow: function () {
                    const checkedRadio = $('#smart_category_table').find('input[type="radio"]:checked');
                    if (checkedRadio.length !== 0) {
                        console.log(checkedRadio.val());
                    }
                },
            };
            attributeRules.setRules();
            switchSmartCategory = this.element.find('#catalog_category_smart_category_onoff');
            switchUseAdvanceRuleSwitcherWrappper = this.element.find('#use_advance_rule_wrapper');
            switchUseAdvanceRuleSwitcher = this.element.find('#use_advance_rule');
            divSmartCategory = this.element.find('#manage-rules-panel');
            divAdvanceSmartCategory = this.element.find('#advance-smart-category-rules');
            rulesSmartCategory = this.element.find('#smart_category_rules');
            divRegularCategory = this.element.find('#regular-category-settings');
            productPositionInputs = $('.position input');

            if (switchSmartCategory.is(':checked')) {
                divSmartCategory.removeClass('hidden');
                divRegularCategory.addClass('hidden');
                attributeRules.showHideRulesTable();
                productPositionInputs.prop('disabled', true);
            }
            switchSmartCategory.change(function () {
                if (switchSmartCategory.is(':checked')) {
                    rulesSmartCategory.prop('disabled', false);
                    //divSmartCategory.removeClass('hidden');
                    //divAdvanceSmartCategory.removeClass('hidden');
                     $('#use_advance_rule').trigger('change');
                    switchUseAdvanceRuleSwitcherWrappper.removeClass('hidden');
                    divRegularCategory.addClass('hidden');
                    productPositionInputs.prop('disabled', true);
                } else {
                    divSmartCategory.addClass('hidden');
                    divAdvanceSmartCategory.addClass('hidden');
                    switchUseAdvanceRuleSwitcherWrappper.addClass('hidden');
                    divRegularCategory.removeClass('hidden');
                    productPositionInputs.prop('disabled', false);
                }
                attributeRules.showHideRulesTable();

            });

            switchUseAdvanceRuleSwitcher.change(function () {
                if (switchUseAdvanceRuleSwitcher.is(':checked')) {
                    divSmartCategory.addClass('hidden');
                    divAdvanceSmartCategory.removeClass('hidden');
                } else {
                    divSmartCategory.removeClass('hidden');
                    divAdvanceSmartCategory.addClass('hidden');
                }
            });

            $('#smart_category_table tbody').find('.smart_category_rule').each(function () {
                jQuery(this).on('change', function () {
                    window.attributeRules.getRules();
                });
            });

            if ($('#add_new_rule_button')) {
                Event.observe('add_new_rule_button', 'click', attributeRules.add.bind(attributeRules));
            }

            $('#manage-rules-panel').on('click', '.delete-rule', function (event) {
                attributeRules.remove(event);
            });

            $('#manage-rules-panel').on('change', 'select[name=attribute_select]', function (event) {
                attributeRules.hideIrrelevantOperators(event, null);
            });

            window.attributeRules = attributeRules;
        },



    };

    return function (targetWidget) {
        $.widget('mage.visualMerchandiser', targetWidget, mixin);
        return $.mage.visualMerchandiser;
    };
});

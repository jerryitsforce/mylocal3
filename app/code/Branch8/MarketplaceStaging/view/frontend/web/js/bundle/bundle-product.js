/**
 * Webkul Software
 *
 * @category    Webkul
 * @package     Webkul_MpBundleProduct
 * @author      Webkul Software Private Limited
 * @copyright   Webkul Software Private Limited (https://webkul.com)
 * @license     https://store.webkul.com/license.html
 */

/*jshint jquery:true*/
define([
    "jquery",
    "Branch8_MarketplaceStaging/js/product/weight-handler",
    "Magento_Ui/js/modal/modal",
    "mage/template",
    "ko",
    "plugins/DOMPurify",
    "jquery/ui",
    "mage/translate",
    "Webkul_MpBundleProduct/js/theme/sortable",
    "prototype",
    'Magento_Ui/js/modal/alert'
], function ($, weightHandler, modal, mageTemplate, ko, DOMPurify) {
    'use strict';
    var jQ = $.noConflict();
    var bundleTemplateBox = DOMPurify.sanitize($('#staging-bundle-option-selection-box-template').html());
    var bundleTemplateRow;
    jQ('#staging-bundle-option-selection-row-template').each(function(){
        bundleTemplateRow = DOMPurify.sanitize(jQ('#staging-bundle-option-selection-row-template').html());
    });
    
    $(document).ready(function () {
        if ($("#stage-wk-bodymain input[name='product[price_type]']").val()) {
            $("#stage-wk-bodymain .col-price.price-type-box").show();
        }
    });
    $(document).on('click','td .actions .action-delete.bundle-delete-options',function (event) {
        bStageSelection.remove(event);
    });
    $(document).on('click','.col-default .default',function (event) {
        bStageSelection.checkGroup(event);
    });

    if(typeof StageBundle=='undefined') {
        window.StageBundle = {};
    }

    StageBundle.Selection = Class.create();
    var inputs;
    StageBundle.Selection.prototype = {
        idLabel : 'staging_bundle_selection',
        scopePrice : 0,
        templateBox : '',
        templateRow : '',
        itemsCount : 0,
        row : null,
        gridSelection: new Hash(),
        gridRemoval: new Hash(),
        gridSelectedProductSkus: [],

        _create: function () {
            let self = this,
                test = self.options.test,
                idLabel = self.options.idLabel,
                scopePrice = self.options.scopePrice,
                selectionSearchUrl = self.options.selectionSearchUrl;
        },

        initialize : function () {
            // this.templateBox = '<div class="tier form-list" id="' + this.idLabel + '_box_<%- data.parentIndex %>">' + bundleTemplateBox + '</div>';

            this.templateRow = '<tr class="selection" id="staging_' + this.idLabel + '_row_<%- data.index %>">' + bundleTemplateRow + '</tr>';

        },

        gridUpdateCallback: function () {
            (function ($) {
                var $grid = $('table[id^=staging_bundle_selection_search_grid_]:visible');
                $grid.find('.checkbox').prop({checked: false});

                var checkRowBySku = function (sku) {
                    sku = $.trim(sku);
                    $grid.find('.sku').filter(function () {
                        return $.trim($(this).text()) == sku;
                    }).closest('tr').find('.checkbox').prop({checked: true});
                };
                $.each(bStageSelection.gridSelection.values().pop().toArray(), function () {
                    checkRowBySku(this.pop().get('sku'));
                });

                $.each(bStageSelection.gridSelectedProductSkus, function () {
                    if (!bStageSelection.gridRemoval.get(this)) {
                        checkRowBySku(this);
                    }
                });
            })($);
        },
        priceTypeManager: function () {
            if ($("#stage-wk-bodymain input[name='product[price_type]']").val()) {
                bStageOption['priceTypeFixed']();
            } else {
                bStageOption['priceTypeDynamic']();
            }
        },

        addRow : function (parentIndex, data) {
            this.templateBox = '<div class="tier form-list" id="staging_' + this.idLabel + '_box_'+parentIndex+'">' + bundleTemplateBox + '</div>';
            
            var box = null;

            if (!(box = $("#staging_"+this.idLabel + '_box_' + parentIndex).length)) {
                this.addBox(parentIndex);
                box = $('staging_' + this.idLabel + '_box_' + parentIndex);
                this.itemsCount = 0;
            } else {
                this.itemsCount = $("#staging_bundle_option_"+parentIndex+ " tr").length-1;
            }

            var option_type = $('staging_' + bStageOption.idLabel + '_' + parentIndex + '_type');
            if (!data) {
                var data = {};
            }

            if (data.can_read_price != undefined && !data.can_read_price) {
                data.selection_price_value = '';
            } else {
                data.selection_price_value = Number(Math.round(data.selection_price_value + "e+2") + "e-2").toFixed(2);
            }
            data.index = this.itemsCount++;
            data.parentIndex = parentIndex;
            option_type.value = $("#staging_" + bStageOption.idLabel + '_' + parentIndex + '_type').val();
            if (option_type.value == 'multi' || option_type.value == 'checkbox') {
                data.option_type = 'checkbox';
            } else {
                data.option_type = 'radio';
            }
            // condition for edit option
            if (data.is_default == 1) {
                data.checked = 'checked="checked"';
            }
            this.template = mageTemplate(this.templateRow);
            var tbody = $$('#staging_' + this.idLabel + '_box_' + parentIndex + ' tbody');
            var escapedHTML = this.template({
                data: data
            }).replace(/<(\/?)script/g, '&lt;$1script');
            jQ(tbody).after(DOMPurify.sanitize(escapedHTML)).css('display','block');

            // Element.insert(tbody[0], {bottom: escapedHTML});
            if (data.selection_price_type) {
                $("#staging_" + this.idLabel +'_'+parentIndex+ '_'+data.index+'_price_type option').each(function () {
                  if ($(this).val() == data.selection_price_type) {
                        $(this).prop("selected","selected");
                    }
                });
            }
            if (data.selection_price_type) {
                $("#staging_"+this.idLabel + '_'+data.index+'_can_change_qty').each(function () {
                  if ($(this).val() == data.selection_can_change_qty) {
                    // if (option.value==data.selection_can_change_qty) {
                        $(this).prop("selected","selected");
                    }
                });
            }
            var checkbox = $('staging_' + this.idLabel + '_'+data.index+'_price_scope');
            if (checkbox && this.scopePrice) {
                if (data.price_scope === undefined) {
                    checkbox.up().hide();
                } else if (!data.price_scope) {
                    checkbox.checked = true;
                    this.addScope(null, checkbox);
                }
            }
            this.bindScopeCheckbox();

            if (option_type.value == 'multi' || option_type.value == 'checkbox') {
                /**
                 * Hide not needed elements (user defined qty select box)
                 */
                inputs = $A($$('#staging_' + this.idLabel + '_box_' + data.parentIndex + ' .qty-box'));
                inputs.each(
                    function (elem) {
                        elem.hide();
                    }
                );
            }

            if (!$('staging_price_type') || $('staging_price_type').value != '1') {
                /**
                 * Hide not needed elements (price type select and price input)
                 */
                inputs = $A($$('#staging_' + this.idLabel + '_box_' + data.parentIndex + ' .price-type-box'));
                inputs.each(
                    function (elem) {
                        elem.hide();
                    }
                );
            }

            $('#staging_bundle_option_' + parentIndex + ' .no-products-message').hide();
        },



        checkGroup : function (event) {
            var i;
            var element = Event.element(event);
            if (element.type == 'radio') {
                var box = element.up('div');

                var inputs = $$('div#staging_' + box.id + ' input.default');
                if (inputs) {
                    for (i=0; i< inputs.length; i++) {
                        if (inputs[i].name != element.name) {
                            inputs[i].checked = false;
                        }
                    }
                }
            }
        },

        bindScopeCheckbox : function () {
            var checkboxes = $$('.bundle-option-price-scope-checkbox');
            for (var i=0; i<checkboxes.length; i++) {
                if (!$(checkboxes[i]).binded) {
                    $(checkboxes[i]).binded = true;
                    Event.observe(checkboxes[i], 'click', this.addScope.bind(this));
                }
            }
        },

        addScope : function (event, element) {
            if (element == undefined) {
                element = $(Event.element(event));
            }
            var priceValue = $(element.id.sub('scope', 'value'));
            var priceType = $(element.id.sub('scope', 'type'));

            if (element.checked) {
                priceValue.disable();
                priceType.disable();
            } else {
                priceValue.enable();
                priceType.enable();
            }
        },

        addBox : function (parentIndex) {
            var divData = jQ("#staging_"+bStageOption.idLabel + '_' + parentIndex+'-content .fieldset .fieldset-alt').html();
            var div = "<fieldset class='fieldset-alt'>"+divData+"</fieldset>"
            this.template = mageTemplate(this.templateBox);
            var data = {'parentIndex' : parentIndex};
            jQ('#staging_bundle_option_'+parentIndex+'-content .fieldset-alt').after(DOMPurify.sanitize(this.templateBox)).css('display','block');
        },

        remove : function (event) {
            var element = Event.findElement(event, 'tr');
            var container = Event.findElement(event, 'div');
            if (element) {
                Element.select(element, '.delete').each(function (elem) {
                    elem.value='1'
                });
                Element.select(element, ['input', 'select']).each(function (elem) {
                    elem.hide()
                });
                Element.removeClassName(element, 'selection');
                Element.hide(element);

                var selection = $$(container.id + ' tr.selection');
                if (container && selection && !selection.length) {
                    container.hide();
                    $(element).closest('.option-box').find('.no-products-message').show();
                }
            }
        },


        productGridRowInit : function (grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0];
            var inputs = $(row).getElementsByClassName('input-text');
            for (var i = 0; i < inputs.length; i++) {
                inputs[i].checkbox = checkbox;
            }
        },

        productGridCheckboxCheck : function (grid, element, checked) {
            var id = element.up('table').id.split('_')[4];
            if (element.value > 0) {
                var tr = element.parentNode.parentNode,
                    sku = $.trim(tr.select('td.sku')[0].innerHTML),
                    name = $.trim(tr.select('td.sku')[0].innerHTML);
                if (element.checked) {
                    if (!this.gridSelection.get(id)) {
                        this.gridSelection.set(id, $H({}));
                    }
                    this.gridSelection.get(id).set(element.value, $H({}));
                    this.gridSelection.get(id).get(element.value).set('name', tr.select('td.name')[0].innerHTML);
                    this.gridSelection.get(id).get(element.value).set('sku', sku);
                    this.gridRemoval.unset(sku);
                } else {
                    this.gridSelection.get(id).unset(element.value);
                    this.gridRemoval.set(sku, 1);
                }
            }
        },

        productGridRowClick : function (grid, event) {
            var trElement = Event.findElement(event, 'tr');
            var isInput = Event.element(event).tagName == 'INPUT';
            var isInput = 1;
            if (trElement) {
                var checkbox = Element.select(trElement, 'input');
                if (checkbox[0]) {
                    var checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                    grid.setCheckboxChecked(checkbox[0], checked);
                }
            }
        }
    };
    var bStageSelection = new StageBundle.Selection();
    var bgridStageSelection = bStageSelection.gridSelection;

   $.widget('mage.stageBundleProduct', {
        _create: function () {
            this._initOptionBoxes()
                ._initSortableSelections()
                ._bindCheckboxHandlers()
                ._initCheckboxState()
                ._bindAddSelectionDialog()
                ._hideProductTypeSwitcher();
        },
        _initOptionBoxes: function () {
            this.element.sortable({
                axis: 'y',
                handle: '[data-role=draggable-handle]',
                items: '.option-box',
                update: this._updateOptionBoxPositions,
                tolerance: 'pointer'
            });

            var syncOptionTitle = function (event) {
                var originalValue = $(event.target).attr('data-original-value'),
                    currentValue = $(event.target).val(),
                    optionBoxTitle = $('.admin__collapsible-title > span', $(event.target).closest('.option-box')),
                    newOptionTitle = $.mage.__('New Option');

                optionBoxTitle.text(currentValue === '' && !originalValue.length ? newOptionTitle : currentValue);
            };
            this._on({
                'change .field-option-title input[name$="[title]"]': syncOptionTitle,
                'keyup .field-option-title input[name$="[title]"]': syncOptionTitle,
                'paste .field-option-title input[name$="[title]"]': syncOptionTitle
            });

            return this;
        },
        _initSortableSelections: function () {
            this.element.find('.option-box .form-list tbody').sortable({
                axis: 'y',
                handle: '[data-role=draggable-handle]',
                helper: function (event, ui) {
                    ui.children().each(function () {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                update: this._updateSelectionsPositions,
                tolerance: 'pointer'
            });

            return this;
        },
        _initCheckboxState: function () {
            this.element.find('.is-required').each(function () {
                $(this).prop('checked', $(this).closest('.option-box').find('[name$="[required]"]').val() > 0);
            });

            this.element.find('.is-user-defined-qty').each(function () {
                $(this).prop('checked', $(this).closest('.qty-box').find('.select').val() > 0);
            });

            return this;
        },
        _bindAddSelectionDialog: function () {
            var jQ = $.noConflict();
            var widget = this;
            var data = jQ("#staging-wk-bundle-product-grid").clone();

            this._on({'click .staging-add-selection': function (event) {

                let $optionBox = jQ(event.target).closest('.option-box'),
                    $selectionGrid = $optionBox.find('.selection-search').clone(),
                    optionIndex = DOMPurify.sanitize($optionBox.attr('id').replace('staging_bundle_option_', '')),
                    productIds = [],
                    productSkus = [],
                    selectedProductList = {};

                $optionBox.find('[name$="[product_id]"]').each(function () {
                    if (!$(this).closest('tr').find('[name$="[delete]"]').val()) {
                        productIds.push($(this).val());
                        productSkus.push($(this).closest('tr').find('.col-sku').text());
                    }
                });
                $(".staging-bundle .action-multicheck-wrap input").on('change', function(event) {
                    if (event.target.checked) {
                        $(".staging-bundle .data-row .data-grid-checkbox-cell-inner input").trigger('change');
                    } else {
                        selectedProductList = {};
                    }
                });

                bgridStageSelection.set(optionIndex, $H({}));
                bStageSelection.gridRemoval = $H({});
                bStageSelection.gridSelectedProductSkus = productSkus;
                $(document).on("change", ".data-row .data-grid-checkbox-cell-inner input", function () {
                    var tr = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        selectedProductList[$(this).val()] = {
                            name: $.trim(tr.find('.col-name .data-grid-cell-content').html()),
                            sku: $.trim(tr.find('.col-sku .data-grid-cell-content').html()),
                            index: $('#staging_bundle_option_' + optionIndex +" tr").length,
                            // price: $.trim(tr.find('.col-price .data-grid-cell-content').html()),
                            product_id: $(this).val(),
                            option_id: $('staging_bundle_selection_id_' + optionIndex).val(),
                            selection_price_value: 0,
                            selection_qty: 1,
                            can_read_price: $("#stage-wk-bodymain input[name='product[price_type]']").val()
                        };
                    } else {
                        delete selectedProductList[$(this).val()];
                    }
                });
                $selectionGrid.modal({
                    title: $optionBox.find('input[name$="[title]"]').val() === '' ?
                        $.mage.__('Add Products to New Option'):
                        $.mage.__('Add Products to Option "%1"')
                            .replace('%1',($('<div>').text($optionBox.find('input[name$="[title]"]').val()).html())),
                    modalClass: 'staging-bundle',
                    type: 'slide',
                    closed: function (e, modal) {
                        jQuery("ul.action-menu li span.action-menu-item:nth-child(1)").click();
                        bStageOption['priceType' + ($("#stage-wk-bodymain input[name='product[price_type]']").val() == '1' ? 'Fixed' : 'Dynamic')]();
                    },
                    buttons: [{
                        text: $.mage.__('Add Selected Products'),
                        'class': 'action-primary action-add',
                        click: function () {
                            $.each(selectedProductList, function () {
                                this.index = $("#staging_bundle_option_"+ optionIndex +" tr").length;
                                bStageSelection.addRow(optionIndex, this);
                            });
                            bStageSelection.gridRemoval.each(
                                function (pair) {
                                        $optionBox.find('.col-sku').filter(function () {
                                        return $.trim($(this).text()) === pair.key; // find row by SKU
                                    }).closest('tr').find('button.delete').trigger('click');
                                }
                            );
                            widget.refreshSortableElements();
                            widget._updateSelectionsPositions.apply(widget.element);
                            $('#staging-wk-bundle-product-grid').addClass("no-display");
                            $selectionGrid.modal('closeModal');

                        }
                    }]
                });
                $selectionGrid.html($("#staging-wk-bundle-product-grid")).modal('openModal');
                    $("#stage-wk-bodymain tr.data-row").show();
                    productIds.each(function (val) {
                        $("#stageIdscheck"+val).closest('tr').hide();
                    });
                    $("#staging-wk-bundle-product-grid").removeClass("no-display");
                }
            });

            return this;
        },
        _hideProductTypeSwitcher: function () {
            weightHandler.hideWeightSwitcher();
        },
        _bindCheckboxHandlers: function () {
            this._on({
                'change .is-required': function (event) {
                    var $this = $(event.target);
                    $this.closest('.option-box').find('[name$="[required]"]').val($this.is(':checked') ? 1 : 0);
                },
                'change .is-user-defined-qty': function (event) {
                    var $this = $(event.target);
                    $this.closest('.qty-box').find('.select').val($this.is(':checked') ? 1 : 0);
                }
            });

            return this;
        },
        _updateOptionBoxPositions: function () {
            $(this).find('[name^=bundle_options][name$="[position]"]').each(function (index) {
                $(this).val(index);
            });

            return this;
        },
        _updateSelectionsPositions: function () {
            $(this).find('[name^=bundle_selections][name$="[position]"]').each(function (index) {
                $(this).val(index);
            });

            return this;
        },
        refreshSortableElements: function () {
            this.element.sortable('refresh');
            this._updateOptionBoxPositions.apply(this.element);
            this._initSortableSelections();
            this._initCheckboxState();

            return this;
        }
    });
});

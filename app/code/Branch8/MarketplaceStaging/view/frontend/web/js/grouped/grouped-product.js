define([
    'jquery',
    'mage/template',
    'plugins/DOMPurify',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/adminhtml/grid'
], function ($, mageTemplate, DOMPurify) {
    'use strict';

    $.widget('mage.stageGroupedProduct', {
        /**
         * Create widget
         * @private
         */
        _create: function () {
            this.$grid = this.element.find('[data-role=staging-grouped-product-grid]');
            this.$grid.sortable({
                distance: 8,
                items: '[data-role=row]',
                tolerance: 'pointer',
                cancel: ':input',
                update: $.proxy(function () {
                    this.element.trigger('resort');
                }, this)
            });

            this.productTmpl = mageTemplate('#staging-group-product-template');
            $.each(
                this.$grid.data('products'),
                $.proxy(function (index, product) {
                    this._add(null, product);
                }, this)
            );

            this._on({
                'add': '_add',
                'resort': '_resort',
                'click [data-column=actions] [data-role=delete]': '_remove'
            });

            this._bindDialog();
            this._updateGridVisibility();
        },

        /**
         * Add product to grouped grid
         * @param {EventObject} event
         * @param {Object} product
         * @private
         */
        _add: function (event, product) {
            var tmpl,
            productExists;
            tmpl = this.productTmpl({
                data: product
            });

            $(tmpl).appendTo(this.$grid.find('tbody'));
        },

        /**
         * Remove product
         * @param {EventObject} event
         * @private
         */
        _remove: function (event) {
            var jQ = $.noConflict();
            jQ(event.target).closest('[data-role=row]').remove();
            this.element.trigger('resort');
            this._updateGridVisibility();
            var idd = DOMPurify.sanitize(jQ(event.target).closest('[data-role=row]').find('button').attr('id')?.toString());
            var ids = idd.split('-');
            var id = ids[ids.length-1];
            var firstEnd = "td.mageproduct_id div:contains(";
            var lastEnd = ")";
            $(firstEnd+id+lastEnd).closest('tr').show().addClass("deleted");
        },

        /**
         * Resort products
         * @private
         */
        _resort: function () {
            this.element.find('[data-role=position]').each($.proxy(function (index, element) {
                $(element).val(index + 1);
            }, this));
        },

        /**
         * Create modal for show product
         * @private
         */
        _bindDialog: function () {
            var widget = this,
                selectedProductList = {},
                popup = $('[data-role=staging-add-product-dialog]');
            popup.modal({
                type: 'slide',
                innerScroll: true,
                title: $.mage.__('Add Products to Group'),
                modalClass: 'staging-grouped',
                open: function () {
                    $(this).addClass('admin__scope-old');
                },
                buttons: [{
                    id: 'staging-grouped-product-dialog-apply-button',
                    text: $.mage.__('Add Selected Products'),
                    'class': 'action-primary action-add',
                    click: function () {
                        if ('on' in selectedProductList || window.selectAll == 1 || window.selectPage == 1) {
                            selectedProductList = widget._selectAll();
                            window.selectAll = 0;
                            window.selectPage = 0;
                        }
                        $.each(selectedProductList, function (index, product) {
                            widget._add(null, product);
                            $('.staging-grouped #stageIdscheck'+index).closest('tr').hide();
                        });
                        widget._resort();
                        widget._updateGridVisibility();
                        jQuery("ul.action-menu li span.action-menu-item:nth-child(1)").click();
                        popup.modal('closeModal');
                    }
                }]
            });

            popup.on('click', '[data-role=row]', function (event) {
                var target = $(event.target);
                if (!target.is('input')) {
                    target.closest('[data-role=row]')
                        .find('[data-column=entity_ids] input')
                        .prop('checked', function (element, value) {
                            return !value;
                        })
                        .trigger('change');
                }
            });

            popup.on('change',".admin__control-checkbox", $.proxy(function (event) {
                var jQ = $.noConflict();
                var main_html = jQ('#staging_wk_mpgrouped_products');
                var html = main_html.find('.table[data-role="staging-grouped-product-grid"]');
                var bodytable = html.find('tbody');
                var arrayHtml=[];
                var arrayData =  bodytable.find('tr');
                jQ(bodytable).find('tr').each (function (index, tr) {
                    arrayHtml.push(index);
                });

                if (arrayHtml.length){
                    var trTotal = arrayHtml.length;
                } else {
                    var trTotal = 0;
                }

                var total = parseInt(trTotal);
                var inside_html = jQuery('.staging-grouped.modal-slide').find("tbody>tr:visible");
                if (!inside_html.hasClass('data-grid-tr-no-data')) {
                    var total_records = parseInt(inside_html.length);
                } else {
                    var total_records = parseInt(0);
                }

                var checkedHtml=[];
                jQ('.staging-grouped.modal-slide .admin__data-grid-wrap tbody .admin__control-checkbox').each(function (index, value) {
                    this.checked ? checkedHtml.push(index) : "";
                });
                var inctotal = checkedHtml.length;
                var inctotal = inctotal + trTotal;
                jQ('.staging-grouped.modal-slide').find('div.admin__control-support-text').html('<span>'+total_records+' records found ('+inctotal+' selected)</span>');

                var element = jQ(event.target),
                    product = {};
                if (element.is(':checked')) {
                    var tr = element.closest('tr');
                    product.id = DOMPurify.sanitize(element.val()?.toString());
                    product.qty = 0;
                    product.name = DOMPurify.sanitize(tr.find('.col-name .data-grid-cell-content').html()).trim();
                    product.sku = DOMPurify.sanitize(tr.find('.col-sku .data-grid-cell-content').html()).trim();
                    product.price = DOMPurify.sanitize(tr.find('.col-price .data-grid-cell-content').html()).trim();
                    element.closest('[data-role=row]').find('[data-column]').each(function (index, element) {
                        product[jQ(element).data('column')] = DOMPurify.sanitize($(element).text()).trim();
                    });
                    selectedProductList[product.id] = product;
                } else {
                    delete selectedProductList[element.val()];
                }
            }, this));

            var gridPopup = $(this.options.gridPopup).data('gridObject');
            $('[data-role=staging-add-product]').on('click', function (event) {
                event.preventDefault();
                popup.modal('openModal');
                selectedProductList = {};
            });

            $('#grouped_grid_popup').on('gridajaxsettings', function (event, ajaxSettings) {
                var ids = widget.$grid.find('[data-role=id]').map(function (index, element) {
                    return $(element).val();
                }).toArray();
                ajaxSettings.data.filter = $.extend(ajaxSettings.data.filter || {}, {
                    'entity_ids': ids
                });
            })
        },

        /**
         * create selected productList in case of select all
         */
        _selectAll: function () {
            var jQ = $.noConflict();
            var selectedProductList={};
            jQ("#staging_wk_mpgrouped_products .grouped-product-grid .admin__data-grid-wrap tbody tr").each(function () {
                var product = {};
                var tr = jQ(this);
                if (jQ(tr).is(':visible')) {
                    var element = this.closest('input[type="ckeckbox"]');
                    if (jQ('.staging-grouped #stageIdscheck'+DOMPurify.sanitize(tr.find('.mageproduct_id .data-grid-cell-content').text()).trim()).is(':checked')) {
                    product.id = DOMPurify.sanitize(tr.find('.mageproduct_id .data-grid-cell-content').html()).trim();
                    product.qty = 0;
                    product.name = DOMPurify.sanitize(tr.find('.col-name .data-grid-cell-content').html()).trim();
                    product.sku = DOMPurify.sanitize(tr.find('.col-sku .data-grid-cell-content').html()).trim();
                    product.price = DOMPurify.sanitize(tr.find('.col-price .data-grid-cell-content').html()).trim();
                    selectedProductList[product.id] = product;
                    }
                }
            });

            var valNull = "";
            if(selectedProductList[valNull] != null){
                delete selectedProductList[valNull];
            }
            return selectedProductList;
        },

        /**
         * Show or hide message
         * @private
         */
        _updateGridVisibility: function () {
            var showGrid = this.element.find('[data-role=id]').length > 0;
            this.element.find('.grid-container').toggle(showGrid);
            this.element.find('.no-products-message').toggle(!showGrid);
        }
    });

    return $.mage.stageGroupedProduct;
});

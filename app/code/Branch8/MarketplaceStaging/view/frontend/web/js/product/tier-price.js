/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'uiRegistry',
    'mage/translate',
    'mage/template',
    'plugins/DOMPurify',
    'select2',
    'prototype'
], function ($, registry, $t, mageTemplate, DOMPurify) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_MarketplaceStaging/js/product/tier-price': function (dataInfo) {
            var jQ = $.noConflict();
            let tierPriceControl = {
                template: mageTemplate('[data-template=tier-price-fixed-row-template]'),
                templatePercent: mageTemplate('[data-template=tier-price-percent-row-template]'),
                itemsCount: 0,
                addItem : function (value) {
                    let data = {
                        cust_group: dataInfo.defaultCustomerGroup,
                        price_qty: '',
                        value_type: 'fixed',
                        index: this.itemsCount++
                    };

                    if(value.cust_group) {
                        data.cust_group = value.cust_group;
                        data.price_qty = value.price_qty;
                        data.value_type = value.value_type;
                        data.price_id = value.price_id;
                        if (data.value_type == 'percent') {
                            data.percentage_value = value.percentage_value;
                        } else {
                            data.price = Math.round(value.price);
                        }
                    } else {
                        data.price = '';
                    }

                    if (data.value_type == 'percent') {
                        Element.insert($('tier_price_container'), {
                            bottom: this.templatePercent({
                                data: data
                            })
                        });
                    } else {
                        Element.insert($('tier_price_container'), {
                            bottom: this.template({
                                data: data
                            })
                        });
                    }

                    $('tier_price_row_' + data.index + '_cust_group').value = data.cust_group;
                    $('tier_price_row_' + data.index + '_value_type').value = data.value_type;
                    this.bindRemoveButtons();
                },
                disableElement: function(el) {
                    el.disabled = true;
                    el.addClassName('disabled');
                },
                deleteItem: function(event) {
                    var tr = Event.findElement(event, 'tr');
                    if (tr) {
                        Element.select(tr, '.delete').each(function(elem){elem.value='1'});
                        Element.select(tr, ['input', 'select']).each(function(elem){elem.hide()});
                        Element.hide(tr);
                        Element.addClassName(tr, 'no-display template');
                    }
                    return false;
                },
                bindRemoveButtons: function() {
                    let buttons = $('tbody#tier_price_container .action-delete');
                    for(let i=0;i<buttons.length;i++){
                        if(!$(buttons[i]).binded){
                            $(buttons[i]).binded = true;
                            Event.observe(buttons[i], 'click', this.deleteItem.bind(this));
                        }
                    }
                }
            };

            jQ('#tier_price_container').on('change','select.type-price', function () {
                if (jQ(this).val() == 'percent') {
                    let currentRow = jQ(this).closest('tr'),
                        index = DOMPurify.sanitize(currentRow.data('index')?.toString());
                    currentRow.find('.col-price').html(
                        '<div class="admin__control-addon">\n' +
                        '<input class="validate-greater-than-zero required-entry" type="text" name="product[tier_price]['+index+'][percentage_value]" value="" id="tier_price_row_'+index+'_percentage_value" />\n' +
                        '<label class="admin__addon-prefix" for="tier_price_row_'+index+'_percentage_value">\n' +
                        '<span>%</span>\n' +
                        '</label>\n' +
                        '</div>'
                    );
                } else {
                    let currentRow = jQ(this).closest('tr'),
                        index = DOMPurify.sanitize(currentRow.data('index')?.toString());
                    currentRow.find('.col-price').html(
                        '<div class="admin__control-addon">\n' +
                        '<input class="validate-greater-than-zero required-entry" type="text" name="product[tier_price]['+index+'][price]" value="" id="tier_price_row_'+index+'_price" />\n' +
                        '<label class="admin__addon-prefix" for="tier_price_row_'+index+'_price">\n' +
                        '<span>'+dataInfo.currencySymbol+'</span>\n' +
                        '</label>\n' +
                        '</div>'
                    );
                }
            });

            if (jQ('#point_money_config_type').val() == '3' || jQ('#point_money_config_type').val() == '4') {
                if (jQ('#point_money_config_type').val() == '3') {
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').show();
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').show();
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').show();
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').show();
                    jQ('#point_money_config_product_point').parents('.field').hide();
                } else {
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                    jQ('#point_money_config_product_point').parents('.field').show();
                }
            } else {
                jQ('#point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                jQ('#point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                jQ('#point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                jQ('#point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                jQ('#point_money_config_product_point').parents('.field').hide();
            }
            if (jQ('#point_money_config_free_ratio_lower_redeem_limit_value').val() == 0
                && jQ('#point_money_config_free_ratio_upper_redeem_limit_value').val() > 0) {
                jQ('#point_money_config_free_ratio_lower_redeem_limit_value').attr('readonly', 'readonly');
            } else if (jQ('#point_money_config_free_ratio_lower_redeem_limit_value').val() > 0
                && jQ('#point_money_config_free_ratio_upper_redeem_limit_value').val() == 0) {
                jQ('#point_money_config_free_ratio_upper_redeem_limit_value').attr('readonly', 'readonly');
            }
            jQ('#point_money_config_free_ratio_upper_redeem_limit_value').on('blur', function(){
                if(jQ(this).val() > 0){
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_value').val('0').trigger('change');
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_value').attr('readonly', 'readonly');
                } else if (jQ(this).val() == 0) {
                    if (jQ('#point_money_config_free_ratio_lower_redeem_limit_value').val() == 0) jQ('#point_money_config_free_ratio_lower_redeem_limit_value').val('').trigger('change');
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_value').removeAttr('readonly');
                }
            });
            jQ('#point_money_config_free_ratio_lower_redeem_limit_value').on('blur', function(){
                if(jQ(this).val() > 0){
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_value').val('0').trigger('change');
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_value').attr('readonly', 'readonly');
                } else if (jQ(this).val() == 0) {
                    if (jQ('#point_money_config_free_ratio_upper_redeem_limit_value').val() == 0) jQ('#point_money_config_free_ratio_upper_redeem_limit_value').val('').trigger('change');
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_value').removeAttr('readonly');
                }
            });

            if (jQ("#limit_purchased_enable").is(":checked")) {
                jQ("#limit_purchased_customer_group").parents(".field").show();
                jQ("#limit_purchased_qty").parents(".field").show();
                jQ("#limit_purchased_start_time").parents(".field").show();
                jQ("#limit_purchased_end_time").parents(".field").show();
            } else {
                jQ("#limit_purchased_customer_group").parents(".field").hide();
                jQ("#limit_purchased_qty").parents(".field").hide();
                jQ("#limit_purchased_start_time").parents(".field").hide();
                jQ("#limit_purchased_end_time").parents(".field").hide();
            }

            if (jQ('#brand').length) {
                jQ("#brand").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    allowClear: true
                });
            }
            if (jQ('#main_category').length) {
                jQ("#main_category").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    allowClear: true
                });
            }

            jQ('#display_barcode').change(function () {
                if (jQ(this).val() > 0) {
                    jQ('#barcode_type').parents('.field').show();
                } else {
                    jQ('#barcode_type').parents('.field').hide();
                }
            });
            if (jQ('#display_barcode').val() < 1) {
                jQ('#barcode_type').parents('.field').hide();
            }
            if (jQ('#is_offline_operation').val() > 0) {
                jQ('#exchange_hint').parents('.field').hide();
                jQ('#exchange_url').parents('.field').hide();
            } else {
                if (jQ('#exchange_url').val() && jQ('#exchange_url').val().trim()) {
                    jQ('#is_offline_operation').find('option[value="1"]').prop('disabled', true);
                }
            }

            jQ('#is_offline_operation').change(function () {
                if (jQ(this).val() > 0) {
                    jQ('#exchange_hint').parents('.field').hide();
                    jQ('#exchange_url').parents('.field').hide();
                } else {
                    jQ('#exchange_hint').parents('.field').show();
                    jQ('#exchange_url').parents('.field').show();
                }
            });

            jQ('#exchange_url').on('change', function() {
                if (!jQ(this).val() || !jQ(this).val().trim()) {
                    jQ('#is_offline_operation').find('option[value="1"]').prop('disabled', false);
                } else {
                    jQ('#is_offline_operation').find('option[value="1"]').prop('disabled', true);
                }
            });

            jQ('#point_money_config_type').change(function () {
                if (jQ(this).val() == '3' || jQ(this).val() == '4') {
                    if (jQ(this).val() == '3') {
                        jQ('#point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').show();
                        jQ('#point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').show();
                        jQ('#point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').show();
                        jQ('#point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').show();
                        jQ('#point_money_config_product_point').parents('.field').hide();
                    } else {
                        jQ('#point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                        jQ('#point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                        jQ('#point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                        jQ('#point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                        jQ('#point_money_config_product_point').parents('.field').show();
                    }
                } else {
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                    jQ('#point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                    jQ('#point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                    jQ('#point_money_config_product_point').parents('.field').hide();
                }
            });

            jQ("#limit_purchased_enable").click(function () {
                if (jQ(this).is(":checked")) {
                    jQ("#limit_purchased_customer_group").parents(".field").show();
                    jQ("#limit_purchased_qty").parents(".field").show();
                    jQ("#limit_purchased_start_time").parents(".field").show();
                    jQ("#limit_purchased_end_time").parents(".field").show();
                } else {
                    jQ("#limit_purchased_customer_group").parents(".field").hide();
                    jQ("#limit_purchased_qty").parents(".field").hide();
                    jQ("#limit_purchased_start_time").parents(".field").hide();
                    jQ("#limit_purchased_end_time").parents(".field").hide();
                }
            });

            jQ("#price_type").click(function () {
                if (jQ(this).is(":checked")) {
                    jQ("#product-custom-options-content #product_options_container").hide();
                    jQ("#product-custom-options-content .actions").hide();
                    jQ('#dynamic-price-warning').show();
                } else {
                    jQ("#product-custom-options-content #product_options_container").show();
                    jQ("#product-custom-options-content .actions").show();
                    jQ('#dynamic-price-warning').hide();
                }
            });

            jQ(".selected-attr input[type='checkbox']").click(function () {
                let currentValue = DOMPurify.sanitize(jQ(this).val()?.toString());
                if (jQ(this).is(":checked")) {
                    jQ("#field_"+currentValue).show();
                } else {
                    jQ("#field_"+currentValue+" input").val('');
                    jQ("#field_"+currentValue+" select").val('');
                    jQ("#field_"+currentValue+" textarea").val('');
                    jQ("#field_"+currentValue).hide();
                }
            });

            jQ('body').on('input focus keydown keyup', 'textarea[name="product[short_description]"]', function() {
                //get Textearea text
                let text = jQ(this).val();
                //Split with \n carriage return
                let lines = text.split("\n");

                for (let i = 0; i < lines.length; i++) {
                    if (lines[i].length > 30) {
                        lines[i] = lines[i].substring(0, 30);
                    }
                }
                while (lines.length > 3){
                    lines.pop();
                }
                //Join with \n.
                //Set textarea
                jQ(this).val(lines.join("\n"));
            });

            jQ('body').on('keyup', 'input[name="product[sku]"]', function() {
                //get seller id
                let prefix = false,
                    val = DOMPurify.sanitize(jQ(this).val());
                if (val){
                    if (val.indexOf('HOTAI') !== -1) {
                        let check = val.split('-');
                        if (check[0].length < 6) {
                            prefix = true;
                        }
                    } else {
                        prefix = true;
                    }
                    if (prefix) {
                        jQ(this).val('HOTAI' + Math.floor(Date.now() / 1000) + '-' + val);
                    }
                }
            });

            if (jQ('#use_config_is_returnable').val() == 1) {
                jQ('#is_returnable').change(function () {
                    jQ('#use_config_is_returnable').val('0');
                });
            }

            if (jQ("#price_type").length && jQ("#price_type").is(":checked")) {
                jQ("#product-custom-options-content #product_options_container").hide();
                jQ("#product-custom-options-content .actions").hide();
            }

            if($('add_tier_price_item')){
                Event.observe('add_tier_price_item', 'click', tierPriceControl.addItem.bind(tierPriceControl));
            }

            jQuery.each(dataInfo.tierPriceInfo, function(key, value) {
                tierPriceControl.addItem(value);
            });

            if (jQ('#show_long_time_ship').is(':checked')) {
                jQ('#long_time_ship').parents('.field').show();
            } else {
                jQ('#long_time_ship').parents('.field').hide();
            }

            jQ('#show_long_time_ship').click(function () {
                if (jQ(this).is(':checked')) {
                    jQ('#long_time_ship').parents('.field').show();
                } else {
                    jQ('#long_time_ship').parents('.field').hide();
                }
            });
        }
    };
});

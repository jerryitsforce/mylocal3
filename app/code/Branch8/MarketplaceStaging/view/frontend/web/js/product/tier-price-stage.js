/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'uiRegistry',
    'mage/translate',
    'mage/template',
    'plugins/DOMPurify',
    'prototype'
], function ($, registry, $t, mageTemplate, DOMPurify) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_MarketplaceStaging/js/product/tier-price-stage': function (dataInfo) {
            var jQ = $.noConflict();
            let tierPriceStageControl = {
                template: mageTemplate('[data-template=tier-price-fixed-stage-row-template]'),
                templatePercent: mageTemplate('[data-template=tier-price-percent-stage-row-template]'),
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
                        Element.insert($('tier_price_staging_container'), {
                            bottom: this.templatePercent({
                                data: data
                            })
                        });
                    } else {
                        Element.insert($('tier_price_staging_container'), {
                            bottom: this.template({
                                data: data
                            })
                        });
                    }

                    $('tier_price_staging_row_' + data.index + '_cust_group').value = data.cust_group;
                    $('tier_price_staging_row_' + data.index + '_value_type').value = data.value_type;
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
                    let buttons = $('tbody#tier_price_staging_container .action-delete');
                    for(let i=0;i<buttons.length;i++){
                        if(!$(buttons[i]).binded){
                            $(buttons[i]).binded = true;
                            Event.observe(buttons[i], 'click', this.deleteItem.bind(this));
                        }
                    }
                }
            };

            jQ('#tier_price_staging_container').on('change','select.type-price', function () {
                if (jQ(this).val() == 'percent') {
                    let currentRow = jQ(this).closest('tr'),
                        index = DOMPurify.sanitize(currentRow.data('index')?.toString());
                    currentRow.find('.col-price').html(
                        '<div class="admin__control-addon">\n' +
                        '<input class="validate-greater-than-zero required-entry" type="text" name="product[tier_price]['+index+'][percentage_value]" value="" id="tier_price_staging_row_'+index+'_percentage_value" />\n' +
                        '<label class="admin__addon-prefix" for="tier_price_staging_row_'+index+'_percentage_value">\n' +
                        '<span>%</span>\n' +
                        '</label>\n' +
                        '</div>'
                    );
                } else {
                    let currentRow = jQ(this).closest('tr'),
                        index = DOMPurify.sanitize(currentRow.data('index')?.toString());
                    currentRow.find('.col-price').html(
                        '<div class="admin__control-addon">\n' +
                        '<input class="validate-greater-than-zero required-entry" type="text" name="product[tier_price]['+index+'][price]" value="" id="tier_price_staging_row_'+index+'_price" />\n' +
                        '<label class="admin__addon-prefix" for="tier_price_staging_row_'+index+'_price">\n' +
                        '<span>'+dataInfo.currencySymbol+'</span>\n' +
                        '</label>\n' +
                        '</div>'
                    );
                }
            });

            if (jQ('#staging_point_money_config_type').val() == '3' || jQ('#staging_point_money_config_type').val() == '4') {
                if (jQ('#staging_point_money_config_type').val() == '3') {
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').show();
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').show();
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').show();
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').show();
                    jQ('#staging_point_money_config_product_point').parents('.field').hide();
                } else {
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                    jQ('#staging_point_money_config_product_point').parents('.field').show();
                }
            } else {
                jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                jQ('#staging_point_money_config_product_point').parents('.field').hide();
            }

            if (jQ("#staging_limit_purchased_enable").is(":checked")) {
                jQ("#staging_limit_purchased_customer_group").parents(".field").show();
                jQ("#staging_limit_purchased_qty").parents(".field").show();
                jQ("#staging_limit_purchased_start_time").parents(".field").show();
                jQ("#staging_limit_purchased_end_time").parents(".field").show();
            } else {
                jQ("#staging_limit_purchased_customer_group").parents(".field").hide();
                jQ("#staging_limit_purchased_qty").parents(".field").hide();
                jQ("#staging_limit_purchased_start_time").parents(".field").hide();
                jQ("#staging_limit_purchased_end_time").parents(".field").hide();
            }

            if (jQ('#staging_brand').length) {
                jQ("#staging_brand").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    allowClear: true
                });
            }
            if (jQ('#staging_main_category').length) {
                jQ("#staging_main_category").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    allowClear: true
                });
            }

            jQ('#staging_display_barcode').change(function () {
                if (jQ(this).val() > 0) {
                    jQ('#staging_barcode_type').parents('.field').show();
                } else {
                    jQ('#staging_barcode_type').parents('.field').hide();
                }
            });
            if (jQ('#staging_display_barcode').val() < 1) {
                jQ('#staging_barcode_type').parents('.field').hide();
            }
            if (jQ('#staging_is_offline_operation').val() > 0) {
                jQ('#staging_exchange_hint').parents('.field').hide();
                jQ('#staging_exchange_url').parents('.field').hide();
            } else {
                if (jQ('#staging_exchange_url').val() && jQ('#staging_exchange_url').val().trim()) {
                    jQ('#staging_is_offline_operation').find('option[value="1"]').prop('disabled', true);
                }
            }

            jQ('#staging_is_offline_operation').change(function () {
                if (jQ(this).val() > 0) {
                    jQ('#staging_exchange_hint').parents('.field').hide();
                    jQ('#staging_exchange_url').parents('.field').hide();
                } else {
                    jQ('#staging_exchange_hint').parents('.field').show();
                    jQ('#staging_exchange_url').parents('.field').show();
                }
            });

            jQ('#staging_exchange_url').on('change', function() {
                if (!jQ(this).val() || !jQ(this).val().trim()) {
                    jQ('#staging_is_offline_operation').find('option[value="1"]').prop('disabled', false);
                } else {
                    jQ('#staging_is_offline_operation').find('option[value="1"]').prop('disabled', true);
                }
            });


            jQ('#staging_point_money_config_type').change(function () {
                if (jQ(this).val() == '3' || jQ(this).val() == '4') {
                    if (jQ(this).val() == '3') {
                        jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').show();
                        jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').show();
                        jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').show();
                        jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').show();
                        jQ('#staging_point_money_config_product_point').parents('.field').hide();
                    } else {
                        jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                        jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                        jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                        jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                        jQ('#staging_point_money_config_product_point').parents('.field').show();
                    }
                } else {
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_type').parents('.field').hide();
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').parents('.field').hide();
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_type').parents('.field').hide();
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').parents('.field').hide();
                    jQ('#staging_point_money_config_product_point').parents('.field').hide();
                }
            });
            if (jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').val() == 0
                && jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').val() > 0) {
                jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').attr('readonly', 'readonly');
            } else if (jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').val() > 0
                && jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').val() == 0) {
                jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').attr('readonly', 'readonly');
            }
            jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').on('blur', function(){
                if (jQ(this).val() > 0){
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').val('0').trigger('change');
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').attr('readonly', 'readonly');
                } else if (jQ(this).val() == 0) {
                    if (jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').val() == 0) jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').val('').trigger('change');
                    jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').removeAttr('readonly');
                }
            });
            jQ('#staging_point_money_config_free_ratio_lower_redeem_limit_value').on('blur', function(){
                if (jQ(this).val() > 0){
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').val('0').trigger('change');
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').attr('readonly', 'readonly');
                } else if (jQ(this).val() == 0){
                    if (jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').val() == 0) jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').val('').trigger('change');
                    jQ('#staging_point_money_config_free_ratio_upper_redeem_limit_value').removeAttr('readonly');
                }
            });

            jQ("#staging_limit_purchased_enable").click(function () {
                if (jQ(this).is(":checked")) {
                    jQ("#staging_limit_purchased_customer_group").parents(".field").show();
                    jQ("#staging_limit_purchased_qty").parents(".field").show();
                    jQ("#staging_limit_purchased_start_time").parents(".field").show();
                    jQ("#staging_limit_purchased_end_time").parents(".field").show();
                } else {
                    jQ("#staging_limit_purchased_customer_group").parents(".field").hide();
                    jQ("#staging_limit_purchased_qty").parents(".field").hide();
                    jQ("#staging_limit_purchased_start_time").parents(".field").hide();
                    jQ("#staging_limit_purchased_end_time").parents(".field").hide();
                }
            });

            jQ(".stage-selected-attr input[type='checkbox']").click(function () {
                let currentValue = DOMPurify.sanitize(jQ(this).val()?.toString());
                if (jQ(this).is(":checked")) {
                    jQ("#staging_field_"+currentValue).show();
                } else {
                    jQ("#staging_field_"+currentValue+" input").val('');
                    jQ("#staging_field_"+currentValue+" select").val('');
                    jQ("#staging_field_"+currentValue+" textarea").val('');
                    jQ("#staging_field_"+currentValue).hide();
                }
            });

            if (jQ("#staging_price_type").length && jQ("#staging_price_type").is(":checked")) {
                jQ("#staging-product-custom-options-content #staging_product_options_container").hide();
                jQ("#staging-product-custom-options-content .actions").hide();
            }

            if($('add_tier_price_staging_item')){
                Event.observe('add_tier_price_staging_item', 'click', tierPriceStageControl.addItem.bind(tierPriceStageControl));
            }

            $.each(dataInfo.tierPriceInfo, function(key, value) {
                tierPriceStageControl.addItem(value);
            });

            if (jQ('#staging_show_long_time_ship').is(':checked')) {
                jQ('#staging_long_time_ship').parents('.field').show();
            } else {
                jQ('#staging_long_time_ship').parents('.field').hide();
            }

            jQ('#staging_show_long_time_ship').click(function () {
                if (jQ(this).is(':checked')) {
                    jQ('#staging_long_time_ship').parents('.field').show();
                } else {
                    jQ('#staging_long_time_ship').parents('.field').hide();
                }
            });
        }
    };
});

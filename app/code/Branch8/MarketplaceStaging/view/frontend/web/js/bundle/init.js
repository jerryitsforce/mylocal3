/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'mage/translate',
    'mage/template',
    'jquery/jquery.tabs',
    'Branch8_MarketplaceStaging/js/bundle/bundle-product',
    'prototype',
    'mage/adminhtml/form'
], function (jQuery, $t, mageTemplate) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_MarketplaceStaging/js/bundle/init': function (dataInfo) {
            let optionTemplate = jQuery('#staging-bundle-option-template').html();
            jQuery('#staging-product-custom-options-content #staging_add_new_defined_option').click(function(){
                bStageOption.add();
            });

            function changeInputType(oldObject, oType) {
                let newObject = document.createElement('input');
                newObject.type = oType;
                if(oldObject.size) newObject.size = oldObject.size;
                if(oldObject.value) newObject.value = oldObject.value;
                if(oldObject.name) newObject.name = oldObject.name;
                if(oldObject.id) newObject.id = oldObject.id;
                if(oldObject.onclick) newObject.onclick = oldObject.onclick;
                if(oldObject.className) newObject.className = oldObject.className;
                oldObject.parentNode.replaceChild(newObject,oldObject);
                return newObject;
            }
            window.bStageSelection = new StageBundle.Selection();
            StageBundle.Option = Class.create();
            StageBundle.Option.prototype = {
                idLabel : dataInfo.fieldId,
                templateText : '',
                itemsCount : 0,
                initialize : function(template) {
                    this.templateText = template;
                },

                add : function(data) {
                    if (!data) {
                        data = {};
                        data.default_title = $t('New Option');
                    } else {
                        data.title = data.title.replace(/</g, "&lt;");
                        data.title = data.title.replace(/"/g, "&quot;");
                        data.default_title =  data.title;
                    }
                    data.index = this.itemsCount++;

                    this.template = mageTemplate(this.templateText);
                    jQuery('#staging_product_bundle_container')
                        .append(this.template({
                            data: data
                        }));

                    //set selected type
                    if (data.type) {
                        $A($('staging_' + this.idLabel + '_'+data.index+'_type').options).each(function(option){
                            if (option.value==data.type) option.selected = true;
                        });
                    }

                    // set selected is_require
                    if (data.required) {
                        $A($('staging_' + this.idLabel + '_'+data.index+'_required').options).each(function(option){
                            if (option.value==data.required) option.selected = true;
                        });
                    }
                    // rebind change notifications
                    window.varienWindowOnload(true);

                    if (jQuery && jQuery('#staging_bundle_product_container').data('stageBundleProduct')) {
                        jQuery('#staging_bundle_product_container').stageBundleProduct('refreshSortableElements');
                    }

                    return data.index;
                },

                remove : function(event){
                    var element = Event.findElement(event, 'div').up('.option-box');
                    if (element) {
                        var idInput = Element.select(element, '[name$="[option_id]"]')[0];
                        if (idInput.value == '') {
                            element.remove();
                        } else {
                            Element.select(element, '[data-state="deleted"]').each(function (elem) {
                                elem.value = '1';
                            });

                            Element.select(element, ['input', 'select']).each(function (elem) {
                                elem.hide();
                                elem.className = '';
                            });

                            Element.hide(element);
                        }
                    }
                },

                changeType : function(event) {
                    let element = Event.element(event),
                        parts = element.id.split('_');
                    i = parts[2];
                    if (element.value == 'multi' || element.value == 'checkbox') {
                        let inputs = $A($$('#staging_bundle_selection_box_' + i + ' tr.selection input.default'));
                        inputs.each(
                            function(elem){
                                //elem.type = "checkbox";
                                changeInputType(elem, 'checkbox');
                            }
                        );

                        /**
                         * Hide not needed elements (user defined qty select box)
                         */
                        inputs = $A($$('#staging_bundle_selection_box_' + i + ' .qty-box'));
                        inputs.each(
                            function(elem){
                                elem.hide();
                            }
                        );

                    } else {
                        let inputs = $A($$('#staging_bundle_selection_box_' + i + ' tr.selection input.default')),
                            have = false, j;
                        for (j=0; j< inputs.length; j++) {
                            //inputs[j].type = "radio";
                            changeInputType(inputs[j], 'radio');
                            if (inputs[j].checked && have) {
                                inputs[j].checked = false;
                            } else {
                                have = true;
                            }
                        }

                        /**
                         * Show user defined select box
                         */
                        inputs = $A($$('#staging_bundle_selection_box_' + i + ' .qty-box'));
                        inputs.each(
                            function(elem){
                                elem.show();
                            }
                        );
                    }
                },

                priceTypeDynamic : function() {
                    let inputs = $A($$('.col-price.price-type-box'));
                    inputs.each(
                        function(elem){
                            elem.hide();
                        }
                    );
                },

                priceTypeFixed : function() {
                    let inputs = $A($$('.col-price.price-type-box'));
                    inputs.each(
                        function(elem){
                            elem.show();
                        }
                    );
                }
            };

            let optionIndex = 0;
            window.bStageOption = new StageBundle.Option(optionTemplate);

            jQuery.each(dataInfo.bundleInfo, function(key, value) {
                optionIndex = bStageOption.add(value.info);
                if (value.selection) {
                    jQuery.each(value.selection, function(subKey, subValue) {
                        bStageSelection.addRow(optionIndex, subValue.info);
                    });
                }
            });
            function toggleStagePriceType() {
                bStageOption['priceType' + (jQuery("#staging_price_type").is(':checked') ? 'Dynamic' : 'Fixed')]();
            }

            jQuery('#staging_bundle_product_container').stageBundleProduct();
            jQuery('#staging_product_bundle_container .collapse').collapse('hide');

            toggleStagePriceType();
            Event.observe('staging_price_type', 'change', toggleStagePriceType);
        }
    };
});

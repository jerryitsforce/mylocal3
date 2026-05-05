/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'Branch8_Catalog/js/alertLowStock',
    'jquery-ui-modules/widget'
], function ($, _, alertLowStock) {
    'use strict';

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {

            /** @inheritdoc */
            _RenderControls: function () {
                var $widget = this;
                this._super()

                // Extract first option of each attribute
                const firstItem = {};
                for (const key in this.options.jsonConfig.attributes) {
                    const attribute = this.options.jsonConfig.attributes[key];
                    if (attribute.options && attribute.options.length > 0) {
                        firstItem[attribute.code] = attribute.options[0].id;
                    }
                }
                //Selected first item by default
                if(_.isEmpty($.parseQuery()))
                    $widget._EmulateSelected(firstItem);
            },

            /** @inheritdoc */
            _OnClick: function ($this, widget) {
                var productVariationsSku = this.options.jsonConfig.sku;
                    

                this._super($this, widget);

                // Update alertLowStock
                alertLowStock({
                    'info': productVariationsSku[widget.getProductId()],
                    'selector': '#alert_low_qty',
                    'url': '/catalog/product/lowStockCheck/',
                    'productType': 'configurable'
                });

                // Update Shipping Methods
                var selectedOptions = '.' + widget.options.classes.attributeClass + '[data-option-selected]',
                    attributeInfo = {};

                widget.element.find(selectedOptions).each(function () {
                    var id = $(this).data('attribute-id'),
                        option = $(this).attr('data-option-selected');
                    attributeInfo[id] = option;
                });

                $.ajax({
                    url: '/b8quickview/product/shippings',
                    type: 'POST',
                    data: {id : this.options.jsonConfig.productId, info : attributeInfo },
                    dataType: 'json',
                    success: function (data) {
                        if(data.error == 0){
                            if(data.data.length){
                                var strMethod = '';
                                $.each(data.data, function(k, methodTxt){
                                    strMethod += '<div class="pdp_product_shipping_method">'+methodTxt+'</div>';
                                });
                                $('.shipping_method_items').html(strMethod).show();
                                $('.pdp_shipping_method').show();
                            }else{
                                $('.pdp_shipping_method').hide();
                            }
                        }
                    }
                });
            }
        });

        return $.mage.SwatchRenderer;
    };
});

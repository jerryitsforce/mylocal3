/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_MarketplacePreorder
 * @author    Webkul
 * @copyright Copyright (c)  Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    "jquery",
    "ko",
    "mage/translate",
    "plugins/DOMPurify",
    "jquery/ui",
    'domReady!'
], function ($, ko, _, DOMPurify) {
    var B8PreorderMixin = {

        _create: function () {
            var self = this;
            var jQ = $.noConflict();
            $(document).ready(function () {
                var url = self.options.url;
                var pay = self.options.payHtml;
                var message = self.options.msg;
                // var flag = self.options.flag;
                var flag = false;
                var productId = self.options.productId;
                self.options.addToCartButtonLabel = $("#product-addtocart-button span").text();
                self.options.stockLabel = $(".product-info-stock-sku .stock").text();
                var payHtml = pay;
                var msg = message;
                msg = msg.replace(/\n/g, "<br />");
                var count = 0;
                var isPreorder = flag;
                if (isPreorder == 1) {
                    self.setPreOrderLabel();
                    jQ(".product-info-main > .product-info-price").after(msg);
                    jQ(".product-info-main > .product-info-price").after(payHtml);
                }
                if (self.options.config == 1) {
                    jQ("#product-options-wrapper").after(self.options.configmsg);
                }
                ;
                $('#product-addtocart-button').click(function () {
                    count = 0;
                });
                var observer = new MutationObserver(function(e) {
                    var title = $('#product-addtocart-button span').text();
                    if (isPreorder == 1) {
                        if (title == self.options.addToCartButtonLabel) {
                            count++;
                            if (count == 1) {
                                self.setPreOrderLabel();
                            }
                        }
                    }
                });
                //observer.observe($('#product-addtocart-button span')[0], {characterData: true, childList: true});
                var targetElement = $('#product-addtocart-button span')[0];
                if (targetElement) {
                    observer.observe(targetElement, { characterData: true, childList: true });
                }
                $('body').on('click', '#product-options-wrapper .swatch-option', function () {
                    var flag = 1;
                    var attributeInfo = {};
                    $(".wk-msg-box").remove();
                    $(".wk-config-msg-box").remove();
                    setTimeout(function () {
                        $('#product-options-wrapper .swatch-attribute').each(function () {
                            if ($(this).attr('option-selected') || $(this).attr('data-option-selected')) {
                                var selectedOption = ($(this).attr("option-selected")) ? $(this).attr('option-selected') : $(this).attr('data-option-selected');
                                var attributeId = ($(this).attr("attribute-id")) ? $(this).attr('attribute-id') : $(this).attr('data-attribute-id');
                                attributeInfo[attributeId] = selectedOption;
                            } else {
                                flag = 0;
                            }
                        });
                        if (flag == 1) {
                            $(".wk-loading-mask").removeClass("wk-display-none");
                            isPreorder = 0;
                            jQ.ajax({
                                url: url,
                                type: 'POST',
                                data: {type: 1, product_id: productId, info: attributeInfo},
                                dataType: 'json',
                                success: function (data) {
                                    if (data.preorder == 1) {
                                        self.setPreOrderLabel();
                                        jQ("#product-options-wrapper").after(self.options.configmsg);
                                        isPreorder = 1;
                                        const sanitizedMsg = DOMPurify.sanitize(data.msg, {USE_PROFILES: {html: true}});
                                        jQ(".product-info-main > .product-info-price").after('<div>'+ sanitizedMsg + '</div>');
                                        const sanitizedHtml = DOMPurify.sanitize(data.payHtml, {USE_PROFILES: {html: true}});
                                        jQ(".product-info-main > .product-info-price").after(sanitizedHtml);
                                    } else {
                                        self.setDefaultLabel();
                                        isPreorder = 0;
                                        $(".wk-config-msg-box").remove();
                                    }
                                    $(".wk-loading-mask").addClass("wk-display-none");
                                }
                            });
                        }
                    }, 0);
                });
                $('#product-options-wrapper .super-attribute-select').change(function () {
                    var flag = 1;
                    setTimeout(function () {
                        $("#product_addtocart_form input[type='hidden']").each(function () {
                            $('#product-options-wrapper .super-attribute-select').each(function () {
                                if ($(this).val() == "") {
                                    flag = 0;
                                }
                            });
                            var name = $(this).attr("name");
                            if (name == "selected_configurable_option") {
                                self.setDefaultLabel();
                                isPreorder = 0;
                                $(".wk-msg-box").remove();
                                var productId = $(this).val();
                                jQ.each(self.options.preorderData, function (i, v) {
                                    if (v.id != 'undefined' && productId == v.id) {
                                        if (v.preorder == 1) {
                                            $(".wk-msg-box").remove();
                                            self.setPreOrderLabel();
                                            isPreorder = 1;
                                            jQ(".product-info-main > .product-info-price").after(DOMPurify.sanitize(v.msg));
                                            jQ(".product-info-main > .product-info-price").after(DOMPurify.sanitize(v.payHtml));
                                        } else {
                                            self.setDefaultLabel();
                                            isPreorder = 0;
                                            $(".wk-msg-box").remove();
                                        }
                                    }
                                });
                            }
                        });
                    }, 0);
                });
                var preorderCheckInt = setInterval(function (){
                    if(typeof magentoStorefrontEvents != "undefined"){
                        var productType = magentoStorefrontEvents.context.getProduct().productType;
                        if (productType != 'configurable') {
                            var isPreorder = null;
                            jQ.ajax({
                                url: url,
                                type: 'POST',
                                data: {product_id: productId},
                                dataType: 'json',
                                success: function (data) {
                                    if (data.preorder == 1) {
                                        self.setPreOrderLabelUpdated(data);
                                        $("#product-options-wrapper").after(self.options.configmsg);
                                        isPreorder = 1;
                                        jQ(".product-info-main > .product-info-price").after(DOMPurify.sanitize(data.msg));
                                        // $(".product-info-main > .product-info-price").after(data.payHtml);
                                        if(data.stock.preorder.mode == 1 /*mode start-end date*/
                                            && data.stock.preorder.preorder_use_qty == 1
                                            && data.stock.preorder.preorder_qty == 0){
                                            $('.box-tocart').addClass('disabled');
                                            if(!$('#product_addtocart_form').find("#product-oos-button").length){
                                                $('#product_addtocart_form .box-tocart .actions').prepend('<button type="button" title=' + $.mage.__('Restocking') + ' class="action primary tocart" id="product-oos-button"><span>' + $.mage.__('Restocking') + '</span></button>');
                                            }
                                        }
                                    } else {
                                        self.setDefaultLabel();
                                        isPreorder = 0;
                                        $(".wk-config-msg-box").remove();
                                        if(!data.stock.is_in_stock){
                                            $('.box-tocart').addClass('disabled');
                                            if(!$('#product_addtocart_form').find("#product-oos-button").length){
                                                $('#product_addtocart_form .box-tocart .actions').prepend('<button type="button" title=' + $.mage.__('Restocking') + ' class="action primary tocart" id="product-oos-button"><span>' + $.mage.__('Restocking') + '</span></button>');
                                            }
                                        }

                                    }
                                    $(".wk-loading-mask").addClass("wk-display-none");
                                }
                            });
                        }
                        clearInterval(preorderCheckInt);
                    }
                }, 200);


            });
        },
        setPreOrderLabel: function(){},
        setPreOrderLabelUpdated: function (data) {
            var self = this;
            var jQ = $.noConflict();
            // Keep addToCartButtonLabel as design
            // $("#product-addtocart-button span").text(self.options.preOrderLabel);
            // $("#product-addtocart-button").attr("title",self.options.preOrderLabel);
            jQ(".product-info-stock-sku .stock").text(self.options.preOrderLabel);
            const sanitizedBadge = DOMPurify.sanitize(data.badge);
            jQ('.product.badges').prepend(sanitizedBadge);
            // $('.product.alert.stock').hide();
        },
        setDefaultLabel: function () {
            var self = this;
            $("#product-addtocart-button span").text(self.options.addToCartButtonLabel);
            $("#product-addtocart-button").attr("title",self.options.addToCartButtonLabel);
            $(".product-info-stock-sku .stock").text(self.options.stockLabel);
            $('.badge.preorder').remove();
            // Hide stock alert
            // $('.product.alert.stock').show();
        }
    }
    return function (targetWidget) {

        $.widget('mage.pageview', targetWidget, B8PreorderMixin);

        return $.mage.pageview;
    };


});

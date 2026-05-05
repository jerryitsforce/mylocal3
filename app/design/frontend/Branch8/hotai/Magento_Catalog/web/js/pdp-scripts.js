/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    "jquery",
    "plugins/DOMPurify",
    "Magento_Catalog/js/price-utils",
    "slideUpSticky",
    "plugins/stickykit",
    "mage/translate",
    "mage/tabs",
    "matchMedia",
    "mage/mage",
    "domReady!",
], function ($, DOMPurify, priceUtils) {
    "use strict";

    if (typeof PDP == "undefined") {
        var PDP = {};
    }

    PDP.Scripts = {
        init: function () {
            this.increaseQty();
            this.triggerTabs();
            this.stickyProductMain();
            this.renderActionsStickyBar();
            this.bottomBar();
            this.setDefaultOption();
        }, 

        increaseQty: function(){
            $(document).on('click', '.fullpoint-pdp-box-tocart .down', function(e){
                $('.box-tocart').find('.down').trigger('click');
                e.preventDefault();
            });

            $(document).on('click', '.fullpoint-pdp-box-tocart .up', function(e){
                $('.box-tocart').find('.up').trigger('click');
                e.preventDefault();
            });

            $('.box-tocart').find('.down').on('click', $.proxy(function(e) {
                var valQty= parseInt($('.box-tocart').find('#qty').val() || 0);
                if(valQty>1)
                    valQty--;
                $('.box-tocart').find('#qty').val(valQty);
                $('.box-tocart').find('#qty').trigger('change');

                if($('.fullpoint-pdp-summary').length && $('.fullpoint-pdp-summary').is(':visible')) {
                    var finalPrice = $('.price-container.price-final_price meta[itemprop="price"]').attr('content');
                    var qty = $('.box-tocart .input-text.qty').val() || 1;
                    var totalPrice = finalPrice * qty;
                    $('#fullpoint_total_price').text(new Intl.NumberFormat('en-US').format(totalPrice));
                    $('.fullpoint-pdp-box-tocart').find('.input-text.qty').val(qty);
                }

            }, this));

            $('.box-tocart').find('.up').on('click', $.proxy(function(e) {

                var valQty= parseInt($('.box-tocart').find('#qty').val() || 0);
                if(valQty<1)
                    valQty = 0;
                valQty++;

                $('.box-tocart').find('#qty').val(valQty);
                $('.box-tocart').find('#qty').trigger('change');

                if($('.fullpoint-pdp-summary').length && $('.fullpoint-pdp-summary').is(':visible')) {
                    var finalPrice = $('.price-container.price-final_price meta[itemprop="price"]').attr('content');
                    var qty = $('.box-tocart .input-text.qty').val() || 1;
                    var totalPrice = finalPrice * qty;
                    $('#fullpoint_total_price').text(new Intl.NumberFormat('en-US').format(totalPrice));
                    $('.fullpoint-pdp-box-tocart').find('.input-text.qty').val(qty);
                }
            }, this));
        },

        triggerTabs: function () {
            var anchor = window.location.hash,
                anchorId = anchor.replace("#",""),
                jQ = $.noConflict();

            jQ('.trigger-tabs-titles .trigger-tabs-title').each(function (index) {
                if(anchor){
                    if(jQ(this).attr('data-trigger') === anchorId){
                        jQ('.trigger-tabs-titles .trigger-tabs-title').removeClass('active');
                        jQ(this).addClass('active');
                    }
                }else{
                    jQ('.trigger-tabs-titles .trigger-tabs-title').eq(0).addClass('active');
                }
                jQ(this).on('click', function (event) {
                    event.preventDefault();
                    const dataTrigger = DOMPurify.sanitize($(this).attr('data-trigger'));
                    jQ('.trigger-tabs-titles .trigger-tabs-title').removeClass('active');
                    jQ('[data-trigger='+dataTrigger+']').addClass('active');
                    if(jQ(this).parents('.sticky').length){
                        jQ('html, body').animate({
                            scrollTop: jQ('.product.info.detailed').offset().top - 122
                        }, 500);
                    }
                    jQ('.product.data.items').tabs('activate', index);
                    if(jQ('#'+dataTrigger).pdpViewMore())
                        if(!jQ('#'+dataTrigger).find(".product-value-inner").hasClass('expanded')){
                            jQ('#'+dataTrigger).pdpViewMore('updateScrollHeight');
                        }
                });
            });
        },

        stickyProductMain: function(){
            if($('body').hasClass('magetop_quickview-catalog_product-view'))
                return;
            mediaCheck({
                media: "(min-width: 769px)",
                entry: $.proxy(function () {
                    $('.product.media').stick_in_parent({
                        parent: '.product-main-wrapper',
                        offset_top: 0
                    });
                }, this),
                exit: $.proxy(function () {
                    $('.product.media').trigger("sticky_kit:detach");
                }, this),
            });
        },

        renderActionsStickyBar: function(){
            if($('body').hasClass('magetop_quickview-catalog_product-view'))
                return;
            mediaCheck({
                media: "(min-width: 769px)",
                entry: $.proxy(function () {
                    if(!$("body").hasClass("page-product-bundle"))
                    {
                        const actionsStickyBar = $('.actions-sticky-bar'),
                            actionsStickyBarInner = $('.actions-sticky-bar .sticky-inner'),
                            productPrice = $('.product-info-main > .product-info-price').clone(true),
                            productTabs = $('.product.trigger-tabs-titles').clone(true).addClass('sticky');
                        if(!actionsStickyBar.find('.product-info-price').length)
                            actionsStickyBarInner.prepend(productPrice);
                        if(!actionsStickyBar.find('.product.trigger-tabs-titles').length)
                            actionsStickyBarInner.prepend(productTabs);

                        actionsStickyBar.slideUpSticky({
                            elementStartSticky: '.product.info.detailed'
                        });
                    }else{
                        
                    }
                    
                }, this),
                exit: $.proxy(function () {
                    // $('.product.trigger-tabs-titles').slideUpSticky({
                    //     elementStartSticky: '.product.info.detailed'
                    // });
                }, this),
            });
        },
        
        bottomBar:function(){
            if($('body').hasClass('magetop_quickview-catalog_product-view'))
                return;
            mediaCheck({
                media: "(max-width: 768px)",
                entry: $.proxy(function () {
                    const bottomButtonCart = $('#bottom-tocart-button'),
                    buttonToCart =  $('#product-addtocart-button'),
                    bottomButtonBuyNow = $('#bottom-buynow-button'),
                    buttonBuyNow =  $('#product-buynow-button'), 
                    productMedia = $('[data-gallery-role="gallery-placeholder"]').clone(true),
                    productName = $('.product-info-title').clone(true),
                    productPrice = $('.product-info-main > .product-info-price').clone(true),
                    productLongShip = $('.product-info-main > .pdp_long_time_ship').clone(true),
                    productMainWrapper = $('<div class="bottom-product-main-wrapper"><div class="bottom-product-info-main"></div><div class="close"></div></div>');

                    // Append product info
                    if(!productMainWrapper.find('[data-gallery-role="gallery-placeholder"]').length)
                        productMainWrapper.prepend(productMedia);
                    if(!productMainWrapper.find('.product-info-title').length)
                        productMainWrapper.find('.bottom-product-info-main').append(productName);
                    if(!productMainWrapper.find('.product-info-price').length)
                        productMainWrapper.find('.bottom-product-info-main').append(productPrice);
                    if(!productMainWrapper.find('.pdp_long_time_ship').length)
                        productMainWrapper.find('.bottom-product-info-main').append(productLongShip);
                    if(!$('.product-add-form').find('.bottom-product-main-wrapper').length)
                        $('.product-add-form .product-options-wrapper').prepend(productMainWrapper);
                    
                    if(!$('.product-add-form').find('.bottom-product-main-wrapper').length)
                        $('.product-add-form > form').prepend(productMainWrapper);
                    if(!buttonBuyNow.length){
                        bottomButtonBuyNow.addClass('hidden');
                    }
                    
                    if($('.product-add-form .product-options-wrapper').length || $('.product-add-form .table-wrapper.grouped').length){
                        // Add to cart button click
                        bottomButtonCart.on('click', function(){
                            // Append preorder message
                            const productPreorderMsg = $('.wk-availability-block').clone(true);
                            if(productPreorderMsg.length && !$('.bottom-product-main-wrapper').find('.wk-availability-block').length){
                                $('.bottom-product-main-wrapper .product-info-price').after(productPreorderMsg);
                            }
                            if(buttonToCart.find('span').length){
                                buttonToCart.find('span').text($('.box-tocart').hasClass('disabled') ? $.mage.__("Restocking") : $.mage.__("Sure"));
                            } else {
                                buttonToCart.text($('.box-tocart').hasClass('disabled') ? $.mage.__("Restocking") : $.mage.__("Sure"));
                            }
                            buttonToCart.attr('data-mobile', 'true');
                            $('.product-add-form').addClass('__show __tocart-form');
                            $('body').addClass('options-form__show');
                        });

                        // Buy now button click
                        bottomButtonBuyNow.on('click', function(){
                            var productBuyNowBtn = $("#product-buynow-button");
                            var isFullPointProduct = productBuyNowBtn.attr('data-full-point') || false;
                            if(isFullPointProduct){
                                // Handle full point product logic here
                                productBuyNowBtn.trigger('click');
                            } else {
                                // Append preorder message
                                const productPreorderMsg = $('.wk-availability-block').clone(true);
                                if(productPreorderMsg.length && !$('.bottom-product-main-wrapper').find('.wk-availability-block').length){
                                    $('.bottom-product-main-wrapper .product-info-price').after(productPreorderMsg);
                                }

                                buttonBuyNow.find('span').text($.mage.__("Sure"));
                                $('.product-add-form').addClass('__show __buynow-form');
                                $('body').addClass('options-form__show');
                            }
                            
                        });

                        if($('.product-add-form .box-tocart').hasClass('disabled')){
                            $('.product-info-main > .pdp_long_time_ship').show();
                            $('.product-info-main > .wk-availability-block').show();
                        }
                    } else{
                        $('.product-info-main > .pdp_long_time_ship').show();
                        $('.product-info-main > .wk-availability-block').show();
                        // Add to cart button click
                        bottomButtonCart.on('click', function(){
                            buttonToCart.trigger('click');
                        });

                        // Buy now button click
                        bottomButtonBuyNow.on('click', function(){
                            buttonBuyNow.trigger('click');
                        });
                    }

                    // Close popup
                    $(document).on('click', '.bottom-product-main-wrapper .close', function(){
                        $('.product-add-form').removeClass('__show __tocart-form __buynow-form');
                        $('body').removeClass('options-form__show');
                    });
                }, this),
                exit: $.proxy(function () {
                    $('.product-add-form').removeClass('__show __tocart-form __buynow-form');
                    $('body').removeClass('options-form__show');
                    const buttonToCart = $('#product-addtocart-button');
                    if(buttonToCart.attr('data-mobile') == 'true'){
                        if(buttonToCart.find('span').length){
                            buttonToCart.find('span').text(buttonToCart.attr('title'));
                        } else {
                            buttonToCart.text(buttonToCart.attr('title'));
                        }
                    }
                }, this),
            });
        },
        setDefaultOption: function(){
            const options = $('.product-options-wrapper .options-list');
            if(options.length > 0){
                options.each(function(){
                    $(this).children().first().find('.product-custom-option').trigger('click');
                });
            }
        }
    }
    PDP.Scripts.init();
});

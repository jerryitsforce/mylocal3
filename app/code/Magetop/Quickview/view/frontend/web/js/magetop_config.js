/**
 * Magetop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magetop.com license that is
 * available through the world-wide-web at this URL:
 * https://www.magetop.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magetop
 * @package     Magetop_Quickview
 * @copyright   Copyright (c) Magetop (https://www.magetop.com/)
 * @license     https://www.magetop.com/LICENSE.txt
 */
define(
    [
        'jquery',
        'mage/mage',
        'Magetop_Quickview/js/jquery.magnific-popup.min'
    ],
    function ($) {
        "use strict";
        $.widget(
            'magetop.magetop_config',
            {
                options: {
                    productUrl: '',
                    buttonText: '',
                    isEnabled: false,
                    baseUrl: '',
                    productImageWrapper: '',
                    productItemInfo: ''
                },

                _create: function () {
                    let $widget = this;
                    this.renderButton();
                    this._EventListener();
                    $(document).ajaxComplete(function() {
                        $widget.renderButton();
                    });
                },

                renderButton: function () {
                    var $widget = this,
                        id_product,
                        productImageWrapper = '.' + this.options.productImageWrapper,
                        productItemInfo = '.' + this.options.productItemInfo;
                    var jQ = $.noConflict();
                    if ($widget.options.isEnabled === 1) {
                        jQ(productImageWrapper).each(
                            function () {
                                if (jQ(this).parents(productItemInfo).find('.magetop-bt-quickview').length === 0) {
                                    if (jQ(this).parents(productItemInfo).find('.actions-primary input[name="product"]').val() !== '') {
                                        id_product = DOMPurify.sanitize(jQ(this).parents(productItemInfo).find('.actions-primary input[name="product"]').val()?.toString());
                                    }
                                    if (!id_product) {
                                        id_product = DOMPurify.sanitize(jQ(this).parents(productItemInfo).find('.price-box').data('product-id')?.toString());
                                    }
                                    if (id_product) {
                                        jQ(this).append('<div id="quickview-' + id_product + '" class="magetop-bt-quickview"><a class="magetop-quickview" data-quickview-url="' + $widget.options.productUrl + 'id/' + id_product + '" rel="nofollow" href="javascript:void(0);" ><span>' + $widget.options.buttonText + '</span></a></div>');
                                    }
                                }
                            }
                        )
                    }
                },

                _EventListener: function () {
                    var $widget = this;
                    if ($widget.options.isEnabled === 1) {

                        $('a.mailto').click(
                            function (e) {
                                e.preventDefault();
                                window.top.location.href = $(this).attr('href');
                                return true;
                            }
                        );

                        $('body, #layer-product-list').on(
                            'contentUpdated',
                            function () {
                                $('.magetop-bt-quickview').remove();
                                $widget.renderButton();
                            }
                        );

                        $(document).on(
                            'click',
                            '.magetop-quickview',
                            function () {
                                var prodUrl = $(this).attr('data-quickview-url');
                                if (prodUrl.length) {
                                    $widget.openPopup(prodUrl);
                                }
                            }
                        );
                    }
                },

                openPopup: function (prodUrl) {
                    var $widget = this,
                        url = $widget.options.baseUrl + 'magetop_quickview/index/updatecart';

                    if (!prodUrl.length) {
                        return false;
                    }

                    $.magnificPopup.open(
                        {
                            items: {
                                src: prodUrl
                            },
                            type: 'iframe',
                            closeOnBgClick: false,
                            scrolling: false,
                            preloader: true,
                            enableEscapeKey: true,
                            tLoading: '',
                            callbacks: {
                                open: function () {
                                    $('.mfp-preloader').css('display', 'block');
                                    $("iframe.mfp-iframe").contents().find("html").addClass("magetop_loader");
                                },
                                beforeClose: function () {
                                    $('[data-block="minicart"]').trigger('contentLoading');
                                    $.ajax(
                                        {
                                            url: url,
                                            method: "POST"
                                        }
                                    );
                                },
                                close: function () {
                                    $('.mfp-preloader').css('display', 'none');
                                }
                            }
                        }
                    );
                }
            }
        );
        return $.magetop.magetop_config;
    }
);

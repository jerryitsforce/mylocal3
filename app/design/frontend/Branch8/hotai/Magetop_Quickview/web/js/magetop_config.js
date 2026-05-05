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
        'Magento_Catalog/js/product/view/product-ids-resolver',
        'Branch8_GA4/js/actions/ga4push',
        'plugins/DOMPurify',
        'mage/mage',
        'Magetop_Quickview/js/jquery.magnific-popup.min',
    ],
    function ($, idsResolver, ga4push, DOMPurify) {
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
                    $widget.renderButton();
                    $widget._EventListener();
                    $(document).ajaxComplete(function() {
                        $widget.renderButton();
                    });

                    const anchor = DOMPurify.sanitize(window.location.hash);
                    if(anchor?.indexOf('quickview_') != -1){
                        const productId = anchor.replace('#quickview_', ''),
                            productQuickviewUrl =  $widget.options.productUrl + 'id/' + productId + '?quickview=true';
                        $widget.openPopup(productQuickviewUrl);
                    }

                    window.quickViewData = {
                        enabled: this.options.isEnabled,
                        productUrl: this.options.productUrl,
                        productItemInfo: this.options.productItemInfo,
                        productImageWrapper: this.options.productImageWrapper,
                        buttonText: this.options.buttonText
                    }
                },

                renderButton: function () {
                    let $widget = this,
                        id_product,
                        productImageWrapper = '.' + this.options.productImageWrapper,
                        productItemInfo = '.' + this.options.productItemInfo;

                    var jQ = $.noConflict();
                    if ($widget.options.isEnabled === "1") {
                        jQ(productImageWrapper).each(
                            function () {
                                if (jQ(this).parents(productItemInfo).find('.magetop-bt-quickview').length === 0) {
                                    if (jQ(this).parents(productItemInfo).find('.actions-primary input[name="product"]').val() !== '') {
                                        id_product = DOMPurify.sanitize(jQ(this).parents(productItemInfo).find('.actions-primary input[name="product"]').val()?.toString());
                                    }
                                    if (!id_product) {
                                        id_product = DOMPurify.sanitize(jQ(this).parents(productItemInfo).find('.price-box').data('product-id')?.toString());
                                    }
                                    if ($('body').hasClass('page-layout-category-official-page')) {
                                        if (id_product) {
                                            jQ(this).parents('.product-item-info').find('.product-item-photo').prepend('<div id="quickview-' + id_product + '" class="magetop-bt-quickview"><a class="magetop-quickview" data-quickview-url="' + $widget.options.productUrl + 'id/' + id_product + '" rel="nofollow" href="javascript:void(0);" ><span>' + $widget.options.buttonText + '</span></a></div>');
                                        }
                                    } else {
                                        if (id_product) {
                                            jQ(this).prepend('<div id="quickview-' + id_product + '" class="magetop-bt-quickview"><a class="magetop-quickview" data-quickview-url="' + $widget.options.productUrl + 'id/' + id_product + '" rel="nofollow" href="javascript:void(0);" ><span>' + $widget.options.buttonText + '</span></a></div>');
                                        }
                                    }
                                }
                            }
                        )
                    }
                },

                _EventListener: function () {
                    var $widget = this;
                    if ($widget.options.isEnabled === "1") {

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
                            function (event) {
                                let prodUrl = $(this).attr('data-quickview-url');
                                const $self = $(this);
                                var jQ = $.noConflict();


                                const $productItem = $self.parents('.product-item').first();
                                let ecommerce = {
                                    promotion_id: $productItem.data('promotion-id') || undefined,
                                    promotion_name: $productItem.data('promotion-name')  || undefined,
                                    item_list_id: $productItem.data('item-list-id') || undefined,
                                    item_list_name: $productItem.data('item-list-name') || undefined,
                                };

                                ecommerce = JSON.parse(JSON.stringify(ecommerce)); //remove undefined key
                                ecommerce.items = [];
                                var item = {};

                                $productItem.find('.ga4-item-json').each(function() {
                                    try {
                                        item = JSON.parse($(this).val());
                                    } catch (error) {
                                        console.error('Invalid JSON', $(this).val());
                                        return;
                                    }

                                    item = {
                                        ...item,
                                        promotion_id: $productItem.data('promotion-id') || undefined,
                                        promotion_name: $productItem.data('promotion-name') || undefined,
                                        item_list_id: $productItem.data('item-list-id') || undefined,
                                        item_list_name: $productItem.data('item-list-name') || undefined,
                                    }

                                    Object.keys(item).forEach(key => {
                                        if (item[key] === undefined || item[key] === null || item[key] === '') {
                                            delete item[key];
                                        }
                                    });

                                    ecommerce.items.push({
                                        ...item,
                                        item_id: parseInt(item.item_id),
                                    });
                                });


                                const eventData = {
                                    event: 'select_item_cart',
                                    type: "商品卡_購物車_icon",
                                    ecommerce
                                }

                                ga4push([eventData]);

                                const appendGa4QueryParams = (url) => {
                                    try {
                                        var _url = new URL(url);
                                        //add query params to _url
                                        _url.searchParams.append('item_list_id', ecommerce.item_list_id);
                                        _url.searchParams.append('item_list_name', ecommerce.item_list_name);
                                        _url.searchParams.append('promotion_id', ecommerce.promotion_id);
                                        _url.searchParams.append('promotion_name', ecommerce.promotion_name);
                                        _url.searchParams.append('quickview', 'true');
                                        _url.searchParams.append('referer', 'card');
                                        _url.searchParams.append('position', parseInt(item.index));
                                        return _url.toString();
                                    } catch (_) {
                                        //check if url already has query params
                                        const queryParamsString =
                                            'item_list_id=' + ecommerce.item_list_id
                                            + '&item_list_name=' + ecommerce.item_list_name
                                            + '&promotion_id=' + ecommerce.promotion_id
                                            + '&promotion_name=' + ecommerce.promotion_name
                                            + '&quickview=true'
                                            + '&referer=card'
                                            + '&position=' + parseInt(item.index);
                                        url +=  (url.includes('?') ? '&' : '?') + queryParamsString;
                                        return url;
                                    }
                                }



                                if (prodUrl.length) {
                                    let form = jQ(this).parents('.product-item-info').find('form'),
                                        productIds = idsResolver(form),
                                        formData,
                                        actionUrl = form.attr('action');

                                    if (form.length && !actionUrl.includes('options=cart')) {
                                        formData = new FormData(form[0]);
                                        $(event.target).addClass('loading');
                                        formData.append('skipMessage', 1);
                                        formData.append('position', parseInt(item.index));

                                        if (!formData.get('form_key')) {
                                            formData.append('form_key', $.mage.cookies.get('form_key'));
                                        }

                                        $('[data-block="minicart"]').trigger('contentLoading');

                                        jQ.ajax({
                                            url: actionUrl,
                                            data: formData,
                                            type: 'post',
                                            dataType: 'json',
                                            cache: false,
                                            contentType: false,
                                            processData: false,

                                            /** @inheritdoc */
                                            beforeSend: function () {
                                                $('.mfp-preloader').css('display', 'block');
                                            },

                                            /** @inheritdoc */
                                            success: function (res) {
                                                var eventData, parameters;
                                                $(document).trigger('ajax:addToCart', {
                                                    'sku': form.data().productSku,
                                                    'productIds': productIds,
                                                    'form': form,
                                                    'response': res
                                                });
                                                $('.mfp-preloader').css('display', 'none');

                                                if (res.backUrl) {
                                                    let parser = document.createElement('a');
                                                    parser.href = res.backUrl;
                                                    if (parser.hostname === window.location.hostname) {
                                                        if (parser.pathname.includes('/checkout/')) {
                                                            $widget._redirect(res.backUrl);
                                                            return;
                                                        }
                                                    } else {
                                                        $widget._redirect(res.backUrl);
                                                        return;
                                                    }

                                                    //add to cart form
                                                    const queryParams = $self.parents('.product-item-inner').find('.product-item-actions').find('[data-role="tocart-form"]').first().serialize()
                                                    console.log('queryParams', queryParams);
                                                    $widget.openPopup(
                                                        appendGa4QueryParams(prodUrl + '?' + queryParams),
                                                        parseInt(item.index)
                                                    );
                                                    return;
                                                }

                                                if (res.minicart) {
                                                    const sanitizedMinicart = DOMPurify.sanitize(res.minicart);
                                                    jQ('[data-block="minicart"]').replaceWith(sanitizedMinicart);
                                                    jQ('[data-block="minicart"]').trigger('contentUpdated');
                                                }

                                                $(event.target).removeClass('loading');
                                            },

                                            /** @inheritdoc */
                                            error: function (res) {

                                                $(document).trigger('ajax:addToCart:error', {
                                                    'sku': form.data().productSku,
                                                    'productIds': productIds,
                                                    'form': form,
                                                    'response': res
                                                });
                                                $widget.openPopup(
                                                    appendGa4QueryParams(prodUrl),
                                                    parseInt(item.index)
                                                );
                                            },

                                            /** @inheritdoc */
                                            complete: function (res) {
                                                $(event.target).removeClass('loading');
                                                if (res.state() === 'rejected') {
                                                    location.reload();
                                                }
                                            }
                                        });
                                    } else {
                                        $widget.openPopup(
                                            appendGa4QueryParams(prodUrl),
                                            parseInt(item.index)
                                        );
                                    }
                                }
                            }
                        );
                    }
                },

                _redirect: function (url) {
                    var urlParts, locationParts, forceReload;

                    urlParts = url.split('#');
                    locationParts = window.top.location.href.split('#');
                    forceReload = urlParts[0] === locationParts[0];

                    window.top.location.assign(url);

                    if (forceReload) {
                        window.top.location.reload();
                    }
                },

                openPopup: function (prodUrl, index = 0) {
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
                            tLoading: '',
                            fixedContentPos: true,
                            mainClass: 'mfp-quickview',
                            callbacks: {
                                open: function () {
                                    $('.mfp-preloader').css('display', 'block');
                                    $("iframe.mfp-iframe").contents().find("html").addClass("magetop_loader");
                                    console.log('open');
                                    $("iframe.mfp-iframe").on('load', function() {
                                        $("iframe.mfp-iframe").contents().find("form").append('<input type="hidden" name="from_popup" value="1">');
                                        $("iframe.mfp-iframe").contents().find("form").append(`<input type="hidden" name="position" value="${index}">`);
                                    });
                                },
                                beforeClose: function () {
                                    $.ajax(
                                        {
                                            url: url,
                                            method: "POST"
                                        }
                                    );
                                },
                                close: function () {
                                    $('.mfp-preloader').css('display', 'none');
                                    $('[data-block="minicart"]').trigger('contentUpdated');
                                },
                                ajaxContentAdded: function(){
                                    console.log('ajaxContentAdded');
                                }
                            }
                        }
                    );

                    $('iframe.mfp-iframe')[0].addEventListener('load', () => {
                        $('.mfp-iframe-scaler .mfp-close').show()
                    });
                }


            }
        );
        return $.magetop.magetop_config;
    }
);

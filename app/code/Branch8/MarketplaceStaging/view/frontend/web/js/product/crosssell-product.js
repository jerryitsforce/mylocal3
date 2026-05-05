/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
/*jshint jquery:true*/
define([
    "jquery",
    'mage/translate',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'plugins/DOMPurify',
    "jquery/ui"
], function ($, $t, mageTemplate, alert, DOMPurify) {
    'use strict';
    $.widget('mage.stageCrosssellProduct', {
        options: {
            backUrl: ''
        },
        _create: function () {
            var self = this;
            var indexValue = 0;
            var crosssellProductData = JSON.parse(self.options.crosssellProducts);
            var jQ = $.noConflict();
            if ($.isArray(crosssellProductData)) {
                $(document).ajaxComplete(function ( event, request, settings ) {
                    var currentAjaxUrl = settings.url;
                    if (currentAjaxUrl.indexOf("marketplacestaging_crosssell_product_listing") > 0) {
                        var responseData = JSON.parse(request.responseText);
                        if(responseData.totalRecords>0) {
                            setTimeout(function () {
                                if ($('#staging-crosssell-product-block-wrapper .data-row').length) {
                                    $.each(crosssellProductData, function (index, value) {
                                        let indexId = value;
                                        $("#stageCrosssellIdscheck"+indexId).trigger("click");
                                        crosssellProductData = $.grep(crosssellProductData, function (arrValue) {
                                            return indexId !== arrValue;
                                        });
                                    });
                                    $("#staging-crosssell-product-block-loader").hide();
                                    $("#staging-crosssell-product-block-wrapper").show();
                                } else {
                                    setTimeout(function () {
                                        if ($('#staging-crosssell-product-block-wrapper .data-row').length) {
                                            $.each(crosssellProductData, function (index, value) {
                                                $("#stageCrosssellIdscheck"+value).trigger("click");
                                            });
                                            $("#staging-crosssell-product-block-loader").hide();
                                            $("#staging-crosssell-product-block-wrapper").show();
                                        } else {
                                            $("#staging-crosssell-product-block-loader").hide();
                                            $("#staging-crosssell-product-block-wrapper").show();
                                        }
                                    }, 2000);
                                }
                            }, 2000);
                        } else {
                            $("#staging-crosssell-product-block-loader").hide();
                            $("#staging-crosssell-product-block-wrapper").show();
                        }
                    } else {
                        $("#staging-crosssell-product-block-loader").hide();
                        $("#staging-crosssell-product-block-wrapper").show();
                    }
                });
            }
            jQ(this.element).delegate(self.options.gridCheckbox, 'change', function () {
                var productId = DOMPurify.sanitize(jQ(this).val()?.toString());
                var parentDivId = DOMPurify.sanitize(jQ(this).parents('div.admin__data-grid-wrap').parents('div').parents('div').attr('id')?.toString());
                if (parentDivId == 'staging-crosssell-product-block-wrapper') {
                    if (jQ(this).is(":checked")) {
                        if (productId == 'on') {
                            jQ('#staging-crosssell-product-block-wrapper .data-row').each(function () {
                                var trElement = jQ(this);
                                var progressTmpl = mageTemplate(self.options.templateId),
                                  tmpl;
                                tmpl = progressTmpl({
                                    data: {
                                        index: indexValue,
                                        id: DOMPurify.sanitize(trElement.find('.wk-mp-grid-id-cell').find('div').text()),
                                        name: DOMPurify.sanitize(trElement.find('.wk-mp-grid-name-cell').find('div').text()),
                                        status: DOMPurify.sanitize(trElement.find('.wk-mp-grid-status-cell').find('div').text()),
                                        attribute_set: DOMPurify.sanitize(trElement.find('.wk-mp-grid-attributeset-cell').find('div').text()),
                                        sku: DOMPurify.sanitize(trElement.find('.wk-mp-grid-sku-cell').find('div').text()),
                                        price: DOMPurify.sanitize(trElement.find('.wk-mp-grid-price-cell').find('div').text()),
                                        thumbnail: DOMPurify.sanitize(trElement.find('.data-grid-thumbnail-cell').find('img').attr('src')),
                                        position: indexValue+1,
                                        record_id: DOMPurify.sanitize(trElement.find('.wk-mp-grid-id-cell').find('div').text())
                                    }
                                });
                                indexValue++;
                                jQ(self.options.crosssellProductId).after(DOMPurify.sanitize(tmpl));
                            });
                        } else {
                            var trElement = jQ(this).parents('tr');
                            var progressTmpl = mageTemplate(self.options.templateId),
                              tmpl;
                            tmpl = progressTmpl({
                                data: {
                                    index: indexValue,
                                    id: DOMPurify.sanitize(trElement.find('.wk-mp-grid-id-cell').find('div').text()),
                                    name: DOMPurify.sanitize(trElement.find('.wk-mp-grid-name-cell').find('div').text()),
                                    status: DOMPurify.sanitize(trElement.find('.wk-mp-grid-status-cell').find('div').text()),
                                    attribute_set: DOMPurify.sanitize(trElement.find('.wk-mp-grid-attributeset-cell').find('div').text()),
                                    sku: DOMPurify.sanitize(trElement.find('.wk-mp-grid-sku-cell').find('div').text()),
                                    price: DOMPurify.sanitize(trElement.find('.wk-mp-grid-price-cell').find('div').text()),
                                    thumbnail: DOMPurify.sanitize(trElement.find('.data-grid-thumbnail-cell').find('img').attr('src')),
                                    position: indexValue+1,
                                    record_id: DOMPurify.sanitize(trElement.find('.wk-mp-grid-id-cell').find('div').text())
                                }
                            });
                            indexValue++;
                            jQ(self.options.crosssellProductId).after(DOMPurify.sanitize(tmpl));
                        }
                    } else {
                        jQ('#staging-crosssell-product-record'+productId).remove();
                    }
                }
            });
        }
    });
    return $.mage.stageCrosssellProduct;
});

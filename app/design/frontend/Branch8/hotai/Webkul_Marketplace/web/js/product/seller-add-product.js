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
    'Magento_Ui/js/modal/alert',
    'plugins/DOMPurify',
    'Webkul_Marketplace/js/product/seller-abstract-action-product',
    "jquery/ui",
    'mage/calendar'
], function ($, $t, alert, DOMPurify, sellerAbstractAction) {
    'use strict';

    $.widget('mage.sellerAddProduct',$.mage.sellerAbstractAction, {
        options: {
            errorMessageSku: $t("SKU can\'t be left empty"),
            ajaxErrorMessage: $t('There was error during fetching results.'),
            ajaxValidateError:$t('Something went wrong when validate product'),
            validateUrl: null,
            productid: 0,
            formElement: "#edit-product"
        },
        /**
         *
         * @private
         */
        _create: function () {
            this._super();
            var self = this;
            var jQ = $.noConflict();
            $('#wk-mp-save-duplicate-btn').click(function () {
                $("#edit-product").append('<input type="hidden" name="back" value="duplicate">');
                $('#save-btn').trigger('click');
            });
            $("#save-btn").on( "click", function() {
                let haveOptionChanged = false,
                    variationCount = 0,
                    dataRealCount = 0;
                let hasCustomOptions = false;
                if ($('#product_options_container').length > 0) {
                    $('#product_options_container').find('.fieldset-wrapper, .field-option').each(function() {
                        let isDeleted = $(this).find('input[name$="[is_delete]"]').val();
                        if ($(this).is(':visible') && isDeleted !== '1' && isDeleted !== 'true') {
                            hasCustomOptions = true;
                            return false; 
                        }
                    });
                }
                
                if (hasCustomOptions && $("#product_type_id").val() !== 'virtual') {
                    if ($('body').find('[name="product[wk_manage_variation]"]').length) {
                        let variationData = $('body').find('[name="product[wk_manage_variation]"]').val();
                        variationData = variationData.match(/(?<=wkvariation\[).+?(?=\]\[)/g);
                        variationData = [...new Set(variationData)];
                        if (variationData.length) {
                            variationCount = variationData.length;
                        }
                    }
                    if (!variationCount) variationCount = 0;
                    $("#product_options_container .fieldset-wrapper").each(function () {
                        let dataRealCountSub = 0;
                        if ($(this).is(':visible')) {
                            if ($(this).find('tr.determined-location').length) {
                                $(this).find('tr.determined-location').each(function () {
                                    if ($(this).is(':visible')) {
                                        dataRealCountSub++;
                                    }
                                });
                            }
                        }
                        if (!dataRealCount) {
                            dataRealCount = 1;
                        }
                        dataRealCount = dataRealCount * dataRealCountSub;
                    });
                    if (dataRealCount !== variationCount) {
                        haveOptionChanged = true;
                    }
                    if (haveOptionChanged) {
                        alert({content: $t('Please ensure that all the required fields have been filled in: Cost, Stock, SKU, Price and Commission Rate.')});
                        return false;
                    }
                }
                self.formElement.submit();
            });
            $('.input-text').change(function () {
                var validt = $(this).val();
                var regex = /(<([^>]+)>)/ig;
                var mainvald = validt .replace(regex, "");
                $(this).val(mainvald);
            });
            $('input#sku').change(function () {
                var len=$('input#sku').val();
                var len2=len.length;
                if (len2 === 0) {
                    alert({
                        content: self.options.errorMessageSku
                    });
                    $('div#skuavail').css('display','none');
                    $('div#skunotavail').css('display','none');
                } else {
                    self.callVerifySkuAjaxFunction();
                }
            });
            jQ('body').on('change','.wk-elements',function () {
                var category_id=DOMPurify.sanitize($(this).val());
                if (this.checked === true) {
                    var $obj = jQ('<input></input>').attr('type','hidden').attr('name','product[category_ids][]').attr('id','wk-cat-hide'+category_id).attr('value',category_id);
                    jQ('.wk-for-validation').append(DOMPurify.sanitize($obj));
                } else {
                    jQ('#wk-cat-hide'+category_id).remove();
                }
            });
            $("#wk-bodymain").delegate('.wk-plus ,.wk-plusend,.wk-minus, .wk-minusend ',"click",function () {
                var thisthis=jQ(this);
                if (thisthis.hasClass("wk-plus") || thisthis.hasClass("wk-plusend")) {
                    if (thisthis.hasClass("wk-plus")) {
                        thisthis.removeClass('wk-plus').addClass('wk-plus_click');
                    }
                    if (thisthis.hasClass("wk-plusend")) {
                        thisthis.removeClass('wk-plusend').addClass('wk-plusend_click');
                    }
                    thisthis.prepend("<span class='wk-node-loader'></span>");
                    self.callCategoryTreeAjaxFunction(thisthis);
                }
                if (thisthis.hasClass("wk-minus") || thisthis.hasClass("wk-minusend")) {
                    self.callRemoveCategoryNodeFunction(thisthis);
                }
            });
        },
        callVerifySkuAjaxFunction: function () {
            var self = this;
            $.ajax({
                url: self.options.verifySkuAjaxUrl,
                type: "POST",
                data: {sku:$('input#sku').val(), product_id:self.options.productid},
                dataType: 'html',
                success:function ($data) {
                    $data=JSON.parse($data);
                    if ($data.avialability==1) {
                        $('div#skuavail').css('display','block');
                        $('div#skunotavail').css('display','none');
                    } else {
                        $('div#skunotavail').css('display','block');
                        $('div#skuavail').css('display','none');
                        $("input#sku").attr('value','');
                    }
                },
                error: function (response) {
                    alert({
                        content: self.options.ajaxErrorMessage
                    });
                }
            });
        },
        callCategoryTreeAjaxFunction: function (thisthis) {
            var self = this;
            var i, len, name, id;
            var jQ = $.noConflict();
            jQ.ajax({
                url     :   self.options.categoryTreeAjaxUrl,
                type    :   "POST",
                data    :   {
                    parentCategoryId:thisthis.siblings("input").val()
                },
                dataType:   "html",
                success :   function (content) {
                    var newdata=  $.parseJSON(content);
                    len = newdata.length;
                    var pxl= parseInt(thisthis.parent(".wk-cat-container").css("margin-left").replace("px",""))+20;
                    thisthis.find(".wk-node-loader").remove();
                    if (thisthis.attr("class") == "wk-plus") {
                        thisthis.attr("class","wk-minus");
                    }
                    if (thisthis.attr("class") == "wk-plusend") {
                        thisthis.attr("class","wk-minusend");
                    }
                    if (thisthis.attr("class") == "wk-plus_click") {
                        thisthis.attr("class","wk-minus");
                    }
                    if (thisthis.attr("class") == "wk-plusend_click") {
                        thisthis.attr("class","wk-minusend");
                    }
                    for (i=0; i<len; i++) {
                        id=DOMPurify.sanitize(newdata[i].id?.toString());
                        name=DOMPurify.sanitize(newdata[i].name);
                        if ($('#wk-cat-hide'+id).length) {
                            if (newdata[i].counting === 0) {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-no"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" checked name="product[category_ids][]" value='+ id +'></div>');
                            } else {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-plusend"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" checked name="product[category_ids][]" value='+ id +'></div>');
                            }
                        } else {
                            if (newdata[i].counting === 0) {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-no"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" name="product[category_ids][]" value='+ id +'></div>');
                            } else {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-plusend"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" name="product[category_ids][]" value='+ id +'></div>');
                            }
                        }
                    }
                    thisthis.parent(".wk-cat-container").nextAll().slideDown(300);
                },
                error: function (response) {
                    alert({
                        content: self.options.ajaxErrorMessage
                    });
                }
            });
        },
        callRemoveCategoryNodeFunction: function (thisthis) {
            if (thisthis.attr("class") == "wk-minus") {
                thisthis.attr("class","wk-plus");
            }
            if (thisthis.attr("class") == "wk-minusend") {
                thisthis.attr("class","wk-plusend");
            }
            var thiscategory = thisthis.parent(".wk-cat-container");
            var marg= parseInt(thiscategory.css("margin-left").replace("px",""));
            while (thiscategory.next().hasClass("wk-removable")) {
                if (parseInt(thiscategory.next().css("margin-left").replace("px",""))>marg) {
                    thiscategory.next().slideUp("slow",function () {
                        $(this).remove();
                    });
                }
                thiscategory = thiscategory.next();
                if (typeof thiscategory.next().css("margin-left")!= "undefined") {
                    if (marg == thiscategory.next().css("margin-left").replace("px","")) {
                        break;
                    }
                }
            }
        }
    });
    return $.mage.sellerAddProduct;
});

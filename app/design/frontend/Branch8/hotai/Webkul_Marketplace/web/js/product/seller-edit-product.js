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
    $.widget('mage.sellerEditProduct', $.mage.sellerAbstractAction, {
        formElement: null,
        bodyClicked: false,
        originProductData: {},
        startToCheckChangesOnJs: false,
        options: {
            errorMessageSku: $t("SKU can\'t be left empty"),
            ajaxErrorMessage: $t('There was error during fetching results.'),
            ajaxValidateError:$t('Something went wrong when validate product'),
            validateUrl: null,
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
            var $form = jQ('[data-form=edit-product]');
            $('#wk-mp-save-duplicate-btn').click(function () {
                $("#edit-product").append('<input type="hidden" name="back" value="duplicate">');
                $('#save-btn').trigger('click');
            });
            $("#save-btn").on("click", function(e) {
                e.preventDefault();
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
                    } else {
                        if (!_.isEmpty(window.variationSavedData)) {
                            variationCount = window.variationSavedData.length;
                        }
                    }
                    if (!variationCount) variationCount = 0;
                    $("#product_options_container .fieldset-wrapper").each(function () {
                        if ($(this).is(':hidden') && $(this).find('input[name^="product[options]"][name$="[option_id]"]').length) {
                            if ($(this).find('input[name^="product[options]"][name$="[option_id]"]') != 0
                                && $(this).find('input[name^="product[options]"][name$="[option_id]"]').val() != "0"
                            ) {
                                if (!variationCount) {
                                    haveOptionChanged = true;
                                    return false; // breaks
                                }
                            }
                        } else {
                            let dataRealCountSub = 0;
                            if ($(this).find('tr.determined-location').length) {
                                $(this).find('tr.determined-location').each(function () {
                                    if (!variationCount) {
                                        if ($(this).is(':hidden')) {
                                            if ($(this).find('input[name^="product[options]"][name*="[values]"][name$="[option_type_id]"]').length
                                                && $(this).find('input[name^="product[options]"][name*="[values]"][name$="[option_type_id]"]').val() != 0
                                                && $(this).find('input[name^="product[options]"][name*="[values]"][name$="[option_type_id]"]').val() != "0") {
                                                haveOptionChanged = true;
                                                return false; // breaks
                                            }
                                        } else {
                                            if (!$(this).find('input[name^="product[options]"][name*="[values]"][name$="[option_type_id]"]').length) {
                                                haveOptionChanged = true;
                                                return false; // breaks
                                            }
                                        }
                                    }
                                    if ($(this).is(':visible')) {
                                        dataRealCountSub++;
                                    }
                                });
                                if (haveOptionChanged) {
                                    return false; // breaks
                                }
                            }
                            if (!dataRealCount) {
                                dataRealCount = 1;
                            }
                            dataRealCount = dataRealCount * dataRealCountSub;
                        }
                    });
                    if (dataRealCount !== variationCount) {
                        haveOptionChanged = true;
                    }
                }
                if (haveOptionChanged) {
                    alert({content: $t('Please ensure that all the required fields have been filled in: Cost, Stock, SKU, Price and Commission Rate.')});
                    return false;
                }
                if ($form.find('input[name="status"]').length
                    && self.options.productStatus !== 1
                    && self.options.productStatus !== "1"
                    && $form.find('input[name="product[salable_qty]"]').length
                    && !$form.find('input[name="product[salable_qty]"]').is(':disabled')) {
                    if ($form.find('input[name="product[salable_qty]"]').val() > 0
                        && $form.find('input[name="status"]:checked').val() !== 1
                        && $form.find('input[name="status"]:checked').val() !== "1") {
                        alert({
                            content: $t("When the product is in 'unlisted' status, the [salable QTY] cannot be changed. If adjustments are needed, please change the product to 'listed' and set the status to 'enabled'."),
                            buttons: [{
                                text: $.mage.__('Confirm and Submit'),
                                class: 'action primary accept',

                                /**
                                 * Click handler.
                                 */
                                click: function () {
                                    self.formElement.submit();
                                }
                            },{
                                text: $.mage.__('Return to Edit'),
                                class: 'action',

                                /**
                                 * Click handler.
                                 */
                                click: function () {
                                    this.closeModal(true);
                                }
                            }]
                        });
                        return false;
                    }
                }
                sessionStorage.setItem('seller_product_saved', '1');
                sessionStorage.setItem('seller_product_saved_v3', '1');
                self.formElement.submit();
            });
            $('#hide_product_on_search').change(function () {
                var isChecked = $(this).is(':checked');
                $('.hide_product_on_search').toggleClass('hide', !isChecked);
            });
            $('.icon-copy').on('click', function () {
                var $this = jQ(this);
                var text = $this.data('clipboard-text');
                navigator.clipboard.writeText(text).then(function() {
                    alert({
                        content: $.mage.__('已複製')
                    });
                });
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
                var category_id=DOMPurify.sanitize(jQ(this).val());
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
            var checkSpinnerCompleted = setInterval(function(){
                var cntSpinnerCompleted = 0;
                var cntSpinner = 0;
                $.each($('.admin__data-grid-loading-mask'), function(k, v){
                    if($(v).attr('data-role') == 'spinner' && $(v).css('display') == 'none'){
                        cntSpinnerCompleted ++;
                    }
                    if($(v).attr('data-role') == 'spinner'){
                        cntSpinner ++;
                    }
                });
                if(cntSpinnerCompleted == cntSpinner){
                    self.startToCheckChangesOnJs = true;
                    clearInterval(checkSpinnerCompleted);
                    self.collectOriginProductData();
                    self.validateChanges();
                    while($('.loading-mask').length && $('.loading-mask').css('display') != 'none'){
                        $('#edit-product').trigger('processStop');
                    }
                }else{
                    if($('.loading-mask').css('display') == 'none' || !$('.loading-mask').length){
                        $('#edit-product').trigger('processStart');
                    }
                }
                console.log('runnnn');
            }, 200);
            $(document).click(function(){
                self.bodyClicked = true;
            });
            $(document).on('change', '#edit-product input, #edit-product textarea, #edit-product select', function(e){
                if(!self.startToCheckChangesOnJs){
                    return;
                }
                self.validateChanges();
                
            });
            $(document).on('change', '#related-product-block .admin__control-checkbox', function(e){
                if(!self.startToCheckChangesOnJs){
                    return;
                }
                self.validateChanges();
            });
            $('#staging_update_new_prevent').click(function(){
                alert({
                    content: $t("The product has been modified. Please complete the 'Submit for Approval' process before creating a new schedule. Creating a new schedule is not allowed at this stage.")
                });
            });
        },
        collectOriginProductData: function(){
            let formOriginProductData = $('#edit-product').serializeArray();
            $.each(formOriginProductData, function(k, _obj){
                if(_obj.name.slice(-2) == '[]'){
                    if(typeof this.originProductData[_obj.name] != "undefined"){
                        this.originProductData[_obj.name] += ','+_obj.value;
                    }else{
                        this.originProductData[_obj.name] = _obj.value;
                    }
                }else{
                    this.originProductData[_obj.name] = _obj.value;
                }
            }.bind(this));
        },
        validateChanges: function(){
            let isUpdated = false;
               
            /*
            $.each(productData, function(attr, value){
                if(["media_gallery", "extension_attributes", "stock_data", "website_ids","entity_id", "attribute_set_id", "type_id"].includes(attr)){
                    return;
                }
                let attributeKey = 'product['+attr+']';
                if(attr == 'status'){
                    attributeKey = 'status';
                }
                if(!$('[name="'+attributeKey+'"]').length && !$('[name="'+attributeKey+'[]"]').length){
                    return;
                }
                let productDataAttrValue = $('[name="'+attributeKey+'"]').val();
                if(['short_description', 'search_tag'].includes(attr)){
                    value = value.replace(/\r/g, "");
                    productDataAttrValue = productDataAttrValue.replace(/\r/g, "");
                }
                if($('[name="'+attributeKey+'"]').parent().hasClass('actions-switch')){
                    if($('[name="'+attributeKey+'"]:checked').length){
                        productDataAttrValue = $('[name="'+attributeKey+'"]:checked').val();
                    }
                }
                if($('[name="'+attributeKey+'"]').hasClass('_has-datepicker')){
                    productDataAttrValue = productDataAttrValue.replaceAll('/','-');
                    if(value){
                        value = value.replaceAll('/','-');
                    }
                    if(productDataAttrValue.length == 10){
                        productDataAttrValue += ' 08:00:00';
                    }
                    if(productDataAttrValue.length){
                        var productDataAttrValueY = productDataAttrValue.substr(6, 4);
                        var productDataAttrValueM = productDataAttrValue.substr(0, 2);
                        var productDataAttrValueD = productDataAttrValue.substr(3, 2);
                        productDataAttrValue = productDataAttrValueY+'-'+productDataAttrValueM+'-'+productDataAttrValueD+productDataAttrValue.substr(10);
                    }
                    if(value && value.length == 10){
                        value += ' 00:00:00'
                    }
                    
                }
                if($('[name="'+attributeKey+'[]"]').length){
                    productDataAttrValue = $('[name="'+attributeKey+'[]"]').val();
                    value = value.split(',');
                }
                if(value == null){
                    value = '';
                }
                if((typeof productDataAttrValue == 'object' && !this.compare(productDataAttrValue, value)) 
                    || (typeof productDataAttrValue != 'object' && productDataAttrValue != value)){
                    isUpdated = true;
                    console.log(attributeKey);
                    console.log(productDataAttrValue);
                    console.log(value);
                }
            }.bind(this));

            $.each(productOptions, function(ind, _option){ 
                $.each(_option, function(_key, _value){
                    let _valueKey = 'product[options]['+_option['option_id']+']['+_key+']';
                    if(!$('[name="'+_valueKey+'"]').length){
                        return;
                    }
                    if(_key != 'values' && $('[name="'+_valueKey+'"]').attr('type') != 'checkbox' && _value != $('[name="'+_valueKey+'"]').val()){
                        isUpdated = true;console.log('22'+_valueKey);
                    }
                    if(_key == 'value'){
                        $.each(_value, function(__keyVal, __val){
                            $.each(__val, function(k, v){
                                let __valKey = 'product[options]['+_option['option_id']+'][values]['+__keyVal+']['+k+']';
                                if($('[name="'+__valKey+'"]').attr('type') != 'checkbox' && __val != $('[name="'+__valKey+'"]').val()){
                                    isUpdated = true;console.log('33'+__valKey);
                                }else if($('[name="'+__valKey+'"]').attr('type') == 'checkbox' && __val != $('[name="'+__valKey+'"]').is(':checked')){
                                    isUpdated = true;console.log('44'+__valKey);
                                }
                            });
                        });
                    }
                });
            });
*/
            
            $.each(this.originProductData, function(itemFieldName, _itemValue){
                let fieldSelector = '[name="'+itemFieldName+'"]';
                if(itemFieldName == 'product[cost_setting]'){
                    fieldSelector = fieldSelector+':checked';
                }
                if($(fieldSelector).parent().hasClass('actions-switch')){
                    if($(fieldSelector+':checked').length){
                        fieldSelector = fieldSelector+':checked';
                    }
                }
                let fieldVal = $(fieldSelector).val();
                if(typeof fieldVal != 'object'){
                    fieldVal = fieldVal.replace(/[\r\n]/g, "")
                }else{
                    fieldVal = fieldVal.join(',');
                }
                if(typeof _itemValue != 'object'){
                    _itemValue = _itemValue.replace(/[\r\n]/g, "");
                }
                if(fieldVal != _itemValue){
                    console.log(itemFieldName);
                    console.log(fieldVal);
                    console.log(fieldSelector);
                    console.log(_itemValue);
                    console.log('-------------');
                    isUpdated = true;
                }
            });
            /** Related product */
            let relatedIds = [];
            $.each($('#edit-product > div'), function(i, _related){
                let relatedId = $(_related).attr('id');
                if(relatedId.indexOf('related-product-record') !== -1){
                    let relatePid = relatedId.replace('related-product-record', '');
                    relatedIds[relatedIds.length] = parseInt(relatePid, 10);
                }
                
            });
            if(!this.compare(relatedIds, productRelatedIds)){
                isUpdated = true;
                console.log('related');
                console.log(relatedIds);
                console.log(productRelatedIds);
            }
            
            
            if(isUpdated){
                this.preventSchedule();
            }else{
                this.allowSchedule();
            }
        },
        compare: function(arr1, arr2) {
            arr1.sort();
            arr2.sort();
        
            if (arr1.length != arr2.length)
                return false;
        
            for (let i = 0; i < arr1.length; i++) {
                if (arr1[i] != arr2[i])
                    return false;
            }
            return true;
        },
        preventSchedule: function(){
            $('#staging_update_new').hide();
            $('#staging_update_new_prevent').show();
        },
        allowSchedule: function(){
            $('#staging_update_new').show();
            $('#staging_update_new_prevent').hide();
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
            var i, len, name, id, checkn;
            var jQ = $.noConflict();
            jQ.ajax({
                url     :   self.options.categoryTreeAjaxUrl,
                type    :   "POST",
                data    :   {
                    parentCategoryId : thisthis.siblings("input").val(),
                    categoryIds :   self.options.categories
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
                        checkn=DOMPurify.sanitize(newdata[i].check?.toString());
                        name=DOMPurify.sanitize(newdata[i].name);
                        if (checkn==1) {
                            if (newdata[i].counting === 0) {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-no"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" checked value='+ id+'></div>');
                            } else {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-plusend"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" checked value='+ id +'></div>');
                            }
                        } else {
                            if (newdata[i].counting === 0) {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-no"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" value='+ id+'></div>');
                            } else {
                                thisthis.parent(".wk-cat-container").after('<div class="wk-removable wk-cat-container" style="display:none;margin-left:'+pxl+'px;"><span  class="wk-plusend"></span><span class="wk-foldersign"></span><span class="wk-elements wk-cat-name">'+ name +'</span><input class="wk-elements" type="checkbox" value='+ id +'></div>');
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
    return $.mage.sellerEditProduct;
});

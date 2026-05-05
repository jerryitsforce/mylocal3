/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_OptionsWithStockAndImages
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    "jquery",
    "mage/translate",
    "Magento_Ui/js/modal/modal",
    'mage/url',
    'ko',
    'Magento_Ui/js/modal/alert',
    'mage/template',
    'underscore',
    'plugins/DOMPurify'
], function($, $t, modal, url, ko, alert, mageTemplate, _, DOMPurify) {
    'use strict';
    return function(option) {
        var format = /[`!@#$%^_+\-=\[\]{};':"\\|,.<>\/?~]/;
        var savedData = option.saved;
        var swatchSavedData = option.swatch;
        var formattedSwatchSaved = [];
        var wkvariationscomb = [];
        var wkvariationsswatch = [];
        var syncUrl = option.syncurl;
        var customoptioncurl = option.customoptioncurl;
        var jQ = $.noConflict();

        if (swatchSavedData != '{}') {
            $.each(swatchSavedData, function(key, value) {
                formattedSwatchSaved[value.title] = value['is_swatch'];
            });
        }
        var formattedSaved = [];
        var check_follow_simple_sku_cost_setting = true;
        var check_follow_simple_sku_price_setting = true;
        if (savedData != '{}') {
            var counter = 0;
            $.each(savedData, function(key, value) {
                formattedSaved[counter] = value;
                counter++;
                if(value.hasOwnProperty('follow_simple_sku_cost_setting') && value.follow_simple_sku_cost_setting == 0) {
                    check_follow_simple_sku_cost_setting = false;
                }
                if(value.hasOwnProperty('follow_simple_sku_price_setting') && value.follow_simple_sku_price_setting == 0) {
                    check_follow_simple_sku_price_setting = false;
                }
            });
        }
        var wksmodal = 0;
        //pop-up display to manage swatches
        $('body').on('click', '#add_wkswatches_button', async function() {
            var optionValuesTitles = [];
            var swatchDatas, swatchTitles = [];

            var data = $("#edit-product").serialize();
            swatchDatas = await getCustomOptions(data);

            var isRequired = true;
            var hasSpecialCharacter = false;
            var isCommonTitle = false;
            var count = 0;
            var noOfField = swatchDatas.length;
            $.each(swatchDatas, function(key, swatchData) {
                if (swatchData.type == "radio" || swatchData.type == "drop-down" || swatchData.type == "drop_down") {
                    if (swatchData.is_require != 1) {
                        count++
                    }
                    var swatchtitle = swatchData.title;
                    swatchTitles.push(swatchtitle);
                    if (jQuery.inArray(swatchtitle, optionValuesTitles) != -1) {
                        isCommonTitle = true;
                    }
                    optionValuesTitles.push(swatchtitle);
                    // if (format.test(swatchtitle) == true) {
                    //     hasSpecialCharacter = true;
                    // }
                    if (swatchData.values.length > 0) {
                        var swatchDataValues = swatchData.values;
                        $.each(swatchDataValues, function(swatchDatakey, swatchDataValue) {
                            var swatchDataTitle = swatchDataValue.title;
                            if (jQuery.inArray(swatchDataTitle, optionValuesTitles) != -1) {
                                isCommonTitle = true;
                            }
                            optionValuesTitles.push(swatchDataTitle);
                            // if (format.test(swatchDataTitle) == true) {
                            //     hasSpecialCharacter = true;
                            // }
                        });
                    }
                }
            });
            if (count == noOfField) {
                isRequired = false;
            }
            if (isCommonTitle) {
                alert({ 'content': $t("Combination can not be created, don't use same option title for mutiple options.") });
                return;
            }
            if (hasSpecialCharacter) {
                alert({ 'content': $t("Don't use special characters in Custom options to save swatch and variation") });
                return;
            }
            if (!isRequired) {
                //alert({ 'content': $t("At least one should be selected as required") });
                //return;
                $('#product_options_container').find('select[name^="product[options]"][name$="[is_require]"]').val(1).trigger('change');
                $('#product_options_container').find('input[id^="product_option_"][id$="_required"]').prop('checked', true);
            }
            if (!swatchTitles.length) {
                alert({ 'content': $t("Swatch can not be created.") });
                return;
            }
            if (wksmodal != 0 && JSON.stringify(wkvariationsswatch) === JSON.stringify(swatchTitles)) {
                wksmodal.openModal();
                return;
            } else {
                $(".wk-mv-swatch").remove();
            }
            var options = {
                type: 'slide',
                responsive: true,
                innerScroll: false,
                modalClass: 'wk-mv-swatch',
                width: '200px',
                title: 'Manage Swatch',
                buttons: [{
                    text: $.mage.__('Save'),
                    class: 'action-default primary',
                    click: function() {
                        var data = JSON.stringify($("#wk-swatch-form").serializeArray());
                        $('body').find('[name="product[wk_manage_swatch]"]').remove();
                        $("body").find("[name='product[name]']").after("<input class='input-text admin__control-text' type='hidden' name='product[wk_manage_swatch]' data-form-part='product_form' value='" + data + "'></input>");
                        let parsedData = JSON.parse(data);
                        wkvariationsswatch = [];
                        $.each(parsedData, function(ind, val) {
                            if (val.name.includes('[title]')) {
                                wkvariationsswatch.push(val.value);
                            }
                        });
                        wksmodal = this;
                        this.closeModal();
                    }
                }]
            }

            var progressTmpl = mageTemplate('#wk-swatch-form-template'),
                swatchTmpl;
            swatchTmpl = progressTmpl({
                data: {
                    swatchs: swatchDatas,
                    formattedSwatch: formattedSwatchSaved
                }
            });
            var cont = $('<div>').append(swatchTmpl);
            modal(options, cont);
            cont.modal('openModal');
            getPagination('#swatch-tbl', 5);
        });
        var wkmvmodal = 0;
        //pop up display for manage variation
        $('body').on('click', '#add_wkvariations_button', async function() {
            var mess = await buildWkSwatch();
            if(mess !== ""){
                alert({ 'content': mess });
                return;
            }

            var datas, values = [],
                oldValues = [],
                titles = [];

            var disabled = $("#product_options_container").find(':input:disabled').removeAttr('disabled');
            var data = $("#edit-product").serialize();
            disabled.attr('disabled','disabled');
            datas = await getCustomOptions(data);
            if (!datas || !datas.length) {
                alert({ 'content': $t("No option available.") });
                return;
            }

            var isRequired = true;
            var sku = [];
            var count = 0;
            var noOfField = datas.length;
            var hasSpecialCharacter = false;

            $.each(datas, function(key, data) {
                if (data.type == "radio" || data.type == "drop-down" || data.type == "drop_down") {
                    if (data.is_require != 1) {
                        count++;
                    }
                    var title = data.title;
                    var valueArr = [];
                    var oldValueArr = [];
                    titles.push(title);
                    if (parseInt(title) != title) {
                        values[title] = [];
                        oldValues[title] = [];
                    } else {
                        values[title + ' '] = [];
                        oldValues[title + ' '] = [];
                    }
                    $.each(data.values, function(index, value) {
                        valueArr.push(value.title);
                        if ($('#product_option_'+data.option_id+'_select_'+value.option_type_id+'_title').length) {
                            const val = $('#product_option_' + data.option_id + '_select_' + value.option_type_id + '_title').data('store-label');
                            oldValueArr.push(val);
                        } else {
                            oldValueArr.push(value.title);
                        }
                        sku[value.title] = value.sku;
                    });
                    if (valueArr.length) {
                        values.push(valueArr);
                        oldValues.push(oldValueArr);
                    }
                }
            });

            if (noOfField == count && !values.length) {
                isRequired = false
            }
            if (hasSpecialCharacter) {
                alert({ 'content': $t("Don't use special characters in Custom options to save swatch and variation") });
                return;
            }
            if (!isRequired) {
                alert({ 'content': $t("Atleast one field should be selected as a required.") });
                return;
            }
            if (!values.length) {
                alert({ 'content': $t("Combination can not be created.") });
                return;
            }
            var comb = combineArrays(values),
                oldComb = combineArrays(oldValues, true);
            if (wkmvmodal != 0 && JSON.stringify(wkvariationscomb) === JSON.stringify(comb)) {
                wkmvmodal.openModal();
                setWeight();
                if($('#follow_simple_sku_price_setting').length && $('#follow_simple_sku_price_setting').is(':checked')) {
                    $('#follow_simple_sku_price_setting').trigger('change');
                }
                if($('#follow_simple_sku_cost_setting').length && $('#follow_simple_sku_cost_setting').is(':checked')) {
                    $('#follow_simple_sku_cost_setting').trigger('change');
                }
                return;
            } else if (comb.length === 0) {
                return;
            } else {
                $(".wk-mv-modal").remove();
            }
            var options = {
                type: 'slide',
                responsive: true,
                innerScroll: false,
                modalClass: 'wk-mv-modal',
                width: '200px',
                title: '管理規格',
                buttons: [{
                    text: $.mage.__('Save'),
                    class: 'action-default primary',
                    click: function() {
                        var isValid = true,
                            isDupSku = false,
                            haveEmpty = false,
                            alreadySku = [];
                        $('#wk-variation-form input.wkv-cost').each(function(ind, val) {
                            if ($(val).val() <= 0 || $.isNumeric($(val).val()) !== true) {
                                isValid = false;
                            }
                            if ($(val).val() === "" || $(val).val().trim() === "") {
                                haveEmpty = true;
                            }
                        });
                        $('#wk-variation-form input.wkv-price').each(function(ind, val) {
                            if ($(val).val() <= 0 || $.isNumeric($(val).val()) !== true) {
                                isValid = false;
                            }
                            if ($(val).val() === "" || $(val).val().trim() === "") {
                                haveEmpty = true;
                            }
                        });
                        $('#wk-variation-form input.wkv-stock').each(function(ind, val) {
                            if ($(val).val() < 0 || $.isNumeric($(val).val()) !== true || /^-?\d+$/.test($(val).val()) !== true) {
                                isValid = false;
                            }
                            if ($(val).val() === "" || $(val).val().trim() === "") {
                                haveEmpty = true;
                            }
                        });
                        $('#wk-variation-form input.wkv-sku').each(function(ind, val) {
                            let valSku = $(val).val().trim();
                            if (valSku) {
                                if (!alreadySku.includes(valSku)) {
                                    alreadySku.push(valSku);
                                } else {
                                    isDupSku = true;
                                }
                            } else {
                                haveEmpty = true;
                            }
                        });
                        if (isValid && !isDupSku && !haveEmpty) {
                            var disabled = $("#wk-variation-form").find(':input:disabled').removeAttr('disabled');
                            var data = JSON.stringify($("#wk-variation-form").serializeArray());
                            disabled.attr('disabled','disabled');
                            $('body').find('[name="product[wk_manage_variation]"]').remove();
                            $("body").find("[name='product[name]']").after("<input class='input-text admin__control-text' type='hidden' name='product[wk_manage_variation]' data-form-part='product_form' value='" + data + "'></input>");
                            let parsedData = JSON.parse(data);
                            wkvariationscomb = [];
                            $.each(parsedData, function(ind, val) {
                                if (val.name.includes('[comb]')) {
                                    wkvariationscomb.push(val.value + "_");
                                }
                            });
                            wkmvmodal = this;
                            this.closeModal();
                        } else {
                            if (isDupSku) {
                                alert({'content': $t("Duplicate SKU found.")});
                            } else {
                                if (haveEmpty) {
                                    alert({'content': $t("Please ensure that all the required fields have been filled in: Cost, Stock, SKU, Price.")});
                                } else {
                                    alert({'content': $t("Please enter a number 0 or greater and must be of numeric type for Cost and Stock fields.")});
                                }
                            }
                        }
                    }
                }]
            }
            var title = titles.join("_");
            var weightDisabled = $("[name='product[product_has_weight]']").is(":checked");
            var progressTmpl = mageTemplate('#wk-variation-form-template'),
                variationTemp;
            variationTemp = progressTmpl({
                data: {
                    combination: comb,
                    oldComb: oldComb,
                    sku: sku,
                    title: title,
                    savedData: formattedSaved,
                    weightDisabled: !weightDisabled
                }
            })
            var cont = $('<div>').append(variationTemp);
            modal(options, cont);
            cont.modal('openModal');
            //setPrice(formattedSaved);
            setWeight();
            getPagination('#variation-tbl', 5);
        });

        //image upload
        jQ("body").on("change", ".wk-variation-table .wkv-image", function() {
            var self = this;
            var linkUrl = option.linkurl + "?form_key=" + window.FORM_KEY;
            var dataId = $(self).attr('data-id');
            var imageIndex = 0;
            var files = $(this)[0].files;
            jQ.each(files, function(key, file) {
                var data = new FormData();
                data.append('image', file);
                jQ.ajax({
                    type: "POST",
                    url: linkUrl,
                    enctype: 'multipart/form-data',
                    mimeType: "multipart/form-data",
                    data: data,
                    contentType: false,
                    cache: false,
                    processData: false,
                    beforeSend: function() {
                        $(self).closest(".data-grid-file-uploader").addClass("_loading");
                    },
                    success: function(response) {
                        response = JSON.parse(response);
                        if (response.error) {
                            $(self).closest(".data-grid-file-uploader").removeClass("_loading");
                            alert({ 'content': $t("We are unable to recognize or support this file extension type.") });
                            return;
                        }
                        var dataImageCount = $(self).attr('data-image-count');
                        imageIndex = parseInt(dataImageCount) + parseInt(key);
                        $(self).closest(".data-grid-file-uploader").removeClass("_loading");
                        var progressTmpl = mageTemplate('#new-uploaded-image-template'),
                            uploadedImage;
                        uploadedImage = progressTmpl({
                            data: {
                                id: dataId,
                                response,
                                imageIndex: imageIndex
                            }
                        })

                        jQ(self).parent().before(DOMPurify.sanitize(uploadedImage));
                        if (key == files.length - 1) {
                            imageIndex++;
                            $(self).attr('data-image-count', imageIndex);
                        }
                    },
                    error: function(response) {
                        $(self).closest(".data-grid-file-uploader").removeClass("_loading");
                    }
                });
            });

            if (files) {}
        });

        jQ('body').on('change', '.maxRows', function() {
            var tblId = DOMPurify.sanitize('#' + jQ(this).closest('form').find('table').attr('id') + '');
            var maxRows = parseInt(jQ(this).val());
            getPagination(tblId, maxRows);
        });
        jQ('.maxRows').trigger('change');

        async function buildWkSwatch(){
            var mess = '';
            var swatchDatas = [];
            var dataForm = $("#edit-product").serialize();
            swatchDatas = await getCustomOptions(dataForm);
            if (!swatchDatas || !swatchDatas.length) {
                return $t("No option available.");
            }
            var data = [];
            $.each(swatchDatas, function(key, swatchData) {
                if (swatchData.type == "radio" || swatchData.type == "drop-down" || swatchData.type == "drop_down") {
                    if (swatchData.is_require != 1) {
                        $('#product_options_container').find('select[name^="product[options]"][name$="[is_require]"]').val(1).trigger('change');
                        $('#product_options_container').find('input[id^="product_option_"][id$="_required"]').prop('checked', true);
                        //mess = $t("The custom option must be is required to be able to setup variation");
                    }
                    data.push(
                        {
                            "name":"wkswatch["+key+"][is_swatch]",
                            "value": 1
                        }
                    );
                    data.push(
                        {
                            "name":"wkswatch["+key+"][option_id]",
                            "value": swatchData.option_id
                        }
                    );
                    data.push(
                        {
                            "name":"wkswatch["+key+"][title]",
                            "value": swatchData.title
                        }
                    );
                } else {
                    mess = $t("The variation only support custom option type radio and drop-down");
                }
            });
            if(mess === '' && data.length > 0){
                var data_str = JSON.stringify(data);
                $('body').find('[name="product[wk_manage_swatch]"]').remove();
                $("body").find("[name='product[name]']").after("<input class='input-text admin__control-text' type='hidden' name='product[wk_manage_swatch]' data-form-part='product_form' value='" + data_str + "'></input>");
            }
            return mess;
        }

        async function getCustomOptions(args) {
            let result;

            try {
                result = await $.ajax({
                    url: customoptioncurl,
                    type: 'POST',
                    data: args
                });

                return result;
            } catch (error) {
                console.error(error);
            }
        }

        function getPagination(table, maxRows) {
            var currenpageAttr = jQ(table).closest('form').find('.currenpage');
            jQ('.pagination').html('');
            var trnum = 0;
            var totalRows = jQ(table + ' tbody tr').length;
            jQ(table + ' tr:gt(0)').each(function() {
                trnum++;
                if (trnum > maxRows) {

                    jQ(this).hide();
                }
                if (trnum <= maxRows) { jQ(this).show(); }
            });
            jQ(currenpageAttr).val(1);
            jQ('.nxt-pag-btn').prop("disabled", true);
            if (totalRows > maxRows) {
                var pagenum = Math.ceil(totalRows / maxRows);
                //	numbers of pages
                if (pagenum > 1) {
                    jQ('.nxt-pag-btn').prop("disabled", false);
                }
            } else {
                pagenum = 1;
            }
            jQ('.pagenumlabel').html('of ' + pagenum);

            showig_rows_count(maxRows, 1, totalRows);

            if(check_follow_simple_sku_price_setting) {
                $('#follow_simple_sku_price_setting').prop('checked', true).trigger('change');
            }

            if(check_follow_simple_sku_cost_setting) {
                $('#follow_simple_sku_cost_setting').prop('checked', true).trigger('change');
            }

            //SHOWING ROWS NUMBER OUT OF TOTAL DEFAULT
            jQ('.wkv-cost_setting').on('change', function(e) {
                if(!isFollowSimpleSkuCostSettingChecked()) {
                    var id = getId(this);
                    setCostAndCommission(id);
                }
            });
            jQ('.wkv-commission_percent').on('change', function(e) {
                if(!isFollowSimpleSkuCostSettingChecked()) {
                    var id = getId(this);
                    setCostAndCommission(id);
                }
            });
            jQ('.wkv-cost').on('change', function(e) {
                if(!isFollowSimpleSkuCostSettingChecked()) {
                    var id = getId(this);
                    setCostAndCommission(id);
                }
            });
            jQ('.wkv-price').on('change', function(e) {
                var id = getId(this);
                setCostAndCommission(id);
            });

            jQ('.switch-is-sync').on('change', function(e) {
                var id = getId(this);
                if($(this).is(":checked")) {
                    if ($('#wkv-sku-' + id).length) {
                        if($('#wkv-sku-' + id).val() === ''){
                            alert({ 'content': $t("Please enter SKU.") });
                            $(this).prop('checked', !$(this).prop('checked'));
                            e.preventDefault();
                            return false;
                        }
                        var data = {
                            sku: $('#wkv-sku-' + id).val()
                        };
                        jQ.ajax({
                            type: "GET",
                            url: syncUrl,
                            data: data,
                            cache: false,
                            beforeSend: function() {},
                            success: function(response) {
                                if (response && response.hasOwnProperty('sku')) {
                                    if ($('#wkv-stock-' + id).length) {
                                        $('#wkv-stock-' + id).val(response.stock);
                                    }
                                    if ($('#wkv-weight-' + id).length) {
                                        $('#wkv-weight-' + id).val(response.weight);
                                    }
                                    if ($('#wkv-sku-' + id).length) {
                                        $('#wkv-sku-' + id).val(response.sku);
                                        $('#wkv-sku-' + id).prop('disabled', true);
                                    }
                                    if ($('#wkv-stock-' + id).length) {
                                        $('#wkv-stock-' + id).prop('disabled', true);
                                    }
                                    if (response.hasOwnProperty('images') && response.images.length) {
                                        var element = '#wk-variation-row-' + id + 'image';
                                        if ($(element).length) {
                                            $(element).parent().closest('.data-grid-file-uploader').children('.wk-img-box').remove();

                                            var imageIndex = response.images.length;
                                            $.each(response.images, function(key, file) {
                                                var progressTmpl = mageTemplate('#new-uploaded-image-template'),
                                                    uploadedImage;
                                                uploadedImage = progressTmpl({
                                                    data: {
                                                        id: id,
                                                        response: file,
                                                        imageIndex: key
                                                    }
                                                })

                                                jQ(element).parent().before(DOMPurify.sanitize(uploadedImage));
                                            });

                                            jQ(element).attr('data-image-count', imageIndex);
                                        }
                                    }
                                } else {
                                    alert({ 'content': response });
                                    $(this).prop('checked', !$(this).prop('checked'));
                                    return false;
                                }
                            }.bind(this),
                            error: function(response) {
                                alert({ 'content': response });
                                $(this).prop('checked', !$(this).prop('checked'));
                                return false;
                            }.bind(this)
                        });
                    }
                } else {
                    if ($('#wkv-sku-' + id).length) {
                        if($('#wkv-is_lock_sku-' + id).length && $('#wkv-is_lock_sku-' + id).val() != 1){
                            $('#wkv-sku-' + id).prop('disabled', false);
                        }
                    }
                    if ($('#wkv-stock-' + id).length) {
                        $('#wkv-stock-' + id).prop('disabled', false);
                    }
                }
            })

            $('.pre-pag-btn, .nxt-pag-btn').unbind('click').click(function() {
                var currenpage = $(currenpageAttr).val();
                if ($(this).attr("class") == 'action-next nxt-pag-btn') {
                    $('.pre-pag-btn').prop("disabled", false);
                    currenpage++;
                }
                if ($(this).attr("class") == 'action-previous pre-pag-btn') {
                    $('.nxt-pag-btn').prop("disabled", false);
                    currenpage--;
                }
                if (pagenum == currenpage) {
                    $('.nxt-pag-btn').prop("disabled", true);
                }
                if (currenpage == 1) {
                    $('.pre-pag-btn').prop("disabled", true);
                }
                nextandpreviousPage(currenpage);
                $('.currenpage').val(currenpage);
            })

            function nextandpreviousPage(pageNum) {
                var trIndex = 0;

                //SHOWING ROWS NUMBER OUT OF TOTAL
                showig_rows_count(maxRows, pageNum, totalRows);
                //SHOWING ROWS NUMBER OUT OF TOTAL

                jQ(table + ' tr:gt(0)').each(function() {
                    trIndex++;
                    // if tr index gt maxRows*pageNum or lt maxRows*pageNum-maxRows fade if out
                    if (trIndex > (maxRows * pageNum) || trIndex <= ((maxRows * pageNum) - maxRows)) {
                        $(this).hide();
                    } else { $(this).show(); }
                });
            }

            jQ('.wkv-cost_setting').trigger('change');
        }

        // $(function() {
        //     // Just to append id number for each row
        //     default_index();

        // });

        // Set Weight
        function setWeight() {
            var weight = 0;
            if ($('input[name="product[weight]"]').length && $('input[name="product[weight]"]').val() > 0) {
                weight = $('input[name="product[weight]"]').val();
            }
            $('.wkv-weight').each(function() {
                if ($(this).val() == 0 || $(this).val() == '') {
                    $(this).val(weight);
                }
            });
        }

        // Get ID
        function getId(element) {
            return DOMPurify.sanitize($(element).data('id')?.toString());
        }

        // Is follow simple sku cost setting checked
        function isFollowSimpleSkuCostSettingChecked() {
            var follow_simple_sku_cost_setting = $('#follow_simple_sku_cost_setting');
            if(follow_simple_sku_cost_setting.length && follow_simple_sku_cost_setting.is(':checked')) {
                return true;
            }
            return false;
        }

        //Cal price
        function getPrice(id){
            var price = 0;
            if ($('#wkv-price-' + id).length && $('#wkv-price-' + id).val() > 0) {
                price = $('#wkv-price-' + id).val();
            }
            if(price == 0){
                price = getProductPrice();
            }
            return price;
        }

        function setPrice(formattedSaved){
            var price = getProductPrice();
            var defaultPrice = false;
            for (var i = 0; i < formattedSaved.length; i++) {
                if(!formattedSaved[i].hasOwnProperty('price') || formattedSaved[i].price == 0) {
                    defaultPrice = true;
                    break;
                }
            }
            if(formattedSaved.length == 0 || (defaultPrice && price)) {
                $('.wkv-price').val(price);
            }
        }

        function getProductPrice() {
            var price = 0;
            if ($('input[name="product[special_price]"]').length && $('input[name="product[special_price]"]').val() > 0) {
                price = $('input[name="product[special_price]"]').val();
            } else {
                price = $('input[name="product[price]"]').val();
            }
            return price;
        }

        function getProductCostSetting() {
            var costSetting = 1;
            if ($('input[name="product[cost_setting]"]').length) {
                costSetting = $('input[name="product[cost_setting]"]').val();
            }
            return costSetting;
        }

        function getProductCost() {
            var cost = 0;
            if ($('input[name="product[cost]"]').length && $('input[name="product[cost]"]').val() > 0) {
                cost = $('input[name="product[cost]"]').val();
            }
            return cost;
        }

        function getProductCommissionPercent() {
            var commissionPercent = 0;
            if ($('input[name="product[commission_percent]"]').length && $('input[name="product[commission_percent]"]').val() > 0) {
                commissionPercent = $('input[name="product[commission_percent]"]').val();
            }
            return commissionPercent;
        }

        function getCostSetting(id) {
            var costSetting = 1;
            if ($('#wkv-cost_setting-' + id).length && $('#wkv-cost_setting-' + id).val() == 0) {
                costSetting = 0;
            }
            return costSetting;
        }

        function getCommissionPercent(id) {
            var commissionPercent = 0;
            if ($('#wkv-commission_percent-' + id).length && $('#wkv-commission_percent-' + id).val() > 0) {
                commissionPercent = $('#wkv-commission_percent-' + id).val();
            }
            if(commissionPercent == 0){
                commissionPercent = getProductCommissionPercent();
            }
            return commissionPercent;
        }

        function getCost(id) {
            var cost = 0;
            if ($('#wkv-cost-' + id).length && $('#wkv-cost-' + id).val() > 0) {
                cost = $('#wkv-cost-' + id).val();
            }
            if(cost == 0){
                cost = getProductCost();
            }
            return cost;
        }

        // Set Cost
        function setCostAndCommission(id){
            var price = getPrice(id);
            var cost = getCost(id);
            var commissionPercent = getCommissionPercent(id);
            var costSetting = getCostSetting(id);
            var cost_value = calculateCost(costSetting, commissionPercent, cost, price);
            var cost_commission_percent = calculateCommissionRate(cost_value, price);
            if($('#wkv-cost-' + id).length) {
                $('#wkv-cost-' + id).val(cost_value);
                $('#wkv-commission_percent-' + id).val(cost_commission_percent);
                if(costSetting == 1){
                    //Fixed commission
                    if($('#wkv-commission_percent-' + id).length) {
                        $('#wkv-commission_percent-' + id).prop('readonly', false);
                    }
                    $('#wkv-cost-' + id).prop('readonly', true);
                    if($('#wkv-cost_setting-input-' + id).length) {
                        $('#wkv-cost_setting-input-' + id).val('1');
                    }
                } else {
                    //Manually input cost
                    if($('#wkv-commission_percent-' + id).length) {
                        $('#wkv-commission_percent-' + id).prop('readonly', true);
                    }
                    $('#wkv-cost-' + id).prop('readonly', false);
                    if($('#wkv-cost_setting-input-' + id).length) {
                        $('#wkv-cost_setting-input-' + id).val('0');
                    }
                }
            }
            if(isFollowSimpleSkuCostSettingChecked()) {
                $('.wkv-cost').prop('readonly', true);
                $('.wkv-commission_percent').prop('readonly', true);
            }
            showCommissionSourceText(id);
        }

        // Commission Source
        function showCommissionSourceText(id){
            var commissionPercent = getCommissionPercent(id);
            var costSetting = getCostSetting(id);
            var commissionSource = getCommissionSource(commissionPercent, costSetting);
            if(commissionSource && commissionSource.length){
                $('#wkv-commission_source-' + id).html(commissionSource[1]);
            }
        }

        function getCommissionSource(commissionPercent, costSetting){
            /** Commission source */
            if(sellerCommissionData && Object.keys(sellerCommissionData).length){
                var sellerCommissionRate = sellerCommissionData.commission_rate;
                var sellerDefaultCommissionRate = sellerCommissionData.default_commission_rate;
                var commissionSourceTxt = $t('N/A');
                var gCommissionSource = '';
                // the contract is being executed
                if(typeof sellerCommissionData.active_contract != 'boolean'){
                    if(costSetting == 1){
                        if(commissionPercent == sellerCommissionRate){
                            // Active Period
                            gCommissionSource = 2;
                        } else {
                            // Manually input
                            gCommissionSource = 1;
                        }
                    } else {
                        // Manually input
                        gCommissionSource = 1;
                    }
                } else {
                    if(costSetting == 1 && commissionPercent == sellerDefaultCommissionRate){
                        // Based on Default Settings
                        gCommissionSource = 3;
                    } else {
                        gCommissionSource = 1;
                    }
                }
                commissionSourceTxt = showCommissionSource(gCommissionSource);
                return [gCommissionSource, commissionSourceTxt];
            }
            return ['',''];
        }

        function showCommissionSource(val){
            var commissionSourceTxt = '';
            if(sellerCommissionData && Object.keys(sellerCommissionData).length){
                if(val == 2){
                    commissionSourceTxt = $t('Based on Active Period: %1 to %2').replace('%1', sellerCommissionData.active_contract.from).replace('%2', sellerCommissionData.active_contract.to);
                }else if(val == 3){
                    commissionSourceTxt = $t('Based on Default Settings');
                }else if(val == 1){
                    commissionSourceTxt = $t('Manually Input');
                }
                return commissionSourceTxt;
            }
            return '';
        }

        //Cal cost and commission percent
        function calculateCost(costSetting, commissionPercent, cost, price) {
            commissionPercent = parseInt(commissionPercent) || 0;
            if (costSetting == 0) {
                return cost;
            } else {
                return Math.round(price - (price * (commissionPercent / 100)));
            }
        }
        //Cal commission rate
        function calculateCommissionRate(cost, price) {
            cost = parseInt(cost) || 0;
            price = parseInt(price) || 0;
            if (price === 0) {
                return 0;
            }
            var rate =  Math.round((100 - (cost * 100 / price)));
            if(rate < 0) {
                rate = 0;
            }
            return rate;
        }

        //ROWS SHOWING FUNCTION
        function showig_rows_count(maxRows, pageNum, totalRows) {
            //Default rows showing
            var end_index = maxRows * pageNum;
            var start_index = ((maxRows * pageNum) - maxRows) + parseFloat(1);
            var string = 'Showing ' + start_index + ' to ' + end_index + ' of ' + totalRows + ' entries';
            $('.rows_count').html(string);
        }

        // CREATING INDEX
        function default_index() {
            $('table tr:eq(0)').prepend('<th> ID </th>')

            var id = 0;

            $('table tr:gt(0)').each(function() {
                id++
                $(this).prepend('<td>' + id + '</td>');
            });
        }

        jQ(document).ready(function() {
            jQ("body").on("keyup", "#variation-search, #swatch-search", function() {
                var tblId = DOMPurify.sanitize('#' + jQ(this).closest('form').find('table').attr('id') + '');
                var value = DOMPurify.sanitize(jQ(this).val().toLowerCase());
                jQ(tblId + " tbody tr").filter(function() {
                    jQ(this).toggle(jQ(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });

        //combination creator
        function combineArrays(array_of_arrays, isOld = false) {
            if (!array_of_arrays) {
                return [];
            }
            if (!Array.isArray(array_of_arrays)) {
                return [];
            }
            if (array_of_arrays.length == 0) {
                return [];
            }
            for (let i = 0; i < array_of_arrays.length; i++) {
                if (!Array.isArray(array_of_arrays[i]) || array_of_arrays[i].length == 0) {
                    return [];
                }
            }
            let odometer = new Array(array_of_arrays.length);
            odometer.fill(0);
            let output = [];
            let newCombination = formCombination(odometer, array_of_arrays);
            if (!isOld && jQuery.inArray(newCombination, output) != -1) {
                alert({ 'content': $t("Combination can not be created, don't use same option title for mutiple options.") });
                return;
            }
            output.push(newCombination);
            while (odometer_increment(odometer, array_of_arrays)) {
                newCombination = formCombination(odometer, array_of_arrays);
                if (!isOld && jQuery.inArray(newCombination, output) != -1) {
                    alert({ 'content': $t("Combination can not be created, don't use same option title for mutiple options.") });
                    return [];
                }
                output.push(newCombination);
            }
            return output;
        }

        function formCombination(odometer, array_of_arrays) {
            return odometer.reduce(
                function(accumulator, odometer_value, odometer_index) {
                    return "" + accumulator + array_of_arrays[odometer_index][odometer_value] + '_';
                },
                ""
            );
        }

        function odometer_increment(odometer, array_of_arrays) {

            for (let i_odometer_digit = odometer.length - 1; i_odometer_digit >= 0; i_odometer_digit--) {
                let maxee = array_of_arrays[i_odometer_digit].length - 1;

                if (odometer[i_odometer_digit] + 1 <= maxee) {
                    odometer[i_odometer_digit]++;
                    return true;
                } else {
                    if (i_odometer_digit - 1 < 0) {
                        return false;
                    } else {
                        odometer[i_odometer_digit] = 0;
                        continue;
                    }
                }
            }
        }

        //delete image
        $("body").on("click", ".data-grid-file-uploader .data-grid-file-uploader-inner .wkosi-image-delete", function() {
            $(this).parent('.wk-img-box').remove();
        });
        $("body").on('change', '#check', function() {
            $("input:checkbox").prop('checked', $(this).prop("checked"));
        });
        $("body").on('change', '#follow_simple_sku_cost_setting', function() {
            if ($(this).is(":checked")) {
                var cost_setting = $('input[name="product[cost_setting]"]:checked').val();
                var commission_percent = Math.round($('input[name="product[commission_percent]"]').val());
                var cost = $('input[name="product[cost]"]').val();
                $('.wkv-cost_setting').val(cost_setting).prop('disabled', true);
                $('.wkv-cost_setting-input').val(cost_setting);
                $('.wkv-commission_percent').val(commission_percent);
                $('.wkv-cost').val(cost);
                $('.wkv-follow_simple_sku_cost_setting').val(1);
                var costs = $('.wkv-cost');
                if(costs.length) {
                    for (var i = 0; i < costs.length; i++) {
                        var id = DOMPurify.sanitize($(costs[i]).data('id')?.toString());
                        setCostAndCommission(id);
                    }
                }
            } else {
                for (var i in formattedSaved) {
                    if(formattedSaved[i].hasOwnProperty('cost_setting')) {
                        $('#wkv-cost_setting-' + i).val(formattedSaved[i].cost_setting);
                        $('#wkv-cost_setting-input-' + i).val(formattedSaved[i].cost_setting);
                    }
                    if(formattedSaved[i].hasOwnProperty('commission_percent')) {
                        $('#wkv-commission_percent-' + i).val(formattedSaved[i].commission_percent);
                    }
                    if(formattedSaved[i].hasOwnProperty('cost')) {
                        $('#wkv-cost-' + i).val(formattedSaved[i].cost);
                    }
                }
                $('.wkv-follow_simple_sku_cost_setting').val(0);
                $('.wkv-cost_setting').prop('disabled', false).trigger('change');
            }
        });
        $("body").on('change', '#follow_simple_sku_price_setting', function() {
            if ($(this).is(":checked")) {
                var price = getProductPrice();
                $('.wkv-price').val(price).prop('readonly', true).trigger('change');
                $('.wkv-follow_simple_sku_price_setting').val(1);
            } else {
                for (var i in formattedSaved) {
                    if(formattedSaved[i].hasOwnProperty('price')) {
                        $('#wkv-price-' + i).val(formattedSaved[i].price).trigger('change');
                    }
                }
                $('.wkv-follow_simple_sku_price_setting').val(0);
                $('.wkv-price').prop('readonly', false);
            }
        });
    }
});

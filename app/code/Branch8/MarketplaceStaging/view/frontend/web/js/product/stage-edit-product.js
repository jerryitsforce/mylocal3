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
    'moment',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'plugins/DOMPurify',
    "jquery/ui",
    'mage/calendar'
], function ($, moment, $t, alert, DOMPurify) {
    'use strict';
    $.widget('mage.stageSellerEditProduct', {
        options: {
            errorMessageSku: $t("SKU can\'t be left empty"),
            ajaxErrorMessage: $t('There was error during fetching results.')
        },
        _create: function () {
            var jQ = $.noConflict();
            let self = this,
                form = jQ('#staging-product');
            /** Init event for editor */
            var editorFieldsStaging = {'note':'staging_note', 'specification':'staging_specification',
                'recommendation':'staging_recommendation', 'description':'staging_product_description'};
            var intervalEditorStaging = setInterval(function(){
                if(typeof tinymce != "undefined"){
                    $.each(editorFieldsStaging, function(field, elm){
                        $('#staging-product #'+elm).click(function(){
                            setUpdatedContent(field);
                        });
                        tinymce.get(elm).on('keyup', function(e) {
                            setUpdatedContent(field);
                       });
                    });
                    clearInterval(intervalEditorStaging);
                }
            }, 100);


            function setUpdatedContent(elm){
                let currentUpdatedFields = $('#staging-product #editor_updated_fields').val();
                if(currentUpdatedFields.trim() == ''){
                    $('#staging-product #editor_updated_fields').val(elm);
                }else{
                    let currentUpdatedFieldsObj = currentUpdatedFields.split(',');
                    if(!currentUpdatedFieldsObj.includes(elm)){
                        currentUpdatedFieldsObj[currentUpdatedFieldsObj.length] = elm;
                    }
                    $('#staging-product #editor_updated_fields').val(currentUpdatedFieldsObj.join(','));
                }
            }

            jQ('#staging-product #save-btn').click(function (e) {
                if (form.valid()!==false) {
                    $("#staging_product_options_container").find(':input:disabled').removeAttr('disabled');

                    function doSubmit(certJson) {
                        let actionUrl = form.attr('action'),
                            formData = form.serializeArray(),
                            index,
                            needReload = false;

                        // Explicitly inject certification data directly into formData,
                        // bypassing any DOM hidden input dependency entirely.
                        if (certJson !== undefined && certJson !== null) {
                            // Remove any pre-existing entry to avoid duplicates
                            formData = formData.filter(function(f) {
                                return f.name !== 'product[branch8_certifications_post]';
                            });
                            formData.push({name: 'product[branch8_certifications_post]', value: certJson});
                        }

                        for (index = 0; index < formData.length; ++index) {
                            if (formData[index].name == "staging[start_time]" && formData[index].value) {
                                formData[index].value = moment.tz(formData[index].value, "MM/DD/YYYY h:mm A", self.options.timezone).tz('UTC').toISOString();
                            }
                            if (formData[index].name == "staging[end_time]" && formData[index].value) {
                                formData[index].value = moment.tz(formData[index].value, "MM/DD/YYYY h:mm A", self.options.timezone).tz('UTC').toISOString();
                            }
                            if (formData[index].name == "staging[update_id]" && !formData[index].value) {
                                needReload = true;
                            }
                        }

                        form.parents('.modal-component').children('.admin__data-grid-loading-mask').show();
                        jQ.ajax({
                            type: "POST",
                            url: actionUrl,
                            data: formData, // serializes the form's elements.
                            success: function(data)
                            {
                                if (data) {
                                    if (data.ajaxExpired) {
                                        window.location.href = data.ajaxRedirect;
                                    }
                                    if (data.error) {
                                        const messageErr = $('<div class="message error">');
                                        form.parents('.modal-component').children('.admin__data-grid-loading-mask').hide();
                                        messageErr.text(data.messages);
                                        jQ('#staging-product .messages').html(messageErr);
                                    } else {
                                        form.find('.messages').html('<div class="message success">' + $t("This product has been successfully submitted for review. Please wait for the result.") + '</div>');
                                        form.parents('.modal-component').children('.admin__data-grid-loading-mask').hide();
                                        setTimeout(function(){
                                            // if (needReload) {
                                            //     window.location.reload();
                                            // } else {
                                            //     form.parents('.modal-inner-wrap').find('.action-close').trigger('click');
                                            // }
                                            /** Location to listing page after save schedule HTGO2-2922 */
                                            window.location.href='/marketplace/product/productlist/';
                                        }, 3000);
                                    }
                                }
                            },
                            error: function (response) {
                                form.parents('.modal-component').children('.admin__data-grid-loading-mask').hide();
                            }
                        });
                    }

                    // Build certification JSON from the KO component directly and pass
                    // it as a parameter to doSubmit — no DOM hidden input needed.
                    try {
                        require(['uiRegistry'], function(registry) {
                            var certJson = null;
                            var certPanel = registry.get('productCertificationPanel');
                            if (certPanel && certPanel.certifications) {
                                var output = {};
                                certPanel.certifications().forEach(function(cert) {
                                    if (cert.isChecked && cert.isChecked()) {
                                        output[String(cert.id)] = cert.value ? (cert.value() || '') : '';
                                    }
                                });
                                certJson = JSON.stringify(output);
                            }
                            doSubmit(certJson);
                        });
                    } catch(err) {
                        doSubmit(null);
                    }
                }
            });
            jQ('#staging-product #remove-btn').click(function (e) {
                if (form.valid()!==false) {
                    let actionUrl = jQ(this).attr('data-url'),
                        formData = form.serializeArray(),
                        index;

                    for (index = 0; index < formData.length; ++index) {
                        if (formData[index].name == "staging[start_time]" && formData[index].value) {
                            formData[index].value = moment.tz(formData[index].value, "MM/DD/YYYY h:mm A", self.options.timezone).tz('UTC').toISOString();
                        }
                        if (formData[index].name == "staging[end_time]" && formData[index].value) {
                            formData[index].value = moment.tz(formData[index].value, "MM/DD/YYYY h:mm A", self.options.timezone).tz('UTC').toISOString();
                        }
                        if (formData[index].name == "staging[mode]") {
                            formData[index].value = 'remove';
                        }
                    }
                    form.parents('.modal-component').children('.admin__data-grid-loading-mask').show();
                    jQ.ajax({
                        type: "POST",
                        url: actionUrl,
                        data: formData, // serializes the form's elements.
                        success: function(data)
                        {
                            if (data) {
                                if (data.error) {
                                    form.parents('.modal-component').children('.admin__data-grid-loading-mask').hide();
                                    const messageDiv = jQ('<div>').addClass('message error');
                                    messageDiv.text(data.messages);
                                    jQ('#staging-product .messages').html(DOMPurify.sanitize(messageDiv));
                                } else {
                                    const messageDiv = jQ('<div>').addClass('message success');
                                    messageDiv.text(data.messages);
                                    jQ('#staging-product .messages').html(DOMPurify.sanitize(messageDiv));
                                    sessionStorage.setItem('seller_product_saved_v3', '1');
                                    window.location.reload();
                                }
                            }
                        },
                        error: function (response) {
                            form.parents('.modal-component').children('.admin__data-grid-loading-mask').hide();
                        }
                    });
                }
            });
            $('#staging-product .input-text').change(function () {
                var validt = $(this).val();
                var regex = /(<([^>]+)>)/ig;
                var mainvald = validt .replace(regex, "");
                $(this).val(mainvald);
            });
            $('#staging-product input#sku').change(function () {
                var len=$('input#sku').val();
                var len2=len.length;
                if (len2 === 0) {
                    alert({
                        content: self.options.errorMessageSku
                    });
                    $('#staging-product div#skuavail').css('display','none');
                    $('#staging-product div#skunotavail').css('display','none');
                } else {
                    self.callVerifySkuAjaxFunction();
                }
            });
            if ($('#staging_use_config_is_returnable').val() == 1) {
                $('#staging_is_returnable').change(function () {
                    $('#staging_use_config_is_returnable').val('0');
                });
            }
        },
        callVerifySkuAjaxFunction: function () {
            var self = this;
            jQ.ajax({
                url: self.options.verifySkuAjaxUrl,
                type: "POST",
                data: {sku:jQ('input#sku').val(), product_id:self.options.productid},
                dataType: 'html',
                success:function ($data) {
                    $data=JSON.parse($data);
                    if ($data.avialability==1) {
                        $('#staging-product div#skuavail').css('display','block');
                        $('#staging-product div#skunotavail').css('display','none');
                    } else {
                        $('#staging-product div#skunotavail').css('display','block');
                        $('#staging-product div#skuavail').css('display','none');
                        $("#staging-product input#sku").attr('value','');
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
            jQ.ajax({
                url     :   self.options.categoryTreeAjaxUrl,
                type    :   "POST",
                data    :   {
                    parentCategoryId : thisthis.siblings("input").val(),
                    categoryIds :   self.options.categories
                },
                dataType:   "html",
                success :   function (content) {
                    var newdata=  JSON.parse(content);
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
    return $.mage.stageSellerEditProduct;
});

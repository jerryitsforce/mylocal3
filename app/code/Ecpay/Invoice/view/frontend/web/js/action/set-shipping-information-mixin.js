define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'plugins/DOMPurify',
    'Ecpay_Invoice/js/validator/tax-id-validator'
], function ($, wrapper, quote, DOMPurify, taxIdValidator) {
    'use strict';

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction) {

            // 重置欄位style及error msg
            $("#ecpay_invoice_love_code").removeAttr("style");
            $("#ecpay_invoice_customer_identifier").removeAttr("style");
            $("#ecpay_invoice_customer_company").removeAttr("style");
            $("#ecpay_invoice_carruer_num").removeAttr("style");
            $("#love_code_error").empty();
            $("#customer_identifier_error").empty();
            $("#customer_company_error").empty();
            $("#carruer_num_error").empty();

            var error = false;
            var jQ = $.noConflict();
            
            // 依照發票開立類型判斷需驗證欄位(個人p、公司c、捐贈d)
            var ecpay_invoice_type = $("#ecpay_invoice_type").val();
            switch (ecpay_invoice_type) {
                case 'd':
                    var ecpay_invoice_love_code = $("#ecpay_invoice_love_code").val();
                    // 未填寫捐贈碼
                    if (ecpay_invoice_love_code == '' || ecpay_invoice_love_code == null) {
                        addErrorMsg('love_code', 'This is a required field.')
                    }
                    else {
                        // 驗證捐贈碼
                        var data = {
                            'loveCode': ecpay_invoice_love_code
                        }
                        verifyAjax('love_code', 'rest/V1/ecpay_general/invoice/check_love_code', data);
                    }
                    break;
                case 'c':
                    var ecpay_invoice_customer_identifier = $("#ecpay_invoice_customer_identifier").val();
                    var ecpay_invoice_carruer_type = $("#ecpay_invoice_carruer_type").val();
                    var ecpay_invoice_company = $("#ecpay_invoice_customer_company").val();

                    // 未填寫公司統編
                    if (ecpay_invoice_customer_identifier == '' || ecpay_invoice_customer_identifier == null) {
                        addErrorMsg('customer_identifier', 'This is a required field.')
                    }
                    else {
                        // 先執行本地格式驗證（如果驗證器可用）
                        var shouldProceedToApi = true;
                        
                        if (taxIdValidator && typeof taxIdValidator.validate === 'function') {
                            try {
                                var validationResult = taxIdValidator.validate(ecpay_invoice_customer_identifier);
                                if (!validationResult.isValid) {
                                    // 本地驗證失敗，顯示錯誤並阻止提交
                                    addErrorMsg('customer_identifier', validationResult.message);
                                    shouldProceedToApi = false;
                                }
                            } catch (e) {
                                console.error('Error during tax ID validation:', e);
                                // 驗證失敗時繼續執行 API 驗證（降級處理）
                            }
                        } else {
                            console.warn('Tax ID validator module not loaded, proceeding with API validation only');
                        }
                        
                        if (shouldProceedToApi) {
                            // 本地驗證成功或未執行，繼續執行現有的 API 驗證
                            var data = {
                                'businessNumber': ecpay_invoice_customer_identifier
                            }
                            verifyAjax('customer_identifier', 'rest/V1/ecpay_general/invoice/check_business_number', data);
                        }
                    }

                    // 紙本發票未填寫公司行號
                    if (ecpay_invoice_carruer_type == '0' && (ecpay_invoice_company == '' || ecpay_invoice_company == null)) {
                        addErrorMsg('customer_company', 'This is a required field.')
                    }

                    // 載具類型選擇自然人憑證或手機條碼，未填寫載具編號
                    if (ecpay_invoice_carruer_type == '2' || ecpay_invoice_carruer_type == '3') {
                        var ecpay_invoice_carruer_num = $("#ecpay_invoice_carruer_num").val();
                        if (ecpay_invoice_carruer_num == '' || ecpay_invoice_carruer_num == null) {
                            addErrorMsg('carruer_num', 'This is a required field.')
                        }
                        else {
                            // 驗證載具自然人憑證(2)、手機條碼(3)
                            if (ecpay_invoice_carruer_type == '2') {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_citizen_digital_certificate';
                                var data = {
                                    'carrierNumber': ecpay_invoice_carruer_num
                                }
                            }
                            else {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_barcode';
                                var data = {
                                    'barcode': ecpay_invoice_carruer_num
                                }
                            }
                            verifyAjax('carruer_num', path_url, data);
                        }
                    }

                    break;
                default:
                    // 個人發票
                    var ecpay_invoice_carruer_type = $("#ecpay_invoice_carruer_type").val();
                    if (ecpay_invoice_carruer_type == '2' || ecpay_invoice_carruer_type == '3') {
                        // 個人發票選擇載具選項
                        var ecpay_invoice_carruer_num = $("#ecpay_invoice_carruer_num").val();
                        if (ecpay_invoice_carruer_num == '' || ecpay_invoice_carruer_num == null) {
                            addErrorMsg('carruer_num', 'This is a required field.')
                        }
                        else {
                            // 驗證載具自然人憑證(2)、手機條碼(3)
                            if (ecpay_invoice_carruer_type == '2') {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_citizen_digital_certificate';
                                var data = {
                                    'carrierNumber': ecpay_invoice_carruer_num
                                }
                            }
                            else {
                                var path_url = 'rest/V1/ecpay_general/invoice/check_barcode';
                                var data = {
                                    'barcode': ecpay_invoice_carruer_num
                                }
                            }
                            verifyAjax('carruer_num', path_url, data);
                        }
                    }
                    break;
            }

            // 驗證欄位Ajax
            function verifyAjax(field, url_path, data) {
                jQ.ajax({ 
                    type: 'POST',
                    url: window.BASE_URL + url_path,
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8',
                        'dataType': 'json',
                    },
                    async: false,
                    data: JSON.stringify(data),
                    error: function(response) {
                        var errorMsg = response.responseJSON;
                        addErrorMsg(DOMPurify.sanitize(field), DOMPurify.sanitize(errorMsg.message + ' (' + errorMsg.code + ')'))
                    },
                    success: function(response) {
                        if (response.code != '0999') {
                            addErrorMsg(DOMPurify.sanitize(field), DOMPurify.sanitize(response.msg + ' (' + response.code + ')'))
                        }
                    }
                });
            }

            // 顯示特定欄位錯誤訊息
            function addErrorMsg(field, msg) {
                $("#ecpay_invoice_" + field).addClass('mage-error');
                $("#ecpay_invoice_" + field).addClass('mage-error');
                if( $("#ecpay_invoice_" + field).next().hasClass('mage-error') == false ) {
                    jQ("#ecpay_invoice_" + field).after('<div generated="true" class="mage-error" id="' + field + '_error"><span>' + msg + '</span></div>');
                }
                $("#ecpay_invoice_" + field).focus();
                $("#" + field + "_error").empty();
                jQ("#" + field + "_error").append('<span>' + msg + '</span>');
                error = true;
            }

            if (error) return false;

            var result = originalAction();
            return result;
        });
    };
});
             
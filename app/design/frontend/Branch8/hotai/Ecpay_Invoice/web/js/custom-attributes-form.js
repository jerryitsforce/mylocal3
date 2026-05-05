define([
    'ko',
    'uiComponent',
    'jquery',
    'Magento_Customer/js/model/customer',
    'mage/url',
    'mage/validation',
    'Ecpay_Invoice/js/validator/tax-id-validator'
], function (ko, Component, $, customer, urlBuilder, validator, taxIdValidator) {
    'use strict';

    return Component.extend ({
        defaults: {
            // 是否顯示欄位、定義欄位預設值
            showCarruerType: ko.observable(true),
            showCustomerIdentifier: ko.observable(false),
            showCustomerCompany: ko.observable(false),
            showLoveCode: ko.observable(false),
            showCarruerNum: ko.observable(false),
            customer_identifier: ko.observable(''),
            customer_company: ko.observable(''),
            love_code: ko.observable(''),
            carruer_num: ko.observable(''),
            currentSelectedCarrierType: ko.observable('two_part_invoice'),
            hasMessage: ko.observable(false),
            invoiceMessage: ko.observable(''),
            invoice_carrier: ko.observable(''),
        },

        initialize: function () {
            this._super();
            this.is_logged_in = this.isLoggedIn();
            this.validation_fields = ["customer_company", "customer_identifier", "carruer_num", "love_code"];

            // 填入預設捐贈碼
            this.defaultLoveCode = window.checkoutConfig.default_love_code;

            // 定義目前載具類型及選項
            this.carruerTypes = ko.observableArray([
                { name: "Paper Invoice", value: "0", active: true },
                { name: "Cloud Invoice", value: "1", active: true },
                { name: "Natural Person Certificate", value: "2", active: true },
                { name: "Mobile Barcode", value: "3", active: true }
            ])

            // 定義目前發票類型及選項
            this.invoiceTypes = ko.observableArray(
                [
                    { name: "Individual", value: "p", active: true },
                    { name: "Company", value: "c", active: true },
                    { name: "Donation", value: "d", active: true }
                ]
            );

            // 輸入後即時移除錯誤欄位 error msg
            this.removeErrorMes = (field) => {
                $("#ecpay_invoice_" + field).removeAttr("style");
                $("#" + field + "_error").empty();
            }

            // 監聽 fields (公司行號、公司統編、載具編號、捐贈碼)
            this.customer_company.subscribe((newValue) => {
                this.removeErrorMes('customer_company');  
                this.resetMageError('customer_company');  
                const validate =  $.validator.validateSingleElement($("#ecpay_invoice_customer_company"));
            });
            
            this.customer_identifier.subscribe((newValue) => {
                this.resetMageError('customer_identifier');
                this.removeErrorMes('customer_identifier'); 
                $("#ecpay_invoice_customer_identifier").removeClass('invoice-api-error');
                
                // 先執行本地格式驗證
                if (newValue && newValue.length === 8) {
                    var validationResult = taxIdValidator.validate(newValue);
                    if (!validationResult.isValid) {
                        this.addMageError('customer_identifier', validationResult.message);
                        $('.checkout-submit-wrapper .checkout-payment-method.submit .actions-toolbar .action.checkout').attr('invoice-error', true);
                        return; // 驗證失敗，不執行 API 呼叫
                    }
                }
                
                // 本地驗證通過後，繼續執行原有的 Magento 驗證和 API 呼叫
                const validate =  $.validator.validateSingleElement($("#ecpay_invoice_customer_identifier"));
                if(validate) {
                    this.apiCheckBusinessNumber(newValue);
                }
            });

            this.carruer_num.subscribe((newValue) => {
                this.removeErrorMes('carruer_num');   
                this.resetMageError('carruer_num'); 
                const validate =  $.validator.validateSingleElement($("#ecpay_invoice_carruer_num"));
            });

            this.love_code.subscribe((newValue) => {
                this.removeErrorMes('love_code');    
            });

            this.currentSelectedCarrierType.subscribe((newValue) => {
                // $('.checkout-submit-wrapper .checkout-payment-method.submit .actions-toolbar .action.checkout').removeAttr('invoice-error');
            });

            this.defaultAction();

            return this;
        },

        resetMageError: function (field) {
            $("#ecpay_invoice_" + field).remove('mage-error');
            if( $("#ecpay_invoice_" + field).next().hasClass('mage-error')) {
                $("#ecpay_invoice_" + field).next().remove();
            }
        },
        
        addMageError: function (field, message) {
            $("#ecpay_invoice_" + field).addClass('mage-error');
            if( $("#ecpay_invoice_" + field).next().hasClass('mage-error')) {
                $("#ecpay_invoice_" + field).next().html(message);
            } else {
                $("#ecpay_invoice_" + field).after('<div generated="true" class="mage-error" id="' + field + '_error">' + message + '</div>');
            }
        },

        defaultAction: function () {
            this.currentCarruerType = ko.observable("0");
            this.currentInvoiceType = ko.observable("p");
            // this.currentCarruerType = ko.observable("3");
            // this.currentInvoiceType = ko.observable("p");
            this.processData();
        },

        processData: function () {
            // 重置元件
            this.refreshData();

            if(this.currentSelectedCarrierType() === "barcode_carrier") {
                this.showCarruerNum(true);
                this.hasMessage(false);
                this.invoiceMessage('');
            }

            if(this.currentSelectedCarrierType() === "two_part_invoice") {
                this.hasMessage(true);
                this.invoiceMessage('發票由綠界發票平台代管不寄送紙本發票，中獎時會由HOTAI購代為寄送紙本發票給您。辦理退貨時，由HOTAI購代為處理電子發票及銷貨退回證明單，以加速退貨退款作業。依統一發票使用辦法規定，個人發票一經開立，不得更改或改開公司戶發票');
            }

            if (this.currentSelectedCarrierType() === "three_part_invoice") {
                this.showCustomerIdentifier(true);
                this.showCustomerCompany(true);
                this.hasMessage(true);
                this.invoiceMessage('將於出貨後14天(超過鑑賞期)開取得發票，若需退貨退款，請填寫於退貨程序中下載折讓單，蓋用公司章並寄回台北市松江路433號12樓(和泰聯網股份有限公司)');
            }

            // check invoice type
            // switch (this.currentInvoiceType()) {
            //     case "c":
            //         this.showCustomerIdentifier(true);
            //         this.showCustomerCompany(true);
            //         if (this.currentCarruerType() === "0") {
            //             $('#ecpay_invoice_customer_company_div').addClass('_required')
            //         }
            //         else {
            //             $('#ecpay_invoice_customer_company_div').removeClass('_required')
            //         }
            //         break;
            //     case "d":
            //         this.showCustomerIdentifier(false);
            //         this.showCustomerCompany(false);
            //         this.showLoveCode(true);
            //         this.love_code(this.defaultLoveCode);
            //         break;
            //     default:
            //         this.showCustomerIdentifier(false);
            //         this.showCustomerCompany(false);
            //         break;
            // }

            // check carrier type
            // if (this.currentCarruerType() === "2" || this.currentCarruerType() === "3") {
            //     this.showCarruerNum(true)
            // } else {
            //     this.showCarruerNum(false)
            // }
        },
        
        selectInvoice: function (data, event) {
            this.currentSelectedCarrierType(event.target.value);  
            
            switch (event.target?.value) {
                // 4. Individual 個人 + Mobile Barcode 手機條碼 (3)
                // 1. Individual 個人 + Paper Invoice 索取紙本 (0),
                // 1. Company 公司 + Paper Invoice 索取紙本 (0)
                // case "member_carrier":
                //     this.currentCarruerType("3");
                //     this.currentInvoiceType("p");
                //     console.log("member_carrier");
                //     break;
                case "barcode_carrier":
                    this.currentInvoiceType("p");
                    this.currentCarruerType("3");
                    this.processData();
                    break;
                case "two_part_invoice":
                    this.currentInvoiceType("p");
                    this.currentCarruerType("0");
                    this.processData();
                    break;
                case "three_part_invoice":
                    this.currentInvoiceType("c");
                    this.currentCarruerType("0");
                    this.processData();
                    break;
            }
        },

        selectCarruerType: function (obj, event) {
            // 變更選定載具類型
            this.currentCarruerType(event.target.value);

            // 重置元件
            this.refreshData();

            // 公司發票
            if (this.currentInvoiceType() === "c") {
                this.showCustomerIdentifier(true);
                this.showCustomerCompany(true);
                // 紙本發票公司行號必填，載具非必填
                if (this.currentCarruerType() === "0") {
                    $('#ecpay_invoice_customer_company_div').addClass('_required')
                }
                else {
                    $('#ecpay_invoice_customer_company_div').removeClass('_required')
                }
            }

            // 顯示載具輸入框
            if (this.currentCarruerType() === "2" || this.currentCarruerType() === "3") {
                this.showCarruerNum(true)
            }
        },
        
        selectInvoiceType: function (obj, event) {
            // 變更選定InvoiceType
            this.currentInvoiceType(event.target.value);

            // 重置元件
            this.refreshData();

            // 更動顯示元件
            switch (this.currentInvoiceType()) {
                case "c":
                    this.showCustomerIdentifier(true);
                    this.showCustomerCompany(true);
                    this.showCarruerType(true);
                    // 重置載具選項、公司行號必填
                    $('#ecpay_invoice_carruer_type option:first').prop("selected", true)
                    $('#ecpay_invoice_customer_company_div').addClass('_required')
                    break;
                case "d":
                    this.showLoveCode(true);
                    this.showCarruerType(false);
                    this.love_code(this.defaultLoveCode);
                    break;
                default:
                    this.showCarruerType(true);
                    // 重置載具選項
                    $('#ecpay_invoice_carruer_type option:first').prop("selected", true)
            }
        },

        refreshData: function () {
            this.invoice_carrier(window.customerData?.custom_attributes?.invoice_carrier?.value || '');
            this.showCarruerType(true);
            this.showCustomerIdentifier(false);
            this.showCustomerCompany(false);
            this.showLoveCode(false);
            this.showCarruerNum(false);
            this.love_code('');
            this.customer_identifier('');
            this.customer_company('');
            this.carruer_num(this.invoice_carrier() || '');
            this.hasMessage(false);
            
            // 移除所有欄位 error msg
            // $.each(this.validation_fields, function(index, value) {
            //     $("#ecpay_invoice_" + value).removeAttr("style");
            //     $("#" + value + "_error").empty();
            // })
        },
        apiCheckBusinessNumber: function (identifier) {
            let self = this;
            let serviceUrl = urlBuilder.build('rest/V1/ecpay_general/invoice/check_business_number');
            $('.checkout-submit-wrapper .checkout-payment-method.submit .actions-toolbar .action.checkout').removeAttr('invoice-error');

            $.ajax({
                url: serviceUrl,
                type: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify({unifiedBusinessNo: identifier}),
                success: function (response) {
                    if (response && response.data) {
                        $("#ecpay_invoice_customer_company").val(response.data);
                        self.customer_company(response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('API 呼叫失敗:', error);
                    self.addMageError('customer_identifier', $.mage.__('Please check the Tax ID Number again and confirm the data is correct'));
                    $("#ecpay_invoice_customer_company")
                    $("#ecpay_invoice_customer_company").val('');
                    self.customer_company('');
                    $('.checkout-submit-wrapper .checkout-payment-method.submit .actions-toolbar .action.checkout').attr('invoice-error', true);
                }
            });
        },

        isLoggedIn: function () {
            return customer.isLoggedIn();
        }
    });
});
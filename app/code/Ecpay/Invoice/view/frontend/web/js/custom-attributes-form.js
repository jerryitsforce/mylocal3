define([
    'ko',
    'uiComponent',
    'jquery',
    'Magento_Customer/js/model/customer',
    'mage/url',
    'mage/storage',
    'Ecpay_Invoice/js/validator/tax-id-validator',
    'mage/translate'
], function (ko, Component, $, customer, urlBuilder, storage, taxIdValidator) {
    'use strict';

    return Component.extend ({
        initialize: function () {
            this._super();

            this.is_logged_in = this.isLoggedIn();
            this.validation_fields = ["customer_company", "customer_identifier", "carruer_num", "love_code"];

            // 填入預設捐贈碼
            this.defaultLoveCode = window.checkoutConfig.default_love_code;

            // 定義目前載具類型及選項
            this.currentCarruerType = ko.observable("Paper Invoice");
            this.carruerTypes = ko.observableArray([
                {name: "Paper Invoice", value: "0", active: true},
                {name: "Cloud Invoice", value: "1", active: true},
                {name: "Natural Person Certificate", value: "2", active: true},
                {name: "Mobile Barcode", value: "3", active: true}
            ])

            // 定義目前發票類型及選項
            this.currentInvoiceType = ko.observable("Individual");
            this.invoiceTypes = ko.observableArray(
                [
                    {name: "Individual", value: "p", active: true},
                    {name: "Company", value: "c", active: true},
                    {name: "Donation", value: "d", active: true}
                ]
            );

            // Input field observables
            this.customer_identifier = ko.observable('');
            this.customer_company = ko.observable('');
            this.love_code = ko.observable('');
            this.carruer_num = ko.observable('');

            this.customer_identifier.subscribe((newValue) => {
                if (newValue.length === 8) {
                    this.apiCheckBusinessNumber(newValue);
                }
            });

            // 為統一編號欄位新增 blur 事件驗證
            setTimeout(() => {
                $('#ecpay_invoice_customer_identifier').on('blur', () => {
                    const taxId = this.customer_identifier();
                    if (taxId && taxId.length > 0) {
                        this.validateTaxId(taxId);
                    }
                });
            }, 1000);

            // 輸入後即時移除錯誤欄位 error msg
            this.removeErrorMes = (field) => {
                $("#ecpay_invoice_" + field).removeAttr("style");
                $("#" + field + "_error").empty();
            }

            // 監聽 fields (公司行號、公司統編、載具編號、捐贈碼)
            this.customer_company.subscribe((newValue) => {
                this.removeErrorMes('customer_company');
            });
            this.customer_identifier.subscribe((newValue) => {
                this.removeErrorMes('customer_identifier');
            });
            this.carruer_num.subscribe((newValue) => {
                this.removeErrorMes('carruer_num');
            });
            this.love_code.subscribe((newValue) => {
                this.removeErrorMes('love_code');
            });

            return this;
        },
        apiCheckBusinessNumber: function (identifier) {
            let self = this;
            let serviceUrl = urlBuilder.build('rest/V1/ecpay_general/invoice/check_business_number');

            $.ajax({
                url: serviceUrl,
                type: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify({unifiedBusinessNo: identifier}),
                success: function (response) {
                    if (response && response.data) {
                        self.customer_company(response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('API 呼叫失敗:', error);
                }
            });
        },
        validateTaxId: function (taxId) {
            // 確保驗證器模組已載入（降級處理）
            if (!taxIdValidator || typeof taxIdValidator.validate !== 'function') {
                console.warn('Tax ID validator module not loaded, skipping client-side validation');
                return;
            }
            
            // 呼叫驗證器並處理結果
            try {
                const validationResult = taxIdValidator.validate(taxId);
                
                if (!validationResult.isValid) {
                    // 驗證失敗，顯示錯誤訊息
                    this.showTaxIdError(validationResult.message);
                } else {
                    // 驗證成功，移除錯誤提示
                    this.removeErrorMes('customer_identifier');
                }
            } catch (e) {
                console.error('Error during tax ID validation:', e);
                // 驗證失敗時不阻止使用者操作，讓後端API驗證處理
            }
        },
        showTaxIdError: function (message) {
            // 顯示紅色邊框
            $("#ecpay_invoice_customer_identifier").css("border", "1px solid red");
            
            // 顯示錯誤訊息
            $("#customer_identifier_error").html(
                '<div class="mage-error" generated="true">' + message + '</div>'
            );
        },
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
                } else {
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
            this.showCarruerType(true);
            this.showCustomerIdentifier(false);
            this.showCustomerCompany(false);
            this.showLoveCode(false);
            this.showCarruerNum(false);
            this.love_code('');
            this.customer_identifier('');
            this.customer_company('')

            if (customer.customerData.custom_attributes && customer.customerData.custom_attributes.invoice_carrier.value) {
                var invoiceCarrier = customer.customerData.custom_attributes.invoice_carrier.value;
                this.carruer_num(invoiceCarrier)
            } else {
                this.carruer_num('')
            }

            // 移除所有欄位 error msg
            $.each(this.validation_fields, function (index, value) {
                $("#ecpay_invoice_" + value).removeAttr("style");
                $("#" + value + "_error").empty();
            })
        },
        isLoggedIn: function () {
            return customer.isLoggedIn();
        }
    });
});

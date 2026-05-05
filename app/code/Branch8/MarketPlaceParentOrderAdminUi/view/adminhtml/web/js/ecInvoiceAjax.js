define([
    'jquery',
    'jquery/ui',
    'mage/translate'
], function ($, ui, $t) {
    'use strict';
    $.widget('mage.ecInvoiceAjax', {
        options: {
            baseUrl: {},
            encryptData: {},
            invoiceData: {},
            invoiceModuleEnable: {}
        },
        invoiceNo: '',
        invoiceDate: '',
        invoiceMode: '',
        invoiceRandomNum: '',
        invoiceType: '',
        invoiceIssueType: '',
        invoiceOdSob: '',
        invoiceCodes: '',
        invoiceInformation: '',
        invoiceMessage: '',
        invoiceMessageContent: '',
        /**
         *
         * @private
         */
        _create: function () {
            let self = this;
            this._super();
            this.invoiceNo = $(this.element).find('[data-role="invoice_no"]');
            this.invoiceDate = $(this.element).find('[data-role="invoice_date"]');
            this.invoiceRandomNum = $(this.element).find('[data-role="invoice_random_num"]');
            this.invoiceType = $(this.element).find('[data-role="invoice_type"]');
            this.invoiceIssueType = $(this.element).find('[data-role="invoice_issue_type"]');
            this.invoiceOdSob = $(this.element).find('[data-role="invoice_od_sob"]');
            this.invoiceCodes = $(this.element).find('[data-role="invoice_codes"]');
            this.invoiceInformation = $(this.element).find('[data-role="invoice_info"]');
            this.invoiceMessage = $(this.element).find('[data-role="invoice-msg"]');
            this.invoiceMessageContent = $(this.element).find('[data-role="invoice-msg-content"]');
            setTimeout(function () {
                self.invoiceAjax(
                    'rest/V1/ecpay_general/invoice/get_invoice_tag',
                    'GET',
                    'status',
                    self.success.bind(self)
                );
            }, 1000);
        },
        /**
         *
         */
        success: function (response, method) {

            if (response.code == '0999') {
                switch (method) {
                    case 'status':
                        if (response.data == '1') {
                            this.invoiceInformation.show();
                            this.appendInvoiceData(this.options.invoiceData);
                        } else {
                            this.invoiceInformation.hide();
                        }
                        break;
                    case 'create':
                        this.invoiceInformation.show();
                        this.appendInvoiceData(JSON.parse(response.data))
                        this.showMessage('success', response.msg)
                        break;
                    case 'invalid':
                        this.invoiceInformation.hide();
                        this.invoiceNo.text('')
                        this.invoiceDate.text('')
                        this.invoiceRandomNum.text('')
                        this.invoiceIssueType.text('')
                        this.invoiceOdSob.text('')
                        this.invoiceType.text('')
                        this.invoiceCodes.empty();
                        this.showMessage('success', response.msg)
                        break;
                }
            } else this.showMessage('error', response.msg)
        },
        /**
         *
         */
        showMessage: function (style_class, message) {
            this.invoiceMessage.removeClass('success', 'error');
            this.invoiceMessage.addClass(style_class);
            this.invoiceMessage.show();
            this.invoiceMessageContent.text(message)
        },
        /**
         *
         * @param invoice_data
         */
        appendInvoiceData: function (invoice_data) {

            this.invoiceIssueType.text(
                invoice_data.ecpay_invoice_issue_type == '1' ? $t('Invoicing') : $t('Delayed Invoicing')
            );
            this.invoiceNo.text(
                invoice_data.ecpay_invoice_number == null ? '' : invoice_data.ecpay_invoice_number
            );
            this.invoiceDate.text(
                invoice_data.ecpay_invoice_date == null ? '' : invoice_data.ecpay_invoice_date
            );
            this.invoiceRandomNum.text(
                invoice_data.ecpay_invoice_random_number == null ? '' : invoice_data.ecpay_invoice_random_number
            );
            this.invoiceOdSob.text(invoice_data.ecpay_invoice_od_sob);
            switch (invoice_data.ecpay_invoice_type) {
                case '公司':
                    this.invoiceType.text($t('公司'));
                    break;
                case '捐贈':
                    this.invoiceType.text($t('捐贈'));
                    break;
                default:
                    this.invoiceType.text($t('個人'));
                    break;
            }

            console.log({
                invoice_data:invoice_data
            })

            this.invoiceCodes.empty();
            //捐贈碼
            if (invoice_data.ecpay_invoice_love_code !== undefined && invoice_data.ecpay_invoice_love_code !== null) {
                this.invoiceCodes.append('<label>' + $t('Donation Code') + '：</label><span id="invoice_love_code"> ' + invoice_data.ecpay_invoice_love_code + '</span><br>')
            }
            // 公司行號
            if (invoice_data.ecpay_invoice_customer_company !== undefined && invoice_data.ecpay_invoice_customer_company !== null) {
                this.invoiceCodes.append('<label>' + $t('Company Name') + '：</label><span id="invoice_customer_company"> ' + invoice_data.ecpay_invoice_customer_company + '</span><br>')
            }
            //統一編號
            if (invoice_data.ecpay_invoice_customer_identifier !== undefined && invoice_data.ecpay_invoice_customer_identifier !== null) {
                this.invoiceCodes.append('<label>' + $t('Uniform Numbers') + '：</label><span id="invoice_customer_identifier"> ' + invoice_data.ecpay_invoice_customer_identifier + '</span><br>')
            }
            //載具編號
            if (invoice_data.ecpay_invoice_carruer_num !== undefined && invoice_data.ecpay_invoice_carruer_num !== null) {
                this.invoiceCodes.append('<label>' + $t('Carrier Code') + '：</label><span id="invoice_carruer_num"> ' + invoice_data.ecpay_invoice_carruer_num + '</span><br>')
            }
        },
        /**
         *
         * @param url_path
         * @param type
         * @param method
         */
        invoiceAjax: function (url_path, type, method, successCallback, errorCallBack) {
            var data = {
                'orderId': this.options.encryptData.order_id,
                'protectCode': this.options.encryptData.protect_code
            };
            if (type === 'POST') {
                data = JSON.stringify(data);
            }

            $.ajax({
                type: type,
                url: this.options.baseUrl + url_path,
                showLoader: false,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'dataType': 'json',
                },
                data: data,
                beforeSend: function (xhr) {
                    $('body').trigger('processStart');
                },
                error: function (response) {
                    $('body').trigger('processStop');
                    if (errorCallBack) {
                        errorCallBack(response, method);
                    }
                },
                success: function (response) {
                    $('body').trigger('processStop');
                    if (successCallback) {
                        successCallback(response, method);
                    }
                }
            });
        },

    });
    return $.mage.ecInvoiceAjax;
});

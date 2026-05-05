/**
 * HotaiConnected CheckoutManagement - 球池票券兌換報表下載
 * 
 * @module HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/table/actions/ticket-verification-details
 * @version 2.0.0
 * @author HotaiConnected
 * @created 2025-12-09
 * 
 * 功能說明：
 * - 下載球池票券兌換報表
 * - 執行前顯示確認對話框（使用模板）
 * - 使用 request.download() API，自動處理下載、loading、錯誤
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/template: Magento 模板引擎
 * - mage/url: Magento URL 建構器
 * - Magento_Ui/js/modal/confirm: Magento 確認對話框
 * - Magento_Ui/js/modal/alert: Magento 警告彈窗
 * - mage/translate: Magento 翻譯功能
 * - mage/validation: Magento 表單驗證
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiFileNameParts: 檔名組合工具（前綴為當下台北時區時間 + 名稱 + 後綴 + 分隔符）
 * - hotaiToastMessage: 訊息提示工具
 * - hotaiDatePickers: 日期選擇器封裝
 * 
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: API 調用服務
 * 
 * 模板 (本模組)
 * - text!HotaiConnected_CheckoutManagement/template/voucherreconciliationarea/table/actions/ball-pit-ticket-verification-details.html
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/template',
    'mage/url',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'mage/validation',

    // 共用模組 (來自 UiShared)
    'hotaiFileNameParts',
    'hotaiToastMessage',
    'hotaiDatePickers',
    'hotaiRequest',
    
    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',
    
    // 模板 (本模組)
    'text!HotaiConnected_CheckoutManagement/template/voucherreconciliationarea/table/actions/ticket-verification-details.html'
], function ($, mageTemplate, urlBuilder, confirm, alert, $t, validation, getFileNameParts, toastMessage, datePickers, request, reconciliationApi, ticketVerificationDetailsTemplate) {
    'use strict';

    return {
        options: {
            form: {
                id: '#ticket-verification-details-form',
                fromSelector: '#ticket_date_from',
                toSelector: '#ticket_date_to',
                searchButton: '#search-button',
                downloadButton: '#download-button',
                errorMessage: '.ticket-verification-details__download .error-msg span',
                className: 'ticket-verification-details',
                validationClass: 'validation-initialized',
                title: {
                    event: $.mage.__('Ball Pit Ticket Redemption Report'),
                    ticket: $.mage.__('2.0 Standard Ticket Redemption Report')
                }
            },
        },
        /**
         * 執行下載球池票券兌換報表
         * 
         * 使用新的 request.download() API，自動處理：
         * - Loading 動畫顯示
         * - 檔案下載觸發（檔名格式：YYYYMMDD_HHMMSS_球池票券兌換報表.xlsx）
         * - 成功/錯誤訊息提示
         * 
         * @param {Array} selectedData - 選中的資料
         * @param {string} actionType - 操作類型
         * @param {Object} context - 上下文對象，包含 tabulatorInstance 和 resetBatchActionSelect
         * 
         * @example
         * // 下載的檔案名稱範例：
         * // 20251029_143025_球池票券兌換報表.xlsx
         */
        execute: function(selectedData, actionType, context) {
            var self = this;

            var labelName = '';
            switch(actionType){
                case 'ticket':
                    labelName = $.mage.__('Ticket Establishment Date')
                    break;
                case 'event':
                    labelName = $.mage.__('Ball Pit Creation Date')
                    break;
            }

            // 使用 mageTemplate 渲染模板
            var content = mageTemplate(ticketVerificationDetailsTemplate, { 
                label: $.mage.__('%1 (From-To)').replace('%1', labelName),
                $t: $.mage.__ 
            });

            // 顯示確認對話框
            confirm({
                modalClass: 'ui-modal-form ' + self.options.form.className,
                title: self.options.form.title[actionType],
                content: content,
                buttons: [],
                actions: {
                    always: function() {
                        // 清除表單驗證狀態
                        $(self.options.form.id).removeData(self.options.form.validationClass);
                        
                        // 重置批量操作選擇
                        if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                            context.resetBatchActionSelect();
                        }
                    }
                },
                opened: function () {
                    var modal = this;
                    
                    // 初始化日期選擇器
                    datePickers.initDatePickers({
                        fromSelector: self.options.form.fromSelector,
                        toSelector: self.options.form.toSelector,
                        setDefaultValues: false,
                        enableValidation: true
                    });
                    
                    // 初始化表單驗證（避免重複初始化）
                    var form = $(self.options.form.id);
                    if (!form.data(self.options.form.validationClass)) {
                        form.validation();
                        form.data(self.options.form.validationClass, true);
                    }

                    // // 綁定搜尋按鈕點擊事件
                    // $(self.options.form.searchButton).off('click.ball-pit').on('click.ball-pit', function(e) {
                    //     e.preventDefault();
                        
                    //     if (!form.valid()) {
                    //         return;
                    //     }

                    //     // 取得日期範圍
                    //     var dateFrom = $(self.options.form.fromSelector).val();
                    //     var dateTo = $(self.options.form.toSelector).val();

                    //     // 執行下載（成功後才關閉對話框）
                    //     self.searchReport(selectedData, actionType, context, {
                    //         dateFrom: dateFrom + ' 00:00:00',
                    //         dateTo: dateTo + ' 23:59:59',
                    //         modal: modal
                    //     });

                    // });
                    
                    // 綁定下載按鈕點擊事件
                    $(self.options.form.downloadButton).off('click.ball-pit').on('click.ball-pit', function(e) {
                        e.preventDefault();
                        
                        if (!form.valid()) {
                            return;
                        }

                        // 取得日期範圍
                        var dateFrom = $(self.options.form.fromSelector).val();
                        var dateTo = $(self.options.form.toSelector).val();

                        // 執行下載（成功後才關閉對話框）
                        self.downloadReport(selectedData, actionType, context, {
                            dateFrom: dateFrom + ' 00:00:00',
                            dateTo: dateTo + ' 23:59:59',
                            modal: modal
                        });
                    });
                }
            });
        },

         /**
         * 執行下載報表
         * 
         * @param {Array} selectedData - 選中的資料
         * @param {string} actionType - 操作類型
         * @param {Object} context - 上下文對象
         * @param {Object} dateRange - 日期範圍對象，包含 dateFrom、dateTo 和 modal
         */
         searchReport: function(selectedData, actionType, context, dateRange) {

            var self = this;
            $(self.options.form.errorMessage).text('');

            var params = self.getTicketCheckoutParams(actionType, dateRange);
            params.current_page = 1
            params.page_size = 1
            
            // 調用 API（支援 Promise）
            reconciliationApi.getTicketCheckoutList(params, {
                showSuccessMessage: false,
                showErrorMessage: false
            })
            .done(function(response) {
                var isValid = response.success === true && response.total_record > 0;
                $(self.options.form.downloadButton).prop('disabled', !isValid);
                self.handleDoneResponse(response);
            })
            .fail(function() {
                // 錯誤訊息已由 request.download() 自動顯示
                // 失敗時不關閉對話框，讓用戶可以修正後重試
            })
            .always(function() {
                // 重置批量操作選擇
                if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        },

        /**
         * 執行下載報表
         * 
         * @param {Array} selectedData - 選中的資料
         * @param {string} actionType - 操作類型
         * @param {Object} context - 上下文對象
         * @param {Object} dateRange - 日期範圍對象，包含 dateFrom、dateTo 和 modal
         */
        downloadReport: function(selectedData, actionType, context, dateRange) {
            var self = this;

            // 以共用工具產生檔名（前綴為當下台北時區時間，名稱為依 actionType 的報表標題）
            var filename = getFileNameParts({
                name: self.options.form.title[actionType].replace(/\./g, '_')
            });

            var params = self.getTicketCheckoutParams(actionType, dateRange);
            
            // 調用 API（支援 Promise）
            reconciliationApi.exportTicketVerificationDetails(params, {
                // filename: filename,  // 例如：20251029_143025_球池票券兌換報表.xlsx
                showSuccessMessage: false,
                showErrorMessage: false
            })
            .done(function(response) {
                var { success, message } = response;
                if(!success) {
                    toastMessage.warning({
                        content: message
                    });
                }

                if(success) {
                    toastMessage.success({
                        content: message
                    });
                }
            })
            .fail(function() {
                // 錯誤訊息已由 request.download() 自動顯示
                // 失敗時不關閉對話框，讓用戶可以修正後重試
            })
            .always(function() {
                // 重置批量操作選擇
                if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        },
        getTicketCheckoutParams: function (type, dateRange){
            var params = {
                type: type,  // 球池票券類型
            }

            switch(type) {
                case 'ticket':
                    params.order_created_from = dateRange.dateFrom;
                    params.order_created_to = dateRange.dateTo;
                    break;
                case 'event':
                    params.event_created_from = dateRange.dateFrom;
                    params.event_created_to = dateRange.dateTo;
                    break;    
            }

            return params
        },
        handleDoneResponse: function(response) {
            var self = this;
            if(response.success === true && response.total_record > 0) {
                return;
            }

            var errorMessage = response.message;
            switch(response.message) {
                case 'No ticket data found for the provided date range.':
                    errorMessage = $.mage.__('No records found. Please refine your search.');
                    break;
            }

            $(self.options.form.errorMessage).text(errorMessage);
        }
    };
});


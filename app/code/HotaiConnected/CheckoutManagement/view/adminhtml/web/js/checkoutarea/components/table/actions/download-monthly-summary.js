/**
 * HotaiConnected CheckoutManagement - 下載月結摘要批量操作
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/download-monthly-summary
 * @version 3.3.0
 * @author HotaiConnected
 * @updated 2025-10-29 - 重構：使用新的 request.download() API（自動處理下載、loading、錯誤）
 * @updated 2024-10-23 - 修正：executeDownloadMonthlySummary 改為檔案下載方式（xlsx）；新增：executeDownloadMonthlySummary 方法（直接下載）
 * @updated 2024-10-16 - 改回使用 HTML 模板（維護性優先）
 * 
 * 功能說明：
 * - 顯示下載月結摘要對話框
 * - 使用 HTML 模板渲染表單（download-monthly-summary.html）
 * - 執行下載月結總表操作（XLSX 檔案下載）
 * - 日期範圍選擇
 * - 特約商名稱顯示
 * - 表單驗證和提交（依賴 jquery/validate + mage/validation，見下方說明）
 * - 自動處理：Loading 動畫、檔案下載、成功/錯誤提示
 * 
 * 驗證失效原因與修復：
 * - Admin 頁面若只依賴 mage/validation，可能尚未載入 jquery/validate，導致
 *   $.validator.methods['required'] 為 undefined，觸發 "Cannot read properties of undefined (reading 'call')"。
 * - 解法：本模組明確依賴 jquery/validate，確保在呼叫 $form.validation() / .valid() 前
 *   已註冊 required 等規則。
 * 
 * 主要方法：
 * - executeDownloadMonthlySummary: 執行下載月結總表 API 調用（直接下載 .xlsx 檔案）
 * - showDownloadMonthlySummaryPopup: 顯示下載月結總表對話框（日期區間 + 下載按鈕）
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/template: Magento 模板引擎
 * - mage/translate: Magento 翻譯功能
 * - mage/url: Magento URL 建構器
 * - jquery/validate: jQuery 驗證基底（提供 required 等 methods，admin 需明確依賴）
 * - mage/validation: Magento 表單驗證（解析 data-validate、.validation()）
 * - Magento_Ui/js/modal/alert: Magento 警告彈窗
 * - Magento_Ui/js/modal/confirm: Magento 確認彈窗
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiSelectedItemsDisplay: 選中項目顯示組件
 * - hotaiToastMessage: 訊息提示工具（支援 5 秒自動消失）
 * - hotaiDatePickers: 日期選擇器封裝
 * - hotaiFileNameParts: 檔名組合工具（前綴為當下台北時區時間 + 名稱 + 後綴 + 分隔符）
 * 
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: API 調用服務（包含 downloadMonthlySummary）
 * 
 * 模板 (本模組)
 * - text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/actions/download-monthly-summary.html
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/template',
    'mage/translate',
    'mage/url',
    'jquery/validate',  // 必須先載入，否則 admin 下 $.validator.methods['required'] 為 undefined
    'mage/validation',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    
    // 共用模組 (來自 UiShared)
    'hotaiSelectedItemsDisplay',
    'hotaiToastMessage',
    'hotaiDatePickers',
    'hotaiFileNameParts',

    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',
    
    // 模板 (本模組)
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/actions/download-monthly-summary.html'
], function ($, mageTemplate, $t, urlBuilder, jqueryValidate, validation, alert, confirm, selectedItemsDisplay, toastMessage, datePickers, getFileNameParts, api, downloadMonthlySummaryTemplate) {
    'use strict';

    return {

        /**
         * 執行下載月結總表 API 調用
         *
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 ID 陣列（例如：[14, 15]）
         * @param {Object} context - 上下文對象，包含 tabulatorInstance 和 resetBatchActionSelect
         * @param {string} [dateFrom] - 結帳日期起（來自 popup #monthly_summary_date_from）
         * @param {string} [dateTo] - 結帳日期訖（來自 popup #monthly_summary_date_to）
         * @param {Object} [modal] - Magento confirm 彈窗實例，下載完成後會呼叫 modal.closeModal() 關閉
         */
        executeDownloadMonthlySummary: function(rowData, selectedIds, context, dateFrom, dateTo) {
            var self = this;
            toastMessage.remove();

            // 以共用工具產生檔名（前綴為當下台北時區時間）
            var filename = getFileNameParts({ name: '月結總表' });

            var params = {
                invoice_from: dateFrom != null ? String(dateFrom).trim() + ' 00:00:00' : '',
                invoice_to: dateTo != null ? String(dateTo).trim() + ' 23:59:59' : '',
            };

            api.downloadMonthlySummary(params, {
                showSuccessMessage: false,
                showErrorMessage: true,
                showErrorMessage: $.mage.__('%1 download failed.').replace('%1', filename)
            })
            .done(function(response) {
                if (
                    response.hasOwnProperty('data')
                    && response.data.hasOwnProperty('success')
                    && !response.data.success
                ) {
                    toastMessage.warning({
                        content: response.data.hasOwnProperty('message') ? response.data.message : ''
                    });
                }
            })
            .fail(function() {
                // 錯誤訊息已由 request.download() 自動顯示，不關閉彈窗讓使用者可重試
            })
            .always(function() {
                if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        },

        /**
         * 顯示下載月結總表 popup 對話框（日期區間 + 下載按鈕）
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 ID 陣列
         * @param {Object} context - 上下文對象
         */
        showDownloadMonthlySummaryPopup: function(rowData, selectedIds, context) {
            var self = this;
            var content = mageTemplate(downloadMonthlySummaryTemplate, {
                $t: $.mage.__,
                rowData: rowData,
                selectedIds: selectedIds
            });

            confirm({
                modalClass: 'ui-modal-form download-monthly-summary-popup',
                title: $.mage.__('Download Monthly Summary'),
                content: content,
                buttons: [],
                // 右上角 X（closeBtn）按下時執行；需自行呼叫 this.closeModal() 才會關閉（關閉後 actions.always 仍會執行）
                opened: function () {
                    datePickers.initDatePickers({
                        fromSelector: '#monthly_summary_date_from',
                        toSelector: '#monthly_summary_date_to',
                        setDefaultValues: false,
                        enableValidation: true
                    });

                    var $form = $('#download-monthly-summary-form');
                    var $errorMsg = $form.find('.error-msg span');
                    var $btn = $('#download-monthly-summary-button');
                    $btn.prop('disabled', false);
                    $btn.off('click.monthlySummary').on('click.monthlySummary', function () {
                        $errorMsg.text('');
                        if (!$form.data('validation-initialized')) {
                            $form.validation();
                            $form.data('validation-initialized', true);
                        }
                        if ($form.valid()) {
                            var dateFrom = ($('#monthly_summary_date_from').val() || '').trim();
                            var dateTo = ($('#monthly_summary_date_to').val() || '').trim();
                            if (dateFrom === '' || dateTo === '') {
                                $errorMsg.text($.mage.__('Please select both From and To dates.'));
                                return;
                            }
                            self.executeDownloadMonthlySummary(rowData, selectedIds, context, dateFrom, dateTo);
                        }
                    });
                },
                actions: {
                    always: function () {
                        $('#download-monthly-summary-button').off('click.monthlySummary');
                        $('#download-monthly-summary-form').removeData('validation-initialized');
                        if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                            context.resetBatchActionSelect();
                        }
                    }
                }
            });
        }
    };
});

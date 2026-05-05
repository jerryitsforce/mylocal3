/**
 * HotaiConnected CheckoutManagement - 下載廠商對帳單批量操作
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/download-vendor-statement
 * @version 2.0.0
 * @author HotaiConnected
 * @updated 2025-10-29 - 重構：使用新的 request.download() API（自動處理下載、loading、錯誤）
 * @updated 2024-10-23 - 修正：使用 <a> 標籤 download 屬性代替 window.location.href，初始版本：獨立處理下載廠商對帳單操作
 * 
 * 功能說明：
 * - 執行下載廠商對帳單操作（ZIP 檔案下載）
 * - 調用 /rest/V1/reconciliation/ecpay-seller-revenue-detail API
 * - 返回 application/zip 文件
 * - 自動處理：Loading 動畫、檔案下載、成功/錯誤提示
 * - 重置批量操作選擇狀態
 * 
 * 主要方法：
 * - executeDownloadVendorStatement: 執行下載廠商對帳單 API 調用（直接下載 ZIP）
 * - showDownloadVendorStatementPopup: 顯示下載廠商對帳單對話框（popup 內容由 template 提供）
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/template: Magento 模板引擎
 * - mage/translate: Magento 翻譯功能
 * - mage/validation: Magento 表單驗證（下載前驗證日期必填）
 * - Magento_Ui/js/modal/confirm: Magento 確認彈窗
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiFileNameParts: 檔名組合工具（前綴為當下台北時區時間 + 名稱 + 後綴 + 分隔符）
 * - hotaiToastMessage: 訊息提示工具（支援 5 秒自動消失）
 * - hotaiDatePickers: 日期選擇器封裝（popup 內 #vendor_statement_date_from / #vendor_statement_date_to）
 * 
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: API 調用服務（包含 downloadVendorStatement）
 * 
 * 模板 (本模組)
 * - text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/actions/download-vendor-statement-popup.html
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',

    // 共用模組 (來自 UiShared，按優先級排序，同優先級 A-Z)
    'hotaiFileNameParts',
    'hotaiToastMessage',
    'hotaiLoadingMask',

    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',
], function ($, getFileNameParts, toastMessage, loadingMask, api) {
    'use strict';

    return {
        /**
         * 執行下載廠商對帳單操作（ZIP 檔案下載，直接下載不經 popup）
         *
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 ID 陣列（例如：[14, 15]）
         * @param {Object} context - 上下文對象，包含 tabulatorInstance 和 resetBatchActionSelect
         */
        executeDownloadVendorStatementForOnlyId: function(rowData, selectedIds, context) {
            var self = this;
            toastMessage.remove();

            // 以共用工具產生檔名（前綴為當下台北時區時間）
            var filename = getFileNameParts({ name: '廠商對帳單' });

            // 調用 API（支援 Promise）
            api.downloadVendorStatement(selectedIds, {
                // filename: filename,  // 例如：20251029_143025_廠商對帳單.zip
                showSuccessMessage: false,
                showErrorMessage: true,
                showErrorMessage: $.mage.__('%1 download failed.').replace('%1', filename)  // 關閉自動錯誤提示，由此處自行處理
            })
            .done(function(response) {
                console.log('下載完成', response);
                // 如果 response 有 success 屬性，且為 false，則代表失敗/例外狀況，顯示後端定義 message 訊息
                if(
                    response.hasOwnProperty('data')
                    && response.data.hasOwnProperty('success')
                    && !response.data.success
                ) {
                    toastMessage.error({
                        content: response.data.hasOwnProperty('message') ? response.data.message : ''
                    });
                }
            })
            .fail(function(xhr, status, error) {
                // HTTP 錯誤（404, 500 等）
            })
            .always(function() {
                // 重置批量操作選擇
                if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        },
        /**
         * 執行下載廠商對帳單操作（ZIP 檔案下載，直接下載不經 popup）
         *
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 ID 陣列（例如：[14, 15]）
         * @param {Object} context - 上下文對象，包含 tabulatorInstance 和 resetBatchActionSelect
         */
        executeDownloadVendorStatementForMultipleIds: function(rowData, selectedIds, context) {
            var self = this;
            toastMessage.remove();

            // 以共用工具產生檔名（前綴為當下台北時區時間）
            var filename = getFileNameParts({ name: '廠商對帳單' });

            // 調用 API（支援 Promise）
            api.downloadEcpaySellerRevenueJobs({ids: selectedIds}, {
                // filename: filename,  // 例如：20251029_143025_廠商對帳單.zip
                showSuccessMessage: false,
                showErrorMessage: true,
                showErrorMessage: $.mage.__('%1 download failed.').replace('%1', filename)  
            })
            .done(function(response) {
                console.log('downloadEcpaySellerRevenueJobs response:', response);
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
            .fail(function(error) {
                // HTTP 錯誤（404, 500 等）
            })
            .always(function() {
                // 重置批量操作選擇
                if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        },
    };
});


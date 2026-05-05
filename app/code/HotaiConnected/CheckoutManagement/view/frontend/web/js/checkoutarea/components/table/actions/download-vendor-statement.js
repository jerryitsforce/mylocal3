/**
 * HotaiConnected MarketplaceCheckout - 下載廠商對帳單批量操作
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
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/translate: Magento 翻譯功能
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiFileNameParts: 檔名組合工具（前綴為當下台北時區時間 + 名稱 + 後綴 + 分隔符）
 * - hotaiToastMessage: 訊息提示工具（支援 5 秒自動消失）
 * 
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: API 調用服務（包含 downloadVendorStatement）
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate',

    // 共用模組 (來自 UiShared，按優先級排序，同優先級 A-Z)
    'hotaiFileNameParts',
    'hotaiToastMessage',

    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function ($, $t, getFileNameParts, toastMessage, api) {
    'use strict';

    return {
        /**
         * 執行下載廠商對帳單操作（ZIP 檔案下載）
         * 
         * 使用新的 request.download() API，自動處理：
         * - Loading 動畫顯示
         * - 檔案下載觸發（檔名格式：YYYYMMDD_HHmmss_廠商對帳單.zip）
         * - 成功訊息提示
         * 
         * 響應處理：
         * - 二進制文件（ZIP）: 自動下載，顯示成功訊息
         * - JSON 響應（200 + JSON）: 根據 response.success 判斷業務邏輯
         *   - success: false → 顯示錯誤訊息（如：No export files were generated.）
         *   - success: true → 顯示成功訊息
         * - HTTP 錯誤（4xx, 5xx）: 顯示通用錯誤訊息
         * 
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 ID 陣列（例如：[14, 15]）
         * @param {Object} context - 上下文對象，包含 tabulatorInstance 和 resetBatchActionSelect
         * 
         * @example
         * // 下載的檔案名稱範例：
         * // 20251029_143025_廠商對帳單.zip
         */
        executeDownloadVendorStatement: function(rowData, selectedIds, context) {
            var self = this;

            // 以共用工具產生檔名（前綴為當下台北時區時間）
            var filename = getFileNameParts({ name: '廠商對帳單' });

            // 調用 API（支援 Promise）
            api.downloadVendorStatement(selectedIds, {
                showSuccessMessage: false,
                showErrorMessage: true,
                showErrorMessage: $.mage.__('%1 download failed.').replace('%1', filename)  // 關閉自動錯誤提示，由此處自行處理
            })
            .done(function(response) {
                console.log('下載完成', response);
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
        }
    };
});


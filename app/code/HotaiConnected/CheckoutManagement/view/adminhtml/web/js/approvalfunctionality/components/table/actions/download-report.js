/**
 * HotaiConnected CheckoutManagement - 下載例外授權報表批量操作
 *
 * @module HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/table/actions/download-report
 * @version 1.0.0
 * @author HotaiConnected
 *
 * 功能說明：
 * - 勾選例外授權紀錄後，執行「下載報表」批量操作
 * - 檔名以共用工具 getFileNameParts 產生（前綴為當下台北時區時間 + 名稱「例外授權表單」）
 * - 呼叫 exception-auth API 匯出並觸發下載
 *
 * 主要方法：
 * - execute: 執行下載例外授權報表（依選取 ID 呼叫 API 匯出並下載）
 *
 * 引用檔案：
 *
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/translate: Magento 翻譯功能
 *
 * 共用模組 (來自 UiShared)
 * - hotaiFileNameParts: 檔名組合工具（前綴/名稱/後綴 + 分隔符，前綴可為當下台北時區時間）
 * - hotaiToastMessage: 訊息提示工具（支援 5 秒自動消失）
 *
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/exception-auth: API 調用服務（包含 exportExceptionAuth）
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate',

    // 共用模組 (來自 UiShared)
    'hotaiFileNameParts',
    'hotaiToastMessage',

    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/exception-auth'
], function (
    $,
    $t,
    getFileNameParts,
    toastMessage,
    exceptionAuthApi
) {
    'use strict';

    return {
        /**
         * 下載例外授權報表
         *
         * @param {Array<Object>} selectedData
         * @param {Array<string|number>} selectedIds
         * @param {Object} context
         */
        execute: function(selectedData, selectedIds, context) {
            var self = this;
            if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
                toastMessage.warning($.mage.__('Please select at least one %1.', $.mage.__('Record')));
                return;
            }

            // 以共用工具產生檔名（前綴為當下台北時區時間）
            var filename = getFileNameParts({ name: '例外授權表單' });

            exceptionAuthApi.exportExceptionAuth(selectedIds, {
                filename: filename,
                showSuccessMessage: true,
                successMessage: $.mage.__('%1 downloaded successfully.').replace('%1', filename),
                showErrorMessage: true,
                showErrorMessage: $.mage.__('%1 download failed.').replace('%1', filename)
            })
            .done(function() {
                console.log('下載完成');
            })
            .fail(function(error) {
                console.error('Download report failed', error);
            }).always(function() {
                if (context && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        }
    };
});


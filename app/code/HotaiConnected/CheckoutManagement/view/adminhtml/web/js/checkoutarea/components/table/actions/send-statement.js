/**
 * HotaiConnected CheckoutManagement - 寄出對帳單批量操作
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/send-statement
 * @version 1.0.0
 * @author HotaiConnected
 * @updated 2024-10-23 - 初始版本：獨立處理寄出對帳單操作
 * 
 * 功能說明：
 * - 執行寄出對帳單操作
 * - 調用 /rest/V1/reconciliation/sellers API
 * - 顯示 Toast 訊息提示（5 秒自動消失）
 * - 重新載入表格資料
 * - 重置批量操作選擇狀態
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/translate: Magento 翻譯功能
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiLoadingMask: 全螢幕載入動畫
 * - hotaiToastMessage: 訊息提示工具（支援 5 秒自動消失）
 * 
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: API 調用服務（包含 sendStatement）
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate',
    
    // 共用模組 (來自 UiShared，按優先級排序，同優先級 A-Z)
    'hotaiLoadingMask',
    'hotaiToastMessage',
    
    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function ($, $t, loadingMask, toastMessage, api) {
    'use strict';

    return {
        /**
         * 執行寄出對帳單操作
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 ID
         * @param {Object} context - 上下文對象，包含 tabulatorInstance 和 resetBatchActionSelect
         */
        executeSendStatement: function(rowData, selectedIds, context) {
            loadingMask.show();
            
            // 將 ID 轉換為數字陣列
            var ids = selectedIds.map(function(id) {
                return parseInt(id);
            });
            
            api.sendStatement({
                ids: ids
            }).done(function(response) {
                toastMessage.success({
                    content: $.mage.__('%1 successfully.').replace('%1', $.mage.__('Send Statement')),
                    autoClose: true,
                    duration: 5000
                });
                
                // 重新載入表格資料（保留篩選條件）
                if (context && typeof context.updateTabulator === 'function') {
                    context.updateTabulator();
                } else if (context.tabulatorInstance) {
                    context.tabulatorInstance.setData();
                }
            }).fail(function(error) {
                console.error('sendStatement error', error);
                
                toastMessage.error({
                    content: $.mage.__('%1 failed.').replace('%1', $.mage.__('Send Statement')),
                    autoClose: true,
                    duration: 5000
                });
            }).always(function() {
                loadingMask.hide();
                
                // 重置批量操作選擇
                if (context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        }
    };
});


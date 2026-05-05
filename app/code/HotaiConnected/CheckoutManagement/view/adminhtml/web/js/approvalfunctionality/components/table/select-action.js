/**
 * HotaiConnected CheckoutManagement - Select Action Controller
 * 
 * 功能說明：
 * - 批量操作執行處理
 * - 支援多種操作類型（批次操作、檔案下載）
 * - 選中項目驗證
 * - Toast 訊息顯示
 * - 批量操作選擇器重置
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiToastMessage: 訊息提示工具
 * 
 * 事件機制
 * - 透過 jQuery document 觸發下列事件供頁面主控制器處理：
 *   - approval:batch-approve
 *   - approval:batch-reject
 *   - approval:download-report
 * 
 * @module HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/table/select-action
 * @version 3.0.0
 * @author HotaiConnected
 * @updated 2024-10-23 - 重構：所有批量操作都使用獨立的 execute 方法，移除 handleDownloadAction；將寄出對帳單邏輯獨立為 send-statement.js
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    
    // 共用模組 (來自 UiShared)
    'hotaiToastMessage'
], function ($, toastMessage) {
    'use strict';

    return {
        /**
         * 執行批量操作
         * @param {string} actionType - 操作類型
         * @param {Array} selectedData - 選中的完整資料陣列（來自 tabulator.getSelectedData()）
         * @param {Array} selectedIds - 選中的 ID 陣列
         * @param {Object} context - 上下文物件（包含 provider 等）
         */
        executeAction: function(actionType, selectedData, selectedIds, context) {
            var self = this;

            // 根據 actionType 執行不同的處理邏輯
            switch (actionType) {

                // 批次審核（同意）
                case 'batch_approve':
                    this.triggerActionEvent('approval:batch-approve', selectedData, selectedIds, context);
                    break;

                // 批次審核（拒絕）
                case 'batch_reject':
                    this.triggerActionEvent('approval:batch-reject', selectedData, selectedIds, context);
                    break;

                // 下載報表
                case 'download_report':
                    this.triggerActionEvent('approval:download-report', selectedData, selectedIds, context);
                    break;

                default:
                    console.warn('Unhandled action type:', actionType);
                    toastMessage.error($.mage.__('Unknown action type.'));
                    self.resetBatchActionSelect();
                    break;
            }
        },

        triggerActionEvent: function(eventName, selectedData, selectedIds, context) {
            $(document).trigger(eventName, [selectedData, selectedIds, context]);
        },

        resetBatchActionSelect: function() {
            $('#batch-action-select').val('');
        }
    };
});
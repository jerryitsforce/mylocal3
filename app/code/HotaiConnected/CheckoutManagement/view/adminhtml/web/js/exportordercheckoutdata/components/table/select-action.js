/**
 * HotaiConnected CheckoutManagement - Select Action Controller
 * 
 * 功能說明：
 * - 批量操作執行處理
 * - Toast 訊息顯示（未知操作類型時）
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
 * @author HotaiConnected
 * @version 1.0.0
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
         * @param {Object} context - 上下文物件（包含 tabulatorInstance、resetBatchActionSelect、updateTabulator 等）
         */
        executeAction: function(actionType, selectedData, selectedIds, context) {
            // 根據 actionType 執行不同的處理邏輯
            switch (actionType) {
                default:
                    console.warn('Unhandled action type:', actionType);
                    toastMessage.error($.mage.__('Unknown action type.'));
                    this.resetBatchActionSelect();
                    break;
            }
        },

        /**
         * 重置批量操作選擇器
         */
        resetBatchActionSelect: function() {
            $('#batch-action-select').val('');
        }
    };
});


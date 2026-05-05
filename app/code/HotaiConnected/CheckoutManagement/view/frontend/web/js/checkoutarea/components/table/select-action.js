/**
 * HotaiConnected MarketplaceCheckout - Select Action Controller
 * 
 * 功能說明：
 * - 批量操作執行處理
 * - 支援多種操作類型（下載、解鎖、授權等）
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
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/download-vendor-statement: 下載廠商對帳單模組
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/select-action
 * @version 3.0.0
 * @author HotaiConnected
 * @updated 2024-10-23 - 重構：所有批量操作都使用獨立的 execute 方法，移除 handleDownloadAction；將寄出對帳單邏輯獨立為 send-statement.js
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    
    // 共用模組 (來自 UiShared)
    'hotaiToastMessage',
    
    // 業務模組 (本模組，按優先級排序，同優先級 A-Z)
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/download-vendor-statement'
], function ($, toastMessage, downloadVendorStatement) {
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

                // 下載廠商對帳單
                case 'download_vendor_statement':
                    downloadVendorStatement.executeDownloadVendorStatement(selectedData, selectedIds, context);
                    break;

                default:
                    console.warn('Unhandled action type:', actionType);
                    toastMessage.error($.mage.__('Unknown action type.'));
                    self.resetBatchActionSelect();
                    break;
            }
        },

        resetBatchActionSelect: function() {
            $('#batch-action-select').val('');
        }
    };
});


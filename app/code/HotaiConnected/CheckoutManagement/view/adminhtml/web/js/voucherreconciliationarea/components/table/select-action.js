/**
 * HotaiConnected CheckoutManagement - Select Action Controller
 * 
 * 功能說明：
 * - 批量操作執行處理（票券對帳區域）
 * - 支援多種操作類型：
 *   - ball_pit: 球池票券兌換報表
 *   - standard_2.0: 2.0標準票券兌換報表
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
 * - HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/table/actions/ticket-verification-details: 票券兌換報表
 * 
 * @module HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/table/select-action
 * @version 2.0.0
 * @author HotaiConnected
 * @created 2025-12-09
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    
    // 共用模組 (來自 UiShared)
    'hotaiToastMessage',
    
    // 業務模組 (本模組，按優先級排序，同優先級 A-Z)
    'HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/table/actions/ticket-verification-details'
], function ($, toastMessage, ticketVerificationDetails) {
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
            var self = this;

            // 根據 actionType 執行不同的處理邏輯
            switch (actionType) {

                // 一般票券兌換報表
                case 'event':
                // 2.0標準票券兌換報表
                case 'ticket':
                    ticketVerificationDetails.execute(selectedData, actionType, context);
                    break;

                default:
                    console.warn('Unhandled action type:', actionType);
                    toastMessage.error($.mage.__('Unknown action type.'));
                    self.resetBatchActionSelect();
                    break;
            }
        },

        /**
         * 重置批量操作選擇器
         */
        resetBatchActionSelect: function() {
            $('#report-type-select').val('');
        }
    };
});






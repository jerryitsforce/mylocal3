/**
 * HotaiConnected CheckoutManagement - 共用對帳系統 API 模組
 *
 * @module HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-11-13
 *
 * 職責：
 * - 集中管理對帳系統（rest/V1/reconciliation）相關的 REST 端點
 * - 提供純粹的 HTTP 呼叫，不處理頁面邏輯或 UI 流程
 * - 供 checkoutarea 頁面使用
 *
 * API 端點：
 * - GET rest/V1/reconciliation/status - 取得對帳狀態資料
 * - GET rest/V1/reconciliation/sellers - 查詢特約商（對帳系統）
 * - POST rest/V1/marketplace/reconciliation/ecpay-seller-revenue-detail/:id - 下載廠商對帳單（ZIP）
 */
define([
    'jquery',
    'hotaiRequest'
], function ($, request) {
    'use strict';
    var BASE_URL = 'rest/V1/marketplace/reconciliation';
    return {
        // 統一管理 API 端點，避免分散硬編碼
        endpoints: {
            base: `${BASE_URL}`,
            vendorStatement: `${BASE_URL}/ecpay-seller-revenue-detail`,
        },
        // ==========================================
        // Reconciliation API (rest/V1/reconciliation/*)
        // ==========================================

        /**
         * 下載廠商對帳單（Excel）
         * POST rest/V1/marketplace/reconciliation/ecpay-seller-revenue-detail/:id
         *
         * @param {Array<string|number>} ids 要下載的記錄 ID 陣列（只使用第一個 ID）
         * @param {Object} [options] 下載選項（filename、showSuccessMessage 等）
         * @returns {jQuery.Deferred}
         */
        downloadVendorStatement: function(ids, options) {
            var opts = options || {};
           return request.download(`${this.endpoints.vendorStatement}/${ids[0]}`, $.extend({
                method: 'POST',
            }, opts));
        }
    };
});
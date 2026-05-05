/**
 * HotaiConnected CheckoutManagement - 訂單結帳匯出 API 服務
 *
 * @module HotaiConnected_CheckoutManagement/js/shared/services/api/ecpay-order-logs
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-12-09
 *
 * 職責：
 * - 集中管理訂單結帳匯出（rest/V1/ecpay-order-logs）相關的 REST 端點
 * - 提供純粹的 HTTP 呼叫，不處理頁面邏輯或 UI 流程
 * - 供 exportordercheckoutdata 頁面使用
 *
 * API 端點：
 * - GET rest/V1/ecpay-order-logs/list - 取得訂單結帳匯出列表
 * - GET rest/V1/ecpay-order-logs/export - 下載訂單結帳匯出報表
 */
define([
    'jquery',
    'hotaiRequest'
], function ($, request) {
    'use strict';

    var BASE_URL = '/rest/V1/ecpay-order-logs';

    return {
        // 統一管理 API 端點，避免分散硬編碼
        endpoints: {
            list: `${BASE_URL}/list`,
            export: `${BASE_URL}/export`
        },
        // ==========================================
        // Ecpay Order Logs API (rest/V1/ecpay-order-logs/*)
        // ==========================================

        /**
         * 取得訂單結帳匯出列表
         * GET rest/V1/ecpay-order-logs/list
         *
         * @param {Object} [params={}] 查詢參數
         * @param {string} [params.ecpay_start_date] 開始日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} [params.ecpay_end_date] 結束日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} [params.seller_code] 特約商代號
         * @param {number} [params.page_size=20] 每頁筆數
         * @param {number} [params.current_page=1] 當前頁碼
         * @param {Object} [options] 其他請求選項
         * @returns {jQuery.Deferred}
         *
         * @example
         * api.getEcpayOrderLogsList({
         *     ecpay_start_date: '2024-06-01 00:00:00',
         *     ecpay_end_date: '2025-10-31 23:59:59',
         *     seller_code: 'HTC01',
         *     page_size: 20,
         *     current_page: 1
         * }).done(function(response) {
         *     console.log('列表資料:', response);
         * });
         */
        getEcpayOrderLogsList: function(params = {}, options) {
            return request.get(this.endpoints.list, params, options);
        },

        /**
         * 下載訂單結帳匯出報表
         * GET rest/V1/ecpay-order-logs/export
         *
         * @param {Object} [params={}] 查詢參數
         * @param {string} [params.ecpay_start_date] 開始日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} [params.ecpay_end_date] 結束日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} [params.seller_code] 特約商代號
         * @param {Object} [options] 其他請求選項
         * @returns {jQuery.Deferred}
         *
         * @example
         * api.exportEcpayOrderLogs({
         *     ecpay_start_date: '2024-06-01 00:00:00',
         *     ecpay_end_date: '2025-10-31 23:59:59',
         *     seller_code: 'A0008'
         * }, {
         *     method: 'GET'
         * }).done(function() {
         *     console.log('下載完成');
         * });
         */
        exportEcpayOrderLogs: function(params = {}, options) {
            var opts = options || {};
            return request.get(this.endpoints.export, params, opts);
        }
    };
});
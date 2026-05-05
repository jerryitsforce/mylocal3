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
 * - 供 checkoutarea、approvalfunctionality 等頁面共用
 */
define([
    'jquery',
    'hotaiRequest'
], function ($, request) {
    'use strict';
    var BASE_URL = '/rest/V1/reconciliation';
    return {

        // 統一管理 API 端點，避免分散硬編碼
        endpoints: {
            base: `${BASE_URL}`,
            status: `${BASE_URL}/status`,
            sellers: `${BASE_URL}/sellers`,
            deleteCheckout: `${BASE_URL}/delete`,
            invoices: `${BASE_URL}/invoices`,
            vendorStatement: `${BASE_URL}/ecpay-seller-revenue-detail`,
            monthlySummary: `${BASE_URL}/ecpay-seller-revenue-summarize/all`,
            exceptionAuth: `${BASE_URL}/exception-auth`,
            ticketExport: `${BASE_URL}/ticket/export`,
            ticketList: `${BASE_URL}/ticket/list`,
            ecpaySellerRevenueJobs: `${BASE_URL}/ecpay-seller-revenue-jobs`,
        },
        // ==========================================
        // Reconciliation API (rest/V1/reconciliation/*)
        // ==========================================

        /**
         * 取得對帳狀態資料
         * GET rest/V1/reconciliation/status
         *
         * @param {Object} [params={}] 查詢參數（可選）
         * @returns {jQuery.Deferred}
         */
        getReconciliationStatusData: function(params = {}, options) {
            return request.get(this.endpoints.status, params, options);
        },

        /**
         * 查詢特約商（對帳系統）
         * GET rest/V1/reconciliation/sellers
         *
         * @param {Object} [params={}] 查詢參數（可選）
         * @returns {jQuery.Deferred}
         */
        getReconciliationSellers: function(params = {}, options) {
            return request.get(this.endpoints.sellers, params, options);
        },

        /**
         * 查詢對帳單批次
         * GET rest/V1/reconciliation
         *
         * @param {Object} [params={}] 查詢參數（page_size、current_page、batch_num 等）
         * @returns {jQuery.Deferred}
         */
        getReconciliationBatches: function(params = {}, options) {
            if (params.hasOwnProperty('invoice_number')) {
                params.invoice_number = params.invoice_number.trim().toUpperCase();
            }
            return request.get(this.endpoints.base, params, options);
        },

        /**
         * 建立月結帳批次
         * POST rest/V1/reconciliation
         *
         * @param {Object} data 月結帳批次資料（from、to、seller_code、batch_num 等）
         * @returns {jQuery.Deferred}
         */
        createMonthlyBatch: function(params = {}, options) {
            return request.post(this.endpoints.base, params, options);
        },

        /**
         * 解除結帳
         * POST rest/V1/reconciliation/delete
         *
         * @param {Object} data 解除結帳資料（ids: Array<string|number>）
         * @returns {jQuery.Deferred}
         */
        deleteCheckout: function(params = {}, options) {
            return request.post(this.endpoints.deleteCheckout, params, options);
        },

        /**
         * 新增廠商發票
         * POST rest/V1/reconciliation/invoices
         *
         * @param {Object} data 廠商發票資料（id、invoices: Array<string>）
         * @returns {jQuery.Deferred}
         */
        createVendorInvoice: function(params = {}, options) {
            return request.post(this.endpoints.invoices, params, options);
        },

        /**
         * 刪除廠商發票
         * DELETE rest/V1/reconciliation/invoices
         *
         * @param {Object} data 廠商發票資料（id、invoices: Array<string>）
         * @returns {jQuery.Deferred}
         */
        deleteVendorInvoice: function(params = {}, options) {
            return request.delete(this.endpoints.invoices, params, options);
        },

        /**
         * 更新廠商發票
         * PUT rest/V1/reconciliation/invoices
         *
         * @param {Object} data 廠商發票資料（id、invoices: Array<string>）
         * @returns {jQuery.Deferred}
         */
        updateVendorInvoice: function(params = {}, options) {
            return request.put(this.endpoints.invoices, params, options);
        },

        /**
         * 寄出對帳單
         * POST rest/V1/reconciliation/sellers
         *
         * @param {Object} data 對帳單資料（ids: Array<number>）
         * @returns {jQuery.Deferred}
         */
        sendStatement: function(params = {}, options) {
            return request.post(this.endpoints.sellers, params, options);
        },

        /**
         * 下載廠商對帳單（ZIP）
         * GET rest/V1/reconciliation/ecpay-seller-revenue-detail
         *
         * @param {Array<string|number>} ids 要下載的記錄 ID 陣列
         * @param {Object} [options] 下載選項（filename、showSuccessMessage 等）
         * @returns {jQuery.Deferred}
         */
        downloadVendorStatement: function(ids, options) {
            var opts = options || {};
            var baseFilename = opts.filename || '廠商對帳單';
            var finalFilename = baseFilename.toLowerCase().endsWith('.zip')
                ? baseFilename
                : baseFilename + '.zip';

            var headers = request.ajaxHeader();
            headers['Content-Type'] = 'application/zip';
            return request.download(this.endpoints.vendorStatement, {
                ids: ids.join(',')
            }, $.extend({
                method: 'GET',
                // filename: finalFilename,
                headers: headers
            }, opts));
        },

        /**
         * 下載月結總表（XLSX）
         * GET rest/V1/reconciliation/ecpay-seller-revenue-summarize
         *
         * @param {Array<string|number>} ids 要下載的記錄 ID 陣列
         * @param {Object} [options] 下載選項（filename、showSuccessMessage 等）
         * @returns {jQuery.Deferred}
         */
        downloadMonthlySummary: function(params = {}, options) {
            var opts = options || {};
            var baseFilename = opts.filename || '月結總表';
            var finalFilename = baseFilename.toLowerCase().endsWith('.xlsx')
                ? baseFilename
                : baseFilename + '.xlsx';

            return request.download(this.endpoints.monthlySummary, params, $.extend({
                method: 'GET',
                // filename: finalFilename
            }, opts));
        },

        /**
         * 取得對帳批次用的例外授權名單
         * GET rest/V1/reconciliation/exception-auth?ids=1,2,3
         *
         * @param {Array<string|number>|string} ids 批次 ID 欄位（支援陣列或字串）
         * @param {Object} [options] 其他請求選項
         * @returns {jQuery.Deferred}
         */
        getBatchExceptionAuth: function(ids, options) {
            var queryIds = Array.isArray(ids) ? ids.join(',') : String(ids || '');

            return request.get(this.endpoints.exceptionAuth, {
                ids: queryIds
            }, options);
        },

        /**
         * 上傳例外授權資料
         * POST rest/V1/reconciliation/exception-auth
         *
         * @param {Object|FormData} data 例外授權資料（id、file、reason 等）
         * @param {Object} [options] 請求選項（透傳至 hotaiRequest）
         * @returns {jQuery.Deferred}
         */
        uploadExceptionAuth: function(params = {}, options) {
            return request.post(this.endpoints.exceptionAuth, params, options);
        },

        /**
         * 下載票券兌換報表
         * GET rest/V1/reconciliation/ticket/export
         *
         * @param {Object} params 請求參數
         * @param {string} params.order_created_from 開始日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} params.order_created_to 結束日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} params.ticket_type 票券類型（'event' 為球池票券，'ticket' 為一般票券）
         * @param {Object} [options] 其他請求選項
         * @returns {jQuery.Deferred}
         */
        exportTicketVerificationDetails: function(params, options) {
            var opts = options || {};
            return request.get(this.endpoints.ticketExport, params, opts);
        },

        /**
         * 票券結帳匯出列表
         * GET rest/V1/reconciliation/ticket/list
         *
         * @param {Object} params 查詢參數
         * @param {string} params.order_created_from 開始日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} params.order_created_to 結束日期（格式：YYYY-MM-DD HH:mm:ss）
         * @param {string} params.ticket_type 票券類型（'event' 為球池票券/活動票券，'ticket' 為一般票券）
         * @param {number} [params.page_size=50] 每頁筆數
         * @param {number} [params.current_page=1] 當前頁碼
         * @returns {jQuery.Deferred}
         */
        getTicketCheckoutList: function(params, options) {
            var queryParams = $.extend({
                page_size: 50
            }, params || {});

            return request.get(this.endpoints.ticketList, queryParams, options);
        },

        /**
         * 下載廠商對帳單批次
         * GET rest/V1/reconciliation/ecpay-seller-revenue-jobs
         *
         * @param {Array<string|number>} ids 要下載的記錄 ID 陣列
         * @param {Object} [options] 下載選項（filename、showSuccessMessage 等）
         * @returns {jQuery.Deferred}
         */
        downloadEcpaySellerRevenueJobs: function(params = {}, options) {
            return request.post(this.endpoints.ecpaySellerRevenueJobs, params, options);
        }
    };
});


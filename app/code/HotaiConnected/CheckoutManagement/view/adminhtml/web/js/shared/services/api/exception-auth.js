/**
 * HotaiConnected CheckoutManagement - 例外授權 API 服務
 *
 * @module HotaiConnected_CheckoutManagement/js/shared/services/api/exception-auth
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-12-09
 *
 * 職責：
 * - 集中管理例外授權（rest/V1/exception-auth）相關的 REST 端點
 * - 提供純粹的 HTTP 呼叫，不處理頁面邏輯或 UI 流程
 *
 * API 端點：
 * - GET rest/V1/exception-auth - 取得例外授權名單
 * - POST rest/V1/exception-auth - 更新例外授權狀態
 * - POST rest/V1/exception-auth/export - 匯出例外授權名單
 */
define([
    'jquery',
    'hotaiRequest'
], function ($, request) {
    'use strict';

    var BASE_URL = '/rest/V1/exception-auth';

    return {
        // 統一管理 API 端點，避免分散硬編碼
        endpoints: {
            base: `${BASE_URL}`,
            export: `${BASE_URL}/export`
        },
        /**
         * 取得例外授權名單
         * GET rest/V1/exception-auth
         *
         * @param {Object} [params] 查詢參數（可選）
         * @param {Object} [options] 其他請求選項
         * @returns {jQuery.Deferred}
         */
        getExceptionAuthList: function(params, options) {
            return request.get(this.endpoints.base, params || {}, options);
        },

        /**
         * 更新例外授權狀態
         * POST rest/V1/exception-auth
         *
         * @param {Object} params 請求資料（status、ids 等）
         * @param {Object} [options] 請求選項
         * @returns {jQuery.Deferred}
         */
        updateExceptionAuthStatus: function(params, options) {
            return request.post(this.endpoints.base, params, options);
        },

        /**
         * 匯出例外授權名單
         * POST rest/V1/exception-auth/export
         *
         * @param {Array<number|string>} ids 匯出資料的 ID 陣列
         * @param {Object} [options] 下載選項（filename、showSuccessMessage 等）
         * @returns {jQuery.Deferred}
         */
        exportExceptionAuth: function(ids, options) {
            var payload = {
                ids: Array.isArray(ids) ? ids : []
            };
            var opts = $.extend({
                method: 'POST'
            }, options);
            var baseFilename = opts.filename || '例外授權報表';
            var finalFilename = baseFilename.toLowerCase().endsWith('.xlsx')
                ? baseFilename
                : baseFilename + '.xlsx';

            // opts.filename = finalFilename;

            return request.download(this.endpoints.export, payload, opts);
        }
    };
});

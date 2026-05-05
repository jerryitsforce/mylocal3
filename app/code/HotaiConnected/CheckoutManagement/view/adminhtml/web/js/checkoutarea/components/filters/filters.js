/**
 * HotaiConnected CheckoutManagement - 結帳區域篩選器組件
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/filters/filters
 * @version 5.0.0
 * @author HotaiConnected
 * @updated 2025-12-09 - 重構：移除共用功能至 UiShared，只保留業務特定邏輯
 * 
 * ========================================
 * 組件職責（業務特定）
 * ========================================
 * 
 * 此組件專注於業務特定的篩選器邏輯：
 * 
 * ✅ 負責：
 * - 特約商選擇器初始化（從 API 獲取）
 * - 狀態資料初始化（票券、物流、發票狀態）
 * - 狀態顯示名稱查詢
 * 
 * ❌ 不負責（已移至 UiShared）：
 * - 事件綁定 → hotaiFiltersBase.bindEvents()
 * - SumoSelect 初始化 → hotaiFiltersBase.initSumoSelect()
 * - 日期選擇器初始化 → hotaiFiltersBase.initDatePickers()
 * - 表單驗證 → hotaiFiltersBase.initFormValidation()
 * - 篩選器可見性控制 → hotaiFiltersBase.initFilterVisibility()
 * - 表單重置 → hotaiFiltersBase.resetForm()
 * - 表單資料收集 → hotaiFiltersBase.getFormData()
 * 
 * ========================================
 * 公開 API（供主控制器調用）
 * ========================================
 * 
 * 1. initialize()
 *    - 初始化篩選器 UI（入口方法）
 *    - 調用共用基礎方法 + 業務特定方法
 * 
 * 2. getFormData()
 *    - 取得當前篩選條件（繼承自 hotaiFiltersBase）
 * 
 * 3. resetForm()
 *    - 重置表單狀態（繼承自 hotaiFiltersBase）
 * 
 * 4. hideFilters()
 *    - 隱藏篩選器（繼承自 hotaiFiltersBase）
 * 
 * 5. getStatusDisplayName(type, code)
 *    - 取得狀態的顯示名稱（業務特定）
 * 
 * 引用檔案：
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiFiltersBase: 篩選器基礎組件（UiShared）
 * 
 * 本模組 (CheckoutManagement)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: 對帳系統 API
 */
define([
    // Magento 內建功能
    'jquery',
    
    // 共用模組 (來自 UiShared)
    'hotaiFiltersBase',
    
    // 本模組 API
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function ($, filtersBase, api) {
    'use strict';

    // 合併共用基礎功能和業務特定功能
    return $.extend({}, filtersBase, {
        // 預設值
        defaultValues: {
            text: '-',
        },
        // 多選資料物件
        data: {
            authorizedDealer: [],
            orderStatus: [],
            invoiceStatus: [],
            ticketStatus: [],
            sales_person: []
        },
        
        // 📌 狀態映射表（用於快速查找 code → displayName）
        // 在 initStatusData 時建立，提供 O(1) 查找效能
        _statusMaps: {
            ticket: {},      // { code: zh_name }
            invoice: {},
            shipping: {},
            sales_person: {}
        },

        /**
         * 模組初始化
         */
        initialize: function() {
            // 調用共用基礎方法
            this.bindEvents();
            this.initSumoSelect();
            this.initDatePickers();
            this.initFormValidation();
            this.initFilterVisibility();
            
            // 業務特定的初始化方法
            this.initAuthorizedDealerSelector();
            this.initStatusData();
        },

        /**
         * 初始化特約商選擇器（從 API 獲取）
         */
        initAuthorizedDealerSelector: function() {
            var self = this;

            api.getReconciliationSellers().done(function(data) {
                if(!Array.isArray(data.items) || data.items.length === 0) {
                    return;
                }

                // 儲存資料
                self.data.authorizedDealer = data.items;

                // 更新選擇器資料 - 特約商名稱 / 特約商代號
                // API 資料格式: [{ seller_id, shop_title, seller_code }]
                var authorizedDealerName = [];
                var authorizedDealerCode = [];
                self.data.authorizedDealer.forEach(function(item) {
                    // 特約商名稱
                    authorizedDealerName.push({
                        id: item.seller_code,
                        // 顯示優先級為 shop_title > seller_code > seller_id
                        // text: [item.shop_title, item.seller_code, item.seller_id].filter(Boolean)[0] || ''
                        text: item.shop_title || self.defaultValues.text
                    });
                    // 特約商代號
                    authorizedDealerCode.push({
                        id: item.seller_code,
                        text: item.seller_code || self.defaultValues.text
                    });
                });

                self.updateSelectData('shop_title', authorizedDealerName);
                self.updateSelectData('seller_code', authorizedDealerCode);
            
            }).fail(function(response) {
                console.error('initAuthorizedDealerSelector 失敗', response);
            });
        },

        /**
         * 初始化狀態資料（從 API 獲取）
         */
        initStatusData: function() {
            var self = this;
            
            api.getReconciliationStatusData().done(function(response) {
                // 儲存資料
                self.data.orderStatus = response.order_status;      // 訂單狀態
                self.data.invoiceStatus = response.invoice_status;  // 發票狀態
                self.data.ticketStatus = response.ticket_status;    // 票券狀態
                self.data.sales_person = response.sales_person;     // 館長姓名
                
                // 更新 - 票券狀態 select
                // API 資料格式: [{ code, en_name, zh_name }]
                if (self.data.ticketStatus && self.data.ticketStatus.length > 0) {
                    var ticketOptions = self.data.ticketStatus.map(function(item) {
                        var displayName = item.zh_name || item.en_name || self.defaultValues.text;
                        
                        // 📌 同時建立映射表（用於 table 快速查找）
                        self._statusMaps.ticket[item.code] = displayName;
                        
                        return {
                            id: item.code,
                            text: displayName
                        };
                    });
                    self.updateSelectData('ticket_status', ticketOptions, { selectAll: true });
                }
                
                // 更新 - 商品物流狀態 select (使用訂單狀態資料)
                // API 資料格式: ["pending", "processing", "complete", ...]
                if (self.data.orderStatus && self.data.orderStatus.length > 0) {
                    var shippingOptions = self.data.orderStatus.map(function(status) {
                        var displayName = $.mage.__(status || self.defaultValues.text);  // 使用 Magento 翻譯功能
                        
                        // 📌 同時建立映射表（用於 table 快速查找）
                        self._statusMaps.shipping[status] = displayName;
                        
                        return {
                            id: status,
                            text: displayName
                        };
                    });
                    self.updateSelectData('shipping_status', shippingOptions, { selectAll: true });
                }

                // 更新 - 發票狀態 select
                // API 資料格式: [{ code, en_name, zh_name }]
                if (self.data.invoiceStatus && self.data.invoiceStatus.length > 0) {
                    var invoiceOptions = self.data.invoiceStatus.map(function(item) {
                        var displayName = item.zh_name || item.en_name || self.defaultValues.text;
                        
                        // 📌 同時建立映射表（用於 table 快速查找）
                        self._statusMaps.invoice[item.code] = displayName;
                        
                        return {
                            id: item.code,
                            text: displayName
                        };
                    });
                    self.updateSelectData('invoice_status', invoiceOptions, { selectAll: true });
                }

                // 更新 - 館長姓名 select
                // API 資料格式: [{ role_id, role_name }]
                if (self.data.sales_person && self.data.sales_person.length > 0) {
                    var salesPersonOptions = self.data.sales_person.map(function(item) {

                        // 📌 同時建立映射表（用於 table 快速查找）
                        self._statusMaps.sales_person[item.role_id] = item.role_name;

                        return {
                            id: item.role_id,
                            text: item.role_name || self.defaultValues.text
                        };
                    });

                    self.updateSelectData('sales_person', salesPersonOptions, { selectAll: true });
                }

            }).fail(function(response) {
                console.error('initStatusData 失敗', response);
            });
        },
        
        /**
         * 取得狀態的顯示名稱（統一查詢介面）
         * 
         * @param {string} type - 狀態類型：'ticket', 'invoice', 'shipping'
         * @param {string} code - 狀態代碼
         * @returns {string} 顯示名稱（中文），若找不到則返回原始 code
         * 
         * @example
         * filterForm.getStatusDisplayName('ticket', 'issued')  // 返回：「已開立」
         * filterForm.getStatusDisplayName('invoice', 'paid')   // 返回：「已付款」
         */
        getStatusDisplayName: function(type, code) {
            return this._statusMaps[type][code] || code;
        }
    });
});

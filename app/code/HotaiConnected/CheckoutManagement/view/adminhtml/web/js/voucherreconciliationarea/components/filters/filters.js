/**
 * HotaiConnected CheckoutManagement - 票券對帳區域篩選器組件
 * 
 * @module HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/filters/filters
 * @version 2.0.0
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
 * - 業務特定的初始化邏輯
 * - 業務特定的資料處理
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
            orderStatus: [],
            ticketStatus: [],
            shop_title: []
        },
        
        // 📌 狀態映射表（用於快速查找 code → displayName）
        // 在 initStatusData 時建立，提供 O(1) 查找效能
        _statusMaps: {
            ticket: {},      // { code: zh_name }
            shipping: {},
            shop_title: {}
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
            this.initStatusData();
        },

        /**
         * 初始化狀態資料（從 API 獲取）
         */
        initStatusData: function() {
            var self = this;
            
            api.getReconciliationStatusData().done(function(response) {
                // 儲存資料
                self.data.orderStatus = response.order_status;      // 訂單狀態
                self.data.ticketStatus = response.ticket_status;    // 票券狀態
                self.data.shop_title = response.shop_title;         // 門市名稱
                
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
                
                // 更新 - 訂單狀態 / 商品物流狀態 select (使用訂單狀態資料)
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
                    self.updateSelectData('order_status', shippingOptions, { selectAll: true });
                }

                // 門市名稱
                if (self.data.shop_title && self.data.shop_title.length > 0) {
                    var shopTitleOptions = self.data.shop_title.map(function(item) {
                        var displayName = item.shop_title || self.defaultValues.text;
                        
                        // 📌 同時建立映射表（用於 table 快速查找）
                        self._statusMaps.shop_title[item.seller_id] = displayName;
                        
                        return {
                            id: item.seller_id,
                            text: displayName
                        };
                    });
                    self.updateSelectData('shop_title', shopTitleOptions, { selectAll: true });
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

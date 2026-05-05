/**
 * HotaiConnected MarketplaceCheckout - 結帳區域篩選器組件
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
 * 引用檔案：
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiFiltersBase: 篩選器基礎組件（UiShared）
 * 
 * 本模組 (MarketplaceCheckout)
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
            invoiceStatus: [],
            ticketStatus: []
        },
        
        // 📌 狀態映射表（用於快速查找 code → displayName）
        // 在 initStatusData 時建立，提供 O(1) 查找效能
        _statusMaps: {
            ticket: {},      // { code: zh_name }
            invoice: {},
            shipping: {}
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
          
        }
    });
});

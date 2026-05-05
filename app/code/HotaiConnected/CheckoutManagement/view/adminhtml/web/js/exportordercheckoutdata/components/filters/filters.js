/**
 * HotaiConnected CheckoutManagement - 匯出訂單結帳資料篩選器組件
 * 
 * @module HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/components/filters/filters
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
 * - 特約商選擇器初始化（從 API 獲取）
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
        // 異步初始化元件狀態
        componentInitFlags: {
            initAuthorizedDealer: false
        },

        // 多選資料物件
        data: {
            authorizedDealer: [],
            sales_person: []
        },

        // 📌 狀態映射表（用於快速查找 code → displayName）
        // 在 initStatusData 時建立，提供 O(1) 查找效能
        _statusMaps: {
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

            if (self.componentInitFlags.initAuthorizedDealer) {
                return;
            }

            api.getReconciliationSellers().done(function(data) {
                if (!Array.isArray(data.items) || data.items.length === 0) {
                    return;
                }

                // 儲存資料
                self.data.authorizedDealer = data.items;

                // 更新選擇器資料
                // API 資料格式: [{ seller_id, shop_title, seller_code }]
                var authorizedDealerOptions = self.data.authorizedDealer.map(function(item) {
                    return {
                        id: item.seller_code,
                        text: item.shop_title || self.defaultValues.text
                    };
                });
                
                self.updateSelectData('shop_title', authorizedDealerOptions);
                self.componentInitFlags.initAuthorizedDealer = true;
            
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
                self.data.sales_person = response.sales_person;     // 館長姓名

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
        
    });
});

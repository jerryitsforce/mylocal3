/**
 * @fileoverview 對帳區域列表頁主控制器
 * 
 * 此模組負責管理對帳管理列表頁的主要功能，包括：
 * - Tabulator 表格的初始化和管理
 * - 篩選器的顯示/隱藏切換
 * - 批量操作的處理（下載、解鎖等）
 * - 載入動畫的控制
 * - 事件監聽器的綁定和管理
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/index
 * @version 4.0.0
 * @author HotaiConnected
 * @created 2024-10-23
 * @updated 2024-10-23 - 重構使用 tabulatorColumnControl 共用組件
 * 
 * @example
 * // 在 index.phtml 中使用
 * require(['HotaiConnected_CheckoutManagement/js/checkoutarea/index'], function(checkoutAreaIndex) {
 *     checkoutAreaIndex.initialize();
 * });
 * 
 * 依賴關係：
 * 
 * Magento 內建功能 (按優先級排序)
 * @requires {jQuery} jquery                           - jQuery 核心庫，用於 DOM 操作和事件處理
 * @requires {Object} mage/translate                   - Magento 翻譯功能，用於多語言支援（間接使用 $.mage.__()）
 * @requires {Object} Magento_Ui/js/modal/alert        - Magento 警告彈窗，用於錯誤訊息顯示
 * @requires {Object} domReady!                        - RequireJS 插件，確保 DOM 載入完成後執行
 * 
 * 載入套件 (按優先級排序，同優先級 A-Z) - 來自 UiShared
 * @requires {Object} tabulator                        - Tabulator 表格庫（UiShared）
 * 
 * 自訂模組 - 共用資源（來自 UiShared，按優先級排序，同優先級 A-Z）
 * @requires {Object} hotaiLoadingMask                 - 全螢幕載入動畫（UiShared）
 * @requires {Object} hotaiToastMessage                - Toast 訊息提示（UiShared）
 * @requires {Object} tabulatorColumnControl           - Tabulator 欄位控制組件（UiShared）
 * 
 * 自訂模組 - 本模組（MarketplaceCheckout）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config - Tabulator 配置（業務特定）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/filters/filters - 篩選器組件
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/select-action - 批量操作處理模組
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/exception-authorization-card/v4 - 例外授權卡片 V4 渲染器
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/exception-authorization-card/v5 - 例外授權卡片 V5 渲染器
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'domReady!',
    
    // 載入套件 (按優先級排序，同優先級 A-Z)
    'tabulator',
    
    // 自訂模組 - 共用資源（來自 UiShared，按優先級排序，同優先級 A-Z）
    'hotaiLoadingMask',
    'hotaiToastMessage',
    'tabulatorColumnControl',
    
    // 自訂模組 - 本模組（CheckoutManagement）
    'HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config',  // 完整配置（已包含通用配置）
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/filters/filters',
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/select-action',
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/exception-authorization-card/v4',
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/exception-authorization-card/v5'
], function (
    // Magento 內建功能 (按優先級排序)
    $,
    $t,
    urlBuilder,
    alert,
    domReady,
    
    // 載入套件 (按優先級排序，同優先級 A-Z)
    Tabulator,
    
    // 自訂模組 - 共用資源（來自 UiShared，按優先級排序，同優先級 A-Z）
    loadingMask,
    toastMessage,
    columnControl,
    
    // 自訂模組 - 本模組（CheckoutManagement）
    tabulatorConfig,  // 完整配置（已包含通用配置）
    filterForm,
    selectAction,
    exceptionAuthorizationCardV4,
    exceptionAuthorizationCardV5
) {
    'use strict';

    return {
        // ========================================
        // 模組屬性
        // ========================================
        
        
        /** @type {string} Tabulator 表格容器的 CSS 選擇器 */
        tabulatorId: '#tabulator-grid',
        
        /** @type {Object|null} Tabulator 實例 */
        tabulatorInstance: null,
        
        /** @type {Object} Tabulator 配置設定 (完整配置，已包含通用配置和業務特定配置) */
        tabulatorConfig: tabulatorConfig.getConfig(),
        
        /** @type {Object} 例外處理功能配置（從 tabulatorConfig 取得） */
        exceptionConfig: tabulatorConfig.exceptionConfig,
        
        /** @type {boolean} 重新插入鎖（防止 renderComplete 無限循環） */
        _isReInserting: false,
        
        /** 
         * @type {Object} 例外授權名單全局緩存
         * 格式：{ parentId: { data: [...], expanded: true/false } }
         * 用途：避免重複呼叫 API，提升效能
         * 清除時機：主表單資料更新時（dataLoaded 事件）
         */
        _exceptionDataCache: {},
        /** @type {Object} 篩選器可見性控制 */
        filtersVisibility: {
            toggle: '#filters-visibility__toggle',
            menu: '#filters-visibility__menu',
            columnsCheckboxes: '#filters-visibility__columns-checkboxes',
            stat: '#filters-visibility__stat',
            reset: '#filters-visibility__reset',
            cancel: '#filters-visibility__cancel',
        },
        /** 
         * @type {Object} 嵌套內容相關 CSS 類名
         * - row: nested-content-row (V4 使用)
         * - body: nested-content-body (V5 使用，目前)
         * - expandBtn: nested-expand-btn (展開/收合按鈕)
         */
        nested: {
            row: 'nested-content-row',
            body: 'nested-content-body',
            expandBtn: 'nested-expand-btn',
        },
        // ========================================
        // 主要方法
        // ========================================

        /**
         * 模組初始化
         */
        initialize: function () {
            this.initTabulator();
            this.bindEvents();
            this.initFilterForm();
            this.initColumnVisibilityControl();
        },

        /**
         * 綁定所有事件監聽器
         */
        bindEvents: function() {
            var self = this;
            
            // ========================================
            // 綁定篩選器按鈕（控制器負責）
            // ========================================
            
            // 套用篩選按鈕
            $('#apply-filters').on('click', function(e) {
                e.preventDefault();
                
                toastMessage.remove();
                
                // 1. 驗證表單
                var $form = $('#filters-form');
                var validator = $form.data('validator');
                var isValid = true;
                
                if (validator) {
                    isValid = $form.validation('isValid');
                }
                
                if (!isValid) {
                    return;
                }
               
                // 2. 收集篩選資料 - 資料取得統一在 filters.js 中 filters.getFormData() 方法
                var params = filterForm.getFormData();

                // 合併 特約商名稱與特約商代號
                if (params.shop_title || params.seller_code){
                    var seller_code_array = ((params.seller_code || '') + ',' + (params.shop_title || '')).split(',');
                    params.seller_code = seller_code_array.filter(Boolean).filter(function(e, i) {
                        return seller_code_array.indexOf(e) === i;
                    }).join(',');
                    delete params.shop_title;
                }            

                // 3. 更新 tabulator-config 中儲存的 filters 參數（用於換頁時保留篩選條件）
                tabulatorConfig.currentFilterParams = params || {};

                // 4. 直接更新 Tabulator（使用 updateTabulator 統一處理動態 URL、參數合併和 Promise 處理）
                if (self.tabulatorInstance) {
                    self.updateTabulator({
                        hideFilters: true  // 套用篩選後隱藏篩選器
                    });
                }
            });
            
            // 重置篩選按鈕
            $('#clear-filters').on('click', function(e) {
                e.preventDefault();
                filterForm.resetForm();
                filterForm.hideFilters();
                
                // 清除 config 中儲存的 filters 參數
                tabulatorConfig.currentFilterParams = {};
                
                if (self.tabulatorInstance) {
                    // 重置 Tabulator 資料、頁碼和每頁筆數
                    var params = $.extend({}, self.tabulatorConfig.ajaxParams);
                    // 根據開關決定是否重置每頁筆數
                    if (tabulatorConfig.resetPageSizeOnClear) {
                        var defaultPageSize = tabulatorConfig.defaultPageSize || 10;
                        self.tabulatorInstance.setPageSize(defaultPageSize);
                    }
                    // 重置頁碼為第1頁
                    self.tabulatorInstance.setPage(1);
                    // 然後重新載入資料
                    self.tabulatorInstance.setData();
                }
            });

            // ========================================
            // 批量操作
            // ========================================

            // 綁定來自配置檔案的事件
            self.bindTabulatorEvents();
            
        },

        /**
         * 綁定 Tabulator 相關的事件監聽器
         */
        bindTabulatorEvents: function() {
            var self = this;

            // 處理廠商對帳單下載事件
            $(document).on('vendor-statement:download', function(e, rowData, selectedIds) {
                selectAction.executeAction('download_vendor_statement', [rowData], selectedIds, {
                    tabulatorInstance: self.tabulatorInstance,
                    resetBatchActionSelect: function() {
                        selectAction.resetBatchActionSelect();
                    }
                });
            });

        },

        /**
         * 初始化篩選表單
         */
        initFilterForm: function() {
            // 初始化篩選表單控制器
            filterForm.initialize();
        },

        /**
         * 初始化 Tabulator 表格
         */
        initTabulator: function() {
            var self = this;
            loadingMask.show();
            self.tabulatorConfig.ajaxParams = {
                'form_key': $('input[name="form_key"]').val() || ''
            };

            self.tabulatorInstance = new Tabulator(self.tabulatorId, self.tabulatorConfig);

            self.tabulatorInstance.on("tableBuilt", function(){
                loadingMask.hide();
                $('.fade-up-initial').addClass('active');
            });
        
            self.tabulatorInstance.on("dataLoaded", function(){
                // 資料載入完成：清空例外授權名單緩存
                if (self.exceptionConfig.enabled) {
                    self._exceptionDataCache = {};
                    console.log('🗑️  主表單資料更新，已清空例外授權名單緩存');
                }
            });
            
            self.tabulatorInstance.on("dataLoadError", function(error){
                console.error('dataLoadError 資料載入失敗', error);
                self.showError(error);
            });
        },

        /**
         * 更新 Tabulator 表格
         * 
         * @param {Object} [options] - 可選參數
         * @param {boolean} [options.hideFilters=false] - 是否在成功後隱藏篩選器
         */
        updateTabulator: function(options) {
            var self = this;
            
            // 有其他參數需要傳遞，請在這裡添加
            var params = $.extend({}, self.tabulatorConfig.ajaxParams);
            
            // 合併儲存的篩選參數（從 tabulatorConfig 取得 currentFilterParams）
            var filterParams = tabulatorConfig.currentFilterParams || {};
            params = $.extend({}, params, filterParams);
            
            // 使用配置的 ajaxURL
            var ajaxURL = self.tabulatorConfig.ajaxURL;

            // 統一管理 Promise 處理邏輯
            self.tabulatorInstance.setData(ajaxURL, params)
                .then(function() {
                    // 根據選項決定是否隱藏篩選器
                    if (options && options.hideFilters === true) {
                        filterForm.hideFilters();
                    }
                })
                .catch(function(error) {
                    console.error('❌ Tabulator 更新失敗', error);
                });
        },
        
        /**
         * 顯示錯誤訊息
         */
        showError: function(message) {
            alert({
                title: $.mage.__('Error'),
                content: message || $.mage.__('Loading Error'),
                modalClass: 'alert alert-danger'
            });
        },

        // ========================================
        // 欄位可見性控制方法（使用 UiShared 共用組件）
        // ========================================

        /**
         * 初始化欄位可見性控制（使用 tabulatorColumnControl 共用組件）
         * 
         * ✅ v4.0.0 重構說明：
         * - 使用 UiShared 的 tabulatorColumnControl.initVisibility()
         * - 所有欄位控制邏輯統一由共用組件管理
         * 
         * 📦 共用組件：HotaiConnected_UiShared/js/components/tabulator-column-control
         */
        initColumnVisibilityControl: function() {
            columnControl.initVisibility({
                tabulatorInstance: this.tabulatorInstance,
                columnsConfig: this.tabulatorConfig.columns,
                selectors: this.filtersVisibility,
                storageKey: 'checkout_table_columns_visibility'
            });
        }
        
    };
});



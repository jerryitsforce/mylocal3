/**
 * @fileoverview 訂單結帳資料匯出頁主控制器
 * 
 * 此模組負責管理訂單結帳資料匯出頁的主要功能，包括：
 * - Tabulator 表格的初始化和管理
 * - 篩選器的顯示/隱藏切換
 * - 批量操作的處理（批量審核、批量拒絕、下載報表等）
 * - 載入動畫的控制
 * - 事件監聽器的綁定和管理
 * 
 * @module HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/index
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2024-10-23
 * 
 * @example
 * // 在 index.phtml 中使用
 * require(['HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/index'], function(exportOrderCheckoutDataIndex) {
 *     exportOrderCheckoutDataIndex.initialize();
 * });
 * 
 * 依賴關係：
 * 
 * Magento 內建功能 (按優先級排序)
 * @requires {jQuery} jquery                           - jQuery 核心庫，用於 DOM 操作和事件處理
 * @requires {Object} mage/translate                   - Magento 翻譯功能，用於多語言支援（間接使用 $.mage.__()）
 * @requires {Object} Magento_Ui/js/modal/alert        - Magento 警告彈窗，用於錯誤訊息顯示
 * @requires {Object} Magento_Ui/js/modal/confirm      - Magento 確認彈窗，用於用戶確認操作
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
 * 自訂模組 - 本模組（CheckoutManagement）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/config/tabulator-config - Tabulator 配置（業務特定）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/components/filters/filters - 篩選器組件
 * @requires {Object} HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/components/table/select-action - 批量操作處理模組
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'domReady!',
    
    // 載入套件 (按優先級排序，同優先級 A-Z)
    'tabulator',
    
    // 自訂模組 - 共用資源（來自 UiShared，按優先級排序，同優先級 A-Z）
    'hotaiLoadingMask',
    'hotaiToastMessage',
    'tabulatorColumnControl',
    
    // 自訂模組 - 本模組（CheckoutManagement）
    'HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/config/tabulator-config',  // 完整配置（已包含通用配置）
    'HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/components/filters/filters',
    'HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/components/table/select-action',
    'HotaiConnected_CheckoutManagement/js/shared/services/api/ecpay-order-logs'  // API 服務
], function (
    // Magento 內建功能 (按優先級排序)
    $,
    $t,
    urlBuilder,
    alert,
    confirm,
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
    ecpayOrderLogsApi  // API 服務
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
        tabulatorConfig: tabulatorConfig.getConfig({}),
        
        /** @type {Object} 篩選器可見性控制 */
        filtersVisibility: {
            toggle: '#filters-visibility__toggle',
            menu: '#filters-visibility__menu',
            columnsCheckboxes: '#filters-visibility__columns-checkboxes',
            stat: '#filters-visibility__stat',
            reset: '#filters-visibility__reset',
            cancel: '#filters-visibility__cancel',
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

                // 特約商名稱
                if (params.shop_title) {
                    // shop_title 選擇器的 id 實際上是 seller_code（見 filters.js 第 196 行）
                    // 傳遞所有選中的 seller_code（逗號分隔）
                    params.seller_code = params.shop_title;
                    delete params.shop_title;
                }
                
                var params = self.getCurrentFilterParams();
                
                // 5. 更新 Tabulator
                if (self.tabulatorInstance) {
                    var params = $.extend({}, self.tabulatorConfig.ajaxParams, params);
                    self.tabulatorInstance.setData(tabulatorConfig.ajaxURL, params)
                        .then(function() {
                            // 5. 隱藏篩選器 - 有需要再開啟
                            filterForm.hideFilters();
                        })
                        .catch(function(error) {
                            console.error('❌ Tabulator 更新失敗', error);
                        });
                }
            });
            
            // 重置篩選按鈕
            $('#clear-filters').on('click', function(e) {
                e.preventDefault();
                filterForm.resetForm();
                filterForm.hideFilters();
                
                // 清除 tabulator-config 中儲存的 filters 參數
                tabulatorConfig.currentFilterParams = {};
                
                // 重置 Tabulator 資料、頁碼和每頁筆數
                if (self.tabulatorInstance) {
                    var params = $.extend({}, self.getCurrentFilterParams());
                    // 根據開關決定是否重置每頁筆數
                    if (tabulatorConfig.resetPageSizeOnClear) {
                        var defaultPageSize = tabulatorConfig.defaultPageSize || 10;
                        self.tabulatorInstance.setPageSize(defaultPageSize);
                    }
                    // 重置頁碼為第1頁
                    self.tabulatorInstance.setPage(1);
                    // 然後重新載入資料
                    self.tabulatorInstance.setData(tabulatorConfig.ajaxURL, params);
                }
            });

            // ========================================
            // 批量操作
            // ========================================
            
            $('#batch-action-select').on('change', function(e) {
                self.handleBatchAction();
            });

            // ========================================
            // 下載搜尋資料按鈕
            // ========================================
            
            $('#download-search-data').on('click', function(e) {
                e.preventDefault();
                self.downloadSearchData();
            });

            // 綁定來自配置檔案的事件
            self.bindTabulatorEvents();
            
        },

        /**
         * 綁定 Tabulator 相關的事件監聽器
         */
        bindTabulatorEvents: function() {
            var self = this;
            
            // 批量操作事件將由 select-action 模組處理
            // 可以在這裡添加其他自定義事件監聽器
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
            
            self.tabulatorInstance.on("dataLoadError", function(error){
                console.error('dataLoadError 資料載入失敗', error);
                self.showError(error);
            });
        },



        /**
         * 取得目前的篩選參數
         */
        getCurrentFilterParams: function() {
            var self = this;
            // 1. 收集篩選資料 - 資料取得統一在 filters.js 中 filters.getFormData() 方法
            var params = filterForm.getFormData();

            // 特約商名稱
            // 如果特約商名稱為空，則傳遞 all
            if (!params.hasOwnProperty('shop_title') || params.shop_title === '' || filterForm.isSelectAllSelected('shop_title')) {
                params.seller_code = 'all';
            } else {
                // 如果特約商不為 all，則傳遞所有選中的 seller_code（逗號分隔）
                // shop_title 選擇器的 id 實際上是 seller_code（見 filters.js 第 196 行）
                // 傳遞所有選中的 seller_code（逗號分隔）
                params.seller_code = params.shop_title;
                delete params.shop_title;
            }

            // 負責館長 不需過濾，直接傳遞
            // if (!params.hasOwnProperty('sales_person') || params.sales_person === '' || filterForm.isSelectAllSelected('sales_person')) {
            //     params.sales_person = 'all';
            // }
            
            // 2. 更新 tabulator-config 中儲存的 filters 參數（用於換頁時保留篩選條件）
            tabulatorConfig.currentFilterParams = params || {};

            return params;
        },

        /**
         * 更新 Tabulator 表格
         */
        updateTabulator: function() {
            var self = this;
            
            // 有其他參數需要傳遞，請在這裡添加
            var params = $.extend({}, self.tabulatorConfig.ajaxParams);
            
            self.tabulatorInstance.setData(tabulatorConfig.ajaxURL, params);
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

        /**
         * 下載搜尋資料（根據目前的篩選參數下載報表）
         * 2026-03-20 因檔案在後端需要額外處理，改 application/json
         */
        downloadSearchData: function() {
            var self = this;
            var params = self.getCurrentFilterParams();

            // 沒有時間，不能下載
            if (!params.hasOwnProperty('invoice_created_from') || params.invoice_created_from === '' || !params.hasOwnProperty('invoice_created_to') || params.invoice_created_to === '') {
                toastMessage.warning({
                    content: $.mage.__('Please select %1').replace('%1', $.mage.__('Order Checkout Number Modification Date (From-To)')),
                    autoClose: true,
                    duration: 5000
                });
                return;
            }
            
            // 呼叫 API 下載報表
            // request.download() 會自動處理：Loading 動畫、檔案下載、成功/錯誤訊息
            ecpayOrderLogsApi.exportEcpayOrderLogs(params, {
                showSuccessMessage: false,
                showErrorMessage: true,
                showErrorMessage: $.mage.__('%1 download failed.').replace('%1', '訂單結帳匯出報表')
            })
            .done(function(response) {
                console.log('下載完成', response);

                var { success, message } = response;
                if(!success) {
                    toastMessage.warning({
                        content: message
                    });
                }

                if(success) {
                    toastMessage.success({
                        content: message
                    });
                }
            })
            .fail(function() {
                // 錯誤訊息已由 request.download() 自動顯示
            });
        },

        /**
         * 處理批量操作
         */
        handleBatchAction: function() {
            var self = this;
            var selectedAction = $('#batch-action-select').val();
            
            if (selectedAction && selectedAction !== '') {
                var selectedData = self.tabulatorInstance.getSelectedData();
                var selectedIds = selectedData.map(function(row) {
                    return row.entity_id || row.id;
                });
                
                if (selectedData.length > 0) {
                    // 執行批量操作，並傳遞上下文物件給各個操作模組
                    selectAction.executeAction(selectedAction, selectedData, selectedIds, {
                        // 傳遞 Tabulator 實例供操作模組使用
                        tabulatorInstance: self.tabulatorInstance,
                        // 定義重置函數：當批量操作完成後自動重置批量操作選擇器為空值
                        resetBatchActionSelect: function() {
                            selectAction.resetBatchActionSelect();
                        },
                        updateTabulator: self.updateTabulator.bind(self)
                    });
                } else {
                    toastMessage.warning($.mage.__('Please select at least one item to perform the action.'));
                    // 將批量操作選擇器重置為空值
                    selectAction.resetBatchActionSelect();
                }
            } else {
                var params = $.extend({}, self.tabulatorConfig.ajaxParams);
                self.tabulatorInstance.setData(tabulatorConfig.ajaxURL, params);
            }
        },

        // ========================================
        // 欄位可見性控制方法（使用 UiShared 共用組件）
        // ========================================

        /**
         * 初始化欄位可見性控制（使用 tabulatorColumnControl 共用組件）
         * 
         * ✅ v1.0.0 說明：
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
                storageKey: 'export_order_checkout_data_table_columns_visibility'
            });
        }
        
    };
});



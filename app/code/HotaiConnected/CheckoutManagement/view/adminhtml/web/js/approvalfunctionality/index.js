/**
 * @fileoverview 對帳區域列表頁主控制器
 * 
 * 此模組負責管理對帳管理列表頁的主要功能，包括：
 * - Tabulator 表格的初始化和管理
 * - 篩選器的顯示/隱藏切換
 * - 批量操作的處理（下載、審核等）
 * - 載入動畫的控制
 * - 事件監聽器的綁定和管理
 * 
 * @module HotaiConnected_CheckoutManagement/js/approvalfunctionality/index
 * @version 4.0.0
 * @author HotaiConnected
 * @created 2024-10-23
 * @updated 2024-10-23 - 重構使用 tabulatorColumnControl 共用組件
 * 
 * @example
 * // 在 index.phtml 中使用
 * require(['HotaiConnected_CheckoutManagement/js/approvalfunctionality/index'], function(approvalFunctionalityIndex) {
 *     approvalFunctionalityIndex.initialize();
 * });
 * 
 * 依賴關係：
 * 
 * Magento 內建功能 (按優先級排序)
 * @requires {jQuery} jquery                           - jQuery 核心庫，用於 DOM 操作和事件處理
 * @requires {Object} Magento_Ui/js/modal/alert        - Magento 警告彈窗，用於錯誤訊息顯示
 * @requires {Object} Magento_Ui/js/modal/confirm      - Magento 確認彈窗，用於用戶確認操作
 * 
 * 載入套件 (按優先級排序，同優先級 A-Z) - 來自 UiShared
 * @requires {Object} tabulator                        - Tabulator 表格庫（UiShared）
 * 
 * 自訂模組 - 共用資源（來自 UiShared）
 * @requires {Object} hotaiLoadingMask                 - 全螢幕載入動畫（UiShared）
 * @requires {Object} hotaiToastMessage                - Toast 訊息提示（UiShared）
 * @requires {Object} tabulatorColumnControl           - Tabulator 欄位控制組件（UiShared）
 * 
 * 自訂模組 - 本模組（CheckoutManagement）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/approvalfunctionality/config/tabulator-config - Tabulator 配置（業務特定）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/filters/filters        - 篩選器組件
 * @requires {Object} HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/table/select-action   - 批量操作處理模組
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    
    // 載入套件 (按優先級排序，同優先級 A-Z)
    'tabulator',
    
    // 自訂模組 - 共用資源（來自 UiShared，按優先級排序，同優先級 A-Z）
    'hotaiLoadingMask',
    'hotaiToastMessage',
    'tabulatorColumnControl',
    
    // 自訂模組 - 本模組（CheckoutManagement）
    'HotaiConnected_CheckoutManagement/js/approvalfunctionality/config/tabulator-config',  // 完整配置（已包含通用配置）
    'HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/filters/filters',
    'HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/table/select-action',
    'HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/table/actions/batch-update-status',
    'HotaiConnected_CheckoutManagement/js/approvalfunctionality/components/table/actions/download-report'
], function (
    // Magento 內建功能 (按優先級排序)
    $,
    alert,
    confirm,
    
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
    batchStatusAction,
    downloadReportAction
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

                // 合併 特約商名稱與特約商代號
                if (params.hasOwnProperty('shop_title')){
                    var seller_code_array = params.hasOwnProperty('seller_code') ? params.seller_code.split(',') : [];

                    params.shop_title.split(',').forEach(function(shop_title) {
                        if (seller_code_array.indexOf(shop_title) === -1) {
                            seller_code_array.push(shop_title);
                        }
                    });

                    params.seller_code = seller_code_array.join(',');
                    delete params.shop_title;
                }

                // 3. 更新 tabulator-config 中儲存的 filters 參數（用於換頁時保留篩選條件）
                tabulatorConfig.currentFilterParams = params || {};
                
                // 4. 更新 Tabulator
                if (self.tabulatorInstance) {
                    params = $.extend({}, self.tabulatorConfig.ajaxParams, params);
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
                    var params = $.extend({}, self.tabulatorConfig.ajaxParams);
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

            // 綁定來自配置檔案的事件
            self.bindTabulatorEvents();
            
        },

        /**
         * 綁定 Tabulator 相關的事件監聽器
         */
        bindTabulatorEvents: function() {
            var self = this;

            // 批次審核（同意）
            $(document).on('approval:batch-approve', function(e, selectedData, selectedIds, context) {
                var extendedContext = $.extend({}, context, {
                    updateTabulator: self.updateTabulator.bind(self)
                });
                batchStatusAction.execute('approved', selectedData, selectedIds, extendedContext);
            });

            // 批次審核（拒絕）
            $(document).on('approval:batch-reject', function(e, selectedData, selectedIds, context) {
                var extendedContext = $.extend({}, context, {
                    updateTabulator: self.updateTabulator.bind(self)
                });
                batchStatusAction.execute('rejected', selectedData, selectedIds, extendedContext);
            });

            // 下載報表
            $(document).on('approval:download-report', function(e, selectedData, selectedIds, context) {
                var extendedContext = $.extend({}, context, {
                    updateTabulator: self.updateTabulator.bind(self)
                });
                downloadReportAction.execute(selectedData, selectedIds, extendedContext);
            });

            // 單筆審核（同意）
            $(document).on('approval:approve', function(e, target, rowData) {
                self.handleSingleApproval(rowData, 'approved');
            });

            // 單筆審核（拒絕）
            $(document).on('approval:reject', function(e, target, rowData) {
                self.handleSingleApproval(rowData, 'rejected');
            });

        },

        /**
         * 處理單筆審核（同意/拒絕）
         * 
         * @param {Object} rowData - 行資料
         * @param {string} status - 審核狀態 ('approved' 或 'rejected')
         * @param {string} title - 確認對話框標題
         * @param {string} content - 確認對話框內容
         */
        handleSingleApproval: function(rowData, status) {
            var self = this;
            var targetId = rowData && (rowData.entity_id || rowData.id);
            var title = status === 'approved' ? $.mage.__('Approve') : $.mage.__('Reject');

            if (!targetId) {
                toastMessage.error($.mage.__('Unable to determine record id.'));
                return;
            }

            confirm({
                title: title,
                content: $.mage.__('Are you sure you want to %1 this record?').replace('%1', title),
                actions: {
                    confirm: function() {
                        batchStatusAction.execute(status, [rowData], [targetId], {
                            updateTabulator: self.updateTabulator.bind(self),
                            resetBatchActionSelect: function() {}
                        });
                    },
                    cancel: function() {}
                }
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
            
            self.tabulatorInstance.on("dataLoadError", function(error){
                console.error('dataLoadError 資料載入失敗', error);
                self.showError(error);
            });
        },

        /**
         * 更新 Tabulator 表格
         */
        updateTabulator: function() {
            var self = this;
            
            // 有其他參數需要傳遞，請在這裡添加
            var params = $.extend({}, self.tabulatorConfig.ajaxParams);
            
            // 合併儲存的篩選參數
            var filterParams = tabulatorConfig.currentFilterParams || {};
            params = $.extend({}, params, filterParams);
            
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
                var params = {
                    ...self.tabulatorConfig.ajaxParams
                };
                self.tabulatorInstance.setData(tabulatorConfig.ajaxURL, params);
            }
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


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
 * 自訂模組 - 本模組（CheckoutManagement）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config - Tabulator 配置（業務特定）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/shared/services/api/exception-auth - 例外授權 API 服務
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/filters/filters - 篩選器組件
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/select-action - 批量操作處理模組
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/vendor-invoice-actions - 廠商發票操作模組
 * @requires {Object} HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/unlock-checkout - 解鎖對帳單操作模組
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
    'HotaiConnected_CheckoutManagement/js/checkoutarea/config/exception-auth-config',  // 例外授權配置（data_source = '2' 時使用）
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/filters/filters',
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/select-action',
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/vendor-invoice-actions',
    'HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/unlock-checkout',
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
    exceptionAuthConfig,  // 例外授權配置（data_source = '2' 時使用）
    apiExceptionAuth,
    filterForm,
    selectAction,
    vendorInvoiceActions,
    unlockCheckout,
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
        
        /** @type {Object} Tabulator 配置模組集合 */
        _tabulatorConfig: {
            default: tabulatorConfig,
            exception: exceptionAuthConfig
        },
        
        /** @type {Object} Tabulator 配置設定 (完整配置，已包含通用配置和業務特定配置) */
        tabulatorConfig: tabulatorConfig.getConfig({
            getStatusName: filterForm.getStatusDisplayName.bind(filterForm),
            // 動態獲取 status maps（確保獲取最新資料，避免初始化時序問題）
            getStatusMaps: function() {
                return filterForm._statusMaps;
            }
        }),
        
        /** @type {Object} 例外處理功能配置（從 tabulatorConfig 取得） */
        exceptionConfig: tabulatorConfig.exceptionConfig,
        
        /** @type {Object} 當前使用的配置模組（用於取得 currentFilterParams） */
        _currentConfigModule: tabulatorConfig,
        
        /**
         * 根據 data_source 取得對應的配置模組
         * @param {string} dataSource - 資料來源 ('2' 為例外授權，其他為預設)
         * @returns {Object} 配置模組物件
         */
        getConfigModule: function(dataSource) {
            return dataSource === '2' ? this._tabulatorConfig.exception : this._tabulatorConfig.default;
        },
        
        /**
         * 根據 data_source 取得對應的配置物件
         * @param {string} dataSource - 資料來源 ('2' 為例外授權，其他為預設)
         * @returns {Object} Tabulator 配置物件
         */
        getConfig: function(dataSource) {
            var configModule = this.getConfigModule(dataSource);
            return configModule.getConfig({
                getStatusName: filterForm.getStatusDisplayName.bind(filterForm),
                getStatusMaps: function() {
                    return filterForm._statusMaps;
                }
            });
        },
        
        /**
         * 根據 data_source 更新 batch-action-select 的 disabled 狀態
         * @private
         * @param {string} dataSource - 資料來源 ('2' 為例外授權，其他為預設)
         */
        _updateBatchActionSelectState: function(dataSource) {
            var $batchActionSelect = $('#batch-action-select');
            if ($batchActionSelect.length) {
                $batchActionSelect.prop('disabled', dataSource === '2');
            }
        },
        
        /**
         * 重新初始化 Tabulator（切換配置時使用）
         * @private
         * @param {Function} [callback] - 初始化完成後的回調函數
         */
        _reinitializeTabulator: function(callback) {
            var self = this;
            if (!self.tabulatorInstance) {
                if (callback) callback();
                return;
            }
            
            // 銷毀舊實例並創建新實例
            self.tabulatorInstance.destroy();
            loadingMask.show();
            self.tabulatorInstance = new Tabulator(self.tabulatorId, self.tabulatorConfig);
            
            self.tabulatorInstance.on("tableBuilt", function(){
                loadingMask.hide();
                $('.fade-up-initial').addClass('active');
                // 初始化完成後執行回調
                if (callback) {
                    callback();
                }
            });
            
            self.tabulatorInstance.on("dataLoaded", function(){
                if (self.exceptionConfig.enabled) {
                    self._exceptionDataCache = {};
                    console.log('🗑️  主表單資料更新，已清空例外授權名單緩存');
                }
            });
            
            self.tabulatorInstance.on("dataLoadError", function(error){
                console.error('dataLoadError 資料載入失敗', error);
                self.showError(error);
            });
            
            // 綁定事件監聽器
            self.bindTabulatorEvents();
        },
        
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

                

                // 3. 根據 data_source 切換配置並更新 currentFilterParams
                var newDataSource = params.data_source;
                var newConfigModule = self.getConfigModule(newDataSource);
                newConfigModule.currentFilterParams = params || {};
                
                // 4. 根據 data_source 更新 batch-action-select 的 disabled 狀態
                self._updateBatchActionSelectState(newDataSource);
                
                // 5. 如果 data_source 改變，需要重新初始化 Tabulator
                var needSwitch = self._currentConfigModule !== newConfigModule;
                
                // 6. 更新 tabulator-config 中儲存的 filters 參數（用於換頁時保留篩選條件，向後兼容）
                tabulatorConfig.currentFilterParams = params || {};

                if (needSwitch && self.tabulatorInstance) {
                    // 切換配置：重新初始化 Tabulator
                    var newConfig = self.getConfig(newDataSource);
                    newConfig.ajaxParams = self.tabulatorConfig.ajaxParams;
                    self.tabulatorConfig = newConfig;
                    self._currentConfigModule = newConfigModule;  // 更新當前使用的 config module
                    // 等待初始化完成後再更新資料
                    self._reinitializeTabulator(function() {
                        // 初始化完成後更新 Tabulator
                        self.updateTabulator({
                            hideFilters: true  // 套用篩選後隱藏篩選器
                        });
                    });
                } else {
                    // 即使不需要切換，也要更新當前使用的 config module
                    self._currentConfigModule = newConfigModule;
                    // 直接更新 Tabulator（使用 updateTabulator 統一處理動態 URL、參數合併和 Promise 處理）
                    if (self.tabulatorInstance) {
                        self.updateTabulator({
                            hideFilters: true  // 套用篩選後隱藏篩選器
                        });
                    }
                }
            });
            
            // 重置篩選按鈕
            $('#clear-filters').on('click', function(e) {
                e.preventDefault();
                filterForm.resetForm();
                filterForm.hideFilters();
                
                // 清除兩個 config module 中儲存的 filters 參數
                tabulatorConfig.currentFilterParams = {};
                exceptionAuthConfig.currentFilterParams = {};
                
                // 清除篩選時，直接切換回預設配置
                // 更新 batch-action-select 為 enabled（因為清除後回到預設配置）
                self._updateBatchActionSelectState(null);
                
                if (self.tabulatorInstance) {
                    var defaultConfig = self.getConfig(null);
                    defaultConfig.ajaxParams = self.tabulatorConfig.ajaxParams;
                    self.tabulatorConfig = defaultConfig;
                    self._currentConfigModule = self._tabulatorConfig.default;
                    
                    // 等待初始化完成後再重置資料
                    self._reinitializeTabulator(function() {
                        // 重置 Tabulator 資料、頁碼和每頁筆數
                        var params = $.extend({}, self.tabulatorConfig.ajaxParams);
                        // 根據開關決定是否重置每頁筆數（使用當前 config module，清除後為預設）
                        var currentConfigModule = self.getConfigModule(null);
                        if (currentConfigModule.resetPageSizeOnClear) {
                            var defaultPageSize = currentConfigModule.defaultPageSize || 10;
                            self.tabulatorInstance.setPageSize(defaultPageSize);
                        }
                        // 重置頁碼為第1頁
                        self.tabulatorInstance.setPage(1);
                        // 然後重新載入資料
                        self.tabulatorInstance.setData();
                    });
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
            
            // 以首次勾選的 batch_num 為基準；後續勾選若不同批次則取消該筆並以 confirm 提示（避免重複綁定）
            if (self.tabulatorInstance && !self.tabulatorInstance._hotaiSelectionLogBound) {
                self.tabulatorInstance._hotaiSelectionLogBound = true;

                var normalizeBatchNum = function(v) {
                    if (v === null || v === undefined || v === '') {
                        return null;
                    }
                    return String(v);
                };

                self.tabulatorInstance.on("rowSelected", function(row) {
                    var batchNum = normalizeBatchNum(row.getData().batch_num);
                    var selected = self.tabulatorInstance.getSelectedData();

                    if (selected.length === 1) {
                        self.tabulatorInstance._hotaiAnchorBatchNum = batchNum;
                        return;
                    }

                    var anchor = self.tabulatorInstance._hotaiAnchorBatchNum;
                    if (batchNum !== anchor) {
                        row.deselect();
                        if(document.querySelectorAll('.modal-popup.alert.alert-danger._show').length === 0) {
                            self.showError( $.mage.__('Cannot unbind across multiple batches') )
                        }
                    }
                });

                self.tabulatorInstance.on("rowDeselected", function() {
                    if (self.tabulatorInstance.getSelectedData().length === 0) {
                        self.tabulatorInstance._hotaiAnchorBatchNum = undefined;
                    }
                });
            }

            // 處理廠商對帳單下載事件
            $(document).on('vendor-statement:download', function(e, rowData, selectedIds) {
                selectAction.executeAction('download_vendor_statement', [rowData], selectedIds, {
                    tabulatorInstance: self.tabulatorInstance,
                    resetBatchActionSelect: function() {
                        selectAction.resetBatchActionSelect();
                    }
                });
            });

            // 處理解鎖結帳事件
            // 綁定自定義事件 'unlock-checkout:show' 到 document，當事件觸發時執行回調函數
            $(document).on('unlock-checkout:show', function(e, rowData, selectedIds) {
                // 執行解除結帳，並傳遞上下文物件
                unlockCheckout.executeUnlockCheckout([rowData], selectedIds, {
                    // 傳遞 Tabulator 實例供對話框使用
                    tabulatorInstance: self.tabulatorInstance,
                    
                    // 定義重置函數：當對話框關閉時自動重置批量操作選擇器為空值
                    resetBatchActionSelect: function() {
                        selectAction.resetBatchActionSelect();
                    }
                });
            });

            // 處理廠商發票相關事件
            $(document).on('vendor-invoice:confirm', function(e, target, rowData) {
                vendorInvoiceActions.handleVendorInvoiceConfirm(target, rowData);
            });

            $(document).on('vendor-invoice:edit', function(e, target, rowData) {
                vendorInvoiceActions.handleVendorInvoiceEdit(target, rowData);
            });

            $(document).on('vendor-invoice:cancel', function(e, target, rowData) {
                vendorInvoiceActions.handleVendorInvoiceCancel(target, rowData);
            });

            $(document).on('vendor-invoice:plus', function(e, target, rowData) {
                vendorInvoiceActions.handleVendorInvoicePlus(target, rowData);
            });

            $(document).on('vendor-invoice:minus', function(e, target, rowData) {
                vendorInvoiceActions.handleVendorInvoiceMinus(target, rowData);
            });

            // 處理嵌套表格展開/收合事件（僅 HTML 模式）
            if (self.exceptionConfig.enabled && self.exceptionConfig.displayMode === 'html') {
                $(document).on('nested-table:toggle', function(e, row) {
                    self.handleNestedTableToggle(row);
                });

                // 初始化 V4 視圖切換事件處理（如果使用 V4）
                exceptionAuthorizationCardV4.initViewToggle();
            }

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

            // 🌳 DataTree 模式：監聽 dataTreeRowExpanded 事件
            if (self.exceptionConfig.enabled && self.exceptionConfig.displayMode === 'dataTree') {
                self.tabulatorInstance.on("dataTreeRowExpanded", function(row, level){
                    console.log('🌳 DataTree 節點展開', {row: row.getData(), level: level});
                    self.handleDataTreeExpand(row);
                });
            }
            
            // 📄 HTML 模式：監聽欄位可見性變化（全部收合展開的內容）
            if (self.exceptionConfig.enabled && self.exceptionConfig.displayMode === 'html') {
                // 監聽表格重繪完成事件（欄位變化時會觸發）
                self.tabulatorInstance.on("renderComplete", function(){
                    // 🔒 防止重入（避免無限循環）
                    if (self._isReInserting) {
                        return;
                    }
                    
                    self._isReInserting = true;
                    
                    try {
                        // 遍歷所有行，清除展開狀態
                        var allRows = self.tabulatorInstance.getRows();
                        var collapseCount = 0;
                        
                        allRows.forEach(function(row) {
                            var rowData = row.getData();
                            
                            // 如果這行是展開狀態，收合它
                            if (rowData._expanded) {
                                var rowElement = row.getElement();
                                
                                // 移除插入的 HTML（如果還在）
                                var nestedWrapper = rowElement ? rowElement.nextElementSibling : null;
                                if (nestedWrapper && nestedWrapper.classList.contains(self.nested.body)) {
                                    nestedWrapper.remove();
                                }
                                
                                // 更新狀態為收合
                                rowData._expanded = false;
                                
                                // 更新按鈕圖示為 ➕
                                var btnElement = rowElement ? rowElement.querySelector('.' + self.nested.expandBtn) : null;
                                if (btnElement) {
                                    btnElement.textContent = '➕';
                                }
                                
                                collapseCount++;
                            }
                        });
                        
                        if (collapseCount > 0) {
                            console.log('🔽 欄位變化後全部收合', {count: collapseCount});
                        }
                    } finally {
                        // 🔓 使用 requestAnimationFrame 延遲釋放鎖
                        requestAnimationFrame(function() {
                            self._isReInserting = false;
                        });
                    }
                });
            }
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
            
            // 合併儲存的篩選參數（從當前使用的 config module 取得 currentFilterParams）
            var filterParams = self._currentConfigModule.currentFilterParams || {};
            params = $.extend({}, params, filterParams);
            
            // 直接使用當前切換後的配置的 ajaxURL（因為 self.tabulatorConfig 已經是切換後的配置）
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

        /**
         * 處理批量操作
         * 註：download_monthly_summary 不強制勾選（前者直接下載，後者顯示 popup）
         */
        handleBatchAction: function() {
            var self = this;
            var selectedAction = $('#batch-action-select').val();
            
            if (selectedAction && selectedAction !== '') {
                var selectedData = self.tabulatorInstance.getSelectedData();
                var selectedIds = selectedData.map(function(row) {
                    return row.entity_id || row.id;
                });
                var allowWithoutSelection = selectedAction === 'download_monthly_summary';

                if (selectedData.length > 0 || allowWithoutSelection) {
                    // 執行批量操作，並傳遞上下文物件給各個操作模組
                    selectAction.executeAction(selectedAction, selectedData, selectedIds, {
                        // 傳遞 Tabulator 實例供操作模組使用
                        tabulatorInstance: self.tabulatorInstance,
                        // 定義重置函數：當批量操作完成後自動重置批量操作選擇器為空值
                        resetBatchActionSelect: function() {
                            selectAction.resetBatchActionSelect();
                        },
                        // 定義更新表格函數：保留篩選條件
                        updateTabulator: self.updateTabulator.bind(self)
                    });
                } else {
                    toastMessage.warning($.mage.__('Please select at least one item to perform the action.'));
                    // 將批量操作選擇器重置為空值
                    selectAction.resetBatchActionSelect();
                }
            } else {
                // 使用 updateTabulator 保留篩選條件
                self.updateTabulator();
                self.tabulatorInstance.on("dataLoaded", function() {
                    loadingMask.hide();
                });
            }
        },

        /**
         * 處理 DataTree 節點展開（動態載入例外授權名單）
         * 
         * @param {Row} row - Tabulator Row Component
         */
        handleDataTreeExpand: function(row) {
            var self = this;
            var rowData = row.getData();
            var parentId = String(rowData.id || rowData.entity_id);
            
            console.log('🌳 DataTree 展開', {parentId: parentId, cache: self._exceptionDataCache[parentId]});
            
            // 優先檢查全局緩存
            if (self._exceptionDataCache[parentId]) {
                console.log('✅ 使用緩存資料', {parentId: parentId});
                rowData._children = self._exceptionDataCache[parentId].data;
                row.update(rowData);
                return;
            }
            
            // 如果已經載入過子資料（行級緩存），不重複載入
            if (rowData._children && rowData._children.length > 0) {
                console.log('✅ 使用行級緩存', {parentId: parentId});
                return;
            }
            
            console.log('🔄 呼叫 API 載入例外授權名單', {parentId: parentId});
            
            // 呼叫 API 取得例外授權名單
            apiExceptionAuth.getBatchExceptionAuth([parentId], {
                showLoader: true  // 控制是否顯示全螨幕 loading
            })
            .done(function(response) {
                var exceptionItems = response.items[parentId] || [];

                console.log('🔄 例外授權名單', exceptionItems)
                
                if (exceptionItems.length === 0) {
                    toastMessage.success($.mage.__('No exception authorization list found.'));
                    return;
                }
                
                // ⭐ 使用統一轉換函數（確保與主表格格式一致）
                var childData = exceptionItems.map(function(item) {
                    var rowData = tabulatorConfig._transformReconciliationItem(item);
                    rowData.data_tree_row = true;
                    return rowData;
                });

                // 💾 儲存到全局緩存
                self._exceptionDataCache[parentId] = {
                    data: childData,
                    timestamp: new Date().getTime()
                };
                console.log('💾 已緩存例外授權名單', {parentId: parentId, count: childData.length});
                
                // 直接更新 _children
                rowData._children = childData;
                row.update(rowData);
            })
            .fail(function(error) {
                console.error('❌ 載入例外授權名單失敗', error);
                toastMessage.error($.mage.__('Failed to load exception authorization list.'));
            });
        },

        /**
         * 處理例外授權名單展開/收合（HTML 模式）
         * 
         * @param {Row} row - Tabulator Row Component
         */
        handleNestedTableToggle: function(row) {
            var rowData = row.getData();
            
            if (rowData._expanded) {
                this.collapseNestedTable(row, rowData);
            } else {
                this.expandNestedTable(row, rowData, rowData.id || rowData.entity_id);
            }
        },

        /**
         * 收合嵌套表格（HTML 模式）
         * 
         * @param {Row} row - Tabulator Row Component
         * @param {Object} rowData - 行資料
         */
        collapseNestedTable: function(row, rowData) {
            // 更新狀態
            rowData._expanded = false;
            
            // 移除插入的 HTML
            var rowElement = row.getElement();
            var nestedWrapper = rowElement.nextElementSibling;
            if (nestedWrapper && nestedWrapper.classList.contains(this.nested.body)) {
                nestedWrapper.remove();
            }
            
            // 更新按鈕圖示
            var btnElement = rowElement.querySelector('.' + this.nested.expandBtn);
            if (btnElement) {
                btnElement.textContent = '➕';
            }
        },

        /**
         * 展開嵌套表格（HTML 模式 - 動態載入例外授權名單）
         * 
         * @param {Row} row - Tabulator Row Component
         * @param {Object} rowData - 行資料
         * @param {string|number} parentId - 父記錄 ID
         */
        expandNestedTable: function(row, rowData, parentId) {
            var self = this;
            parentId = String(parentId);
            
            console.log('📄 HTML 展開', {parentId: parentId, cache: self._exceptionDataCache[parentId]});
            
            // 優先檢查全局緩存
            if (self._exceptionDataCache[parentId]) {
                console.log('✅ 使用緩存資料', {parentId: parentId});
                var cachedData = self._exceptionDataCache[parentId].data;
                rowData._childData = cachedData;
                self.renderNestedHTML(row, rowData, cachedData);
                return;
            }
            
            // 如果已載入過（行級緩存），直接使用
            if (rowData._childData) {
                console.log('✅ 使用行級緩存', {parentId: parentId});
                self.renderNestedHTML(row, rowData, rowData._childData);
                return;
            }
            
            console.log('🔄 呼叫 API 載入例外授權名單', {parentId: parentId});
            
            // 呼叫 API 取得例外授權名單
            apiExceptionAuth.getExceptionAuthList([parentId], {
                showLoader: false
            })
            .done(function(response) {
                var exceptionItems = response.items || [];
                if (exceptionItems.length === 0) {
                    toastMessage.info($.mage.__('No exception authorization list found'));
                    return;
                }
                
                // 💾 儲存到全局緩存
                self._exceptionDataCache[parentId] = {
                    data: exceptionItems,
                    timestamp: new Date().getTime()
                };
                console.log('💾 已緩存例外授權名單', {parentId: parentId, count: exceptionItems.length});
                
                // 快取資料並渲染
                rowData._childData = exceptionItems;
                self.renderNestedHTML(row, rowData, exceptionItems);
            })
            .fail(function(error) {
                console.error('❌ 載入例外授權名單失敗', error);
                toastMessage.error($.mage.__('Failed to load exception authorization list'));
            });
        },
        
        /**
         * 渲染例外授權名單 HTML 內容
         * 
         * @param {Row} row - Tabulator Row Component
         * @param {Object} rowData - 行資料
         * @param {Array} childData - 例外授權名單資料
         */
        renderNestedHTML: function(row, rowData, childData) {
            // 更新狀態
            rowData._expanded = true;
            
            // 檢查是否已經插入過（避免重複插入）
            var rowElement = row.getElement();
            var existingNested = rowElement.nextElementSibling;
            if (existingNested && existingNested.classList.contains(this.nested.body)) {
                return;
            }
            
            // 建立嵌套內容 HTML
            // var nestedHTML = exceptionAuthorizationCardV4.render(rowData, childData);
            var nestedHTML = exceptionAuthorizationCardV5.render(rowData, childData);
            
            // 在當前 row 下方插入一個新的 div
            var nestedWrapper = document.createElement('div');
            nestedWrapper.classList.add(this.nested.body);
            nestedWrapper.setAttribute('data-row-id', rowData.id);
            nestedWrapper.innerHTML = nestedHTML;
            
            // 找到正確的插入位置（跳過其他 nested-content-body）
            var nextRow = rowElement.nextElementSibling;
            while (nextRow && nextRow.classList.contains(this.nested.body)) {
                nextRow = nextRow.nextElementSibling;
            }
            
            // 插入到下一個真正的 row 之前（或 parentNode 的末尾）
            if (nextRow) {
                rowElement.parentNode.insertBefore(nestedWrapper, nextRow);
            } else {
                rowElement.parentNode.appendChild(nestedWrapper);
            }
            
            // 更新按鈕圖示
            var btnElement = rowElement.querySelector('.' + this.nested.expandBtn);
            if (btnElement) {
                btnElement.textContent = '➖';
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



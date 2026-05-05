/**
 * HotaiConnected CheckoutManagement - 對帳區域 Tabulator 配置
 * 
 * 此檔案包含對帳區域表格的業務特定配置
 * 基於 UiShared 的 getRemoteConfig() 遠端分頁框架
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config
 * @version 4.2.0
 * @author HotaiConnected
 * @updated 2025-10-29 - 新增：dataTree 樹狀結構功能，支援動態載入子節點
 * @updated 2025-10-27 - 重構：採用 BEM 架構重構 vendor-invoice 元件
 * @updated 2025-10-23 - 修正：允許 vendor-invoice-input 輸入框正常輸入
 * @updated 2025-10-22 - 繼承 UiShared 的 movableColumns 功能，允許拖曳欄位排序
 * 
 * ========================================
 * 配置繼承關係
 * ========================================
 * 
 * UiShared.getRemoteConfig()        ← 提供遠端分頁框架與通用配置
 * ├── pagination: true
 * ├── paginationMode: "remote"
 * ├── paginationSize: 20
 * ├── dataSendParams: { page: "current_page", size: "page_size" }
 * ├── movableColumns: true
 * ├── ajaxMethod: 'POST'
 * ├── ajaxContentType: 'json'
 * ├── layout: 'fitData'
 * ├── locale: "zh-tw"
 * └── ajaxResponse: (空實作，業務模組必須實作)
 * 
 * CheckoutManagement (本模組)       ← 覆蓋業務特定配置
 * ├── ajaxURL: /rest/V1/reconciliation
 * ├── paginationSize: 10 (覆蓋 UiShared 預設 20)
 * ├── ajaxResponse: (實作欄位轉換與分頁計算)
 * └── columns: [...] (對帳專用欄位)
 * 
 * ========================================
 * API 格式說明
 * ========================================
 * 
 * 官方文件：https://tabulator.info/docs/6.3/page#remote
 * 
 * API 回應格式（後端）：
 * {
 *     "items": [...],           // 當前頁資料
 *     "total_record": 150,      // 總筆數
 *     "page_size": 10           // 每頁筆數
 * }
 * 
 * Tabulator 期望格式（ajaxResponse 返回）：
 * {
 *     "last_page": 15,          // 總頁數（必要）
 *     "data": [...]             // 當前頁資料（必要）
 * }
 * 
 * ========================================
 * 業務配置內容
 * ========================================
 * 
 * 1. ajaxURL
 *    - API 端點：/rest/V1/reconciliation
 * 
 * 2. paginationSize
 *    - 每頁筆數：10（覆蓋 UiShared 的預設 20）
 * 
 * 3. dataSendParams
 *    - 繼承 UiShared 預設值：{ page: "current_page", size: "page_size" }
 *    - 符合 Magento 標準 REST API 規範
 *    - 若 API 參數不同，可在 businessConfig 中覆蓋（參考程式碼註解）
 * 
 * 4. ajaxResponse (覆蓋 UiShared)
 *    - 處理欄位名稱對應（API 欄位 → Tabulator columns）
 *    - 資料來源：response.items, response.total_record, response.page_size
 *    - 返回格式：{ last_page: N, data: transformedData }
 * 
 * 5. columns
 *    - 對帳區域特定的欄位定義
 *    - 包含操作按鈕（下載、解鎖等）
 *    - 所有欄位設定 widthShrink: 0 確保資料完整顯示
 * 
 * 6. movableColumns
 *    - 繼承 UiShared 預設值：true
 *    - 允許使用者拖曳欄位標題來重新排列順序
 *    - 凍結欄位（rowHeader）無法移動
 * 
 * ========================================
 * 使用方式
 * ========================================
 * 
 * @example
 * // 在 index.js 中使用
 * define([
 *     'tabulator',
 *     'HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config'
 * ], function(Tabulator, tabulatorConfig) {
 *     var config = tabulatorConfig.getConfig();
 *     new Tabulator("#table", config);
 * });
 */
define([
    // Magento 內建功能
    'jquery',
    'mage/template',
    'mage/translate',
    'mage/url',
    
    // UiShared 通用配置
    'tabulatorConfig',
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',
    
    // 模板
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/vendor-invoice-group.html'
], function ($, mageTemplate, $t, urlBuilder, baseTabulatorConfig, reconciliationApi, vendorInvoiceGroupTemplate) {
    'use strict';

    return {
        emptyValue: '-',
        /**
         * AJAX 載入資料的 URL (對帳系統 API)
         * 
         * 注意：此 URL 僅用於模組頂層定義，實際使用的 URL 在 getConfig() 的 businessConfig.ajaxURL 中
         * 當 data_source = '2' 時，會切換到 exception-auth-config.js，使用其 ajaxURL
         */
        ajaxURL: urlBuilder.build(reconciliationApi.endpoints.base),

        /**
         * 狀態顯示模式開關
         * false（預設）：只顯示有的狀態
         * true：顯示所有可能的狀態（有的綠燈，沒有的灰燈）
         */
        showAllStatuses: false,

        /**
         * 預設的每頁筆數（清除篩選時使用）
         */
        defaultPageSize: 10,
        
        /**
         * 清除篩選時是否重置每頁筆數
         * true: 清除時將每頁筆數重置為 defaultPageSize
         * false: 清除時不重置每頁筆數，保留使用者設定的每頁筆數
         */
        resetPageSizeOnClear: false,

        pageSizeMax: 10,

        dataTreeRow: false,

        scopeAcl: {
            // 下載廠商對帳單權限
            download_vendor_statement: document.querySelector('#batch-action-select option[value="download_vendor_statement"]') ? true : false,
            // 解除按鈕權限
            unlock_checkout: document.querySelector('#batch-action-select option[value="unlock_checkout"]') ? true : false,
            // 廠商發票編輯權限
            invoiceEditable: document.querySelector('.checkout-area__table').getAttribute('data-invoice-editable') === 'true',
            // 例外授權子選單權限
            exceptionAuthorizationSubTable: document.querySelector('.checkout-area__table').getAttribute('data-exception-authorization-subtable') === 'true',
        },
        
        /**
         * 儲存當前的 filters 參數（用於換頁時保留篩選條件）
         * 
         * 此屬性應在業務模組的 config 物件中定義，因為：
         * 1. 它是狀態屬性，會動態變化（apply-filters 時更新，clear-filters 時清除）
         * 2. 需要在 ajaxURLGenerator 和 index.js 中存取業務模組的 config 物件
         * 3. 每個業務模組實例需要獨立的狀態
         * 
         * 使用方式：
         * - apply-filters 時：tabulatorConfig.currentFilterParams = params || {};
         * - clear-filters 時：tabulatorConfig.currentFilterParams = {};
         * - ajaxURLGenerator 中：var filterParams = self.currentFilterParams || {};
         */
        currentFilterParams: {},
        
        /**
         * 例外處理功能配置
         * 
         * 統一管理例外處理功能的開關、顯示模式及相關 Tabulator 配置
         * 
         * @property {boolean} enabled - 功能總開關（關閉後整個功能不顯示）
         * @property {string} displayMode - 顯示模式：'html' 或 'dataTree'
         *   - 'html': 在 row 下方插入自訂 HTML 內容
         *   - 'dataTree': 使用 Tabulator 內建的樹狀結構
         */
        exceptionConfig: {
            enabled: true,              // 功能總開關
            displayMode: 'dataTree',    // 'html' 或 'dataTree'
            templateVersion: 'v4',      // 例外授權卡片版型：目前僅支援 'v4'

            /**
             * 判斷是否為指定模式
             * @param {string} mode - 要檢查的模式 ('html' 或 'dataTree')
             * @returns {boolean}
             */
            isMode: function(mode) {
                return this.enabled && this.displayMode === mode;
            },
            
            /**
             * 取得 DataTree 模式的 Tabulator 配置
             * @returns {Object} DataTree 配置物件（非 DataTree 模式返回空物件）
             */
            getDataTreeConfig: function() {
                if (!this.isMode('dataTree')) {
                    return {}; // HTML 模式或功能關閉時不需要額外配置
                }
                
                return {
                    dataTree: true,
                    dataTreeChildField: "_children",
                    dataTreeElementColumn: "data_source",
                    selectableRows: "highlight",
                    selectableCheck: function(row) {
                        return row.getTreeLevel() === 0; // 只允許選擇主表格行（level 0）
                    }
                };
            },
            
            /**
             * 取得布局配置（根據模式決定是否使用虛擬 DOM）
             * @returns {Object} 布局配置物件
             */
            getLayoutConfig: function() {
                if (this.isMode('html')) {
                    // HTML 模式：禁用虛擬 DOM，讓表格自適應高度
                    // 這樣手動插入的 DOM 元素就不會被清除
                    return {
                        height: false,           // 禁用固定高度
                        renderVertical: "basic"  // 禁用虛擬 DOM
                    };
                }
                
                // DataTree 模式或功能關閉：使用預設配置（虛擬 DOM）
                return {};
            },

        },
        
        /**
         * 轉換對帳資料項目（統一資料轉換邏輯，消除重複代碼）
         * @private
         * 
         * 此函數為純粹的資料轉換函數，只負責：
         * - 欄位名稱映射（後端 API 欄位 → 前端 Tabulator 欄位）
         * - 預設值設定
         * - 資料格式轉換（例如：字串分割為陣列）
         * 
         * 不處理業務邏輯（如 _children、data_tree_row 的條件設定）
         * 業務邏輯應在呼叫處根據需求設定
         * 
         * @param {Object} item - 後端 API 返回的原始資料項目
         * @returns {Object} 轉換後的資料物件（包含所有基礎欄位 + 預設值）
         */
        _transformReconciliationItem: function(item) {
            var self = this;
            return {
                entity_id: item.id,
                id: item.id,
                // ⭐ 是否有例外授權（直接從後端讀取）
                // 後端應提供 has_exception: true/false
                // true: 有例外授權名單，會顯示展開按鈕
                // false: 無例外授權名單，不顯示按鈕
                has_exception: item.has_exception || false,
                // 資料來源: 注意，如果是 show_value 有值，則代表此次進來的資料屬於子選單，所以資料來源為空，直接只顯示 dataTree icon
                data_source: item.show_value ? $.mage.__('Exception Authorization') : item.data_source || '',
                // 特約商名稱
                shop_title: item.shop_title || self.emptyValue,
                // 特約商代號
                seller_code: item.seller_code || self.emptyValue,
                // 售價
                price: item.price || self.emptyValue,
                // 抽成%
                commission_rate: item.commission_rate || self.emptyValue,
                // 總付款額
                total_paid: item.total_paid || self.emptyValue,
                // 負責館長
                salesperson_role_name: item.salesperson_role_name || self.emptyValue,
                // 結帳日期
                created_at: item.created_at || self.emptyValue,
                // 結帳批次
                batch_num: item.batch_num || self.emptyValue,
                // 帳單狀態
                settlement_status: item.show_value ? $.mage.__('Additional License') : item.settlement_status || self.emptyValue,
                // 解除批次按鈕
                del_btn_visible: item.del_btn_visible || false,
                // 票券狀態
                ticket_status: item.ticket_status || self.emptyValue,
                // 商品物流狀態
                shipping_status: item.shipping_status || self.emptyValue,
                // 發票狀態
                invoice_status: item.invoice_status || self.emptyValue,
                // 發票號碼
                invoice_numbers: typeof item.invoice_numbers === 'string' 
                    ? item.invoice_numbers.split(',') 
                    : item.invoice_numbers !== null ? [item.invoice_numbers] : [],
                
                // 原因
                reason: item.reason || self.emptyValue,
                // 審查結果
                exception_status: item.exception_status || self.emptyValue,
                // 訂單結帳序號異動日(起)
                from: item.from || self.emptyValue,
                // 訂單結帳序號異動日(訖)
                to: item.to || self.emptyValue,

                // ========================================
                // 審核子選單調整欄位預設值
                // ========================================
                
                // 審核狀態
                exception_status: item.exception_status || self.emptyValue,
                // 原因
                reason: item.reason || self.emptyValue,
                // 顯示內容
                // - 抽成%
                // - 採購成本
                // - 特約商專櫃銷售淨額
                // - 約定服務費
                // - 行銷負擔(廠商)
                // - 運費補貼
                show_value: item.show_value || {},
                
                // ========================================
                // 特殊欄位預設值（呼叫處可根據需求覆蓋）
                // ========================================
                
                // DataTree 子行標記
                // - false: 主表格資料（預設）
                // - true: DataTree 子資料（呼叫處覆蓋）
                data_tree_row: self.dataTreeRow,
                
                // DataTree 子資料欄位
                // - undefined: 無子資料，不顯示展開按鈕（預設）
                // - []: 有子資料但尚未載入，顯示 ▶ 按鈕（呼叫處設定）
                // - [...]: 已載入的子資料陣列
                _children: undefined,

                editable: self.scopeAcl.invoiceEditable
            };
        },
        
        /**
         * 建立狀態指示器 formatter（內部 helper，消除重複代碼）
         * @private
         * 
         * @param {string} statusType - 狀態類型（'ticket', 'shipping', 'invoice'）
         * @param {Function} getStatusMaps - 動態獲取狀態映射表的函數
         * @param {Function} getStatusName - 狀態名稱查詢函數
         * @param {Function} [getValueFn] - 自訂取值函數（預設用 cell.getValue()）
         * @returns {Function} Tabulator formatter 函數
         */
        _createStatusFormatter: function(statusType, getStatusMaps, getStatusName, getValueFn) {
            var self = this;
            
            return function(cell) {
                // 取得值（支援自訂取值函數）
                var value = getValueFn ? getValueFn(cell) : cell.getValue();
                
                // 處理空值
                if (!value || value === '-') {
                    return '-';
                }
                
                // 統一數據準備（動態獲取最新的 statusMaps）
                var statusMaps = getStatusMaps();  // ← 動態調用
                var statusMap = statusMaps[statusType] || {};
                var activeStatuses = typeof value === 'string' ? value.split(',').map(s => s.trim()) : 
                                   (Array.isArray(value) ? value : [value]);
                
                // 決定要顯示的狀態列表
                var codesToDisplay = self.showAllStatuses ? 
                    Object.keys(statusMap) :  // 顯示全部：所有可能的狀態
                    activeStatuses;           // 只顯示有的：活躍狀態
                
                // 統一生成 HTML
                var html = '';
                codesToDisplay.forEach(function(code) {
                    // 統一從 map 獲取顯示名稱（容錯處理）
                    var displayName = statusMap[code] || code;
                    
                    // 判斷是否活躍（用於灰燈樣式）
                    var isActive = activeStatuses.includes(code) || value === 'all';
                    var modifierClass = (self.showAllStatuses && !isActive) ? 
                        ' ' + statusType + '-status__item--inactive' : '';
                    
                    // 簡單結構：單層 span，添加 title 提示
                    html += '<span class="' + statusType + '-status__item' + modifierClass + '" title="' + displayName + '">' + displayName + '</span>';
                });
                
                return '<div class="' + statusType + '-status">' + html + '</div>';
            };
        },
        
        /**
         * 取得完整的 Tabulator 配置（合併通用配置和業務特定配置）
         * 
         * @param {Object} options - 配置選項
         * @param {Function} options.getStatusName - 統一的狀態名稱查詢函數 (type, code) => displayName
         * @param {Function} options.getStatusMaps - 動態獲取狀態映射表的函數 () => {ticket: {}, invoice: {}, shipping: {}}
         * @returns {Object} 完整的 Tabulator 配置
         */
        getConfig: function(options) {
            options = options || {};
            var getStatusName = options.getStatusName || function(type, code) { return code; };
            var getStatusMaps = options.getStatusMaps || function() { return {ticket: {}, invoice: {}, shipping: {}}; };
            var self = this;

            var businessConfig = {
                // ==========================================================
                // 業務特定配置 (Business Specific Configuration)
                // ==========================================================
                
                // 1. AJAX 載入資料的 URL (對帳系統 API)
                ajaxURL: urlBuilder.build(reconciliationApi.endpoints.base),
                ajaxURLGenerator:function(url, config, params){
                    console.log('ajaxURLGenerator', url, config, params);
                    delete params.data_source;
                    // 因為 true 是 Tabulator 內建的 pageSize "全選" 參數，所以需要特殊處理
                    if(params.page_size === true){
                        params.page_size = self.pageSizeMax;
                    }

                    // 合併當前的 filters 參數到請求參數中（從 self.currentFilterParams 讀取）
                    var filterParams = self.currentFilterParams || {};
                    var mergedParams = $.extend({}, filterParams, params);
                    
                    const searchParams = new URLSearchParams(mergedParams);
                    return url + "?" + searchParams.toString();
                },
                
                // 2. AJAX 回應處理（覆蓋 UiShared 的 ajaxResponse）
                // 原因：需要將 API 欄位名稱轉換為 Tabulator columns 的欄位名稱
                // 
                // 資料來源：response (API 回應)
                // 返回格式：{ last_page: N, data: transformedData }
                //
                ajaxResponse: function(url, params, response) {
                    if (response.success === false) {
                        console.error('❌ API 錯誤', response);
                        throw new Error(response.message || 'API 請求失敗');
                    }

                    self.pageSizeMax = response.total_record || self.pageSizeMax;

                    var items = response.items || [];
                    var totalRecord = response.total_record || 0;
                    var pageSize = response.page_size || params.page_size || 10;
                    var lastPage = Math.ceil(totalRecord / pageSize);
                    
                    // 轉換欄位名稱（使用統一的轉換函數）
                    var transformedData = items.map(function(item) {
                        // ⭐ 基礎轉換（純函數，無業務邏輯）
                        var rowData = self._transformReconciliationItem(item);
                        
                        // 🎭 測試用：模擬所有資料都有例外（實際使用時應移除此行）
                        // 實際使用：has_exception 已在 _transformReconciliationItem 中從 item.has_exception 讀取
                        
                        // 🌳 DataTree 模式專屬：有例外資料時設定 _children 為空陣列
                        if (self.exceptionConfig.isMode('dataTree') && rowData.has_exception) {
                            rowData._children = [];
                        }
                        
                        return rowData;
                    });
                    
                    console.log('📊 主表格資料載入完成', {
                        totalRows: transformedData.length,
                        mode: self.exceptionConfig.displayMode,
                        firstRowHasChildren: transformedData[0] && transformedData[0]._children !== undefined
                    });
                    
                    // ⭐ 返回符合官方規範的物件格式 ⭐
                    return {
                        last_page: lastPage,
                        data: transformedData
                    };
                },

                columnDefaults:{
                    minWidth: 120,
                },
                // 4. 欄位定義（對應 ajaxResponse 轉換後的欄位名稱）
                columns: [
                    // 資料來源（含例外處理按鈕）
                    { 
                        title: $.mage.__('Resource Source'), 
                        field: 'data_source',
                        hozAlign: "left",
                        formatter: function(cell) {
                            var rowData = cell.getRow().getData();
                            var value = self.currentFilterParams.data_source === '2' ? $.mage.__('Exception') : cell.getValue();
                            var html = '';
                            
                            // ⭐ 只有在 HTML 模式且有例外資料時才顯示 ➕➖ 按鈕
                            var showButton = self.scopeAcl.exceptionAuthorizationSubTable && self.exceptionConfig.isMode('html') && rowData.has_exception ;
                            
                            if (showButton) {
                                var isExpanded = rowData._expanded || false;
                                var icon = isExpanded ? '➖' : '➕';
                                html += '<button class="action-default nested-expand-btn" type="button" style="margin-right: 8px; padding: 2px 8px; font-size: 14px;">' + icon + '</button>';
                            }
                            
                            html += '<span>' + value + '</span>';
                            return html;
                        },
                        cellClick: function(e, cell) {
                            // 只處理按鈕點擊
                            if (e.target.classList.contains('nested-expand-btn')) {
                                // 觸發自訂事件，由主控制器處理
                                $(document).trigger('nested-table:toggle', [cell.getRow()]);
                            }
                        }
                    },

                    // 特約商名稱
                    { 
                        title: $.mage.__('Authorized Dealer Name'), 
                        field: 'shop_title',
                        minWidth: 180,
                        hozAlign : "left",
                    },

                    // 特約商代號
                    { 
                        title: $.mage.__('Authorized Dealer Code'), 
                        field: 'seller_code',
                        hozAlign : "left",
                        minWidth: 180,
                    },

                    // 售價
                    { 
                        title: $.mage.__('Sell Price'), 
                        field: 'price', 
                        formatter: 'money', 
                        formatterParams: { 
                            decimal: '.',
                            thousand: ',',
                            symbol: '$'
                        },
                        hozAlign : "right"
                    },

                    // 抽成%
                    { 
                        title: $.mage.__('Commission %'), 
                        field: 'commission_rate',
                        hozAlign : "right",
                        formatter: (cell) => {
                            return cell.getValue() === self.emptyValue ? self.emptyValue : cell.getValue() + '%';
                        }
                    },

                    // 總付款額
                    { 
                        title: $.mage.__('Total Payment Amount'), 
                        field: 'total_paid', 
                        formatter: 'money', 
                        formatterParams: { 
                            decimal: '.',
                            thousand: ',',
                            symbol: '$'
                        },
                        hozAlign : "right"
                    },

                    // 負責館長
                    { 
                        title: $.mage.__('Principal Curator'),
                        field: 'salesperson_role_name'
                    },

                    // 結帳日期
                    { 
                        title: $.mage.__('Checkout Date'),
                        field: 'created_at',
                        minWidth: 180,
                    },

                    // 結帳批次
                    { 
                        title: $.mage.__('Checkout Batch'),
                        field: 'batch_num',
                        hozAlign : "left",
                    },

                    // 票券狀態
                    {
                        title: $.mage.__('Ticket Status'),
                        field: 'ticket_status',
                        minWidth: 150,
                        hozAlign: "left",
                        visible: false,
                        formatter: self._createStatusFormatter('ticket', getStatusMaps, getStatusName)
                    },

                    // 商品物流狀態
                    { 
                        title: $.mage.__('Product Shipping Status'),
                        field: 'shipping_status',
                        minWidth: 150,
                        hozAlign : "left",
                        visible: false,
                        formatter: self._createStatusFormatter('shipping', getStatusMaps, getStatusName)
                    },

                    // 發票狀態
                    { 
                        title: $.mage.__('Invoice Status'),
                        field: 'invoice_status',
                        minWidth: 150,
                        hozAlign: "left",
                        visible: false,
                        formatter: self._createStatusFormatter('invoice', getStatusMaps, getStatusName)
                    },

                    //  帳單狀態
                    { 
                        title: $.mage.__('Settlement Status'),
                        field: 'settlement_status'
                    },

                    // 下載廠商對帳單 - 使用事件觸發，不直接調用業務邏輯
                    {
                        title: $.mage.__('Download %1').replace('%1', $.mage.__('Vendor Statement')),
                        field: 'download_vendor_statement',
                        minWidth: 150,
                        visible: self.scopeAcl.download_vendor_statement,
                        formatter: (cell) => {
                            var rowData = cell.getRow().getData();
                            return !rowData.data_tree_row ? '<button class="action-default" type="button"><span>' + $.mage.__('Download') + '</span></button>' : self.emptyValue;
                        },
                        cellClick: function(e, cell) {
                            var rowData = cell.getRow().getData();
                            var selectedIds = [rowData.entity_id || rowData.id];
                            // 觸發自定義事件，由主控制器處理
                            $(document).trigger('vendor-statement:download', [rowData, selectedIds]);
                        }
                    },

                    // 解除批次 - 使用事件觸發，不直接調用業務邏輯
                    {
                        title: $.mage.__('Batch Unlock'),
                        field: 'del_btn_visible',
                        visible: self.scopeAcl.unlock_checkout,
                        formatter: (cell) => {
                            var rowData = cell.getRow().getData();
                            return !rowData.data_tree_row && rowData.del_btn_visible ? '<button class="action-default settlement-status-action-btn" type="button"><span>' + $.mage.__('Release') + '</span></button>' : self.emptyValue;
                        },
                        cellClick: function(e, cell) {
                            // 只有點擊按鈕或按鈕內的元素（如 span）時才觸發
                            if (!e.target.closest('button.settlement-status-action-btn')) {
                                return;
                            }

                            var rowData = cell.getRow().getData();
                            var selectedIds = [rowData.entity_id || rowData.id];
                            // 觸發自定義事件，由主控制器處理
                            $(document).trigger('unlock-checkout:show', [rowData, selectedIds]);
                        }
                    },

                    // 廠商發票號碼 - 使用優化的模板渲染
                    {
                        title: $.mage.__('Vendor Invoice Number'),
                        field: 'invoice_numbers',
                        minWidth: self.scopeAcl.invoiceEditable ? 320 : 240,
                        hozAlign : "left",
                        headerSort: false,
                        formatter: function(cell) {
                            var rowData = cell.getRow().getData();
                            if(rowData.data_tree_row) return self.emptyValue;

                            var value = cell.getValue();
                            var rowData = cell.getRow().getData();
                            var rowId = rowData.id || rowData.entity_id;
                            
                            // 處理不同的資料格式
                            var invoiceNumbers = [];
                            if (Array.isArray(value)) {
                                invoiceNumbers = value;
                            } else if (typeof value === 'string' && value.trim() !== '') {
                                invoiceNumbers = value.split(',').map(v => v.trim()).filter(v => v !== '');
                            }
                            
                            // 如果沒有資料，顯示一個空的輸入框
                            if (invoiceNumbers.length === 0) {
                                invoiceNumbers = [''];
                            }
                            
                            // 生成 HTML 結構
                            var html = '<div class="vendor-invoice">';
                            
                            invoiceNumbers.forEach((invoiceNumber, index) => {
                                var hasValue = invoiceNumber && invoiceNumber.trim() !== '';
                                var country = rowData.country || 'TW'; // 從 rowData 取得國家代碼
                                var templateData = {
                                    index: index,
                                    value: invoiceNumber || '',
                                    rowId: rowId,
                                    country: country,
                                    hasValue: hasValue,
                                    confirmText: $.mage.__('Confirm'),
                                    editText: $.mage.__('Edit'),
                                    cancelText: $.mage.__('Cancel'),
                                    dataTreeRow: rowData.data_tree_row,
                                    editable: rowData.editable
                                };
                                html += mageTemplate(vendorInvoiceGroupTemplate, templateData);
                            });
                            
                            html += '</div>';
                            
                            return html;
                        },
                        // 添加點擊事件處理 - 使用事件觸發，不直接調用業務邏輯
                        cellClick: function(e, cell) {
                            var target = e.target;
                            
                            // ⭐ 如果點擊的是 input，不做任何處理，讓 input 正常工作
                            if (target.classList.contains('vendor-invoice__input')) {
                                return;
                            }
                            
                            var row = cell.getRow();
                            var table = cell.getTable();
                            var rowData = row.getData();
                            
                            if (target.classList.contains('vendor-invoice__btn--confirm')) {
                                $(document).trigger('vendor-invoice:confirm', [target, rowData]);
                            } else if (target.classList.contains('vendor-invoice__btn--edit')) {
                                $(document).trigger('vendor-invoice:edit', [target, rowData]);
                            } else if (target.classList.contains('vendor-invoice__btn--cancel')) {
                                $(document).trigger('vendor-invoice:cancel', [target, rowData]);
                            } else if (target.classList.contains('vendor-invoice__btn--plus')) {
                                $(document).trigger('vendor-invoice:plus', [target, rowData]);
                            } else if (target.classList.contains('vendor-invoice__btn--minus')) {
                                $(document).trigger('vendor-invoice:minus', [target, rowData]);
                            }

                            // 重新計算表格高度
                            row.normalizeHeight();
                            // 重新繪製表格高度，避免只有row的height被調整，導致表格內出現滾輪，需要上下滑動才能看到完整內容
                            table.rowManager.redraw();
                        }
                    },
                    //  原因
                    { 
                        title: $.mage.__('Reason'),
                        field: 'reason',
                        visible: false,
                    },
                    //  調整欄位
                    { 
                        title: $.mage.__('Adjust Columns'),
                        field: 'show_value',
                        minWidth: 200,
                        hozAlign: "left",
                        visible: false,
                        formatter: (cell) => {
                            var rowData = cell.getRow().getData();

                            var html = [];
                            Object.keys(rowData.show_value).forEach(function(key) {
                                html.push('<p>' + key + ': ' + rowData.show_value[key] + '</p>');
                            });
                            return html.length > 0 ? '<div>' + html.join('') + '</div>' : self.emptyValue;
                        }
                    },
                    //  審查結果
                    { 
                        title: $.mage.__('Review Result'),
                        field: 'exception_status',
                        visible: false,
                    },
                    // 訂單結帳序號異動日(起)
                    { 
                        title: $.mage.__('Order Checkout Number Modification Date (From)'),
                        field: 'from',
                        minWidth: 200,
                        visible: false,
                        formatter: (cell) => {
                            var rowData = cell.getRow().getData();
                            var value = rowData.from;
                            if(value === self.emptyValue) return self.emptyValue;
                            return value.split(' ')[0]
                        }
                    },
                    // 訂單結帳序號異動日(訖)
                    { 
                        title: $.mage.__('Order Checkout Number Modification Date (To)'),
                        field: 'to',
                        minWidth: 200,
                        visible: false,
                        formatter: (cell) => {
                            var rowData = cell.getRow().getData();
                            var value = rowData.to;
                            if(value === self.emptyValue) return self.emptyValue;
                            return value.split(' ')[0]
                        },
                    }
                ],
                
                // ==========================================================
                // 🌳 DataTree 配置（條件性加入）
                // ==========================================================
                // 透過 exceptionConfig.getDataTreeConfig() 統一取得 DataTree 配置
                // 避免重複判斷邏輯，提升維護性
                ...self.exceptionConfig.getDataTreeConfig(),
                
                // ==========================================================
                // 📐 布局配置（條件性加入）
                // ==========================================================
                // HTML 模式：禁用虛擬 DOM，避免手動插入的 DOM 被清除
                // DataTree 模式：使用預設虛擬 DOM
                ...self.exceptionConfig.getLayoutConfig()
            };

            // 合併配置：UiShared 遠端分頁基礎配置 + 業務特定配置
            return $.extend(true, {}, baseTabulatorConfig.getRemoteConfig(), businessConfig);
        }
    };
});
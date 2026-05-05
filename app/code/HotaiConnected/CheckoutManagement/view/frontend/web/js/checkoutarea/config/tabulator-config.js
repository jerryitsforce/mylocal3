/**
 * HotaiConnected MarketplaceCheckout - 對帳區域 Tabulator 配置
 * 
 * 此檔案包含對帳區域表格的業務特定配置
 * 基於 UiShared 的 getRemoteConfig() 遠端分頁框架
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config
 * @version 4.2.0
 * @author HotaiConnected
 * @updated 2025-10-29 - 新增：dataTree 樹狀結構功能，支援動態載入子節點
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
 * MarketplaceCheckout (本模組)       ← 覆蓋業務特定配置
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
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
    
], function ($, mageTemplate, $t, urlBuilder, baseTabulatorConfig, reconciliationApi) {
    'use strict';

    return {
        emptyValue: '-',
        /**
         * AJAX 載入資料的 URL (對帳系統 API)
         * 
         * 注意：此 URL 僅用於模組頂層定義，實際使用的 URL 在 getConfig() 的 businessConfig.ajaxURL 中
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
            return {
                entity_id: item.id,
                id: item.id,
                // ⭐ 是否有例外授權（直接從後端讀取）
                // 後端應提供 has_exception: true/false
                // true: 有例外授權名單，會顯示展開按鈕
                // false: 無例外授權名單，不顯示按鈕
                has_exception: item.has_exception || false,
                // 資料來源
                data_source: $.mage.__('Reconciliation Created'),
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
                settlement_status: item.settlement_status || self.emptyValue,
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
                data_tree_row: false,
                
                // DataTree 子資料欄位
                // - undefined: 無子資料，不顯示展開按鈕（預設）
                // - []: 有子資料但尚未載入，顯示 ▶ 按鈕（呼叫處設定）
                // - [...]: 已載入的子資料陣列
                _children: undefined
            };
        },
        
        /**
         * 取得完整的 Tabulator 配置（合併通用配置和業務特定配置）
         * 
         * @param {Object} options - 配置選項
         * @returns {Object} 完整的 Tabulator 配置
         */
        getConfig: function(options) {
            options = options || {};
            var self = this;
            

            var businessConfig = {
                // ==========================================================
                // 業務特定配置 (Business Specific Configuration)
                // ==========================================================
                
                // 1. AJAX 載入資料的 URL (對帳系統 API)
                ajaxURL: urlBuilder.build(reconciliationApi.endpoints.base),

                ajaxURLGenerator:function(url, config, params){
                    console.log('ajaxURLGenerator', url, config, params);
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
                        formatter: function(cell) {
                            var value = cell.getValue();
                            var html = '';
                            
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
                        visible: false
                    },

                    // 商品物流狀態
                    { 
                        title: $.mage.__('Product Shipping Status'),
                        field: 'shipping_status',
                        minWidth: 150,
                        hozAlign : "left",
                        visible: false
                    },

                    // 發票狀態
                    { 
                        title: $.mage.__('Invoice Status'),
                        field: 'invoice_status',
                        minWidth: 150,
                        hozAlign: "left",
                        visible: false
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

                    // ========================================
                    // 子選單欄位 - 廠商目前沒有以下欄位
                    // ========================================
                    // //  原因
                    // { 
                    //     title: $.mage.__('Reason'),
                    //     field: 'reason'
                    // },
                    // //  調整欄位
                    // { 
                    //     title: $.mage.__('Adjust Columns'),
                    //     field: 'show_value',
                    //     minWidth: 200,
                    //     hozAlign: "left",
                    //     formatter: (cell) => {
                    //         var rowData = cell.getRow().getData();

                    //         var html = [];
                    //         Object.keys(rowData.show_value).forEach(function(key) {
                    //             html.push('<p>' + key + ': ' + rowData.show_value[key] + '</p>');
                    //         });
                    //         return html.length > 0 ? '<div>' + html.join('') + '</div>' : self.emptyValue;
                    //     }
                    // },
                    // //  審查結果
                    // { 
                    //     title: $.mage.__('Review Result'),
                    //     field: 'exception_status'
                    // }
                ],
                
                // ==========================================================
                // 📐 布局配置（條件性加入）
                // ==========================================================
                // HTML 模式：禁用虛擬 DOM，避免手動插入的 DOM 被清除
                // DataTree 模式：使用預設虛擬 DOM
                ...self.exceptionConfig.getLayoutConfig(),
                
                // ==========================================================
                // 🔲 禁用行選擇 checkbox
                // ==========================================================
                // 覆蓋 UiShared 預設的 rowHeader 配置，禁用表格前面的 checkbox
                rowHeader: false
            };
            
            // 合併配置：UiShared 遠端分頁基礎配置 + 業務特定配置
            return $.extend(true, {}, baseTabulatorConfig.getRemoteConfig(), businessConfig);
        }
    };
});
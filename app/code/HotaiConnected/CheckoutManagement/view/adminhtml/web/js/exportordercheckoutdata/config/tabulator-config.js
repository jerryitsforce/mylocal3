/**
 * HotaiConnected CheckoutManagement - 訂單結帳資料匯出 Tabulator 配置
 * 
 * 此檔案包含訂單結帳資料匯出表格的業務特定配置
 * 基於 UiShared 的 getRemoteConfig() 遠端分頁框架
 * 
 * @module HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/config/tabulator-config
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-12-09
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
 * ├── ajaxURL: /rest/V1/export-order-checkout-data (待確認)
 * ├── paginationSize: 20 (繼承 UiShared 預設)
 * ├── ajaxResponse: (實作欄位轉換與分頁計算)
 * └── columns: [...] (訂單結帳資料匯出專用欄位)
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
 *     "page_size": 20           // 每頁筆數
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
 *    - API 端點：/rest/V1/export-order-checkout-data (待確認實際端點)
 * 
 * 2. paginationSize
 *    - 每頁筆數：20（繼承 UiShared 預設）
 * 
 * 3. dataSendParams
 *    - 繼承 UiShared 預設值：{ page: "current_page", size: "page_size" }
 *    - 符合 Magento 標準 REST API 規範
 * 
 * 4. ajaxResponse (覆蓋 UiShared)
 *    - 處理欄位名稱對應（API 欄位 → Tabulator columns）
 *    - 資料來源：response.items, response.total_record, response.page_size
 *    - 返回格式：{ last_page: N, data: transformedData }
 * 
 * 5. columns
 *    - 訂單結帳資料匯出特定的欄位定義
 *    - 所有欄位設定 widthShrink: 0 確保資料完整顯示
 * 
 * 6. movableColumns
 *    - 繼承 UiShared 預設值：true
 *    - 允許使用者拖曳欄位標題來重新排列順序
 * 
 * ========================================
 * 使用方式
 * ========================================
 * 
 * @example
 * // 在 index.js 中使用
 * define([
 *     'tabulator',
 *     'HotaiConnected_CheckoutManagement/js/exportordercheckoutdata/config/tabulator-config'
 * ], function(Tabulator, tabulatorConfig) {
 *     var config = tabulatorConfig.getConfig();
 *     new Tabulator("#table", config);
 * });
 */
define([
    // Magento 內建功能
    'jquery',
    'mage/url',

    // UiShared 通用配置
    'tabulatorConfig',
    'HotaiConnected_CheckoutManagement/js/shared/services/api/ecpay-order-logs',

    // Magento 翻譯
    'mage/translate'
], function ($, urlBuilder, baseTabulatorConfig, ecpayOrderLogsApi) {
    'use strict';

    return {
        /**
         * AJAX 載入資料的 URL (訂單結帳資料匯出 API)
         */
        ajaxURL: urlBuilder.build(ecpayOrderLogsApi.endpoints.list),
        
        /**
         * 預設的每頁筆數（清除篩選時使用）
         */
        defaultPageSize: 10,
        
        /**
         * 清除篩選時是否重置每頁筆數
         * true: 清除時將每頁筆數重置為 defaultPageSize
         * false: 清除時不重置每頁筆數，保留使用者設定的每頁筆數
         */
        resetPageSizeOnClear: true,
        
        pageSizeMax: 0,
        
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
         * 轉換訂單結帳資料項目（統一資料轉換邏輯）
         * @private
         * 
         * @param {Object} item - 後端 API 返回的原始資料項目
         * @returns {Object} 轉換後的資料物件（包含所有基礎欄位 + 預設值）
         */
        _transformExportOrderCheckoutDataItem: function(item, index) {
            return {
                // 訂單結帳序號異動日 / 訂單編號
                id: index + 1 + '_' + item.increment_id,
                // 訂單發票記錄建立時間
                created_at: item.created_at || '-',
                // 訂單建立時間
                order_created_at_tz: item.order_created_at_tz || '-',
                // 特約商代號
                // seller_code: item.seller_code || '-',
                // 特約商名稱
                shop_title: item.shop_title || '-',
                // 館長姓名 / 業務角色名稱
                role_name: item.role_name || '-',
                // 子訂單編號
                increment_id: item.increment_id || '-',
                // 訂單結帳序號
                hotai_checkout_number: item.hotai_checkout_number || '-',
                // 發票狀態
                invoice_status: item.invoice_status || '-',
                // 商品狀態 / 流程狀態
                flow_status: item.flow_status || '-',
            };
        },

        /**
         * 更新下載搜尋資料按鈕的文字
         */
        updateDownloadSearchDataText: function(value) {
            var totalRecord = value || 0;
            var recordsFoundText = $('.records-found-text');
            var downloadBtn = $('#download-search-data');
            // 格式化為千分位顯示
            recordsFoundText.find('span').text(totalRecord.toLocaleString('zh-TW'));
            // if (totalRecord === 0) {
            //     recordsFoundText.addClass('d-none');
            //     downloadBtn.prop('disabled', true);
            // } else {
            //     recordsFoundText.removeClass('d-none');
            //     downloadBtn.prop('disabled', false);
            // }
        },

        /**
         * 取得完整的 Tabulator 配置（合併通用配置和業務特定配置）
         * 
         * @param {Object} options - 配置選項
         * @param {Function} options.getStatusName - 統一的狀態名稱查詢函數 (type, code) => displayName
         * @param {Function} options.getStatusMaps - 動態獲取狀態映射表的函數 () => {}
         * @returns {Object} 完整的 Tabulator 配置
         */
        getConfig: function(options) {
            options = options || {};
            var self = this;
            
            // 取得業務特定配置
            var businessConfig = {
                // ==========================================================
                // 業務特定配置 (Business Specific Configuration)
                // ==========================================================
                
                // 1. AJAX 載入資料的 URL (訂單結帳資料匯出 API)
                // ajaxURL: urlBuilder.build(ecpayOrderLogsApi.endpoints.list),

                // 在指定 Element 中插入分頁元素
                paginationElement: document.getElementsByClassName('tabulator-paginator')[0],

                ajaxURLGenerator: function(url, config, params) {
                    // 因為 true 是 Tabulator 內建的 pageSize "全選" 參數，所以需要特殊處理
                    if (params.page_size === true) {
                        params.page_size = self.pageSizeMax;
                    }
                    
                    // 合併當前的 filters 參數到請求參數中（從 self.currentFilterParams 讀取）
                    var filterParams = self.currentFilterParams || {};
                    var mergedParams = $.extend({}, filterParams, params);

                    const searchParams = new URLSearchParams(mergedParams);
                    return url + "?" + searchParams.toString();
                },
                
                // 2. AJAX 回應處理（覆蓋 UiShared 的 ajaxResponse）
                ajaxResponse: function(url, params, response) {
                    if (response.success === false) {
                        console.error('❌ API 錯誤', response);
                        throw new Error(response.message || 'API 請求失敗');
                    }

                    // 更新下載搜尋資料按鈕的文字
                    self.updateDownloadSearchDataText(response.total_record);

                    // 更新 pageSizeMax
                    self.pageSizeMax = response.total_record || self.pageSizeMax;

                    var items = response.items || [];
                    var totalRecord = response.total_record || 0;
                    var pageSize = response.page_size || params.page_size || 20;
                    var lastPage = Math.ceil(totalRecord / pageSize);
                    
                    // 轉換欄位名稱（使用統一的轉換函數）
                    var transformedData = items.map(function(item, index) {
                        return self._transformExportOrderCheckoutDataItem(item, index);
                    });
                    
                    console.log('📊 訂單結帳資料載入完成', {
                        totalRows: transformedData.length
                    });
                    
                    // 返回符合官方規範的物件格式
                    return {
                        last_page: lastPage,
                        data: transformedData
                    };
                },

                columnDefaults: {
                    minWidth: 150,
                    hozAlign: "left",
                },
                
                // 3. 欄位定義（對應 ajaxResponse 轉換後的欄位名稱）
                columns: [
                    // 訂單發票記錄建立時間
                    { 
                        title: $.mage.__('Order Invoice Record Creation Date'), 
                        field: 'created_at',
                        minWidth: 200,
                    },

                    // 訂單建立時間
                    { 
                        title: $.mage.__('Order Creation Date'), 
                        field: 'order_created_at_tz',
                        minWidth: 180,
                    },

                    // 特約商代號
                    // { 
                    //     title: $.mage.__('Authorized Dealer Code'), 
                    //     field: 'seller_code'
                    // },

                    // 特約商名稱
                    { 
                        title: $.mage.__('Authorized Dealer Name'), 
                        field: 'shop_title',
                        minWidth: 200,
                    },

                    // 館長姓名 / 業務角色名稱
                    { 
                        title: $.mage.__('Curator name'),
                        field: 'role_name'
                    },

                    // 子訂單編號
                    { 
                        title: $.mage.__('Sub Order Number'), 
                        field: 'increment_id',
                        minWidth: 200,
                    },

                    // 訂單結帳序號
                    { 
                        title: $.mage.__('Order Checkout Number'), 
                        field: 'hotai_checkout_number'
                    },

                    // 發票狀態
                    { 
                        title: $.mage.__('Invoice Status'), 
                        field: 'invoice_status'
                    },

                    // 商品狀態 / 流程狀態
                    { 
                        title: $.mage.__('Flow Status'), 
                        field: 'flow_status',
                        minWidth: 180,
                    },
                ],
                
                // ==========================================================
                // 📐 布局配置
                // ==========================================================
                height: false,
                renderVertical: "basic",
                
                // ==========================================================
                // 🔲 禁用行選擇 checkbox
                // ==========================================================
                // 覆蓋 UiShared 預設的 rowHeader 配置，禁用表單前面的 checkbox
                rowHeader: false
            };

            var baseConfig = baseTabulatorConfig.getRemoteConfig();
            baseConfig.paginationSizeSelector = [10, 20, 50, 100, 200];
            
            // 合併配置：UiShared 遠端分頁基礎配置 + 業務特定配置
            return $.extend(true, {}, baseConfig, businessConfig);
        }
    };
});


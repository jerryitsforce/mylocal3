/**
 * HotaiConnected UiShared - Tabulator 通用配置
 * 
 * 提供 Tabulator 表格的通用配置框架，支援本地分頁和遠端分頁兩種模式
 * 
 * @module HotaiConnected_UiShared/js/config/tabulator-config
 * @version 2.2.1
 * @author HotaiConnected
 * @updated 2025-10-28 - 重構：分頁配置統一至 _getBaseConfig，移除重複定義
 * @updated 2025-10-22 - 新增 movableColumns 預設啟用，允許拖曳欄位排序
 * 
 * ========================================
 * 分頁模式說明（基於 Tabulator 官方文件）
 * ========================================
 * 
 * 官方文件：https://tabulator.info/docs/6.3/page#remote
 * 
 * 【本地分頁】Local Pagination
 * - 一次載入所有資料到前端
 * - 前端 JavaScript 進行分頁處理
 * - ajaxResponse 返回：陣列 [...]
 * - 適用：資料量小（<1000 筆）
 * 
 * 【遠端分頁】Remote Pagination
 * - 每次換頁時向後端請求該頁資料
 * - 後端處理分頁邏輯
 * - ajaxResponse 返回：物件 { last_page, data }
 * - 適用：資料量大（>1000 筆）
 * 
 * ========================================
 * 通用配置包含
 * ========================================
 * 
 * - AJAX 請求配置（ajaxMethod, ajaxContentType, ajaxURL, ajaxParams）
 * - 布局配置（layout, responsiveLayout）
 * - 互動功能配置（movableColumns）
 * - 分頁配置（pagination, paginationSize, paginationSizeSelector）
 * - 語系配置（locale, langs）
 * - 選擇行標頭配置（rowHeader）
 * - 其他共用設定
 * 
 * ========================================
 * API 設計
 * ========================================
 * 
 * 提供三個方法：
 * 
 * 1. _getBaseConfig() [私有]
 *    - 基礎共用配置（AJAX、布局、語系、分頁等）
 *    - 包含 pagination、paginationSize、paginationSizeSelector
 *    - 不包含 paginationMode 和 ajaxResponse
 * 
 * 2. getLocalConfig() [公開]
 *    - 本地分頁完整配置
 *    - 資料來源：response (API 回應)
 *    - ajaxResponse 返回：[{}, {}, ...]（完整資料陣列）
 *    - 業務模組必須實作 ajaxResponse
 * 
 * 3. getRemoteConfig() [公開]
 *    - 遠端分頁完整配置
 *    - 資料來源：response.items, response.total_record, response.page_size
 *    - ajaxResponse 返回：{ last_page: N, data: [...] }
 *    - 業務模組必須實作 ajaxResponse
 * 
 * ========================================
 * 使用範例
 * ========================================
 * 
 * @example
 * // 使用遠端分頁（推薦用於大數據）
 * define(['tabulatorConfig'], function(baseConfig) {
 *     var businessConfig = {
 *         ajaxURL: 'rest/V1/your-api',
 *         ajaxResponse: function(url, params, response) {
 *             var items = response.items || [];
 *             var totalRecord = response.total_record || 0;
 *             var pageSize = response.page_size || 20;
 *             var lastPage = Math.ceil(totalRecord / pageSize);
 *             return { last_page: lastPage, data: items };
 *         },
 *         columns: [...]
 *     };
 *     var config = $.extend(true, {}, baseConfig.getRemoteConfig(), businessConfig);
 *     new Tabulator("#table", config);
 * });
 * 
 * @example
 * // 使用本地分頁（適用於小數據）
 * define(['tabulatorConfig'], function(baseConfig) {
 *     var businessConfig = {
 *         ajaxURL: 'rest/V1/your-api',
 *         ajaxResponse: function(url, params, response) {
 *             return response.items || [];
 *         },
 *         columns: [...]
 *     };
 *     var config = $.extend(true, {}, baseConfig.getLocalConfig(), businessConfig);
 *     new Tabulator("#table", config);
 * });
 */
define([
    'jquery',
    'mage/translate',
    'mage/storage',
    'hotaiRequest'
], function ($, $t, storage, request) {
    'use strict';

    return {
        
        /**
         * 取得共用基礎配置（不含分頁設定）
         * 
         * @private
         * @returns {Object} 基礎配置物件
         */
        _getBaseConfig: function() {
            return {
                // ==========================================================
                // 核心與 AJAX 配置 (Core and AJAX Configuration)
                // ==========================================================
                // 設定為 POST 請求 (更適合傳遞複雜篩選參數)
                ajaxMethod: 'POST', 
                
                // 設定 POST 請求體為 JSON 格式
                ajaxContentType: 'json', 

                // 預設資料來源 URL（由業務模組覆蓋）
                // 空字串代表不自動請求，需由業務端設定
                ajaxURL: '',
                
                // 官方文件：https://tabulator.info/docs/6.3/page#remote
                // 預設傳遞給後端的參數 (例如 Magento 2 的 Form Key)
                // 注意：ajaxParams 將在初始化時動態設定，避免在模組載入時就執行 DOM 查詢
                // 如果是需要額外添加傳遞參數，可以在此進行
                ajaxParams: {},

                // AJAX 請求配置（確保瀏覽器自動發送 cookies）
                // 官方文件：https://tabulator.info/docs/6.3/data#ajax-config
                // 當 REST API 設置為 Magento_Backend::admin 時，Magento 會自動從 Cookie header 讀取 admin session
                ajaxConfig: {
                    xhrFields: {
                        // 關鍵設置：允許跨域攜帶 Cookie（憑證）
                        // 當 REST API 設置為 Magento_Backend::admin 時，瀏覽器會自動發送 admin cookie
                        withCredentials: true 
                    },
                    headers: request.ajaxHeader(),
                    beforeSend: request.ajaxBeforeSend
                },

                // 修改 tabulator 既有 AJAX 功能中的 url, config，以及傳遞參數 Params，可在此進行
                // 官方文件：https://tabulator.info/docs/6.3/page  >>> Custom Pagination URL Construction
                // |- url - the url from the ajaxURL property or setData function
                // |- config - the request config object from the ajaxConfig property
                // |- params - the params object from the ajaxParams property, this will also include any pagination, filter and sorting properties based on table setup
                   
                ajaxURLGenerator:function(url, config, params){
                    //url - the url from the ajaxURL property or setData function
                    //config - the request config object from the ajaxConfig property
                    //params - the params object from the ajaxParams property, this will also include any pagination, filter and sorting properties based on table setup
                    return url + "?" + new URLSearchParams(params).toString();
                },

                // 一次抓取所有資料，可以使用 tabulator 內建的 paginationSizeSelector 提供的 true 來開始[全部]選項
                paginationSizeSelector: [10, 20, 50, 100, 200, true],
                
                // ⭐ 禁用 Tabulator 內建錯誤訊息顯示 ⭐
                dataLoaderErrorTimeout: false,
                
                // ⭐⭐⭐ ajaxResponse 由各分頁模式實作 ⭐⭐⭐
                // 
                // 注意：此基礎配置不包含 ajaxResponse
                // 
                // 各分頁模式應實作自己的 ajaxResponse：
                // 
                // 【本地分頁】getLocalConfig() 應實作：
                // ajaxResponse: function(url, params, response) {
                //     return [...];  // 返回完整資料陣列
                // }
                // 
                // 【遠端分頁】getRemoteConfig() 應實作：
                // ajaxResponse: function(url, params, response) {
                //     return {       // 返回包含分頁資訊的物件
                //         last_page: 15,
                //         data: [...]
                //     };
                // }

                // -----------------------------------------------------------
                // 數據與布局配置 (Data and Layout Configuration)
                // -----------------------------------------------------------
                // 設定表格布局模式
                // fitColumns: 自動平均分配。將所有欄位平均分配到表格的可用寬度中。如果欄位總寬度不足，會拉伸欄位來填滿空間。
                // fitDataFill: 內容優先填滿。根據欄位內容計算初始寬度，然後將剩餘空間按比例分配給所有欄位。
                // fitData: 內容優先。只根據欄位內容寬度來設定欄寬。表格寬度可能小於或大於視窗寬度。
                layout: 'fitColumns',
                // ⭐ 關鍵調整 1：禁用響應式佈局 ⭐
                // 設為 false 或 'hide' 可以防止欄位被隱藏或折疊
                responsiveLayout: false,
                autoResize: true,
                // -----------------------------------------------------------
                // 互動功能配置 (Interactive Features Configuration)
                // -----------------------------------------------------------
                
                // 允許使用者拖曳欄位標題來重新排列欄位順序
                // 使用者可透過滑鼠拖曳欄位標題，自訂最適合的欄位順序
                // 注意：凍結欄位 (frozen: true) 無法移動
                movableColumns: true, 

                // height: false, // 讓表格高度根據內容自動擴展

                // -----------------------------------------------------------
                // 分頁配置（所有分頁模式共用）
                // -----------------------------------------------------------
                pagination: true,
                paginationSize: 10,

                // ==========================================================
                // ⭐ 語系對應 i18n 配置：新增 Tabulator 本地化配置 ⭐
                // ==========================================================
                
                // 1. 指定使用 zh-tw 語言環境（請確保 Magento 環境設定為 zh_TW）
                locale: "zh-tw", 
                
                // 2. 定義 'zh-tw' 語言環境下的所有 Tabulator 內建文字字串
                langs:{
                    "zh-tw":{
                        "pagination":{
                            // Tabulator 內建按鈕的英文 Key，使用 $.mage.__() 進行翻譯
                            // 目前暫時不需要無障礙網站配置，所以先不加 title 屬性
                            "first":"<<", // 使用符號代替文字
                            "last":">>", // 使用符號代替文字
                            "prev": $.mage.__('previous page'),
                            "next": $.mage.__('Next Page'),
                            "page_size": $.mage.__('per page'),
                            
                            // 分頁資訊顯示
                            "of": $.mage.__('of'),
                            "page_text": $.mage.__('page'),
                            "go": $.mage.__('Go'),
                            "all": $.mage.__('All'),
                        },
                        // 其他 Tabulator 內建介面文字 (如載入狀態和無資料提示)
                        "data":{
                            "loading": $.mage.__('Loading...'), 
                            // "error": $.mage.__('Loading Error'),
                            // "empty": $.mage.__('No Data Found'), 
                        }
                    }
                },

                // -----------------------------------------------------------
                // 選擇行標頭配置 (Row Header / Selection Configuration)
                // -----------------------------------------------------------
                // 設定行標頭（通常用於行選取或編號）
                rowHeader:{
                    // 標頭不允許排序，因為它是選擇框
                    headerSort:false, 
                    // 標頭寬度不允許調整
                    resizable: false, 
                    // 標頭欄位保持固定 (凍結)
                    frozen:true, 
                    // 標頭文字置中對齊
                    headerHozAlign:"center", 
                    // 儲存格內容置中對齊
                    hozAlign:"center", 
                    // 使用 Tabulator 內建的 "rowSelection" 格式化器，顯示 checkbox
                    formatter:"rowSelection", 
                    // 使用 Tabulator 內建的 "rowSelection" 標題格式化器，在標題顯示一個全選 checkbox
                    titleFormatter:"rowSelection",
                    // 標頭寬度
                    width: 45,
                    minWidth: 45,
                    maxWidth: 45,
                    // 點擊儲存格時的事件處理
                    cellClick:(e, cell)=>{
                        console.log('cellClick', e, cell);
                        // 切換該行的選擇狀態
                        cell.getRow().toggleSelect();
                    }
                },

                // 無資料時的提示文字
                placeholder: $.mage.__('We could not find any records'),

                // 預設欄位配置
                // 官方文件：https://tabulator.info/docs/6.3/columns#main-contents
                columnDefaults:{
                    // widthShrink: 0,
                    resizable: true,
                    variableHeight: true,   
                    hozAlign: "center",
                    headerHozAlign: "center",
                    vertAlign: "middle"
                },

                // 預設欄位（由業務模組覆蓋）
                // 空陣列代表未定義欄位，避免 Tabulator 報錯
                columns: []
            };
        },
        
        /**
         * 取得本地分頁配置
         * 適用於資料量小的場景（<1000 筆）
         * 
         * @returns {Object} 本地分頁完整配置
         */
        getLocalConfig: function() {
            var config = this._getBaseConfig();
            
            // 本地分頁特定配置
            $.extend(config, {

                // 本地分頁模式不需要額外配置
                // pagination 和 paginationSize 已在 _getBaseConfig 中設定
                
                // ⭐⭐⭐ ajaxResponse 應由業務模組實作 ⭐⭐⭐
                // 
                // 資料來源：response (API 回應物件)
                // 返回格式：[{id:1, ...}, {id:2, ...}, ...]
                // 注意事項：只需返回完整的資料 Array，Tabulator 會在前端自動進行分頁處理
                //
                ajaxResponse: function(url, params, response) {
                    // 業務模組應實作此函數
                    // 從 response 中取得完整資料並返回陣列
                    throw new Error('ajaxResponse 必須由業務模組實作');
                }
            });
            
            return config;
        },
        
        /**
         * 取得遠端分頁配置
         * 適用於資料量大的場景（>1000 筆）
         * 官方文件：https://tabulator.info/docs/6.3/page#remote
         * 
         * ⚠️ 業務模組注意事項：
         * 
         * 使用遠端分頁的業務模組應在 config 物件中定義 currentFilterParams：
         * 
         *   /**
         *    * 儲存當前的 filters 參數（用於換頁時保留篩選條件）
         *    * 
         *    * 此屬性應在業務模組的 config 物件中定義，因為：
         *    * 1. 它是狀態屬性，會動態變化（apply-filters 時更新，clear-filters 時清除）
         *    * 2. 需要在 ajaxURLGenerator 和 index.js 中存取業務模組的 config 物件
         *    * 3. 每個業務模組實例需要獨立的狀態
         *    * 
         *    * 使用方式：
         *    * - apply-filters 時：tabulatorConfig.currentFilterParams = params || {};
         *    * - clear-filters 時：tabulatorConfig.currentFilterParams = {};
         *    * - ajaxURLGenerator 中：var filterParams = self.currentFilterParams || {};
         *    * /
         *   currentFilterParams: {},
         * 
         * @returns {Object} 遠端分頁完整配置
         */
        getRemoteConfig: function() {
            var config = this._getBaseConfig();
            
            // 遠端分頁特定配置
            $.extend(config, {
                // 啟用遠端分頁
                paginationMode: "remote",
                // 遠端分頁模式，因不會知道總筆數，所以無法使用 true 來開始[全部]選項
                
                // -----------------------------------------------------------
                // 分頁參數映射：Tabulator 內部 → API 參數名稱
                // -----------------------------------------------------------
                // 
                // 預設值適用於 Magento 2 標準 REST API
                // 若您的 API 使用不同參數名稱，請在業務模組中覆蓋此配置
                // 
                // Tabulator 內部參數：
                //   - page: 當前頁碼（從 1 開始）
                //   - size: 每頁筆數
                // 
                // 常見 API 參數格式：
                //   - Magento 標準：current_page, page_size（預設）
                //   - REST 標準：page, size
                //   - Laravel：page, per_page
                //   - Spring Boot：page, size
                // 
                // 覆蓋方式（在業務模組中）：
                //   var config = baseTabulatorConfig.getRemoteConfig();
                //   config.dataSendParams = { page: "page", size: "size" };
                // 
                dataSendParams: {
                    page: "current_page",   // Tabulator page → API current_page
                    size: "page_size"       // Tabulator size → API page_size
                },
                
                // ⭐⭐⭐ ajaxResponse 應由業務模組實作 ⭐⭐⭐
                // 
                // 資料來源：response (API 回應物件)
                //          必須包含：items (當前頁資料), total_record (總筆數), page_size (每頁筆數)
                // 
                // 返回格式：{ last_page: 15, data: [{id:1, ...}, {id:2, ...}] }
                ajaxResponse: function(url, params, response) {
                    // 業務模組應實作此函數
                    // 從 response 中取得完整資料並返回物件
                    throw new Error('ajaxResponse 必須由業務模組實作');
                }
            });
            
            return config;
        }
    };
});

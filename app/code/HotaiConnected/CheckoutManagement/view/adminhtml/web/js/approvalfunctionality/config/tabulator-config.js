/**
 * HotaiConnected CheckoutManagement - 對帳區域 Tabulator 配置
 * 
 * 此檔案包含對帳區域表格的業務特定配置
 * 基於 UiShared 的 getRemoteConfig() 遠端分頁框架
 * 
 * @module HotaiConnected_CheckoutManagement/js/approvalfunctionality/config/tabulator-config
 * @version 4.2.0
 * @author HotaiConnected
 * @updated 2025-11-13 - 調整：審查結果欄位改為單純同意/拒絕按鈕
 * @updated 2025-11-13 - 調整：移除 dataTree 相關配置，僅保留 HTML 模式
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
 *     'HotaiConnected_CheckoutManagement/js/approvalfunctionality/config/tabulator-config'
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
    'HotaiConnected_CheckoutManagement/js/shared/services/api/exception-auth',

    // Magento 翻譯
    'mage/translate'
], function ($, urlBuilder, baseTabulatorConfig, exceptionAuthApi) {
    'use strict';

    return {

        /**
         * AJAX 載入資料的 URL (對帳系統 API)
         */
        ajaxURL: urlBuilder.build(exceptionAuthApi.endpoints.base),

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
         * 轉換對帳資料項目（統一資料轉換邏輯，消除重複代碼）
         * @private
         * 
         * 此函數為純粹的資料轉換函數，只負責：
         * - 欄位名稱映射（後端 API 欄位 → 前端 Tabulator 欄位）
         * - 預設值設定
         * - 資料格式轉換（例如：字串分割為陣列）
         * 
         * 不處理業務邏輯（如嵌套資料的組裝）
         * 業務邏輯應在呼叫處根據需求設定
         * 
         * @param {Object} item - 後端 API 返回的原始資料項目
         * @returns {Object} 轉換後的資料物件（包含所有基礎欄位 + 預設值）
         */
        _transformReconciliationItem: function(item) {
            return {
                entity_id: item.id,
                id: item.id,
                // 結帳批次
                batch_num: item.batch_num || '-',
                // 特約商名稱
                shop_title: item.shop_title || '-',
                // 特約商代號
                seller_code: item.seller_code || '-',
                // 審核狀態
                exception_status: item.exception_status || '-',
                // 負責館長
                salesperson_role_name: item.salesperson_role_name || '-',
                // 原因
                reason: item.reason || '-',
                // 調整欄位
                show_value: item.show_value || '-',
                // 審核結果
                approval_action: item.approval_action || '-',
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
            var self = this;
            
            // 取得業務特定配置
            var businessConfig = {
                // ==========================================================
                // 業務特定配置 (Business Specific Configuration)
                // ==========================================================
                
                // 1. AJAX 載入資料的 URL (對帳系統 API)
                ajaxURL: self.ajaxURL,

                ajaxURLGenerator:function(url, config, params){

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
                        totalRows: transformedData.length
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
                        title: $.mage.__('Checkout Batch'), 
                        field: 'batch_num',
                        minWidth: 200,
                    },

                    // 特約商名稱
                    { 
                        title: $.mage.__('Authorized Dealer Name'), 
                        field: 'shop_title',
                        minWidth: 250,
                        hozAlign : "left",
                    },

                    // 特約商代號
                    { 
                        title: $.mage.__('Authorized Dealer Code'), 
                        field: 'seller_code',
                        hozAlign : "left",
                    },

                    // 審核狀態
                    {
                        title: $.mage.__('Review Status'),
                        field: 'exception_status',
                    },

                    // 負責館長
                    { 
                        title: $.mage.__('Principal Curator'),
                        field: 'salesperson_role_name'
                    },

                    // 原因
                    { 
                        title: $.mage.__('Reason'),
                        field: 'reason'
                    },

                    // 調整欄位
                    { 
                        title: $.mage.__('Adjust Columns'), 
                        field: 'show_value',
                        minWidth: 250,
                        hozAlign : "left",
                        formatter: (cell) => {
                            var value = cell.getValue();
                            var html = [];
                            Object.keys(value).forEach(function(key) {
                                html.push('<p>' + key + ': ' + value[key] + '</p>');
                            });

                            return '<div>' + html.join('') + '</div>';
                        }
                    },

                    // 下載例外授權
                    {
                        title: $.mage.__('Download %1').replace('%1', $.mage.__('Exception Authorization')),
                        field: 'download_exception_authorization',
                        minWidth: 150,
                        formatter: (cell) => {
                            var rowData = cell.getRow().getData();
                            return !rowData.data_tree_row ? '<button class="action-default" type="button"><span>' + $.mage.__('Download') + '</span></button>' : '';
                        },
                        cellClick: function(e, cell) {
                            var rowData = cell.getRow().getData();
                            var selectedIds = [rowData.entity_id || rowData.id];
                            // 觸發自定義事件，由主控制器處理
                            $(document).trigger('approval:download-report', [rowData, selectedIds]);
                        }
                    },

                    // 審核結果 - 同意 / 拒絕
                    {
                        title: $.mage.__('Approval Result'),
                        field: 'approval_action',
                        minWidth: 180,
                        hozAlign: "center",
                        formatter: function(cell) {
                            var rowData = cell.getRow().getData();
                            var approveLabel = $.mage.__('Approve');
                            var rejectLabel = $.mage.__('Reject');
                            var actionStatus = !['審核中'].includes(rowData.exception_status);

                            return (
                                actionStatus ? '-' : '<div class="approval-actions">' +
                                    '<button class="action-default approval-actions__btn approval-actions__btn--approve" type="button">' +
                                        '<span>' + approveLabel + '</span>' +
                                    '</button>' +
                                    '<button class="action-default approval-actions__btn approval-actions__btn--reject" type="button">' +
                                        '<span>' + rejectLabel + '</span>' +
                                    '</button>' +
                                '</div>'
                            );
                        },
                        cellClick: function(e, cell) {
                            var target = e.target;

                            if (!target.classList.contains('approval-actions__btn')) {
                                target = target.closest('.approval-actions__btn');
                            }

                            if (!target) {
                                return;
                            }

                            var rowData = cell.getRow().getData();

                            if (target.classList.contains('approval-actions__btn--approve')) {
                                $(document).trigger('approval:approve', [target, rowData]);
                            } else if (target.classList.contains('approval-actions__btn--reject')) {
                                $(document).trigger('approval:reject', [target, rowData]);
                            }
                        }
                    }
                ],
                // ==========================================================
                // 📐 布局配置
                // ==========================================================
                // 禁用虛擬 DOM，避免手動插入的 DOM 被清除
                height: false,
                renderVertical: "basic"
            };
            
            // 合併配置：UiShared 遠端分頁基礎配置 + 業務特定配置
            return $.extend(true, {}, baseTabulatorConfig.getRemoteConfig(), businessConfig);
        }
    };
});
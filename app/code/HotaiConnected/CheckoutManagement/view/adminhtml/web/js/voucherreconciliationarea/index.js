/**
 * @fileoverview 票券對帳區域頁主控制器
 * 
 * 此模組負責管理票券對帳區域頁的主要功能，包括：
 * - Tabulator 表格的初始化和管理
 * - 篩選器的顯示/隱藏切換
 * - 報表類型選擇（球池票券、1.0標準票券、2.0標準票券）
 * - 載入動畫的控制
 * - 事件監聽器的綁定和管理
 * 
 * @module HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/index
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2024-10-23
 * 
 * @example
 * // 在 index.phtml 中使用
 * require(['HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/index'], function(voucherReconciliationAreaIndex) {
 *     voucherReconciliationAreaIndex.initialize();
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
 * @requires {Object} HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/config/tabulator-config - Tabulator 配置（業務特定）
 * @requires {Object} HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/filters/filters - 篩選器組件
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
    'HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/config/tabulator-config',  // 完整配置（已包含通用配置）
    'HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/filters/filters',
    'HotaiConnected_CheckoutManagement/js/voucherreconciliationarea/components/table/select-action',
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
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
    reconciliationApi
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

        btnAction: {
            reportTypeSelect: '#report-type-select',
        },

        dateRangeInputs: {
            order: { 
                from: '#order_created_from',
                to: '#order_created_to',
            },
            invoice: {
                from: '#invoice_created_from',
                to: '#invoice_created_to'
            },
            event: {
                from: '#event_created_from',
                to: '#event_created_to'
            } 
        },
        _dateRange:{},

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
            
            self._dateRange = {
                // 訂單成立時間
                order: {
                    from: $(self.dateRangeInputs.order.from),
                    to: $(self.dateRangeInputs.order.to)
                },
                // 訂單結帳序號異動日
                invoice:{
                    from: $(self.dateRangeInputs.invoice.from),
                    to: $(self.dateRangeInputs.invoice.to)
                },
                 // 球池建立日
                event:{
                    from: $(self.dateRangeInputs.event.from),
                    to: $(self.dateRangeInputs.event.to)
                }
            };

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
                selectAction.resetBatchActionSelect();
                
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

                $('input.admin__control-text._has-datepicker').prop('disabled', false);
            });

            // ========================================
            // 綁定下拉選單變更事件
            // ========================================

            $('#report-type-select').on('change', function(e) {
                self.handleReportTypeSelect();
            });

            $('select[name=type]').on('change', function(){
                var s = $(this).val();
                self.handleTypeChange(s);
            })
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
                loadingMask.hide();
                self.showError(error);
            });
        },

        handleTypeChange: function(s){
            var self = this;
            var t = s || ''
            var setRangeState = function(range, disabled) {
                range.from.prop('disabled', disabled);
                range.to.prop('disabled', disabled);
                if (disabled) {
                    range.from.val('');
                    range.to.val('');
                }
            };

            switch(t) {
                case 'event':
                    setRangeState(self._dateRange.order, true);
                    setRangeState(self._dateRange.invoice, true);
                    setRangeState(self._dateRange.event, false);
                    break;
                case 'ticket':
                    setRangeState(self._dateRange.order, false);
                    setRangeState(self._dateRange.invoice, false);
                    setRangeState(self._dateRange.event, true);
                    break;
                default:
                    setRangeState(self._dateRange.order, false);
                    setRangeState(self._dateRange.invoice, false);
                    setRangeState(self._dateRange.event, false);
                    break;
            }
        },

        handleReportTypeSelect: function() {
            var self = this;
            var selectedAction = $(self.btnAction.reportTypeSelect).val();
            if (selectedAction && selectedAction !== '') {
                selectAction.executeAction(selectedAction,{},[],{
                    resetBatchActionSelect: function() {
                        selectAction.resetBatchActionSelect();
                    },
                    updateTabulator: function() {
                        self.updateTabulator();
                    }
                })
            } else {
                selectAction.resetBatchActionSelect();
            }
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
            
            // 建立 API URL
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
                storageKey: 'voucher_reconciliation_area_table_columns_visibility'
            });
        }
        
    };
});


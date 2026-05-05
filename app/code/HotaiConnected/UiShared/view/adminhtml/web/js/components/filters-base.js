/**
 * HotaiConnected UiShared - 篩選器基礎組件（共用）
 * 
 * @module HotaiConnected_UiShared/js/components/filters-base
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-01-XX
 * 
 * ========================================
 * 組件說明
 * ========================================
 * 
 * 此模組提供篩選器組件的共用功能，包含：
 * - 事件綁定
 * - SumoSelect 初始化
 * - 日期選擇器初始化
 * - 表單驗證初始化
 * - 篩選器可見性控制
 * - 表單重置
 * - 表單資料收集
 * - SumoSelect 全選判斷
 * 
 * ========================================
 * 使用方式
 * ========================================
 * 
 * @example
 * define([
 *     'HotaiConnected_UiShared/js/components/filters-base',
 *     // 其他模組...
 * ], function(filtersBase) {
 *     return $.extend({}, filtersBase, {
 *         // 業務特定的方法
 *         initCustomMethod: function() {
 *             // ...
 *         }
 *     });
 * });
 * 
 * ========================================
 * 依賴關係
 * ========================================
 * 
 * Magento 內建功能
 * - jquery: DOM 操作和事件處理
 * - mage/translate: 多語言翻譯
 * - mage/validation: 表單驗證
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiDatePickers: 日期選擇器工具
 * - hotaiSumoselect: SumoSelect 封裝
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate',
    'mage/validation',
    
    // 共用模組 (來自 UiShared)
    'hotaiDatePickers',
    'hotaiSumoselect'
], function ($, $t, validation, datePickers, sumoUtil) {
    'use strict';

    return {
        /**
         * 篩選器表單選擇器（預設值，可被覆蓋）
         */
        selectors: {
            form: '#filters-form',
            toggle: '#filters-toggle',
            wrapper: '#filters-wrapper',
            dateInputs: 'input[type="text"][readonly]',
            textInputs: 'input[type="text"]:not([readonly])',
            clearBtn: '#clear-filters',
            applyBtn: '#apply-filters'
        },

        /**
         * 綁定事件監聽器
         */
        bindEvents: function() {
            var self = this;
            
            // 篩選器切換按鈕（UI 控制，保留在 filters.js）
            $(this.selectors.toggle).on('click', function(e) {
                e.preventDefault();
                self.toggleFilterVisibility();
            });
            
            // 注意：套用和清除按鈕的綁定已移至 index.js（主控制器負責）
            // 此處只保留 UI 相關的展開/收合功能
        },

        /**
         * 初始化日期選擇器
         * 使用 date-pickers 工具模組處理所有日期範圍欄位
         */
        initDatePickers: function() {
            var self = this;
            
            // 尋找所有日期範圍欄位
            $(this.selectors.form).find('[data-type="date-range"]').each(function() {
                var $container = $(this);
                var $fromInput = $container.find('input').first();
                var $toInput = $container.find('input').last();
                
                if ($fromInput.length && $toInput.length) {
                    var fromId = $fromInput.attr('id');
                    var toId = $toInput.attr('id');
                    
                    // 使用 date-pickers 工具模組初始化這組日期範圍
                    // setDefaultValues: false 確保初始化後日期欄位保持空白
                    datePickers.initDatePickers({
                        fromSelector: '#' + fromId,
                        toSelector: '#' + toId,
                        setDefaultValues: false
                    });
                }
            });
        },

        /**
         * 初始化 SumoSelect 多選下拉選單
         */
        initSumoSelect: function() {
            var self = this;
            
            // 遍歷所有 data-type="multiselect" 的容器
            $(this.selectors.form).find('[data-type="multiselect"]').each(function() {
                var $container = $(this);
                var $select = $container.find('select[multiple]');
                
                if ($select.length === 0) {
                    return;
                }

                // 直接初始化 SumoSelect
                sumoUtil.init({
                    selector: '#' + $select.attr('id'),
                    placeholder: $select.data('placeholder') || $.mage.__('Search...'),
                });
            });
        },

        /**
         * 更新指定 select 的資料（當 API 資料回來後使用）
         * 
         * @param {string} selectName - select 的 name 屬性
         * @param {Array} data - 資料陣列 [{ id, text }]
         * @param {Object} customConfig - 自訂配置物件（選填）
         */
        updateSelectData: function(selectName, data, customConfig) {
            var $select = $(this.selectors.form).find('select[name="' + selectName + '"]');
            
            if ($select.length === 0) {
                console.error('updateSelectData: 找不到 select - ' + selectName);
                return;
            }
            
            var selectId = $select.attr('id');
            
            // 使用 sumoUtil 更新資料（會清空現有選擇並重建）
            sumoUtil.updateData('#' + selectId, data, customConfig || null);
        },

        /**
         * 判斷指定 select 是否全選
         * 
         * @param {string} selectName - select 的 name 屬性
         * @returns {boolean|null} 是否全選（找不到元素時返回 null）
         * 
         * @example
         * // 判斷是否全選
         * if (self.isSelectAllSelected('shop_title')) {
         *     console.log('已全選');
         * }
         */
        isSelectAllSelected: function(selectName) {
            var $select = $(this.selectors.form).find('select[name="' + selectName + '"]');
            
            if ($select.length === 0) {
                console.error('isSelectAllSelected: 找不到 select - ' + selectName);
                return null;
            }
            
            var selectId = $select.attr('id');
            return sumoUtil.isAllSelected('#' + selectId);
        },

        /**
         * 初始化表單驗證
         */
        initFormValidation: function() {
            var $form = $(this.selectors.form);
            
            if ($form.length) {
                // 初始化 Magento 表單驗證
                $form.validation({
                    ignore: ':hidden', // 忽略隱藏欄位
                    errorClass: 'mage-error',
                    validClass: 'mage-success',
                    errorElement: 'div',
                    errorPlacement: function(error, element) {
                        // 自訂錯誤訊息位置
                        if (element.closest('[data-type="date-range"]').length) {
                            // 日期範圍欄位的錯誤訊息放在容器下方
                            error.insertAfter(element.closest('[data-type="date-range"]'));
                        } else {
                            // 其他欄位的錯誤訊息放在欄位下方
                            error.insertAfter(element);
                        }
                    }
                });
            }
        },

        /**
         * 初始化篩選器可見性
         */
        initFilterVisibility: function() {
            // 預設隱藏篩選器
            this.hideFilters();
        },

        /**
         * 切換篩選器可見性
         */
        toggleFilterVisibility: function() {
            var $form = $(this.selectors.form);
            
            if ($form.hasClass('_show')) {
                this.hideFilters();
            } else {
                this.showFilters();
            }
        },

        /**
         * 顯示篩選器
         */
        showFilters: function() {
            var $form = $(this.selectors.form);
            var $toggle = $(this.selectors.toggle);
            
            $form.addClass('_show');
            $toggle.addClass('_active');
            
            // 觸發自訂事件
            $(document).trigger('filters:show');
        },

        /**
         * 隱藏篩選器
         */
        hideFilters: function() {
            var $form = $(this.selectors.form);
            var $toggle = $(this.selectors.toggle);
            
            $form.removeClass('_show');
            $toggle.removeClass('_active');
            
            // 觸發自訂事件
            $(document).trigger('filters:hide');
        },

        /**
         * 重置表單（公開方法，供主控制器調用）
         */
        resetForm: function() {
            var $form = $(this.selectors.form);
            
            // 1. 清除所有文字輸入值
            $form.find('input[type="text"]').val('');
            
            // 2. 重置所有 SumoSelect 下拉選單（不觸發 change 事件）
            $form.find('select[multiple]').each(function() {
                var $select = $(this);
                var selectId = $select.attr('id');
                
                if (selectId) {
                    // 使用 sumoUtil.resetSelection 清除選擇（不觸發 change 事件）
                    sumoUtil.resetSelection('#' + selectId, false);
                }
            });
            
            // 3. 重置所有單選下拉選單
            $form.find('select').not('[multiple]').each(function() {
                var $select = $(this);
                $select.val('');
            });
            
            // 4. 清除驗證錯誤狀態
            $form.find('.mage-error').remove();
            $form.find('.mage-success').removeClass('mage-success');
            $form.find('input, select').removeClass('mage-error');
        },

        /**
         * 取得表單資料
         * 
         * 收集所有表單欄位的值：
         * - input[type="text"] - 文字輸入
         * - select[multiple] - 多選下拉選單（陣列）
         * - select:not([multiple]) - 單選下拉選單
         */
        getFormData: function() {
            var $form = $(this.selectors.form);
            var formData = {};
            
            // 1. 收集所有文字輸入欄位
            $form.find('input[type="text"]').each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                var value = $input.val();
                
                if (name && value) {
                    formData[name] = value;
                }
            });
            
            // 2. 收集所有多選下拉選單（select[multiple]）
            $form.find('select[multiple]').each(function() {
                var $select = $(this);
                var name = $select.attr('name');
                var value = $select.val(); // 多選會返回陣列
                
                // 調試：檢查 sales_person
                if (name === 'sales_person') {
                    console.log('🔍 getFormData - sales_person:', {
                        name: name,
                        value: value,
                        valueType: typeof value,
                        isArray: Array.isArray(value),
                        length: value ? value.length : 'N/A',
                        selectedOptions: $select.find('option:selected').map(function() { return $(this).val(); }).get(),
                        sumoInstance: $select[0].sumo ? 'exists' : 'not found'
                    });
                }
                
                // 只有在有選擇項目時才加入（避免空字串）
                // 轉換為逗號分隔字串格式：["a", "b", "c"] → "a,b,c"
                if (name && value && value.length > 0) {
                    formData[name] = value.join(',');
                }
            });
            
            // 3. 收集所有單選下拉選單（select:not([multiple])）
            $form.find('select:not([multiple])').each(function() {
                var $select = $(this);
                var name = $select.attr('name');
                var value = $select.val();
                
                // 只有在有選擇項目時才加入（避免空字串）
                if (name && value) {
                    formData[name] = value;
                }
            });
            
            // 4. 統一處理 DateRange Format
            // date_from 預設加上 ' 00:00:00'
            // date_to 預設加上 ' 23:59:59'
            $form.find('div[data-type=date-range]').each(function() {
                $(this).find('input.admin__control-text._has-datepicker').each(function(i, e){
                    if(formData[e.name]){
                        if(i === 0 ){
                            formData[e.name] += ' 00:00:00';
                        } else if(i === 1 ){
                            formData[e.name] += ' 23:59:59';
                        }
                    }
                })
            });

            return formData;
        },
    };
});


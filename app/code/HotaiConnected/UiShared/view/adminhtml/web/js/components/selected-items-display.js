/**
 * 選中項目顯示工具模組（真正通用化）
 * 
 * 提供簡單的項目列表顯示功能：
 * - 動態創建完整的 DOM 結構（label + container）
 * - 顯示標準化格式的資料陣列
 * - 完全解耦於業務資料結構
 * 
 * @module HotaiConnected_UiShared/js/components/selected-items-display
 * @author HotaiConnected
 * @version 5.0.0
 * @updated 2024-10-23 - 重大架構改善：標準化資料格式，組件零業務知識
 * @updated 2024-10-23 - 極簡化：移除過度驗證和猜測性配置，遵循 YAGNI 原則
 * 
 * @requires jquery
 * 
 * 設計原則：
 * - 組件完全不知道業務欄位名稱（shop_title, customer_name 等）
 * - 調用方負責將業務資料轉換為標準格式 [{value: '...'}]
 * - 符合依賴倒置原則（業務層依賴組件層，而非反向）
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery'
], function ($) {
    'use strict';

    return {
        /**
         * 顯示項目列表（使用標準化資料格式）
         * 
         * 此方法接收標準化格式的資料陣列，動態創建完整的 DOM 結構並插入到父元素中。
         * 組件完全不知道業務資料結構，由調用方負責資料轉換。
         * 
         * @param {Array} items - 標準化資料陣列，格式：[{value: '顯示文字'}, ...]（必要）
         * @param {Object} options - 配置選項（必要）
         * @param {string} options.parentSelector - 父元素選擇器（必要）
         * @param {string} options.labelText - Label 顯示文字（必要）
         * @returns {jQuery} 創建的 DOM 元素，如果失敗返回 null
         * 
         * @example
         * // 顯示特約商名稱
         * var displayData = rowData.map(function(item) {
         *     return {value: item.shop_title};
         * });
         * selectedItemsDisplay.display(displayData, {
         *     parentSelector: '.modal-content',
         *     labelText: '特約商名稱'
         * });
         * 
         * @example
         * // 顯示客戶名稱
         * var displayData = customers.map(function(item) {
         *     return {value: item.customer_name};
         * });
         * selectedItemsDisplay.display(displayData, {
         *     parentSelector: '#customer-section',
         *     labelText: 'Customer Names'
         * });
         */
        display: function (items, options) {
            // 驗證必要參數
            if (!options || !options.parentSelector || !options.labelText) {
                console.error('❌ selectedItemsDisplay: 缺少必要參數', options);
                return null;
            }
            
            if (!Array.isArray(items)) {
                console.error('❌ selectedItemsDisplay: items 必須是陣列');
                return null;
            }
            
            // 創建 DOM 結構
            var $wrapper = $('<div class="selected-items-display"></div>');
            var $label = $('<label class="selected-items-display__label"></label>').text(options.labelText);
            var $container = $('<div class="selected-items-display__container"></div>');
            
            // 過濾有效項目並顯示
            var validItems = items.filter(function(item) {
                return item && item.value;
            });
            
            if (validItems.length > 0) {
                validItems.forEach(function(item) {
                    $container.append(
                        $('<div class="selected-items-display__item"></div>').text(item.value)
                    );
                });
            } else {
                $container.append('<span class="no-selected-items">No items available</span>');
            }
            
            // 組裝並插入
            $wrapper.append($label).append($container);
            $(options.parentSelector).append($wrapper);
            
            return $wrapper;
        }
    };
});


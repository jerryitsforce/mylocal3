/**
 * HotaiConnected UiShared - SumoSelect Configuration
 * 
 * @module HotaiConnected_UiShared/js/config/sumoselect-config
 * @version 2.0.0
 * @author HotaiConnected
 * @updated 2025-10-16 - 移至 UiShared 共用模組
 * 
 * 功能說明：
 * - 提供 SumoSelect 的全域預設配置
 * - 支援多選、單選配置
 * - 統一的 i18n 翻譯
 */
define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return {
        /**
         * 多選預設配置
         */
        multipleDefaults: {
            placeholder: $.mage.__('Search...'),    // 下拉選單未展開時顯示的搜尋文字
            search: true,                           // 啟用搜尋
            searchText: $.mage.__('Search...'),     // 下拉選單展開時的搜尋文字
            noMatch: $.mage.__('No results found'), // 找不到結果的文字
            clearAll: false,                        // 啟用清空所有按鈕
            closeAfterClearAll: false,              // 清空後關閉
            selectAll: true,                        // 啟用全選按鈕
            captionFormat: '{0}',                   // 簡單格式，會被自訂渲染覆蓋
            captionFormatAllSelected: '{0}',        // 簡單格式，會被自訂渲染覆蓋
            csvDispCount: 999,                      // 啟用顯示所有選中項目（大數字）
            isClickAwayOk: true,                    // 啟用點擊外部區域確認
            okCancelInMulti: false,                 // 啟用確認和取消按鈕
            triggerChangeCombined: false,            // 啟用觸發變更組合
            
            // 啟用語言包
            locale: [
                $.mage.__('OK'),                    // [0] - 確認按鈕
                $.mage.__('Cancel'),                // [1] - 取消按鈕
                $.mage.__('Check All'),             // [2] - 全選按鈕
                $.mage.__('Deselect All')           // [3] - 清空所有按鈕
            ]
        }
    };
});
/**
 * HotaiConnected UiShared - Date Pickers Utility
 * 
 * @module HotaiConnected_UiShared/js/utils/date-pickers
 * @version 2.0.0
 * @author HotaiConnected
 * @updated 2025-10-16 - 移至 UiShared 共用模組
 * 
 * 功能說明：
 * - Magento 日期選擇器初始化封裝
 * - 日期範圍驗證
 * - 預設值設定
 * - 表單驗證整合
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/calendar: Magento 日期選擇器
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/calendar'
], function ($, calendar) {
    'use strict';

    return {
        /**
         * 格式化日期為 yyyy-MM-dd 格式
         * @param {Date} date - 要格式化的日期物件
         * @returns {string} 格式化後的日期字串
         */
        formatDate: function(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        },

        /**
         * 初始化日期選擇器
         * @param {Object} config - 配置物件
         * @param {string} config.fromSelector - 開始日期選擇器，預設 '#date_from'
         * @param {string} config.toSelector - 結束日期選擇器，預設 '#date_to'
         * @param {string|Date|number} config.minDate - 最小日期範圍，預設 '-6m'，或傳入月數如 6
         * @param {boolean} config.setDefaultValues - 是否設定預設值，預設 true
         * @param {boolean} config.enableValidation - 選日期時是否清除錯誤顯示（僅移除 DOM，不呼叫 .valid()，避免 admin 下 jquery.validate 未註冊規則錯誤），預設 false
         * @param {Object} config.baseConfig - 基礎日曆配置，預設使用內建配置
         */
        initDatePickers: function(config) {
            // 使用預設配置
            var monthNames = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
            // 使用預設配置
            var defaultConfig = {
                fromSelector: '#date_from',
                toSelector: '#date_to',
                // minDate: '-10y',
                setDefaultValues: true,
                enableValidation: false,
                baseConfig: {
                    dateFormat: 'yy-mm-dd',
                    showsTime: false,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-10:+10',
                    buttonText: '',
                    monthNames: monthNames,
                    monthNamesShort: monthNames,
                    showOn: 'button'
                }
            };
            
            // 合併傳入的配置
            config = $.extend({}, defaultConfig, config || {});
            
            var fromSelector = config.fromSelector;
            var toSelector = config.toSelector;
            var minDate = config.minDate;
            var setDefaultValues = config.setDefaultValues;
            var enableValidation = config.enableValidation;
            var baseConfig = config.baseConfig;
            
            // 取得 DOM 元素
            var $fromElement = $(fromSelector);
            var $toElement = $(toSelector);
            
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            // 建立日曆配置
            var calendarConfig = $.extend({}, baseConfig, {
                maxDate: '+0d',
                minDate: minDate
            });
            
            var fromDateConfig = $.extend({}, calendarConfig, {
                onSelect: function(dateText, inst) {
                    $fromElement.val(dateText);
                    var selectedDate = new Date(dateText);
                    $toElement.datepicker('option', 'minDate', selectedDate);

                    if (enableValidation && $fromElement.length) {
                        $fromElement.removeClass('mage-error');
                        $fromElement.siblings('.mage-error').remove();
                        if ($fromElement.parent().length) {
                            $fromElement.parent().find('.mage-error').remove();
                        }
                    }
                }
            });

            var toDateConfig = $.extend({}, calendarConfig, {
                onSelect: function(dateText, inst) {
                    $toElement.val(dateText);
                    var selectedDate = new Date(dateText);
                    $fromElement.datepicker('option', 'maxDate', selectedDate);

                    if (enableValidation && $toElement.length) {
                        $toElement.removeClass('mage-error');
                        $toElement.siblings('.mage-error').remove();
                        if ($toElement.parent().length) {
                            $toElement.parent().find('.mage-error').remove();
                        }
                    }
                }
            });
            
            $fromElement.calendar(fromDateConfig);
            $toElement.calendar(toDateConfig);
            
            // 如果設定為設定預設值
            if (setDefaultValues) {
                $fromElement.val(this.formatDate(firstDay));
                $toElement.val(this.formatDate(lastDay));
            }
        }

    };
});

/**
 * HotaiConnected UiShared - SumoSelect Utility
 * 
 * @module HotaiConnected_UiShared/js/utils/sumoselect
 * @version 3.1.0
 * @author HotaiConnected
 * @updated 2025-10-22 - 新增 resetSelection() 方法（清除選擇但不重建）
 * @updated 2025-10-21 - 重構優化：移除未使用方法、統一邏輯、效能改進
 * @updated 2025-10-16 - 移至 UiShared 共用模組
 * @created 2025-10-15
 * 
 * 功能說明：
 * - SumoSelect 多選下拉選單的功能封裝
 * - 支援從資料陣列建立 SumoSelect
 * - 支援搜尋、多選、Checkbox 功能
 * - 精簡的 API：init(), updateData(), resetSelection(), getValue()
 * 
 * 核心需求：
 * 1. 輸入框只顯示已選的 tags
 * 2. 下拉選單與輸入框緊密貼合
 * 3. 下拉選單開頭有搜尋框
 * 4. 每個選項前有 checkbox
 * 5. 多選模式，選擇後不關閉
 * 
 * v3.0.0 重構內容：
 * - 移除未使用的方法 (setValue, clear, reload)
 * - init() 統一使用 _rebuildOptions 和 _validateDataFormat
 * - 改進 _renderSelectedAsTags 時序處理 (requestAnimationFrame)
 * - 效能優化：批次 DOM 操作、減少重複程式碼
 */
define([
    'jquery',
    'mage/translate',
    'sumoselect',
    'sumoselectConfig'
], function ($, $t, sumoselect, sumoConfig) {
    'use strict';

    return {
        /**
         * 初始化 SumoSelect 多選
         * 
         * @param {Object} config - 配置物件
         * @param {string} config.selector - 選擇器（必填）
         * @param {Array} config.data - 資料陣列 [{ id, text }]（選填，若不提供則使用現有 options）
         * @param {string} config.placeholder - 佔位文字（選填）
         * @param {Object} config.customConfig - 其他自訂配置（選填）
         * @returns {jQuery} SumoSelect 實例
         * 
         * @example
         * // 使用新資料初始化（會清空並重建 options）
         * sumoUtil.init({
         *     selector: '#my-select',
         *     data: [{id: '1', text: 'Option 1'}],
         *     placeholder: 'Select...'
         * });
         * 
         * @example
         * // 使用現有 options 初始化（不清空）
         * sumoUtil.init({
         *     selector: '#my-select',
         *     placeholder: 'Select...'
         * });
         */
        init: function(config) {
            if (!config.selector) {
                console.error('SumoSelect Util: selector is required');
                return null;
            }

            var $select = $(config.selector);
            
            if ($select.length === 0) {
                console.error('SumoSelect Util: Element not found - ' + config.selector);
                return null;
            }

            // 確保是多選模式
            if (!$select.prop('multiple')) {
                $select.prop('multiple', true);
            }

            // 如果提供了 data，驗證並重建 options
            if (config.data && Array.isArray(config.data)) {
                // 驗證資料格式
                if (!this._validateDataFormat(config.data)) {
                    return null;
                }
                // 批次重建選項（效能優化，與 updateData 一致）
                this._rebuildOptions($select, config.data);
            }
            // 否則使用現有的 options（不做任何處理）

            // 合併配置：基礎配置 → placeholder → 自訂配置（customConfig 可覆蓋所有設定）
            var sumoOptions = $.extend({}, 
                sumoConfig.multipleDefaults,  // 1. 基礎配置
                {
                    placeholder: config.placeholder || $t('Search...')  // 2. placeholder
                },
                config.customConfig || {}      // 3. 自訂配置（優先權最高）
            );

            // 初始化 SumoSelect
            $select.SumoSelect(sumoOptions);

            // 自訂渲染：將選中項目顯示為獨立的 span 標籤
            if (config.renderAsTags !== false) {  // 預設啟用，可透過 config.renderAsTags = false 關閉
                this._renderSelectedAsTags($select);
            }

            return $select;
        },

        /**
         * 將選中項目渲染為獨立的 span 標籤
         * @private
         * @param {jQuery} $select - jQuery 選擇器物件
         */
        _renderSelectedAsTags: function($select) {
            var self = this;
            
            var renderTags = function() {
                var sumo = $select[0].sumo;
                if (!sumo) return;
                
                var $caption = $select.parent().find('.CaptionCont > span:first');
                if ($caption.length === 0) return;
                
                var selectedOptions = $select.find('option:selected');
                
                // 清空內容
                $caption.empty();
                
                if (selectedOptions.length === 0) {
                    // 沒有選中項目，顯示 placeholder
                    $caption.html('<span class="placeholder">' + (sumo.placeholder || '') + '</span>');
                    return;
                }
                
                // 為每個選中項目建立 span 標籤
                selectedOptions.each(function() {
                    var text = $(this).text();
                    var value = $(this).val();
                    
                    // 建立標籤容器
                    var $tag = $('<span class="sumo-tag"></span>');
                    var $text = $('<span class="sumo-tag-text"></span>').text(text);
                    var $close = $('<span class="sumo-tag-close">×</span>');
                    
                    // 綁定關閉按鈕點擊事件
                    $close.on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        sumo.unSelectItem(value);
                        $select.trigger('change');
                    });
                    
                    $tag.append($text).append($close);
                    $caption.append($tag);
                });
            };
            
            // 使用 requestAnimationFrame 取代 setTimeout（更精確的時機）
            var scheduleRender = function() {
                if (window.requestAnimationFrame) {
                    requestAnimationFrame(renderTags);
                } else {
                    setTimeout(renderTags, 0);  // Fallback for older browsers
                }
            };
            
            // 初始渲染（等待 DOM 完成）
            scheduleRender();
            
            // 監聽多個事件以確保正確渲染（合併為一次綁定）
            $select.on('change.sumoTags sumo:opened.sumoTags sumo:closed.sumoTags', scheduleRender);
            
            // 監聽選單容器的點擊（使用事件委託）
            // 延遲一小段時間等待 SumoSelect DOM 完成
            setTimeout(function() {
                var $sumoContainer = $select.parent('.SumoSelect');
                if ($sumoContainer.length > 0) {
                    $sumoContainer.on('click.sumoTags', '.options li', scheduleRender);
                }
            }, 100);
        },

        /**
         * 取得選中的值
         * @param {string} selector - 選擇器
         * @returns {Array|null} 選中的值
         */
        getValue: function(selector) {
            return $(selector).val();
        },

        /**
         * 重置選擇（清除已選的選項，但不重建選項）
         * 
         * 採用 SumoSelect 官方 API：
         * - unSelectAll() - 取消所有選擇（會自動更新 UI）
         * 
         * 官方文檔：https://hemantnegi.github.io/jquery.sumoselect/
         * 用法：$('select.SlectBox')[0].sumo.unSelectAll();
         * 
         * @param {string} selector - 選擇器（必填）
         * @param {boolean} triggerChange - 是否觸發 change 事件（選填，預設 false）
         * @returns {boolean} 是否成功
         * 
         * @example
         * // 清除選擇（不觸發 change 事件）
         * sumoUtil.resetSelection('#my-select');
         * 
         * @example
         * // 清除選擇並觸發 change 事件
         * sumoUtil.resetSelection('#my-select', true);
         */
        resetSelection: function(selector, triggerChange) {
            // 1. 驗證選擇器
            var $select = $(selector);
            if ($select.length === 0) {
                console.error('SumoSelect Util (resetSelection): Element not found - ' + selector);
                return false;
            }

            // 2. 取得 SumoSelect 實例
            var sumoInstance = $select[0].sumo;
            if (!sumoInstance) {
                console.error('SumoSelect Util (resetSelection): SumoSelect instance not found - ' + selector);
                return false;
            }

            // 3. 使用官方 API 取消所有選擇（會自動更新 UI）
            // 等同於：$('select.SlectBox')[0].sumo.unSelectAll();
            sumoInstance.unSelectAll();

            console.log('✅ SumoSelect 已重置選擇：' + selector);
            return true;
        },

        /**
         * 驗證資料格式
         * @private
         * @param {Array} data - 資料陣列
         * @returns {boolean} 是否有效
         */
        _validateDataFormat: function(data) {
            if (!data || !Array.isArray(data)) {
                console.error('SumoSelect Util: Invalid data - must be an array');
                return false;
            }

            if (data.length === 0) {
                console.warn('SumoSelect Util: Empty data array');
                return true; // 空陣列是有效的
            }

            // 驗證每個項目的格式
            var isValid = data.every(function(item, index) {
                if (!item || typeof item !== 'object') {
                    console.error('SumoSelect Util: Invalid item at index ' + index + ' - must be an object');
                    return false;
                }
                if (!('id' in item) || !('text' in item)) {
                    console.error('SumoSelect Util: Invalid item at index ' + index + ' - must have {id, text}');
                    return false;
                }
                return true;
            });

            return isValid;
        },

        /**
         * 銷毀 SumoSelect 實例（內部共用方法）
         * @private
         * @param {jQuery} $select - jQuery 選擇器物件
         */
        _destroyInstance: function($select) {
            if (!$select || $select.length === 0) {
                return;
            }

            // 移除事件監聽
            $select.off('.sumoTags');
            
            var $sumoContainer = $select.parent('.SumoSelect');
            if ($sumoContainer.length > 0) {
                $sumoContainer.find('.options').off('.sumoTags');
            }
            
            // 銷毀 SumoSelect 實例
            var sumo = $select[0].sumo;
            if (sumo) {
                sumo.unload();
            }
        },

        /**
         * 批次重建選項（效能優化）
         * @private
         * @param {jQuery} $select - jQuery 選擇器物件
         * @param {Array} data - 資料陣列 [{ id, text }]
         */
        _rebuildOptions: function($select, data) {
            // 使用 map + 批次 append，減少 DOM 操作次數
            var options = data.map(function(item) {
                return new Option(item.text, item.id, false, false);
            });
            
            $select.empty().append(options);
        },

        /**
         * 更新 SumoSelect 的資料
         * 
         * 此方法會銷毀現有的 SumoSelect、清空選項、重建選項並重新初始化
         * 
         * @param {string} selector - 選擇器
         * @param {Array} data - 新的資料陣列 [{ id, text }]
         * @param {Object} customConfig - 自訂配置（選填）可覆蓋 sumoConfig.multipleDefaults 的任何設定
         *                                全域預設包含 selectAll: true（啟用全選按鈕）
         *                                可透過 customConfig 覆蓋，例如：{ selectAll: false }
         * @returns {jQuery|boolean} 成功時返回 jQuery 物件，失敗時返回 false
         * 
         * @example
         * // 使用全域預設配置（selectAll: true）
         * sumoUtil.updateData('#my-select', data);
         * 
         * @example
         * // 覆蓋全域配置，停用全選按鈕
         * sumoUtil.updateData('#my-select', data, { selectAll: false });
         * 
         * @example
         * // 自訂多個配置選項，並支援鏈式呼叫
         * var $select = sumoUtil.updateData('#my-select', data, { placeholder: 'Custom...' });
         * if ($select) {
         *     // 更新成功
         * }
         */
        updateData: function(selector, data, customConfig) {
            // 1. 驗證選擇器
            var $select = $(selector);
            if ($select.length === 0) {
                console.error('SumoSelect Util: Element not found - ' + selector);
                return false;
            }

            // 2. 驗證資料格式
            if (!this._validateDataFormat(data)) {
                return false;
            }

            // 3. 銷毀舊的 SumoSelect 實例
            this._destroyInstance($select);

            // 4. 批次重建選項（效能優化）
            this._rebuildOptions($select, data);

            // 5. 合併配置並初始化
            var sumoOptions = $.extend({}, 
                sumoConfig.multipleDefaults,  // 1. 基礎配置（包含 selectAll: true）
                customConfig || {}           // 2. 自訂配置（優先權最高，可覆蓋任何設定）
            );

            $select.SumoSelect(sumoOptions);

            // 6. 自訂渲染：將選中項目顯示為獨立的 span 標籤
            this._renderSelectedAsTags($select);

            // 7. 返回 jQuery 物件，支援鏈式呼叫
            return $select;
        },

        /**
         * 銷毀 SumoSelect
         * @param {string} selector - 選擇器
         * @returns {boolean} 是否成功銷毀
         */
        destroy: function(selector) {
            var $select = $(selector);
            
            if ($select.length === 0) {
                console.warn('SumoSelect Util: Element not found for destroy - ' + selector);
                return false;
            }
            
            this._destroyInstance($select);
            return true;
        }
    };
});


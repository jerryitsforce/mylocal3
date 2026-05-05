/**
 * HotaiConnected UiShared - Loading Mask Component
 * 
 * @module HotaiConnected_UiShared/js/components/loading-mask
 * @version 2.0.1
 * @author HotaiConnected
 * @updated 2025-10-16 - 移至 UiShared 共用模組，改用 DOM API 創建元素
 * 
 * 功能說明：
 * - 提供統一的全螢幕載入動畫功能
 * - 使用原生 DOM API 創建元素（無需 HTML 模板）
 * - 支援動態創建容器、自動初始化、事件監聽
 * - 確保所有頁面的載入動畫效果完全一致
 * 
 * 基於 Magento 2 官方 Loader widget 實作：
 * @see {@link https://developer.adobe.com/commerce/frontend-core/javascript/jquery-widgets/loader/}
 * 
 * @example
 * // 基本使用（自動創建容器）
 * require(['hotaiLoadingMask'], function(loadingMask) {
 *     loadingMask.init();
 * });
 * 
 * @example
 * // 自訂配置使用
 * require(['hotaiLoadingMask'], function(loadingMask) {
 *     loadingMask.init('#custom-loader', {
 *         icon: 'custom-loader.gif',
 *         texts: {
 *             loaderText: $.mage.__('載入中...'),
 *             imgAlt: $.mage.__('載入圖示')
 *         }
 *     }, {
 *         onStart: function() { console.log('載入開始'); },
 *         onStop: function() { console.log('載入結束'); }
 *     });
 * });
 * 
 * @example
 * // 顯示/隱藏載入動畫
 * loadingMask.show();
 * loadingMask.hide();
 * 
 * 依賴關係：
 * 
 * Magento 內建功能 (按優先級排序)
 * @requires {jQuery} jquery                        - jQuery 核心庫，用於 DOM 操作和事件處理
 * @requires {Object} mage/loader                   - Magento Loader widget，用於載入動畫
 * @requires {Object} mage/translate                - Magento 翻譯功能，提供 $.mage.__() 函數
 * @requires {Object} mage/url                      - Magento URL 建構器，用於生成圖片 URL
 * @requires {Object} domReady!                     - RequireJS 插件，確保 DOM 載入完成後執行
 * 
 * 技術實作：
 * - 使用原生 DOM API 創建 HTML 元素，無需外部模板檔案
 * - 透過 _createLoadingMaskElement() 方法動態生成載入動畫結構
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/loader',
    'mage/translate',
    'mage/url',
    'domReady!'
], function ($, loader, $t, urlBuilder, domReady) {
    'use strict';

    // 預設配置參數
    var DEFAULT_CONFIG = {
        selector: '#loading-mask',
        icon: 'images/loader-1.gif',
        texts: {
            loaderText: 'Please wait...',
            imgAlt: ''
        }
    };

    return {
        /**
         * 建立載入動畫 DOM 結構
         * @param {Object} options - 配置選項
         * @returns {HTMLElement} 載入動畫容器
         * @private
         */
        _createLoadingMaskElement: function(options) {
            // 建立主容器
            var container = document.createElement('div');
            container.className = 'loading-mask';
            container.setAttribute('data-role', 'loader');

            // 建立 loader 容器
            var loaderDiv = document.createElement('div');
            loaderDiv.className = 'loader';

            // 建立圖片
            var img = document.createElement('img');
            img.alt = $.mage.__(options.texts.imgAlt);
            img.src = urlBuilder.build(options.icon);

            // 建立文字
            var p = document.createElement('p');
            p.textContent = $.mage.__(options.texts.loaderText);

            // 組裝結構
            loaderDiv.appendChild(img);
            loaderDiv.appendChild(p);
            container.appendChild(loaderDiv);

            return container;
        },

        /**
         * 初始化 Loader widget 並綁定事件監聽器
         * 
         * 如果未指定選擇器，會自動創建預設容器。支援自訂配置和事件回調。
         * 
         * @param {string} [selector] - 目標容器的 CSS 選擇器，預設為 '#loading-mask'
         * @param {Object} [options] - 可選配置參數
         * @param {string} [options.icon] - 載入圖示路徑，預設為 'images/loader-1.gif'
         * @param {Object} [options.texts] - 文字配置
         * @param {string} [options.texts.loaderText] - 載入文字，預設為 'Please wait...'
         * @param {string} [options.texts.imgAlt] - 圖片 alt 文字，預設為空字串
         * @param {Object} [callbacks] - 事件回調函數
         * @param {Function} [callbacks.onStart] - 載入開始時的回調函數
         * @param {Function} [callbacks.onStop] - 載入結束時的回調函數
         * 
         * @example
         * // 基本使用
         * loadingMask.init();
         * 
         * @example
         * // 自訂配置
         * loadingMask.init('#my-loader', {
         *     icon: 'custom-loader.gif',
         *     texts: {
         *         loaderText: $.mage.__('載入中...')
         *     }
         * });
         */
        init: function(selector, options, callbacks) {
            
            // 如果沒有指定選擇器或使用預設選擇器，動態創建容器
            if (!selector || selector === DEFAULT_CONFIG.selector) {
                var defaultId = DEFAULT_CONFIG.selector.replace('#', '');
                if (document.getElementById(defaultId) === null) {
                    var containerDiv = document.createElement('div');
                    containerDiv.id = defaultId;
                    document.body.appendChild(containerDiv);
                }
                selector = DEFAULT_CONFIG.selector;
            }
            
            options = options || {};
            callbacks = callbacks || {};
            
            // 合併配置（icon 保留路徑，在 _createLoadingMaskElement 內才做 urlBuilder.build，避免重複 build）
            var mergedOptions = {
                icon: options.icon || DEFAULT_CONFIG.icon,
                texts: {
                    loaderText: (options.texts && options.texts.loaderText) || DEFAULT_CONFIG.texts.loaderText,
                    imgAlt: (options.texts && options.texts.imgAlt) || DEFAULT_CONFIG.texts.imgAlt
                }
            };

            // 取得/建立模板內容
           
            var templateElement = this._createLoadingMaskElement(mergedOptions);

            var config = {
                template: templateElement.outerHTML
            };

            // 初始化 Loader widget
            $(selector).empty();
            $(selector).loader(config);
            
            // 綁定事件監聽器
            $(selector).on('processStart', function() {
                console.log('載入動畫開始');
                if (callbacks.onStart && typeof callbacks.onStart === 'function') {
                    callbacks.onStart();
                }
            });
            
            $(selector).on('processStop', function() {
                console.log('載入動畫結束');
                if (callbacks.onStop && typeof callbacks.onStop === 'function') {
                    callbacks.onStop();
                }
            });
        },

        /**
         * 顯示載入動畫
         * 
         * 觸發 processStart 事件來顯示載入動畫
         * 
         * @param {string} [selector] - 目標容器的 CSS 選擇器，預設為 '#loading-mask'
         * 
         * @example
         * // 使用預設容器
         * loadingMask.show();
         * 
         * @example
         * // 使用自訂容器
         * loadingMask.show('#my-loader');
         */
        show: function(selector) {
            selector = selector || DEFAULT_CONFIG.selector;
            $(selector).trigger('processStart');
        },

        /**
         * 隱藏載入動畫
         * 
         * 觸發 processStop 事件來隱藏載入動畫
         * 
         * @param {string} [selector] - 目標容器的 CSS 選擇器，預設為 '#loading-mask'
         * 
         * @example
         * // 使用預設容器
         * loadingMask.hide();
         * 
         * @example
         * // 使用自訂容器
         * loadingMask.hide('#my-loader');
         */
        hide: function(selector) {
            selector = selector || DEFAULT_CONFIG.selector;
            $(selector).trigger('processStop');
        },

    };
});
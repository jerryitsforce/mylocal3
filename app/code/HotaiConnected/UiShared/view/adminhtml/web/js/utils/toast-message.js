/**
 * HotaiConnected UiShared - Toast Message Controller
 * 
 * 功能說明：
 * - 統一的 Toast 訊息顯示系統
 * - 支援多種訊息類型（success, error, warning, info）
 * - 支援字串和物件兩種輸入方式
 * - 支援自訂顯示時間（0 表示不自動消失）
 * - 支援 HTML 內容顯示
 * - 手動關閉功能
 * - 使用原生 DOM API 創建元素（無需 HTML 模板）
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/translate: 翻譯功能
 * 
 * @module HotaiConnected_UiShared/js/utils/toast-message
 * @version 2.0.1
 * @author HotaiConnected
 * @updated 2025-10-20 - 修正：關閉按鈕 HTML 結構與 CSS 樣式匹配，避免與 Magento 預設樣式衝突
 * @updated 2025-10-16 - 改用原生 DOM API 創建元素，移除 HTML 模板依賴
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return {
            /**
             * 預設顯示設定
             */
            defaultDuration: {
                autoClose: false,    // 預設不自動關閉
                duration: 3000       // 預設顯示時間 3 秒
            },

            /**
             * 建立 Toast 訊息 DOM 結構
             * @param {string} type - 訊息類型
             * @param {string} title - 標題
             * @param {string} content - 內容
             * @returns {HTMLElement} Toast 訊息容器
             * @private
             */
            _createToastElement: function(type, title, content) {
                // 建立主容器
                var container = document.createElement('div');
                container.className = 'toast-message';
                container.setAttribute('data-type', type);

                // 建立內容容器
                var contentWrapper = document.createElement('div');
                contentWrapper.className = 'toast-message__content';

                // 建立標題
                var labelDiv = document.createElement('div');
                labelDiv.className = 'toast-message__content__label';
                var labelSpan = document.createElement('span');
                labelSpan.textContent = $.mage.__(title);
                labelDiv.appendChild(labelSpan);

                // 建立訊息內容
                var messageDiv = document.createElement('div');
                messageDiv.className = 'toast-message__content__message';
                
                // 判斷內容是否為 HTML
                if (content && content.indexOf('<') !== -1) {
                    messageDiv.innerHTML = content;  // HTML 內容
                } else {
                    messageDiv.textContent = $.mage.__(content);  // 純文字
                }

                // 建立關閉按鈕
                var closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'toast-message__close';
                closeBtn.title = 'Close';
                
                // 建立關閉按鈕的 span 元素（符合 CSS 樣式結構）
                var closeSpan = document.createElement('span');
                closeSpan.textContent = '×';
                closeBtn.appendChild(closeSpan);

                // 組裝結構
                contentWrapper.appendChild(labelDiv);
                contentWrapper.appendChild(messageDiv);
                container.appendChild(contentWrapper);
                container.appendChild(closeBtn);

                return container;
            },

            /**
             * 顯示 Toast 訊息
             * @param {string} type - 訊息類型 (success, error, warning, info)
             * @param {string|object} input - 訊息內容或包含 title、content、autoClose、duration 的物件
             * @param {string} [input.title] - 訊息標題（當 input 是物件時）
             * @param {string} [input.content] - 訊息內容（當 input 是物件時，支援 HTML）
             * @param {boolean} [input.autoClose] - 是否自動關閉（預設 false）
             * @param {number} [input.duration] - 顯示時間（毫秒，預設 3000）
             */
            show: function(type, input) {
                var title, content, autoClose, duration;
                
                // 判斷輸入類型
                if (typeof input === 'object' && input !== null && (input.title || input.content)) {
                    title = input.title || this._getDefaultTitle(type);
                    content = input.content || '';
                    autoClose = input.autoClose !== undefined ? input.autoClose : this.defaultDuration.autoClose;
                    duration = input.duration !== undefined ? input.duration : this.defaultDuration.duration;
                } else {
                    title = this._getDefaultTitle(type);
                    content = input || '';
                    autoClose = this.defaultDuration.autoClose;
                    duration = this.defaultDuration.duration;
                }

                // 計算實際顯示時間
                var displayDuration = autoClose ? duration : 0;

                // 移除現有的 toast 訊息
                var existingToasts = document.querySelectorAll('.toast-message');
                existingToasts.forEach(function(toast) {
                    toast.remove();
                });

                // 使用 DOM API 建立訊息容器
                var messageContainer = this._createToastElement(type, title, content);
                
                // 插入訊息到 main 元素內的第一個位置
                var main = document.querySelector('main');
                var targetElement = main || document.body;
                targetElement.insertBefore(messageContainer, targetElement.firstChild);
                
                // 綁定關閉按鈕事件
                var closeBtn = messageContainer.querySelector('.toast-message__close');
                closeBtn.addEventListener('click', function() {
                    $(messageContainer).fadeOut(300, function() {
                        messageContainer.remove();
                    });
                });
                
                // 自動隱藏訊息
                if (displayDuration > 0) {
                    setTimeout(function() {
                        $(messageContainer).fadeOut(300, function() {
                            messageContainer.remove();
                        });
                    }, displayDuration);
                }
            },

            /**
             * 取得預設標題
             * @param {string} type - 訊息類型
             * @returns {string} 預設標題
             */
            _getDefaultTitle: function(type) {
                switch(type) {
                    case 'success': return 'Success';
                    case 'error': return 'Error';
                    case 'warning': return 'Warning';
                    case 'info': 
                    default: return 'Information';
                }
            },

        /**
         * 顯示成功訊息
         * @param {string|object} input - 訊息內容或包含 title、content、autoClose、duration 的物件
         */
        success: function(input) {
            this.show('success', input);
        },

        /**
         * 顯示錯誤訊息
         * @param {string|object} input - 訊息內容或包含 title、content、autoClose、duration 的物件
         */
        error: function(input) {
            this.show('error', input);
        },

        /**
         * 顯示警告訊息
         * @param {string|object} input - 訊息內容或包含 title、content、autoClose、duration 的物件
         */
        warning: function(input) {
            this.show('warning', input);
        },

        /**
         * 顯示資訊訊息
         * @param {string|object} input - 訊息內容或包含 title、content、autoClose、duration 的物件
         */
        info: function(input) {
            this.show('info', input);
        },

        /**
         * 手動移除所有 toast 訊息
         */
        remove: function() {
            $('.toast-message').fadeOut(300, function() {
                $(this).remove();
            });
        }
    };
});

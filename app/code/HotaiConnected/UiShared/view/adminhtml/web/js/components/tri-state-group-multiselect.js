/**
 * HotaiConnected UiShared - Tri-State Group Multiselect Component
 * 
 * @module HotaiConnected_UiShared/js/components/tri-state-group-multiselect
 * @version 2.0.0
 * @author HotaiConnected
 * @updated 2025-10-16 - 移至 UiShared 共用模組
 * 
 * 功能說明：
 * - 動態建立 checkbox 群組（支援多選）
 * - 智能全選 checkbox，支援三態顯示（checked / indeterminate / unchecked）
 * - 自動配置驗證規則（validate-one-required-by-name）
 * - 支援自訂錯誤訊息
 * - 支援多種網格佈局（grid-2, grid-3, grid-4 等）
 * - 支援 header 和 footer 自訂內容
 * - 統一的 checkbox 建立邏輯
 * - 資料格式：[{ key: 'checkbox值', value: '顯示文字' }]
 * 
 * 使用範例：
 * ```javascript
 * // 基本使用（單欄顯示）
 * triStateGroupMultiselect.create({
 *     containerSelector: '.product-shipping',
 *     name: 'product-shipping-status',
 *     data: [
 *         { key: 'pending', value: '待處理' },
 *         { key: 'shipped', value: '已出貨' }
 *     ],
 *     validationMessage: 'Please select at least one shipping status.',
 *     showSelectAll: true,
 *     columns: 1  // 每行 1 個（預設值）
 * });
 * 
 * // 多欄顯示
 * triStateGroupMultiselect.create({
 *     containerSelector: '.invoice-status',
 *     name: 'invoice-status',
 *     data: invoiceData,  // 格式：[{ key: 'code', value: 'label' }]
 *     columns: 3,  // 每行 3 個
 *     showSelectAll: true
 * });
 * 
 * // 完整範例（含 header 和 footer）
 * triStateGroupMultiselect.create({
 *     containerSelector: '.ticket-status',
 *     name: 'ticket-status',
 *     data: ticketData,  // 格式：[{ key: 'code', value: 'label' }]
 *     columns: 2,  // 每行 2 個
 *     headerContent: '<p class="note">請至少選擇一個票券狀態</p>',
 *     footerContent: '<a href="#">查看更多說明</a>',
 *     showSelectAll: true
 * });
 * ```
 * 
 * 生成的 HTML 結構：
 * ```html
 * <div class="tri-state-group-multiselect">
 *     <div class="tri-state-group-multiselect__header">...</div>
 *     <div class="tri-state-group-multiselect__main">
 *         <div class="tri-state-group-multiselect__main__action">
 *             <input type="checkbox" class="admin__control-checkbox tri-state-group-multiselect__select-all">
 *             <label class="admin__field-label">Select All</label>
 *         </div>
 *         <div class="tri-state-group-multiselect__main__options grid-3">
 *             <div class="tri-state-group-multiselect__item">...</div>
 *             <div class="tri-state-group-multiselect__item">...</div>
 *         </div>
 *     </div>
 *     <div class="mage-error">錯誤訊息（由 Magento 驗證插入）</div>
 *     <div class="tri-state-group-multiselect__footer">...</div>
 * </div>
 * ```
 * 
 * 全選 checkbox 三態說明：
 * - 未勾選：所有選項都未選取
 * - 部分選取（indeterminate，顯示 - ）：部分選項被選取
 * - 全選（checked，顯示 ✓ ）：所有選項都被選取
 * 
 * 點擊行為：
 * - 未勾選狀態 → 點擊 → 全選
 * - 部分選取狀態 → 點擊 → 全部取消
 * - 全選狀態 → 點擊 → 全部取消
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/translate: Magento 多語系翻譯功能（$.mage.__）
 * 
 * @author Branch8
 * @version 3.3.1
 * @created 2025-10-09
 * @updated 2025-10-14 - 修正註解：統一資料格式說明為 { key, value }，與實際程式碼一致
 * @updated 2025-10-09 - 新增 removeError() API，錯誤消失時移除 _error class
 * @updated 2025-10-09 - placeError() 添加 _error class 到 __main 元素
 * @updated 2025-10-09 - 新增 placeError() API，讓元件自己決定錯誤訊息位置
 * @updated 2025-10-09 - 統一所有 class 名稱為 tri-state-group-multiselect
 * @updated 2025-10-09 - 重命名為 tri-state-group-multiselect（更準確描述功能）
 * @updated 2025-10-09 - 修正 removeClass 錯誤移除識別 class 的問題
 * @updated 2025-10-09 - 簡化狀態判斷邏輯，使用布林運算式
 * @updated 2025-10-09 - 使用專屬 class (checkbox-group__select-all)
 * @updated 2025-10-09 - 簡化架構，移除過度抽象，統一使用 jQuery
 * @updated 2025-10-09 - 移除 setTimeout，分離建立與綁定邏輯，添加事件命名空間
 * @updated 2025-10-09 - 新增 columns 參數，可控制每行顯示的選項數量（1-6）
 * @updated 2025-10-09 - 重構 HTML 結構，支援 header/footer 自訂內容
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * 建立 checkbox 群組
         * 
         * @param {Object} config - 配置物件
         * @param {string} config.containerSelector - 容器選擇器（必填）
         * @param {string} config.name - checkbox 的 name 屬性（必填）
         * @param {Array} config.data - 資料陣列，每個項目需包含 key 和 value，格式：[{ key: 'code', value: 'label' }]（必填）
         * @param {string} [config.validationMessage] - 驗證錯誤訊息（選填，預設為 'Please select at least one option.'）
         * @param {string} [config.checkboxClass] - 額外的 checkbox CSS class（選填）
         * @param {boolean} [config.enableValidation=true] - 是否啟用驗證（選填，預設為 true）
         * @param {boolean} [config.showSelectAll=false] - 是否顯示全選 checkbox（選填，預設為 false）
         * @param {number} [config.columns=1] - 每行顯示的選項數量，範圍 1-6（選填，預設為 1）
         * @param {string|jQuery} [config.headerContent] - 頭部自訂內容（選填）
         * @param {string|jQuery} [config.footerContent] - 尾部自訂內容（選填）
         * 
         * @returns {jQuery} 容器元素
         */
        create: function(config) {
            // 驗證必填參數
            if (!config.containerSelector) {
                console.error('Tri-State Group Multiselect: containerSelector is required');
                return null;
            }
            if (!config.name) {
                console.error('Tri-State Group Multiselect: name is required');
                return null;
            }
            if (!config.data || !Array.isArray(config.data) || config.data.length === 0) {
                console.error('Tri-State Group Multiselect: data array is required and must not be empty');
                return null;
            }

            // 預設配置
            var defaultConfig = {
                validationMessage: 'Please select at least one option.',
                checkboxClass: '',
                enableValidation: true,
                showSelectAll: false,
                columns: 1,
                headerContent: null,
                footerContent: null
            };

            // 合併配置
            config = $.extend({}, defaultConfig, config);
            
            // 驗證 columns 參數範圍（確保為整數）
            config.columns = Math.floor(Math.max(1, Math.min(6, Number(config.columns) || 1)));

            // 取得容器元素
            var $container = $(config.containerSelector);
            if ($container.length === 0) {
                console.error('Tri-State Group Multiselect: Container not found - ' + config.containerSelector);
                return null;
            }

            // 清空容器（如果需要重新建立）
            $container.empty();
            
            // 加上結構 class（保留原有 class）
            $container.addClass('tri-state-group-multiselect');

            // 建立頭部區塊（如果有內容）
            if (config.headerContent) {
                var $header = $('<div>', {
                    'class': 'tri-state-group-multiselect__header'
                });
                if (typeof config.headerContent === 'string') {
                    $header.html(config.headerContent);
                } else {
                    $header.append(config.headerContent);
                }
                $container.append($header);
            }

            // 建立主要內容區塊
            var $main = $('<div>', {
                'class': 'tri-state-group-multiselect__main'
            });

            // 如果啟用全選控制，建立全選 checkbox（只建立 DOM，不綁定事件）
            var $selectAllCheckbox = null;
            if (config.showSelectAll) {
                var $action = this._createSelectAllControls(config);
                $main.append($action);
                $selectAllCheckbox = $action.find('input[type="checkbox"]');
            }

            // 建立選項容器
            var $options = this._createOptions(config);
            $main.append($options);
            $container.append($main);

            // 建立尾部區塊（如果有內容）
            if (config.footerContent) {
                var $footer = $('<div>', {
                    'class': 'tri-state-group-multiselect__footer'
                });
                if (typeof config.footerContent === 'string') {
                    $footer.html(config.footerContent);
                } else {
                    $footer.append(config.footerContent);
                }
                $container.append($footer);
            }

            // ✅ DOM 完全建立後，綁定事件
            if (config.showSelectAll) {
                this._bindSelectAllEvents(config.containerSelector);
            }

            return $container;
        },

        /**
         * 建立選項容器（內部方法）
         * 
         * @param {Object} config - 配置物件
         * @private
         * @returns {jQuery} 選項容器元素
         */
        _createOptions: function(config) {
            var optionsClass = 'tri-state-group-multiselect__main__options';
            if (config.columns > 1) {
                optionsClass += ' grid-' + config.columns;
            }
            var $options = $('<div>', {
                'class': optionsClass
            });

            // 建立 checkbox 元素
            config.data.forEach(function(item, index) {
                var checkboxId = config.name + '-' + item.key;
                var checkboxName = config.name + '[]';
                
                // 創建容器 div
                var $itemContainer = $('<div>', {
                    'class': 'tri-state-group-multiselect__item'
                });
                
                // 建立 checkbox input
                var $input = $('<input>', {
                    'type': 'checkbox',
                    'name': checkboxName,
                    'id': checkboxId,
                    'class': 'admin__control-checkbox',
                    'value': item.key
                });

                // 添加額外的 CSS class
                if (config.checkboxClass) {
                    $input.addClass(config.checkboxClass);
                }
                
                // 第一個 checkbox 添加驗證規則
                if (config.enableValidation && index === 0) {
                    $input.addClass('validate-one-required-by-name');
                    $input.attr('data-validate', JSON.stringify({
                        'validate-one-required-by-name': 'input[name="' + checkboxName + '"]:checked'
                    }));
                    $input.attr('data-msg-validate-one-required-by-name', $.mage.__(config.validationMessage));
                }
                
                // 創建 label
                var $label = $('<label>', {
                    'for': checkboxId,
                    'class': 'admin__field-label',
                    'text': item.value
                });
                
                // 組裝元素
                $itemContainer.append($input).append($label);
                $options.append($itemContainer);
            });

            return $options;
        },

        /**
         * 建立全選控制 checkbox（內部方法）
         * 只負責建立 DOM 元素，不綁定事件
         * 
         * @param {Object} config - 配置物件
         * @private
         * @returns {jQuery} 全選控制元素
         */
        _createSelectAllControls: function(config) {
            // 建立全選動作區塊
            var $actionContainer = $('<div>', {
                'class': 'tri-state-group-multiselect__main__action'
            });
            
            // 生成唯一 ID
            var uniqueId = 'select-all-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            
            // 建立「全選」checkbox
            var $selectAllCheckbox = $('<input>', {
                'type': 'checkbox',
                'id': uniqueId,
                'class': 'admin__control-checkbox tri-state-group-multiselect__select-all'
            });
            
            // 建立 label
            var $selectAllLabel = $('<label>', {
                'for': uniqueId,
                'class': 'admin__field-label',
                'text': $.mage.__('Check All')
            });
            
            // 組裝元素
            $actionContainer.append($selectAllCheckbox).append($selectAllLabel);
            
            return $actionContainer;
        },

        /**
         * 綁定全選相關事件（內部方法）
         * 在 DOM 完全建立後調用
         * 
         * @param {string} containerSelector - 容器選擇器
         * @private
         */
        _bindSelectAllEvents: function(containerSelector) {
            var self = this;
            var $container = $(containerSelector);
            
            // ✅ 使用專屬 class 選擇全選 checkbox
            var $selectAllCheckbox = $container.find('.tri-state-group-multiselect__select-all');
            var $optionsContainer = $container.find('.tri-state-group-multiselect__main__options');
            
            if ($selectAllCheckbox.length === 0) {
                console.error('Tri-State Group Multiselect: select-all checkbox not found for', containerSelector);
                return;
            }
            
            // 記錄點擊前的狀態
            var previousState = {
                checked: false,
                indeterminate: false
            };
            
            // 綁定全選 checkbox 的 mousedown 事件（記錄狀態）
            $selectAllCheckbox.on('mousedown.triStateGroup', function() {
                previousState.checked = this.checked;
                previousState.indeterminate = this.indeterminate;
            });
            
            // 綁定全選 checkbox 的 click 事件
            $selectAllCheckbox.on('click.triStateGroup', function(e) {
                // 根據點擊前的狀態決定操作
                if (previousState.checked || previousState.indeterminate) {
                    // 點擊前有選取 → 全部取消
                    self.clearSelection(containerSelector);
                    this.checked = false;
                    this.indeterminate = false;
                } else {
                    // 點擊前未選取 → 全部勾選
                    self.selectAll(containerSelector);
                    this.checked = true;
                    this.indeterminate = false;
                }
            });
            
            // 綁定選項 checkbox 的 change 事件（使用事件委派）
            $optionsContainer.on('change.triStateGroup', 'input[type="checkbox"]', function() {
                self._updateSelectAllState(containerSelector);
            });
            
            // 初始化全選 checkbox 的狀態
            self._updateSelectAllState(containerSelector);
        },

        /**
         * 更新全選 checkbox 的狀態（內部方法）
         * 
         * @param {string} containerSelector - 容器選擇器
         * @private
         */
        _updateSelectAllState: function(containerSelector) {
            var $container = $(containerSelector);
            
            // ✅ 使用專屬 class 選擇元素
            var $selectAllCheckbox = $container.find('.tri-state-group-multiselect__select-all');
            var $checkboxes = $container.find('.tri-state-group-multiselect__main__options input[type="checkbox"]');
            
            if ($selectAllCheckbox.length === 0) {
                return;
            }
            
            var totalCount = $checkboxes.length;
            var checkedCount = $checkboxes.filter(':checked').length;
            
            // 設定全選 checkbox 的狀態：全選 / 部分選取 / 未選
            var checkbox = $selectAllCheckbox[0];
            checkbox.checked = (checkedCount === totalCount);
            checkbox.indeterminate = (checkedCount > 0 && checkedCount < totalCount);
        },

        /**
         * 取得選中的 checkbox 值
         * 
         * @param {string} containerSelector - 容器選擇器
         * @returns {Array} 選中的值陣列
         */
        getSelectedValues: function(containerSelector) {
            var values = [];
            $(containerSelector).find('.tri-state-group-multiselect__main__options input[type="checkbox"]:checked').each(function() {
                values.push($(this).val());
            });
            return values;
        },

        /**
         * 設定選中狀態
         * 
         * @param {string} containerSelector - 容器選擇器
         * @param {Array} values - 要選中的值陣列
         */
        setSelectedValues: function(containerSelector, values) {
            if (!Array.isArray(values)) {
                console.error('Tri-State Group Multiselect: values must be an array');
                return;
            }

            // 先取消所有選中狀態
            $(containerSelector).find('.tri-state-group-multiselect__main__options input[type="checkbox"]').prop('checked', false);

            // 設定指定的選中狀態
            values.forEach(function(value) {
                $(containerSelector).find('.tri-state-group-multiselect__main__options input[type="checkbox"][value="' + value + '"]').prop('checked', true);
            });
        },

        /**
         * 清除所有選中狀態
         * 
         * @param {string} containerSelector - 容器選擇器
         */
        clearSelection: function(containerSelector) {
            $(containerSelector).find('.tri-state-group-multiselect__main__options input[type="checkbox"]').prop('checked', false);
        },

        /**
         * 全選
         * 
         * @param {string} containerSelector - 容器選擇器
         */
        selectAll: function(containerSelector) {
            $(containerSelector).find('.tri-state-group-multiselect__main__options input[type="checkbox"]').prop('checked', true);
        },

        /**
         * 放置錯誤訊息（供 Magento 驗證框架呼叫）
         * 
         * @param {jQuery} $error - 錯誤元素
         * @param {jQuery} $element - 觸發驗證的元素（第一個 checkbox）
         * @returns {boolean} 是否成功放置錯誤訊息
         */
        placeError: function($error, $element) {
            var $triStateGroup = $element.closest('.tri-state-group-multiselect');
            
            if ($triStateGroup.length > 0) {
                var $main = $triStateGroup.find('.tri-state-group-multiselect__main');
                
                // 添加錯誤狀態 class
                $main.addClass('_error');
                
                // 將錯誤訊息放在 tri-state-group-multiselect__main 之後
                $main.after($error);
                return true;
            }
            
            return false; // 找不到容器，返回 false
        },

        /**
         * 移除錯誤狀態（供 Magento 驗證框架呼叫）
         * 
         * @param {jQuery} $element - 觸發驗證的元素（第一個 checkbox）
         * @returns {boolean} 是否成功移除錯誤狀態
         */
        removeError: function($element) {
            var $triStateGroup = $element.closest('.tri-state-group-multiselect');
            
            if ($triStateGroup.length > 0) {
                // 移除錯誤狀態 class
                $triStateGroup.find('.tri-state-group-multiselect__main').removeClass('_error');
                return true;
            }
            
            return false; // 找不到容器，返回 false
        }

    };
});


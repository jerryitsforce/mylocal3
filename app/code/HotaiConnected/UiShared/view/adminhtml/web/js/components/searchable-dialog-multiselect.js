/**
 * HotaiConnected UiShared - Searchable Dialog Multiselect Component
 * 
 * @module HotaiConnected_UiShared/js/components/searchable-dialog-multiselect
 * @version 2.0.0
 * @author HotaiConnected
 * @updated 2025-10-16 - 移至 UiShared 共用模組
 * 
 * 功能說明：
 * - 可搜尋的多選對話框元件（客製化 modal，不使用 Magento confirm）
 * - 支援即時搜尋和篩選
 * - 標籤顯示和移除功能
 * - 三態全選 checkbox（全選/部分選/未選）
 * - 完全由 JavaScript 動態建立，無需模板
 * - 解決 Magento modal 點選選項時往上跳的問題
 * - 整合 Magento 驗證框架（支援 required-entry、自訂錯誤位置）
 * - 支援動態取得/設定選中的選項
 * - 支援程式化清除選擇
 * 
 * 公開 API：
 * - create(config)                        建立可搜尋的多選對話框
 * - getSelectedOptions(containerSelector) 取得選中的選項陣列
 * - setSelectedOptions(containerSelector, options) 設定選中的選項
 * - clearSelection(containerSelector)     清除所有選擇
 * - placeError($error, $element)          自訂錯誤訊息放置位置（驗證框架回調）
 * - removeError($element)                 移除錯誤狀態（驗證框架回調）
 * 
 * 使用範例：
 * ```javascript
 * // 1. 建立可搜尋的多選對話框（使用 id，推薦）
 * searchableDialogMultiselect.create({
 *     containerSelector: '.authorized-dealer-selector',
 *     data: [
 *         { id: 1, key: 'dealer1', value: 'Dealer 1' },
 *         { id: 2, key: 'dealer2', value: 'Dealer 2' }
 *     ],
 *     validationMessage: $.mage.__('Please select at least one authorized dealer.'),
 *     defaultSelected: [],      // 預設選中的項目（可以是 id 或 key）
 *     selectAllOnInit: false,    // 初始化時是否全選（設為 true 會忽略 defaultSelected）
 *     showFilter: true,
 *     showClearButton: false
 * });
 * 
 * // 2. 取得選中的選項（返回 id 陣列）
 * var selected = searchableDialogMultiselect.getSelectedOptions('.authorized-dealer-selector');
 * console.log(selected); // [1, 2] 或 ['dealer1', 'dealer2']（如果沒有 id）
 * 
 * // 3. 程式化設定選中的選項（可以是 id 或 key，會自動轉換）
 * searchableDialogMultiselect.setSelectedOptions('.authorized-dealer-selector', [1, 2]);
 * 
 * // 4. 清除所有選擇
 * searchableDialogMultiselect.clearSelection('.authorized-dealer-selector');
 * ```
 * 
 * 生成的 HTML 結構：
 * ```html
 * <!-- 外部容器 -->
 * <div class="searchable-dialog-multiselect">
 *     <div class="searchable-dialog-multiselect__input-wrapper">
 *         <div class="searchable-dialog-multiselect__display">
 *             <input class="input-text admin__control-text required-entry" />
 *             <!-- mage-error 會插入到這裡 -->
 *         </div>
 *         <button class="searchable-dialog-multiselect__trigger">Choose Options</button>
 *     </div>
 *     <div class="searchable-dialog-multiselect__tags">...</div>
 * </div>
 * 
 * <!-- 客製化 Modal -->
 * <div class="searchable-dialog-multiselect__modal">
 *     <div class="searchable-dialog-multiselect__modal-overlay"></div>
 *     <div class="searchable-dialog-multiselect__modal-wrapper">
 *         <div class="searchable-dialog-multiselect__modal-dialog">
 *             <div class="searchable-dialog-multiselect__modal-header">
 *                 <h2>Select Options</h2>
 *                 <button class="searchable-dialog-multiselect__modal-close">×</button>
 *             </div>
 *             <div class="searchable-dialog-multiselect__modal-content">
 *                 <div class="searchable-dialog-multiselect__header">
 *                     <!-- 搜尋框、篩選、清除按鈕 -->
 *                 </div>
 *                 <div class="searchable-dialog-multiselect__main">
 *                     <div class="searchable-dialog-multiselect__main__action">
 *                         <div class="searchable-dialog-multiselect__main__item">
 *                             <input type="checkbox" class="searchable-dialog-multiselect__select-all" />
 *                             <label>Check All</label>
 *                         </div>
 *                     </div>
 *                     <div class="searchable-dialog-multiselect__main__options">
 *                         <!-- 選項列表 -->
 *                     </div>
 *                 </div>
 *                 <div class="searchable-dialog-multiselect__footer">
 *                     <!-- 選中數量統計 -->
 *                     <span class="searchable-dialog-multiselect__selection-count">0 selected</span>
 *                     <!-- 按鈕區域 -->
 *                     <div class="searchable-dialog-multiselect__footer-actions">
 *                         <button class="action-secondary searchable-dialog-multiselect__modal-cancel">Cancel</button>
 *                         <button class="action-primary searchable-dialog-multiselect__modal-apply">Apply</button>
 *                     </div>
 *                 </div>
 *             </div>
 *         </div>
 *     </div>
 * </div>
 * ```
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/translate: Magento 多語系翻譯功能（$.mage.__）
 * 
 * @author Branch8
 * @version 5.0.0
 * @created 2025-10-09
 * @updated 2025-10-09 - 棄用 Magento_Ui/js/modal/confirm，改為客製化 modal
 * @updated 2025-10-09 - 解決點選選項時整個區塊往上跳的問題
 * @updated 2025-10-09 - 實作客製化 modal 結構和樣式
 * @updated 2025-10-09 - 新增 removeError() API，錯誤消失時移除 _error class
 * @updated 2025-10-09 - placeError() 添加 _error class 到 __display 元素
 * @updated 2025-10-09 - 新增 placeError() API，讓元件自己決定錯誤訊息位置
 * @updated 2025-10-09 - 新增 selectAllOnInit 配置選項，可設定初始時是否全選
 * @updated 2025-10-09 - 全選選項也使用 __main__item 包裹，保持結構一致性
 * @updated 2025-10-09 - 修正 BEM 命名：__option → __main__item（更符合層級結構）
 * @updated 2025-10-09 - 統一資料格式為 { key, value }，與 tri-state-group-multiselect 一致
 * @updated 2025-10-09 - 調整 __display 為包裹 input 的 div，mage-error 放置其中
 * @updated 2025-10-09 - 調整 HTML 結構為 BEM，包含 __header、__main、__footer
 * @updated 2025-10-09 - 保持所有內容由 jQuery 動態建立（無需模板）
 * @updated 2025-10-13 - 補充完整的註解文檔（公開 API、使用範例、功能說明）
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * 建立可搜尋的多選對話框
         * 
         * @param {Object} config - 配置物件
         * @param {string} config.containerSelector - 容器選擇器（必填）
         * @param {Array} config.data - 資料陣列，每個項目需包含 key 和 value（必填）
         *                              - key: 選項的鍵值（必填，用於向後相容）
         *                              - value: 選項的顯示文字（必填）
         *                              - id: 選項的唯一識別符（選填，優先使用，確保唯一性）
         * @param {string} [config.validationMessage] - 驗證錯誤訊息（選填）
         * @param {Array} [config.defaultSelected] - 預設選中的值陣列（選填，可以是 key 或 id）
         * @param {boolean} [config.selectAllOnInit=false] - 初始化時是否全選所有選項（選填）
         * @param {boolean} [config.showFilter=true] - 是否顯示篩選下拉選單（選填）
         * @param {boolean} [config.showClearButton=false] - 是否顯示清除按鈕（選填）
         * 
         * @returns {jQuery} 容器元素
         * 
         * @example
         * // 使用 id（推薦，確保唯一性）
         * searchableDialogMultiselect.create({
         *     containerSelector: '.selector',
         *     data: [
         *         { id: 1, key: 'code1', value: 'Option 1' },
         *         { id: 2, key: 'code2', value: 'Option 2' }
         *     ]
         * });
         * 
         * // 僅使用 key（向後相容）
         * searchableDialogMultiselect.create({
         *     containerSelector: '.selector',
         *     data: [
         *         { key: 'code1', value: 'Option 1' },
         *         { key: 'code2', value: 'Option 2' }
         *     ]
         * });
         */
        create: function(config) {
            // 驗證必填參數
            if (!config.containerSelector) {
                console.error('Searchable Dialog Multiselect: containerSelector is required');
                return null;
            }
            if (!config.data || !Array.isArray(config.data) || config.data.length === 0) {
                console.error('Searchable Dialog Multiselect: data array is required and must not be empty');
                return null;
            }

            // 預設配置
            var defaultConfig = {
                validationMessage: 'Please select at least one option.',
                defaultSelected: [],
                selectAllOnInit: false,
                showFilter: true,
                showClearButton: false
            };

            // 合併配置
            config = $.extend({}, defaultConfig, config);

            console.log('Searchable Dialog Multiselect: Initializing with config:', {
                containerSelector: config.containerSelector,
                dataCount: config.data.length,
                data: config.data
            });

            // 取得容器元素
            var $container = $(config.containerSelector);
            if ($container.length === 0) {
                console.error('Searchable Dialog Multiselect: Container not found - ' + config.containerSelector);
                return null;
            }

            // 清空容器並加上主 class
            $container.empty().addClass('searchable-dialog-multiselect');

            // 處理初始選中狀態
            var initialSelected = config.defaultSelected || [];
            
            // 如果設定為初始全選，則選中所有選項（使用 id 優先）
            if (config.selectAllOnInit) {
                var self = this;
                initialSelected = config.data.map(function(item) {
                    return self._getItemId(item);
                });
            } else if (initialSelected.length > 0) {
                // 轉換 defaultSelected 中的 key 為 id（如果資料有 id）
                var self = this;
                initialSelected = initialSelected.map(function(selectedKey) {
                    var item = config.data.find(function(d) { return d.key === selectedKey; });
                    return item ? self._getItemId(item) : selectedKey;
                });
            }

            // 在容器上儲存配置和狀態
            $container.data('config', config);
            $container.data('selectedOptions', initialSelected);
            
            console.log('Searchable Dialog Multiselect: Config saved to container');

            // 建立輸入框和觸發按鈕
            var $inputWrapper = this._createInputWrapper(config);
            $container.append($inputWrapper);

            // 建立標籤顯示區
            var $tagsContainer = this._createTagsContainer();
            $container.append($tagsContainer);

            // 綁定觸發按鈕
            this._bindTriggerButton(config.containerSelector);

            // 初始化顯示
            this._updateDisplay(config.containerSelector);

            return $container;
        },

        // ==========================================
        // DOM 建立方法（內部方法）
        // ==========================================

        /**
         * 建立輸入框和觸發按鈕區域
         * @private
         */
        _createInputWrapper: function(config) {
            var $wrapper = $('<div>', {
                'class': 'searchable-dialog-multiselect__input-wrapper'
            });

            // 建立輸入框容器（mage-error 會插入到這裡）
            var $displayWrapper = $('<div>', {
                'class': 'searchable-dialog-multiselect__display'
            });

            // 建立輸入框（class 已移到外層 div）
            var $input = $('<input>', {
                'type': 'text',
                'class': 'input-text admin__control-text required-entry',
                'placeholder': $.mage.__('Select options...'),
                'readonly': true
            });
            
            // 單獨設置 autocomplete 和 data 屬性，避免與 jQuery UI 衝突
            $input.attr('autocomplete', 'off');
            $input.attr('data-validate', JSON.stringify({ required: true }));
            $input.attr('data-msg-required', config.validationMessage);

            $displayWrapper.append($input);

            // 建立觸發按鈕
            var $trigger = $('<button>', {
                'type': 'button',
                'class': 'action-secondary searchable-dialog-multiselect__trigger'
            }).append($('<span>', {
                'text': $.mage.__('Choose Options')
            }));

            $wrapper.append($displayWrapper).append($trigger);
            return $wrapper;
        },

        /**
         * 建立標籤顯示容器
         * @private
         */
        _createTagsContainer: function() {
            return $('<div>', {
                'class': 'searchable-dialog-multiselect__tags'
            });
        },

        /**
         * 建立 Modal 內容（傳給 Magento confirm）
         * 遵循 BEM 結構：__header、__main、__footer
         * @private
         */
        _createModalContent: function(config) {
            var $content = $('<div>', {
                'class': 'searchable-dialog-multiselect__modal-content'
            });

            // 建立 Header（搜尋 + 篩選 + 清除）
            var $header = this._createModalHeader(config);
            $content.append($header);

            // 建立 Main（全選 + 選項列表）
            var $main = this._createModalMain(config);
            $content.append($main);

            // 建立 Footer（統計資訊）
            var $footer = this._createModalFooter(config);
            $content.append($footer);

            return $content;
        },

        /**
         * 建立 Modal Header（搜尋控制區）
         * @private
         */
        _createModalHeader: function(config) {
            var $header = $('<div>', {
                'class': 'searchable-dialog-multiselect__header'
            });

            // 建立控制區域（搜尋 + 篩選 + 清除）
            var $controls = this._createControls(config);
            $header.append($controls);

            return $header;
        },

        /**
         * 建立 Modal Main（選項區）
         * @private
         */
        _createModalMain: function(config) {
            var $main = $('<div>', {
                'class': 'searchable-dialog-multiselect__main'
            });

            // 建立全選動作區塊
            var $action = $('<div>', {
                'class': 'searchable-dialog-multiselect__main__action'
            });
            var $selectAllOption = this._createSelectAllOption();
            $action.append($selectAllOption);
            $main.append($action);

            // 建立選項容器
            var $options = $('<div>', {
                'class': 'searchable-dialog-multiselect__main__options'
            });
            $main.append($options);

            return $main;
        },

        /**
         * 建立 Modal Footer（統計區）
         * @private
         */
        _createModalFooter: function(config) {
            var $footer = $('<div>', {
                'class': 'searchable-dialog-multiselect__footer'
            });

            var $stats = $('<span>', {
                'class': 'searchable-dialog-multiselect__selection-count',
                'text': '0 ' + $.mage.__('selected')
            });

            $footer.append($stats);
            return $footer;
        },

        /**
         * 建立控制區域（搜尋 + 篩選 + 清除）
         * @private
         */
        _createControls: function(config) {
            var $controls = $('<div>', {
                'class': 'searchable-dialog-multiselect__controls'
            });

            // 建立搜尋區域
            var $searchWrapper = $('<div>', {
                'class': 'searchable-dialog-multiselect__search-wrapper'
            });

            var $searchInput = $('<input>', {
                'type': 'text',
                'class': 'input-text admin__control-text searchable-dialog-multiselect__search',
                'placeholder': $.mage.__('Search options...')
            });

            var $searchStats = $('<div>', {
                'class': 'searchable-dialog-multiselect__search-stats'
            }).append($('<span>', {
                'class': 'searchable-dialog-multiselect__stats-text',
                'text': config.data.length + ' ' + $.mage.__('options')
            }));

            $searchWrapper.append($searchInput).append($searchStats);
            $controls.append($searchWrapper);

            // 建立篩選下拉選單（可選）
            if (config.showFilter) {
                var $filter = this._createFilterDropdown();
                $controls.append($filter);
            }

            // 建立清除按鈕（可選）
            if (config.showClearButton) {
                var $clearBtn = this._createClearButton();
                $controls.append($clearBtn);
            }

            return $controls;
        },

        /**
         * 建立篩選下拉選單
         * @private
         */
        _createFilterDropdown: function() {
            var $filter = $('<select>', {
                'class': 'select admin__control-select searchable-dialog-multiselect__filter'
            });

            $filter.append([
                $('<option>', { value: 'all', text: $.mage.__('All options') }),
                $('<option>', { value: 'selected', text: $.mage.__('Selected options') }),
                $('<option>', { value: 'unselected', text: $.mage.__('Unselected options') })
            ]);

            return $filter;
        },

        /**
         * 建立清除按鈕
         * @private
         */
        _createClearButton: function() {
            return $('<button>', {
                'type': 'button',
                'class': 'action-secondary searchable-dialog-multiselect__clear',
                'text': $.mage.__('Clear All')
            });
        },

        /**
         * 建立全選選項
         * @private
         */
        _createSelectAllOption: function() {
            var uniqueId = 'select-all-' + Date.now();

            // 使用 __main__item 包裹，與一般選項結構一致
            var $item = $('<div>', {
                'class': 'searchable-dialog-multiselect__main__item'
            });

            var $checkbox = $('<input>', {
                'type': 'checkbox',
                'id': uniqueId,
                'class': 'admin__control-checkbox searchable-dialog-multiselect__select-all'
            });

            var $label = $('<label>', {
                'for': uniqueId,
                'class': 'admin__field-label',
                'text': $.mage.__('Check All')
            });

            $item.append($checkbox).append($label);
            return $item;
        },

        /**
         * 取得項目的唯一識別符（id 優先，否則使用 key）
         * @private
         * @param {Object} item - 資料項目
         * @returns {string|number} 唯一識別符
         */
        _getItemId: function(item) {
            return item.id !== undefined && item.id !== null ? item.id : item.key;
        },

        /**
         * 建立單個選項
         * @private
         */
        _createOption: function(item) {
            var itemId = this._getItemId(item);
            var $option = $('<div>', {
                'class': 'searchable-dialog-multiselect__main__item',
                'data-value': item.key,
                'data-id': itemId
            });

            var optionId = 'option-' + itemId;

            var $checkbox = $('<input>', {
                'type': 'checkbox',
                'id': optionId,
                'class': 'admin__control-checkbox',
                'value': itemId
            });

            var $label = $('<label>', {
                'for': optionId,
                'class': 'admin__field-label',
                'text': item.value
            });

            $option.append($checkbox).append($label);
            return $option;
        },

        /**
         * 建立標籤
         * @private
         * @param {string} label - 標籤顯示文字
         * @param {string|number} id - 唯一識別符（id 優先）
         * @param {string|number} value - 備用識別符（key，向後相容）
         * @param {boolean} isAllSelected - 是否為全選標籤
         */
        _createTag: function(label, id, value, isAllSelected) {
            var $tag = $('<div>', {
                'class': 'searchable-dialog-multiselect__tag' + (isAllSelected ? ' searchable-dialog-multiselect__tag--all' : '')
            });

            $tag.append($('<span>', {
                'class': 'searchable-dialog-multiselect__tag-text',
                'text': label
            }));

            $tag.append($('<a>', {
                'href': '#',
                'class': 'searchable-dialog-multiselect__tag-remove',
                'data-id': id,
                'data-value': value, // 保留以向後相容
                'data-action': isAllSelected ? 'clear-all' : 'remove',
                'text': '×'
            }));

            return $tag;
        },

        // ==========================================
        // 事件綁定方法（內部方法）
        // ==========================================

        /**
         * 綁定觸發按鈕
         * @private
         */
        _bindTriggerButton: function(containerSelector) {
            var self = this;
            var $container = $(containerSelector);

            // 綁定輸入框容器和觸發按鈕點擊
            $container.find('.searchable-dialog-multiselect__display input, .searchable-dialog-multiselect__trigger')
                .on('click.searchableDialog', function() {
                    self._showModal(containerSelector);
                });

            // 綁定標籤移除（使用事件委派，綁定在容器上以確保動態添加的元素也能觸發）
            $container.on('click.searchableDialog', '.searchable-dialog-multiselect__tag-remove', function(e) {
                    e.preventDefault();
                e.stopPropagation();
                    self._removeTag(containerSelector, $(this));
                });
        },

        /**
         * 綁定 Modal 內部事件
         * @private
         */
        _bindModalEvents: function(containerSelector, $modalContent) {
            var self = this;

            // 綁定搜尋輸入
            $modalContent.find('.searchable-dialog-multiselect__search')
                .on('input.searchableDialogModal', function() {
                    self._handleSearch(containerSelector, $modalContent);
                });

            // 綁定篩選下拉選單
            $modalContent.find('.searchable-dialog-multiselect__filter')
                .on('change.searchableDialogModal', function() {
                    self._handleSearch(containerSelector, $modalContent);
                });

            // 綁定全選 checkbox
            $modalContent.find('.searchable-dialog-multiselect__select-all')
                .on('change.searchableDialogModal', function() {
                    self._handleSelectAll(containerSelector, $modalContent);
                });

            // 綁定選項 checkbox（使用事件委派）
            $modalContent.find('.searchable-dialog-multiselect__main__options')
                .on('change.searchableDialogModal', 'input[type="checkbox"]', function() {
                    self._updateSelectAllState(containerSelector, $modalContent);
                    self._updateSelectionCount(containerSelector, $modalContent);
                });

            // 綁定清除按鈕
            $modalContent.find('.searchable-dialog-multiselect__clear')
                .on('click.searchableDialogModal', function() {
                    self._handleClear(containerSelector, $modalContent);
                });
        },

        /**
         * 解綁 Modal 內部事件
         * @private
         */
        _unbindModalEvents: function($modalContent) {
            $modalContent.find('.searchable-dialog-multiselect__search').off('.searchableDialogModal');
            $modalContent.find('.searchable-dialog-multiselect__filter').off('.searchableDialogModal');
            $modalContent.find('.searchable-dialog-multiselect__select-all').off('.searchableDialogModal');
            $modalContent.find('.searchable-dialog-multiselect__main__options').off('.searchableDialogModal');
            $modalContent.find('.searchable-dialog-multiselect__clear').off('.searchableDialogModal');
        },

        // ==========================================
        // Modal 顯示方法
        // ==========================================

        /**
         * 顯示客製化 modal
         * @private
         */
        _showModal: function(containerSelector) {
            var self = this;
            var $container = $(containerSelector);
            var config = $container.data('config');

            console.log('_showModal: Opening modal for', containerSelector);
            console.log('_showModal: Config from container:', config);

            // 建立完整的客製化 modal
            var $modal = this._createCustomModal(config);
            
            // 將 modal 附加到 body
            $('body').append($modal);
            
            // 儲存 modal 元素到容器，供後續使用
            $container.data('currentModal', $modal);

            // 找到 modal 內容區域
            var $modalContent = $modal.find('.searchable-dialog-multiselect__modal-content');

            // 渲染選項
            this._renderOptions(containerSelector, $modalContent);
            this._updateSelectAllState(containerSelector, $modalContent);
            this._updateSelectionCount(containerSelector, $modalContent);
            
            // 綁定事件
            this._bindModalEvents(containerSelector, $modalContent);
            this._bindModalActions(containerSelector, $modal);

            // 顯示 modal（加入動畫效果）
            setTimeout(function() {
                $modal.addClass('_show');
                // 聚焦搜尋框
                $modalContent.find('.searchable-dialog-multiselect__search').focus();
            }, 10);
        },

        /**
         * 關閉客製化 modal
         * @private
         */
        _closeModal: function(containerSelector) {
            var $container = $(containerSelector);
            var $modal = $container.data('currentModal');
            
            if ($modal) {
                var self = this;
                var $modalContent = $modal.find('.searchable-dialog-multiselect__modal-content');
                
                // 移除顯示 class（觸發淡出動畫）
                $modal.removeClass('_show');
                
                // 等待動畫結束後移除 modal
                setTimeout(function() {
                    // 解綁事件
                    self._unbindModalEvents($modalContent);
                    self._unbindModalActions($modal);
                    
                    // 移除 DOM
                    $modal.remove();
                    
                    // 清除儲存的引用
                    $container.removeData('currentModal');
                }, 300); // 配合 CSS transition 時間
            }
        },

        /**
         * 建立完整的客製化 modal
         * @private
         */
        _createCustomModal: function(config) {
            var $modal = $('<div>', {
                'class': 'searchable-dialog-multiselect__modal'
            });

            // 建立遮罩層
            var $overlay = $('<div>', {
                'class': 'searchable-dialog-multiselect__modal-overlay'
            });

            // 建立 modal 包裹層
            var $wrapper = $('<div>', {
                'class': 'searchable-dialog-multiselect__modal-wrapper'
            });

            // 建立 modal 對話框
            var $dialog = $('<div>', {
                'class': 'searchable-dialog-multiselect__modal-dialog'
            });

            // 建立 Header
            var $header = $('<div>', {
                'class': 'searchable-dialog-multiselect__modal-header'
            });
            $header.append($('<h2>', {
                'text': $.mage.__('Select Options')
            }));
            $header.append($('<button>', {
                'type': 'button',
                'class': 'searchable-dialog-multiselect__modal-close',
                'html': '&times;'
            }));

            // 建立內容區（使用原有的 _createModalContent）
            var $content = this._createModalContent(config);

            // 建立 Actions（將會被加入到 footer 中）
            var $actions = $('<div>', {
                'class': 'searchable-dialog-multiselect__footer-actions'
            });
            $actions.append($('<button>', {
                'type': 'button',
                'class': 'action-secondary searchable-dialog-multiselect__modal-cancel',
                'text': $.mage.__('Cancel')
            }));
            $actions.append($('<button>', {
                'type': 'button',
                'class': 'action-primary searchable-dialog-multiselect__modal-apply',
                'text': $.mage.__('Apply')
            }));

            // 將 actions 加入到 footer 尾部
            $content.find('.searchable-dialog-multiselect__footer').append($actions);

            // 組裝結構（不再單獨加入 $actions）
            $dialog.append($header).append($content);
            $wrapper.append($dialog);
            $modal.append($overlay).append($wrapper);

            return $modal;
        },

        /**
         * 綁定 modal 動作按鈕事件
         * @private
         */
        _bindModalActions: function(containerSelector, $modal) {
            var self = this;
            var $container = $(containerSelector);

            // 綁定關閉按鈕
            $modal.find('.searchable-dialog-multiselect__modal-close')
                .on('click.customModal', function() {
                    self._closeModal(containerSelector);
                });

            // 綁定取消按鈕
            $modal.find('.searchable-dialog-multiselect__modal-cancel')
                .on('click.customModal', function() {
                    self._closeModal(containerSelector);
                });

            // 綁定應用按鈕
            $modal.find('.searchable-dialog-multiselect__modal-apply')
                .on('click.customModal', function() {
                    var $modalContent = $modal.find('.searchable-dialog-multiselect__modal-content');
                    self._applySelection(containerSelector, $modalContent);
                    self._closeModal(containerSelector);
                });

            // 綁定遮罩點擊關閉
            $modal.find('.searchable-dialog-multiselect__modal-overlay')
                .on('click.customModal', function() {
                    self._closeModal(containerSelector);
                });

            // 綁定 ESC 鍵關閉
            $(document).on('keydown.customModal', function(e) {
                if (e.keyCode === 27) { // ESC
                    self._closeModal(containerSelector);
                }
            });
        },

        /**
         * 解綁 modal 動作按鈕事件
         * @private
         */
        _unbindModalActions: function($modal) {
            $modal.find('.searchable-dialog-multiselect__modal-close').off('.customModal');
            $modal.find('.searchable-dialog-multiselect__modal-cancel').off('.customModal');
            $modal.find('.searchable-dialog-multiselect__modal-apply').off('.customModal');
            $modal.find('.searchable-dialog-multiselect__modal-overlay').off('.customModal');
            $(document).off('keydown.customModal');
        },

        // ==========================================
        // 事件處理方法（內部方法）
        // ==========================================

        /**
         * 渲染選項列表
         * @private
         */
        _renderOptions: function(containerSelector, $modalContent) {
            console.log('_renderOptions called');
            var $container = $(containerSelector);
            var config = $container.data('config');
            var selectedOptions = $container.data('selectedOptions') || [];
            var $optionsContainer = $modalContent.find('.searchable-dialog-multiselect__main__options');
            
            console.log('_renderOptions called');
            console.log('containerSelector:', containerSelector);
            console.log('config from container:', config);
            console.log('config.data:', config ? config.data : 'config is null/undefined');
            
            // Debug: 確認容器是否找到
            if ($optionsContainer.length === 0) {
                console.error('Options container not found!');
                console.log('modalContent:', $modalContent);
                console.log('Looking for: .searchable-dialog-multiselect__main__options');
                return;
            }
            
            if (!config || !config.data) {
                console.error('Config or config.data is missing!');
                console.log('config:', config);
                return;
            }
            
            console.log('Rendering options, data count:', config.data.length);
            
            $optionsContainer.empty();
            
            config.data.forEach(function(item) {
                var $option = this._createOption(item);
                var $checkbox = $option.find('input[type="checkbox"]');
                var itemId = this._getItemId(item);
                
                // 設定選中狀態（使用 id 優先，否則使用 key 向後相容）
                var isSelected = selectedOptions.indexOf(itemId) !== -1 || 
                                 (item.id === undefined && selectedOptions.indexOf(item.key) !== -1);
                if (isSelected) {
                    $checkbox.prop('checked', true);
                }
                
                $optionsContainer.append($option);
            }.bind(this));
            
            console.log('Options rendered, count:', $optionsContainer.children().length);
        },

        /**
         * 處理搜尋和篩選
         * @private
         */
        _handleSearch: function(containerSelector, $modalContent) {
            var $container = $(containerSelector);
            var searchTerm = $modalContent.find('.searchable-dialog-multiselect__search').val().toLowerCase();
            var filterType = $modalContent.find('.searchable-dialog-multiselect__filter').val() || 'all';
            var $options = $modalContent.find('.searchable-dialog-multiselect__main__options .searchable-dialog-multiselect__main__item');
            var visibleCount = 0;

            $options.each(function() {
                var $option = $(this);
                var $label = $option.find('label');
                var $checkbox = $option.find('input[type="checkbox"]');
                var text = $label.text().toLowerCase();
                var isSelected = $checkbox.is(':checked');
                
                var textMatch = text.indexOf(searchTerm) !== -1;
                var filterMatch = (filterType === 'all') || 
                                  (filterType === 'selected' && isSelected) || 
                                  (filterType === 'unselected' && !isSelected);
                
                if (textMatch && filterMatch) {
                    $option.show();
                    visibleCount++;
                } else {
                    $option.hide();
                }
            });

            // 更新統計
            $modalContent.find('.searchable-dialog-multiselect__stats-text')
                .text($.mage.__('Found %1 options').replace('%1', visibleCount));
        },

        /**
         * 處理全選
         * @private
         */
        _handleSelectAll: function(containerSelector, $modalContent) {
            var $selectAll = $modalContent.find('.searchable-dialog-multiselect__select-all');
            var isChecked = $selectAll.is(':checked');
            
            $modalContent.find('.searchable-dialog-multiselect__main__options input[type="checkbox"]')
                .prop('checked', isChecked);
            
            this._updateSelectionCount(containerSelector, $modalContent);
        },

        /**
         * 更新全選狀態
         * @private
         */
        _updateSelectAllState: function(containerSelector, $modalContent) {
            var $selectAll = $modalContent.find('.searchable-dialog-multiselect__select-all');
            var $checkboxes = $modalContent.find('.searchable-dialog-multiselect__main__options input[type="checkbox"]');
            
            var totalCount = $checkboxes.length;
            var checkedCount = $checkboxes.filter(':checked').length;
            
            var selectAllCheckbox = $selectAll[0];
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = (checkedCount === totalCount && totalCount > 0);
                selectAllCheckbox.indeterminate = (checkedCount > 0 && checkedCount < totalCount);
            }
        },

        /**
         * 更新選中數量
         * @private
         */
        _updateSelectionCount: function(containerSelector, $modalContent) {
            var checkedCount = $modalContent.find('.searchable-dialog-multiselect__main__options input[type="checkbox"]:checked').length;
            
            $modalContent.find('.searchable-dialog-multiselect__selection-count')
                .text($.mage.__('Selected %1').replace('%1', checkedCount));
        },

        /**
         * 處理清除
         * @private
         */
        _handleClear: function(containerSelector, $modalContent) {
            $modalContent.find('.searchable-dialog-multiselect__main__options input[type="checkbox"]')
                .prop('checked', false);
            
            this._updateSelectAllState(containerSelector, $modalContent);
            this._updateSelectionCount(containerSelector, $modalContent);
        },

        /**
         * 應用選擇
         * @private
         */
        _applySelection: function(containerSelector, $modalContent) {
            var self = this;
            var $container = $(containerSelector);
            var config = $container.data('config');
            var selectedOptions = [];
            
            // 收集選中的項目 id（checkbox 的 value 已經是 id）
            $modalContent.find('.searchable-dialog-multiselect__main__options input[type="checkbox"]:checked').each(function() {
                var checkboxValue = $(this).val();
                selectedOptions.push(checkboxValue);
            });
            
            // 儲存選中狀態（統一使用 id）
            $container.data('selectedOptions', selectedOptions);
            
            // 更新顯示
            this._updateDisplay(containerSelector);
        },

        /**
         * 更新顯示（輸入框和標籤）
         * @private
         */
        _updateDisplay: function(containerSelector) {
            var $container = $(containerSelector);
            var config = $container.data('config');
            var selectedOptions = $container.data('selectedOptions') || [];
            var $input = $container.find('.searchable-dialog-multiselect__display input');
            var $tagsContainer = $container.find('.searchable-dialog-multiselect__tags');
            
            $tagsContainer.empty();
            
            if (selectedOptions.length === 0) {
                $input.val('');
            } else {
                $input.val($.mage.__('Selected %1').replace('%1', selectedOptions.length));
                
                // 判斷是否全選
                var isAllSelected = selectedOptions.length === config.data.length;
                
                if (isAllSelected) {
                    // 顯示「全部已選」標籤
                    var $tag = this._createTag($.mage.__('All Selected'), 'all', 'all', true);
                    $tagsContainer.append($tag);
                } else {
                    // 顯示個別標籤
                    selectedOptions.forEach(function(selectedId) {
                        // 優先使用 id 查找，否則使用 key（向後相容）
                        var item = config.data.find(function(d) {
                            var itemId = this._getItemId(d);
                            return String(itemId) === String(selectedId) || 
                                   (d.id === undefined && d.key === selectedId);
                        }.bind(this));
                        if (item) {
                            var itemId = this._getItemId(item);
                            var $tag = this._createTag(item.value, itemId, item.key, false);
                            $tagsContainer.append($tag);
                        }
                    }.bind(this));
                }
            }
            
            // 觸發 change 事件以支援驗證
            $input.trigger('change');
        },

        /**
         * 移除標籤
         * @private
         */
        _removeTag: function(containerSelector, $removeBtn) {
            var $container = $(containerSelector);
            var action = $removeBtn.attr('data-action');
            // 優先使用 data-id，否則使用 data-value（向後相容）
            var id = $removeBtn.attr('data-id') || $removeBtn.attr('data-value');
            var selectedOptions = $container.data('selectedOptions') || [];
            
            if (action === 'clear-all') {
                selectedOptions = [];
            } else {
                // 統一轉換為字符串進行比較，確保類型一致
                var idStr = String(id);
                var index = -1;
                for (var i = 0; i < selectedOptions.length; i++) {
                    if (String(selectedOptions[i]) === idStr) {
                        index = i;
                        break;
                    }
                }
                if (index !== -1) {
                    selectedOptions.splice(index, 1);
                }
            }
            
            $container.data('selectedOptions', selectedOptions);
            this._updateDisplay(containerSelector);
        },

        // ==========================================
        // 公開 API
        // ==========================================

        /**
         * 取得選中的選項
         * 
         * @param {string} containerSelector - 容器選擇器
         * @returns {Array} 選中的值陣列
         */
        getSelectedOptions: function(containerSelector) {
            var $container = $(containerSelector);
            return $container.data('selectedOptions') || [];
        },

        /**
         * 設定選中的選項
         * 
         * @param {string} containerSelector - 容器選擇器
         * @param {Array} options - 要選中的值陣列（可以是 id 或 key，會自動轉換為 id）
         */
        setSelectedOptions: function(containerSelector, options) {
            if (!Array.isArray(options)) {
                console.error('Searchable Dialog Multiselect: options must be an array');
                return;
            }
            
            var $container = $(containerSelector);
            var config = $container.data('config');
            
            // 如果資料有 id，將 key 轉換為 id
            if (config && config.data) {
                var self = this;
                options = options.map(function(option) {
                    // 如果已經是 id（在資料中找到對應的 id），直接返回
                    var itemById = config.data.find(function(d) {
                        return self._getItemId(d) === option;
                    });
                    if (itemById) {
                        return option;
                    }
                    // 否則嘗試作為 key 查找並轉換為 id
                    var itemByKey = config.data.find(function(d) { return d.key === option; });
                    return itemByKey ? self._getItemId(itemByKey) : option;
                });
            }
            
            $container.data('selectedOptions', options);
            this._updateDisplay(containerSelector);
        },

        /**
         * 清除所有選擇
         * 
         * @param {string} containerSelector - 容器選擇器
         */
        clearSelection: function(containerSelector) {
            var $container = $(containerSelector);
            $container.data('selectedOptions', []);
            this._updateDisplay(containerSelector);
        },

        /**
         * 放置錯誤訊息（供 Magento 驗證框架呼叫）
         * 
         * @param {jQuery} $error - 錯誤元素
         * @param {jQuery} $element - 觸發驗證的元素（input）
         * @returns {boolean} 是否成功放置錯誤訊息
         */
        placeError: function($error, $element) {
            var $displayWrapper = $element.closest('.searchable-dialog-multiselect__display');
            
            if ($displayWrapper.length > 0) {
                // 添加錯誤狀態 class
                $displayWrapper.addClass('_error');
                
                // 將錯誤訊息放在 searchable-dialog-multiselect__display 內部的最後
                $displayWrapper.append($error);
                return true;
            }
            
            return false; // 找不到容器，返回 false
        },

        /**
         * 移除錯誤狀態（供 Magento 驗證框架呼叫）
         * 
         * @param {jQuery} $element - 觸發驗證的元素（input）
         * @returns {boolean} 是否成功移除錯誤狀態
         */
        removeError: function($element) {
            var $displayWrapper = $element.closest('.searchable-dialog-multiselect__display');
            
            if ($displayWrapper.length > 0) {
                // 移除錯誤狀態 class
                $displayWrapper.removeClass('_error');
                return true;
            }
            
            return false; // 找不到容器，返回 false
        }
    };
});

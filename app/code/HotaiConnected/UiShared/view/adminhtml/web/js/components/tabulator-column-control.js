/**
 * HotaiConnected UiShared - Tabulator 欄位可見性控制組件
 * 
 * 提供 Tabulator 表格的欄位可見性控制功能（顯示/隱藏）
 * 
 * @module HotaiConnected_UiShared/js/components/tabulator-column-control
 * @version 1.1.0
 * @author HotaiConnected
 * @created 2024-10-23
 * @updated 2024-10-23 - 移除未實作的方法，遵循 YAGNI 原則
 * 
 * @requires jquery
 * @requires mage/translate
 * 
 * ========================================
 * 設計理念
 * ========================================
 * 
 * 1. 單一職責：專注於欄位可見性控制
 * 2. 配置驅動：從 tabulator-config.js 讀取欄位定義
 * 3. YAGNI 原則：只實作當前需要的功能
 * 4. 易用性：簡單的初始化接口
 * 
 * ========================================
 * 使用範例
 * ========================================
 * 
 * @example
 * // 在業務模組的 index.js 中
 * define([
 *     'tabulator',
 *     'tabulatorColumnControl',
 *     'HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config'
 * ], function(Tabulator, columnControl, tabulatorConfig) {
 *     
 *     return {
 *         initialize: function() {
 *             // 初始化 Tabulator
 *             this.tabulatorInstance = new Tabulator('#table', tabulatorConfig.getConfig());
 *             
 *             // 初始化欄位可見性控制
 *             columnControl.initVisibility({
 *                 tabulatorInstance: this.tabulatorInstance,
 *                 columnsConfig: tabulatorConfig.getConfig().columns,
 *                 selectors: {
 *                     toggle: '#filters-visibility__toggle',
 *                     menu: '#filters-visibility__menu',
 *                     columnsCheckboxes: '#filters-visibility__columns-checkboxes',
 *                     stat: '#filters-visibility__stat',
 *                     reset: '#filters-visibility__reset',
 *                     cancel: '#filters-visibility__cancel'
 *                 },
 *                 storageKey: 'my_table_columns_visibility'
 *             });
 *         }
 *     };
 * });
 * 
 * ========================================
 * 配置說明
 * ========================================
 * 
 * columnsConfig 格式（來自 tabulator-config.js）：
 * [
 *     {
 *         field: 'column_name',        // 必要
 *         title: 'Column Title',       // 必要
 *         visible: true,               // 可選（預設 true）
 *         // ... 其他 Tabulator 欄位配置
 *     }
 * ]
 * 
 * visible 屬性說明：
 * - visible: true  → 初始顯示（預設值）
 * - visible: false → 初始隱藏
 * - 未定義 visible → 視為 true（顯示）
 * 
 * ========================================
 * 儲存機制
 * ========================================
 * 
 * 使用者的欄位顯示偏好會儲存到 localStorage：
 * 
 * localStorage.getItem(storageKey) 格式：
 * {
 *     "field1": true,   // 顯示
 *     "field2": false,  // 隱藏
 *     "field3": true
 * }
 * 
 * 優先級：localStorage > columnsConfig.visible > default(true)
 */
define([
    'jquery',
    'mage/translate'
], function($, $t) {
    'use strict';
    
    return {
        
        // ========================================
        // 模組屬性
        // ========================================
        
        /**
         * 當前配置物件
         * @type {Object|null}
         */
        config: null,
        
        /**
         * Tabulator 實例
         * @type {Object|null}
         */
        tabulatorInstance: null,
        
        /**
         * 欄位配置陣列
         * @type {Array}
         */
        columnsConfig: [],
        
        /**
         * DOM 選擇器
         * @type {Object}
         */
        selectors: {},
        
        /**
         * localStorage 鍵名
         * @type {string}
         */
        storageKey: '',
        
        // ========================================
        // 公開方法：欄位可見性控制
        // ========================================
        
        /**
         * 初始化欄位可見性控制
         * 
         * @param {Object} config - 配置物件
         * @param {Object} config.tabulatorInstance - Tabulator 實例（必要）
         * @param {Array} config.columnsConfig - 欄位配置陣列（必要）
         * @param {Object} config.selectors - DOM 選擇器物件（必要）
         * @param {string} config.selectors.toggle - 切換按鈕選擇器
         * @param {string} config.selectors.menu - 下拉選單選擇器
         * @param {string} config.selectors.columnsCheckboxes - Checkbox 容器選擇器
         * @param {string} config.selectors.stat - 統計文字選擇器
         * @param {string} config.selectors.reset - 重置按鈕選擇器
         * @param {string} config.selectors.cancel - 取消按鈕選擇器
         * @param {string} config.storageKey - localStorage 鍵名（必要）
         * 
         * @returns {boolean} 初始化是否成功
         */
        initVisibility: function(config) {
            // 驗證必要參數
            if (!this._validateConfig(config)) {
                return false;
            }
            
            // 儲存配置
            this.config = config;
            this.tabulatorInstance = config.tabulatorInstance;
            this.columnsConfig = config.columnsConfig || [];
            this.selectors = config.selectors;
            this.storageKey = config.storageKey;
            
            // 綁定切換按鈕事件
            this._bindToggleEvents();
            
            // 等待 Tabulator 資料載入後再生成 checkbox 和載入設定
            this.tabulatorInstance.on("dataLoaded", () => {
                this._generateCheckboxes();
                this._loadSavedState();
            });
            
            console.log('✅ TabulatorColumnControl (Visibility) 已初始化');
            return true;
        },
        
        // ========================================
        // 私有方法：配置驗證
        // ========================================
        
        /**
         * 驗證配置參數
         * 
         * @private
         * @param {Object} config - 配置物件
         * @returns {boolean} 驗證是否通過
         */
        _validateConfig: function(config) {
            if (!config) {
                console.error('❌ TabulatorColumnControl: 缺少配置物件');
                return false;
            }
            
            if (!config.tabulatorInstance) {
                console.error('❌ TabulatorColumnControl: 缺少 tabulatorInstance');
                return false;
            }
            
            if (!config.columnsConfig || !Array.isArray(config.columnsConfig)) {
                console.error('❌ TabulatorColumnControl: columnsConfig 必須是陣列');
                return false;
            }
            
            if (!config.selectors) {
                console.error('❌ TabulatorColumnControl: 缺少 selectors 物件');
                return false;
            }
            
            if (!config.storageKey) {
                console.error('❌ TabulatorColumnControl: 缺少 storageKey');
                return false;
            }
            
            return true;
        },
        
        // ========================================
        // 私有方法：事件綁定
        // ========================================
        
        /**
         * 綁定切換按鈕和選單事件
         * 
         * @private
         */
        _bindToggleEvents: function() {
            var self = this;
            var $button = $(self.selectors.toggle);
            var $menu = $(self.selectors.menu);
            var $wrapper = $('.admin__data-grid-action-columns');
            
            // 綁定切換按鈕點擊事件
            $button.off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // 切換選單顯示
                $menu.toggle();
                $wrapper.toggleClass('_active');
            });
            
            // 綁定「取消」按鈕
            $(self.selectors.cancel).off('click').on('click', function(e) {
                e.preventDefault();
                $menu.hide();
                $wrapper.removeClass('_active');
            });
            
            // 綁定「重置」按鈕
            $(self.selectors.reset).off('click').on('click', function(e) {
                e.preventDefault();
                self._resetAllColumns();
            });
            
            // 點擊外部區域關閉選單
            $(document).off('click.columnControl').on('click.columnControl', function(e) {
                if (!$(e.target).closest('.admin__data-grid-action-columns').length) {
                    $menu.hide();
                    $wrapper.removeClass('_active');
                }
            });
        },
        
        // ========================================
        // 私有方法：生成 Checkbox
        // ========================================
        
        /**
         * 動態生成欄位 checkbox（從配置檔案讀取）
         * 
         * @private
         */
        _generateCheckboxes: function() {
            var self = this;
            var $container = $(self.selectors.columnsCheckboxes);
            
            // 清空容器
            $container.empty();
            
            // 過濾可控制的欄位（必須有 field 和 title）
            var controllableColumns = self.columnsConfig.filter(function(column) {
                return column.field && column.title;
            });
            
            if (controllableColumns.length === 0) {
                console.warn('⚠️ TabulatorColumnControl: 沒有可控制的欄位');
                return;
            }
            
            // 計算初始可見數量（根據 visible 屬性）
            var visibleCount = controllableColumns.filter(function(column) {
                return column.visible !== false;  // undefined 或 true 都視為可見
            }).length;
            var totalCount = controllableColumns.length;
            
            // 更新統計文字
            this._updateStats(visibleCount, totalCount);
            
            // 生成 checkbox（使用 Magento 標準樣式）
            controllableColumns.forEach(function(column) {
                var field = column.field;
                var title = column.title;
                var isVisible = column.visible !== false;  // 預設顯示
                var checkboxId = 'column-toggle-' + field;
                
                // 建立 Magento 標準結構
                var $item = $('<div class="admin__field-option"></div>');
                var $checkbox = $('<input class="admin__control-checkbox" type="checkbox">')
                    .attr('id', checkboxId)
                    .attr('data-field', field)
                    .prop('checked', isVisible);
                
                var $label = $('<label class="admin__field-label"></label>')
                    .attr('for', checkboxId)
                    .text(title);
                
                $item.append($checkbox).append($label);
                $container.append($item);
                
                // 根據配置檔案的 visible 屬性設定 Tabulator 欄位顯示/隱藏
                if (isVisible) {
                    self.tabulatorInstance.showColumn(field);
                } else {
                    self.tabulatorInstance.hideColumn(field);
                }
            });
            
            // 綁定 checkbox 變更事件
            this._bindCheckboxEvents($container);
            
            console.log('✅ 欄位控制器已生成（從配置檔案），共 ' + controllableColumns.length + ' 個欄位');
        },
        
        /**
         * 綁定 checkbox 變更事件
         * 
         * @private
         * @param {jQuery} $container - Checkbox 容器
         */
        _bindCheckboxEvents: function($container) {
            var self = this;
            
            $container.find('input[type="checkbox"]').off('change').on('change', function() {
                var field = $(this).data('field');
                var isChecked = $(this).is(':checked');
                
                // 切換欄位顯示/隱藏
                if (isChecked) {
                    self.tabulatorInstance.showColumn(field);
                    console.log('✅ 顯示欄位：' + field);
                } else {
                    self.tabulatorInstance.hideColumn(field);
                    console.log('❌ 隱藏欄位：' + field);
                }
                
                // 更新統計
                var visible = $container.find('input[type="checkbox"]:checked').length;
                var total = $container.find('input[type="checkbox"]').length;
                self._updateStats(visible, total);
                
                // 觸發重繪以確保欄位寬度正確對齊
                requestAnimationFrame(function() {
                    self.tabulatorInstance.redraw(true);
                    console.log('🔄 表格已重新計算寬度');
                });
                
                // 儲存設定
                self._saveState();
            });
        },
        
        // ========================================
        // 私有方法：統計更新
        // ========================================
        
        /**
         * 更新統計文字
         * 
         * @private
         * @param {number} visible - 可見欄位數量
         * @param {number} total - 總欄位數量
         */
        _updateStats: function(visible, total) {
            var $stat = $(this.selectors.stat);
            var statText = $.mage.__('%1 out of %2 visible')
                .replace('%1', visible)
                .replace('%2', total);
            $stat.text(statText);
        },
        
        // ========================================
        // 私有方法：狀態管理
        // ========================================
        
        /**
         * 儲存欄位可見性設定到 localStorage
         * 
         * @private
         */
        _saveState: function() {
            var self = this;
            var $container = $(self.selectors.columnsCheckboxes);
            var visibility = {};
            
            $container.find('input[type="checkbox"]').each(function() {
                var field = $(this).data('field');
                var isVisible = $(this).is(':checked');
                visibility[field] = isVisible;
            });
            
            try {
                localStorage.setItem(self.storageKey, JSON.stringify(visibility));
                console.log('💾 欄位可見性已儲存');
            } catch (e) {
                console.error('❌ 儲存欄位可見性失敗：', e);
            }
        },
        
        /**
         * 從 localStorage 載入欄位可見性設定
         * 
         * @private
         */
        _loadSavedState: function() {
            var self = this;
            var saved = localStorage.getItem(self.storageKey);
            
            if (!saved) {
                console.log('ℹ️ 沒有已儲存的欄位可見性設定');
                return;
            }
            
            try {
                var visibility = JSON.parse(saved);
                var $container = $(self.selectors.columnsCheckboxes);
                
                Object.keys(visibility).forEach(function(field) {
                    var isVisible = visibility[field];
                    var $checkbox = $container.find('[data-field="' + field + '"]');
                    
                    if ($checkbox.length > 0) {
                        // 更新 checkbox 狀態
                        $checkbox.prop('checked', isVisible);
                        
                        // 更新 Tabulator 欄位顯示
                        if (isVisible) {
                            self.tabulatorInstance.showColumn(field);
                        } else {
                            self.tabulatorInstance.hideColumn(field);
                        }
                    }
                });
                
                // 更新統計
                var visible = $container.find('input[type="checkbox"]:checked').length;
                var total = $container.find('input[type="checkbox"]').length;
                self._updateStats(visible, total);
                
                // 重繪表格
                requestAnimationFrame(function() {
                    self.tabulatorInstance.redraw(true);
                });
                
                console.log('✅ 欄位可見性已載入');
            } catch (e) {
                console.error('❌ 載入欄位可見性失敗：', e);
            }
        },
        
        /**
         * 重置所有欄位為可見
         * 
         * @private
         */
        _resetAllColumns: function() {
            var self = this;
            var $container = $(self.selectors.columnsCheckboxes);
            
            // 清除 localStorage
            try {
                localStorage.removeItem(self.storageKey);
            } catch (e) {
                console.error('❌ 清除 localStorage 失敗：', e);
            }
            
            // 顯示所有欄位
            $container.find('input[type="checkbox"]').prop('checked', true).each(function() {
                var field = $(this).data('field');
                self.tabulatorInstance.showColumn(field);
            });
            
            // 更新統計
            var total = $container.find('input[type="checkbox"]').length;
            self._updateStats(total, total);
            
            // 重繪表格
            requestAnimationFrame(function() {
                self.tabulatorInstance.redraw(true);
            });
            
            console.log('🔄 欄位顯示已重置');
        },
    };
});


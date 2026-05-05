/**
 * HotaiConnected MarketplaceCheckout - Exception Authorization Card V5 Renderer
 * 
 * 功能說明：
 * - 渲染例外授權名單 V5 版型（簡易顯示版本）
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/exception-authorization-card/v5
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-12-09
 */
define([
    // Magento 內建功能 (按優先級排序)
    'mage/template',
    
    // 模板檔案
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/exception-authorization-card/v5/header.html',
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/exception-authorization-card/v5/table.html',
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/exception-authorization-card/v5/table-list.html'
], function (
    mageTemplate,
    exceptionCardTemplateV5Header,
    exceptionCardTemplateV5Table,
    exceptionCardTemplateV5TableList
) {
    'use strict';

    return {
        /**
         * 渲染例外授權名單 V5 版型（簡易顯示版本）
         * 
         * @param {Object} rowData - 主記錄資料
         * @param {Array} childData - 例外授權名單資料陣列
         * @returns {string} HTML 字串
         */
        render: function(rowData, childData) {
            var totalCount = Array.isArray(childData) ? childData.length : 0;
            var preparedItems = this._normalizeChildItems(childData);
            
            // 建立表格行 HTML
            var tableRowsHtml = '';
            preparedItems.forEach(function(data) {
                tableRowsHtml += mageTemplate(exceptionCardTemplateV5TableList, data);
            });
            
            // 建立完整 HTML
            var html = '<div class="exception-auth-v5" data-exception-version="v5">';
            html += mageTemplate(exceptionCardTemplateV5Header, {
                totalCount: totalCount
            });
            html += mageTemplate(exceptionCardTemplateV5Table, {
                tableRows: tableRowsHtml
            });
            html += '</div>';

            return html;
        },

        /**
         * 正規化子項目資料
         * 
         * @param {Array} childData - 原始子項目資料
         * @returns {Array} 正規化後的項目陣列
         */
        _normalizeChildItems: function(childData) {
            var self = this;
            var items = Array.isArray(childData) ? childData : [];

            return items
                .filter(function(item) { return !!item; })
                .map(function(item, index) {
                    return self._prepareTemplateData(item, index);
                });
        },

        /**
         * 準備模板資料
         * 
         * @param {Object} item - 原始項目資料
         * @param {number} index - 項目索引
         * @returns {Object} 準備好的模板資料物件
         */
        _prepareTemplateData: function(item, index) {
            var shopTitleDisplay = this._sanitizeDisplayValue(item.shop_title);
            var batchNumDisplay = this._sanitizeDisplayValue(item.batch_num);
            var sellerCodeDisplay = this._sanitizeDisplayValue(item.seller_code);
            var salespersonDisplay = this._sanitizeDisplayValue(item.salesperson_role_name);
            var reasonDisplay = this._sanitizeDisplayValue(item.reason);
            var dataSourceDisplay = this._sanitizeDisplayValue(item.data_source);
            if (dataSourceDisplay === '-') {
                dataSourceDisplay = '例外授權';
            }
            var showValueEntries = this._buildShowValueEntries(item.show_value);
            var createdAtDisplay = this._sanitizeDisplayValue(item.created_at);
            var createdDateDisplay = createdAtDisplay !== '-' && createdAtDisplay.indexOf(' ') !== -1
                ? this._sanitizeDisplayValue(createdAtDisplay.split(' ')[0])
                : createdAtDisplay;
            var statusDisplay = this._sanitizeDisplayValue(item.exception_status);

            return {
                itemIndex: index + 1,
                shop_title: shopTitleDisplay,
                batch_num: batchNumDisplay,
                seller_code: sellerCodeDisplay,
                exception_status: statusDisplay,
                salesperson_role_name: salespersonDisplay,
                reason: reasonDisplay,
                created_date: createdDateDisplay,
                data_source: dataSourceDisplay,
                showValueEntries: showValueEntries
            };
        },

        /**
         * 清理顯示值（轉義 HTML 並處理空值）
         * 
         * @param {*} value - 原始值
         * @returns {string} 清理後的顯示值
         */
        _sanitizeDisplayValue: function(value) {
            if (value === null || value === undefined) {
                return '-';
            }
            if (typeof value === 'number') {
                return this._escapeHtml(value);
            }
            var stringValue = String(value).trim();
            if (stringValue === '') {
                return '-';
            }
            return this._escapeHtml(stringValue);
        },

        /**
         * 轉義 HTML 特殊字元
         * 
         * @param {*} value - 原始值
         * @returns {string} 轉義後的字串
         */
        _escapeHtml: function(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        /**
         * 建立顯示值項目陣列
         * 
         * @param {Object} showValue - 顯示值物件
         * @returns {Array} 顯示值項目陣列
         */
        _buildShowValueEntries: function(showValue) {
            var self = this;
            if (!showValue || typeof showValue !== 'object') {
                return [];
            }

            return Object.keys(showValue).reduce(function(result, key) {
                if (key === null || key === undefined || key === '') {
                    return result;
                }
                var label = self._sanitizeDisplayValue(key);
                var value = self._sanitizeDisplayValue(showValue[key]);
                if (value !== '-') {
                    result.push({
                        label: label,
                        value: value
                    });
                }
                return result;
            }, []);
        }
    };
});


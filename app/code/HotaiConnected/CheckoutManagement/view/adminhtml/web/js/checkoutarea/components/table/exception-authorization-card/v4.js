/**
 * HotaiConnected CheckoutManagement - Exception Authorization Card V4 Renderer
 * 
 * 功能說明：
 * - 渲染例外授權名單 V4 版型
 * - 支援條列/卡片視圖切換
 * - 資料正規化與模板渲染
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/exception-authorization-card/v4
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-12-09
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - mage/template: Magento 模板引擎
 * 
 * 模板檔案
 * - exception-authorization-card-v4-header.html: Header 模板
 * - exception-authorization-card-v4-body.html: Body 容器模板
 * - exception-authorization-card-v4-list.html: 條列視圖項目模板
 * - exception-authorization-card-v4-card.html: 卡片視圖項目模板
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/template',
    
    // 模板檔案
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/exception-authorization-card/v4/header.html',
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/exception-authorization-card/v4/body.html',
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/exception-authorization-card/v4/list.html',
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/exception-authorization-card/v4/card.html'
], function (
    $,
    mageTemplate,
    exceptionCardTemplateV4Header,
    exceptionCardTemplateV4Body,
    exceptionCardTemplateV4List,
    exceptionCardTemplateV4Card
) {
    'use strict';

    return {
        /**
         * 渲染例外授權名單 V4 版型
         * 
         * @param {Object} rowData - 主記錄資料
         * @param {Array} childData - 例外授權名單資料陣列
         * @returns {string} HTML 字串
         */
        render: function(rowData, childData) {
            var templateVersion = 'v4';
            var parentIdDisplay = this._sanitizeDisplayValue(rowData.id || rowData.entity_id);
            var preparedItems = this._normalizeChildItems(childData, templateVersion);
            var html = '<div class="exception-auth-v4" data-exception-version="' + templateVersion + '">';
            html += this._buildNestedHeaderV4(parentIdDisplay, preparedItems.length);
            html += this._renderVariantV4(preparedItems);
            html += '</div>';

            return html;
        },

        /**
         * 正規化子項目資料
         * 
         * @param {Array} childData - 原始子項目資料
         * @param {string} templateVersion - 模板版本
         * @returns {Array} 正規化後的項目陣列
         */
        _normalizeChildItems: function(childData, templateVersion) {
            var self = this;
            var items = Array.isArray(childData) ? childData : [];

            return items
                .filter(function(item) { return !!item; })
                .map(function(item, index) {
                    return self._prepareTemplateData(item, index, templateVersion);
                });
        },

        /**
         * 準備模板資料
         * 
         * @param {Object} item - 原始項目資料
         * @param {number} index - 項目索引
         * @param {string} templateVersion - 模板版本
         * @returns {Object} 準備好的模板資料物件
         */
        _prepareTemplateData: function(item, index, templateVersion) {
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
            var invoiceInfo = this._buildInvoiceDisplay(item.invoice_numbers);
            var createdAtDisplay = this._sanitizeDisplayValue(item.created_at);
            var createdDateDisplay = createdAtDisplay !== '-' && createdAtDisplay.indexOf(' ') !== -1
                ? this._sanitizeDisplayValue(createdAtDisplay.split(' ')[0])
                : createdAtDisplay;
            var statusDisplay = this._sanitizeDisplayValue(item.exception_status);
            var statusClassMap = {
                '審核通過': 'is-approved',
                '拒絕': 'is-rejected',
                '待審核': 'is-pending'
            };

            return {
                itemIndex: index + 1,
                shop_title: shopTitleDisplay,
                batch_num: batchNumDisplay,
                seller_code: sellerCodeDisplay,
                exception_status: statusDisplay,
                salesperson_role_name: salespersonDisplay,
                reason: reasonDisplay,
                created_at: createdAtDisplay,
                created_date: createdDateDisplay,
                settlement_status: this._sanitizeDisplayValue(item.settlement_status),
                data_source: dataSourceDisplay,
                priceFormatted: this._formatNumber(item.price),
                totalPaidFormatted: this._formatNumber(item.total_paid),
                commission_rate: this._sanitizeDisplayValue(item.commission_rate || 0),
                ticketStatusDisplay: this._buildStatusDisplay(item.ticket_status),
                shippingStatusDisplay: this._buildStatusDisplay(item.shipping_status),
                invoiceStatusDisplay: this._buildStatusDisplay(item.invoice_status),
                hasInvoiceNumbers: invoiceInfo.has,
                invoiceNumbersDisplay: invoiceInfo.display,
                showValueEntries: showValueEntries,
                hasShowValueEntries: showValueEntries.length > 0,
                hasReason: reasonDisplay !== '-',
                statusClass: statusClassMap[statusDisplay] || '',
                templateVersion: templateVersion
            };
        },

        /**
         * 渲染 Header
         * 
         * @param {string} parentIdDisplay - 主記錄 ID 顯示值
         * @param {number} totalCount - 總筆數
         * @returns {string} HTML 字串
         */
        _buildNestedHeaderV4: function(parentIdDisplay, totalCount) {
            return mageTemplate(exceptionCardTemplateV4Header, {
                parentIdDisplay: parentIdDisplay,
                totalCount: totalCount
            });
        },

        /**
         * 渲染 Body（包含條列和卡片視圖）
         * 
         * @param {Array} items - 準備好的項目資料陣列
         * @returns {string} HTML 字串
         */
        _renderVariantV4: function(items) {
            var listHtml = '';
            var cardHtml = '';

            items.forEach(function(data) {
                listHtml += mageTemplate(exceptionCardTemplateV4List, data);
                cardHtml += mageTemplate(exceptionCardTemplateV4Card, data);
            });

            return mageTemplate(exceptionCardTemplateV4Body, {
                listItems: listHtml,
                cardItems: cardHtml
            });
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
         * 格式化數字
         * 
         * @param {*} value - 原始數值
         * @returns {string} 格式化後的數字字串
         */
        _formatNumber: function(value) {
            var numberValue = Number(value);
            if (isNaN(numberValue)) {
                return '0';
            }
            return this._escapeHtml(numberValue.toLocaleString());
        },

        /**
         * 建立狀態顯示值
         * 
         * @param {*} status - 原始狀態值
         * @returns {string} 狀態顯示字串
         */
        _buildStatusDisplay: function(status) {
            if (status === null || status === undefined) {
                return '-';
            }
            var statusValue = String(status).trim();
            if (statusValue === '') {
                return '-';
            }
            if (statusValue.toLowerCase() === 'all') {
                return '全部';
            }
            return this._sanitizeDisplayValue(statusValue);
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
        },

        /**
         * 建立發票顯示資訊
         * 
         * @param {*} invoiceNumbers - 發票號碼（字串或陣列）
         * @returns {Object} {has: boolean, display: string}
         */
        _buildInvoiceDisplay: function(invoiceNumbers) {
            if (invoiceNumbers === null || invoiceNumbers === undefined) {
                return {
                    has: false,
                    display: '-'
                };
            }

            var list = Array.isArray(invoiceNumbers)
                ? invoiceNumbers
                : String(invoiceNumbers).split(',');

            var sanitizedList = list
                .map(function(item) { return typeof item === 'string' ? item.trim() : item; })
                .filter(function(item) { return item !== null && item !== undefined && String(item).trim() !== ''; })
                .map(this._sanitizeDisplayValue.bind(this));

            if (sanitizedList.length === 0) {
                return {
                    has: false,
                    display: '-'
                };
            }

            return {
                has: true,
                display: sanitizedList.join(', ')
            };
        },

        /**
         * 初始化 V4 視圖切換事件處理
         * 
         * 功能說明：
         * - 綁定條列/卡片視圖切換按鈕的點擊事件
         * - 處理視圖模式的切換邏輯
         * 
         * 使用方式：
         * exceptionAuthorizationCardV4.initViewToggle();
         */
        initViewToggle: function() {
            $(document).off('click', '.exception-auth-v4__toggle-btn');
            $(document).on('click', '.exception-auth-v4__toggle-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var targetView = $btn.data('view');
                var $container = $btn.closest('.exception-auth-v4');

                if (!$container.length || !targetView) {
                    return;
                }

                var activeClass = 'is-active';
                var listClass = '.exception-auth-v4__view--list';
                var cardClass = '.exception-auth-v4__view--card';

                $btn
                    .addClass('is-selected')
                    .siblings('.exception-auth-v4__toggle-btn')
                    .removeClass('is-selected');

                if (targetView === 'card') {
                    $container.attr('data-view-mode', 'card');
                    $container.find(listClass).removeClass(activeClass);
                    $container.find(cardClass).addClass(activeClass);
                } else {
                    $container.attr('data-view-mode', 'list');
                    $container.find(cardClass).removeClass(activeClass);
                    $container.find(listClass).addClass(activeClass);
                }
            });
        }
    };
});


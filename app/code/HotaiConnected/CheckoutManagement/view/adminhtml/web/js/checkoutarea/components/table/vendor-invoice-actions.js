/**
 * HotaiConnected CheckoutManagement - Vendor Invoice Actions Controller
 * 
 * 功能說明：
 * - 廠商發票號碼欄位操作處理
 * - 渲染廠商發票群組
 * - 確認、新增、刪除按鈕事件處理
 * - 群組索引更新
 * - 發票格式驗證（支援台灣及其他國家格式）
 * - 自訂驗證邏輯（點擊確認時觸發）
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/template: Magento 模板引擎
 * - mage/translate: Magento 翻譯功能
 * - Magento_Ui/js/modal/confirm: Magento 確認彈窗
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiLoadingMask: 全螢幕載入動畫
 * - hotaiToastMessage: 訊息提示工具
 * 
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: API 調用服務
 * 
 * 載入套件 (按優先級排序，同優先級 A-Z)
 * - text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/vendor-invoice-group.html: 廠商發票群組模板
 * 
 * 驗證邏輯：
 * - 台灣格式：2位大寫英文字母 + 8位數字（例如：AB12345678）
 * - 其他國家：2-16位英數字元（例如：INV2024001）
 * - 驗證時機：點擊確認按鈕時
 * - 錯誤顯示：紅框 + 錯誤訊息
 * 
 * @author HotaiConnected
 * @version 2.4.0
 * @updated 2025-10-27 - 重構：改用自訂驗證邏輯，移除 mage/validation 依賴
 * @updated 2025-10-27 - 修改：發票格式改為僅接受大寫字母（[A-Z]{2}\d{8}）
 * @updated 2025-10-27 - 重構：採用 BEM 架構重構 vendor-invoice 元件
 * @updated 2024-10-23 - 整合 API：按下確定時調用 createVendorInvoice API
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/template',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    
    // 共用模組 (來自 UiShared)
    'hotaiLoadingMask',
    'hotaiToastMessage',
    
    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',
    
    // 載入套件 (按優先級排序，同優先級 A-Z)
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/vendor-invoice-group.html'
], function ($, mageTemplate, $t, confirm, loadingMask, toastMessage, api, vendorInvoiceGroupTemplate) {
    'use strict';

    return {
        
        // 渲染廠商發票群組的共用函數
        renderVendorInvoiceGroup: function(index, value, rowId, country) {
            // 有值 = 唯讀模式（無按鈕）
            // 無值 = 可編輯模式（有按鈕）
            var hasValue = value && value.trim() !== '';
            country = country || 'TW'; // 預設為台灣

            var templateData = {
                index: index,
                value: value || '',
                rowId: rowId,
                country: country,
                hasValue: hasValue,
                confirmText: $.mage.__('Confirm'),
                editText: $.mage.__('Edit'),
                cancelText: $.mage.__('Cancel'),
                dataTreeRow: false,
                editable: $('.checkout-area__table').attr('data-invoice-editable') === 'true'
            };
            return mageTemplate(vendorInvoiceGroupTemplate, templateData);
        },
        
        /**
         * 驗證輸入框的值
         * @param {HTMLElement} input - 要驗證的輸入框元素
         * @returns {Object} {isValid: boolean, message: string}
         */
        validateInput: function(input, value) {
            var country = input.getAttribute('data-country') || 'TW';
            
            // 檢查必填
            if (value === '') {
                return {
                    isValid: false,
                    message: $.mage.__('Invoice number cannot be empty')
                };
            }
            
            // 檢查格式
            if (country === 'TW') {
                // 台灣格式：2位大寫英文 + 8位數字
                if (!/^[A-Z]{2}[0-9]{8}$/.test(value)) {
                    return {
                        isValid: false,
                        message: $.mage.__('Taiwan invoice format: 2 uppercase letters + 8 digits')
                    };
                }
            } else {
                // 其他國家格式：2-16位數字或英文
                if (!/^[0-9A-Za-z]{2,16}$/.test(value)) {
                    return {
                        isValid: false,
                        message: $.mage.__('Invoice format: 2-16 alphanumeric characters')
                    };
                }
            }

            return { isValid: true, message: '' };
        },
        
        /**
         * 顯示驗證錯誤訊息
         * @param {HTMLElement} input - 輸入框元素
         * @param {string} message - 錯誤訊息
         */
        showValidationError: function(input, message) {
            var $input = $(input);
            var $group = $input.closest('.vendor-invoice__group');
            
            // 移除舊的錯誤訊息（只移除 div.mage-error，不影響 input）
            $group.children('div.mage-error').remove();
            
            // 添加錯誤樣式到 input
            $group.find('.vendor-invoice__field').addClass('mage-error');
            
            // 在 vendor-invoice__group 的最後添加錯誤訊息
            $group.append('<div class="mage-error" generated="true">' + message + '</div>');
        },
        
        /**
         * 清除驗證錯誤訊息
         * @param {HTMLElement} input - 輸入框元素
         */
        clearValidationError: function(input) {
            var $input = $(input);
            var $group = $input.closest('.vendor-invoice__group');
            
            // 移除錯誤樣式
            $group.find('.vendor-invoice__field').removeClass('mage-error');
            // 移除錯誤訊息（只移除 div.mage-error）
            $group.children('div.mage-error').remove();
        },
        
        /**
         * 確認廠商發票
         * @param {Object} button - 確認按鈕元素
         * @param {Object} rowData - 行資料
         */
        handleVendorInvoiceConfirm: function(button, rowData) {
            var self = this;
            var group = button.closest('.vendor-invoice__group');
            if (!group) {
                return;
            }
            var input = group.querySelector('.vendor-invoice__input');
            if (!input) {
                return;
            }
            var value = String(input.value || '').trim().toUpperCase();

            var type = group.getAttribute('data-state');
            
            // 驗證輸入
            var validation = self.validateInput(input, value);
            if (!validation.isValid) {
                // 顯示驗證錯誤
                self.showValidationError(input, validation.message);
                return;
            }
            
            // 清除可能存在的錯誤訊息
            self.clearValidationError(input);
         
  
            // 調用 API
            loadingMask.show();

            // 新增模式
            if (type === 'addable') {
                var invoices = [value];

                api.createVendorInvoice({
                    id: String(rowData.id || rowData.entity_id),
                    invoices: invoices
                }).done(function(response) {
                    if (!response.success) {
                        // 將 %1 替換為 value，為後續 i18n 使用做準備
                        var i18nTxt = response.message.replace(value, '%1');
                        toastMessage.error({
                            content: $.mage.__(i18nTxt).replace('%1', value),
                            autoClose: true,
                            duration: 5000
                        });
                        return;
                    }
    
                    // 成功後設為唯讀狀態
                    // 透過 data-state 集中控制：input disabled + 隱藏 confirm/minus 按鈕
                    input.disabled = true;
                    group.setAttribute('data-state', 'readonly');
                    
                    toastMessage.success({
                        content: $.mage.__('Invoice added successfully.'),
                        autoClose: true,
                        duration: 5000
                    });
                }).fail(function(error) {
                    console.error('createVendorInvoice error', error);
                    
                    toastMessage.error({
                        content: $.mage.__('Failed to add invoice.'),
                        autoClose: true,
                        duration: 5000
                    });
                }).always(function() {
                    loadingMask.hide();
                });
            }

            // 編輯模式
            if (type === 'editable') {
                var oldValue = input.getAttribute('data-old-value');

                api.updateVendorInvoice({
                    id: String(rowData.id || rowData.entity_id),
                    new_invoice_number: value,
                    old_invoice_number: oldValue
                }).done(function(response) {
                    if (!response.success) {
                        // 將 %1 替換為 value，為後續 i18n 使用做準備
                        var i18nTxt = response.message.replace(value, '%1');
                        toastMessage.error({
                            content: $.mage.__(i18nTxt).replace('%1', value),
                            autoClose: true,
                            duration: 5000
                        });
                        return;
                    }
    
                    // 成功後設為唯讀狀態
                    // 透過 data-state 集中控制：input disabled + 隱藏 confirm/minus 按鈕
                    input.removeAttribute('data-old-value');
                    input.disabled = true;
                    group.setAttribute('data-state', 'readonly');
                    
                    toastMessage.success({
                        content: $.mage.__('Invoice added successfully.'),
                        autoClose: true,
                        duration: 5000
                    });
                }).fail(function(error) {
                    
                    toastMessage.error({
                        content: $.mage.__('Failed to add invoice.'),
                        autoClose: true,
                        duration: 5000
                    });
                }).always(function() {
                    loadingMask.hide();
                });
            } else {
                // type 非 addable/editable 時不呼叫 API，仍須關閉 loading
                loadingMask.hide();
            }
        },

        /**
         * 編輯廠商發票
         * @param {Object} button - 編輯按鈕元素
         * @param {Object} rowData - 行資料
         */
        handleVendorInvoiceEdit: function(button, rowData) {
            var group = button.closest('.vendor-invoice__group');
            if (!group) {
                return;
            }

            var input = group.querySelector('.vendor-invoice__input');
            if (!input) {
                return;
            }

            group.setAttribute('data-state', 'editable');
            input.disabled = false;
            input.setAttribute('data-old-value', input.value);
        },

        /**
         * 取消廠商發票
         * @param {Object} button - 取消按鈕元素
         * @param {Object} rowData - 行資料
         */
        handleVendorInvoiceCancel: function(button, rowData) {
            var self = this;
            var group = button.closest('.vendor-invoice__group');
            if (!group) {
                return;
            }

            var input = group.querySelector('.vendor-invoice__input');
            if (!input) {
                return;
            }

            // 清除可能存在的錯誤訊息
            self.clearValidationError(input);

            input.value = input.getAttribute('data-old-value') || '';
            input.removeAttribute('data-old-value');
            group.setAttribute('data-state', 'readonly');
            input.disabled = true;
        },

        /**
         * 在容器中插入一個新的廠商發票群組（共用方法，供新增按鈕與刪除後補空白用）
         * @param {HTMLElement} container - 容器元素 (.vendor-invoice)
         * @param {Object} rowData - 行資料（需含 id 或 entity_id、country）
         * @param {Number} newIndex - 新群組的 data-index
         * @param {Object} [options] - { focus: boolean } 是否聚焦到新輸入框
         */
        addGroupToContainer: function(container, rowData, newIndex, options) {
            var self = this;
            options = options || {};
            var country = rowData.country || 'TW';
            var rowId = rowData.id || rowData.entity_id;

            var newGroupHtml = self.renderVendorInvoiceGroup(newIndex, '', rowId, country);
            var nextGroup = container.querySelector('.vendor-invoice__group[data-index="' + newIndex + '"]');
            if (nextGroup) {
                nextGroup.insertAdjacentHTML('beforebegin', newGroupHtml);
            } else {
                container.insertAdjacentHTML('beforeend', newGroupHtml);
            }
            self.updateGroupIndexes(container);

            if (options.focus) {
                var newGroup = container.querySelector('.vendor-invoice__group[data-index="' + newIndex + '"]');
                if (newGroup) {
                    var newInput = newGroup.querySelector('.vendor-invoice__input');
                    if (newInput) {
                        newInput.focus();
                    }
                }
            }
        },

        /**
         * 新增廠商發票（由「＋」按鈕觸發）
         * @param {Object} button - 新增按鈕元素
         * @param {Object} rowData - 行資料
         */
        handleVendorInvoicePlus: function(button, rowData) {
            var self = this;
            var index = parseInt(button.getAttribute('data-index'), 10);
            var container = button.closest('.vendor-invoice');
            var newIndex = index + 1;
            self.addGroupToContainer(container, rowData, newIndex, { focus: true });
        },

        /**
         * 刪除廠商發票
         * @param {Object} button - 刪除按鈕元素
         * @param {Object} rowData - 行資料
         */
        handleVendorInvoiceMinus: function(button, rowData) {
            var self = this;
            var index = parseInt(button.getAttribute('data-index'), 10);
            var container = button.closest('.vendor-invoice');
            var group = button.closest('.vendor-invoice__group');
            var input = group.querySelector('.vendor-invoice__input');
            var value = input.value.trim();

            var dataState = group.getAttribute('data-state');

            if (dataState === 'readonly') {
                self.deleteReadOnlyGroup(group, container, rowData);
                return;
            } else {
                if (value !== '') {
                    // 有值時，顯示確認對話框
                    confirm({
                        title: $.mage.__('Confirm Delete'),
                        content: $.mage.__('This field has a value. Are you sure you want to delete it?'),
                        actions: {
                            confirm: function() {
                                self.removeGroup(group, container, rowData);
                            }
                        }
                    });
                } else {
                    // 沒有值時，直接刪除
                    self.removeGroup(group, container, rowData);
                }
            }
        },

        deleteReadOnlyGroup: function(group, container, rowData) {
            var self = this;
            var input = group.querySelector('.vendor-invoice__input');
            
            var params = {
                id: String(rowData.id || rowData.entity_id),
                invoice: input.value
            };

            api.deleteVendorInvoice(params).done(function(response) {

                if (response.success) {
                    // 刪除群組
                    self.removeGroup(group, container, rowData);

                    // 顯示成功訊息
                    toastMessage.success({
                        content: $.mage.__('Invoice deleted successfully.'),
                        autoClose: true,
                        duration: 5000
                    });
                } else {
                    toastMessage.error({
                        content: response.message,
                        autoClose: true,
                        duration: 5000
                    });
                }
            }).fail(function(error) {
                console.error('deleteVendorInvoice error', error);
            }).always(function() {
                loadingMask.hide();
            });
        },

        /**
         * 刪除群組
         * @param {HTMLElement} group - 群組元素
         * @param {HTMLElement} container - 容器元素 (.vendor-invoice)
         * @param {Object} [rowData] - 行資料（剩餘 group 為 0 時需用來插入新群組；呼叫時若可能刪到最後一筆請務必傳入）
         */
        removeGroup: function(group, container, rowData) {
            var self = this;
            // 1. 刪除 DOM
            group.remove();
            // 2. 判斷剩餘 group 數量
            var remaining = container.querySelectorAll('.vendor-invoice__group');
            if (remaining.length > 0) {
                self.updateGroupIndexes(container);
            } else if (remaining.length === 0 && rowData) {
                // 沒有剩餘群組時，呼叫共用方法插入一個新的（index 0）
                self.addGroupToContainer(container, rowData, 0);
            }
        },

        /**
         * 更新群組索引
         * @param {Object} container - 容器元素
         */
        updateGroupIndexes: function(container) {
            var groups = container.querySelectorAll('.vendor-invoice__group');
            groups.forEach((group, newIndex) => {
                group.setAttribute('data-index', newIndex);
                var inputs = group.querySelectorAll('[data-index]');
                inputs.forEach(input => {
                    input.setAttribute('data-index', newIndex);
                });
            });
        }
    };
});


/**
 * HotaiConnected CheckoutManagement - 解鎖結帳批量操作
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/unlock-checkout
 * @version 3.3.0
 * @author HotaiConnected
 * @updated 2024-10-23 - 優化：Toast 訊息改用 <ul><li> 列表顯示店鋪名稱；新增：Toast 訊息顯示店鋪名稱（shop_title）；重構：將 API 調用獨立為 executeUnlockCheckout 方法
 * @updated 2024-10-16 - 改回使用 HTML 模板（維護性優先）
 * 
 * 功能說明：
 * - 執行解除結帳操作
 * - 調用 /rest/V1/reconciliation/delete API
 * - 顯示 Toast 訊息提示（包含店鋪名稱列表，5 秒自動消失）
 * - 重新載入表格資料
 * - 重置批量操作選擇狀態
 * 
 * 主要方法：
 * - executeUnlockCheckout: 執行解除結帳 API 調用（獨立方法）
 * - showUnlockCheckoutDialog: 顯示解除結帳視窗（目前直接調用 executeUnlockCheckout）
 * 
 * 引用檔案：
 * 
 * Magento 內建功能 (按優先級排序)
 * - jquery: DOM 操作和事件處理
 * - mage/template: Magento 模板引擎
 * - mage/translate: Magento 翻譯功能
 * - mage/url: Magento URL 建構器
 * - Magento_Ui/js/modal/alert: Magento 警告彈窗
 * - Magento_Ui/js/modal/confirm: Magento 確認彈窗
 * 
 * 共用模組 (來自 UiShared)
 * - hotaiLoadingMask: 全螢幕載入動畫
 * - hotaiSelectedItemsDisplay: 選中項目顯示組件
 * - hotaiToastMessage: 訊息提示工具（支援 5 秒自動消失）
 * - hotaiDatePickers: 日期選擇器封裝
 * 
 * 業務模組 (本模組)
 * - HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation: API 調用服務（包含 deleteCheckout）
 * 
 * 模板 (本模組)
 * - text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/actions/unlock-checkout.html
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/template',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    
    // 共用模組 (來自 UiShared，按優先級排序，同優先級 A-Z)
    'hotaiLoadingMask',
    'hotaiSelectedItemsDisplay',
    'hotaiToastMessage',
    'hotaiDatePickers',
    
    // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',
    
    // 模板 (按優先級排序，同優先級 A-Z)
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/actions/unlock-checkout.html'
], function ($, mageTemplate, $t, urlBuilder, alert, confirm, loadingMask, selectedItemsDisplay, toastMessage, datePickers, api, unlockCheckoutTemplate) {
    'use strict';

    return {
        /**
         * 執行解除結帳 API 調用
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 id
         * @param {Object} context - 上下文對象，包含 tabulatorInstance 和 resetBatchActionSelect
         */
        executeUnlockCheckout: function(rowData, selectedIds, context) {

            // 提取所有店鋪名稱
            var shopTitles = rowData.map(function(item) {
                return item.shop_title || item.authorized_dealer_name || '';
            }).filter(function(title) {
                return title !== '';
            });
            
            // 格式化店鋪名稱列表（用 <li> 標籤包裹，空數組會返回空字符串）
            var shopTitlesText = shopTitles.length > 0 
                ? '<ul>' + shopTitles.map(function(title) {
                    return '<li>' + title + '</li>';
                }).join('') + '</ul>'
                : '';
            
            loadingMask.show();
            
            api.deleteCheckout({ids: selectedIds}, {
                showSuccessMessage: true,
                showErrorMessage: true,
                successMessage: $.mage.__('%1 successfully.').replace('%1', $.mage.__('Unlock Checkout')),
                errorMessage: $.mage.__('%1 failed.').replace('%1', $.mage.__('Unlock Checkout')),
            }).done(function(response) {
                
                // 重新載入表格資料（保留篩選條件）
                if (context && typeof context.updateTabulator === 'function') {
                    
                    context.updateTabulator();
                } else if (context.tabulatorInstance) {
                    context.tabulatorInstance.setData();
                }
            }).fail(function(response) {
                
            }).always(function(response) {
                loadingMask.hide();
                
                // 重置批量操作選擇
                if (context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        },

        /**
         * 顯示解除結帳視窗（目前直接執行解除結帳）
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 id
         * @param {Object} context - 上下文對象，包含 provider 等資訊
         */
        showUnlockCheckoutDialog: function (rowData, selectedIds, context) {
            var self = this;
            var currentRowData = rowData;

             // 過濾已解除資料，不重複解除
             selectedIds = selectedIds.filter(function(id) {
                return rowData.find(function(item) {
                    return item.id === id && item.settlement_status !== '解除';
                });
            });

            if (selectedIds.length === 0) {
                toastMessage.error({
                    content: $.mage.__('No data available for checkout reversal.'),
                    autoClose: true,
                    duration: 5000
                });
                context.resetBatchActionSelect();
                return;
            }

            // 使用 mageTemplate 渲染模板
            var content = mageTemplate(unlockCheckoutTemplate, {
                $t: $.mage.__
            });

            var displayData = []

            currentRowData.forEach(function(item) {
                // 在模板渲染後，動態添加特約商名稱
                // 轉換業務資料為標準格式
                displayData.push({
                    value: item.shop_title
                });
            });

            confirm({
                modalClass: 'ui-modal-form unlock-checkout',
                title: $.mage.__('Unlock Checkout'),
                content: content,
                buttons: [
                    {
                        text: $.mage.__('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $.mage.__('Confirm'),
                        class: 'action-primary action-accept',
                        click: function () {
                        
                            var unlockCheckoutData = []
                            currentRowData.forEach(function(item) {
                                // 結帳批次
                                if(!unlockCheckoutData.includes(item.batch_num)) {
                                    unlockCheckoutData.push(item.batch_num);
                                }
                            });

                            if(unlockCheckoutData.length > 1) {
                                return;
                            }

                            /*
                            * 資料檢查
                            * magento key: form_key
                            * 結帳批次: checkout_batch
                            * 選中的 id: selected_ids
                            * 開始日期: checkout_date_from
                            * 結束日期: checkout_date_to
                            * 選中的資料: data
                            */
                            const ajaxData = {
                                form_key: $('input[name=form_key]').val(),
                                selected_ids: selectedIds,
                                data: rowData
                            };

                            console.log('ajaxData', ajaxData);

                            // 執行實際的批量操作，包含日期數據
                            // EX: self.deleteCheckout(ajaxData);

                            self.executeUnlockCheckout(rowData, selectedIds, context);
                            
                            // 關閉對話框
                            this.closeModal();
                        }
                    }
                ],
                actions: {
                    always: function() {
                        // 清除表單驗證狀態
                        $('#unlock-checkout-form').removeData('validation-initialized');
                        
                        // 使用 context 中的重置函數
                        if (context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                            context.resetBatchActionSelect();
                        }
                    }
                },
                opened: function () {

                    selectedItemsDisplay.display(displayData, {
                        parentSelector: '.ui-modal-form__item.authorized-dealer-names-display',
                        labelText: $.mage.__('Authorized Dealer Name')
                    });

                    // 設定結帳批次
                    $('.unlock-checkout-data').html(currentRowData[0].batch_num);
                    // 設定訂單結帳序號異動日(起)
                    $('#checkout_date_from').val(currentRowData[0].from.split(' ')[0]);
                    // 設定訂單結帳序號異動日(訖)
                    $('#checkout_date_to').val(currentRowData[0].to.split(' ')[0]);
                }
            });
        }
    };
});

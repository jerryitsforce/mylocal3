/**
 * HotaiConnected CheckoutManagement - 例外授權操作
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/components/table/actions/exception-authorization
 * @version 3.0.0
 * @author HotaiConnected
 * @updated 2024-10-16 - 改回使用 HTML 模板（維護性優先）
 * 
 * 功能說明：
 * - 顯示例外授權對話框
 * - 使用 HTML 模板渲染表單（exception-authorization.html）
 * - 下載範本按鈕
 * - 檔案上傳處理
 * - 原因說明輸入
 * - 結帳批次輸入
 * - 特約商名稱顯示
 * - 表單驗證和 API 提交
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
 * - hotaiSelectedItemsDisplay: 選中項目顯示組件
 * 
 * 模板 (本模組)
 * - text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/actions/exception-authorization.html
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/template',
    'mage/translate',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    
    // 共用模組 (來自 UiShared)
    'hotaiToastMessage',
    
    // 自訂模組 (按優先級排序，同優先級 A-Z)
    'hotaiSelectedItemsDisplay',

     // 業務模組 (本模組)
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation',

    // 模板 (按優先級排序，同優先級 A-Z)
    'text!HotaiConnected_CheckoutManagement/template/checkoutarea/table/actions/exception-authorization.html'
], function ($, mageTemplate, $t, urlBuilder, alert, confirm, toastMessage, selectedItemsDisplay, api, exceptionAuthorizationTemplate) {
    'use strict';

    return {
        /**
         * 顯示例外授權視窗
         * @param {Array} rowData - 選中的資料
         * @param {Array} selectedIds - 選中的 id
         * @param {Object} context - 上下文對象，包含 provider 等資訊
         */
        showExceptionAuthorizationDialog: function (rowData, selectedIds, context) {
            var self = this;
            var toastMessageWarning = '';

            // 例外授權 - 限制規則
            // 1. 只能選擇一筆資料:
            //    |- 因為例外授權是針對單筆資料進行處理，不會有多比使用相同例外授權的狀況，在財務邏輯上不合理
            //    |- 所以在 API 設計上 id 為 number 而非 string or array
            //    |- 此處先檢查用戶是否多選

            // 1. 只能選擇一筆資料
            if(selectedIds.length !== 1){
                toastMessageWarning = $.mage.__('Exception authorization can only select one record at a time.');
            }

            // // 2. 帳單狀態為“建立”才可進行例外授權
            // 2026-04-08 Elaine(PM)說他沒有提過這需求，先註解掉
            // if (rowData[0].settlement_status !== '建立') {
            //     toastMessageWarning = $.mage.__('Exception authorization is only available for bills with \'建立\' status.');
            // }

            // 如果 toastMessageWarning 為 true，則顯示提示訊息，3秒後自動關閉
            if (toastMessageWarning.length > 0) {

                // 顯示提示訊息，3秒後自動關閉
                toastMessage.warning({
                    content: toastMessageWarning,
                    autoClose: true,
                    duration: 5000
                })

                // 使用 context 中的重置函數
                if (context && context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
                return;
            }
            
            // 使用 mageTemplate 渲染模板
            var content = mageTemplate(exceptionAuthorizationTemplate, {
                $t: $.mage.__
            });

            confirm({
                modalClass: 'ui-modal-form exception-authorization',
                title: '<h3 class="ui-modal-form__title">' + $.mage.__('Please upload the file.') + '</h3>' 
                    +  '<span class="ui-modal-form__remark"> *' + $.mage.__('Before authorizing an exception, you must revert the settled batch status.') + '</span>',
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

                            var form = $('#exception-authorization-form');

                            if (!form.valid()) {
                                return;
                            }

                            // 取得選中的 id
                            var recordId = selectedIds[0];
                            // 取得原因
                            var reasonValue = $('#explanation').val() || '';
                            // 取得上傳的檔案
                            var file = $('#file')[0].files[0] || null;

                            // 將檔案轉換為 Base64
                            var reader = new FileReader();
                            reader.onload = function (event) {
                                var base64Content = event.target.result || '';
                                var cleanBase64Content = base64Content.split(',')[1] || base64Content;
                                var formData = {
                                    form_key: $('input[name=form_key]').val(),
                                    id: recordId,
                                    reason: reasonValue,
                                    file: cleanBase64Content
                                };

                                self.executeAjaxAction(formData, context);
                            };

                            reader.onerror = function () {
                                if (toastMessage && typeof toastMessage.error === 'function') {
                                    toastMessage.error($.mage.__('Failed to read the uploaded file.'));
                                }
                            };

                            reader.readAsDataURL(file);
                        }
                    }
                ],
                actions: {
                    always: function() {
                        // 清除表單驗證狀態
                        $('#exception-authorization-form').removeData('validation-initialized');
                        
                        // 使用 context 中的重置函數
                        if (context.resetBatchActionSelect && typeof context.resetBatchActionSelect === 'function') {
                            context.resetBatchActionSelect();
                        }
                    }
                },
                opened: function () {
                    // 在模板渲染後，動態添加特約商名稱
                    // 轉換業務資料為標準格式
                    var displayData = rowData.map(function(item) {
                        return {value: item.shop_title};
                    });
                    selectedItemsDisplay.display(displayData, {
                        parentSelector: '.checkout-popup__item.authorized-dealer-names-display',
                        labelText: $.mage.__('Authorized Dealer Name')
                    });
                    
                    // 添加下載模板按鈕的點擊事件
                    // var templateDownloadRoute = '/media/exception_example.xlsx';
                    // var templateFileName = '例外授權示範檔案.xlsx';
                    // var $downloadButton = $('#download-template-button');

                    // $downloadButton.off('click.popup').on('click.popup', function (event) {
                    //     event.preventDefault();
                    //     var link = document.createElement('a');
                    //     link.href = templateDownloadRoute;
                    //     link.download = templateFileName;

                    //     link.click();
                    //     document.body.removeChild(link);
                    //     // self.downloadTemplateFile(templateDownloadRoute, templateFileName);
                    // });
                    
                    // 初始化表單驗證（避免重複初始化）
                    var form = $('#exception-authorization-form');
                    if (!form.data('validation-initialized')) {
                        form.validation();
                        form.data('validation-initialized', true);
                    }
                }
            });
        },


        /**
         * 執行 AJAX 請求
         * @param {Object} data - 要傳送的資料
         * @param {Object} context - 上下文對象
         */
        executeAjaxAction: function (data, context) {
            var self = this;
            var popup = $('.exception-authorization');
            popup.addClass('disabled');

            api.uploadExceptionAuth(data, {
                showSuccessMessage: false,
                showErrorMessage: false
            }).done(function (response) {
                if (response.success) {
                    toastMessage.success({
                        content: response.message || $.mage.__('%1 successfully.').replace('%1', $.mage.__('Exception Authorization'))
                    });
                } else {
                    toastMessage.error({
                        content: response.message || $.mage.__('%1 failed.').replace('%1', $.mage.__('Exception Authorization'))
                    });
                }
            }).fail(function () {

            }).always(function () {
                popup.removeClass('disabled');
                $('.exception-authorization .action-close').trigger('click');
            });

        },

        /**
         * 下載例外授權模板
         * @param {String} templateRoute
         * @param {String} fileName
         */
        downloadTemplateFile: function (templateRoute, fileName) {
            var templateDownloadUrl = urlBuilder.build(templateRoute);
            var link = document.createElement('a');
            link.href = templateDownloadUrl;
            link.download = fileName;

            link.click();
            document.body.removeChild(link);
        }
    };
});

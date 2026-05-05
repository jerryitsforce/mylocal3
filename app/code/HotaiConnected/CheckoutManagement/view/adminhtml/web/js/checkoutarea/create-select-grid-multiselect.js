/**
 * HotaiConnected CheckoutManagement - 建立月結批次頁主控制器
 * 
 * @module HotaiConnected_CheckoutManagement/js/checkoutarea/create
 * @version 3.0.0
 * @author HotaiConnected
 * @updated 2025-10-16 - 架構重構：移至 checkoutarea/create.js，使用 UiShared 共用資源
 * 
 * 功能說明：
 * - 建立月度批次表單的初始化和驗證
 * - 日期範圍選擇器（訂單結帳號修改日期）
 * - 特約商名稱多選彈窗驗證（使用自訂 multiselect 模組）
 * - 商品物流狀態多選驗證（使用 Magento 內建 validate-one-required-by-name）
 * - 發票狀態多選驗證（使用 Magento 內建 validate-one-required-by-name）
 * - 票券狀態多選驗證（使用 Magento 內建 validate-one-required-by-name）
 * - 結帳批次輸入欄位驗證
 * - 排除已結帳訂單選項
 * - 表單提交前驗證處理
 * - 後台管理訊息顯示（Toast 通知）
 * - 表單資料整理和序列化
 * 
 * 驗證機制：
 * - 日期範圍：必填驗證（required-entry）
 * - 特約商名稱：自訂 multiselect 驗證
 * - 物流狀態：validate-one-required-by-name（至少選一個）
 * - 發票狀態：validate-one-required-by-name（至少選一個）
 * - 票券狀態：validate-one-required-by-name（至少選一個）
 * - 結帳批次：必填驗證（required）
 * 
 * 引用檔案：
 * 
 * Magento 內建功能
 * - jquery: DOM 操作和事件處理
 * - mage/translate: Magento 多語系翻譯功能（$.mage.__）
 * - mage/validation: Magento 表單驗證框架
 * - domReady!: RequireJS 插件，確保 DOM 載入完成後執行
 * 
 * 自訂模組
 * - HotaiConnected_CheckoutManagement/js/utils/searchable-dialog-multiselect: 可搜尋的多選對話框元件
 * - HotaiConnected_CheckoutManagement/js/utils/date-pickers: 日期選擇器工具
 * - HotaiConnected_CheckoutManagement/js/utils/toast-message: Toast 訊息控制器
 * - HotaiConnected_CheckoutManagement/js/utils/tri-state-group-multiselect: 三態群組多選工具
 * - HotaiConnected_CheckoutManagement/js/checkoutarea/ajax/api: Ajax API 請求模組
 * 
 * @author Branch8
 * @version 2.13.0
 * @updated 2025-10-14 - 移除未使用的模組：mage/url 和 loading-mask，優化依賴載入
 * @updated 2025-10-09 - 修正 success callback：element 轉為 jQuery 對象才能使用 hasClass/closest
 * @updated 2025-10-09 - 修正 checkbox 群組驗證：勾選時觸發第一個 checkbox 的驗證
 * @updated 2025-10-09 - success callback 呼叫元件的 removeError() 移除錯誤狀態
 * @updated 2025-10-09 - 兩個元件的 errorPlacement 都改為呼叫 placeError() 方法
 * @updated 2025-10-09 - 統一所有元件資料格式為 { code, zh_name }
 * @updated 2025-10-09 - 調整 searchable-dialog-multiselect 錯誤訊息放在 __display 內部
 * @updated 2025-10-09 - 新增 searchable-dialog-multiselect 的錯誤訊息放置邏輯
 * @updated 2025-10-09 - 移除舊版對比功能，完全使用新版
 * @updated 2025-10-09 - 簡化 initAuthorizedDealerSelector，移除不必要的內部方法
 * @updated 2025-10-09 - 整合 initMultiselectValidator 至 initAuthorizedDealerSelector
 * @updated 2025-10-09 - 統一假資料格式為 { code, zh_name }
 * @updated 2025-10-09 - 重構 searchable-dialog-multiselect 使用 create() API
 * @updated 2025-10-09 - 重命名多選對話框模組為 searchable-dialog-multiselect
 * @updated 2025-10-09 - 更新所有 class 選擇器為 tri-state-group-multiselect
 * @updated 2025-10-09 - 重命名多選模組為 tri-state-group-multiselect
 * @updated 2025-10-09 - 優化 getFormData，統一使用組件 API 取得多選值
 * @updated 2025-10-09 - 重構多選邏輯，使用獨立的三態多選模組
 * @updated 2025-10-09 - 將發票狀態和票券狀態從單選改為多選
 * @updated 2025-10-08 - 簡化物流狀態驗證，改用 validate-one-required-by-name
 */
define([
    // Magento 內建功能 (按優先級排序)
    'jquery',
    'mage/translate',
    'mage/validation',
    'domReady!',
    
    // 自訂模組 (按優先級排序，同優先級 A-Z)
    'hotaiSearchableDialogMultiselect',
    'hotaiDatePickers',
    'hotaiToastMessage',
    'hotaiTriStateGroupMultiselect',
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function ($, $t, validation, domReady, searchableDialogMultiselect, datePickers, toastMessage, triStateGroupMultiselect, api) {
    'use strict';

    return {
        // 預設值
        defaultValues: {
            text: '-',
        },
        // ==========================================
        // 選擇器常數
        // ==========================================
        fromDateSelector: '#order-checkout-number-modification-date-from',
        toDateSelector: '#order-checkout-number-modification-date-to',
        formSelector: '#create_monthly_batch_form',
        multiselectContainerSelector: '.authorized-dealer-selector',
        confirmButtonSelector: '#confirm-submit',
        
        // ==========================================
        // jQuery 快取物件（私有屬性）
        // 注意：_ 前綴表示這是內部屬性，在 initialize() 中初始化為 jQuery 物件
        // 初始化前為 null，初始化後為 jQuery 物件
        // ==========================================
        _form: null,

        // 異步初始化元件狀態
        componentInitFlags: {
            initOrderStatus: false,
            initInvoiceStatus: false,
            initTicketStatus: false,
            initDatePickers: false,
            initMultiselectValidator: false
        },

        // 多選資料物件
        data: {
            authorizedDealer: [],
            orderStatus: [],
            invoiceStatus: [],
            ticketStatus: []
        },
        isAll: 'all',
        backSettings: {
            selector: '.back-seconds',
            seconds: 5
        },

        /**
         * 初始化
         */
        initialize: function () {
            var self = this;
            // 快取 DOM 元素為 jQuery 物件
            self._form = $(self.formSelector);

            self.initStatusData();
            self.initDatePickers();
            // 初始化特約商選擇器
            self.initAuthorizedDealerSelector();

            $('#create_monthly_batch_form.fade-up-initial').addClass('active');

            // 依據文件，當 checkout-batch 從 focus 狀態離開，欄位時進行驗證
            $('input[name="checkout-batch"]').on('blur', function() {
                $(this).valid();
            });
        },

        initStatusData: function() {
            var self = this;
            api.getReconciliationStatusData().done(function(response) {
                self.data.orderStatus = response.order_status;
                self.data.invoiceStatus = response.invoice_status;
                self.data.ticketStatus = response.ticket_status;

                // 初始化商品物流狀態
                self.initOrderStatus(response.order_status);
                // 初始化發票狀態
                self.initInvoiceStatus(response.invoice_status);
                // 初始化票券狀態
                self.initTicketStatus(response.ticket_status);
            }).fail(function(response) {
                console.error('initStatusData 失敗', response);
                $('.product-shipping,.invoice-status,.ticket-status').addClass('fail');
            });
        },

        initTriStateGroupMultiselect: function(setting) {
            var self = this;

            // 使用 tri-state-group-multiselect 模組建立多選群組
            var response = triStateGroupMultiselect.create({
                componentInitFlags: setting.componentInitFlags,
                containerSelector: setting.containerSelector,
                name: setting.name,
                data: setting.data,
                validationMessage: setting.validationMessage,
                checkboxClass: setting.checkboxClass,
                showSelectAll: setting.showSelectAll,
                columns: setting.columns
            });

            if(response) {
                self.componentInitFlags[setting.componentInitFlags] = true;
                $(setting.containerSelector).addClass('active');
                self.initFormValidation();
            } else {
                $(setting.containerSelector).addClass('fail');
            }
        },

        // 初始化商品物流狀態
        initOrderStatus: function(data) {
            var self = this;

            // 轉換資料格式
            var orderStatus = [];
            data.forEach(function(value, key) {
                orderStatus.push({
                    'key': value,
                    'value': value
                });
            });

            self.initTriStateGroupMultiselect({
                componentInitFlags: 'initOrderStatus',
                containerSelector: '.product-shipping',
                name: 'product-shipping-status',
                data: orderStatus,
                validationMessage: $.mage.__('Please select at least one %1.').replace('%1', $.mage.__('Product Shipping Status')),
                checkboxClass: 'product-shipping-status-checkbox',
                showSelectAll: true,
                columns: 3
            });
        },

        // 初始化發票狀態
        initInvoiceStatus: function(data) {
            var self = this;

            // 轉換資料格式
            var invoiceStatus = [];
            data.forEach(function(item) {
                invoiceStatus.push({
                    'key': item.code,
                    'value': item.zh_name
                });
            });

            self.initTriStateGroupMultiselect({
                componentInitFlags: 'initInvoiceStatus',
                containerSelector: '.invoice-status',
                name: 'invoice-status',
                data: invoiceStatus,
                validationMessage: $.mage.__('Please select at least one %1.').replace('%1', $.mage.__('Invoice Status')),
                checkboxClass: 'invoice-status-checkbox',
                showSelectAll: true,
                columns: 2
            });
        },

        // 初始化票券狀態
        initTicketStatus: function(data) {
            var self = this;

            var ticketStatus = [];
            data.forEach(function(item) {
                ticketStatus.push({
                    'key': item.code,
                    'value': item.zh_name
                });
            });

            self.initTriStateGroupMultiselect({
                componentInitFlags: 'initTicketStatus',
                containerSelector: '.ticket-status',
                name: 'ticket-status',
                data: ticketStatus,
                validationMessage: $.mage.__('Please select at least one %1.').replace('%1', $.mage.__('Ticket Status')),
                checkboxClass: 'ticket-status-checkbox',
                showSelectAll: true,
                columns: 2
            });
        },

        /**
         * 初始化日期選擇器
         */
        initDatePickers: function () {
            var self = this;
            datePickers.initDatePickers({
                fromSelector: self.fromDateSelector,
                toSelector: self.toDateSelector,
                enableValidation: true,
                setDefaultValues: false
            });

            self.componentInitFlags.initDatePickers = true;
            self.initFormValidation();
        },

        /**
         * 初始化特約商選擇器
         */
        initAuthorizedDealerSelector: function () {
            var self = this;
            var containerSelector = '.authorized-dealer-selector';

            // TODO: 未來改為實際 API 調用
            api.getReconciliationSellers().done(function(data) {
                if(!Array.isArray(data.items) || data.items.length === 0) {
                    return;
                }

                var authorizedDealerData = [];
                var authorizedDealerOptions = {};
                data.items.forEach(function(item) {

                    /* item 結構: { "seller_id": "", "shop_title": null, "seller_code": "" }
                     * value 本應顯示 shop_title，但部分資料為空字串
                     * 為避免 shop_title 為空字串 or null，使用 filter(Boolean) 取得非空字串的值
                     * 優先級為 shop_title > seller_code > seller_id
                     */
                    authorizedDealerData.push({
                        id: item.seller_id,  // 使用 seller_id 作為唯一識別符
                        key: item.seller_code,
                        value: item.shop_title || item.seller_code || item.seller_id || self.defaultValues.text
                    });

                    authorizedDealerOptions[item.seller_id] = {
                        key: item.seller_code,
                        value: item.shop_title,
                    };
                });

                self.data.authorizedDealer = authorizedDealerOptions;

                // 使用 create() API 建立多選對話框
                // searchable-dialog-multiselect 現已支援 { code, zh_name } 格式
                var response = searchableDialogMultiselect.create({
                    containerSelector: self.multiselectContainerSelector,
                    data: authorizedDealerData,
                    validationMessage: $.mage.__('Please select at least one %1.').replace('%1', $.mage.__('Authorized Dealer Name')),
                    defaultSelected: [],
                    selectAllOnInit: true,
                    showFilter: true,
                    showClearButton: false
                });

                if(response) {
                    self.componentInitFlags.initMultiselectValidator = true;
                    $(containerSelector).addClass('active');
                    self.initFormValidation();
                } else {
                    $(containerSelector).addClass('fail');
                }
            
            }).fail(function(response) {
                console.error('initAuthorizedDealerSelector 失敗', response);
            });;
        },
        
        /**
         * 初始化 Magento 表單驗證
         */
        initFormValidation: function() {
            var self = this;

            // 檢查js動態建立的元素是否都 ready
            if(!Object.values(self.componentInitFlags).every(Boolean)) {
                return;
            }

            // 綁定事件：避免動態新增的元素綁定事件失效，在所有元件都初始化後，才綁定事件
            self.bindEvents()
            
            var classname = 'mage-error';

            self._form.validation({
                errorClass: classname,
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    // console.log('errorPlacement', error, element);
                    var dateFields = [self.fromDateSelector, self.toDateSelector];
                    var elementId = '#' + element.attr('id');
    
                    // 1. 處理日期欄位
                    if ($.inArray(elementId, dateFields) !== -1) {
                        element.parent().append(error);
                    } 
                    // 2. 處理 Searchable Dialog Multiselect
                    else if (element.closest('.searchable-dialog-multiselect__display').length > 0) {
                        // 呼叫元件的 placeError 方法來決定錯誤位置
                        var placed = searchableDialogMultiselect.placeError(error, element);
                        
                        // 降級處理：如果元件無法處理，放在預設位置
                        if (!placed) {
                            error.insertAfter(element);
                        }
                    }
                    // 3. 處理 Tri-State Group Multiselect (validate-one-required-by-name)
                    else if (element.hasClass('validate-one-required-by-name')) {
                        // 呼叫元件的 placeError 方法來決定錯誤位置
                        var placed = triStateGroupMultiselect.placeError(error, element);
                        
                        // 降級處理：如果元件無法處理，放在預設位置
                        if (!placed) {
                            error.insertAfter(element);
                        }
                    }
                    // 4. 其他所有欄位
                    else {
                        error.insertAfter(element);
                    }
                },
                success: function(label, element) {
                    // 移除錯誤訊息
                    label.remove();
                    
                    // 轉換為 jQuery 對象（Magento 傳入的是原生 DOM 元素）
                    var $element = $(element);
                    
                    // 移除元件的錯誤狀態
                    if ($element.hasClass('validate-one-required-by-name')) {
                        triStateGroupMultiselect.removeError($element);
                    } else if ($element.closest('.searchable-dialog-multiselect__display').length > 0) {
                        searchableDialogMultiselect.removeError($element);
                    }
                }
            });
        },

        /**
         * 綁定事件
         */
        bindEvents: function() {
            var self = this;
            $(self.confirmButtonSelector).on('click', function(e) {
                // 手動觸發表單驗證和提交邏輯
                self.submitForm();
            });
        
            // 當使用者在 input/select 欄位上做出以下動作時觸發：
            // - change: 值改變時
            $(self.formSelector).on('change', 'input, select', function() {
                var $element = $(this);
                
                // 特殊處理：如果是 checkbox 群組（非第一個 checkbox）
                if ($element.is(':checkbox') && !$element.hasClass('validate-one-required-by-name')) {
                    var checkboxName = $element.attr('name');
                    
                    // 找到同組的第一個 checkbox（有驗證規則的）
                    var $firstCheckbox = $('input[name="' + checkboxName + '"].validate-one-required-by-name');
                    
                    if ($firstCheckbox.length > 0) {
                        // 觸發第一個 checkbox 的驗證
                        $firstCheckbox.valid();
                        return;
                    }
                }
                
                // 一般欄位：觸發整個表單驗證
                self._form.valid();
            });

        },

        /**
         * 顯示後台管理訊息
         * @param {Object} data - 訊息資料 {code, message, options}
         * @param {string} type - 訊息類型 ('success', 'error', 'warning', 'info')
         */
        showAdminMessage: function(data, type) {
            var self = this;

            let code = data.code;
            let checkoutBatch = {
                value: $('#checkout-batch').val(),
                text: $.mage.__('Checkout Batch')
            }

            // 根據代碼設定訊息內容
            let content = data.message || '';
            switch(code){
                // 欄位必填未填
                case '001':
                    // 『特約商』日期區間已建立，請確認。結帳批次
                    content = `<p>${$.mage.__('The field is required')}</p>`;
                    break;

                case '002':
                    // 找出特約商對應名稱
                    var authorizedDealerNamesArray = [];

                    Object.values(self.data.authorizedDealer).forEach(function(item) {
                        if(data.message.includes(item.key)) {
                            authorizedDealerNamesArray.push(`<li>${item.value}</li>`);
                        }
                    });

                    // 『特約商』日期區間已建立，請確認。結帳批次
                    content = `<p>${checkoutBatch.text} : ${ checkoutBatch.value }</p>`
                            + `<p>${$.mage.__('Authorized Dealer Name')}${$.mage.__('Date range already exists. Please confirm.')}</p>` 
                            + `<ol>${ authorizedDealerNamesArray.join('') }</ol>`;
                    break;

                case '003':
                    // 結帳批次已使用，請確認
                    content = `<p>${checkoutBatch.text} : ${ checkoutBatch.value } ${$.mage.__('Has already been used. Please confirm.')}</p>`;
                    break;

                case '004':
                    // 『特約商』日期區間已建立，請確認。結帳批次
                    content = `<p>${$.mage.__('The format of from/to time is incorrect')}</p>`;
                    break;

                case '005':
                    // 『特約商』日期區間已建立，請確認。結帳批次
                    content = `<p>${$.mage.__('Invalid Authorized Dealer Code')}</p>`;
                    break;
            }

            // 使用 toast 訊息顯示
            switch(type) {
                case 'success':
                    toastMessage.success(content);
                    break;
                case 'error':
                    toastMessage.error(content);
                    break;
                case 'warning':
                    toastMessage.warning(content);
                    break;
                case 'info':
                default:
                    toastMessage.info(content);
                    break;
            }
        },

        /**
         * 取得表單資料
         */
        getFormData: function() {
            var self = this;

            // 取得所有表單欄位資料可以使用 .serializeArray() 方法，這裡因欄位少，所以直接指定較快
            // var formData = self._form.serializeArray();
            
            // 1. 用組件 API 取得特殊欄位
            var authorizedDealer = []
            searchableDialogMultiselect.getSelectedOptions(self.multiselectContainerSelector).forEach(function(id) {
                authorizedDealer.push(self.data.authorizedDealer[id].key);
            });
            
            var productShippingStatus = triStateGroupMultiselect.getSelectedValues('.product-shipping');
            var invoiceStatus = triStateGroupMultiselect.getSelectedValues('.invoice-status');
            var ticketStatus = triStateGroupMultiselect.getSelectedValues('.ticket-status');
            
            // 2. 直接取得一般表單欄位
            var dateFrom = $('[name="order-checkout-number-modification-date-from"]').val();
            var dateTo = $('[name="order-checkout-number-modification-date-to"]').val();
            var checkoutBatch = $('[name="checkout-batch"]').val();
            var excludeCheckedOut = $('[name="exclude-checked-out-orders"]').is(':checked') ? 1 : 0;
            
            // 3. 組合成最終物件（不需要遍歷）
            return {
                // 訂單結帳序號異動日(起) - 加上時間 00:00:00
                from: dateFrom ? `${dateFrom} 00:00:00` : '',
                // 訂單結帳序號異動日(訖) - 加上時間 23:59:59
                to: dateTo ? `${dateTo} 23:59:59` : '',
                // 結帳批次
                batch_num: checkoutBatch || '',
                // 特約商名稱
                seller_code: authorizedDealer.length === self.data.authorizedDealer.length ? self.isAll : authorizedDealer.join(','),
                // 是否排除結帳訂單
                exclude_settled_orders: excludeCheckedOut,
                // 商品物流狀態
                shipping_status: productShippingStatus.length === self.data.orderStatus.length ? self.isAll : productShippingStatus.join(','),
                // 發票狀態
                invoice_status: invoiceStatus.length === self.data.invoiceStatus.length ? self.isAll : invoiceStatus.join(','),
                // 票券狀態
                ticket_status: ticketStatus.length === self.data.ticketStatus.length ? self.isAll : ticketStatus.join(',')    
            };
        },

        back: function() {
            var self = this;

            //  模擬點擊返回按鈕
            setTimeout(function() {
                self.backSettings.seconds--;
                $(self.backSettings.selector).text(self.backSettings.seconds);
                if(self.backSettings.seconds > 0) {
                    self.back();
                } else {
                    $('#back-btn').click();
                }
            }, 1000);
        },

        /**
         * 提交表單
         */
        submitForm: function() {
            var self = this;
            // 執行表單檢查
            var isValid = self._form.valid();
            
            if (isValid) {
                // 移除現有的 toast 訊息
                toastMessage.remove();

                var formDataObject = self.getFormData();

                /*
                 * Ajax 傳送資料格式 - formDataObject
                 * magento key: form_key
                 * 開始日期: order-checkout-number-modification-date-from
                 * 結束日期: order-checkout-number-modification-date-to
                 * 商家名稱: multiselect-display (多選陣列)
                 * 商品物流狀態: product-shipping-status (多選陣列)
                 * 發票狀態: invoice-status (多選陣列)
                 * 票券狀態: ticket-status (多選陣列)
                 * 結帳批次: batch-description
                 * 排除已出貨訂單: exclude-checked-out-orders
                */
                
                api.createMonthlyBatch(formDataObject).done(function(response) {
                    window.scrollTo({top: 0, behavior: 'smooth'});

                    if(!response.success) {
                        self.showAdminMessage(response, 'error');
                        return;
                    }

                    // 成功，顯示成功訊息
                    self.showAdminMessage({
                        code: '',
                        message: '<p>' + $.mage.__('Create Monthly Checkout Batch') + $.mage.__('Success') + '</p>' + '<p><span class="back-seconds">' + self.backSettings.seconds + '</span> 秒後自動返回結帳專區</p>'
                    }, 'success');

                    self.back();
                    

                }).fail(function(response) {
                    var responseData = JSON.parse(response.responseText);
    
                    self.showAdminMessage({
                        code: '',
                        message: responseData.message
                    }, 'error');
                })
            }
        }
    }
});
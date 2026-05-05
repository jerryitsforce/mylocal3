/**
 * Voucher Lookup Component for ticket_7ELEVEN Products
 * 
 * 此組件專門處理 ticket_7ELEVEN 屬性集的 GUID 查詢功能  
 * 提供自動填入產品表單的完整功能
 */
define([
    'jquery',
    'mage/url',
    'mage/translate',
    'plugins/DOMPurify'
], function ($, urlBuilder, $t, DOMPurify) {
    'use strict';

    return {
        /**
         * 初始化 Voucher Lookup 功能
         */
        init: function () {
            this.bindEvents();
            this.initializeDefaultValues();
        },

        /**
         * 為 ticket_7ELEVEN 添加 GUID 查詢按鈕
         */
        addGuidLookupButton: function () {
            var self = this;

            // 檢查按鈕是否已存在
            if ($('#qware-guid-lookup-container').length > 0) {
                return;
            }

            // 等待表單完全載入
            if ($('.page-content .admin__fieldset').length === 0) {
                setTimeout(function () {
                    self.addGuidLookupButton();
                }, 1500);
                return;
            }

            // 多種方式尋找 qware_guid 欄位
            var guidField = $('input[name="product[qware_guid]"]');
            if (guidField.length === 0) {
                guidField = $('input[id*="qware_guid"], input[data-bind*="qware_guid"]');
            }
            if (guidField.length === 0) {
                // 重試直到找到
                if (!this.retryCount) this.retryCount = 0;
                this.retryCount++;
                var delay = Math.min(this.retryCount * 500, 2000);
                setTimeout(function () {
                    self.addGuidLookupButton();
                }, delay);
                return;
            }

            this.retryCount = 0; // 重置重試計數

            // 複製現有欄位結構以確保對齊
            var existingField = guidField.closest('.admin__field');
            var clonedField = existingField.clone();

            // 清空並重新設定內容
            clonedField.attr('id', 'qware-guid-lookup-container');
            clonedField.removeClass().addClass('admin__field field field-qware_lookup');

            // 設定標籤
            var label = clonedField.find('.admin__field-label label span');
            if (label.length) {
                label.text($t('商品查詢'));
            } else {
                clonedField.find('.admin__field-label').html(`
                    <label><span>${$t('商品查詢')}</span></label>
                `);
            }

            // 設定控制項
            clonedField.find('.admin__field-control').html(`
                <button id="voucher-lookup-btn" type="button" class="action-secondary">
                    <span>${$t('查詢商品')}</span>
                </button>
                <div class="admin__field-note" style="margin-top: 8px;">
                    <span>${$t('輸入 GUID 後點擊查詢按鈕，將自動填入商品資訊')}</span>
                </div>
            `);

            // 將複製的欄位插入到原欄位後面
            existingField.after(clonedField);

            // 初始化事件綁定
            this.init();
        },

        /**
         * 綁定事件監聽器
         */
        bindEvents: function () {
            var self = this;

            // 綁定查詢按鈕點擊事件
            $(document).on('click', '#voucher-lookup-btn', function () {
                self.lookupVoucher();
            });

            // 綁定 GUID 欄位 Enter 鍵事件
            $(document).on('keypress', 'input[name="product[qware_guid]"]', function (e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    self.lookupVoucher();
                }
            });

            // 監聽 Qware 日期欄位變化
            $(document).on('change blur', 'input[name="product[qware_sale_start_date]"], input[name="product[qware_sale_end_date]"]', function () {
                self.validateQwareDate($(this));
            });

        },

        /**
         * 展開內容區塊
         */
        openContentSection: function () {
            // 等待一下讓頁面完全載入
            setTimeout(function () {
                // 根據提供的HTML結構尋找內容區塊
                var $contentWrapper = $('.fieldset-wrapper[data-index="content"]');

                if ($contentWrapper.length) {
                    // 尋找標題元素，這裡有 data-state-collapsible 屬性
                    var $title = $contentWrapper.find('.fieldset-wrapper-title[data-state-collapsible]');

                    if ($title.length) {
                        var collapsibleState = $title.attr('data-state-collapsible');

                        if (collapsibleState === 'closed') {
                            // 點擊標題來展開
                            $title.click();
                        }
                    }
                }
            }, 1000); // 增加延遲時間確保頁面完全載入
        },

        /**
         * 執行 Voucher 查詢
         */
        lookupVoucher: function () {
            var guid = $('input[name="product[qware_guid]"]').val().trim();

            if (!guid) {
                this.showMessage($t('請輸入 GUID'), 'error');
                return;
            }

            // 展開內容區塊
            this.openContentSection();

            this.setLoadingState(true);

            var self = this;

            // 偵測環境並構建 API URL
            var hostname = window.location.hostname;
            var currentUrl = window.location.href;
            var apiUrl;

            if (hostname.includes('localhost')) {
                // 從當前 URL 中提取 admin frontName (如 admin_wt8n1mq)
                var urlParts = currentUrl.split('/');
                var indexPhpIndex = urlParts.indexOf('index.php');
                var adminFrontName = urlParts[indexPhpIndex + 1]; // 取得 admin_wt8n1mq
                apiUrl = '/index.php/' + adminFrontName + '/qware/index/getVoucherInfo';

            } else {
                var urlParts = currentUrl.split('/');
                apiUrl = '/' + urlParts[3] + '/qware/index/getVoucherInfo';
            }

            // 獲取 form_key
            var formKey = window.FORM_KEY ||
                $('input[name="form_key"]').val() ||
                $('meta[name="form_key"]').attr('content');

            if (!formKey) {
                this.showMessage($t('無法獲取 form_key，請刷新頁面重試'), 'error');
                return;
            }

            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: {
                    guid: guid,
                    form_key: formKey
                },
                dataType: 'json',
                timeout: 30000,
                xhrFields: {
                    withCredentials: true
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (response) {

                // 檢查是否返回了 HTML 而不是 JSON
                if (typeof response === 'string' && response.indexOf('<!doctype') !== -1) {
                    self.showMessage($t('API 路由錯誤'), 'error');
                    return;
                }

                if (response.success && response.data) {
                    self.fillProductForm(response.data);
                    self.showMessage($t('商品資訊已成功載入！'), 'success');
                } else {
                    var errorMsg = response.message || $t('查詢失敗，請檢查 GUID 是否正確');
                    self.showMessage(errorMsg, 'error');
                }
            }).fail(function (xhr, status, error) {

                var errorMsg = '網路錯誤，請稍後重試';

                if (status === 'timeout') {
                    errorMsg = '請求逾時，請稍後重試';
                } else if (xhr.status === 404) {
                    errorMsg = 'API 路由不存在 (404)';
                } else if (xhr.status === 403) {
                    errorMsg = '權限不足 (403)';
                } else if (xhr.status === 500) {
                    errorMsg = '伺服器錯誤 (500)';
                } else if (xhr.status === 0) {
                    errorMsg = '無法連接到伺服器';
                }

                self.showMessage(errorMsg + ' (狀態碼: ' + xhr.status + ')', 'error');
            }).always(function () {
                self.setLoadingState(false);
            });
        },

        /**
         * 設定 TinyMCE 編輯器內容
         */
        setTinyMCEContent: function (content, fieldName) {
            if (!content) return;

            setTimeout(function () {
                // 嘗試 TinyMCE 編輯器
                var editorIds = ['product_form_description', 'product_form_note'];
                for (var i = 0; i < editorIds.length; i++) {
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorIds[i])) {
                        tinyMCE.get(editorIds[i]).setContent(content);
                        return;
                    }
                }

                // 回退到 textarea
                if (fieldName) {
                    var $field = $('textarea[name="' + fieldName + '"]');
                    if ($field.length) {
                        $field.val(content).trigger('change');
                    }
                }
            }, 2000);
        },

        /**
         * 自動填入產品表單
         */
        fillProductForm: function (data) {
            try {
                // Name商品名稱 => 產品名稱 (name) - 全形轉半形
                var productName = data.name;
                if (productName) {
                    productName = productName.replace(/[\uFF01-\uFF5E]/g, function (match) {
                        return String.fromCharCode(match.charCodeAt(0) - 0xFEE0);
                    }).replace(/\u3000/g, ' ');
                }
                this.setFieldValue('product[name]', productName);

                // 觸發 SKU 自動生成 - 只有在 SKU 欄位未被禁用時才觸發
                setTimeout(function () {
                    var skuField = $('input[name="product[sku]"]');
                    var nameField = $('input[name="product[name]"]');
                    var qwareStartDateField = $('input[name="product[qware_sale_start_date]"]');
                    var qwareEndDateField = $('input[name="product[qware_sale_end_date]"]');

                    // 檢查 SKU 欄位未被禁用
                    if (nameField.length && !skuField.prop('disabled')) {
                        nameField.trigger('keyup');
                    }

                    // 如果 SKU 被禁用，同時禁用 Qware 日期欄位
                    if (skuField.length && qwareStartDateField.length && qwareEndDateField.length) {
                        var isSkuDisabled = skuField.prop('disabled');
                        qwareStartDateField.prop('disabled', isSkuDisabled);
                        qwareEndDateField.prop('disabled', isSkuDisabled);
                    }
                }, 100);

                // ShortName商品簡稱 => 產品名稱-子名稱 (product_name_sub) - 全形轉半形
                var productNameSub = data.short_name;
                if (productNameSub) {
                    productNameSub = productNameSub.replace(/[\uFF01-\uFF5E]/g, function (match) {
                        return String.fromCharCode(match.charCodeAt(0) - 0xFEE0);
                    }).replace(/\u3000/g, ' ');
                }
                this.setFieldValue('product[product_name_sub]', productNameSub);

                // Summary商品簡述 => 產品簡介，遇到斜線等同於換行 (short_description)
                if (data.summary) {
                    var summary = data.summary.replace(/\//g, '\n');
                    this.setFieldValue('product[short_description]', summary);
                }

                // 設定描述內容
                var description = this.buildDescriptionContent(data);
                this.setTinyMCEContent(description, 'product[description]');

                // 設定注意事項內容
                var notes = this.buildNotesContent(data);
                this.setTinyMCEContent(notes, 'product[note]');

                // MaxPurchaseQty單次購買上限 => 如果有值，設定購買限制
                if (data.max_purchase_qty && data.max_purchase_qty > 0) {
                    // 打開 limit_purchased_enable
                    this.setCheckboxValue('product[limit_purchased_enable]', true);

                    // 設定 limit_purchased_qty，若超過5000則設為5000
                    var purchaseQty = data.max_purchase_qty > 5000 ? 5000 : data.max_purchase_qty;
                    this.setFieldValue('product[limit_purchased_qty]', purchaseQty);

                    // 打開 individual_product
                    this.setCheckboxValue('product[individual_product]', true);

                    // 選擇所有客戶群組
                    this.selectAllCustomerGroups();
                }

                // 設定為限純點支付
                this.setFieldValue('product[point_money_config_type]', 2);

                // 設定 visibility 為 4 (Catalog, Search)
                this.setFieldValue('product[visibility]', 4);

                // Tags商品標籤 => 搜尋標籤 (search_tag)
                if (data.tags) {
                    this.setFieldValue('product[search_tag]', data.tags);
                }

                // 庫存設定預設999
                this.setFieldValue('product[stock_data][qty]', 999);
                this.setFieldValue('product[stock_data][is_in_stock]', 1);

                // 設定可銷售數量為99999
                this.setFieldValue('product[salable_qty]', 99999);

                // 設定 Qware 銷售時間 - 用於自動排程（包含日期驗證）
                if (data.for_sale_start_date && data.for_sale_end_date) {
                    // 處理開始和結束日期
                    var startDate = this.formatDate(data.for_sale_start_date, false);
                    var endDate = this.formatDate(data.for_sale_end_date, true);

                    // 驗證日期順序
                    var validatedDates = this.validateDateOrder(startDate, endDate);

                    this.setFieldValue('product[qware_sale_start_date]', validatedDates.startDate);
                    this.setFieldValue('product[qware_sale_end_date]', validatedDates.endDate);

                    // 使用 Alert Dialog 顯示日期處理結果
                    require(['Magento_Ui/js/modal/alert'], function (alert) {
                        var alertContent =
                            '原始銷售開始日期：' + DOMPurify.sanitize(data.for_sale_start_date) + '<br>' +
                            '原始銷售結束日期：' + DOMPurify.sanitize(data.for_sale_end_date) + '<br>' +
                            '修改後銷售開始日期：' + DOMPurify.sanitize(validatedDates.startDate) + '<br>' +
                            '修改後銷售結束日期：' + DOMPurify.sanitize(validatedDates.endDate);

                        alert({
                            title: 'Qware 銷售日期修改',
                            content: alertContent
                        });
                    });
                }

            } catch (error) {
                console.error('Error in fillProductForm:', error);
                this.showMessage($t('填入表單時發生錯誤'), 'error');
            }
        },

        /**
         * 解碼HTML實體
         */
        decodeHtmlEntities: function (text) {
            if (!text) return text;

            var textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            var decoded = textarea.value;

            // 移除 <br /> 標籤
            decoded = decoded.replace(/<br\s*\/?>/gi, '\n');

            return decoded;
        },

        /**
         * 建立描述內容 (Description商品說明 + Specification票券注意事項)
         */
        buildDescriptionContent: function (data) {
            var description = '';

            // Description商品說明 => 描述
            if (data.description) {
                var cleanDescription = this.cleanHtmlContent(data.description);
                description += '<h3>商品說明</h3>\n' + cleanDescription + '\n\n';
            }

            // Specification票券注意事項 => 描述
            if (data.specification) {
                var cleanSpecification = this.cleanHtmlContent(data.specification);
                description += '<h3>票券注意事項</h3>\n' + cleanSpecification + '\n\n';
            }

            return description;
        },

        /**
         * 建立注意事項內容 (Feature購回注意事項 + Notes票券使用說明)
         */
        buildNotesContent: function (data) {
            var notes = '';

            // Feature購回注意事項 => 注意事項
            if (data.feature) {
                var cleanFeature = this.cleanHtmlContent(data.feature);
                notes += '<h3>購回注意事項</h3>\n' + cleanFeature + '\n\n';
            }

            // Notes票券使用說明 => 注意事項
            if (data.notes) {
                var cleanNotes = this.cleanHtmlContent(data.notes);
                notes += '<h3>票券使用說明</h3>\n' + cleanNotes + '\n\n';
            }

            return notes;
        },

        /**
         * 清理HTML內容，轉換為適合TinyMCE的格式
         */
        cleanHtmlContent: function (htmlContent) {
            if (!htmlContent) return '';

            // 建立臨時元素解碼HTML實體
            var textarea = document.createElement('textarea');
            textarea.innerHTML = htmlContent;
            var decoded = textarea.value;

            // 清理和轉換HTML標籤
            var cleaned = decoded
                // 移除或轉換HTML標籤
                .replace(/<p[^>]*>/gi, '')                    // 移除 <p> 開始標籤
                .replace(/<\/p>/gi, '\n')                     // </p> 轉換為換行
                .replace(/<br\s*\/?>/gi, '\n')               // <br> 轉換為換行
                .replace(/<div[^>]*>/gi, '')                 // 移除 <div> 開始標籤
                .replace(/<\/div>/gi, '\n')                  // </div> 轉換為換行
                .replace(/<span[^>]*>/gi, '')                // 移除 <span> 開始標籤
                .replace(/<\/span>/gi, '')                   // 移除 </span> 結束標籤

                // 處理特殊字符和實體
                .replace(/&nbsp;/gi, ' ')                    // &nbsp; 轉換為空格
                .replace(/&amp;/gi, '&')                     // &amp; 轉換為 &
                .replace(/&lt;/gi, '<')                      // &lt; 轉換為 <
                .replace(/&gt;/gi, '>')                      // &gt; 轉換為 >
                .replace(/&quot;/gi, '"')                    // &quot; 轉換為 "
                .replace(/&#39;/gi, "'")                     // &#39; 轉換為 '

                // 清理數字標籤 <1>, <2> 等
                .replace(/&lt;(\d+)&gt;/gi, '$1. ')         // &lt;1&gt; 轉換為 "1. "
                .replace(/<(\d+)>/gi, '$1. ')                // <1> 轉換為 "1. "

                // 清理多餘的換行和空格
                .replace(/\n\s*\n\s*\n/g, '\n\n')           // 多個換行合併為兩個
                .replace(/[ \t]+/g, ' ')                     // 多個空格合併為一個
                .replace(/^\s+/gm, '')                       // 移除行首空格
                .replace(/\s+$/gm, '')                       // 移除行尾空格
                .trim();                                     // 移除首尾空格

            return cleaned;
        },


        /**
         * 設定一般欄位值
         */
        setFieldValue: function (fieldName, value) {
            if (value === null || value === undefined) return;

            var field = $('input[name="' + fieldName + '"], textarea[name="' + fieldName + '"], select[name="' + fieldName + '"]');
            if (field.length) {
                field.val(value).trigger('change');
            }
        },

        /**
         * 設定 Checkbox 值
         */
        setCheckboxValue: function (fieldName, checked) {
            // 尋找標準 checkbox
            var $checkbox = $('input[type="checkbox"][name="' + fieldName + '"]');
            if ($checkbox.length) {
                $checkbox.prop('checked', checked).trigger('change');
                return;
            }

            // 尋找一般 input 欄位
            var $input = $('input[name="' + fieldName + '"]');
            if ($input.length) {
                $input.val(checked ? 1 : 0).trigger('change');
                return;
            }
        },

        /**
         * 設定 Toggle Switch 值 (Magento Admin UI)
         */
        setToggleSwitchValue: function (fieldName, checked) {
            // 尋找 toggle switch 的隱藏欄位
            var hiddenField = $('input[type="hidden"][name="' + fieldName + '"]');
            if (hiddenField.length) {
                hiddenField.val(checked ? 1 : 0).trigger('change');
            }

            // 更新 toggle switch 的顯示狀態
            var toggleLabel = hiddenField.closest('.admin__field').find('.admin__actions-switch-label');
            var toggleText = toggleLabel.find('.admin__actions-switch-text');

            if (toggleText.length) {
                if (checked) {
                    toggleText.addClass('admin__actions-switch-text').attr('data-text-checked', 'true');
                } else {
                    toggleText.removeClass('admin__actions-switch-text').attr('data-text-checked', 'false');
                }
            }

        },

        /**
         * 選擇所有客戶群組
         */
        selectAllCustomerGroups: function () {
            var selectField = $('select[name="product[limit_purchased_customer_group]"]');
            if (selectField.length) {
                // 選擇所有選項
                selectField.find('option').prop('selected', true);
                selectField.trigger('change');
            }
        },

        /**
         * 格式化日期為 Magento Admin UI 的格式，並進行日期驗證
         */
        formatDate: function (dateString, isEndDate = false) {
            if (!dateString) return '';

            try {
                var inputDate = new Date(dateString);
                var today = new Date();
                today.setHours(0, 0, 0, 0);  // 設定為今日 00:00:00

                var processedDate;

                if (isEndDate) {
                    // 結束日期：若小於等於今日則帶入一年後
                    if (inputDate <= today) {
                        processedDate = new Date();
                        processedDate.setFullYear(processedDate.getFullYear() + 1);
                    } else {
                        processedDate = inputDate;
                    }
                } else {
                    // 開始日期：若小於等於今日則帶入明日
                    if (inputDate <= today) {
                        processedDate = new Date();
                        processedDate.setDate(processedDate.getDate() + 1);
                    } else {
                        processedDate = inputDate;
                    }
                }

                var year = processedDate.getFullYear();
                var month = ('0' + (processedDate.getMonth() + 1)).slice(-2);  // 補零
                var day = processedDate.getDate();         // 不補零

                return year + '/' + month + '/' + day;
            } catch (error) {
                return '';
            }
        },

        /**
         * 驗證並調整日期順序
         */
        validateDateOrder: function (startDateString, endDateString) {
            try {
                var startDate = new Date(startDateString);
                var endDate = new Date(endDateString);

                // 如果結束日期不大於開始日期，將結束日期設為開始日期後一年
                if (endDate <= startDate) {
                    endDate = new Date(startDate);
                    endDate.setFullYear(endDate.getFullYear() + 1);
                    return {
                        startDate: startDateString,
                        endDate: this.formatDate(endDate.toISOString(), true)
                    };
                }

                return {
                    startDate: startDateString,
                    endDate: endDateString
                };
            } catch (error) {
                console.error('[validateDateOrder] Error:', error);
                return {
                    startDate: startDateString,
                    endDate: endDateString
                };
            }
        },

        /**
         * 初始化預設值
         */
        initializeDefaultValues: function () {
            // 不再需要設定預設值，只在查詢時填入
        },

        /**
         * 設定載入狀態
         */
        setLoadingState: function (loading) {
            var btn = $('#voucher-lookup-btn');
            var input = $('input[name="product[qware_guid]"]');

            if (loading) {
                btn.prop('disabled', true).find('span').text($t('查詢中...'));
                input.prop('disabled', true);
            } else {
                btn.prop('disabled', false).find('span').text($t('查詢商品'));
                input.prop('disabled', false);
            }
        },

        /**
         * 驗證 Qware 日期欄位
         */
        validateQwareDate: function (field) {
            var inputValue = field.val();
            if (!inputValue) return;

            try {
                var selectedDate = new Date(inputValue);
                var today = new Date();
                today.setHours(0, 0, 0, 0); // 設定為今日開始時間
                selectedDate.setHours(0, 0, 0, 0); // 設定為選擇日期開始時間

                if (selectedDate < today) {
                    var fieldName = field.attr('name');
                    var message = '';

                    if (fieldName === 'product[qware_sale_start_date]') {
                        message = 'Qware 銷售開始日期不能小於今日';
                    } else if (fieldName === 'product[qware_sale_end_date]') {
                        message = 'Qware 銷售結束日期不能小於今日';
                    }

                    // 清空欄位並顯示訊息
                    field.val('');
                    field.trigger('change'); // 觸發變更事件以更新 KnockoutJS

                    // 使用 Alert Dialog 顯示錯誤訊息
                    require(['Magento_Ui/js/modal/alert'], function (alert) {
                        alert({
                            title: 'Qware 日期驗證錯誤',
                            content: message
                        });
                    });
                }
            } catch (e) {
                // 忽略無效日期格式錯誤
                console.log('Invalid date format:', inputValue);
            }
        },

        /**
         * 顯示訊息
         */
        showMessage: function (message, type) {
            type = type || 'info';

            var backgroundColor = type === 'error' ? '#ffebee' : (type === 'success' ? '#e8f5e8' : '#e3f2fd');
            var borderColor = type === 'error' ? '#e57373' : (type === 'success' ? '#81c784' : '#64b5f6');
            var textColor = type === 'error' ? '#c62828' : (type === 'success' ? '#2e7d32' : '#1976d2');

            var $message = $('<div class="voucher-message" style="margin: 12px 0 8px 12px; padding: 8px 12px; border-radius: 3px; background: ' + backgroundColor + '; border: 1px solid ' + borderColor + '; color: ' + textColor + '; font-size: 12px; width: auto; max-width: 400px; display: inline-block; word-wrap: break-word; position: relative; z-index: 1000;">' + DOMPurify.sanitize(message) + '</div>');

            // 優先尋找查詢按鈕，在按鈕後面插入訊息
            var $button = $('#voucher-lookup-btn');
            var $insertTarget = null;

            if ($button.length) {
                // 在查詢按鈕後面插入
                $insertTarget = $button;
            } else {
                // 回退：尋找查詢按鈕容器
                var $container = $('#qware-guid-lookup-container');
                if ($container.length === 0) {
                    $container = $('input[name="product[qware_guid]"]').closest('.admin__field');
                }
                $insertTarget = $container;
            }

            if ($insertTarget && $insertTarget.length > 0) {
                // 移除舊的訊息
                $('.voucher-message').remove();

                // 在目標元素後面插入訊息
                $insertTarget.after($message);

                // 自動移除訊息
                var timeout = type === 'error' ? 8000 : 3000;
                setTimeout(function () {
                    $message.fadeOut(300, function () {
                        $(this).remove();
                    });
                }, timeout);
            } else {
                // 備用方案：使用alert
                alert('[' + type.toUpperCase() + '] ' + message);
            }
        }
    };
});
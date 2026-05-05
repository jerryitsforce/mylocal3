/**
 * Marketplace Voucher Lookup Component for ticket_7ELEVEN Products
 *
 * 此組件專門處理 marketplace/product/add 頁面中 ticket_7ELEVEN 屬性集的 GUID 查詢功能
 * 提供自動填入產品表單的完整功能，適用於前端 marketplace 環境
 */
require([
    'jquery',
    'mage/url',
    'plugins/DOMPurify'
], function ($, urlBuilder, DOMPurify) {
    'use strict';

    var $t = function (text) { return text; }; // 簡單的翻譯函數
    var alert = function (options) {
        if (typeof options === 'object' && options.content) {
            window.alert(options.content);
        } else {
            window.alert(options);
        }
    };

    var voucherLookup = {
        /**
         * 初始化 Voucher Lookup 功能
         */
        init: function () {
            this.bindEvents();
            this.initializeDefaultValues();
            this.checkForTicket7Eleven();
        },

        /**
         * 檢查是否為 ticket_7ELEVEN 屬性集
         */
        checkForTicket7Eleven: function () {
            var self = this;

            // 監聽屬性集變更
            $(document).on('change', '#attribute-set-id', function () {
                var selectedText = $(this).find('option:selected').text().trim();
                if (selectedText === 'ticket_7ELEVEN') {
                    setTimeout(function () {
                        self.addGuidLookupButton();
                    }, 500);
                } else {
                    self.removeGuidLookupButton();
                }
            });

            // 檢查當前選中的屬性集
            var currentText = $('#attribute-set-id option:selected').text().trim();
            if (currentText === 'ticket_7ELEVEN') {
                setTimeout(function () {
                    self.addGuidLookupButton();
                }, 1000);
            }
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
            if ($('.field, .control').length === 0) {
                setTimeout(function () {
                    self.addGuidLookupButton();
                }, 1500);
                return;
            }

            // 多種方式尋找 qware_guid 欄位 - 適應 marketplace 前端結構
            var guidField = $('input[name="product[qware_guid]"]');
            if (guidField.length === 0) {
                guidField = $('input[id*="qware_guid"]');
            }

            // 如果找不到 qware_guid 欄位，嘗試創建一個
            if (guidField.length === 0) {
                this.createQwareGuidField();
                guidField = $('input[name="product[qware_guid]"]');
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

            // 找到欄位的容器 - 適應前端結構
            var existingField = guidField.closest('.field, .form-field, .control-group');
            if (existingField.length === 0) {
                existingField = guidField.parent().parent();
            }

            // 創建查詢按鈕容器
            var lookupContainer = $(`
                <div id="qware-guid-lookup-container" class="field field-qware_lookup">
                    <label class="label">
                        <span>${$t('商品查詢:')}</span>
                    </label>
                    <div class="control">
                        <button id="voucher-lookup-btn" type="button" class="button action primary">
                            <span>${$t('查詢商品')}</span>
                        </button>
                        <div class="note" style="margin-top: 8px; font-size: 12px; color: #666;">
                            <span>${$t(' 輸入 GUID 後點擊查詢按鈕，將自動填入商品資訊')}</span>
                        </div>
                    </div>
                </div>
            `);

            // 將查詢按鈕插入到 GUID 欄位後面
            existingField.after(lookupContainer);

            // 初始化事件綁定
            this.bindLookupEvents();
        },

        /**
         * 創建 Qware GUID 欄位（如果不存在）
         */
        createQwareGuidField: function () {
            // 尋找合適的插入點
            var insertAfter = this.findInsertionPoint();
            if (!insertAfter.length) {
                return;
            }

            var guidFieldHtml = `
                <div class="field field-qware_guid">
                    <label class="label" for="product_qware_guid">
                        <span>${$t('Qware GUID')}</span>
                        <span class="required">*</span>
                    </label>
                    <div class="control">
                        <input type="text"
                               name="product[qware_guid]"
                               id="product_qware_guid"
                               class="input-text required-entry"
                               placeholder="${$t('請輸入 Qware GUID')}" />
                    </div>
                </div>
            `;

            insertAfter.after(guidFieldHtml);
        },

        /**
         * 尋找欄位插入點
         */
        findInsertionPoint: function () {
            var candidates = [
                $('.field-price').last(),
                $('.field-sku').last(),
                $('.field-name').last(),
                $('input[name="product[price]"]').closest('.field'),
                $('input[name="product[sku]"]').closest('.field'),
                $('input[name="product[name]"]').closest('.field')
            ];

            for (var i = 0; i < candidates.length; i++) {
                if (candidates[i].length > 0) {
                    return candidates[i];
                }
            }

            return $();
        },

        /**
         * 移除 GUID 查詢按鈕
         */
        removeGuidLookupButton: function () {
            $('#qware-guid-lookup-container').remove();
        },

        /**
         * 綁定查詢相關事件
         */
        bindLookupEvents: function () {
            var self = this;

            // 綁定查詢按鈕點擊事件
            $(document).off('click', '#voucher-lookup-btn').on('click', '#voucher-lookup-btn', function () {
                self.lookupVoucher();
            });

            // 綁定 GUID 欄位 Enter 鍵事件
            $(document).off('keypress', 'input[name="product[qware_guid]"]')
                .on('keypress', 'input[name="product[qware_guid]"]', function (e) {
                    if (e.which === 13) { // Enter key
                        e.preventDefault();
                        self.lookupVoucher();
                    }
                });
        },

        /**
         * 綁定事件監聽器
         */
        bindEvents: function () {
            var self = this;

            // 監聽 Qware 日期欄位變化
            $(document).on('change blur', 'input[name="product[qware_sale_start_date]"], input[name="product[qware_sale_end_date]"]', function () {
                self.validateQwareDate($(this));
            });
        },

        /**
         * 執行 Voucher 查詢
         */
        lookupVoucher: function () {
            var guid = DOMPurify.sanitize($('input[name="product[qware_guid]"]').val().trim());

            if (!guid) {
                this.showMessage($t('請輸入 GUID'), 'error');
                return;
            }

            this.setLoadingState(true);

            var self = this;

            // 構建 API URL - 適用於前端環境
            var apiUrl = this.buildApiUrl();

            // 獲取 form_key
            var formKey = this.getFormKey();

            if (!formKey) {
                this.showMessage($t('無法獲取 form_key，請刷新頁面重試'), 'error');
                this.setLoadingState(false);
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
                    self.clearGuidField();
                    self.showMessage($t('API 路由錯誤'), 'error');
                    return;
                }

                if (response.success && response.data) {
                    self.fillProductForm(response.data);
                    self.showMessage($t('商品資訊已成功載入！'), 'success');
                } else {
                    // 查詢失敗時清空 GUID 欄位
                    self.clearGuidField();
                    var errorMsg = response.message || $t('查詢失敗，請檢查 GUID 是否正確');
                    self.showMessage(errorMsg, 'error');
                }
            }).fail(function (xhr, status, error) {

                var errorMsg = $t('網路錯誤，請稍後重試');

                if (status === 'timeout') {
                    errorMsg = $t('請求逾時，請稍後重試');
                } else if (xhr.status === 404) {
                    errorMsg = $t('API 路由不存在 (404)');
                } else if (xhr.status === 403) {
                    errorMsg = $t('權限不足 (403)');
                } else if (xhr.status === 500) {
                    errorMsg = $t('伺服器錯誤 (500)');
                } else if (xhr.status === 0) {
                    errorMsg = $t('無法連接到伺服器');
                }

                // 網路錯誤時也清空 GUID 欄位
                self.clearGuidField();
                self.showMessage(errorMsg + ' ' + $t('(狀態碼: %1)').replace('%1', xhr.status), 'error');
            }).always(function () {
                self.setLoadingState(false);
            });
        },

        /**
         * 構建 API URL - 使用 Magento 的 URL Builder
         */
        buildApiUrl: function () {
            var apiUrl = urlBuilder.build('qware/index/getVoucherInfo');
            return apiUrl;
        },

        /**
         * 取得 form_key
         */
        getFormKey: function () {
            return window.FORM_KEY ||
                $('input[name="form_key"]').val() ||
                $('meta[name="form_key"]').attr('content') || '';
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

                // 觸發 SKU 自動生成
                setTimeout(function () {
                    var nameField = $('input[name="product[name]"]');
                    if (nameField.length) {
                        nameField.trigger('keyup').trigger('change');
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

                // Summary商品簡述 => 產品簡介
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

                    // 觸發限購欄位展開
                    this.triggerLimitPurchasedToggle();

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

                // Tags商品標籤 => 搜尋標籤
                if (data.tags) {
                    this.setFieldValue('product[search_tag]', data.tags);
                }

                // 庫存設定
                this.setFieldValue('product[stock_data][qty]', 999);
                this.setFieldValue('product[stock_data][is_in_stock]', 1);
                this.setFieldValue('product[salable_qty]', 99999);

                // 設定 Qware 銷售時間
                if (data.for_sale_start_date && data.for_sale_end_date) {
                    var startDate = this.formatDate(data.for_sale_start_date, false);
                    var endDate = this.formatDate(data.for_sale_end_date, true);

                    var validatedDates = this.validateDateOrder(startDate, endDate);

                    this.setFieldValue('product[qware_sale_start_date]', validatedDates.startDate);
                    this.setFieldValue('product[qware_sale_end_date]', validatedDates.endDate);
                }

            } catch (error) {
                this.showMessage($t('填入表單時發生錯誤'), 'error');
            }
        },

        /**
         * 建立描述內容
         */
        buildDescriptionContent: function (data) {
            var description = '';

            if (data.description) {
                var cleanDescription = this.cleanHtmlContent(data.description);
                description += '<h3>' + $t('商品說明') + '</h3>\n' + cleanDescription + '\n\n';
            }

            if (data.specification) {
                var cleanSpecification = this.cleanHtmlContent(data.specification);
                description += '<h3>' + $t('票券注意事項') + '</h3>\n' + cleanSpecification + '\n\n';
            }

            return description;
        },

        /**
         * 設定 TinyMCE 編輯器內容
         */
        setTinyMCEContent: function (content, fieldName) {
            if (!content) return;

            setTimeout(function () {
                // 嘗試尋找 TinyMCE 編輯器實例
                if (typeof tinyMCE !== 'undefined') {
                    var editorId = fieldName.replace(/\[|\]/g, '_').replace(/__/g, '_');
                    var editor = tinyMCE.get(editorId) || tinyMCE.get(fieldName.replace('product[', '').replace(']', ''));

                    if (editor) {
                        editor.setContent(content);
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
         * 建立注意事項內容 (Feature購回注意事項 + Notes票券使用說明)
         */
        buildNotesContent: function (data) {
            var notes = '';

            // Feature購回注意事項 => 注意事項
            if (data.feature) {
                var cleanFeature = this.cleanHtmlContent(data.feature);
                notes += '<h3>' + $t('購回注意事項') + '</h3>\n' + cleanFeature + '\n\n';
            }

            // Notes票券使用說明 => 注意事項
            if (data.notes) {
                var cleanNotes = this.cleanHtmlContent(data.notes);
                notes += '<h3>' + $t('票券使用說明') + '</h3>\n' + cleanNotes + '\n\n';
            }

            return notes;
        },

        /**
         * 選擇所有客戶群組
         */
        selectAllCustomerGroups: function () {
            var $selectField = $('select[name="product[limit_purchased_customer_group][]"]');
            if ($selectField.length) {
                $selectField.find('option').each(function () {
                    var optionValue = $(this).val();
                    var optionText = $(this).text();

                    // 選擇有效的選項（跳過空值或placeholder）
                    if (optionValue && optionValue !== '' &&
                        optionText.indexOf('Select') === -1 && optionText.indexOf('選擇') === -1) {
                        $(this).prop('selected', true);
                    }
                });

                $selectField.trigger('change');
            }
        },

        /**
         * 觸發限購設定欄位展開 - 使用原生 tier-price.js 邏輯
         */
        triggerLimitPurchasedToggle: function () {
            var self = this;
            var retryCount = 0;
            var maxRetries = 10;

            function attemptToggle() {
                // 尋找限購checkbox
                var $checkbox = $('#limit_purchased_enable');

                if ($checkbox.length && $checkbox.is(':visible')) {
                    var wasChecked = $checkbox.is(':checked');

                    // 觸發原生tier-price.js邏輯
                    if (!wasChecked) {
                        $checkbox.prop('checked', true);
                        $checkbox.trigger('click');
                    } else {
                        // 先取消再勾選來觸發事件
                        $checkbox.prop('checked', false).trigger('click');
                        setTimeout(function () {
                            $checkbox.prop('checked', true).trigger('click');
                        }, 100);
                    }

                    // 檢查並執行後續設定
                    setTimeout(function () {
                        // 確保欄位顯示
                        self.triggerTierPriceLogicDirectly();
                        self.showLimitPurchasedFields();

                        // 執行最終設定
                        setTimeout(function () {
                            self.finalizeLimitPurchasedSettings();
                        }, 300);
                    }, 300);

                } else if (retryCount < maxRetries) {
                    retryCount++;
                    setTimeout(attemptToggle, 500);
                } else {
                    self.showLimitPurchasedFields();
                }
            }

            // 延遲開始第一次嘗試
            setTimeout(attemptToggle, 300);
        },

        /**
         * 直接顯示限購相關欄位（簡化版）
         */
        showLimitPurchasedFields: function () {
            var limitFieldSelectors = [
                'input[name="product[limit_purchased_qty]"]',
                'select[name="product[limit_purchased_customer_group][]"]',
                'input[name="product[limit_purchased_start_time]"]',
                'input[name="product[limit_purchased_end_time]"]',
                'input[name="product[individual_product]"]'
            ];

            limitFieldSelectors.forEach(function (selector) {
                var $element = $(selector);
                if ($element.length) {
                    $element.closest('.field').show();
                }
            });
        },

        /**
         * 完成限購設定的最終步驟
         */
        finalizeLimitPurchasedSettings: function () {
            var self = this;

            // 確保checkbox顯示正確狀態
            var $checkbox = $('#limit_purchased_enable');
            if ($checkbox.length) {
                $checkbox.prop('checked', true);
            }

            // 選擇所有客戶群組
            setTimeout(function () {
                self.selectAllCustomerGroups();
            }, 500);

            // 勾選individual_product
            setTimeout(function () {
                var $individualCheckbox = $('input[name="product[individual_product]"][type="checkbox"]');
                if ($individualCheckbox.length) {
                    $individualCheckbox.prop('checked', true).trigger('change');
                }
            }, 700);
        },

        /**
         * 直接觸發tier-price.js的顯示邏輯
         */
        triggerTierPriceLogicDirectly: function () {
            var fieldsToShow = [
                '#limit_purchased_customer_group',
                '#limit_purchased_qty',
                '#limit_purchased_start_time',
                '#limit_purchased_end_time'
            ];

            fieldsToShow.forEach(function (fieldId) {
                var $field = $(fieldId);
                if ($field.length) {
                    $field.parents('.field').show();
                }
            });
        },


        /**
         * 清理HTML內容
         */
        cleanHtmlContent: function (htmlContent) {
            if (!htmlContent) return '';
            return DOMPurify.sanitize(htmlContent, {
                ALLOWED_TAGS: [], // Strip all tags to get plain text, or configure as needed for TinyMCE
                KEEP_CONTENT: true
            });
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
            var $checkbox = $('input[type="checkbox"][name="' + fieldName + '"]');
            if ($checkbox.length) {
                $checkbox.prop('checked', checked).trigger('change');
                return;
            }

            var $input = $('input[name="' + fieldName + '"]');
            if ($input.length) {
                $input.val(checked ? 1 : 0).trigger('change');
                return;
            }
        },

        /**
         * 格式化日期為 MM/DD/YYYY 格式並補零
         */
        formatDate: function (dateString, isEndDate = false) {
            if (!dateString) return '';

            try {
                var inputDate = new Date(dateString);
                var today = new Date();
                today.setHours(0, 0, 0, 0);

                var processedDate;

                if (isEndDate) {
                    if (inputDate <= today) {
                        processedDate = new Date();
                        processedDate.setFullYear(processedDate.getFullYear() + 1);
                    } else {
                        processedDate = inputDate;
                    }
                } else {
                    if (inputDate <= today) {
                        processedDate = new Date();
                        processedDate.setDate(processedDate.getDate() + 1);
                    } else {
                        processedDate = inputDate;
                    }
                }

                var year = processedDate.getFullYear();
                var month = ('0' + (processedDate.getMonth() + 1)).slice(-2); // 補零
                var day = ('0' + processedDate.getDate()).slice(-2); // 補零

                return month + '/' + day + '/' + year; // MM/DD/YYYY 格式
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
                return {
                    startDate: startDateString,
                    endDate: endDateString
                };
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
                today.setHours(0, 0, 0, 0);
                selectedDate.setHours(0, 0, 0, 0);

                if (selectedDate < today) {
                    var fieldName = field.attr('name');
                    var message = '';

                    if (fieldName === 'product[qware_sale_start_date]') {
                        message = $t('Qware 銷售開始日期不能小於今日');
                    } else if (fieldName === 'product[qware_sale_end_date]') {
                        message = $t('Qware 銷售結束日期不能小於今日');
                    }

                    field.val('');
                    field.trigger('change');

                    alert({
                        title: $t('Qware 日期驗證錯誤'),
                        content: message
                    });
                }
            } catch (e) {
                // Invalid date format, ignore
            }
        },

        /**
         * 初始化預設值
         */
        initializeDefaultValues: function () {
            // 預設值設定可在這裡添加
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
         * 清空 GUID 欄位
         */
        clearGuidField: function () {
            try {
                // 清空 GUID 欄位
                this.setFieldValue('product[qware_guid]', '');
            } catch (error) {
                console.warn('Error clearing GUID field:', error);
            }
        },

        /**
         * 顯示訊息
         */
        showMessage: function (message, type) {
            type = type || 'info';

            // 使用 Magento Alert Modal
            if (typeof alert !== 'undefined') {
                var title = type === 'error' ? $t('錯誤') :
                    type === 'success' ? $t('成功') : $t('訊息');
                alert({
                    title: title,
                    content: DOMPurify.sanitize(message)
                });
            } else {
                // 回退到 browser alert
                window.alert('[' + type.toUpperCase() + '] ' + message);
            }
        }
    };

    // 初始化
    jQuery(document).ready(function () {
        voucherLookup.init();
    });

    // 使用 jQuery 的 ajaxComplete 事件來確保動態內容載入完成後再初始化
    jQuery(document).ajaxComplete(function () {
        voucherLookup.init();
    });
});

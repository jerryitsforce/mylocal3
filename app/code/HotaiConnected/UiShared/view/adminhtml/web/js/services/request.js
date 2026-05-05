/**
 * HotaiConnected UiShared - HTTP Request Service
 * 
 * @module HotaiConnected_UiShared/js/services/request
 * @version 2.3.0
 * @author HotaiConnected
 * @updated 2025-10-29 - 重構：使用共用的 logger 工具模組
 * @updated 2025-10-29 - 重構：拆分 download() 方法，提升代碼可讀性和可維護性
 * @updated 2025-10-29 - 新增：通用檔案下載方法，支援 ZIP、XLSX、PDF 等二進制文件
 * @updated 2025-10-20 - 修正：GET 請求參數處理，正確放在 URL 查詢字串中
 * @updated 2025-10-16 - 移至 UiShared 共用模組，提供統一的 HTTP 請求服務
 * 
 * 功能說明：
 * - 提供統一的 AJAX 請求處理函數（GET, POST, PUT, DELETE, DOWNLOAD）
 * - 統一的錯誤處理和成功處理
 * - 自動整合載入動畫和訊息提示
 * - 自動添加認證頭
 * - 支援自訂配置選項
 * - 檔案下載支援二進制資料處理和自動下載（ZIP、XLSX、PDF 等）
 * - 使用共用 logger 工具進行日誌管理
 * 
 * 設計理念：
 * - 所有業務模組應該調用此服務
 * - 提供簡潔的 API（get, post, put, delete, download）
 * - 自動處理 loading 和 toast 訊息
 * 
 * Debug 控制：
 * - 使用 hotaiLogger 統一管理日誌
 * - 全局啟用（臨時）：require(['hotaiLogger'], function(logger) { logger.enableDebug(); });
 * - 全局關閉：require(['hotaiLogger'], function(logger) { logger.disableDebug(); });
 * - 查看狀態：require(['hotaiLogger'], function(logger) { console.log(logger.getGlobalDebug()); });
 * - 所有使用 logger 的模組都會受全局開關控制
 * 
 * 引用檔案：
 * 
 * Magento 內建功能
 * - jquery: AJAX 請求處理
 * - mage/url: Magento URL 建構器
 * - mage/translate: 多語系翻譯
 * 
 * 共用模組（來自 UiShared）
 * - hotaiLoadingMask: 載入動畫
 * - hotaiToastMessage: Toast 訊息
 * - hotaiLogger: 統一日誌管理
 */
define([
    'jquery',
    'mage/url',
    'mage/translate',
    'mage/storage',
    'hotaiLoadingMask',
    'hotaiToastMessage',
    'hotaiLogger'
], function ($, urlBuilder, $t, storage, loadingMask, toastMessage, logger) {
    'use strict';

    // 創建模組專用的 logger 實例
    var log = logger.create('Request');

    // 共用 Header 定義（從 DOM 讀取配置）
    var getAjaxHeaderData = function() {
        var $configElement = $('#js-shared-config');
        var xMagentoAuth = $configElement.attr('data-x-magento-auth') || '';
    
        return {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Magento-Auth': xMagentoAuth
        };
    };

    return {

        // ==========================================
        // 預設配置
        // ==========================================

        /**
         * 預設請求配置
         * @type {Object}
         */
        defaults: {
            dataType: 'json',
            cache: false,
            showLoader: true,
            showSuccessMessage: false,
            showErrorMessage: true
        },

        /**
         * 檔案下載專用配置
         * @type {Object}
         */
        downloadDefaults: {
            cache: false,
            showLoader: true,
            showSuccessMessage: false,
            showErrorMessage: true
            // 注意：不包含 dataType，因為要處理二進制數據
        },

        /**
         * 常數定義
         * @type {Object}
         */
        constants: {
            BLOB_CLEANUP_DELAY: 100,
            JSON_CONTENT_TYPES: ['application/json', 'text/json'],
            DEFAULT_DOWNLOAD_FILENAME: 'download',
            MIME_TYPES: {
                'zip': 'application/zip',
                'xlsx': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xls': 'application/vnd.ms-excel',
                'pdf': 'application/pdf',
                'csv': 'text/csv',
                'png': 'image/png',
                'jpg': 'image/jpeg',
                'jpeg': 'image/jpeg'
            }
        },

        // ==========================================
        // 核心方法
        // ==========================================

        /**
         * 發送 AJAX 請求（核心方法）
         * 
         * @param {string} url - API URL 路徑
         * @param {string} method - HTTP 方法 ('GET', 'POST', 'PUT', 'DELETE')
         * @param {Object} data - 請求資料
         * @param {Object} options - 額外選項配置
         * @param {boolean} [options.showLoader=true] - 是否顯示載入動畫
         * @param {boolean} [options.showSuccessMessage=false] - 是否顯示成功訊息
         * @param {boolean} [options.showErrorMessage=true] - 是否顯示錯誤訊息
         * @param {string} [options.successMessage] - 自訂成功訊息
         * @returns {jQuery.Deferred} AJAX Promise
         * 
         * @example
         * // 基本使用（會顯示成功訊息）
         * request.send('rest/V1/batch/create', 'POST', {batch_id: 123}).done(function(response) {
         *     console.log('成功:', response);
         * });
         * 
         * @example
         * // 關閉成功訊息
         * request.send('rest/V1/batch/list', 'GET', {}, {
         *     showSuccessMessage: false
         * });
         */
        send: function(url, method, data, options) {
            var self = this;
            var settings = $.extend({}, this.defaults, options || {});
            
            // 參數驗證
            if (!url) {
                log.error('Request error: URL is required');
                return $.Deferred().reject({message: 'URL is required'}).promise();
            }
            
            if (!method) {
                log.error('Request error: HTTP method is required');
                return $.Deferred().reject({message: 'HTTP method is required'}).promise();
            }

            // 顯示載入動畫
            if (settings.showLoader) {
                loadingMask.show();
            }

            // 準備請求資料
            var requestData = data || {};
            var httpMethod = method.toUpperCase();

            // 建構完整 URL（所有 API 都是 REST API）
            var fullUrl = urlBuilder.build(url);
            requestData = httpMethod === 'GET' ? requestData : JSON.stringify(requestData)

            // 根據 HTTP 方法決定資料處理方式
            var ajaxConfig = {
                url: fullUrl,
                type: httpMethod,
                dataType: settings.dataType,
                cache: settings.cache,
                global: false,  // 禁用全局 AJAX 事件，防止 Magento 自動插入錯誤訊息
                data: requestData,
                xhrFields: {
                    // 關鍵設置：允許跨域攜帶 Cookie（憑證）
                    withCredentials: true 
                },
                headers: self.ajaxHeader(),
                beforeSend: self.ajaxBeforeSend
            };

            // 發送請求
            return $.ajax(ajaxConfig)
                .done(function(response) {
                    log.info('✓ AJAX 成功:', httpMethod, url, response);
                    
                    // 顯示成功訊息
                    if (settings.showSuccessMessage) {
                        var message = settings.successMessage || 
                                      response.message || 
                                      $.mage.__('Success');
                        toastMessage.success(message);
                    }
                })
                .fail(function(xhr, status, error) {
                    log.error('✗ AJAX 失敗:', httpMethod, url, {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        error: error
                    });
                    
                    // 顯示錯誤訊息
                    if (settings.showErrorMessage) {
                        var errorMessage = self._getErrorMessage(xhr);
                        toastMessage.error(errorMessage);
                    }
                })
                .always(function() {
                    // 隱藏載入動畫
                    if (settings.showLoader) {
                        loadingMask.hide();
                    }
                });
        },

        /**
         * 取得 AJAX 請求的 headers 配置
         * 主要用於 ajax & tabulator-config
         * @returns {Object} Headers 物件
         */
        ajaxHeader: function() {
            var headerData = getAjaxHeaderData();

            // 如有個別定義，可於此處加入
            // EX: headerData['XXX'] = 'XXX';

            return headerData;
        },

        /**
         * AJAX beforeSend 回調函數
         * 強制設定 headers，防止被其他攔截器修改
         * 
         * 注意：headers 定義與 ajaxHeader() 保持一致
         * 
         * @param {XMLHttpRequest} xhr - XMLHttpRequest 物件
         */
        ajaxBeforeSend: function(xhr) {
            var headerData = getAjaxHeaderData();
            Object.keys(headerData).forEach(function(key){
                xhr.setRequestHeader(key, headerData[key]);
            })

            // 如有個別定義，可於此處加入
            // EX: xhr.setRequestHeader('X-XXX', 'XXX');
        },

        // ==========================================
        // 快捷方法
        // ==========================================

        /**
         * GET 請求快捷方法
         * 
         * @param {string} url - API URL
         * @param {Object} params - 查詢參數（會自動轉換為 URL 查詢字串）
         * @param {Object} options - 選項配置
         * @returns {jQuery.Deferred}
         * 
         * @example
         * // GET 請求，參數會自動轉換為查詢字串
         * request.get('rest/V1/reconciliation/status', {page: 1, limit: 20})
         * // 實際請求：rest/V1/reconciliation/status?page=1&limit=20
         */
        get: function(url, params, options) {
            return this.send(url, 'GET', params, options);
        },

        /**
         * POST 請求快捷方法
         * 
         * @param {string} url - API URL
         * @param {Object} data - 請求資料
         * @param {Object} options - 選項配置
         * @returns {jQuery.Deferred}
         */
        post: function(url, data, options) {
            return this.send(url, 'POST', data, options);
        },

        /**
         * PUT 請求快捷方法
         * 
         * @param {string} url - API URL
         * @param {Object} data - 請求資料
         * @param {Object} options - 選項配置
         * @returns {jQuery.Deferred}
         */
        put: function(url, data, options) {
            return this.send(url, 'PUT', data, options);
        },

        /**
         * DELETE 請求快捷方法
         * 
         * @param {string} url - API URL
         * @param {Object} data - 請求資料
         * @param {Object} options - 選項配置
         * @returns {jQuery.Deferred}
         */
        delete: function(url, data, options) {
            return this.send(url, 'DELETE', data, options);
        },

        /**
         * 檔案下載快捷方法（支援所有二進制文件）
         * 
         * 技術說明：
         * - 支援任何二進制文件格式：ZIP、XLSX、PDF、CSV、PNG 等
         * - 請求 Content-Type: 根據 HTTP 方法自動設置（GET 不設置，POST 使用 application/json）
         * - 響應 Content-Type: 服務器返回對應的 MIME 類型（application/zip, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet 等）
         * - 響應 Content-Disposition: 服務器應設置 "attachment; filename=xxx.ext"
         * - 使用 Blob 處理二進制數據，自動觸發瀏覽器下載
         * 
         * 常見使用場景：
         * - ZIP 壓縮檔 (.zip)
         * - Excel 報表 (.xlsx, .xls)
         * - PDF 文件 (.pdf)
         * - CSV 數據導出 (.csv)
         * - 圖片文件 (.png, .jpg 等)
         * 
         * @param {string} url - API URL
         * @param {Object} data - 請求資料
         * @param {Object} options - 選項配置
         * @param {string} [options.filename] - 下載的檔案名稱（未指定時，優先使用服務器返回的檔名）
         * @param {Object} [options.headers] - 自訂 HTTP request headers（注意：不是 response headers）
         * @param {string} [options.method='POST'] - HTTP 方法 ('GET' 或 'POST')
         * @returns {jQuery.Deferred}
         * 
         * @example
         * // 使用服務器返回的檔案名稱
         * request.download('rest/V1/export/zip', {batch_id: 123});
         * // 服務器 Content-Disposition: attachment; filename="report_2024.zip"
         * // → 下載為 report_2024.zip
         * 
         * @example
         * // 使用客戶端指定的檔案名稱
         * request.download('rest/V1/export/excel', {ids: [1, 2, 3]}, {
         *     method: 'GET',
         *     filename: 'my_custom_report.xlsx'
         * });
         * // → 下載為 my_custom_report.xlsx（忽略服務器的檔名）
         * 
         * @example
         * // 下載 PDF - 自訂 headers
         * request.download('rest/V1/invoice/pdf', {invoice_id: 456}, {
         *     filename: 'invoice.pdf',
         *     headers: {
         *         'X-Export-Format': 'detailed'
         *     }
         * });
         */
        download: function(url, data, options) {
            var self = this;
            var settings = this._prepareDownloadSettings(options);
            var ajaxConfig = this._buildDownloadConfig(url, data, settings);
            
            return this._executeDownload(ajaxConfig, settings);
        },


        // ==========================================
        // 私有方法
        // ==========================================

        /**
         * 準備下載設置
         * @private
         * @param {Object} options - 選項配置
         * @returns {Object} 合併後的設置
         */
        _prepareDownloadSettings: function(options) {
            return $.extend({}, this.downloadDefaults, options || {});
        },

        /**
         * 建構下載 AJAX 配置
         * @private
         * @param {string} url - API URL
         * @param {Object} data - 請求資料
         * @param {Object} settings - 設置
         * @returns {Object} AJAX 配置
         */
        _buildDownloadConfig: function(url, data, settings) {
            var self = this;
            var requestData = data || {};
            var httpMethod = (settings.method || 'POST').toUpperCase();
            var fullUrl = urlBuilder.build(url);
            var customHeaders = settings.headers || {};

            var ajaxConfig = {
                url: fullUrl,
                type: httpMethod,
                cache: false,
                dataType: 'binary',
                xhrFields: {
                    responseType: 'blob'
                },
                global: false,
                headers: self.ajaxHeader(),
                beforeSend: self.ajaxBeforeSend,
                converters: {
                    '* binary': function(data) {
                        return data;
                    }
                }
            };

            // 根據 HTTP 方法決定資料處理方式
            if (httpMethod === 'GET') {
                ajaxConfig.data = requestData;
                ajaxConfig.processData = true;
            } else {
                var jsonData = JSON.stringify(requestData);
                ajaxConfig.data = jsonData;
                ajaxConfig.processData = false;
                ajaxConfig.headers['Content-Type'] = 'application/json';
            }

            // 合併自訂 headers
            if (Object.keys(customHeaders).length > 0) {
                ajaxConfig.headers = $.extend({}, ajaxConfig.headers, customHeaders);
            }

            // 使用 beforeSend 強制設置 headers
            ajaxConfig.beforeSend = function(xhr) {
                $.each(ajaxConfig.headers, function(key, value) {
                    xhr.setRequestHeader(key, value);
                });
            };

            return ajaxConfig;
        },

        /**
         * 執行下載請求
         * @private
         * @param {Object} ajaxConfig - AJAX 配置
         * @param {Object} settings - 設置
         * @returns {jQuery.Deferred} Promise
         */
        _executeDownload: function(ajaxConfig, settings) {
            var self = this;
            var deferred = $.Deferred();

            // 顯示載入動畫
            if (settings.showLoader) {
                loadingMask.show();
            }

            $.ajax(ajaxConfig)
                .done(function(blob, status, xhr) {
                    log.info('✓ AJAX 請求成功:', ajaxConfig.type, ajaxConfig.url, {
                        size: blob.size,
                        type: blob.type
                    });

                    if (self._isJsonResponse(blob)) {
                        self._handleJsonResponse(blob, deferred, settings);
                    } else {
                        self._handleFileDownload(blob, xhr, deferred, settings);
                    }
                })
                .fail(function(xhr, status, error) {
                    self._handleDownloadError(xhr, status, error, settings, deferred);
                });

            return deferred.promise();
        },

        /**
         * 檢查是否為 JSON 響應
         * @private
         * @param {Blob} blob - 響應 Blob
         * @returns {boolean} 是否為 JSON
         */
        _isJsonResponse: function(blob) {
            // 如果 blob.type 為空或 undefined，當作二進制文件處理（不下載請求不應該返回空類型）
            if (!blob.type || blob.type === '') {
                return false;
            }
            return this.constants.JSON_CONTENT_TYPES.some(function(type) {
                return blob.type.indexOf(type) !== -1;
            });
        },

        /**
         * 處理 JSON 響應
         * @private
         * @param {Blob} blob - 響應 Blob
         * @param {jQuery.Deferred} deferred - Deferred 物件
         * @param {Object} settings - 設置
         */
        _handleJsonResponse: function(blob, deferred, settings) {
            var self = this;
            log.warn('⚠️ 收到 JSON 響應而非二進制文件');

            var reader = new FileReader();
            reader.onload = function() {
                try {
                    var response = JSON.parse(reader.result);
                    log.info('JSON 響應內容:', response);

                    if (settings.showLoader) {
                        loadingMask.hide();
                    }

                    deferred.resolve({
                        isJsonResponse: true,
                        data: response
                    });
                } catch (e) {
                    log.error('解析 JSON 響應失敗:', e);

                    if (settings.showLoader) {
                        loadingMask.hide();
                    }

                    deferred.reject({
                        parseError: e
                    });
                }
            };
            reader.readAsText(blob);
        },

        /**
         * 處理文件下載
         * @private
         * @param {Blob} blob - 響應 Blob
         * @param {Object} xhr - XMLHttpRequest 物件
         * @param {jQuery.Deferred} deferred - Deferred 物件
         * @param {Object} settings - 設置
         */
        _handleFileDownload: function(blob, xhr, deferred, settings) {
            var self = this;
            log.info('✓ 檔案下載成功:', settings.method || 'POST');

            var filename = this._resolveFilename(xhr, settings.filename);
            this._triggerBrowserDownload(blob, filename);
            this._showDownloadSuccess(settings, filename);

            if (settings.showLoader) {
                loadingMask.hide();
            }

            deferred.resolve(blob, 'success', xhr);
        },

        /**
         * 處理下載錯誤
         * @private
         * @param {Object} xhr - XMLHttpRequest 物件
         * @param {string} status - 狀態
         * @param {string} error - 錯誤
         * @param {Object} settings - 設置
         * @param {jQuery.Deferred} deferred - Deferred 物件
         */
        _handleDownloadError: function(xhr, status, error, settings, deferred) {
            var self = this;
            log.error('✗ 檔案下載失敗:', {
                status: xhr.status,
                statusText: xhr.statusText,
                error: error
            });

            if (settings.showErrorMessage) {
                this._getBlobErrorMessage(xhr).done(function(errorMessage) {
                    toastMessage.error(errorMessage);
                });
            }

            if (settings.showLoader) {
                loadingMask.hide();
            }

            deferred.reject(xhr, status, error);
        },

        /**
         * 解析檔案名稱
         * @private
         * @param {Object} xhr - XMLHttpRequest 物件
         * @param {string} userFilename - 用戶指定的檔名
         * @returns {string} 最終檔名
         */
        _resolveFilename: function(xhr, userFilename) {
            if (userFilename) {
                return userFilename;
            }

            var contentDisposition = xhr.getResponseHeader('Content-Disposition');
            if (contentDisposition) {
                var filenameMatch = contentDisposition.match(/filename\*?=['"]?(?:UTF-\d+'')?([^;\r\n"']*)['"]?/i);
                if (filenameMatch && filenameMatch[1]) {
                    return decodeURIComponent(filenameMatch[1]);
                }
            }

            return this.constants.DEFAULT_DOWNLOAD_FILENAME;
        },

        /**
         * 根據檔案副檔名獲取 MIME 類型
         * @private
         * @param {string} filename - 檔案名稱
         * @returns {string} MIME 類型
         */
        _getMimeTypeFromFilename: function(filename) {
            if (!filename) {
                return 'application/octet-stream';
            }

            var extension = filename.split('.').pop().toLowerCase();
            return this.constants.MIME_TYPES[extension] || 'application/octet-stream';
        },

        /**
         * 觸發瀏覽器下載
         * @private
         * @param {Blob} blob - 文件 Blob
         * @param {string} filename - 檔案名稱
         */
        _triggerBrowserDownload: function(blob, filename) {
            // 如果 blob.type 為空或不正確，根據檔案副檔名設置正確的 MIME 類型
            var mimeType = blob.type || this._getMimeTypeFromFilename(filename);
            
            // 如果 blob.type 與推斷的類型不一致，創建新的 Blob 以確保正確的 MIME 類型
            var finalBlob = blob.type === mimeType ? blob : new Blob([blob], { type: mimeType });
            
            var link = document.createElement('a');
            var blobUrl = window.URL.createObjectURL(finalBlob);
            link.href = blobUrl;
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();

            // 清理
            setTimeout(function() {
                document.body.removeChild(link);
                window.URL.revokeObjectURL(blobUrl);
            }, this.constants.BLOB_CLEANUP_DELAY);
        },

        /**
         * 顯示下載成功訊息
         * @private
         * @param {Object} settings - 設置
         * @param {string} filename - 檔案名稱
         */
        _showDownloadSuccess: function(settings, filename) {
            if (settings.showSuccessMessage) {
                var message = settings.successMessage || 
                              $.mage.__('File downloaded successfully: ') + filename;
                toastMessage.success(message);
            }
        },

        /**
         * 從 Blob 響應中提取錯誤訊息（用於檔案下載失敗）
         * 
         * 當 responseType 是 'blob' 時，即使服務器返回錯誤，xhr.response 也是 Blob
         * 需要讀取 Blob 內容並解析 JSON 來獲取錯誤訊息
         * 
         * @private
         * @param {Object} xhr - XMLHttpRequest 物件
         * @returns {jQuery.Deferred<string>} Promise 返回錯誤訊息
         */
        _getBlobErrorMessage: function(xhr) {
            var deferred = $.Deferred();
            var defaultMessage = this._getErrorMessage(xhr);
            
            // 如果響應不是 Blob，直接使用標準錯誤處理
            if (!xhr.response || !(xhr.response instanceof Blob)) {
                deferred.resolve(defaultMessage);
                return deferred.promise();
            }
            
            // 讀取 Blob 內容
            var reader = new FileReader();
            reader.onload = function() {
                try {
                    // 嘗試解析 JSON 錯誤訊息
                    var response = JSON.parse(reader.result);
                    var message = response.message || defaultMessage;
                    deferred.resolve(message);
                } catch (e) {
                    // 解析失敗，使用預設訊息
                    log.warn('[Request._getBlobErrorMessage] 無法解析 Blob 錯誤訊息:', e);
                    deferred.resolve(defaultMessage);
                }
            };
            reader.onerror = function() {
                log.error('[Request._getBlobErrorMessage] FileReader 錯誤');
                deferred.resolve(defaultMessage);
            };
            reader.readAsText(xhr.response);
            
            return deferred.promise();
        },

        /**
         * 從 XHR 物件取得錯誤訊息
         * 
         * @private
         * @param {Object} xhr - XMLHttpRequest 物件
         * @returns {string} 錯誤訊息
         */
        _getErrorMessage: function(xhr) {
            var message = $.mage.__('An error occurred. Please try again.');
            
            try {
                // 嘗試解析 JSON 回應
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    var response = JSON.parse(xhr.responseText);
                    message = response.message || message;
                }
            } catch (e) {
                // 注意：這裡不使用 this._log，因為可能在 FileReader 回調中調用
                if (console && console.error) {
                    console.error('無法解析錯誤訊息:', e);
                }
            }
            
            // 根據 HTTP 狀態碼提供更具體的訊息
            switch (xhr.status) {
                case 400:
                    message = $.mage.__('Invalid request. Please check your input.');
                    break;
                case 401:
                    message = $.mage.__('Authentication required. Please login again.');
                    break;
                case 403:
                    message = $.mage.__('You do not have permission to perform this action.');
                    break;
                case 404:
                    message = $.mage.__('API endpoint not found.');
                    break;
                case 500:
                    message = $.mage.__('Server error. Please try again later.');
                    break;
                case 503:
                    message = $.mage.__('Service temporarily unavailable. Please try again later.');
                    break;
                case 0:
                    message = $.mage.__('Network error. Please check your connection.');
                    break;
            }
            
            return message;
        }
    };
});
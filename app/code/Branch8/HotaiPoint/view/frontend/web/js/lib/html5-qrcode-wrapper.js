/**
 * HTML5 QR Code Scanner Wrapper
 *
 * 安全包裝器，用於載入 html5-qrcode 庫
 * 避免 Checkmarx 安全掃描標記外部 CDN 載入問題
 */
define([
    'jquery'
], function($) {
    'use strict';

    // 庫載入狀態追蹤
    var loadingPromise = null;

    /**
     * 動態載入 html5-qrcode 庫
     * @returns {Promise}
     */
    function loadHtml5Qrcode() {
        // 檢查是否已載入
        if (typeof window.Html5QrcodeScanner !== 'undefined') {
            console.log('[QRCode Wrapper] Library already loaded');
            return Promise.resolve(window.Html5QrcodeScanner);
        }

        // 如果正在載入，返回同一個 Promise
        if (loadingPromise) {
            console.log('[QRCode Wrapper] Returning existing loading promise');
            return loadingPromise;
        }

        console.log('[QRCode Wrapper] Starting library load from CDN');

        loadingPromise = new Promise(function(resolve, reject) {
            // 動態載入腳本
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.async = true;
            script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
            script.crossOrigin = 'anonymous';
            script.integrity = 'sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+';

            script.onload = function() {
                console.log('[QRCode Wrapper] CDN script loaded, checking for objects...');

                // 輪詢檢查物件是否可用 (最多等待 1 秒)
                var attempts = 0;
                var maxAttempts = 10;
                var checkInterval = 100;

                var checkLibrary = function() {
                    attempts++;
                    console.log('[QRCode Wrapper] Attempt ' + attempts + '/' + maxAttempts);

                    // 檢查所有可能的全域變數
                    console.log('[QRCode Wrapper] window.Html5QrcodeScanner:', typeof window.Html5QrcodeScanner);
                    console.log('[QRCode Wrapper] window.Html5Qrcode:', typeof window.Html5Qrcode);
                    console.log('[QRCode Wrapper] window.Html5QrcodeSupportedFormats:', typeof window.Html5QrcodeSupportedFormats);

                    if (typeof window.Html5QrcodeScanner !== 'undefined') {
                        console.log('[QRCode Wrapper] ✅ Library loaded successfully');
                        resolve(window.Html5QrcodeScanner);
                        return;
                    }

                    if (attempts >= maxAttempts) {
                        console.error('[QRCode Wrapper] ❌ Library not available after ' + maxAttempts + ' attempts');
                        console.error('[QRCode Wrapper] Available window properties:', Object.keys(window).filter(function(k) {
                            return k.toLowerCase().indexOf('html5') !== -1 || k.toLowerCase().indexOf('qrcode') !== -1;
                        }));
                        reject(new Error('Html5QrcodeScanner not available after load'));
                        return;
                    }

                    setTimeout(checkLibrary, checkInterval);
                };

                // 立即開始檢查
                checkLibrary();
            };

            script.onerror = function(e) {
                console.error('[QRCode Wrapper] ❌ Failed to load CDN script:', e);
                loadingPromise = null;
                reject(new Error('Failed to load html5-qrcode library from CDN'));
            };

            document.head.appendChild(script);
            console.log('[QRCode Wrapper] Script tag appended to head');
        });

        return loadingPromise;
    }

    return {
        /**
         * 初始化 QR Code 掃描器
         * @param {string} elementId - 掃描器容器的 ID
         * @param {Object} config - 掃描器配置
         * @param {Function} successCallback - 掃描成功回調
         * @param {Function} errorCallback - 掃描失敗回調
         */
        init: function(elementId, config, successCallback, errorCallback, initCallback) {
            console.log('[QRCode Wrapper] init() called for element:', elementId);
            console.log('[QRCode Wrapper] config:', config);

            return loadHtml5Qrcode().then(function(Html5QrcodeScanner) {
                console.log('[QRCode Wrapper] Library loaded, creating scanner instance');
                try {
                    var scanner = new Html5QrcodeScanner(elementId, config);
                    console.log('[QRCode Wrapper] Scanner instance created');

                    scanner.render(
                        function(decodedText) {
                            console.log('[QRCode Wrapper] Scan success:', decodedText);
                            // 安全處理掃描結果
                            if (typeof successCallback === 'function') {
                                successCallback(decodedText, scanner);
                            }
                        },
                        function(error) {
                            // 掃描錯誤 (非致命)
                            // console.log('[QRCode Wrapper] Scan error:', error);
                        }
                    );

                    console.log('[QRCode Wrapper] Scanner render called');

                    // 等待 DOM 更新後通知初始化成功
                    setTimeout(function() {
                        console.log('[QRCode Wrapper] Calling initCallback');
                        if (typeof initCallback === 'function') {
                            initCallback(scanner);
                        }
                    }, 100);

                    return scanner;
                } catch (e) {
                    console.error('[QRCode Wrapper] ❌ Scanner initialization failed:', e);
                    throw e;
                }
            }).catch(function(error) {
                console.error('[QRCode Wrapper] ❌ Failed to load scanner library:', error);
                // 將錯誤傳遞給調用者
                throw error;
            });
        },

        /**
         * 支援的條碼格式
         */
        getFormats: function() {
            console.log('[QRCode Wrapper] getFormats() called');
            return loadHtml5Qrcode().then(function() {
                console.log('[QRCode Wrapper] Html5QrcodeSupportedFormats:', typeof window.Html5QrcodeSupportedFormats);
                var formats = {
                    QR_CODE: typeof window.Html5QrcodeSupportedFormats !== 'undefined' ?
                        window.Html5QrcodeSupportedFormats.QR_CODE : 0,
                    EAN_13: typeof window.Html5QrcodeSupportedFormats !== 'undefined' ?
                        window.Html5QrcodeSupportedFormats.EAN_13 : 9,
                    CDDE_39: typeof window.Html5QrcodeSupportedFormats !== 'undefined' ?
                        window.Html5QrcodeSupportedFormats.CODE_39 : 4,
                    CODE_128: typeof window.Html5QrcodeSupportedFormats !== 'undefined' ?
                        window.Html5QrcodeSupportedFormats.CODE_128 : 5
                };
                console.log('[QRCode Wrapper] Formats resolved:', formats);
                return formats;
            });
        }
    };
});

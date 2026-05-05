/**
 * 台灣統一編號驗證模組
 *
 * 根據台灣財政部規定實作統一編號檢查碼驗證邏輯
 * 參考: https://cynthiachuang.github.io/Check-Tax-ID-Number/
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        /**
         * 驗證台灣統一編號
         *
         * @param {string} taxId - 8位數統一編號
         * @return {Object} {isValid: boolean, message: string}
         */
        validate: function(taxId) {
            // 檢查格式
            if (!this.isValidFormat(taxId)) {
                return {
                    isValid: false,
                    message: $t('Please check the Tax ID Number again and confirm the data is correct')
                };
            }

            // 檢查檢查碼
            if (!this.isValidChecksum(taxId)) {
                return {
                    isValid: false,
                    message: $t('Please check the Tax ID Number again and confirm the data is correct')
                };
            }

            return {
                isValid: true,
                message: ''
            };
        },

        /**
         * 檢查統一編號格式（僅檢查長度和數字）
         *
         * @param {string} taxId
         * @return {boolean}
         */
        isValidFormat: function(taxId) {
            // 檢查是否為空
            if (!taxId || taxId === null || taxId === '') {
                return false;
            }

            // 轉換為字串並去除空白
            taxId = String(taxId).trim();

            // 檢查長度是否為8
            if (taxId.length !== 8) {
                return false;
            }

            // 檢查是否全為數字
            if (!/^\d{8}$/.test(taxId)) {
                return false;
            }

            return true;
        },

        /**
         * 執行統一編號檢查碼驗證
         *
         * 驗證邏輯:
         * 1. 權重: [1, 2, 1, 2, 1, 2, 4, 1]
         * 2. 將每位數字乘以對應權重
         * 3. 將乘積的十位數和個位數相加
         * 4. 所有位數的和必須能被5整除
         * 5. 特殊情況: 第7位為7時，乘積為28，需特殊處理
         *
         * @param {string} taxId
         * @return {boolean}
         */
        isValidChecksum: function(taxId) {
            // 權重陣列，對應 PHP: $weight = [1, 2, 1, 2, 1, 2, 4, 1]
            var weights = [1, 2, 1, 2, 1, 2, 4, 1];
            var sum = 0;

            // 計算加權總和，邏輯與 PHP checkBusinessNumber 完全對應
            for (var i = 0; i < 8; i++) {
                var digit = parseInt(taxId.charAt(i), 10);
                var product = digit * weights[i];

                // 對應 PHP: $s = (int)($p / 10) + ($p % 10)
                var crossSum = Math.floor(product / 10) + (product % 10);

                // 對應 PHP: $s = ($s === 10) ? 0 : $s
                // 唯一會產生 crossSum=10 的情況是 digit[6]=7（7×4=28，2+8=10）
                if (crossSum === 10) {
                    crossSum = 0;
                }

                sum += crossSum;
            }

            // 對應 PHP:
            // $isLegal = ($sum % 5 === 0)
            //         || (($sum + 1) % 5 === 0 && (int)$eachChar[6] === 7)
            //
            // 情況一: crossSum=0（等效 crossSum=10，10 mod 5 = 0）→ sum % 5 === 0
            // 情況二: crossSum=1（遞迴 digit sum：1+0=1）→ (sum+1) % 5 === 0
            var digit6 = parseInt(taxId.charAt(6), 10);
            return (sum % 5 === 0) || ((sum + 1) % 5 === 0 && digit6 === 7);
        }
    };
});

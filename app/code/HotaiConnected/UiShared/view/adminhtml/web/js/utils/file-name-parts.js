/**
 * HotaiConnected UiShared - 檔名組合共用工具
 *
 * 依「前綴、中間、後綴」與分隔符組合成一個檔名字串。
 * 傳入單一 options 物件，未傳的欄位使用預設值，語意清楚。
 *
 * @module HotaiConnected_UiShared/js/utils/file-name-parts
 * @version 1.0.0
 * @author HotaiConnected
 *
 * 使用範例：
 *   getFileNameParts({ name: '例外授權表單' });
 *   getFileNameParts({ prefix: '20250319', name: '報表', suffix: 'v1', separator: '-' });
 */
define([
    'moment',
    'moment-timezone-with-data'
], function (moment) {
    'use strict';

    var defaultTimezone = 'Asia/Taipei';
    var defaultPrefixFormat = 'YYYYMMDD_HHmmss';
    var defaultSeparator = '_';

    /**
     * 將前綴、檔名主體、後綴用指定符號組合成檔名（空值會濾掉後再 join）
     *
     * @param {Object} options
     * @param {string|null|undefined} [options.prefix] - 前綴；不傳或空值時以當下台北時區時間產生
     * @param {string} options.name - 檔名主體（例如報表名稱、檔案標題）
     * @param {string} [options.suffix=''] - 後綴
     * @param {string} [options.separator='_'] - 組合時使用的分隔符
     * @returns {string} 組合後的檔名字串
     */
    function getFileNameParts(options) {
        options = options || {};
        var name = options.name;
        var separator = options.separator != null ? options.separator : defaultSeparator;
        var suffix = options.suffix != null ? options.suffix : '';
        var prefix = options.prefix;

        if (prefix == null || prefix === '') {
            prefix = moment().tz(defaultTimezone).format(defaultPrefixFormat);
        }

        return [prefix, name, suffix].filter(Boolean).join(separator);
    }

    return getFileNameParts;
});

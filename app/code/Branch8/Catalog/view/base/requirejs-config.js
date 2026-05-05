/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    paths: {
        // Checkmarx V9.4.5+ 需要看到 'dompurify' 模組名才能識別安全淨化
        'dompurify': 'js/purify.min',
        // 保留相容性，舊引用仍可使用
        'plugins/DOMPurify': 'https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.2.7/purify.min'
    },
    shim: {
        'dompurify': {
            exports: 'DOMPurify',
            init: function () {
                return window.DOMPurify;
            }
        }
    }
};

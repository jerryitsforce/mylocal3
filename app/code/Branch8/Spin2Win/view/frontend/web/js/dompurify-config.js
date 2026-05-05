define([], function () {
    'use strict';

    var defaultConfig = {
        ALLOWED_TAGS: ['b', 'i', 'u', 'em', 'strong', 'br', 'div', 'span', 'p'],
        ALLOWED_ATTR: ['class', 'style'],
        FORBID_TAGS: ['script', 'iframe', 'style', 'link', 'object', 'embed'],
        FORBID_ATTR: ['onerror', 'onload', 'onclick', 'onmouseover'],
        ALLOW_DATA_ATTR: false,
        SAFE_FOR_TEMPLATES: true
    };

    return function (overrides) {
        return Object.assign({}, defaultConfig, overrides || {});
    };
});

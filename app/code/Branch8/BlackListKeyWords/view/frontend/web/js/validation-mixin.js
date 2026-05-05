define(['jquery', './ahoCorasick'], function ($) {
    'use strict';
    let ahoCorasick = null;
    return function (targetWidget) {
        $.validator.addMethod(
            'validate-blacklist-words',
            function (value, element) {
                if (!window.blacklistWords) {
                    return true;
                }
                if (!ahoCorasick) {
                    ahoCorasick = new AhoCorasick(window.blacklistWords.split('|'));
                }
                const validator = this;
                const search = ahoCorasick.search(value);
                if (search.length) {
                    const result = search.flatMap(item => item[1]);
                    validator.blackWordMessage = $.mage.__('Your message contains restricted words (%1). Please revise it.')
                        .replace('%1', result.filter((value, index, array) => array.indexOf(value) === index).join(','))
                }
                return search.length === 0;
            },
            function () {
                return this.blackWordMessage;
            },
        )
        return targetWidget;
    }
});

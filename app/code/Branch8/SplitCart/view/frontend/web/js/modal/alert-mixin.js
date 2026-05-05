define(['jquery'], function ($) {
    'use strict';

    return function (originalAlert) {
        return function (config) {
            var content = typeof config === 'string' ? config : (config.content || '');
            
            // check msg for limit error
            if (content.indexOf('\u200B\u200B\u200B') !== -1) {
                var cleanMsg = content.replace(/\u200B\u200B\u200B/g, '');
                
                $(document).trigger('hotai:showLimitError', {msg: cleanMsg, isLimit: true});

                return;
            }

            return originalAlert(config);
        };
    };
});

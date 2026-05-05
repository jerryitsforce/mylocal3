define([
    'jquery',
    'Branch8_React/js/megamenu'
], function ($, megamenu) {
    'use strict';

    return function (config, element) {
        if (window.mountMegaMenu) {
            window.mountMegaMenu({
                dataUrl: config.dataUrl,
                elId: config.elId
            });
        } else {
            console.error('mountMegaMenu is not defined. Make sure React bundle is loaded.');
        }
    };
});

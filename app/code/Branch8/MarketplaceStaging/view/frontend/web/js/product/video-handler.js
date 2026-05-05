/*jshint browser:true jquery:true expr:true*/
define([
    "jquery",
    "mage/mage"
], function ($) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_MarketplaceStaging/js/product/video-handler': function (dataInfo) {
            $(function(){
                $('#stage-new-video').mage(
                    'newVideoDialog',
                    dataInfo
                );
            });
        }
    };
});

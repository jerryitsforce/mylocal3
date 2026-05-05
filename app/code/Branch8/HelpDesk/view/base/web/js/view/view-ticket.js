define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/translate',
    'mage/validation'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        isLoading: ko.observable(false),
        defaults: {
            template: 'Branch8_HelpDesk/view-ticket'
        },
        /**
         * Init
         */
        initialize: function () {
            this._super();
        },
        /**
         *
         * @param key
         */
        getTicketData: function (key) {
            return this.configuration.ticket[key];
        }
    });
});

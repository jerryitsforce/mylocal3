define([
    'ko',
    'jquery',
    'uiComponent'
], function (ko, $, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_Rma/form/reason'
        },
        reasons: window.reasons,
        initialize: function () {
            this._super();
            // console.log('reason rendering');
            // console.log(this.reasons)
            return this;
        }
    });
});
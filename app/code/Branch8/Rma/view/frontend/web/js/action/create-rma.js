define([
    'ko',
    'jquery',
    'uiComponent'
], function (ko, $, Component) {
    'use strict';

    const rmaData = window.rmaData;

    return Component.extend({
        defaults: {
            template: 'Branch8_Rma/form/rma-form'
        },

        createFormUrl: ko.observable(rmaData?.rmaUrl || ''),

        initialize: function () {
            this._super();
            // console.log('rma form', rmaData);
            this.triggerEvent();
            return this;
        },

        triggerEvent: function () {
            var self = this;
            // console.log('trigger event');
        }
    });
});
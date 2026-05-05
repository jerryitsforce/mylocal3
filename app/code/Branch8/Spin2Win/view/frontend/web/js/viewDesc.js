define([
    'ko',
    'uiComponent',
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer-info',
    'Branch8_Spin2Win/js/model/spin-data',
    'Magento_Ui/js/modal/modal',
    'spinwheelmain',
    'plugins/DOMPurify',
    'matchMedia'
], function (ko, Component, $, urlBuilder, customerData, customerInfomation, spinData, modal, spinwheelmain, DOMPurify) {
    'use strict';

    return Component.extend({
      data: spinData.data,
      isReady: ko.observable(false),
      winningDescription: ko.observable(''),

      initialize: function () {
          this._super();
      },

      initObservable: function () {
        const self = this;
        this._super();

        this.data.subscribe(function (newData) {
          console.log('Data updated:', newData);
          const info = newData?.info || null;
          if (info) {
            self.winningDescription(info.description || '');
          }
          self.isReady(true);
        });
        return this;
      }
    });
});

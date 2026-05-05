define([
    'ko',
    'uiComponent',
    'jquery',
    'Magento_Customer/js/customer-data',
    'Branch8_Spin2Win/js/model/spin-data',
    'Magento_Ui/js/modal/modal',
    'spinwheelmain',
    'plugins/DOMPurify',
    'mage/url',
    'matchMedia'
], function (ko, Component, $, customerData, spinData, modal, spinwheelmain, DOMPurify, urlBuilder) {
    'use strict';

    return Component.extend({
        productId: ko.observable(null),
        batches: ko.observableArray([]),
        isReady: ko.observable(false),
        
        initialize: function () {
          this._super();
          if(this.productId) {
            this.ajaxBatches();
          }
        },

        initObservable: function () {
          this._super();

          this.batches.subscribe(function (batches) {
            // console.log('batches changed:', batches);
          });

          return this;
        },

        ajaxBatches: function () {
          const self = this;
          $.ajax({
              type: "GET",
              url: urlBuilder.build('catalog/ajax/batches')+'/id/' + self.productId,
              dataType: "json",
              cache: false,
              beforeSend: function () {
                // $("body").trigger("processStart");
              },
              success: function (response) {
                self.isReady(true);
                if(!response.error) {
                  const batches = response.batches;
                  if (batches && batches.length) {
                    self.batches(batches);
                    self.selectFirstBatch();
                    $('#batches-label-' + self.productId).removeAttr('style');
                    $('#batches-option-' + self.productId).removeAttr('style');
                  }
                }
              },
              error: function (response) {
                self.isReady(true);
                console.error('callAjaxData error:', response);
              }
          }).always(function () {
            // console.log('callAjaxData always');
            // $("body").trigger("processStop"); 
          });
        },

        selectFirstBatch: function () {
          const self = this;
          $('#batches-option-'+self.productId).find('.batch-setting-id').each(function (index) {
            if(index == 0) {
              $(this).addClass('active');
              const batchCode = $(this).data('batch-code');
              const $select = $(this).parents('.control').find('select');
              self.selectBatch($select, batchCode);
            }
          });
        },
    
        selectBatch: function ($select, batchCode) {
          if($select.length) {
            var batchCodeStr = (batchCode || "").toString().trim();
            $select.find('option').each(function () {
              const optionText = $(this).text();
              if(optionText.trim() === batchCodeStr) {
                $select.val($(this).attr("value"));
                $select.trigger('change');
              }
            });
          }
        },

        selectedBatch: function (batch, event) {
          $('#batches-option-'+this.productId).find('.batch-setting-id').removeClass('active');
          $(event.currentTarget).addClass('active');
          const batchCode = batch.batch_code;
          const $select = $(event.currentTarget).parents('.control').find('select');
          this.selectBatch($select, batchCode);
        }
    });
});
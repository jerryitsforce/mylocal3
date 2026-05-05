define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'jquery/ui',
    'matchMedia',
    'mage/validation',
    'uiRegistry',
    'domReady!',
], function ($, modal, alertBox, urlBuilder ) {
    $.widget('b8.rmaModal', {
      options: {
        regionJson: {},
        orderId: null,
        modalId: 'modal-apply-rma-order'
      },

      _create: function () {
        // console.log('rma modal', this.options.regionJson)
        const self = this;

        var options = {
            type: 'popup',
            responsive: false,
            modalClass: 'modal-custom modal-full-popup apply-rma-order-popup',
            title: $.mage.__('Return or exchange application'),
            buttons: [
                {
                    text: $.mage.__('Cancel Apply Rma'),
                    class: 'action secondary action-dismiss close-modal-button',
                    click: function () {
                        this.closeModal();
                    }
                },
                {
                    text: $.mage.__('Apply Rma'),
                    class: 'action primary action-accept apply-rma-order-button disabled',

                    /** @inheritdoc */
                    click: async function () {
                        // console.log('click ok');
                        var form = $('#new_rma_form');
                        if (!(form.validation() && form.validation('isValid'))) {
                            return;
                        }

                        // let thisModal = this;
                        // console.log('form', form.serializeArray());
                        var formData = new FormData(form[0]);
                        const resolutionType = parseInt(formData.get('resolution_type') ?? 1);
                        // console.log( formData, resolutionType);
                        $('body').trigger('processStart');
                        $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").addClass('disabled');
                        $.ajax({
                          url: urlBuilder.build('reqrma/customer/create'),
                          type: 'POST',
                          data: formData,
                          async: true,
                          cache: false,
                          contentType: false,
                          processData: false,
                          success: function (response) {
                              $('body').trigger('processStop');
                              if (response.success) {
                                  alertBox({
                                      title: (resolutionType === 1 ? $.mage.__('Return request has been sent') : $.mage.__('Exchange request has been sent')),
                                      modalClass: 'parent-order-alert',
                                      content: `<div class='wk-mprma-success-content'>${$.mage.__(response.message)}</div>`,
                                      actions: {
                                          always: function () {
                                            window.location.href = urlBuilder.build('sales/parentOrder/history');
                                            //   location.reload();
                                          }
                                      },
                                      buttons: [{
                                          text: $.mage.__('I see'),
                                          class: 'action-primary action-accept',

                                          /**
                                           * Click handler.
                                           */
                                          click: function () {
                                            window.location.href = urlBuilder.build('sales/parentOrder/history')
                                            //   location.reload();
                                              this.closeModal(true);
                                          }
                                      }]
                                  });
                              } else {
                                    var error =  $.mage.__('Something went wrong.');
                                    $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").removeClass('disabled');
                                    if (response.message) {
                                        error = response.message;
                                    }
                                  alertBox({
                                      title: $.mage.__('RMA request failed'),
                                      modalClass: 'parent-order-alert',
                                      content: "<div class='wk-mprma-success-content'>"+ error +"</div>",
                                      buttons: [{
                                          text: $.mage.__('OK'),
                                          class: 'action-primary action-accept',

                                          /**
                                           * Click handler.
                                           */
                                          click: function () {
                                              this.closeModal(true);
                                          }
                                      }]
                                  });
                              }
                          },
                          error: function (response) {
                              $('body').trigger('processStop');
                              var error =  $.mage.__('Something went wrong.');
                              if (response.message) {
                                  error = response.message;
                              }
                              alertBox({
                                  title: $.mage.__('RMA request failed'),
                                  modalClass: 'parent-order-alert',
                                  content: "<div class='wk-mprma-warning-content'>" + error + "</div>",
                                  buttons: [{
                                      text: $.mage.__('OK'),
                                      class: 'action-primary action-accept',

                                      /**
                                       * Click handler.
                                       */
                                      click: function () {
                                          this.closeModal(true);
                                      }
                                  }]
                              });
                          }
                      });

                        // form.submit();
                        // thisModal.closeModal(true);
                        // $('body').trigger('processStart');
                    }
                }]
        };

        modal(options, $('#modal-apply-rma-order'));

        this.triggerEvent();

      },

      triggerEvent: function () {
        // click RMA button
        $(document).on('click', '.action-return-exchange-primary', function(){
            $('#modal-apply-rma-order').modal("openModal");
            // console.log('rma click');
        });
      }
  });

  return $.b8.rmaModal;
});

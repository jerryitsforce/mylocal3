// /**
//  * Copyright 2023 Adobe
//  * All Rights Reserved.
//  */
// define([
//     'jquery',
//     'Magento_Ui/js/modal/modal',
//     'Magento_Customer/js/customer-data',
//     'mage/validation'
// ],function ($, modal, customerData) {
//     'use strict';

//     return function (config, element) {
//         let order_id = config.order_id,

//     };
// });

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery/ui',
    'matchMedia',
    'mage/validation',
    'domReady!'
  ], function ($, modal) {
    $.widget('b8.cancelOrderModal', {
      options: {
        orderId: null,
        modalId: 'modal-confirm-delete'
      },
  
      _create: function () { 
        const self = this;  
        $(this.element).on('click', function () {
            $('#cancel_order_form').modal('openModal');
            $('#order_id').val(self.options.orderId);

            // reset form
            $('#reason').val('').addClass('empty');
            $('#reason_description').val('');
            $('.cancel-order-popup .cancel-order-button').addClass('disabled');
        });   

        var options = {
            type: 'popup',
            responsive: false,
            modalClass: 'modal-custom modal-full-popup cancel-order-popup',
            title: $.mage.__('Order Cancel Request'),
            buttons: [
                {
                    text: $.mage.__('Think Again'),
                    class: 'action secondary action-dismiss close-modal-button',
                    click: function () {
                        this.closeModal();
                    }
                },
                {
                    text: $.mage.__('Apply Cancel'),
                    class: 'action primary action-accept cancel-order-button disabled',

                    /** @inheritdoc */
                    click: function () {
                        var form = $('#cancel_order_form');
                        if (!(form.validation() && form.validation('isValid'))) {
                            return;
                        }

                        let thisModal = this;
                        // console.log('form', form.serializeArray());
                        form.submit();
                        thisModal.closeModal(true);
                        $('body').trigger('processStart');
                    }
                }]
        };

        modal(options, $('#cancel_order_form'));

        if($('#reason').val() == '') {
            $('#reason').addClass('empty');
        } else{
            $('#reason').removeClass('empty');
        }

        $('#reason').on('click', function () {
            $(this).removeClass('empty');
        });

        $('#reason').on('change', function () {
            const validate = $.validator.validateSingleElement($(this));
            // const reasonDesc  = $('#reason_description').val();
            if(!$(this).val() || $(this).val() == '') {
                $(this).addClass('empty');
                // console.log('ok')
            } else {
                $(this).removeClass('empty');
            }
            if (validate) {
                $('.cancel-order-popup .cancel-order-button').removeClass('disabled');
            } else {
                $('.cancel-order-popup .cancel-order-button').addClass('disabled');
            }
        });

        $('#reason_description').on('input change', function () {
            const validate = $.validator.validateSingleElement($(this));
            const reason  = $('#reason').val();
            if (validate && reason != '') {
                $('.cancel-order-popup .cancel-order-button').removeClass('disabled');
            } else {
                $('.cancel-order-popup .cancel-order-button').addClass('disabled');
            }
        });

        // validation
        
      }
  });
  
  return $.b8.cancelOrderModal;
  });
  
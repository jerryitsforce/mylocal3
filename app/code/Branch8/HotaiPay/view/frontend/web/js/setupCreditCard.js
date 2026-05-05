define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  $.widget('b8.setupCreditCard', {
    options: {
    },

    _create: function () {
      if (this.options.formId && this.options.formId === "setup_creditcard") {
        this.openCreditCardModal();
      } else {
        this.openQuickCreditCardModal();
      }
    },

    openCreditCardModal: function () {
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('綁定信用卡'),
        modalClass: 'modal-custom creadit-card-popup',
        buttons: [{
          text: $.mage.__('確定'),
          class: 'action primary action-primary',
          click: function () {
            // console.log("submit form");
            $("#" + self.options.formId).submit();
          }
        }]
      };
      var popup = modal(options, $("#" + this.options.modalId));
      const self = this;
      $("#" + this.options.modalTriggerBtnId).click(function () {
        $("#" + self.options.modalId).modal('openModal');
      });
    },

    openQuickCreditCardModal: function () {
      var options = {
        type: 'popup',
        responsive: false,
        modalClass: 'modal-custom quick-card-popup',
        title: $.mage.__('中信快速綁卡'),
        buttons: [{
          text: $.mage.__('取消'),
          class: 'action secondary action-secondary',
          id: "quick_card_submit",
          click: function () {
            this.closeModal();
          }
        },
        {
          text: $.mage.__('快速新增中信卡'),
          class: 'action primary action-primary disabled',
          click: function () {
            // console.log("submit form");
            $("#" + self.options.formId).submit();
          }
        }]
      };
      var popup = modal(options, $("#" + this.options.modalId));
      const self = this;
      $("#" + this.options.modalTriggerBtnId).click(function () {
        $("#" + self.options.modalId).modal('openModal');
      });

      $('#setup_creditcard_fast').mage('validation', {
        errorPlacement: function (error, element) {
            var parent = element.parent();
            if (parent.hasClass('control-field')) {
              if($(element).parents('.field').find('div.mage-error').length < 1) {
                error.insertAfter(element.parent().parent());
              } else {
                if(!$(element).parents('.field').find('div.mage-error').is(':visible')) {
                  $(element).parents('.field').find('div.mage-error').remove();
                  error.insertAfter(element.parent().parent());
                }
                // $(element).parents('.field').find('div.mage-error').attr('style', 'display: block');
              }
            } else {
                error.insertAfter(element);
            }
        }
      });

      $('#setup_creditcard_fast_id').change(function () {
        const validate =  $.validator.validateSingleElement($(this));
        if(validate && $("#setup_creditcard_fast_birthday").val() !== '') {
          $(this).closest(".modal-popup").find(".action.primary").attr('disabled', false);
          $(this).closest(".modal-popup").find(".action.primary").removeClass('disabled');
        } else {
          $(this).closest(".modal-popup").find(".action.primary").attr('disabled', true);
          $(this).closest(".modal-popup").find(".action.primary").addClass('disabled');
        }
      });
      $('input[name="birthdayTemp[]"').change(function () { 
        const validate =  $.validator.validateSingleElement($(this));
        if(validate) {
          $('input[name="birthdayTemp[]"').removeClass('mage-error');
          $(this).parents('.field').find('div.mage-error').remove();
          const date = "".concat($('#year').val(), $('#month').val(), $('#day').val());
      
          $("#setup_creditcard_fast_birthday").val(date);
          if($('#setup_creditcard_fast_id').val() !== '') {
            $(this).closest(".modal-popup").find(".action.primary").attr('disabled', false);
            $(this).closest(".modal-popup").find(".action.primary").removeClass('disabled');
          }
        } else {
          $('input[name="birthdayTemp[]"').addClass('mage-error');    
          $(this).closest(".modal-popup").find(".action.primary").attr('disabled', true);
          $(this).closest(".modal-popup").find(".action.primary").addClass('disabled');
        }
      });
      
    },
  });

  return $.b8.setupCreditCard;
});

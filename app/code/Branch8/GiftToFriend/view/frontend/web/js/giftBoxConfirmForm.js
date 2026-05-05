define([
  'jquery',
  'plugins/DOMPurify',
  'Branch8_GiftToFriend/js/dompurify-config',
  'mage/url',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, DOMPurify, getSanitizeConfig, url, modal) {
  $.widget('b8.giftBoxConfirmForm', {
    options: {
      countDownTimeBtnEle: '#confirming-countdown-time-btn',
      countDownTimeEle: '#giftbox-countdown-time',
      countDownEle: '.giftbox-confirm-countdown',
      countDownInfoEle: '.giftbox-confirm-coundown-info',
      countDownExpiredEle: '.giftbox-confirm-coundown-expired',
      btnSubmit: '#giftbox-confirm-submit-button',
      btnSmsConfirmSubmit: '#gift-confirm-send-code-button',
      formSelector: '#address-form',
      isVirtual: false,
      sendSMSActionUrl: '',
      formActionUrl: '',
      giftOrderConfirmationSuccessUrl: '',
      smsLeftTime: 1
    },

    _create: function () {
      // console.log('Gift Box Form Widget Initialized', this.options);

      $(this.options.btnSubmit).attr('disabled', "true");
      $(this.options.btnSubmit).addClass('disabled');

      this.initActions();
      this.initPopups();
    },

    initActions: function(){
      const self = this;
      // if(!this.options.isVirtual) {

      // } else {

      // }

      $(this.options.btnSmsConfirmSubmit).on('click', function(event){
        event.preventDefault();
        self.sendCode();
      });

      $(this.options.btnSubmit).on('click', function(event){
          event.preventDefault();
          self.validateForm();
      });

      $(document).on('change, input', '#name, #street, #region_id, #sms_code', function(){
          const validate = $.validator.validateSingleElement($(this));
          if (validate) {
            self.enableSubmit();
          } else {
            $(self.options.btnSubmit).attr('disabled', true);
            $(self.options.btnSubmit).addClass('disabled');
          }
      });

      $(document).on('change, input', '#phone_number', function(){
          const validate = $.validator.validateSingleElement($(this));
          if (validate) {
              self.enableSubmit();
          } else {
            $(self.options.btnSmsConfirmSubmit).attr('disabled', true);
            $(self.options.btnSmsConfirmSubmit).addClass('disabled');
            $(self.options.btnSubmit).attr('disabled', true);
            $(self.options.btnSubmit).addClass('disabled');
            console.log('disable submit');
          }
      });

      $(document).on('change, input', '#city_id', function(){
          const validate = $.validator.validateSingleElement($(this));
          if (validate) {
              self.enableSubmit();
          } else {
              $(this).val('');
              $(self.options.btnSubmit).attr('disabled', true);
              $(self.options.btnSubmit).addClass('disabled');
          }
      });
    },

    initPopups: function () {
      this.initSendingCodeLimitationPopup();
      this.initConfirmingFailedPopup();
    },

    initSendingCodeLimitationPopup: function () {
      var self = this;
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('驗證碼發送次數已達上限'),
        modalClass: 'modal-custom modal-giftbox sending-code-limitation',
        buttons: [{
          text: $.mage.__('我知道了'),
          class: 'action primary action-primary',
          click: function () {
              this.closeModal();
          }
        }]
      };

      const popup = modal(options, $('#modal-gift-sending-code-limitation'));
    },

    initConfirmingFailedPopup: function () {
      var self = this;
      var options = {
        type: 'popup',
        responsive: false,
        title: $.mage.__('驗證碼錯誤'),
        modalClass: 'modal-custom modal-giftbox failed-confirmation',
        buttons: [{
          text: $.mage.__('我知道了'),
          class: 'action primary action-primary',
          click: function () {
            const reload = $('#modal-gift-failed-confirmation').attr('data-reload') || false;
            this.closeModal();
            if(reload) {
              $('#modal-gift-failed-confirmation').removeAttr('data-reload');
              window.location.reload();
            }
          }
        }],
        closed: function (){
          const reload = $('#modal-gift-failed-confirmation').attr('data-reload') || false;
            if(reload) {
            $('#modal-gift-failed-confirmation').removeAttr('data-reload');
            window.location.reload();
          }
        }
      };

      const popup = modal(options, $('#modal-gift-failed-confirmation'));
    },

    triggerSendingCodeLimitationPopup: function (open = true) {
      if($('#modal-gift-sending-code-limitation').data('mageModal')) {
        if (open) {
          $('#modal-gift-sending-code-limitation').modal('openModal');
        } else {
          $('#modal-gift-sending-code-limitation').modal('closeModal');
        }
      }
    },

    triggerConfirmingFailedPopup: function (open = true) {
      if($('#modal-gift-failed-confirmation').data('mageModal')) {
        if (open) {
          $('#modal-gift-failed-confirmation').modal('openModal');
        } else {
          $('#modal-gift-failed-confirmation').modal('closeModal');
        }
      }
    },

    validateForm: function () {
        const isValid = $("#address-form").validation() && $("#address-form").validation('isValid');
        if(isValid){
            $(this.options.btnSubmit).attr('disabled', "true");
            $(this.options.btnSubmit).addClass('disabled');
            this.processAction();
        } else {
            $(this.options.btnSubmit).attr('disabled', "true");
            $(this.options.btnSubmit).addClass('disabled');
            return;
        }
    },

    enableSubmit: function() {
        var name = $('#name').val();
        var phone = $('#phone_number').val();
        var regionId = $('#region_id').val();
        var cityId = $('#city_id').val();
        var street = $('#street').val();
        var code = $('#sms_code').val();

        // console.log({a: this.options.isVirtual, name, phone, regionId, cityId, street, code});

        if(phone && $(this.options.btnSmsConfirmSubmit).attr('isCountDown') !== 'true') {
          $(this.options.btnSmsConfirmSubmit).removeAttr('disabled');
          $(this.options.btnSmsConfirmSubmit).removeClass('disabled');
        }

        if(this.options.isVirtual) {
          if(name && phone && code && $(this.options.btnSubmit).attr('sended') === "true"){
            $(this.options.btnSubmit).removeAttr('disabled');
            $(this.options.btnSubmit).removeClass('disabled');
            $(this.options.formSelector + ' button[type="submit"]').attr('disabled', false);
          } else{
            $(this.options.btnSubmit).attr('disabled', true);
            $(this.options.btnSubmit).addClass('disabled');
            if(name && phone && regionId && cityId && street && code) {
              this.showMessage('Please clicking to send verification code', 'error');
            }
          }
        } else {
          if(name && phone && regionId && cityId && street && code && $(this.options.btnSubmit).attr('sended') === "true"){
            if ($("#address-form").validation('isValid')) {
              $(this.options.btnSubmit).removeAttr('disabled');
              $(this.options.btnSubmit).removeClass('disabled');
              $(this.options.formSelector + ' button[type="submit"]').attr('disabled', false);
            } else {
              $(this.options.btnSubmit).attr('disabled', true);
              $(this.options.btnSubmit).addClass('disabled');
            }
          } else{
            $(this.options.btnSubmit).attr('disabled', true);
            $(this.options.btnSubmit).addClass('disabled');
            if(name && phone && regionId && cityId && street && code) {
              this.showMessage('Please clicking to send verification code', 'error');
            }
          }
        }


    },

    sendCode: function () {
      const self = this;
      const phone = $('#phone_number').val();
      const countDownExpiredEle = $(this.options.countDownExpiredEle);
      countDownExpiredEle.removeClass('show');

      if (!phone) {
          this.showMessage('請輸入手機號碼', 'error');
          return;
      }

      var giftConfirmOrderCode = $('#order_code').val();
      $.ajax({
        url: this.options.sendSMSActionUrl,
        type: 'POST',
        data: { phone_number: phone, order_code:  giftConfirmOrderCode},
        dataType: 'json',
        beforeSend: function () {
          $(document.body).trigger('processStart');
          $(self.options.btnSubmit).removeAttr('data-code');
        },
        success: function (res) {
          // console.log('Response:', res);
          if(res.success) {
            $(self.options.btnSubmit).attr('sended', true);
            $(self.options.btnSubmit).attr('data-code', res.smsCode);
            self.showCountdown();
            $("#sms_code").removeAttr('disabled');
          } else {
            $(document.body).trigger('processStop');
            if(res?.message_code === 'limitReached') {
              self.triggerSendingCodeLimitationPopup(true);
            } else {
              self.showMessage(res.message, 'error');
              $(self.options.btnSubmit).removeAttr('sended');
              $(self.options.btnSubmit).removeAttr('data-code');
            }
            $("#sms_code").attr('disabled', 'disabled');
          }
        }.bind(this),
        error: function (error) {
          $(document.body).trigger('processStop');
          // console.error('Sending SMS Code Error:', error);
          self.triggerSendingCodeLimitationPopup(true);
          $("#sms_code").attr('disabled', 'disabled');
        }.bind(this)
      }).always(function () {
        $(document.body).trigger('processStop');
      });
    },

    processAction: function () {
      const form = $(this.options.formSelector);
      const self = this;

      const formData = form.serializeArray();
      $.ajax({
        url: this.options.formActionUrl,
        data: formData,
        type: 'POST',
        dataType: 'json',
        beforeSend: function () {
          $(document.body).trigger('processStart');
        },
        success: function (res) {
          // console.log('Response:', res);
          if(!res.success){
            const reload = res?.reload || false;
            self.triggerConfirmingFailedPopup();
            $('#modal-gift-failed-confirmation').attr('data-reload', reload);
            if(reload) {
              $('#giftConfirmingFailMsg').hide();
              $('#giftConfirmingReloadMsg').text(res?.message || '').show();
            }
            $('#sms_code').val('');
            $(self.options.btnSubmit).attr('disabled', true);
            $(self.options.btnSubmit).addClass('disabled');
            $(self.options.formSelector + ' button[type="submit"]').attr('disabled', true);
            // self.showMessage(res.message);
          } else {
            window.location.href = self.options.giftOrderConfirmationSuccessUrl
          }
        },
        error: function (error) {
          console.error('Error processing address form:', error);
          $(self.options.btnSubmit).removeAttr('disabled');
          $(self.options.btnSubmit).removeClass('disabled');
          $(self.options.formSelector + ' button[type="submit"]').attr('disabled', false);
        }
      }).always(function () {
        $(document.body).trigger('processStop');
      });
    },

    showMessage: function (message, type = 'error') {
      var jQ = $.noConflict();
      var msgContainer = jQ('.page.messages');
      var sanitizeConfig = getSanitizeConfig();

      var messageDiv = document.createElement('div');
      messageDiv.className = 'message ' + (type === 'success' ? 'message-success success' : 'message-error error');

      var fragment = DOMPurify.sanitize(message, $.extend({}, sanitizeConfig, {
        RETURN_DOM_FRAGMENT: true
      }));

      if (fragment && fragment.nodeType === 11 && fragment.hasChildNodes()) {
        messageDiv.appendChild(fragment);
      } else {
        var sanitizedText = DOMPurify.sanitize(message, sanitizeConfig);
        messageDiv.appendChild(document.createTextNode(sanitizedText));
      }

      var wrapper = document.createElement('div');
      wrapper.classList.add('messages', 'custom-messages');
      wrapper.appendChild(messageDiv);

      msgContainer.append(wrapper);
      msgContainer.addClass('__show');

      var timeCheck;
      clearTimeout(timeCheck);
      timeCheck = setTimeout(function () {
        msgContainer.removeClass('__show');
        msgContainer.find('.custom-messages').remove();
      }, 3000);
    },

    showCountdown: function () {
      this.displayCountDownMsg(true);
      this.countdown();
    },

    countdown: function () {
      const self = this;
      const countdownTimeEle = $(this.options.countDownTimeEle);
      const countDownTimeBtnEle = $(this.options.countDownTimeBtnEle);
      const countDownExpiredEle = $(this.options.countDownExpiredEle);
      const countDownInfoEle = $(this.options.countDownInfoEle);
      let seconds = parseInt(this.options.smsLeftTime) * 60;
      const interval = setInterval(function () {
        seconds--;
        if (seconds <= 0) {
          clearInterval(interval);
          // show expired message, allow to send again
          countDownExpiredEle.addClass('show');
          countDownInfoEle.removeClass('show');
          self.displayCountdownTimeBtn(false);
          // hide expired message after 3 seconds
          // setTimeout(function () {
          //   countDownExpiredEle.removeClass('show');
          // }, 3000);
        } else {
          // update countdown time
          const min = Math.floor(seconds / 60);
          const sed = seconds % 60;
          const text = (min > 0 ? (min < 10 ? '0' + min : min) + ':' : '') + (sed < 10 ? '0' + sed : sed);

          countdownTimeEle.text(text);
          countDownExpiredEle.removeClass('show');
          countDownInfoEle.addClass('show');
          countDownTimeBtnEle.text(text);
          self.displayCountdownTimeBtn(true);
        }
      }, 1000);
    },

    displayCountdownTimeBtn: function (status = true) {
      const btnSmsConfirmSubmit = $(this.options.btnSmsConfirmSubmit);
      // status = true ? 'show' : 'hide';
      if(status) {
        btnSmsConfirmSubmit.addClass('sent');
        btnSmsConfirmSubmit.attr('disabled', 'disabled');
        btnSmsConfirmSubmit.addClass('disabled');
        btnSmsConfirmSubmit.attr('isCountDown', 'true');
      } else {
        btnSmsConfirmSubmit.removeClass('sent');
        btnSmsConfirmSubmit.removeAttr('disabled');
        btnSmsConfirmSubmit.removeClass('disabled');
        btnSmsConfirmSubmit.removeAttr('isCountDown');
      }
    },

    displayCountDownMsg: function (status = true) {
      // status = true ? 'show' : 'hide';
      const countDownEle = $(this.options.countDownEle);
      if(status) {
        countDownEle.addClass('show');
      } else {
        countDownEle.removeClass('show');
      }
    }

  });

  return $.b8.giftBoxConfirmForm;
});

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery/ui',
    'domReady!'
], function ($, modal) {
    'use strict';
    $.widget('b8.giftActions', {
      _create: function () {
          console.log('Gift Actions Widget Initialized', this.element, this.options);
          this._bindEvents();
      },

      _bindEvents: function () {
        const self = this;
        $(document).on('click', '#confirmLinkCopy', function (event) {
          event.preventDefault();
          const link = $(this).data('clipboard-text');
          self.copyCouponCode(link);
          $("#step2InProgress").addClass('hide');
          $("#step2Finished").addClass('show');
        });

        $(document).on('click', '#step2InProgress', function (event) {
          event.preventDefault();
          const link = $(this).data('clipboard-text');
          self.copyCouponCode(link);
          $("#step2InProgress").addClass('hide');
          $("#step2Finished").addClass('show');
        });

        var options = {
          type: 'popup',
          responsive: false,
          title: $.mage.__('記得把連結發給對方喔！'),
          modalClass: 'modal-custom modal-order-success-gift-box-confirm',
          buttons: [{
            text: $.mage.__('我知道了'),
            class: 'action primary action-primary',
            click: function () {
              if($('#spin-trigger-btn').hasClass('clicked')) {
                $('#spin-trigger-btn').trigger('click');
              } else if($('.dropdown.customer-account .dropdown-toggle').hasClass('clicked')) {
                $('.dropdown.customer-account .dropdown-toggle').trigger('click');
              } else if($('.magenest-notification-action').hasClass('clicked')) {
                $('.magenest-notification-action').trigger('click');
              } else if($('.magenest-notification-icon').hasClass('clicked')) {
                $('.magenest-notification-icon').trigger('click');
              } else if ($('#search_mini_form').length && $('#search_mini_form').hasClass('clicked')) {
                $('#search_mini_form').trigger('submit');
              } else {
                var $clickedLink = $('a.clicked-link');
                var href = $clickedLink? $clickedLink.attr('href') : null;
                if (href) {
                  window.location.href = href;
                }
              }
              this.closeModal();
            }
          }],
          closed: function () {
            console.log('Modal is closed!');
            $('#spin-trigger-btn').removeClass('clicked');
            $('.dropdown.customer-account .dropdown-toggle').removeClass('clicked');
            $('.magenest-notification-action').removeClass('clicked');
            $('.magenest-notification-icon').removeClass('clicked');
            $('#search_mini_form').removeClass('clicked');
          }
        };

        modal(options, $('#order-success-gift-box-confirm-modal'));

        $('#order-success-gift-box-confirm-modal').on('modalclosed', function () {
            $('a.clicked-link').removeClass('clicked-link');
        });

        // window.addEventListener('pagehide', function () {
        //   console.log('User left the onepage/success.');
        //   $('#order-success-gift-box-confirm-modal').modal('openModal');
        // });

        $(document).on('click', 'a', function (event) {
          var target = $(this).attr('target');
          if ($(this).hasClass('clicked-link') || target === '_blank') {
            return;
          }
          event.preventDefault();
          $(this).addClass('clicked-link');
          $('#order-success-gift-box-confirm-modal').modal('openModal');
        });
      },

      copyCouponCode: function (link) {
        this.copyCode(link);
      },

      copyCode: function (code) {
        const self = this;
        if (navigator.clipboard && window.isSecureContext) {
            // ✅ Modern Clipboard API
            navigator.clipboard.writeText(code)
              .then(() => {
                self.showSuccess();
              })
              .catch(err => {
                console.error('Spin2Win View: Failed to copy coupon code:', err);
                // self.showMessage($.mage.__('Something went wrong.'), 'error');
              });
          } else {
            // 🔙 Fallback for older browsers
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(code).select();

            try {
              const successful = document.execCommand('copy');
              if(successful) {
                self.showSuccess();
              } else {
                console.error('Spin2Win View: Fallback copy failed');
              }
            } catch (err) {
              console.error('Spin2Win View: Fallback copy failed', err);
            }

            $temp.remove();
          }
      },

      showSuccess: function (message = '') {
        if (message == '') {
          message = $.mage.__('已複製');
        }
        this.showMessage(message, 'success');
      },

      showMessage: function (message, type = 'error') {
        var msgContainer = $('.page.messages');
        msgContainer.append('<div class="messages custom-messages"><div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'">' + message + '</div></div>');
        msgContainer.addClass('__show');
        var timeCheck;
        clearTimeout(timeCheck);
        timeCheck = setTimeout(function () {
          msgContainer.removeClass('__show');
          msgContainer.find('.custom-messages').remove();
        }, 3000);
      }
    });

    return $.b8.giftActions;
});

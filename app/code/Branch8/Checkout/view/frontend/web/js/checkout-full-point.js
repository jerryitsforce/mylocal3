define([
  'jquery',
  'jquery/ui',
  'plugins/DOMPurify',
  'mage/translate',
  'mage/url',
  'matchMedia',
  'domReady!'
], function ($, ui, DOMPurify, $t, urlBuilder) {

  $.widget('b8.checkoutFullpoint', {
    options: {
      duration: 5, // seconds
      cancelUrl: '',
      processUrl: '',
      productUrl: '',
      checkoutSuccessUrl: '',
      checkoutFailureUrl: ''
    },

    _create: function () {
      var jQ = $.noConflict();
      var self = this;
      $('.fullpoint-cancel-payment').prop('disabled', false).removeClass('disabled');
      this.triggerCancelPayment();
      this.countDownAction();
    },

    triggerCancelPayment: function () {
      var self = this;
      $(document).on('click', '.fullpoint-discount', function (e) {
        e.preventDefault();
        $(this).toggleClass('-collapsed');
      });

      $(document).on('click', '.fullpoint-cancel-payment', function (e) {
        e.preventDefault();

        const $button = $(this);

        // Prevent double click
        if ($button.prop('disabled')) {
            return;
        }

        $button.prop('disabled', true).addClass('disabled');
        $button.addClass('clicked-disabled');
        self.countDownAction(true);

        $.ajax({
            url: self.options.cancelUrl,
            method: 'POST',
            dataType: 'json'
        })
        .done(function (response) {
          window.location.href = self.options.checkoutFailureUrl;
            // if (response && response.success && self.options.productUrl !='' ) {
            //     window.location.href = self.options.productUrl;
            // } else {
            //   // show success message or handle failure
            // }
        })
        .fail(function () {
          window.location.href = self.options.checkoutFailureUrl;
        });
      });
    },

    countDownAction: function (stop = false) {
      var self = this;  
      var DURATION = this.options.duration;
      var STORAGE_KEY = 'b8_countdown_end_time';
      var $el = $("#fullpoint_cancel_countdown");
      
      var endTime = localStorage.getItem(STORAGE_KEY);

      if (!endTime) {
          endTime = Date.now() + DURATION * 1000;
          localStorage.setItem(STORAGE_KEY, endTime);
      }

      if(stop) {
        clearInterval(timer);
        localStorage.removeItem(STORAGE_KEY);
        return;
      }

      function updateCountdown() {
          var timeLeft = Math.ceil((endTime - Date.now()) / 1000);

          if (timeLeft > 0 && !$('.fullpoint-cancel-payment').hasClass('clicked-disabled')) {
              $el.text(timeLeft);
          } else if($('.fullpoint-cancel-payment').hasClass('clicked-disabled')) {
              clearInterval(timer);
              localStorage.removeItem(STORAGE_KEY);
          } else {
              $el.text(0);
              $('.fullpoint-cancel-payment').prop('disabled', true).addClass('disabled');
              self.triggerProcessPayment();
              clearInterval(timer);
              localStorage.removeItem(STORAGE_KEY);
          }
      }

      updateCountdown();
      var timer = setInterval(updateCountdown, 1000);
    },

    triggerProcessPayment: function () {
      var self = this;

      if(!self.options.processData || !self.options.processData.paymentMethod || !self.options.processData.billingAddress || !self.options.processData.cartId) {
        console.error('missing processData');
        return;
      }

      if(self.options.checkoutSuccessUrl === '' || self.options.checkoutFailureUrl === '') {
        console.error('missing checkoutSuccessUrl or checkoutFailureUrl');
        return;
      }

      $.ajax({
          url: urlBuilder.build('rest/V1/carts/mine/payment-information'),
          method: 'POST',
          contentType: 'application/json',
          dataType: 'json',
          data: JSON.stringify(self.options.processData)
      })
      .done(function (response) {
          console.log('Payment successful:', response);
          window.location.href = self.options.checkoutSuccessUrl;
          // Handle successful payment here
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
          console.error('Payment request failed:', textStatus, errorThrown, jqXHR.responseJSON);
          window.location.href = self.options.checkoutFailureUrl;
      })
      .always(function () {
          // Optional: re-enable buttons or hide loader
      });
    }

  });

  return $.b8.checkoutFullpoint;
});

define([
    'jquery',
    'jquery/ui',
    'matchMedia',
    'mage/validation',
    'domReady!'
], function ($) {
    $.widget('b8.b8Repayment', {
        options: {},

        _create: function () {
           console.log('repayment widget created');
            this.actions();
        },
        
        actions: function () {
            var self = this;
            var returnBtn = $('#returnBtn');
            returnBtn.on('click', function (e) {
               e.preventDefault();
               var url = returnBtn.data('redirect');
               if (url) {
                   window.location.href = url;
               }
            });

            var processBtn = $('#processBtn');
            processBtn.on('click', function (e) {
                e.preventDefault();
                var payment_method = $('#payment_method_hotaipay').val();
                var credit_card = $('input[name="payment[creditcard_list]"]:checked').val();
                console.log('processBtn clicked', {payment_method, credit_card});

                if (!payment_method || !credit_card) {
                    self.showMessage($.mage.__('請選擇付款方式與信用卡'));
                    return false;
                }
                $('#repayment_paymentMethod').val(payment_method);
                $('#repayment_creditCardId').val(credit_card);
                $('#repayment').submit();
            });
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

    return $.b8.b8Repayment;
});

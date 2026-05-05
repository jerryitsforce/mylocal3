define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  
  $.widget('b8.memberHotaiPay', {
    options: {
    },

    _create: function () {        
      // console.log('memberHotaiPay.js loaded');
      if($('.member-hotaipay-cards').length > 0 && $('.member-hotaipay-binding-label').length > 0) {
        $('.member-hotaipay-binding-label').addClass('active');
      }

      $('.member-hotaipay-card-list-header, #member_card_close_btn').on('click', function() {
        $(this).parent().toggleClass('show');
      });

      $('.member-hotaipay-bottom').addClass('fixed');
    //   $(window).scroll(function() {
    //     let closetimer = 700;
    //     function canceltimer () {
    //       if(closetimer) {
    //           window.clearTimeout(closetimer);
    //           closetimer = null;
    //       }
    //     }

    //     function timer () {
    //         closetimer = window.setTimeout(closeAction, closetimer);
    //     }

    //     function closeAction() {
    //       console.log('scrolling', {a: $(window).scrollTop(), aa: window.scrollY, windo: window.innerHeight(), aaa: $('.member-hotaipay-bottom').offset(), b: $('.member-hotaipay-bottom').offset().top});
    //       if ($(window).scrollTop() <= $('.member-hotaipay-bottom').offset().top)
    //        {
    //           $('.member-hotaipay-bottom').addClass('fixed');
    //        }
    //       else
    //        {
    //         $('.member-hotaipay-bottom').removeClass('fixed');
    //        }
    //     }
        
    //     canceltimer();
    //     timer();
    //  });
    }
});

return $.b8.memberHotaiPay;
});

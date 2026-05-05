define([
  'jquery',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, modal) {
  
  $.widget('b8.hotaiPayMember', {
    options: {
    },

    _create: function () {        
      $(document).on('click', '.creditcard-member-table .action.action-delete', function () {
        $('.creditcard-member-table .action.action-delete').removeClass('deleted');
        $(this).addClass('deleted');
        $('#modal-credit-card-delete').modal('openModal');
      });
    }
});

return $.b8.hotaiPayMember;
});

define([
  'jquery',
  'plugins/DOMPurify',
  'Magento_Customer/js/customer-data',
  'Magento_Ui/js/modal/modal',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, DOMPurify, customerData) {
  $.widget('b8.giftListing', {
    options: {
      giftBoxItemActionEle: '.giftbox-listing-action-view-full',
      giftBoxViewFullBtn: '.giftbox-listing-action-view-full .action.view-full',
      giftBoxViewLessBtn: '.giftbox-listing-action-view-full .action.view-less',
      giftBoxActionEle: '.giftbox-listing-actions',
      loadMoreBtn: '#giftbox-listing-more-button',
      copyBtn: '.copy-button'
    },

    _create: function () {
      this._initGiftListing();
      this._bindEvents();
    },

    _initGiftListing: function () {
      // Initialize gift listing functionality
      console.log('Gift Listing Initialized');
    },

    _bindEvents: function () {
      var self = this;

      // $(document).on('click', this.options.loadMoreBtn, function (event) {
      //   event.preventDefault();
      //   self.processLoadMore();
      // });

      $(document).on('click', this.options.giftBoxViewFullBtn, function (event) {
        event.preventDefault();
        $(this).parents('.giftbox-listing-order-item').addClass('expanded');
      });

      $(document).on('click', this.options.giftBoxViewLessBtn, function (event) {
        event.preventDefault();
        $(this).parents('.giftbox-listing-order-item').removeClass('expanded');
      });

      $(document).on('click', this.options.copyBtn, function (event) {
        event.preventDefault();
        var trackingNumber = $(this).data('tracking-number');
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(trackingNumber).select();
        document.execCommand("copy");

        setTimeout(function () {
            customerData.set('messages', {
                messages: [{
                    text: '已複製物流編號',
                    type: 'success'
                }]
            });
        }, 500);
      });
    },

    processLoadMore: function () {
      var self = this;
      console.log('Processing load more items');
      // if (data && data.items && data.items.length > 0) {
      //   $.each(data.items, function (index, item) {
      //     var itemHtml = '<div class="giftbox-item">' + DOMPurify.sanitize(item.html) + '</div>';
      //     $(self.options.giftBoxItemActionEle).append(itemHtml);
      //   });
      // } else {
      //   console.log('No more items to load');
      // }
    }

  });

  return $.b8.giftListing;
});

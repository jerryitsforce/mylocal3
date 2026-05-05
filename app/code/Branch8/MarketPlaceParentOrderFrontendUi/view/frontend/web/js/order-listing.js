define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'plugins/rollDate',
    'Magento_Customer/js/customer-data',
    'Branch8_Security/js/security-utils',
    'jquery/ui',
    'domReady!'
], function ($, $t, modal, rollDate, customerData, securityUtils) {
    'use strict';
    $.widget('b8.orderListing', {
      _create: function () {
          console.log('Order Listing Widget Initialized', this.element, this.options);
          this._bindEvents();
          this.collapseOrderItem();
          this.collapseSearchItemByDefault();
          this._initDateScroll();
      },

      _bindEvents: function () {
        const self = this;
        $(document).on('click', '.action-copy-confirm-url', function (event) {
          event.preventDefault();
          const link = $(this).data('clipboard-text');
          self.copyCouponCode(link);
        });

        $('.copy-button').click(function (e) {
            var progressUrl = $(this).data('progress-url');
            if (!progressUrl) {
                e.preventDefault();
            }
            const trackingNumber = $(this).data('tracking-number');
            const isHotaiApp = $('body').hasClass('hotai-app')

            if (isHotaiApp && window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(JSON.stringify({
                    type: 'COPY_TO_CLIPBOARD',
                    data: trackingNumber
                }));
            } else {
                var $temp = $("<input>");
                $("body").append($temp);
                $temp.val(trackingNumber).select();
                document.execCommand("copy");
            }

            setTimeout(function () {
                customerData.set('messages', {
                    messages: [{
                        text: '已複製物流編號',
                        type: 'success'
                    }]
                });
            }, 500);
        });


        let options = {
            type: 'popup',
            title: $.mage.__('訂單篩選'),
            modalClass: 'modal-order-filter',
            responsive: true,
            innerScroll: true,
            autoOpen: true,
            buttons: [
                {
                    text: $.mage.__('Order Re-filter'),
                    class: 'action secondary re-filter',
                    click: function () {
                        self._resetFilterForm();
                    }
                },
                {
                    text: $.mage.__('Order Filter Confirm'),
                    class: 'action primary confirm',
                    click: function () {
                        if($('#account-board-root').find('.order-management-header').length){
                            window.submitOrderStatusForm();
                        }else{
                            $('#order-status-form').submit();
                        }
                        this.closeModal();
                    }
                }],

        };

        $(document).on('click', ".btn-filter, .change-filter", function () {
            let popup = modal(options, $('#popup-order-filter'));
            $("#popup-order-filter").modal("openModal");
        });



        // Search functionality
        $('#order-search').on('input', function () {
            // var searchValue = $(this).val().toLowerCase();
            // var hasResults = false;

            // $('.search-in-filter').val($(this).val());

            // $('.order-item').each(function () {
            //     var orderNumber = $(this).find('.order-number').text().toLowerCase();
            //     var productName = $(this).find('.product-name').text().toLowerCase();

            //     if (orderNumber.includes(searchValue) || productName.includes(searchValue)) {
            //         $(this).show();
            //         hasResults = true;
            //     } else {
            //         $(this).hide();
            //     }
            // });

            // if (!hasResults) {
            //     $('.order-results.order-empty').show();
            // } else {
            //     $('.order-results.order-empty').hide();
            // }
        });



        // Event listener for the close buttons
        $(document).on('click', '.filter-item .clear-filter', function () {
            var filterParam = $(this).closest('.filter-item').data('filter');
            if(!$('#account-board-root').find('.order-management-header').length){
                self._removeFilter(filterParam);  // Call the function to remove the filter and reload the page
            }
            $(this).closest('.filter-item').remove();
        });

        // Event handler for the search form submission
        $('.form-search-order').on('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            self._mergeQueryParams(this); // Merge query params with form data
        });

        // Event handler for the filter form submission
        $('#order-status-form').on('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            self._mergeQueryParams(this); // Merge query params with form data
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
      },

      collapseSearchItemByDefault: function(){
        console.log('_collapseOrderItem');
        // if parent_id is set in the URL, then expand the order details
        let searchParams = window.location.search;
        if($('#account-board-root').find('.order-management-header').length){
            searchParams = $('#account-board-root').attr('data-filtered');
        }
        const urlParams = new URLSearchParams(searchParams);
        const parentNumber = urlParams.get('search');
        if (parentNumber) {
            const $orderItem = $(`.order-item[data-parent-number="${parentNumber}"]`);
            const $orderDetails = $orderItem.find('.order-detail-container');
            const $collapseBtn = $orderItem.find('.collapse-control span');
            const $orderHeaderProductImage = $orderItem.find('.suborder-product-image-container');
            const $orderHeader = $orderItem.find('.order-header');

            $orderDetails.toggleClass('no-display');
            $orderHeader.toggleClass('no-border-bottom');

            if ($orderDetails.hasClass('no-display')) {
                $collapseBtn.text('詳情');
                $orderItem.find('.collapse-control').addClass('collapsed');
                $orderHeaderProductImage.removeClass('no-display');
            } else {
                $collapseBtn.text('收合');
                $orderItem.find('.collapse-control').removeClass('collapsed');
                $orderHeaderProductImage.addClass('no-display');
            }
        }
      },

      collapseOrderItem: function(){
        $(document).on('click', '.collapse-control', function () {
            console.log('onn click collapse-control');
            var $orderDetails = $(this).closest('.order-item').find('.order-detail-container');
            var $collapseBtn = $(this).find('span');
            var $orderHeaderProductImage = $(this).closest('.order-item').find('.suborder-product-image-container');
            var $orderHeader = $(this).closest('.order-item').find('.order-header');

            $orderDetails.toggleClass('no-display');
            $orderHeader.toggleClass('no-border-bottom');

            if ($orderDetails.hasClass('no-display')) {
                $collapseBtn.text('詳情');
                $(this).addClass('collapsed');
                $orderHeaderProductImage.removeClass('no-display');
            } else {
                $collapseBtn.text('收合');
                $(this).removeClass('collapsed');
                $orderHeaderProductImage.addClass('no-display');
            }
        });
      },

      _initDateScroll: function(){
        new rollDate({
          el: '#start-time',
          format: 'YYYY-MM',
          beginYear: 2020,
          endYear: new Date().getFullYear(),
          // value: "2024-09",
          lang: {
              title: '<div class="header-title">From Date</div>',
              confirm: '確定',
              year: '',
              month: ''
          },
          mouseWheel: {
            speed: 100,
            invert: false,
            easeTime: 400
            },
          customClass: 'filter-date start-date',

          init2: function () {
              setTimeout(function () {
                  let rollDateContainer = $(".rolldate-container");
                  $('#start-time').parents(".input-control").find(".date-container").append(rollDateContainer);
              }, 100)
          },

          init3: function () {
              if($(window).width() > 768){
                  setTimeout(function() {
                      let rollPanel = $(".rolldate-container");
                      rollPanel.addClass("filter-date start-date");
                      // let top = $("#start-time").offset().top;
                      // let left = $("#start-time").offset().left;
                      // $(".rolldate-container .rolldate-panel").css("top", top + 50);
                      // $(".rolldate-container .rolldate-panel").css("left", left);
                      // $(".rolldate-container .rolldate-panel").css("width", 150);
                  }, 150);
              }
          },

          confirm: function (date) {
              $("#start-time").val(date);
              $('.date-container').find(".rolldate-container").remove();
          },
          cancel: function () {
              $('.date-container').find(".rolldate-container").remove();
          },
          hide: function () {
              $('.date-container').find(".rolldate-container").remove();
          }
        });

        new rollDate({
          el: '#end-time',
          format: 'YYYY-MM',
          beginYear: 2020,
          endYear: new Date().getFullYear(),
          lang: {
              title: '<div class="header-title">To Date</div>',
              confirm: '確定',
              year: '',
              month: ''
          },
          mouseWheel: {
            speed: 100,
            invert: false,
            easeTime: 400
            },
          customClass: 'filter-date end-date',

          init2: function () {
              setTimeout(function () {
                  let rollDateContainer = $(".rolldate-container");
                  $('#end-time').parents(".input-control").find(".date-container").append(rollDateContainer);
              }, 200)
          },

          init3: function () {
              if($(window).width() > 768){
                  setTimeout(function() {
                      let rollPanel = $(".rolldate-container");
                      rollPanel.addClass("filter-date end-date");
                      // let top = $("#end-time").offset().top;
                      // let left = $("#end-time").offset().left;
                      // $(".rolldate-container .rolldate-panel").css("top", top + 100);
                      // $(".rolldate-container .rolldate-panel").css("left", left);
                      // $(".rolldate-container .rolldate-panel").css("width", 150);
                  }, 150);
              }
          },

          confirm: function (date) {
              $("#end").val(date);
              $('.date-container').find(".rolldate-container").remove();
          },
          cancel: function () {
              $('.date-container').find(".rolldate-container").remove();
          },
          hide: function () {
              $('.date-container').find(".rolldate-container").remove();
          }
        });
      },

      _mergeQueryParams: function(form){
        // Get the current URL and query params
        var currentUrl = new URL(window.location.href);
        var searchParams = new URLSearchParams(currentUrl.search);

        // Get form data
        var formData = new FormData(form);
        formData.forEach(function(value, key) {
            // Add or update the form data to the query parameters
            searchParams.set(key, value);
        });

        // Update the form action with the merged query parameters
        var newAction = currentUrl.pathname + '?' + searchParams.toString();
        // Validate URL before setting form action
        if (newAction.match(/^(https?|#|\/)/)) {
            form.action = newAction;
            // Submit the form
            form.submit();
        } else {
            securityUtils.safeConsoleLog('Invalid form action URL: ' + newAction);
        }
      },

      _removeFilter: function(param) {
          const currentUrl = new URL(window.location.href);
          const searchParams = currentUrl.searchParams;

          // Remove the selected filter based on the parameter
          if (param === 'from_date') {
              searchParams.delete('from_date');
              searchParams.delete('to_date');
          } else if (param === 'status') {
              searchParams.delete('status[]');
              searchParams.delete('status[0]');
          }

          // Build the new URL after removing the filter
          const newUrl = currentUrl.pathname + '?' + searchParams.toString();
          // Validate URL before setting href
          if (newUrl.match(/^(https?|#|\/)/)) {
              window.location.href = newUrl;
          } else {
              securityUtils.safeConsoleLog('Invalid URL constructed: ' + newUrl);
          }
      },

      _resetFilterForm: function() {
          $('#order-status-form')[0].reset();
      }
    });

    return $.b8.orderListing;
});

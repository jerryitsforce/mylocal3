define([
  "jquery",
  "Branch8_Checkout/js/action/reloadCartItems",
  "mage/url",
  "Magento_Customer/js/customer-data",
  "jquery/ui",
  'domReady!'
], function ($, reloadCartItems, urlBuilder, customerData) {
  'use strict';

  $.widget('branch8.cartQty', {
      options: {
        btnMinus: '.action-minus',
        btnPlus: '.action-plus',
        qtyInput: '.qty'
      },
      _create: function () {
        $(this.element).addClass('wg-cart-qty');
        $(this.element).find(this.options.btnMinus).on('click', this._onMinus.bind(this));
        $(this.element).find(this.options.btnPlus).on('click', this._onPlus.bind(this));
        $(this.element).find(this.options.qtyInput).on('change', this._onQtyChange.bind(this));
        $(this.element).parents('.product-item-details').find('.cart-actions-wrapper .action-custom-delete').on('click', this._onDelete.bind(this));
        window.cartActionProcessed = window.cartActionProcessed ?? false;
        $('.action.action-delete').on('click', function(){
          var dataDelete = JSON.parse($(this).attr('data-delete'));
          var deletePostData = dataDelete.data;
          deletePostData['form_key'] = $('input[name="form_key"]').val();
          if (!window.cartActionProcessed) {
              window.cartActionProcessed = true;
              $.ajax({
                  type: "POST",
                  url: dataDelete.action,
                  data: deletePostData, // Serialize form data
                  success: function (data) {
                      if (data.success) {
                          reloadCartItems();
                          window.cartActionProcessed = false;
                      }
                  },
                  error: function (data) {
                      window.location.reload();
                  }
              });
          }
        });
        var $qty = parseInt($(this.element).find(this.options.qtyInput).val());
        if($qty === 1) {
          // $(this.element).find(this.options.btnMinus).attr('disabled', true);
          $(this.element).find(this.options.btnMinus).addClass('btn-remove');
        }
      },

      hasPendingCartChanges: function (form) {
          var hasChanges = false;
          form.find('input[data-role="cart-item-qty"]').each(function() {
              var currentVal = parseInt($(this).val());
              var originVal = parseInt($(this).attr('data-item-qty'));
              if (currentVal !== originVal) {
                  hasChanges = true;
              }
          });
          return hasChanges;
      },

      _onMinus: function () {
        var $qty = parseInt($(this.element).find(this.options.qtyInput).val());
        var input = $(this.element).find(this.options.qtyInput);
        var originQty = parseInt(input.attr('data-item-qty'));
        var self = this;
        if (($qty - 1) === originQty) {
            input.val(originQty);
            try {
                if (input.valid) {
                    input.valid();
                }
            } catch(e) {}
            input.trigger('validate_siblings');
            this._validateAndToggleCheckout(input.closest('form'));
            if(originQty === 1) {
              $(this.element).find(this.options.btnMinus).addClass('btn-remove');
            } else {
              $(this.element).find(this.options.btnMinus).removeClass('btn-remove');
            }
            if (this.hasPendingCartChanges(input.closest('form'))) {
                this._triggerUpdate();
            }
            return;
        }
        $(this.element).find(this.options.qtyInput).val($qty > 1 ? $qty - 1 : 1);
        const btnMinus = $(this.element).find(this.options.btnMinus);
        if($qty === 1) {
          if(btnMinus.hasClass('btn-remove')) {
            const btDetele = $(this.element).parents('.product-item-details').find('.cart-actions-wrapper .action.action-delete');
            if(btDetele.length > 0) {
              $("#modal-cart-delete").modal("openModal");
              // process delete item
              $("#modal-cart-delete").on('deleteItem', function() {
                // $('body').trigger('processStart');
                $('.form.form-cart').trigger('processCartStart');
                btDetele.trigger('click');
              });

              // process add item to wishlist
              $("#modal-cart-delete").on('addItemToWishlist', function() {
                // $('body').trigger('processStart');
                if(!self.isLoggedIn()) {
                  var loginUrl = urlBuilder.build('customer/account/login');
                  window.location.href = loginUrl;
                  return;
                }
                var wishlistBtn = btDetele.parents('.product-item-details').find('.cart-actions-wrapper .action.towishlist');
                if(wishlistBtn && wishlistBtn.length != 0) {
                $('.form.form-cart').trigger('processCartStart');
                  var postData = JSON.parse(wishlistBtn.attr('data-post'));
                  postData.data.type = '刪除商品';
                  wishlistBtn.attr('data-post', JSON.stringify(postData));
                  btDetele.parents('.product-item-details').find('.cart-actions-wrapper .action.towishlist').trigger('click');
                }
              });
            }
          } else {
            $(this.element).find(this.options.btnMinus).removeClass('btn-remove');
            // $(this.element).find(this.options.btnMinus).attr('disabled', true);
            this._triggerUpdate();
          }
        } else {
          this._triggerUpdate();
        }
      },

      _onPlus: function () {
        var $qty = parseInt($(this.element).find(this.options.qtyInput).val());
        var maxQty = 999;

        if($qty + 1 > maxQty) {
          $(this.element).find(this.options.qtyInput).val(maxQty);
          return;
        }

        $(this.element).find(this.options.qtyInput).val($qty + 1);
        // $(this.element).find(this.options.btnMinus).attr('disabled', false);
        $(this.element).find(this.options.btnMinus).removeClass('btn-remove');
        this._triggerUpdate();
      },

      _onQtyChange: function () {
        var input = $(this.element).find(this.options.qtyInput);
        var $qty = parseInt(input.val());
        var originQty = parseInt(input.attr('data-item-qty'));
        var maxQty = 999;

        if ($qty === originQty) {
            try {
                if (input.valid) {
                    input.valid();
                }
            } catch(e) {}
            input.trigger('validate_siblings');
            this._validateAndToggleCheckout(input.closest('form'));
            if (this.hasPendingCartChanges(input.closest('form'))) {
                this._triggerUpdate();
            }
            return;
        }

        if($qty === 1) {
          // $(this.element).find(this.options.btnMinus).attr('disabled', false);
          $(this.element).find(this.options.btnMinus).addClass('btn-remove');
        } else if($qty < 1) {
          $(this.element).find(this.options.qtyInput).val(1);
        } else if($qty > maxQty) {
          $(this.element).find(this.options.qtyInput).val(maxQty);
        }
        this._triggerUpdate();
      },

      _onDelete: function () {
        const btDetele = $(this.element).parents('.product-item-details').find('.cart-actions-wrapper .action.action-delete');
        if(btDetele.length > 0) {
          $("#modal-cart-delete").modal("openModal");
          //
          $("#modal-cart-delete").on('deleteItem', function() {
            // $('body').trigger('processStart');
            $('.form.form-cart').trigger('processCartStart');
            btDetele.trigger('click');
          });
        }
      },

      _validateAndToggleCheckout: function(form) {
          if (form.length && form.data('validator')) {
              var checkoutBtn = $('button[data-role=proceed-to-checkout]');
              var isFormValid = true;
              form.find('input[data-role="cart-item-qty"]').each(function() {
                  var input = $(this);
                  var itemId = input.data('cart-item-id');
                  var checkbox = $('#split-cart-item-checkbox-' + itemId);
                  // Only care about checked items
                  if (!checkbox.length || checkbox.is(':checked')) {
                      if (!input.valid()) {
                          isFormValid = false;
                      }
                  }
              });
              if (isFormValid) {
                  checkoutBtn.removeClass('disabled').attr('disabled', false);
              } else {
                  checkoutBtn.addClass('disabled').attr('disabled', true);
              }
          }
      },

      _triggerUpdate: function() {
        var input = $(this.element).find(this.options.qtyInput);
        var form = input.closest('form');
        var checkoutBtn = $('button[data-role=proceed-to-checkout]');
        
        var isCheckoutBlocked = false;
        if (form.length && form.data('validator')) {
            form.find('input[data-role="cart-item-qty"]').each(function() {
                var inputItem = $(this);
                var itemId = inputItem.data('cart-item-id');
                var checkbox = $('#split-cart-item-checkbox-' + itemId);
                if (!checkbox.length || checkbox.is(':checked')) {
                    if (!inputItem.valid()) {
                        isCheckoutBlocked = true;
                    }
                }
            });
        }

        var isCurrentInputInvalid = !input.valid();

        // Only trigger update if the SPECIFIC input being modified is valid.
        // Magento core will natively check total form.valid() and block if anything else is currently red.
        if (!isCurrentInputInvalid) {
            $('.cart.main.actions .action.update').trigger('click');
        } else {
            input.focus();
        }

        if (isCheckoutBlocked) {
            checkoutBtn.addClass('disabled').attr('disabled', true);
        }
      },
      
      isLoggedIn: function() {
          var customerInfo = customerData.get('customer')();
          console.log('Customer Info:', customerInfo);
          return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
      }
  });
  return $.branch8.cartQty;
});

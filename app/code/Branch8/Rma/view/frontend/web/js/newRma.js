define([
    "jquery",
    'Magento_Ui/js/modal/confirm',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'plugins/DOMPurify',
    'mage/validation',
    "jquery/ui"
], function ($, confirmation,  customerData, alertBox, DOMPurify) {
    'use strict';
    $.widget('mprma.newRma', {
        options: {
            rmaFormSelector: '#wk_new_rma_form',
            rmaSubmitSelector: '.return-order-modal button.wk-save',
            orderShippingCity: null
        },

        _create: function () {
            var self = this;
            var reasons = self.options.reasons;
            var blockHtml = self.options.blockHtml;
            var imgErrorMsg = self.options.imgErrorMsg;
            var consignmentLabel = self.options.consignmentLabel;
            var sellerLabel = self.options.sellerLabel;
            var selectItemLabel = self.options.selectItemLabel;
            var imgSelectLabel = self.options.imgSelectLabel;
            var orderSelectLabel = self.options.orderSelectLabel;
            var sellerSelectLabel = self.options.sellerSelectLabel;
            var resolutionSelectLabel = self.options.resolutionSelectLabel;
            var itemsErrorLabel = self.options.itemsErrorLabel;
            var refundLabel = self.options.refundLabel;
            var replaceLabel = self.options.replaceLabel;
            var cancelLabel = self.options.cancelLabel;
            var deliveredLabel = self.options.deliveredLabel;
            var notDeliveredLabel = self.options.notDeliveredLabel;
            var orderStatusLabelText = self.options.orderStatusLabel;
            var selectQtyLabel = self.options.selectQtyLabel;
            var selectSellerLabel = self.options.selectSellerLabel;
            var warningLabel = self.options.warningLabel;
            var qtyMsg = self.options.qtyMsg;
            var orderData = {};
            var result = [];
            var imgCount = 0;
            var selectedCount = 0;
            var img = "";
            var error = false;
            var isVirtual = 0;
            var jQ = $.noConflict();

            // $(document).ready(function () {

            //     $("#orders").val("");

            //     $('#rma_delivery_time_not_specific').on('change', function(){
            //         if (this.checked) {
            //             $( "#rma_delivery_time_morning" ).prop( "checked", true );
            //             $( "#rma_delivery_time_afternoon" ).prop( "checked", true );
            //             $( "#rma_delivery_time_evening" ).prop( "checked", true );
            //          }
            //      })


            //     $(".action-return-exchange-primary").on('click', function () {
            //         $('#wk_new_rma_form').trigger("reset");
            //         $("#order_id").remove();
            //         $(".order_item").remove();

            //         var orderId = $(this).data('order-id');
            //         var itemId = $(this).data('item-id');

            //         var orderIdHtml = "<input type='hidden' id='order_id' name='order_id' value='" + orderId + "'>";
            //         $("#wk_new_rma_form").append(orderIdHtml);

            //         var orderItemHtml = $('<input>', { type: 'hidden', name: 'item_id', class: 'order_item', value: itemId, 'data-id': itemId});
            //         $("#wk_new_rma_form").append(orderItemHtml);

            //         if (orderId == "") {
            //             resetOrderArea();
            //         } else {
            //             showLoadingMask();
            //             $.ajax({
            //                 type: 'post',
            //                 url: self.options.orderUrl,
            //                 async: true,
            //                 dataType: 'json',
            //                 data : { order_id : orderId, is_guest : self.options.isGuest, item_id: itemId},
            //                 success:function (data) {
            //                     if (data.isLoggedIn == 1) {
            //                         orderData = data;
            //                         $("#order_items").empty();

            //                         var orderStatus = data.order_status;
            //                         var orderDetails = data.order_details;
            //                         self.options.orderShippingCity = orderDetails.shipping_address_city;
            //                         var items = data.items;

            //                         displayItems(items, orderStatus);
            //                         appendReason();
            //                         setOrderQty(items[0]);
            //                         setOrderDetails(orderDetails);
            //                         self.handleSubmitButton();
            //                     } else {
            //                         location.reload();
            //                     }
            //                     hideLoadingMask();
            //                 }
            //             });
            //         }

            //     });
            // });

            // function setOrderQty(item) {
            //     $(".order_item_qty").remove();

            //     var orderItemQtyHtml = $('<input>', { type: 'hidden', name: 'order_item_qty', class: 'order_item_qty', value: item.original_qty});
            //     $("#wk_new_rma_form").append(orderItemQtyHtml);
            // }

            // function setOrderDetails(orderDetails) {
            //     if (orderDetails.is_exchange == 1) {
            //         $('.rma-delivery-time').addClass('hidden');
            //         $('.rma-delivery-time input').attr('disabled', true);
            //         $(".rma-receiver").html(orderDetails.customer_name);
            //         $(".rma-address").html(orderDetails.store_address);
            //         $(".rma-phone").html(orderDetails.phone_masked);
            //         $('#return_or_exchange_customer_name').addClass('hidden');
            //         $("#return_or_exchange_phone_number").addClass('hidden');
            //         $("#return_or_exchange_phone_number_masked").addClass('hidden');
            //         $("#return_or_exchange_order_address_city").addClass('hidden');
            //         $("#return_or_exchange_order_address_region").addClass('hidden');
            //         $("#return_or_exchange_order_address_street").addClass('hidden');
            //         $("#return_or_exchange_order_address_street_masked").addClass('hidden');
            //         $(".rma-receiver").show();
            //         $(".rma-phone").show();
            //         $(".rma-address").show();
            //         $("#return_or_exchange_order_store_address").val(orderDetails.store_address);
            //         $("#return_or_exchange_order_store_address").attr('type', 'hidden');
            //     }else{
            //         $('.rma-delivery-time').removeClass('hidden');
            //         $('.rma-delivery-time input').attr('disabled', false);
            //         $('#return_or_exchange_customer_name').removeClass('hidden');
            //         $("#return_or_exchange_phone_number").removeClass('hidden');
            //         $("#return_or_exchange_phone_number_masked").removeClass('hidden');
            //         $("#return_or_exchange_order_address_city").removeClass('hidden');
            //         $("#return_or_exchange_order_address_region").removeClass('hidden');
            //         $("#return_or_exchange_order_address_street").removeClass('hidden');
            //         $("#return_or_exchange_order_address_street_masked").removeClass('hidden');

            //         $(".rma-receiver").hide();
            //         $(".rma-phone").hide();
            //         $(".rma-address").hide();

            //         var directoryData = customerData.get('directory-data')();
            //         if (directoryData['TW'] == undefined) {
            //             customerData.reload(['directory-data']);
            //         }
            //         var intervalDirectoryData = setInterval(function (){
            //             directoryData = customerData.get('directory-data')();console.log(directoryData);
            //             if(directoryData['TW'] != undefined){console.log('Visit');
            //                 clearInterval(intervalDirectoryData);

            //                 var TWRegion = directoryData['TW']['regions'];
            //                 $('#return_or_exchange_order_address_region').html('');
            //                 var selectedState = null;
            //                 $.each(TWRegion, function(id, state){
            //                     if(state.name == orderDetails.shipping_address_region){
            //                         /**
            //                          * Backend is using text, so FE have push test instead of id
            //                          * @type {string}
            //                          */
            //                         selectedState = orderDetails.shipping_address_region;
            //                         var optionRegion = '<option selected value="'+state.name+'">'+state.name+'</option>';
            //                     }else{
            //                         var optionRegion = '<option value="'+state.name+'">'+state.name+'</option>';
            //                     }

            //                     $('#return_or_exchange_order_address_region').append(optionRegion);
            //                 });
            //                 showCity(selectedState, orderDetails.shipping_address_city);
            //             }
            //         }, 200);

            //         $("#return_or_exchange_order_address_street").val(orderDetails.shipping_address_street);
            //         $("#return_or_exchange_order_address_street_masked").val(orderDetails.shipping_address_street_masked);
            //     }
            //     $("#return_or_exchange_customer_name").val(orderDetails.customer_name);
            //     $("#return_or_exchange_phone_number").val(orderDetails.phone);
            //     $("#return_or_exchange_phone_number_masked").val(orderDetails.phone_masked);
            // }


            // function resetOrderArea() {
            //     var errorHtml = '<div class="message info"><span>' + orderSelectLabel + '</span></div>';
            //     var html = '<tr><td colspan="7">' + errorHtml + '</td></tr>';
            //     $("#order_items").empty();
            //     $("#order_items").append(html);
            // }

            // function setErrorMessage() {
            //     var errorHtml = '<div class="message error"><span>' + itemsErrorLabel + '</span></div>';
            //     var html = '<tr><td colspan="7">' + errorHtml + '</td></tr>';
            //     $("#order_items").empty();
            //     $("#order_items").append(html);
            // }

            // $('body').on('change', '.wk-order-status', function () {
            //     $(".wk-order-cons-field").remove();
            //     var orderStatus = $('.wk-order-status').val();
            //     var consignmentLabel = $.mage.__("Consignment Number");
            //     var orderConsHtml = $("<input class='wk-order-con-no required-entry validate-no-html-tags' name='number'>");

            //     if (orderStatus == 0) {
            //         $(".wk-order-cons-field").remove();
            //     } else {
            //         var fieldsetHtml = $("<div>", { class: 'required field wk-order-cons-field' });
            //         fieldsetHtml.append($("<label>", { class: 'label' }).append($("<span>", { text: consignmentLabel })));
            //         fieldsetHtml.append($("<div>", { class: 'control' }).append(orderConsHtml));
            //         $(".wk-actions-toolbar").before(fieldsetHtml);
            //     }

            // });

            // $('body').on('change', '#return_or_exchange_order_address_region', function () {
            //     showCity($('#return_or_exchange_order_address_region').val(), this.options.orderShippingCity);
            // });

            // /**
            //  *
            //  * @param string selectedState
            //  * @param currentCity
            //  */
            // function showCity(selectedState, currentCity){;
            //     if(selectedState != null){
            //         /**
            //          * get state id from text
            //          */
            //         var directoryData = customerData.get('directory-data')();
            //         var TWRegion = directoryData['TW']['regions'];
            //         var selectedStateId = null;
            //         $.each(TWRegion, function (stateId, stateData){
            //             if(stateData.name == selectedState){
            //                 selectedStateId = stateId;
            //                 return;
            //             }
            //         });
            //         console.log(selectedStateId);
            //         $('#return_or_exchange_order_address_city').html('<option></option>');
            //         var currentCitiesData = cities[selectedStateId];
            //         $.each(currentCitiesData, function(id, cityName){
            //             if(cityName == currentCity){
            //                 var optionCity = '<option selected value="'+cityName+'">'+cityName+'</option>';
            //             }else{
            //                 var optionCity = '<option value="'+cityName+'">'+cityName+'</option>';
            //             }
            //             $('#return_or_exchange_order_address_city').append(optionCity);
            //         });
            //     }
            // }

            // function appendReason() {
            //     var alreadyAppended = Object.keys(reasons).some(function(key) {
            //         return $("#return_or_exchange_reason option[value='" + key + "']").length > 0;
            //     });
            //     if(!alreadyAppended){
            //         for (var key in reasons) {
            //             if (reasons.hasOwnProperty(key)) {
            //                 var val = key;
            //                 if (val == 0) {
            //                     val = "";
            //                 }
            //                 $("#return_or_exchange_reason").append("<option value='" + val + "'>" + reasons[key] + "</option>");
            //             }
            //         }
            //     }
            // }

            // function getHtml(obj) {
            //     var itemContent = $('<td>').addClass('item-content')
            //         .append($('<span>', {text: obj.name}).addClass('item-name'))
            //         .append(obj.optionText ? $('<span>', {html: obj.optionText}).addClass('item-description') : '')
            //         .append($('<span>', { name:'qty', text: 'x' + obj.qty }).addClass('item-qty'))
            //         .append($('<span>', { html: obj.price }).addClass('item-price'));

            //     if (obj.point_used > 0) {
            //         itemContent.append($('<span>', { text: '+' }))
            //             .append($('<span>', { text: obj.point_used }).addClass('item-point'));
            //     }


            //     return $('<tr>')
            //         .append(
            //             $('<td>').addClass('item-image').append($('<img/>', {src: obj.product_image}))
            //         )
            //         .append(
            //             $('<td>').addClass('item-content')
            //                 .append($('<span>', {text: obj.name}).addClass('item-name'))
            //                 .append(obj.optionText ? $('<span>', {html: obj.optionText}).addClass('item-description') : '')
            //                 .append($('<span>', { name:'qty', text: 'x' + obj.qty }).addClass('item-qty'))
            //                 .append($('<span>', { html: obj.price }).addClass('item-price'))
            //         );
            // }

            // function getReasonDropDown(itemId) {
            //     var combo = $("<select class='wk-reason' name='reason_ids["+itemId+"]'></select>");
            //     for (var key in reasons) {
            //         if (reasons.hasOwnProperty(key)) {
            //             var val = key;
            //             if (val == 0) {
            //                 val = "";
            //             }
            //             combo.append("<option value='" + val + "'>" + reasons[key] + "</option>");
            //         }
            //     }
            //     return combo;
            // }

            // function hideLoadingMask() {
            //     $(".wk-loading-mask").addClass("wk-display-none");
            //     $(".loading-mask").addClass("wk-display-none");

            // }

            // function showLoadingMask() {
            //     $(".wk-loading-mask").removeClass("wk-display-none");
            // }


            // function rmaErrorPopUp() {
            //     alertBox({
            //         title: warningLabel,
            //         content: "<div class='wk-mprma-warning-content'>" + itemsErrorLabel + "</div>",
            //         actions: {
            //             always: function () { }
            //         }
            //     });
            //     setErrorMessage();
            // }

            // function displayItems(items, orderStatus) {
            //     $.each(items, function (itemKey, itemObj) {
            //         var html = getHtml(itemObj, orderStatus);
            //         $("#order_items").append(html);
            //     });
            // }

            // this._observeEvents();
        },

        /**
         * Observe all events
         */
        _observeEvents: function () {
            $(document).on('change', `${this.options.rmaFormSelector} input`, this.handleSubmitButton.bind(this));
            $(document).on('change', `${this.options.rmaFormSelector} select`, this.handleSubmitButton.bind(this));
            $(document).on('change focusout', `${this.options.rmaFormSelector} textarea`, this.handleSubmitButton.bind(this));
            // $(document).on('click', '.masked-input', this.unmaskInput);
            $(document).on('input', '.masked-input', this.changeUnmaskInput);
        },

        /**
         * Check if form is valid
         *
         * @returns {*}
         */
        isFormValid: function () {
            return $(this.options.rmaFormSelector).validation('isValid');
        },

        /**
         * Enable/disable RMA form's submit button
         */
        handleSubmitButton: function () {
            $(this.options.rmaSubmitSelector).attr('disabled', !this.isFormValid());
        },

        /**
         * Unmask the value in input
         */
        unmaskInput: function () {
            var parentElement = $(this).parents('.control');
            parentElement.find('.unmasked-input').attr('type', 'text').focus();
            $(this).attr('type', 'hidden');
        },

        /**
         * Change the value in unmasked input
         */
        changeUnmaskInput: function () {
            var parentElement = $(this).parents('.control'),
                unmaskedElement = parentElement.find('.unmasked-input');
            unmaskedElement.val($(this).val());
        },
    });
    return $.mprma.newRma;
});

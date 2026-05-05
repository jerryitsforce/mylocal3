define([
    'ko',
    'jquery',
    'uiComponent',
    'Branch8_Rma/js/model/order',
    'mage/validation'
], function (ko, $, Component, order) {
    'use strict';

    const rmaData = window.rmaData;

    return Component.extend({
        defaults: {
            template: 'Branch8_Rma/form/rma-form'
        },
        orderId: ko.observable(''),
        orderItemId: ko.observable(''),
        createFormUrl: ko.observable(rmaData?.rmaUrl || ''),
        isLoading: ko.observable(false),
        isConvenienceStore: ko.observable(false),
        isVirtual: ko.observable(false),
        orderInfo: ko.observable(null),
        isExpiredPoint: ko.observable(false),

        initialize: function () {
            var self = this;
            this._super();
            // console.log('rma form', rmaData, order.rmaData);
            this.initEventHandlers();
            var orderInfoData = order.info();
            this.isConvenienceStore(orderInfoData?.isConvenienceStore? true : false);
            this.isVirtual(orderInfoData?.isTicketOrder? true : false);
            this.orderInfo(orderInfoData);

            order.info.subscribe(function (newValue) {
                self.isConvenienceStore(newValue?.isConvenienceStore? true : false);
                self.isVirtual(newValue?.isTicketOrder? true : false);
                self.orderInfo(newValue);
                // console.log('order info updated:', newValue, self.orderInfo(), self.isVirtual());
            });

            if(this.isVirtual()) {
                $('.apply-rma-order-popup .modal-title').text($.mage.__('退貨申請'));
            } else {
                $('.apply-rma-order-popup .modal-title').text($.mage.__('退換貨申請'));
            }

            this.isVirtual.subscribe(function (newValue) {
                if(newValue) {
                    $('.apply-rma-order-popup .modal-title').text($.mage.__('退貨申請'));
                } else {
                    $('.apply-rma-order-popup .modal-title').text($.mage.__('退換貨申請'));
                }
            });

            return this;
        },

        initEventHandlers: function () {
            // console.log('trigger event');

            var self = this;
            // click RMA button
            $(document).on('click', '.action-return-exchange-primary', function(){
                // console.log('click RMA button', $(this).data('item-id'), $(this).data('order-id'));
                self.orderItemId($(this).data('item-id'));
                self.orderId($(this).data('order-id'));
                self.isExpiredPoint(false);
                // reset form
                order.resetData();
                self.resetForm();
                self.updateItem();
            });

            // Form 
            var reasonId = '#return_or_exchange_reason';
            var reason = $(reasonId).val();
            if(!reason || reason  ==  '') {
                $(reasonId).addClass('empty');
            } else {
                $(reasonId).removeClass('empty');
            }

            var regionId = '#return_or_exchange_order_address_region';
            var region = $(regionId).val();
            if(!region || region  ==  '') {
                $(regionId).addClass('empty');
            } else {
                $(regionId).removeClass('empty');
            }

            var cityId = '#return_or_exchange_order_address_city';
            var city = $(cityId).val();
            if(!city || city  ==  '') {
                $(cityId).addClass('empty');
            } else {
                $(cityId).removeClass('empty');
            }

            // Validation
            $(document).on('change, input', '#return_or_exchange_reason, input[name=resolution_type], input[name=order_status], .reason_info, #return_or_exchange_customer_name, #return_or_exchange_phone_number_masked, #return_or_exchange_order_address_region, #return_or_exchange_order_address_street, input[name=rma_delivery_time], #return_or_exchange_phone_number', function(){
                // console.log('change', this);
                // if($(this).attr('id') == 'return_or_exchange_order_address_region') {
                //     var citySelect = registry.get('rmaCity');
                //     var value = $(this).val();
                //     console.log('Selected region:', value);
                //     if (citySelect) {
                //         citySelect.cities(cities? cities[value] : "");
                //         console.log('Updated cities:', citySelect.cities());
                //     }
                // }
                const validate = $.validator.validateSingleElement($(this));
                if (validate) {
                    // console.log('ok')
                    self.enableSubmitBtn();
                } else {
                    // console.log('not ok')
                    // $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").attr('disabled', true);
                    $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").addClass('disabled');
                }
            });

            
            $(document).on('change, input', '#return_or_exchange_order_address_city', function(){
                const validate = $.validator.validateSingleElement($(this));
                if (validate) {
                    self.enableSubmitBtn();
                } else {
                    $(this).val('');
                    $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").addClass('disabled');
                }
            });

            $(document).on('change, input', '#return_or_exchange_customer_name_masked', function(){
                var value = $('#return_or_exchange_customer_name_masked').val();
                $('#return_or_exchange_customer_name').val(value);
                $('#return_or_exchange_customer_name').trigger('input');
                $('#return_or_exchange_customer_name').trigger('change');
            });

            $(document).on('change, input', '#return_or_exchange_phone_number_masked', function(){
                var value = $('#return_or_exchange_phone_number_masked').val();
                $('#return_or_exchange_phone_number').val(value);
                $('#return_or_exchange_phone_number').trigger('input');
                $('#return_or_exchange_phone_number').trigger('change');
            });
            
            $(document).on('change, input', '#return_or_exchange_order_address_street_masked', function(){
                var value = $('#return_or_exchange_order_address_street_masked').val();
                $('#return_or_exchange_order_address_street').val(value);
                $('#return_or_exchange_order_address_street').trigger('input');
                $('#return_or_exchange_order_address_street').trigger('change');
            });
            
        },

        resetForm: function() {
            // console.log('reset form');
            $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").addClass('disabled');
            $("#new_rma_form input").removeClass('mage-error').removeAttr('aria-invalid');
            $("#new_rma_form select").removeClass('mage-error').removeAttr('aria-invalid');
            $("#new_rma_form div.mage-error").remove();
            
            if(!this.isVirtual()){
                $('#new_rma_form input[name=resolution_type]').prop('checked', false);
                $('#new_rma_form input[name=order_status]').prop('checked', false);
                $('#new_rma_form select[name=reason_id]').val('');
                $('#new_rma_form input[name=rma_receiver]').val('');
                $('#return_or_exchange_order_address_region').val('');
                $('#return_or_exchange_order_address_city').val('');
                $('#return_or_exchange_order_address_street').val('');
                $('#new_rma_form input[name=rma_delivery_time]').prop('checked', false);
            } else {
                $('#new_rma_form input[name=resolution_type]').val('');
                $('#new_rma_form select[name=reason_id]').val('');
            }
        },

        enableSubmitBtn: function() {
            if(!this.isVirtual()){
                var type = $('#new_rma_form input[name=resolution_type]:checked').val();
                var orderStatus = $('#new_rma_form input[name=order_status]:checked').val();
                var reasonId = $('#new_rma_form select[name=reason_id]').val();
                var rmaReceiver = $('#new_rma_form input[name=rma_receiver]').val();
                var rmaRegion = $('#return_or_exchange_order_address_region').val();
                var rmaCity = $('#return_or_exchange_order_address_city').val();
                var rmaStreet = $('#return_or_exchange_order_address_street').val();
                var rmaDeliveryTime = $('#new_rma_form input[name=rma_delivery_time]:checked').val();
                // console.log({a: this.isVirtual, type, orderStatus, reasonId, rmaReceiver, rmaRegion, rmaCity, rmaStreet, rmaDeliveryTime});
                
                if(type && orderStatus && reasonId && rmaReceiver && rmaRegion && rmaCity && rmaStreet && rmaDeliveryTime && !$('#return_or_exchange_order_address_city').hasClass('mage-error') && !$('#return_or_exchange_order_address_region').hasClass('mage-error')) {
                    $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").removeClass('disabled');
                } else  {
                    $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").addClass('disabled');
                }
            } else{
                var type = $('#new_rma_form input[name=resolution_type]:checked').val();
                var reasonId = $('#new_rma_form select[name=reason_id]').val();
                // console.log({a: this.isVirtual, type, reasonId});
                if(type && reasonId) {
                    $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").removeClass('disabled');
                } else  {
                    $(".apply-rma-order-popup .modal-footer .apply-rma-order-button").addClass('disabled');
                }
            }
        },

        updateItem: function () {
            // console.log('update item',this.orderId(), this.orderItemId());
            var self = this;

            if(self.orderId() && self.orderItemId()){
                self.isLoading(true);
                $.ajax({
                    type: 'post',
                    url: rmaData.orderUrl,
                    async: true,
                    dataType: 'json',
                    data : { order_id : self.orderId(), is_guest : 0, item_id: self.orderItemId()},
                    success:function (data) {
                        // console.log('data', data);
                        self.isLoading(false);
                        if(data?.items) {
                            order.items(data.items);
                        }
                        if(data?.order_details) {
                            order.info(data.order_details);
                        }
                        if(data?.sellers) {
                            order.sellers(data.sellers);
                        }
                    },
                    error: function (xhr, status, error) {
                        // console.log('error', xhr, status, error);
                        self.isLoading(false);
                    },
                });
                $.ajax({
                    type: 'post',
                    url: rmaData.checkRmaPointUrl,
                    async: true,
                    dataType: 'json',
                    data : {item_id: self.orderItemId()},
                    success:function (data) {
                        // console.log('data', data);
                        // self.isLoading(false);
                        if(data?.data && data.data['failure_point']) {
                            self.isExpiredPoint(true);
                        } else {
                            self.isExpiredPoint(false);
                        }
                    },
                    error: function (xhr, status, error) {
                        // console.log('error', xhr, status, error);
                        // self.isLoading(false);
                    },
                });
            }
        }
    });
});
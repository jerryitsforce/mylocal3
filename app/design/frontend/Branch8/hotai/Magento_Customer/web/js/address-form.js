/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'mageUtils',
    'mage/translate',
    'mage/url',
    'plugins/DOMPurify',
    "Magento_Ui/js/modal/modal",
    'jquery-ui-modules/widget',
    'validation',
], function ($, __, utils, $t, urlBuilder, DOMPurify, modal ) {
    'use strict';

    $.widget('b8.addressForm', {
        options: {
            selectors: {
                selectStoreButton: '#select-store-button',
                primaryShippingCheckbox: '#primary_shipping',
                btnSubmit: '.actions-toolbar .action.save'
            }
        },

        /**
         * Validation creation
         *
         * @protected
         */
        _create: function () {
            // var options = {
            //     type: 'popup',
            //     responsive: true,
            //     title: '',
            //     buttons: []
            // };

            // modal(options, $('#modal-store-pickup'));

            var self = this;
            this.primaryShippingCheckbox = $(this.options.selectors.primaryShippingCheckbox, this.element);
            this.primaryShippingCheckbox.on('change', this.onPrimaryShippingChange);

            $(this.options.selectors.btnSubmit).attr('disabled', "true");
            $(this.options.selectors.btnSubmit).addClass('disabled');

            this.initActions();
            this.processConvenienceStore();
            if($(this.options.selectors.selectStoreButton).length){
                this.enableConservationFormSubmit();
            } else {
                this.enableSubmit();
            }

            $(this.options.selectors.btnSubmit).on('click', function(event){
                event.preventDefault();
                self.validateForm();
            });

            this.updateBreadcrumb();

        },

        initActions: function(){
            var self = this;
            if($(self.options.selectors.selectStoreButton).length){
                $(self.options.selectors.selectStoreButton).on('click', function(){
                    self.selectStore();
                });
                $(document).on('change, input', '#firstname, #telephone', function(){
                    // console.log('change', this);
                    const validate = $.validator.validateSingleElement($(this));
                    if (validate) {
                        self.enableConservationFormSubmit();
                    } else {
                        $("#address-form .actions-toolbar .action.save").attr('disabled', true);
                        $("#address-form .actions-toolbar .action.save").addClass('disabled');
                    }
                });
            } else {
                $(document).on('change, input', '#firstname, #telephone, #street_1, #region_id, #city_id', function(){
                    // console.log('change', this);
                    const validate = $.validator.validateSingleElement($(this));
                    if (validate) {
                        self.enableSubmit();
                    } else {
                        $("#address-form .actions-toolbar .action.save").attr('disabled', true);
                        $("#address-form .actions-toolbar .action.save").addClass('disabled');
                    }
                });
            }
        },

        processConvenienceStore: function () {
            var self = this;
            console.log(typeof this.options.storeData)
            var storeData = typeof this.options.storeData === 'string' ? JSON.parse(this.options.storeData) : null;
            var storeAddress = this.options.storeAddress;
            var currentURL = window.location.href;
            var hasStoredAdd = window.localStorage.getItem('hasStoredAdd');

            if (currentURL.includes('customer/address/new') && storeAddress && storeAddress.type == 'edit') {
                storeData = null;
            } else if (currentURL.includes('customer/address/edit') && storeAddress && storeAddress.type == 'new') {
                storeData = null;
            }

            console.log({storeData, storeAddress, currentURL, hasStoredAdd, aa: (currentURL.includes('customer/address/new') && storeAddress?.type == 'edit'), bb: (currentURL.includes('customer/address/edit') && storeAddress?.type == 'new')});

            if (storeAddress && hasStoredAdd && (currentURL.includes('customer/address/new') && storeAddress?.type == 'new') || (currentURL.includes('customer/address/edit') && storeAddress?.type == 'edit')) {
                // reset
                console.log('reset store data', hasStoredAdd, storeData, storeAddress);
                this.options.storeData = null;
                this.options.storeAddress = null;
                 $.ajax({
                    url: urlBuilder.build('checkout/address/clearData'),
                    type: "POST",
                    success: function (response) {
                        if (response.success) {
                            console.log('Store data cleared successfully');
                            window.localStorage.removeItem('hasStoredAdd');
                        }
                    },
                    error: function (err) {
                        // check the err for error details
                    }
                });
            }

            console.log({storeData, storeAddress, currentURL, a: currentURL.includes('customer/address/new'), aa: storeAddress?.type})

            if (storeAddress && storeData) {
                $('#firstname').val(storeAddress.firstname).trigger('change');
                $('#telephone').val(storeAddress.telephone).trigger('change');
                $('#region_id').val(storeAddress.region_id).trigger('change');
                $('#region').val(storeAddress.region).trigger('change');
                $('#city_id').val(storeAddress.city_id).trigger('change');
                // $('#city').val(address.city).trigger('change');
                $('#country').val(storeAddress.country).trigger('change');
                // $('#street_1').val(address.street).trigger('change');
                $('#zip').val(storeAddress.postcode).trigger('change');
                if(storeAddress.primary_shipping === '1'){
                    $('#primary_shipping').prop('checked', true);
                    $('#primary_shipping').val(1);
                }else{
                    $('#primary_shipping').prop('checked', false);
                    $('#primary_shipping').removeAttr('checked');
                    $('#primary_shipping').val(0);
                }
                $('#primary_shipping').trigger('change');

                $('#city').val(storeData.storename).trigger('change');
                $('#street_1').val(storeData.address).trigger('change');
                $('#store-name').text('711'+storeData.storename);
                $('#store-address-text').text(storeData.address);

                $('.store-address').removeClass('hidden');
                $('.store-name').removeClass('hidden');

                $('#cvs_store_code').val(storeData.storeid).trigger('change');
                $('#cvs_store_name').val(storeData.storename).trigger('change');
                $('#cvs_store_servicetype').val(storeData.servicetype).trigger('change');
                $('#cvs_store_outside').val(storeData.outside).trigger('change');

                self.enableConservationFormSubmit();
            }
        },

        enableConservationFormSubmit: function() {
            var name = $('#firstname').val();
            var phone = $('#telephone').val();
            var regionId = $('#region_id').val();
            var region = $('#region').val();
            var cityId = $('#city_id').val();
            var city = $('#city').val();
            var street = $('#street_1').val();
            var postcode = $('#zip').val();
            // console.log({name, phone, regionId, region, cityId, city, street, postcode});

            if(name && phone && city && street){
                $("#address-form .actions-toolbar .action.save").removeAttr('disabled');
                $("#address-form .actions-toolbar .action.save").removeClass('disabled');
                $('button[type="submit"]').attr('disabled', false);
            } else{
                $("#address-form .actions-toolbar .action.save").attr('disabled', true);
                $("#address-form .actions-toolbar .action.save").addClass('disabled');
            }

        },

        enableSubmit: function() {
            var name = $('#firstname').val();
            var phone = $('#telephone').val();
            var regionId = $('#region_id').val();
            var region = $('#region').val();
            var cityId = $('#city_id').val();
            var city = $('#city').val();
            var street = $('#street_1').val();
            var postcode = $('#zip').val();
            // console.log({name, phone, regionId, region, cityId, city, street, postcode});
            if(name && phone && regionId && cityId && street && !$('#city_id').hasClass('mage-error') && !$('#region_id').hasClass('mage-error')) {
                $("#address-form .actions-toolbar .action.save").removeAttr('disabled');
                $("#address-form .actions-toolbar .action.save").removeClass('disabled');
                $('button[type="submit"]').attr('disabled', false);
            } else{
                $("#address-form .actions-toolbar .action.save").attr('disabled', true);
                $("#address-form .actions-toolbar .action.save").addClass('disabled');
            }
        },

        validateForm: function () {
            // console.log('validateForm', this);
            const isValid = $("#address-form").validation() && $("#address-form").validation('isValid');
            if(isValid){
                var cityId = $('#cvs_store_name').val();
                var street = $('#street_1').val();
                const keywords = ["澎湖縣", "金門", "澎湖", "金門縣", "馬祖", "連江縣", "北竿鄉", "東引鄉", "南竿鄉", "莒光鄉", "台東縣蘭嶼鄉", "台東縣綠島鄉","屏東縣琉球鄉"];
                var hasKeyword = keywords.some(keyword => street.includes(keyword));
                var hasKeywordCity = false;
                if(cityId){
                    hasKeywordCity = keywords.some(keyword => cityId.includes(keyword));
                }
                console.log(cityId, street, hasKeyword, hasKeywordCity);

                if (hasKeyword || hasKeywordCity) {
                    this.showMessage('暫不提供外島配送，請選擇台灣本島地區門市。', 'error');
                    console.log("Address contains 澎湖縣, 金門縣, or 馬祖");
                    // $("#address-form .actions-toolbar .action.save").attr('disabled', true);
                    // $("#address-form .actions-toolbar .action.save").addClass('disabled');
                } else {
                    $("#address-form .actions-toolbar .action.save").attr('disabled', "true");
                    $("#address-form .actions-toolbar .action.save").addClass('disabled');
                    $("#address-form").submit();
                }
            } else {
                $("#address-form .actions-toolbar .action.save").attr('disabled', "true");
                $("#address-form .actions-toolbar .action.save").addClass('disabled');
                return;
            }
        },

        selectStore: function () {
            event.preventDefault();
            const url = window.location.href;
            var ajaxUrl = urlBuilder.build('checkout/ajax/selectStores?type=address&redirect_url='+encodeURIComponent(url));

            // saving data before redirecting
            var address = null;
            if(url.indexOf('new') > 0) {
                address = {
                    type: 'new',
                    firstname: $('#firstname').val(),
                    telephone: $('#telephone').val(),
                    region_id: $('#region_id').val(),
                    region: $('#region').val(),
                    city_id: $('#city_id').val(),
                    city: $('#city').val(),
                    country: $('#country').val(),
                    street: $('#street_1').val(),
                    postcode: $('#zip').val(),
                    primary_shipping: $('#primary_shipping').val()
                };
            } else {
                address = {
                    type: 'edit',
                    firstname: $('#firstname').val(),
                    telephone: $('#telephone').val(),
                    region_id: $('#region_id').val(),
                    region: $('#region').val(),
                    city_id: $('#city_id').val(),
                    city: $('#city').val(),
                    country: $('#country').val(),
                    street: $('#street_1').val(),
                    postcode: $('#zip').val(),
                    cvs_store_name: $('#cvs_store_name').val(),
                    cvs_store_servicetype: $('#cvs_store_servicetype').val(),
                    cvs_store_outside: $('#cvs_store_outside').val(),
                    primary_shipping: $('#primary_shipping').val()
                };
            }

            $.ajax({
                url: urlBuilder.build('checkout/address/storeData'),
                type: "POST",
                data: {checkoutAddress: address},
                success: function (response) {
                    if (response.success) {
                        window.localStorage.setItem('hasStoredAdd', true);
                        window.location.href = ajaxUrl;
                    }
                },
                error: function (err) {
                    // check the err for error details
                }
            });


            // $('#modal-store-pickup').modal('openModal');

            // var storeWindow;
            // var form = document.createElement('form');
            // form.method = 'POST';
            // form.action = 'https://emap.presco.com.tw/emapmobileu.ashx';
            // var ajaxUrl = urlBuilder.build('checkout/ajax/callback');
            // var inputFields = [
            //     { name: 'eshopid', value: '234' },
            //     { name: 'servicetype', value: 3 },
            //     { name: 'url', value: ajaxUrl },
            //     { name: 'tempvar', value: 'checkout' },
            //     { name: 'storeid', value: '' }
            // ];

            // inputFields.forEach(function(field) {
            //     var input = document.createElement('input');
            //     input.type = 'hidden';
            //     input.name = field.name;
            //     input.value = field.value;
            //     form.appendChild(input);
            // });

            // var width = 1200;
            // var height = 800;
            // var left = window.screenX + (window.outerWidth - width) / 2;
            // var top = window.screenY + (window.outerHeight - height) / 2;

            // storeWindow = window.open('', 'storeSelector', `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=${width}, height=${height}, top=${top}, left=${left}`);
            // if(storeWindow) {
            //     storeWindow.document.body.appendChild(form);
            //     form.submit();

            // window.sessionStorage.removeItem('stored');

            // const enableConservationFormSubmit = function() {
            //     var name = $('#firstname').val();
            //     var phone = $('#telephone').val();
            //     var regionId = $('#region_id').val();
            //     var region = $('#region').val();
            //     var cityId = $('#city_id').val();
            //     var city = $('#city').val();
            //     var street = $('#street_1').val();
            //     var postcode = $('#zip').val();
            //     // console.log({name, phone, regionId, region, cityId, city, street, postcode});
            //     if(name && phone && city && street){
            //         $("#address-form .actions-toolbar .action.save").removeAttr('disabled');
            //         $("#address-form .actions-toolbar .action.save").removeClass('disabled');
            //         $('button[type="submit"]').attr('disabled', false);
            //     } else{
            //         $("#address-form .actions-toolbar .action.save").attr('disabled', true);
            //         $("#address-form .actions-toolbar .action.save").addClass('disabled');
            //     }
            // }

            // const handleStoreMessage = function(event)  {
            //     if (event.origin !== window.location.origin) {
            //         // Ignore messages from unknown origins
            //         return;
            //     }
            //     const stored = window.sessionStorage.getItem('stored');
            //     if (!stored) {
            //         // The data returned from the popup window
            //         var storeData = event.data;
            //         // console.log({storeData});
            //         if (storeData && storeData.storeid) {
            //             // console.log(storeData)
            //             $('#city').val(storeData.storename).trigger('change');
            //             $('#street_1').val(storeData.address).trigger('change');
            //             $('#store-name').text('711'+storeData.storename).removeClass('hidden');
            //             $('#store-address-text').text(storeData.address)
            //             $('.store-address').removeClass('hidden');

            //             $('#cvs_store_code').val(storeData.storeid).trigger('change');
            //             $('#cvs_store_name').val(storeData.storename).trigger('change');
            //             $('#cvs_store_servicetype').val(storeData.servicetype).trigger('change');
            //             $('#cvs_store_outside').val(storeData.outside).trigger('change');

            //             // $('button[type="submit"]').removeAttr('disabled');
            //             if(storeData.storename){
            //                 window.sessionStorage.setItem('stored', 1);
            //                 enableConservationFormSubmit();
            //             }

            //         } else {
            //             if(!$("#address-form").data('id')) {
            //                 $('#city').val('').trigger('change');
            //                 $('#street_1').val('').trigger('change');
            //                 $('#store-name').text('').addClass('hidden');
            //                 $('#store-address-text').text('')
            //                 $('.store-address').addClass('hidden');

            //                 $('#cvs_store_code').val('').trigger('change');
            //                 $('#cvs_store_name').val('').trigger('change');
            //                 $('#cvs_store_servicetype').val('').trigger('change');
            //                 $('#cvs_store_outside').val('').trigger('change');
            //             }
            //             // console.log('no store data');
            //             // $('button[type="submit"]').attr('disabled', "true");
            //         }
            //     }
            // }

            // // Remove the existing event listener before adding a new one
            // window.removeEventListener('message', handleStoreMessage);

            // // Add the new event listener
            // window.addEventListener('message', handleStoreMessage, false);
            // }
        },

        onPrimaryShippingChange: function (e) {
            if($(this).is(':checked') ) {
                $(this).val(1)
            }else{
                $(this).val(0)
            }
        },

        updateBreadcrumb: function () {
            var jQ = $.noConflict();

            jQ('.breadcrumbs .item').each(function(){
                if(jQ(this).last()){
                    const lastBreadcrumbItem = jQ('.breadcrumbs .item:last-child > a');
                    var lastItemHref = lastBreadcrumbItem.attr('href');
                    lastItemHref = lastItemHref.startsWith(window.location.origin) ? lastItemHref : window.location.origin + lastItemHref;
                    const lastBreadcrumbItemUrl = DOMPurify.sanitize(lastItemHref);
                    const currentURL = window.location.href;
                    let url = new URL(lastBreadcrumbItemUrl);
                    let params = new URLSearchParams(url.search);

                    if (currentURL.indexOf("type/convenience_store") > 0) {
                        params.set('tab', 'convenience_store');
                        url.search = params.toString();
                        lastBreadcrumbItem.attr('href', DOMPurify.sanitize(url.toString()));
                    }
                }

            });

        },

        showMessage: function (message, type = 'error') {
            var jQ = $.noConflict();
            var msgContainer = jQ('.page.messages');
            var messageContent = jQ('<div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'"/>');
            messageContent.text(DOMPurify.sanitize(message));
            const config = {
                ALLOWED_TAGS: ['div'],
                ALLOW_DATA_ATTR: true
            };
            const content = DOMPurify.sanitize(messageContent.prop('outerHTML'), config);
            msgContainer.append('<div class="messages custom-messages">' + content + '</div>');
            msgContainer.addClass('__show');
            var timeCheck;
            clearTimeout(timeCheck);
            timeCheck = setTimeout(function () {
                msgContainer.removeClass('__show');
                msgContainer.find('.custom-messages').remove();
            }, 3000);
        },

    });

    return $.b8.addressForm;
});

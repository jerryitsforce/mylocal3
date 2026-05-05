define(
    [
        'ko',
        'jquery',
        'uiElement',
        'uiRegistry',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-service',
        'Amasty_CheckoutCore/js/model/payment/payment-loading',
        'Amasty_CheckoutStyleSwitcher/js/action/start-place-order',
        'Amasty_CheckoutStyleSwitcher/js/model/amalert',
        'Amasty_CheckoutCore/js/action/focus-first-error',
        'Amasty_CheckoutCore/js/model/payment-validators/login-form-validator',
        'Amasty_CheckoutCore/js/model/address-form-state',
        'Amasty_CheckoutCore/js/model/one-step-layout',
        'Amasty_CheckoutCore/js/model/payment/place-order-state',
        'Magento_Ui/js/lib/knockout/extender/bound-nodes',
        'Magento_Ui/js/lib/view/utils/dom-observer',
        'Magento_Customer/js/model/address-list',
        'Branch8_OneStepCheckout/js/action/save-custom-fields',
        'Branch8_OneStepCheckout/js/model/gift-to-friend',
        'Branch8_OneStepCheckout/js/model/payment',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url',
        'mage/storage',
        'Magento_Ui/js/lib/view/utils/async',
        'mage/translate'
    ],
    function (
        ko,
        $,
        Component,
        registry,
        quote,
        shippingService,
        paymentLoader,
        startPlaceOrderAction,
        alert,
        focusFirstError,
        loginFormValidator,
        addressFormState,
        oneStepLayout,
        placeOrderState,
        boundNodes,
        domObserver,
        addressList,
        saveCustomFields,
        giftToFriendModel,
        checkPayment,
        addressConverter,
        checkoutData,
        setBillingAddressAction,
        messageList,
        fullScreenLoader,
        urlBuilder
    ) {
        'use strict';

        return function (PlaceButton) {
            return PlaceButton.extend({
                initObservable: function () {
                    this._super();
                    const self = this;

                    if (quote.paymentMethod()) {
                        this.checkButton(quote.paymentMethod());
                    }

                    quote.paymentMethod.subscribe(this.checkButton, this);

                    giftToFriendModel.disabledGiftToFriend.subscribe(function (value) {
                        console.log('disabledGiftToFriend', value);
                        giftToFriendModel.placingOrder('');
                    });

                    giftToFriendModel.useGiftToFriend.subscribe(function (value) {
                        console.log('useGiftToFriend', value);
                        giftToFriendModel.placingOrder('');
                    });

                    giftToFriendModel.recipientFillOption.subscribe(function (value) {
                        console.log('recipientFillOption', value);
                        giftToFriendModel.placingOrder('');
                    });

                    // addressFormState.isBillingFormVisible.subscribe(this.updateWarning, this);

                    // if (quote.isVirtual()) {
                    //     quote.paymentMethod.subscribe(this.updateWarning, this);
                    // } else {
                    //     addressFormState.isShippingFormVisible.subscribe(this.updateWarning, this);
                    // }

                    return this;
                },

                checkButton: function (paymentMethod) {
                        // console.log('checkButton', checkPayment.isSelectedPaymentMethod());
                    if (!paymentMethod) {
                        // console.log('checkButton - isSelectedPaymentMethod - paymentMethod', false);
                        checkPayment.isSelectedPaymentMethod(false);
                        return;
                    }

                    var quotePaymentMethod = quote.paymentMethod().method;
                    if(quotePaymentMethod === 'hotaipay') {
                        var hotaiPaymentCard = $('.checkout-payment-method .card-item input[name="payment[creditcard_list]"]:checked').val();
                        if(!hotaiPaymentCard) {
                            // console.log('checkButton - isSelectedPaymentMethod - no selected payment option', false);
                            checkPayment.isSelectedPaymentMethod(false);
                            return;
                        }
                    }

                    checkPayment.isSelectedPaymentMethod(true);
                },

                isPlaceOrderActionAllowed: ko.pureComputed(function () {
                    console.log({a: paymentLoader(), b: addressFormState.isBillingFormVisible(), c: addressFormState.isShippingFormVisible(), d: shippingService.isLoading(), e: placeOrderState(), f: checkPayment.isSelectedPaymentMethod()});
                    return !paymentLoader()
                        && !addressFormState.isBillingFormVisible()
                        && !addressFormState.isShippingFormVisible()
                        && !shippingService.isLoading()
                        && placeOrderState()
                        && checkPayment.isSelectedPaymentMethod();
                }),

                placeOrder: function () {
                    var errorMessage = '';
                    const self = this;

                    if (!quote.paymentMethod()) {
                        errorMessage = $.mage.__('No payment method selected');
                        messageList.addErrorMessage({message: errorMessage});
                        return;
                    }

                    var quotePaymentMethod = quote.paymentMethod().method;
                    if(quotePaymentMethod === 'hotaipay') {
                        var hotaiPaymentCard = $('.checkout-payment-method .card-item input[name="payment[creditcard_list]"]:checked').val();
                        if(!hotaiPaymentCard) {
                            errorMessage = $.mage.__('No card item selected');
                            messageList.addErrorMessage({message: errorMessage});
                            return;
                        }
                    }

                    if (!quote.shippingMethod() && !quote.isVirtual()) {
                        errorMessage = $.mage.__('No shipping method selected');
                        messageList.addErrorMessage({message: errorMessage});
                        return;
                    }

                    if(!quote.isVirtual()) {
                        // validate shipping address when customer dont complete input/select address but clicking place order
                        var shipping = registry.get('checkout.steps.shipping-step.shippingAddress');
                        var provider = registry.get('checkoutProvider');

                        var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                        var hotaiAddressType = 'normal';
                        if (quote.shippingMethod()) {
                        var selectedShippingMethod = quote.shippingMethod().method_code;
                            if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                                hotaiAddressType = 'convenience_store';
                            }
                        }

                        if(hotaiAddressType === 'convenience_store' && giftToFriendModel.useGiftToFriend()) {
                            errorMessage = $.mage.__('贈禮功能不提供超商取貨選項');
                            messageList.addErrorMessage({message: errorMessage});
                            return;
                        }

                        console.log('giftToFriendModel', giftToFriendModel.placingOrder(), giftToFriendModel.allowGiftToFriend(), giftToFriendModel.useGiftToFriend(), giftToFriendModel.disabledGiftToFriend(), giftToFriendModel.recipientFillOption(), giftToFriendModel.placeholderGiftAddress(), giftToFriendModel.filledAddress());

                        // if(giftToFriendModel.placingOrder() === 'saved') {
                        //     console.log('giftToFriendModel - placingOrder saved');
                        //     // // if gift to friend is saved, then we can proceed to place order
                        //     giftToFriendModel.placingOrder('placing_saved');
                        //     $('.checkout-payment-method.submit .actions-toolbar .action.checkout').trigger('click');
                        //     return;
                        // }
                        if(giftToFriendModel.placingOrder() === '' && giftToFriendModel.useGiftToFriend() && !giftToFriendModel.disabledGiftToFriend() && giftToFriendModel.allowGiftToFriend()  && giftToFriendModel.recipientFillOption() === 1) {
                            // console.log('use gift');
                            provider.trigger('giftShippingAddress.data.validate');
                            console.log('use gift', provider, provider.get('params.invalid'));
                            const giftForm = $('#gift-to-friend-form');
                            if (giftForm.length && giftForm.is(":visible")) {
                                const placeholderGiftAddress = giftToFriendModel.placeholderGiftAddress();
                                var name = $('#gift-to-friend-form input[name=firstname]').val(); //|| placeholderGiftAddress.firstname;
                                var telephone = $('#gift-to-friend-form input[name=telephone]').val(); //|| placeholderGiftAddress.telephone;
                                var region = $('#gift-to-friend-form select[name=region_id]').val(); //|| placeholderGiftAddress.region_id;
                                var city = $('#gift-to-friend-form select[name=city_id]').val(); //|| placeholderGiftAddress.city;
                                var street = $('#gift-to-friend-form input[name="street[0]"]').val(); //|| placeholderGiftAddress.street;
                                var postcode = $('#gift-to-friend-form input[name=postcode]').val() || '000';

                                console.log({name, telephone, region, city, street, postcode });
                                giftToFriendModel.filledAddress('');
                                var filledAddress = '';

                                if(!name) {
                                    name = placeholderGiftAddress.firstname;
                                } else {
                                    filledAddress += 'name';
                                }
                                if(!telephone) {
                                    telephone = placeholderGiftAddress.telephone;
                                } else {
                                    filledAddress += filledAddress ? ',telephone' : 'telephone';
                                }
                                if(!region) {
                                    region = placeholderGiftAddress.region_id;
                                } else{
                                    filledAddress += filledAddress ? ',region' : 'region';
                                }
                                if(!city) {
                                    city = placeholderGiftAddress.city;
                                } else {
                                    filledAddress += filledAddress ? ',city' : 'city';
                                }
                                if(!street) {
                                    street = placeholderGiftAddress.street;
                                } else {
                                    filledAddress += filledAddress ? ',street' : 'street';
                                }
                                console.log({name, telephone, region, city, street, postcode, filledAddress });
                                giftToFriendModel.filledAddress(filledAddress);

                                // return;
                                if (self?._renderAddressTimeout) {
                                    clearTimeout(self._renderAddressTimeout);
                                }
                                // error: Special characters are not allowed.
                                var textError = $.mage.__('不接受特殊符號。');
                                if( (name === '' || telephone === '' || region === '' || city === '' || street === '') && provider.get('params.invalid')) {
                                    messageList.addErrorMessage({message: textError});
                                    return;
                                } else {
                                    if($('#gift-to-friend-form input[name=firstname]').hasClass('mage-error') ||
                                        $('#gift-to-friend-form input[name=telephone]').hasClass('mage-error') ||
                                        $('#gift-to-friend-form select[name=region_id]').hasClass('mage-error') ||
                                        $('#gift-to-friend-form select[name=city_id]').hasClass('mage-error') ||
                                        $('#gift-to-friend-form input[name="street[0]"]').hasClass('mage-error')) {
                                            console.log('giftToFriendModel - placingOrder error');
                                        messageList.addErrorMessage({message: textError});
                                        return;
                                    }
                                    console.log('ok - set placeholder address');
                                    // self._renderAddressTimeout = setTimeout(function() {
                                        const addressData = {
                                            firstname: name,
                                            lastname: 'Hotai',
                                            street: [street],
                                            city: city,
                                            regionId: region,
                                            postcode: postcode || "000",
                                            countryId: window.checkoutConfig.defaultCountryId,
                                            telephone: telephone,
                                            save_in_address_book: 0
                                        }
                                        const newShippingAddress = addressConverter.formAddressDataToQuoteAddress(addressData);
                                        console.log('processGiftToFriend - newShippingAddress', newShippingAddress);
                                        // selectShippingAddress(newShippingAddress);
                                        quote.shippingAddress(newShippingAddress);
                                        quote.billingAddress(newShippingAddress);
                                        console.log(quote.shippingAddress().extensionAttributes);
                                        checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                                    // }, 1000);
                                    // self._renderAddressTimeout = setTimeout(function() {
                                    //     console.log('trigger click');
                                    //     $('.checkout-payment-method.submit .actions-toolbar .action.checkout').trigger('click');
                                    // }, 1000);
                                    // console.log('giftShippingAddress.data.validate', provider.get('params.invalid'));

                                    giftToFriendModel.placingOrder('placing');
                                    fullScreenLoader.startLoader();
                                    $('.checkout-payment-method.submit .actions-toolbar .action.checkout').addClass('disabled');
                                    return;
                                }
                            }
                        } else {
                            giftToFriendModel.placingOrder('');
                        }

                        console.log('placeOrder - shipping', {shipping, provider, quote, selectedShippingMethod, hotaiAddressType});

                        // console.log({arrayHomeDeliveryMethods, selectedShippingMethod, hotaiAddressType});
                        // console.log('111', {shipping, provider, quote, type: typeof shipping.sameAsPurchaser, value: shipping.sameAsPurchaser()});

                        // check if shipping address is same as purchaser/同購買人
                        if (typeof shipping.sameAsPurchaser !== "undefined" && shipping.sameAsPurchaser()) {
                            if (quote.shippingMethod()) {
                                if (hotaiAddressType === 'normal') {
                                    // validate shipping address
                                    var sameAsPurchaserAddressButtonAdd = $('#add-same-as-purchaser-address');
                                    if (sameAsPurchaserAddressButtonAdd.length && sameAsPurchaserAddressButtonAdd.is(":visible")) {
                                        provider.trigger('shippingAddress.data.validate');
                                        if (provider.get('params.invalid')) {
                                            errorMessage = $.mage.__('請新增配送地址。');
                                            messageList.addErrorMessage({message: errorMessage});
                                            sameAsPurchaserAddressButtonAdd.trigger('click');
                                        }
                                        errorMessage = $.mage.__('請新增配送地址。');
                                        messageList.addErrorMessage({message: errorMessage});
                                        return;
                                    }
                                    var sameAsPurchaserAddressButton = $('#use-same-as-purchaser-address-button');
                                    if (sameAsPurchaserAddressButton.length && sameAsPurchaserAddressButton.is(":visible")) {
                                        provider.trigger('shippingAddress.data.validate');
                                        if (provider.get('params.invalid')) {
                                            errorMessage = $.mage.__('請新增配送地址。');
                                            messageList.addErrorMessage({message: errorMessage});
                                        }
                                        errorMessage = $.mage.__('請新增配送地址。');
                                        messageList.addErrorMessage({message: errorMessage});
                                        return;
                                    }
                                } else if(hotaiAddressType === 'convenience_store') {
                                    var storeButton = $('#select-store-button');
                                    if (storeButton.length && storeButton.is(":visible")) {
                                        var name = $('#co-shipping-form-same-as-purchaser input[name=firstname]').val();
                                        var telephone = $('#co-shipping-form-same-as-purchaser input[name=telephone]').val();
                                        var city = $('#co-shipping-form-same-as-purchaser input[name=city]').val();
                                        var street = $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val();
                                        if(storeButton.hasClass('unsupported-store')){
                                            errorMessage = $.mage.__('暫不提供外島配送，請選擇台灣本島地區門市。');
                                        }else{
                                            errorMessage = $.mage.__('Please select store address');
                                        }
                                        messageList.addErrorMessage({message: errorMessage});
                                        return;
                                    }

                                    var shippingAddress = quote.shippingAddress();
                                    var billingAddress = quote.billingAddress();
                                    var pickupStoreAddress = $('.pickup-store-address');

                                    if (pickupStoreAddress.length && pickupStoreAddress.is(":visible") && shippingAddress && billingAddress && shippingAddress.city && shippingAddress.street) {
                                        var name = $('#co-shipping-form-same-as-purchaser input[name=firstname]').val();
                                        var telephone = $('#co-shipping-form-same-as-purchaser input[name=telephone]').val();
                                        var city = $('#co-shipping-form-same-as-purchaser input[name=city]').val();
                                        var street = $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val();
                                        console.log({quote, name, telephone, city, street, hotaiAddressType});
                                    }
                                }
                            }
                        }

                        // check if shipping address is 常用收件人
                        // logic validate for frequently used address
                        if (typeof shipping.sameAsPurchaser !== "undefined" && !shipping.sameAsPurchaser()) {
                            var isAnyAddressSelected = false;
                            var selectedAddress = $('input[name="select_shipping_address"]:checked');
                            if (selectedAddress.length && selectedAddress.is(':visible')) {
                                isAnyAddressSelected = true;
                            }

                            errorMessage = '';
                            if (!selectedAddress.length) {
                                errorMessage = $.mage.__('Please add a frequently used address');
                            } else if (!isAnyAddressSelected) {
                                var newAddressForm = $('#co-shipping-form-inline');
                                var name = $('#co-shipping-form-inline input[name=firstname]').val();
                                var telephone = $('#co-shipping-form-inline input[name=telephone]').val();
                                var city = $('#co-shipping-form-inline input[name=city]').val();
                                var street = $('#co-shipping-form-inline input[name="street[0]').val();
                                // console.log({name, telephone, city, street, hotaiAddressType});
                                if (newAddressForm.length && newAddressForm.is(":visible")) {
                                    provider.trigger('newShippingAddress.data.validate');
                                    if (provider.get('params.invalid')) {
                                        errorMessage = $.mage.__('Please complete select store address or input required address fields then add address');
                                    }
                                    // if (hotaiAddressType === 'convenience_store' && (name === '' || telephone === '' || city === '' || street === '')) {
                                    //     provider.trigger('newShippingAddress.data.validate');
                                    //     if (provider.get('params.invalid')) {
                                    //         errorMessage = $.mage.__('Please complete select store address or input required address fields then add address');
                                    //     }
                                    // } else if(hotaiAddressType === 'normal') {
                                    //     provider.trigger('newShippingAddress.data.validate');
                                    //     if (provider.get('params.invalid')) {
                                    //         errorMessage = $.mage.__('Please complete select store address or input required address fields then add address');
                                    //     }
                                    // }
                                } else {
                                    errorMessage = $.mage.__('Please select a frequently used address');
                                }
                            }
                            if (errorMessage) {
                                messageList.addErrorMessage({message: errorMessage});
                                return;
                            }

                        }

                        // console.log({shipping, provider, quote});
                    } else {
                        var shipping = registry.get('checkout.steps.shipping-step.shippingAddress');
                        var provider = registry.get('checkoutProvider');
                        console.log('processGiftToFriend - processGiftToFriendgiftToFriendModel', giftToFriendModel.placingOrder(), giftToFriendModel.allowGiftToFriend(), giftToFriendModel.useGiftToFriend(), giftToFriendModel.disabledGiftToFriend(), giftToFriendModel.recipientFillOption(), giftToFriendModel.placeholderGiftAddress());
                        if(giftToFriendModel.placingOrder() === '' && giftToFriendModel.useGiftToFriend() && !giftToFriendModel.disabledGiftToFriend() && giftToFriendModel.allowGiftToFriend()  && giftToFriendModel.recipientFillOption() === 1) {
                            self.processGiftToFriend();
                            return;
                        } else {
                            giftToFriendModel.placingOrder('');
                        }
                    }

                    const ecpayInvoiceForm = $('#ecpay-invoice-form'),
                        orderNoteForm = $('#order-note-form'),
                        referrerCodeForm = $('#referrer-code-form');

                      var checkInvoiceValidation = ecpayInvoiceForm.length && ecpayInvoiceForm.validation(),
                      checkOrderNoteValidation = orderNoteForm.length && orderNoteForm.validation(),
                      checkReferrerCodeValidation = referrerCodeForm.length && referrerCodeForm.validation();
                    // console.log({ecpayInvoiceForm, orderNoteForm, referrerCodeForm, checkInvoiceValidation, checkOrderNoteValidation, checkReferrerCodeValidation});
                    if (ecpayInvoiceForm.length && (!checkInvoiceValidation || !ecpayInvoiceForm.validation('isValid'))) {
                        return;
                    }

                    // // validate Invoice
                    var ecpay_invoice_type = $("#ecpay_invoice_type").val();
                    // // var ecpay_invoice_customer_identifier = $("#ecpay_invoice_customer_identifier").val();
                    var ecpay_invoice_carruer_type = $("#ecpay_invoice_carruer_type").val();
                    var invoiceError = $('.checkout-submit-wrapper .checkout-payment-method.submit .actions-toolbar .action.checkout').attr('invoice-error');

                    // check if an invoice type is 三聯式發票(公司)
                    if (ecpay_invoice_type === 'c' && ecpay_invoice_carruer_type === '0' && invoiceError) {
                        this.addMageError('customer_identifier', $.mage.__('輸入的統一編號錯誤，請確認後重新輸入'));
                        return;
                    }
                    // check if an invoice type is Two-part Personal Invoice
                    // console.log('ecpay_invoice_type', ecpay_invoice_type, ecpay_invoice_carruer_type);
                    if (ecpay_invoice_type === 'p' && ecpay_invoice_carruer_type === '0') {
                       $("#ecpay_invoice_carruer_num").val('');
                    }

                    if (referrerCodeForm.length && (!checkReferrerCodeValidation || !referrerCodeForm.validation('isValid'))) {
                        return;
                    }

                    if (orderNoteForm.length && (!checkOrderNoteValidation || !orderNoteForm.validation('isValid'))) {
                        return;
                    }
                    
                    if (quote.shippingMethod() && !giftToFriendModel.useGiftToFriend()) {
                        var shippingAddress = quote.shippingAddress();
                        var billingAddress = quote.billingAddress();
                        var errorMessage = $.mage.__('您選擇的地址與配送方式不符，請更換地址或重新選擇配送方式後再繼續。');

                        if (hotaiAddressType === 'normal') {
                            const hasNormalShippingAddress = shippingAddress?.customAttributes?.some(
                                attr => attr.attribute_code === "hotai_address_type" && attr.value === "normal"
                            );
                            const hasNormalBillingAddress = billingAddress?.customAttributes?.some(
                                attr => attr.attribute_code === "hotai_address_type" && attr.value === "normal"
                            );

                            // console.log(hasNormalShippingAddress, hasNormalBillingAddress);
                            if (!hasNormalShippingAddress || !hasNormalBillingAddress) {
                                messageList.addErrorMessage({message: errorMessage});
                                return;
                            }
                        } else if(hotaiAddressType === 'convenience_store') {
                            const hasConvenienceStoreShippingAddress = shippingAddress?.customAttributes?.some(
                                attr => attr.attribute_code === "hotai_address_type" && attr.value === "convenience_store"
                            );
                            const hasConvenienceStoreBillingAddress = billingAddress?.customAttributes?.some(
                                attr => attr.attribute_code === "hotai_address_type" && attr.value === "convenience_store"
                            );

                            // console.log(hasConvenienceStoreShippingAddress, hasConvenienceStoreBillingAddress);
                            if (!hasConvenienceStoreShippingAddress || !hasConvenienceStoreBillingAddress) {
                                messageList.addErrorMessage({message: errorMessage});
                                return;
                            } 
                        }

                    }

                    // console.log('placeOrder', hotaiAddressType, quote.shippingMethod(), quote.paymentMethod(), quote.billingAddress(), quote.shippingAddress());
                    // return;
                    // save custom fields,
                    // save order note and referrer code
                    // saveCustomFields();
                    startPlaceOrderAction();
                },

                addMageError: function (field, message) {
                    $("#ecpay_invoice_" + field).addClass('mage-error');
                    if( $("#ecpay_invoice_" + field).next().hasClass('mage-error')) {
                        $("#ecpay_invoice_" + field).next().html(message).removeAttr("style");
                    } else {
                        $("#ecpay_invoice_" + field).after('<div generated="true" class="mage-error" id="' + field + '_error">' + message + '</div>');
                    }
                },

                processGiftToFriend: function () {
                    const self = this;
                    var shipping = registry.get('checkout.steps.shipping-step.shippingAddress');
                    var provider = registry.get('checkoutProvider');
                    console.log('processGiftToFriend - processGiftToFriendgiftToFriendModel', giftToFriendModel.placingOrder(), giftToFriendModel.allowGiftToFriend(), giftToFriendModel.useGiftToFriend(), giftToFriendModel.disabledGiftToFriend(), giftToFriendModel.recipientFillOption(), giftToFriendModel.placeholderGiftAddress());
                    provider.trigger('giftShippingAddress.data.validate');
                    console.log('use gift', provider);
                    const giftForm = $('#gift-to-friend-form');
                    if (giftForm.length && giftForm.is(":visible")) {
                        const placeholderGiftAddress = giftToFriendModel.placeholderGiftAddress();

                        var name = $('#gift-to-friend-form input[name=firstname]').val(); //|| placeholderGiftAddress.firstname;
                        var telephone = $('#gift-to-friend-form input[name=telephone]').val(); // || placeholderGiftAddress.telephone;
                        var region = $('#gift-to-friend-form select[name=region_id]').val() || placeholderGiftAddress.region_id;
                        var city = $('#gift-to-friend-form select[name=city_id]').val() || placeholderGiftAddress.city;
                        var street = $('#gift-to-friend-form input[name="street[0]"]').val() || placeholderGiftAddress.street;
                        var postcode = $('#gift-to-friend-form input[name=postcode]').val() || '000';

                        giftToFriendModel.filledAddress('');
                        var filledAddress = '';

                        console.log({name, telephone, region, city, street, postcode});
                        if(!name) {
                            name = placeholderGiftAddress.firstname;
                        } else {
                            filledAddress += 'name';
                        }
                        if(!telephone) {
                            telephone = placeholderGiftAddress.telephone;
                        } else {
                            filledAddress += filledAddress ? ',telephone' : 'telephone';
                        }
                        console.log({name, telephone, region, city, street, postcode, filledAddress});
                        giftToFriendModel.filledAddress(filledAddress);

                        if (self?._renderAddressTimeout) {
                            clearTimeout(self._renderAddressTimeout);
                        }
                        // error: Special characters are not allowed.
                        var textError = $.mage.__('不接受特殊符號。');
                        if((name === '' || telephone === '') && provider.get('params.invalid')) {
                            // var errorMessage = $.mage.__('請新增配送地址。');
                            messageList.addErrorMessage({message: textError});
                            return;
                        } else {
                            if($('#gift-to-friend-form input[name=firstname]').hasClass('mage-error') ||
                                $('#gift-to-friend-form input[name=telephone]').hasClass('mage-error')) {
                                console.log('giftToFriendModel - placingOrder error');
                                // var errorMessage = $.mage.__('請新增配送地址。');
                                messageList.addErrorMessage({message: textError});
                                return;
                            }
                            console.log('ok - set placeholder address');
                            // self._renderAddressTimeout = setTimeout(function() {
                                const addressData = {
                                    firstname: name,
                                    lastname: 'Hotai',
                                    street: [street],
                                    city: city,
                                    regionId: region,
                                    postcode: postcode || "000",
                                    countryId: window.checkoutConfig.defaultCountryId,
                                    telephone: telephone,
                                    save_in_address_book: 0
                                }
                                const newShippingAddress = addressConverter.formAddressDataToQuoteAddress(addressData);
                                console.log('processGiftToFriend - newShippingAddress', newShippingAddress);
                                // selectShippingAddress(newShippingAddress);
                                quote.shippingAddress(newShippingAddress);
                                quote.billingAddress(newShippingAddress);
                                console.log(quote);
                                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                                checkoutData.setSelectedBillingAddress(newShippingAddress.getKey());
                                setBillingAddressAction(messageList);
                            // }, 1000);
                            // self._renderAddressTimeout = setTimeout(function() {
                            //     console.log('trigger click');
                            //     $('.checkout-payment-method.submit .actions-toolbar .action.checkout').trigger('click');
                            // }, 1000);
                            // console.log('giftShippingAddress.data.validate', provider.get('params.invalid'));

                            giftToFriendModel.placingOrder('placing');
                            fullScreenLoader.startLoader();
                            $('.checkout-payment-method.submit .actions-toolbar .action.checkout').addClass('disabled');
                        }
                    }
                }

            });
        };
    }
);

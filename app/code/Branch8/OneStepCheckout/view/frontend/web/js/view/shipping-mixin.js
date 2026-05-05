define(
    [
        'underscore',
        'ko',
        'jquery',
        'mage/url',
        'Magento_Customer/js/model/address-list',
        'Magento_Customer/js/model/customer/address',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/model/new-customer-address',
        'Magento_Ui/js/lib/view/utils/async',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/customer-email-validator',
        'Amasty_CheckoutCore/js/view/utils',
        'Amasty_CheckoutCore/js/model/payment/payment-loading',
        'Amasty_CheckoutCore/js/action/get-totals',
        'Amasty_CheckoutCore/js/model/shipping-registry',
        'Amasty_CheckoutCore/js/model/address-form-state',
        'Amasty_CheckoutCore/js/model/events',
        'Amasty_CheckoutStyleSwitcher/js/model/amalert',
        'Magento_Ui/js/model/messageList',
        'uiRegistry',
        'rjsResolver',
        'mage/translate',
        'Branch8_OneStepCheckout/js/model/text-helper',
        'Branch8_OneStepCheckout/js/model/store-pickup',
        'Branch8_OneStepCheckout/js/model/gift-to-friend',
        'Magento_Checkout/js/model/shipping-save-processor',
        'plugins/DOMPurify',
        'Magento_Ui/js/modal/modal',
        'validation'
    ],
    function (
        _,
        ko,
        $,
        urlBuilder,
        addressList,
        Address,
        createShippingAddress,
        newCustomerAddress,
        async,
        customer,
        addressConverter,
        customerData,
        checkoutData,
        shippingService,
        selectShippingAddress,
        selectBillingAddress,
        selectShippingMethod,
        setShippingInformationAction,
        setBillingAddressAction,
        quote,
        emailValidator,
        viewUtils,
        paymentLoader,
        totalsProcessor,
        shippingRegistry,
        addressFormState,
        events,
        alert,
        messageList,
        registry,
        onLoad,
        $t,
        textHelper,
        storePickupModel,
        giftToFriendModel,
        shippingSaveProcessor,
        DOMPurify,
        modal
    ) {
        'use strict';

        var countryData = customerData.get('directory-data');
        window.setNewAddressAsSelected = '';
        var regions = registry.get('checkoutProvider').get('dictionaries.region_id');

        return function (Shipping) {
            return Shipping.extend({
                allowedDynamicalSave: false,
                allowedDynamicalValidation: false,
                isUpdateCancelledByBilling: false,
                isInitialDataSaved: false,
                previousShippingMethodData: {},
                sameAsPurchaser: ko.observable(1),
                defaultBillingAddress: '',
                defaultConvenienceAddress: ko.observable(''),
                sameAsPurchaserRegion: ko.observable(''),
                sameAsPurchaserCity: ko.observable(''),
                sameAsPurchaserAddress: ko.observable(''),
                sameAsPurchaserAddressData: ko.observable(''),
                isSelectStoreVisible: ko.observable(false),
                isStoreSelected: ko.observable(false),
                isUnsupportedStore: ko.observable(false),
                isStoreSelectedOnFrequentlyAddress: ko.observable(false),
                storeName: ko.observable(''),
                storeNameOnFrequentlyAddress: ko.observable(''),
                storeAddress: ko.observable(''),
                storeAddressOnFrequentlyAddress: ko.observable(''),
                storeData: ko.observable(null),
                customerAddresses: ko.observableArray(addressList()),
                customer: window.customerData,
                newAddressAsSelected: ko.observable(''),
                clickedAddNewAddress: ko.observable(false),
                isShowDefaultBillingAddress: ko.observable(false),
                isShowDefaultConvenienceAddress: ko.observable(false),
                defaultConvenienceStoreId: ko.observable(''),
                isAddedNewAddress: ko.observable(false),
                useGiftToFriend: ko.observable(giftToFriendModel.useGiftToFriend()),
                disabledGiftToFriend: ko.observable(giftToFriendModel.disabledGiftToFriend()),
                recipientFillOption: ko.observable(giftToFriendModel.recipientFillOption()),
                allowGiftToFriend: ko.observable(giftToFriendModel.allowGiftToFriend()),
                placeholderGiftAddress: ko.observable(giftToFriendModel.placeholderGiftAddress()),
                giftStep1Url: ko.observable(giftToFriendModel.giftStep1Url()),
                giftStep2Url: ko.observable(giftToFriendModel.giftStep2Url()),
                giftStep3Url: ko.observable(giftToFriendModel.giftStep3Url()),
                isStep1: ko.observable(true),
                isStep2: ko.observable(false),
                isStep3: ko.observable(false),
                showOptionAutoOpenGiftNotePopup: ko.observable(true),
                isAutoOpenGiftNotePopup: ko.computed(function () {
                    var customerData = window.customerData;
                    var giftNotePopup = window.localStorage.getItem('giftNotePopup') || '{}';
                    // console.log('giftNotePopup', {customerData, giftNotePopup , data:JSON.parse(giftNotePopup)});
                    giftNotePopup = JSON.parse(giftNotePopup);
                    if (giftNotePopup?.id && customerData?.id && giftNotePopup.id != customerData.id) {
                        window.localStorage.removeItem('giftNotePopup');
                        return true;
                    }
                    if (giftNotePopup?.auto) {
                        return giftNotePopup.auto === 'true';
                    }
                    return true;
                }, this),

                isAutoOpenGiftNoteChecked: ko.observable(true),

                initialize: function () {
                    this._super();
                    this.setDefaultShippingmethod();
                    this.defaultConvenienceStoreId(this.customer?.custom_attributes?.default_convenience_store?.value || '');
                    this.processSalesPresentativeAddress();
                    this.processGiftToFriendVirtual();
                    this.processConvenienceData();

                    var giftNotePopup = window.localStorage.getItem('giftNotePopup')
                    if (giftNotePopup && this.isAutoOpenGiftNotePopup()) {
                        this.isAutoOpenGiftNoteChecked(false);
                    }

                    // console.log('initialize - openGiftStepsPopup', this.isAutoOpenGiftNoteChecked(), this.isAutoOpenGiftNotePopup(), window.localStorage.getItem('giftNotePopup'));
                },

                initObservable: function () {
                    this._super();
                    var self = this;

                    // this.clickedAddNewAddress = ko.computed(function () {
                    //     var clickedAddNewAddress = true,
                    //         shippingAddress = quote.selectedShipping();
                    //     if (shippingAddress) {
                    //         clickedAddNewAddress = false;
                    //     }

                    //     console.log('clickedAddNewAddress', clickedAddNewAddress);

                    //     return clickedAddNewAddress;
                    // }, this);

                    // this.isAutoOpenGiftNotePopup = ko.computed(function () {
                    //     const autoOpenGiftNotePopup = window.localStorage.getItem('autoOpenGiftNotePopup') || '{}';
                    //     console.log('autoOpenGiftNotePopup', this.customer, autoOpenGiftNotePopup , JSON.parse(autoOpenGiftNotePopup));
                    //     return true;
                    // }, this);

                    this.isHideBorderLine = ko.computed(function () {
                        // console.log('isHideBorderLine', self.isVirtualCheckout(), self.sameAsPurchaser(), self.useGiftToFriend(), this.isVirtualCheckout() && this.allowGiftToFriend() && this.sameAsPurchaser() === 3 && !self.useGiftToFriend());
                        return this.isVirtualCheckout() && this.allowGiftToFriend() && this.sameAsPurchaser() === 3 && !self.useGiftToFriend();
                    }, this);

                    this.isGiftAvailable = ko.computed(function () {
                        var check = quote.getItems() && quote.getItems().length > 0 && quote.getItems().some(function (item) { 
                            return item?.available_to_checkout == 1 && item?.seller_code == 'M0037'; 
                        });
                        return this.allowGiftToFriend() && !check;
                    }, this);

                    addressList.subscribe(function (newAddressList) {
                        // console.log('addressList.subscribe', {newAddressList, list: addressList()});
                        self.customerAddresses(addressList());
                        self.setDefaultShippingAddress();
                        self.showDefaultBillingAddress();
                        self.showConvenienceStoreAddress();
                    }, this, 'arrayChange');

                    // using for what?
                    if (this.isCustomerLoggedIn()) {
                        shippingRegistry.excludedCollectionNames.push('shipping-address-fieldset');
                    }

                    this.disabledGiftToFriend.subscribe(function (disabled) {
                        // console.log('disabledGiftToFriend.subscribe', disabled);
                        giftToFriendModel.disabledGiftToFriend(disabled);
                    });

                    this.sameAsPurchaser.subscribe(function (isSame) {
                        quote.selectedShipping(false);
                        const shippingMethod = quote.shippingMethod();
                        console.log('sameAsPurchaser.subscribe', { isSame, shippingMethod });
                        if (shippingMethod && (shippingMethod.method_code === 'hotai_711' || shippingMethod.carrier_code === 'hotai_711')) {
                            self.disabledGiftToFriend(true);
                            giftToFriendModel.disabledGiftToFriend(true);
                        } else {
                            self.disabledGiftToFriend(false);
                            giftToFriendModel.disabledGiftToFriend(false);
                        }

                        if (isSame) {
                            if (isSame === 1) {
                                self.useSameAsPurchaser();
                                self.useGiftToFriend(false);
                                giftToFriendModel.useGiftToFriend(false);
                            } else if (isSame === 2) {
                                self.useGiftToFriend(true);
                                giftToFriendModel.useGiftToFriend(true);
                                self.processGiftToFriend();
                                console.log('useGiftToFriend', self.useGiftToFriend());
                            } else if (isSame === 3) {
                                console.log('useGiftToFriend - Unselect', self.useGiftToFriend());
                                self.useGiftToFriend(false);
                                giftToFriendModel.useGiftToFriend(false);
                                self.resetGiftToFriendForm();
                                setBillingAddressAction(messageList);
                            }
                        } else {
                            self.useGiftToFriend(false);
                            giftToFriendModel.useGiftToFriend(false);
                            if (self.isCustomerLoggedIn()) {
                                // self.useDefaultShippingAddress();
                                self.isFormInline = false;
                            } else {
                                self.isFormInline = true;
                            }
                        }
                    });

                    this.recipientFillOption.subscribe(function (option) {
                        console.log('recipientFillOption.subscribe', option);
                        giftToFriendModel.recipientFillOption(option);
                        if (option === 1) {
                            // reset recipient data
                            self.resetGiftToFriendForm();
                        }
                        if (self.checkToAutoShow()) {
                            self.showOptionAutoOpenGiftNotePopup(true);
                            self.openGiftStepsPopup();
                        }
                        self.processGiftToFriend();
                    });

                    this.isAutoOpenGiftNoteChecked.subscribe(function (isChecked) {
                        console.log('isAutoOpenGiftNotePopup.subscribe', isChecked);
                        // self.isAutoOpenGiftNotePopup(!isChecked);
                        if (isChecked) {
                            window.localStorage.setItem('giftNotePopup', JSON.stringify({ id: self.customer.id, auto: 'false' }));
                        } else {
                            window.localStorage.setItem('giftNotePopup', JSON.stringify({ id: self.customer.id, auto: 'true' }));
                        }
                    });

                    quote.selectedShipping.subscribe(function (newSelectedAddress) {
                        console.log('quote.selectedShipping.subscribe', newSelectedAddress);
                        if (newSelectedAddress) {
                            self.clickedAddNewAddress(false);
                        }
                    });

                    quote.shippingMethod.subscribe(function (newMethod) {
                        console.log('quote.shippingMethod.subscribe', { newMethod, aa: self.sameAsPurchaser() });

                        if (!newMethod) {
                            return;
                        }

                        self.processSalesPresentativeAddress();

                        console.log('quote.shippingMethod.subscribe - newMethod', newMethod);

                        if (newMethod?.carrier_code && newMethod?.method_code && (newMethod.method_code !== 'hotai_711' && !newMethod.carrier_code === 'hotai_711')) {
                            self.removeAddressData();
                        }

                        if (newMethod?.carrier_code && newMethod?.method_code && (newMethod.method_code === 'hotai_711' || newMethod.carrier_code === 'hotai_711')) {
                            $('#same-as-purchaser-address-form').hide();
                            self.disabledGiftToFriend(true);
                            self.useGiftToFriend(false);
                            giftToFriendModel.useGiftToFriend(false);
                            var checkoutAddressData = window.checkoutConfig.checkoutAddress;
                            console.log('set sameAsPurchasse', { checkoutAddressData })
                            if (checkoutAddressData) {
                                let isSameAsPurchaser = (checkoutAddressData.checkSameAsPurchaser == 'true' || checkoutAddressData.checkSameAsPurchaser == true);
                                self.sameAsPurchaser(isSameAsPurchaser ? 1 : 0);
                            } else {
                                if (self.sameAsPurchaser() !== 0) {
                                    self.sameAsPurchaser(1);
                                }
                            }
                        } else {
                            self.disabledGiftToFriend(false);
                        }

                        // set isSelectStoreVisible to true/false
                        self.checkShowSelectStoreButton();
                        self.setDefaultShippingAddress();
                        self.showDefaultBillingAddress();
                        self.showConvenienceStoreAddress();

                        const isSame = self.sameAsPurchaser();

                        if (isSame) {
                            if (isSame === 1) {
                                self.useSameAsPurchaser();
                            } else if (isSame === 2) {
                                console.log('useGiftToFriend', self.useGiftToFriend());
                                self.processGiftToFriend();
                            } else if (isSame === 3) {
                                console.log('useGiftToFriend - Unselect', self.useGiftToFriend());
                            }
                        } else {
                            if (self.isCustomerLoggedIn()) {
                                // self.useDefaultShippingAddress();
                                self.isFormInline = false;
                            } else {
                                self.isFormInline = true;
                            }

                            //clear current input if shipping method change
                            $('#co-shipping-form-inline').hide();
                            $('#add-new-address-label').show();
                        }
                    });

                    storePickupModel.selectedStore.subscribe(function (store) {
                        // console.log('storePickupModel.selectedStore.subscribe', store);
                        if (store) {
                            if (self.sameAsPurchaser()) {
                                self.storeName('711' + store.name);
                                self.storeAddress(store.address);
                                self.isStoreSelected(true);
                            } else {
                                self.storeNameOnFrequentlyAddress('711' + store.name);
                                self.storeAddressOnFrequentlyAddress(store.address);
                                self.isStoreSelectedOnFrequentlyAddress(true);
                            }
                        }
                    });

                    shippingService.getShippingRates().subscribe(function (rates) {
                        console.log('shipping rates', rates);
                        var shippingMethod = quote.shippingMethod();
                        console.log('shipping method', shippingMethod);
                        var checkoutAddressData = window.checkoutConfig.checkoutAddress;
                        // var checkoutAddressData = JSON.parse(checkoutAddressData);
                        var storeData = window.checkoutConfig.storeCheckoutData;
                        storeData = JSON.parse(storeData);
                        console.log({ checkoutAddressData, storeData });

                        if (checkoutAddressData && storeData) {

                        } else {
                            if (!shippingMethod && rates.length > 1) {
                                console.log('shipping method', shippingMethod);
                                checkoutData.setSelectedShippingRate('hotai_delivery_hotai_delivery');
                            }
                        }

                    });

                    this.checkShowSelectStoreButton();
                    this.actionGiftToFriendForm();

                    return this;
                },

                setDefaultShippingmethod: function () {
                    var self = this;
                    var rates = shippingService.getShippingRates();
                    console.log('setDefaultShippingmethod', quote, quote.shippingMethod(), self.rates(), this, rates(), shippingService.getShippingRates());
                },

                actionGiftToFriendForm: function () {
                    const self = this;
                    $(document).on('change, input', '#co-shipping-form-gift input[name="firstname"], #co-shipping-form-gift input[name="street[0]"], #co-shipping-form-gift input[name="telephone"], #co-shipping-form-gift select[name="region_id"], #co-shipping-form-gift select[name="city_id"]', function () {
                        giftToFriendModel.placingOrder('');
                    });
                },

                processSalesPresentativeAddress: function () {
                    console.log('processSalesPresentativeAddress', this.allowGiftToFriend(), this.placeholderGiftAddress(), this.useGiftToFriend(), this.disabledGiftToFriend(), this.recipientFillOption());
                    if (this.allowGiftToFriend()) {
                        const salesPresentativeAddress = giftToFriendModel.salesPresentativeAddress();
                        if (salesPresentativeAddress && salesPresentativeAddress.name !== '' && salesPresentativeAddress.phone !== '') {
                            this.sameAsPurchaser(2);
                            this.recipientFillOption(1);
                            const self = this;
                            // reset recipient data
                            if (self._renderSaleAddressTimeout) {
                                clearTimeout(self._renderSaleAddressTimeout);
                            }
                            self._renderSaleAddressTimeout = setTimeout(function () {
                                self.resetGiftToFriendForm();
                            }, 2000);
                        }
                    }
                },

                processGiftToFriendVirtual: function () {
                    if (this.isVirtualCheckout() && this.allowGiftToFriend()) {
                        const salesPresentativeAddress = giftToFriendModel.salesPresentativeAddress();
                        if (salesPresentativeAddress && salesPresentativeAddress.name !== '' && salesPresentativeAddress.phone !== '') {
                            this.sameAsPurchaser(2);
                        } else {
                            this.sameAsPurchaser(3);
                        }
                    }
                },

                checkGiftToFriendData: function () {
                    const giftForm = $('#gift-to-friend-form');
                    var name = $('#gift-to-friend-form input[name=firstname]').val();
                    var telephone = $('#gift-to-friend-form input[name=telephone]').val();
                    var region = $('#gift-to-friend-form select[name=region_id]').val();
                    var city = $('#gift-to-friend-form select[name=city_id]').val();
                    var street = $('#gift-to-friend-form input[name="street[0]"]').val();
                    console.log('checkGiftToFriendData', { name, telephone, region, city, street, giftForm: giftForm.length, giftFormVisible: giftForm.is(":visible"), useGiftToFriend: giftToFriendModel.useGiftToFriend(), disabledGiftToFriend: giftToFriendModel.disabledGiftToFriend(), allowGiftToFriend: giftToFriendModel.allowGiftToFriend(), recipientFillOption: giftToFriendModel.recipientFillOption() });
                    if (giftToFriendModel.useGiftToFriend() && !giftToFriendModel.disabledGiftToFriend() && giftToFriendModel.allowGiftToFriend() && giftToFriendModel.recipientFillOption() === 1 && giftForm.length && giftForm.is(":visible") && name !== '' && telephone !== '' && region !== '' && city !== '' && street !== '') {
                        console.log('ok - checkGiftToFriendData - set placeholder address');
                        const addressData = {
                            firstname: name,
                            lastname: 'Hotai',
                            street: [street],
                            city: city,
                            regionId: region,
                            postcode: "000",
                            countryId: window.checkoutConfig.defaultCountryId,
                            telephone: telephone,
                            save_in_address_book: 0
                        }
                        const newShippingAddress = addressConverter.formAddressDataToQuoteAddress(addressData);
                        console.log('processGiftToFriend - newShippingAddress', newShippingAddress);
                        // selectShippingAddress(newShippingAddress);
                        quote.shippingAddress(newShippingAddress);
                        quote.billingAddress(newShippingAddress);
                        checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                    }
                },

                processGiftToFriend: function () {
                    console.log('processGiftToFriend', this.allowGiftToFriend(), this.placeholderGiftAddress(), this.useGiftToFriend(), this.disabledGiftToFriend(), this.recipientFillOption());
                    if (this.allowGiftToFriend() && !this.disabledGiftToFriend() && this.useGiftToFriend()) {
                        // todo
                        console.log('ok');
                        if (this.recipientFillOption() === 2) {
                            const placeholderAddress = this.placeholderGiftAddress();
                            const checkAddress = placeholderAddress && placeholderAddress.street && placeholderAddress.street !== ''
                                && placeholderAddress.firstname && placeholderAddress.firstname !== ''
                                && placeholderAddress.telephone && placeholderAddress.telephone !== ''
                                && placeholderAddress.city && placeholderAddress.city !== ''
                                && placeholderAddress.region_id && placeholderAddress.region_id !== '';
                            console.log('processGiftToFriend - checkAddress', checkAddress, placeholderAddress);
                            if (checkAddress) {
                                console.log('ok - set placeholder address');
                                const addressData = {
                                    firstname: placeholderAddress.firstname,
                                    lastname: placeholderAddress.lastname,
                                    street: [placeholderAddress.street],
                                    city: placeholderAddress.city,
                                    regionId: placeholderAddress.region_id,
                                    postcode: "000",
                                    countryId: window.checkoutConfig.defaultCountryId,
                                    telephone: placeholderAddress.telephone,
                                    save_in_address_book: 0
                                }
                                const newShippingAddress = addressConverter.formAddressDataToQuoteAddress(addressData);
                                console.log('processGiftToFriend - newShippingAddress', newShippingAddress);
                                // selectShippingAddress(newShippingAddress);
                                quote.shippingAddress(newShippingAddress);
                                quote.billingAddress(newShippingAddress);
                                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                                if (this.isVirtualCheckout()) {
                                    checkoutData.setSelectedBillingAddress(newShippingAddress.getKey());
                                    setBillingAddressAction(messageList);
                                }
                                // quote.shippingAddress().extensionAttributes.is_gift_order = true;
                                // quote.shippingAddress().extensionAttributes.gift_address_type = 2;
                                // shippingSaveProcessor.saveShippingInformation(newShippingAddress.getType());
                            } else {
                                messageList.addErrorMessage({ message: $.mage.__("Please set the placeholder address in the configuration.") });
                            }
                        } else {
                            // gift to friend address form
                        }
                    }
                },

                resetGiftToFriendForm: function () {
                    console.log('resetGiftToFriendForm', this.isVirtualCheckout(), this.placeholderGiftAddress());
                    if (this.isVirtualCheckout()) {
                        $('#gift-to-friend-form div[name="giftShippingAddress.city_id"]').addClass("hidden-field");
                        $('#gift-to-friend-form div[name="giftShippingAddress.region_id"]').addClass("hidden-field");
                        $('#gift-to-friend-form .field.street').addClass("hidden-field");
                    }
                    const salesPresentativeAddress = giftToFriendModel.salesPresentativeAddress();
                    if (salesPresentativeAddress && salesPresentativeAddress.name !== '' && salesPresentativeAddress.phone !== '') {
                        console.log('resetGiftToFriendForm - set salesPresentativeAddress', salesPresentativeAddress);
                        // const regionId = salesPresentativeAddress.region_id || '';
                        const regions = registry.get('checkoutProvider').get('dictionaries.region_id');
                        console.log('resetGiftToFriendForm - regions', regions);
                        const filtered = regions.filter(region => region.label === salesPresentativeAddress.address.city.replace(/^台/, "臺"));
                        const regionId = filtered.length > 0 ? filtered[0].value : '';
                        // const cities = JSON.parse(window.checkoutConfig.cities);
                        // const citiesFiltered = cities[regionId];
                        // console.log(filtered, regionId, cities, citiesFiltered);
                        // let cityId = '';
                        // if(citiesFiltered && citiesFiltered.length > 0) {
                        //     cityId = citiesFiltered.find(city => city === salesPresentativeAddress.address.city) || '';
                        // }
                        // console.log('resetGiftToFriendForm - cityId', cityId);
                        // console.log($('#co-shipping-form-gift input[name=firstname]'), $('#co-shipping-form-gift input[name=telephone]'), $('#co-shipping-form-gift input[name="street[0]"]'), $('#co-shipping-form-gift select[name=region_id]'), $('#co-shipping-form-gift input[name=region]'), $('#co-shipping-form-gift select[name=city_id]'), $('#co-shipping-form-gift input[name=city]'), $('#co-shipping-form-gift input[name=postcode]'));
                        $('#co-shipping-form-gift input[name=firstname]').val(salesPresentativeAddress.name).change();
                        $('#co-shipping-form-gift input[name=telephone]').val(salesPresentativeAddress.phone).change();
                        $('#co-shipping-form-gift input[name="street[0]"]').val(salesPresentativeAddress.address.detail).change();

                        if (regionId !== '') {
                            $('#co-shipping-form-gift select[name=region_id]').val(regionId).removeClass('empty').change();
                            $('#co-shipping-form-gift input[name=region]').val(salesPresentativeAddress.address.city.replace(/^台/, "臺")).change();
                        } else {
                            $('#co-shipping-form-gift select[name=region_id]').val('').addClass('empty')
                            $('#co-shipping-form-gift input[name=region]').val('')
                        }
                        $('#co-shipping-form-gift select[name=city_id]').val(salesPresentativeAddress.address.district.replace(/^台/, "臺")).removeClass('empty').change();
                        $('#co-shipping-form-gift input[name=city]').val(salesPresentativeAddress.address.district.replace(/^台/, "臺")).change();
                        $('#co-shipping-form-gift input[name=postcode]').val(salesPresentativeAddress.address.zipcode || '000').change();


                        // $('#co-shipping-form-gift input[name=telephone]').val('');
                        // $('#co-shipping-form-gift input[name="street[0]"]').val('');
                        // $('#co-shipping-form-gift select[name=region_id]').val('').addClass('empty');
                        // $('#co-shipping-form-gift input[name=region]').val('');
                        // $('#co-shipping-form-gift select[name=city_id]').val('').addClass('empty');
                        // $('#co-shipping-form-gift input[name=city]').val('');
                    } else {
                        $('#co-shipping-form-gift input[name=firstname]').val('');
                        $('#co-shipping-form-gift input[name=telephone]').val('');

                        if (this.isVirtualCheckout()) {
                            const placeholderAddress = this.placeholderGiftAddress();
                            $('#gift-to-friend-form select[name=region_id]').val(placeholderAddress.region_id);
                            $('#gift-to-friend-form select[name=city_id]').val(placeholderAddress.city);
                            $('#gift-to-friend-form input[name="street[0]"]').val(placeholderAddress.street);
                        } else {
                            $('#co-shipping-form-gift input[name="street[0]"]').val('');
                            $('#co-shipping-form-gift select[name=region_id]').val('').addClass('empty');
                            $('#co-shipping-form-gift input[name=region]').val('');
                            $('#co-shipping-form-gift select[name=city_id]').val('').addClass('empty');
                            $('#co-shipping-form-gift input[name=city]').val('');
                        }
                    }
                },

                onAfterRendeGiftToFriend: function () {
                    console.log('onAfterRendeGiftToFriend', this.recipientFillOption());
                    const self = this;
                    // if(!this.recipientFillOption()) {
                    // reset recipient data
                    if (self._renderAddressTimeout) {
                        clearTimeout(self._renderAddressTimeout);
                    }
                    self._renderAddressTimeout = setTimeout(function () {
                        self.resetGiftToFriendForm();
                    }, 150);
                    // }
                },

                useGiftAddress: function () {
                    console.log('useGiftAddress');
                },

                removeAddressData: function () {
                    console.log('removeAddressData');
                    const hasStoredAdd = window.localStorage.getItem('hasStoredAdd');
                    if (hasStoredAdd) {
                        window.checkoutConfig.checkoutAddress = null;
                        window.checkoutConfig.storeCheckoutData = null;
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
                },

                removeAddressData711: function () {
                    const hasStoredAdd = window.localStorage.getItem('hasStoredAdd');
                    const checkoutAddressData = window.checkoutConfig.checkoutAddress;
                    if (!hasStoredAdd && checkoutAddressData) {
                        window.checkoutConfig.checkoutAddress = null;
                        window.checkoutConfig.storeCheckoutData = null;
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
                },

                validatePlaceOrder: function () {
                    // console.log('validatePlaceOrder - Amasty - Hotai');
                    this._super();
                },

                /**
                * Trigger Shipping data Validate Event.
                * @returns {void}
                */
                triggerShippingDataValidateEvent: function () {
                    // console.log('triggerShippingDataValidateEvent - Amasty  - Hotai');
                    var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                    var hotaiAddressType = 'normal';
                    if (quote.shippingMethod()) {
                        var selectedShippingMethod = quote.shippingMethod().method_code;
                        if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                            hotaiAddressType = 'convenience_store';
                        }
                    }
                    if (hotaiAddressType === 'normal' || (!this.sameAsPurchaser() && hotaiAddressType === 'convenience_store')) {
                        // console.log('triggerShippingDataValidateEvent - Amasty  - Hotai validation');
                        this.source.trigger('shippingAddress.data.validate');

                        if (this.source.get('shippingAddress.custom_attributes')) {
                            this.source.trigger('shippingAddress.custom_attributes.data.validate');
                        }
                    }
                },

                resetSameAsPurchaserAddressForm: function () {
                    // console.log('resetSameAsPurchaserAddressForm');
                    // set name, telephone to form
                    $('#co-shipping-form-same-as-purchaser input[name=firstname]').val(this.getCustomerName()).change();
                    $('#co-shipping-form-same-as-purchaser input[name=telephone]').val(this.getTelephone()).change();

                    // console.log('resetSameAsPurchaserAddressForm', this.getCustomerName(), this.getTelephone());
                    // reset other data
                    $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val('').change();
                    $('#co-shipping-form-same-as-purchaser input[name="region"]').val('').change();
                    $('#co-shipping-form-same-as-purchaser select[name="region_id"]').val('').change();
                    $('#co-shipping-form-same-as-purchaser input[name="city"]').val('').change();
                    $('#co-shipping-form-same-as-purchaser select[name="city_id"]').val('').change();
                    // console.log('resetSameAsPurchaserAddressForm',  $('#co-shipping-form-same-as-purchaser select[name="region_id"]'),  $('#co-shipping-form-same-as-purchaser select[name="city_id"]'));
                },

                resetSameAsPurchaserAddressForm711: function () {
                    // console.log('resetSameAsPurchaserAddressForm711');
                    // set name, telephone to form
                    $('#co-shipping-form-same-as-purchaser input[name=firstname]').val(this.getCustomerName()).change();
                    $('#co-shipping-form-same-as-purchaser input[name=telephone]').val(this.getTelephone()).change();

                    // console.log('resetSameAsPurchaserAddressForm711', this.getCustomerName(), this.getTelephone());
                    // reset other data
                    $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val('').change();
                    // $('#co-shipping-form-same-as-purchaser input[name="region"]').val('').change();
                    // $('#co-shipping-form-same-as-purchaser select[name="region_id"]').val('').change();
                    $('#co-shipping-form-same-as-purchaser input[name="city"]').val('').change();
                    // $('#co-shipping-form-same-as-purchaser select[name="city_id"]').val('').change();
                    // console.log('resetSameAsPurchaserAddressForm711',  $('#co-shipping-form-same-as-purchaser select[name="region_id"]'),  $('#co-shipping-form-same-as-purchaser select[name="city_id"]'));
                },


                setDefaultShippingAddress: function () {
                    const self = this;
                    //defaultConvenienceStoreId = this.customer?.custom_attributes?.default_convenience_store?.value;

                    // set default shipping address/billing address
                    addressList.some(function (addrs) {
                        if (addrs.isDefaultBilling() || addrs.isDefaultShipping()) {
                            self.defaultBillingAddress = addrs;
                        }
                        if (self.defaultConvenienceStoreId() && addrs.customerAddressId === self.defaultConvenienceStoreId()) {
                            self.defaultConvenienceAddress = addrs;
                        }
                        if (addrs?.default_convenience_store) {
                            self.defaultConvenienceAddress = addrs;
                        }
                    });

                    // if(!self.defaultBillingAddress && !self.defaultConvenienceAddress) {
                    //     // reset same as purchaser form
                    //     self.resetSameAsPurchaserAddressForm();
                    // }

                    // console.log('setDefaultShippingAddress', self.defaultBillingAddress, self.defaultConvenienceAddress);
                },

                selectShippingMethod: function (method) {
                    console.log('selectShippingMethod', method);
                    this._super(method);
                    return true;
                },

                checkShowSelectStoreButton: function () {
                    // Check if the current shipping method is 'hotai_711'
                    if (quote.shippingMethod() && quote.shippingMethod().method_code == 'hotai_711') {
                        this.isSelectStoreVisible(true);
                    } else {
                        this.isSelectStoreVisible(false);
                    }
                },

                useSameAsPurchaser: function () {
                    // console.log('useSameAsPurchaser', this.sameAsPurchaser(), this.defaultBillingAddress, this.defaultConvenienceAddress,this.isShowDefaultBillingAddress(), this.isShowDefaultConvenienceAddress());
                    this.isFormInline = true;
                    if (this.isShowDefaultBillingAddress()) {
                        this.isFormInline = false;
                        shippingRegistry.isAddressChanged(true);
                        selectShippingAddress(this.defaultBillingAddress);
                        checkoutData.setSelectedShippingAddress(this.defaultBillingAddress.getKey());
                        selectBillingAddress(this.defaultBillingAddress);
                    }
                    if (this.isShowDefaultConvenienceAddress()) {
                        this.isFormInline = false;
                        shippingRegistry.isAddressChanged(true);
                        selectShippingAddress(this.defaultConvenienceAddress);
                        checkoutData.setSelectedShippingAddress(this.defaultConvenienceAddress.getKey());
                        selectBillingAddress(this.defaultConvenienceAddress);
                    }
                    if (!this.isShowDefaultBillingAddress() && !this.isSelectStoreVisible()) {
                        // console.log('useSameAsPurchaser', 'for new same as purchaser address - Home');
                        this.resetSameAsPurchaserAddressForm();
                    }
                    if (!this.isShowDefaultConvenienceAddress() && !this.isSelectStoreVisible()) {
                        // console.log('useSameAsPurchaser', 'for new same as purchaser address - 711');
                        this.resetSameAsPurchaserAddressForm711();
                    }
                },

                useDefaultShippingAddress: function () {
                    // const self = this;
                    // const selectedShippingMethod = quote?.shippingMethod()?.method_code;
                    // this.resetShippingAddress();
                    // this.resetBillingAddress();
                    // // let defaultAddresses = [];
                    // let defaultShippingAddress = null;

                    // if (selectedShippingMethod === 'hotai_711' && self.defaultConvenienceAddress) {
                    //     defaultShippingAddress = self.defaultConvenienceAddress;
                    //     // defaultAddresses = self.customerAddresses().filter(function (address) {
                    //     //     return self.getHotaiAddressType(address) === 'convenience_store' && address.customerAddressId === self.customerDefaultConvenienceStoreAddressId();
                    //     // })
                    // } else if(self.defaultBillingAddress){
                    //     // defaultAddresses = self.customerAddresses().filter(function (address) {
                    //     //     return self.getHotaiAddressType(address) === 'normal' && address.isDefaultShipping();
                    //     // })
                    //     defaultShippingAddress = self.defaultBillingAddress;
                    // }

                    // console.log('useDefaultShippingAddress', {selectedShippingMethod, defaultShippingAddress});

                    // if (defaultShippingAddress) {
                    //     // const defaultAddress = defaultAddresses[0];
                    //     shippingRegistry.isAddressChanged(true);
                    //     selectShippingAddress(null);
                    //     checkoutData.setSelectedShippingAddress(defaultShippingAddress.getKey());
                    //     selectBillingAddress(null);
                    // }
                },

                // resetShippingAddress: function () {
                //     quote.shippingAddress(null);
                //     shippingSaveProcessor.saveShippingInformation();
                //     console.log('Shipping address reset');
                // },

                // resetBillingAddress: function () {
                //     quote.billingAddress(null);
                //     // billingAddress.setBillingAddress(null);
                //     console.log('Billing address reset');
                // },

                isConvenienceStore: function (address) {
                    var result = false;
                    if (address) {
                        var hotaiAddressType = this.getHotaiAddressType(address);
                        if (hotaiAddressType == 'convenience_store') {
                            result = true;
                        }
                    }
                    return result;
                },

                getHotaiAddressType: function (address) {
                    var hotaiAddressType = '';
                    if (address.customAttributes && Array.isArray(address.customAttributes)) {
                        for (var i = 0; i < address.customAttributes.length; i++) {
                            var customAddressAttribute = address.customAttributes[i];
                            if (customAddressAttribute.attribute_code == 'hotai_address_type') {
                                hotaiAddressType = customAddressAttribute.value;
                            }
                        }
                    }

                    return hotaiAddressType;
                },

                showDefaultBillingAddress: function () {
                    var showAddress = false;
                    var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                    if (this.defaultBillingAddress && quote.shippingMethod()) {
                        var selectedShippingMethod = quote.shippingMethod().method_code;
                        var hotaiAddressType = this.getHotaiAddressType(this.defaultBillingAddress);
                        if (arrayHomeDeliveryMethods.includes(selectedShippingMethod) && hotaiAddressType != 'convenience_store') {
                            showAddress = true;
                        }
                    }
                    // console.log('showDefaultBillingAddress', showAddress, this.defaultBillingAddress, quote.shippingMethod(), hotaiAddressType, arrayHomeDeliveryMethods);
                    this.isShowDefaultBillingAddress(showAddress);

                    // console.log('showDefaultBillingAddress', showAddress, this.defaultBillingAddress, quote.shippingMethod(), hotaiAddressType, arrayHomeDeliveryMethods, this.isShowDefaultBillingAddress());
                },

                showConvenienceStoreAddress: function () {
                    var showAddress = false;
                    var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                    if (this.defaultConvenienceAddress && quote.shippingMethod()) {
                        var selectedShippingMethod = quote.shippingMethod().method_code;
                        var hotaiAddressType = this.getHotaiAddressType(this.defaultConvenienceAddress);
                        if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod) && hotaiAddressType == 'convenience_store') {
                            showAddress = true;
                        }
                    }
                    this.isShowDefaultConvenienceAddress(showAddress);
                    // console.log('showConvenienceStoreAddress', showAddress, this.defaultConvenienceAddress, quote.shippingMethod(), hotaiAddressType, arrayHomeDeliveryMethods, this.isShowDefaultConvenienceAddress());
                },

                /**
                 * @param {String} countryId
                 * @return {String}
                 */
                getCountryName: function (countryId) {
                    return countryData()[countryId] != undefined ? countryData()[countryId].name : '';
                },

                getRegionName: function (regionId) {
                    // console.log('getRegionName', regionId, regions[regionId], regions);
                    const region = regions.find(function (region) {
                        return region.value === regionId;
                    });
                    if (region != undefined) {
                        return region.title || region.label;
                    }
                    return '';
                },

                isShippingMethodSelected: function () {
                    if (quote.shippingMethod()) {
                        return true;
                    }
                    return false;
                },

                clearShippingAddressForm: function () {
                    var newShippingAddressData = registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset');
                    var allFields = newShippingAddressData.elems();
                    allFields.forEach(function (field, index) {
                        //ignore field country_id and lastname
                        if (typeof field.initialValue !== "undefined" && field.index != 'country_id' && field.index != 'lastname') {
                            field.initialValue = '';
                        }
                        if (typeof field.reset !== "undefined" && field.index != 'country_id' && field.index != 'lastname') {
                            field.reset();
                        } else if (field.index == 'street') {
                            var streetFields = field.elems();
                            streetFields.forEach(function (streetField, key) {
                                if (typeof streetField.reset !== "undefined") {
                                    streetField.reset();
                                }
                            });
                        }
                    });
                },

                refreshFormToAddNewAddress: function () {
                    // reset select shipping address
                    // quote.shippingAddress(null);
                    // checkoutData.setSelectedShippingAddress(null);
                    // quote.billingAddress(null);
                    quote.setSelectedShipping(null);
                    this.clickedAddNewAddress(true);
                    this.clearShippingAddressForm();
                    $('#co-shipping-form-inline input[name=firstname]').val('');
                    $('#co-shipping-form-inline input[name="street[0]"]').val('');
                    $('#co-shipping-form-inline input[name=region_id]').val('');
                    $('#co-shipping-form-inline input[name=region]').val('');
                    $('#co-shipping-form-inline input[name=city_id]').val('');
                    $('#co-shipping-form-inline input[name=city]').val('');
                    $('#co-shipping-form-inline input[name=postcode]').val('');
                    $('#co-shipping-form-inline input[name=telephone]').val('');
                    $('#co-shipping-form-inline input[name=address_id]').val('');
                    $('#co-shipping-form-inline input[name=index_id]').val('');

                    if (this.isSelectStoreVisible()) {
                        $('div[name="newShippingAddress.region_id"]').hide();
                        $('div[name="newShippingAddress.region"]').hide();
                        $('div[name="newShippingAddress.city_id"]').hide();
                        $('div[name="newShippingAddress.city"]').hide();
                        $('div[name="newShippingAddress.street.0"]').parent().parent().hide();
                        this.isStoreSelectedOnFrequentlyAddress(false);
                    } else {
                        // var regionOptions = $('div[name="newShippingAddress.region_id"] option').length;
                        // if(regionOptions > 1) {
                        //     $('div[name="newShippingAddress.region_id"]').show();
                        //     // $('div[name="newShippingAddress.city"]').show();
                        // } else {
                        //     // $('div[name="newShippingAddress.region"]').show();
                        //     // $('div[name="newShippingAddress.city"]').show();
                        // }
                        $('div[name="newShippingAddress.city_id"]').show();
                        $('div[name="newShippingAddress.region_id"]').show();
                        $('div[name="newShippingAddress.street.0"]').parent().parent().show();
                    }

                    $('#ajax-update-address').hide();
                    $('#ajax-add-address').show();
                    $('#add-new-address-label').hide();
                    $('#co-shipping-form-inline').show();

                    var self = this;
                    var checkoutAddressData = window.checkoutConfig.checkoutAddress;
                    var storeData = window.checkoutConfig.storeCheckoutData;
                    // var checkoutAddressData = JSON.parse(checkoutAddressData);
                    storeData = JSON.parse(storeData);
                    if (checkoutAddressData) {
                        // console.log({checkoutAddressData, storeData});
                        if (checkoutAddressData.type === 'new') {
                            // $('#add-new-address-label').trigger('click');
                            $('#co-shipping-form-inline input[name=firstname]').val(checkoutAddressData.firstname).trigger('change');
                            $('#co-shipping-form-inline input[name=lastname]').val(checkoutAddressData.lastname).trigger('change');
                            // $('#co-shipping-form-inline input[name="street[0]"]').val();
                            $('#co-shipping-form-inline select[name=country_id]').val(checkoutAddressData.country).trigger('change');
                            $('#co-shipping-form-inline select[name=region_id]').val(checkoutAddressData.region_id).trigger('change');
                            $('#co-shipping-form-inline input[name=region_code]').val(checkoutAddressData.region_code).trigger('change');
                            $('#co-shipping-form-inline input[name=region]').val(checkoutAddressData.region).trigger('change');
                            // $('#co-shipping-form-inline input[name=city]').val();
                            $('#co-shipping-form-inline select[name=city_id]').val(checkoutAddressData.city_id).trigger('change');
                            $('#co-shipping-form-inline input[name=postcode]').val(checkoutAddressData.postcode).trigger('change');
                            $('#co-shipping-form-inline input[name=telephone]').val(checkoutAddressData.telephone).trigger('change');

                            if (checkoutAddressData.type === 'edit') {
                                $('#co-shipping-form-inline input[name=address_id]').val(checkoutAddressData.addressId).trigger('change');
                                $('#co-shipping-form-inline input[name=index_id]').val(checkoutAddressData.indexId).trigger('change');
                                $('#co-shipping-form-inline').attr('data-address-cache-key', checkoutAddressData.addressCacheKey);
                            }

                            // as frequently used address
                            self.storeNameOnFrequentlyAddress('711' + storeData.storename);
                            self.storeAddressOnFrequentlyAddress(storeData.address);
                            self.isStoreSelectedOnFrequentlyAddress(true);

                            $('#co-shipping-form-inline input[name="city"]').val(storeData.storename).trigger('change');
                            $('#co-shipping-form-inline input[name="street[0]"]').val(storeData.address).trigger('change');

                            $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_code]"]').val(storeData.storeid).trigger('change');
                            $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_name]"]').val(storeData.storename).trigger('change');
                            $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_servicetype]"]').val(storeData.servicetype).trigger('change');
                            if (storeData.outside == '1') {
                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", true).trigger("change");
                            } else {
                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", false).trigger("change");
                            }

                            self.storeData(storeData);

                            // if(self.hasUnsupportedOutlyingIslands(storeData.address)) {
                            //     self.showMessage('暫不提供外島配送，請選擇台灣本島地區門市。', 'error');
                            //     console.log("Address contains 澎湖縣, 金門縣, or 馬祖");
                            // }
                        }

                        let isSameAsPurchaser = (checkoutAddressData.checkSameAsPurchaser == 'true' || checkoutAddressData.checkSameAsPurchaser == true) ? 1 : 0;
                        if (checkoutAddressData.type === 'edit' && !isSameAsPurchaser) {
                            if (self._renderAddressTimeout) {
                                clearTimeout(self._renderAddressTimeout);
                            }
                            self._renderAddressTimeout = setTimeout(function () {
                                $('#address_list_id_' + checkoutAddressData.addressId).find('.action.update-address-link').trigger('click');

                                // console.log(4444, {checkoutAddressData, storeData});
                                // $('#add-new-address-label').trigger('click');
                                $('#co-shipping-form-inline input[name=firstname]').val(checkoutAddressData.firstname).trigger('change');
                                $('#co-shipping-form-inline input[name=lastname]').val(checkoutAddressData.lastname).trigger('change');
                                // $('#co-shipping-form-inline input[name="street[0]"]').val();
                                $('#co-shipping-form-inline select[name=country_id]').val(checkoutAddressData.country).trigger('change');
                                $('#co-shipping-form-inline select[name=region_id]').val(checkoutAddressData.region_id).trigger('change');
                                $('#co-shipping-form-inline input[name=region_code]').val(checkoutAddressData.region_code).trigger('change');
                                $('#co-shipping-form-inline input[name=region]').val(checkoutAddressData.region).trigger('change');
                                // $('#co-shipping-form-inline input[name=city]').val();
                                $('#co-shipping-form-inline select[name=city_id]').val(checkoutAddressData.city_id).trigger('change');
                                $('#co-shipping-form-inline input[name=postcode]').val(checkoutAddressData.postcode).trigger('change');
                                $('#co-shipping-form-inline input[name=telephone]').val(checkoutAddressData.telephone).trigger('change');

                                if (checkoutAddressData.type === 'edit') {
                                    $('#co-shipping-form-inline input[name=address_id]').val(checkoutAddressData.addressId).trigger('change');
                                    $('#co-shipping-form-inline input[name=index_id]').val(checkoutAddressData.indexId).trigger('change');
                                    $('#co-shipping-form-inline').attr('data-address-cache-key', checkoutAddressData.addressCacheKey);
                                }

                                // as frequently used address
                                self.storeNameOnFrequentlyAddress('711' + storeData.storename);
                                self.storeAddressOnFrequentlyAddress(storeData.address);
                                self.isStoreSelectedOnFrequentlyAddress(true);

                                $('#co-shipping-form-inline input[name="city"]').val(storeData.storename).trigger('change');
                                $('#co-shipping-form-inline input[name="street[0]"]').val(storeData.address).trigger('change');

                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_code]"]').val(storeData.storeid).trigger('change');
                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_name]"]').val(storeData.storename).trigger('change');
                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_servicetype]"]').val(storeData.servicetype).trigger('change');
                                if (storeData.outside == '1') {
                                    $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", true).trigger("change");
                                } else {
                                    $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", false).trigger("change");
                                }

                                self.storeData(storeData);

                                // if(self.hasUnsupportedOutlyingIslands(storeData.address)) {
                                //     self.showMessage('暫不提供外島配送，請選擇台灣本島地區門市。', 'error');
                                //     console.log("Address contains 澎湖縣, 金門縣, or 馬祖");
                                // }
                            }, 500);

                        }
                    }
                },

                isHotai711: function () {
                    // var shippingMethod = quote.shippingMethod();
                    // console.log('isHotai711', shippingMethod);
                    // if(shippingMethod && (shippingMethod.method_code === 'hotai_711' || shippingMethod.carrier_code === 'hotai_711')) {
                    //     return true;
                    // }
                    // return false;
                    return true;
                },

                processConvenienceData: function () {
                    var checkoutAddressData = window.checkoutConfig.checkoutAddress;
                    // var checkoutAddressData = JSON.parse(checkoutAddressData);
                    var self = this;

                    // console.log('processConvenienceData', {checkoutAddressData})
                    // this.removeAddressData711();

                    var hasStoredAdd = window.localStorage.getItem('hasStoredAdd');
                    // const checkoutAddressData = window.checkoutConfig.checkoutAddress;
                    if (!hasStoredAdd && checkoutAddressData) {
                        window.checkoutConfig.checkoutAddress = null;
                        window.checkoutConfig.storeCheckoutData = null;
                        checkoutAddressData = null;
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

                    if (checkoutAddressData) {
                        let isSameAsPurchaser = (checkoutAddressData.checkSameAsPurchaser == 'true' || checkoutAddressData.checkSameAsPurchaser == true) ? 1 : 0;
                        this.sameAsPurchaser(isSameAsPurchaser);
                        this.isSelectStoreVisible(true);
                        if (!isSameAsPurchaser) {
                            const intervalId = setInterval(() => {
                                // Check the condition here
                                if ($('#co-shipping-form-inline input[name=firstname]').is(':visible')) {
                                    clearInterval(intervalId); // Stop the interval
                                    this.isSelectStoreVisible(true);
                                    $('#add-new-address-label').trigger('click');
                                } else {
                                    $('#add-new-address-label').trigger('click');
                                }
                            }, 500);
                        }
                    }
                },

                onAfterRender: function () {
                    // console.log('afterREndeer', this)
                    // var self = this;
                    // var checkoutAddressData = window.localStorage.getItem('checkoutAddress');
                    // var checkoutAddressData = JSON.parse(checkoutAddressData);
                    // if (checkoutAddressData) {
                    //     if(checkoutAddressData.type === 'new' && !checkoutAddressData.checkSameAsPurchaser) {
                    //         console.log(2222)
                    //         $('#add-new-address-label').trigger('click');

                    //         // if (self._renderTimeout) {
                    //         //     clearTimeout(self._renderTimeout);
                    //         // }
                    //         // self._renderTimeout = setTimeout(function() {
                    //         //     $('#add-new-address-label').trigger('click');
                    //         //     checkoutAddressData = null;
                    //         // }, 500);
                    //     }
                    // }
                },

                onAfterRenderAddressItem: function (self) {
                    // console.log('onAfterRenderAddressItem', this)
                    // var self = this;
                    var checkoutAddressData = window.checkoutConfig.checkoutAddress;
                    // var checkoutAddressData = JSON.parse(checkoutAddressData);
                    var storeData = window.checkoutConfig.storeCheckoutData;
                    storeData = JSON.parse(storeData);
                    if (checkoutAddressData && storeData) {
                        let isSameAsPurchaser = (checkoutAddressData.checkSameAsPurchaser == 'true' || checkoutAddressData.checkSameAsPurchaser == true);
                        if (checkoutAddressData.type === 'edit' && !isSameAsPurchaser) {
                            if (self._renderAddressTimeout) {
                                clearTimeout(self._renderAddressTimeout);
                            }
                            self._renderAddressTimeout = setTimeout(function () {
                                $('#address_list_id_' + checkoutAddressData.addressId).find('.action.update-address-link').trigger('click');

                                // console.log(4444, {checkoutAddressData, storeData});
                                // $('#add-new-address-label').trigger('click');
                                $('#co-shipping-form-inline input[name=firstname]').val(checkoutAddressData.firstname).trigger('change');
                                $('#co-shipping-form-inline input[name=lastname]').val(checkoutAddressData.lastname).trigger('change');
                                // $('#co-shipping-form-inline input[name="street[0]"]').val();
                                $('#co-shipping-form-inline select[name=country_id]').val(checkoutAddressData.country).trigger('change');
                                $('#co-shipping-form-inline select[name=region_id]').val(checkoutAddressData.region_id).trigger('change');
                                $('#co-shipping-form-inline input[name=region_code]').val(checkoutAddressData.region_code).trigger('change');
                                $('#co-shipping-form-inline input[name=region]').val(checkoutAddressData.region).trigger('change');
                                // $('#co-shipping-form-inline input[name=city]').val();
                                $('#co-shipping-form-inline select[name=city_id]').val(checkoutAddressData.city_id).trigger('change');
                                $('#co-shipping-form-inline input[name=postcode]').val(checkoutAddressData.postcode).trigger('change');
                                $('#co-shipping-form-inline input[name=telephone]').val(checkoutAddressData.telephone).trigger('change');

                                if (checkoutAddressData.type === 'edit') {
                                    $('#co-shipping-form-inline input[name=address_id]').val(checkoutAddressData.addressId).trigger('change');
                                    $('#co-shipping-form-inline input[name=index_id]').val(checkoutAddressData.indexId).trigger('change');
                                    $('#co-shipping-form-inline').attr('data-address-cache-key', checkoutAddressData.addressCacheKey);
                                }

                                // as frequently used address
                                self.storeNameOnFrequentlyAddress('711' + storeData.storename);
                                self.storeAddressOnFrequentlyAddress(storeData.address);
                                self.isStoreSelectedOnFrequentlyAddress(true);

                                $('#co-shipping-form-inline input[name="city"]').val(storeData.storename).trigger('change');
                                $('#co-shipping-form-inline input[name="street[0]"]').val(storeData.address).trigger('change');

                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_code]"]').val(storeData.storeid).trigger('change');
                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_name]"]').val(storeData.storename).trigger('change');
                                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_servicetype]"]').val(storeData.servicetype).trigger('change');
                                if (storeData.outside == '1') {
                                    $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", true).trigger("change");
                                } else {
                                    $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", false).trigger("change");
                                }

                                self.storeData(storeData);

                                // if(self.hasUnsupportedOutlyingIslands(storeData.address)) {
                                //     self.showMessage('暫不提供外島配送，請選擇台灣本島地區門市。', 'error');
                                //     console.log("Address contains 澎湖縣, 金門縣, or 馬祖");
                                // }
                            }, 500);

                        }
                    }
                },

                onAfterRenderSameAsPurchaser: function () {
                    // console.log('onAfterRenderSameAsPurchaser', this)
                    var self = this;
                    var checkoutAddressData = window.checkoutConfig.checkoutAddress;
                    // var checkoutAddressData = JSON.parse(checkoutAddressData);
                    var storeData = window.checkoutConfig.storeCheckoutData;
                    storeData = JSON.parse(storeData);
                    // console.log({checkoutAddressData, storeData})
                    if (checkoutAddressData && storeData) {
                        let isSameAsPurchaser = (checkoutAddressData.checkSameAsPurchaser == 'true' || checkoutAddressData.checkSameAsPurchaser == true);
                        if (checkoutAddressData.type === 'new' && isSameAsPurchaser) {
                            // as same as purchaser address
                            self.storeName('711' + storeData.storename);
                            self.storeAddress(storeData.address);
                            self.isStoreSelected(true);
                            self.setShippingAddressAsStorePickUpAddress(storeData);

                            $('#co-shipping-form-same-as-purchaser input[name=firstname]').val(checkoutAddressData.firstname).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name=lastname]').val(checkoutAddressData.lastname).trigger('change');
                            // $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val();
                            $('#co-shipping-form-same-as-purchaser select[name=country_id]').val(checkoutAddressData.country).trigger('change');
                            $('#co-shipping-form-same-as-purchaser select[name=region_id]').val(checkoutAddressData.region_id).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name=region_code]').val(checkoutAddressData.region_code).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name=region]').val(checkoutAddressData.region).trigger('change');
                            // $('#co-shipping-form-same-as-purchaser input[name=city]').val();
                            $('#co-shipping-form-same-as-purchaser select[name=city_id]').val(checkoutAddressData.city_id).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name=postcode]').val(checkoutAddressData.postcode).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name=telephone]').val(checkoutAddressData.telephone).trigger('change');

                            $('#co-shipping-form-same-as-purchaser input[name="city"]').val(storeData.storename).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val(storeData.address).trigger('change');

                            $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_code]"]').val(storeData.storeid).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_name]"]').val(storeData.storename).trigger('change');
                            $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_servicetype]"]').val(storeData.servicetype).trigger('change');
                            if (storeData.outside == '1') {
                                $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_outside]"]').prop("checked", true).trigger("change");
                            } else {
                                $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_outside]"]').prop("checked", false).trigger("change");
                            }
                            self.storeData(storeData);
                            // if(self.hasUnsupportedOutlyingIslands(storeData.address)) {
                            //     this.showMessage('暫不提供外島配送，請選擇台灣本島地區門市。', 'error');
                            //     console.log("Address contains 澎湖縣, 金門縣, or 馬祖");
                            // } else {
                            self.useSameAsPurchaserAddress();
                            // }
                        }
                    }
                },

                hasUnsupportedOutlyingIslands: function (text) {
                    const keywords = ["澎湖縣", "金門", "澎湖", "金門縣", "馬祖", "連江縣", "北竿鄉", "東引鄉", "南竿鄉", "莒光鄉", "台東縣蘭嶼鄉", "台東縣綠島鄉", "屏東縣琉球鄉"];
                    return keywords.some(keyword => text.includes(keyword));
                },

                showMessage: function (message, type = 'error') {
                    var jQ = $.noConflict();
                    var msgContainer = jQ('.page.messages');
                    var messageContent = jQ('<div class="message ' + (type === 'success' ? 'message-success success' : 'message-error error') + '"/>');
                    messageContent.text(DOMPurify.sanitize(message));

                    // Fix: Use secure DOM manipulation instead of string concatenation/parsing sink
                    var wrapper = jQ('<div class="messages custom-messages"></div>');
                    wrapper.append(messageContent);
                    msgContainer.append(wrapper);
                    msgContainer.addClass('__show');
                    var timeCheck;
                    clearTimeout(timeCheck);
                    timeCheck = setTimeout(function () {
                        msgContainer.removeClass('__show');
                        msgContainer.find('.custom-messages').remove();
                    }, 3000);
                },

                selectStore: function (isNew = false, action = 'add') {
                    event.preventDefault();
                    const url = window.location.href;
                    var ajaxUrl = urlBuilder.build('checkout/ajax/selectStores?type=checkout&redirect_url=' + encodeURIComponent(url));

                    // saving data before redirecting
                    var address = null;
                    var checkSameAsPurchaser = this.sameAsPurchaser();
                    var shippingAdressData = quote.shippingAddress();

                    var cardSelected = $('.card-list input[name="payment[creditcard_list]"]').val() || '';

                    var referrerCode = $('#referrer-code-form input[name=referrer_code]').val() || '';
                    var orderNote = $('#order-note-form textarea[name=order_note]').val() || '';

                    var epayInvoiceChoice = $('#ecpay-invoice-form input[name=ecpay_invoice_choice]').val() || '';
                    var ecpayInvoiceCarruerNumber = $('#ecpay-invoice-form input[name=ecpay_invoice_carruer_number]').val() || '';
                    var ecpayInvoiceCustomerIdentifier = $('#ecpay-invoice-form input[name=ecpay_invoice_customer_identifier]').val() || '';
                    var ecpayInvoiceCustomerCompany = $('#ecpay-invoice-form input[name=ecpay_invoice_customer_company]').val() || '';

                    var appliedPoint = $('#point_input').val() || 0;

                    // checkSameAsPurchaser = false
                    if (!checkSameAsPurchaser) {
                        const isEdit = $('#co-shipping-form-inline input[name=address_id]').val();
                        address = {
                            type: isEdit ? 'edit' : 'new',
                            checkSameAsPurchaser: false,
                            firstname: $('#co-shipping-form-inline input[name=firstname]').val(),
                            lastname: $('#co-shipping-form-inline input[name=lastname]').val(),
                            street: $('#co-shipping-form-inline input[name="street[0]"]').val(),
                            country: $('#co-shipping-form-inline select[name=country_id]').val(),
                            region_id: $('#co-shipping-form-inline select[name=region_id]').val(),
                            region_code: $('#co-shipping-form-inline input[name=region_code]').val(),
                            region: $('#co-shipping-form-inline input[name=region]').val(),
                            city: $('#co-shipping-form-inline input[name=city]').val(),
                            city_id: $('#co-shipping-form-inline select[name=city_id]').val(),
                            postcode: $('#co-shipping-form-inline input[name=postcode]').val(),
                            telephone: $('#co-shipping-form-inline input[name=telephone]').val(),
                            addressId: $('#co-shipping-form-inline input[name=address_id]').val(),
                            indexId: $('#co-shipping-form-inline input[name=index_id]').val(),
                            addressCacheKey: $('#co-shipping-form-inline').attr('data-address-cache-key'),
                            selectedShippingAddressId: shippingAdressData?.customerAddressId || '',
                            referrerCode: referrerCode,
                            orderNote: orderNote,
                            epayInvoiceChoice: epayInvoiceChoice,
                            ecpayInvoiceCarruerNumber: ecpayInvoiceCarruerNumber,
                            ecpayInvoiceCustomerIdentifier: ecpayInvoiceCustomerIdentifier,
                            ecpayInvoiceCustomerCompany: ecpayInvoiceCustomerCompany,
                            appliedPoint: appliedPoint,
                            cardSelected: cardSelected
                        }
                    } else {
                        // #co-shipping-form-same-as-purchaser
                        address = {
                            type: 'new',
                            checkSameAsPurchaser: true,
                            firstname: $('#co-shipping-form-same-as-purchaser input[name=firstname]').val(),
                            lastname: $('#co-shipping-form-same-as-purchaser input[name=lastname]').val(),
                            street: $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val(),
                            country: $('#co-shipping-form-same-as-purchaser select[name=country_id]').val(),
                            region_id: $('#co-shipping-form-same-as-purchaser select[name=region_id]').val(),
                            region_code: $('#co-shipping-form-same-as-purchaser input[name=region_code]').val(),
                            region: $('#co-shipping-form-same-as-purchaser input[name=region]').val(),
                            city: $('#co-shipping-form-same-as-purchaser input[name=city]').val(),
                            city_id: $('#co-shipping-form-same-as-purchaser select[name=city_id]').val(),
                            postcode: $('#co-shipping-form-same-as-purchaser input[name=postcode]').val(),
                            telephone: $('#co-shipping-form-same-as-purchaser input[name=telephone]').val(),
                            referrerCode: referrerCode,
                            orderNote: orderNote,
                            epayInvoiceChoice: epayInvoiceChoice,
                            ecpayInvoiceCarruerNumber: ecpayInvoiceCarruerNumber,
                            ecpayInvoiceCustomerIdentifier: ecpayInvoiceCustomerIdentifier,
                            ecpayInvoiceCustomerCompany: ecpayInvoiceCustomerCompany,
                            appliedPoint: appliedPoint,
                            cardSelected: cardSelected
                        }
                    }

                    window.checkoutConfig.checkoutAddress = address;
                    $.ajax({
                        url: urlBuilder.build('checkout/address/storeData'),
                        type: "POST",
                        data: { checkoutAddress: address },
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
                    // console.log('address', {checkSameAsPurchaser, isNew, address, addressData: JSON.stringify(address), ajaxUrl});

                    // window.location.href = ajaxUrl;

                    // var storeWindow, self = this;
                    // var form = document.createElement('form');
                    // form.method = 'POST';
                    // form.action = 'https://emap.presco.com.tw/emapmobileu.ashx';
                    // var redirectUrl = urlBuilder.build('checkout/ajax/callback');
                    // var inputFields = [
                    //     { name: 'eshopid', value: '234' },
                    //     { name: 'servicetype', value: 3 },
                    //     { name: 'url', value: redirectUrl },
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

                    //     window.sessionStorage.removeItem('stored');

                    //     // Remove the existing event listener before adding a new one
                    //     window.removeEventListener('message', self.handleStoreMessage);

                    //     // Define the handler function
                    //     self.handleStoreMessage = function (event) {
                    //         if (event.origin !== window.location.origin) {
                    //             // Ignore messages from unknown origins
                    //             return;
                    //         }

                    //         // The data returned from the popup window
                    //         // console.log(event.data);
                    //         if (typeof event.data.storename === "undefined") {
                    //             return;
                    //         }

                    //         const stored = window.sessionStorage.getItem('stored');

                    //         if (!stored) {
                    //             // The data returned from the popup window
                    //             var storeData = event.data;
                    //             // console.log({storeData, isNew, action});
                    //             if (storeData && storeData.storeid) {
                    //                 storePickupModel.selectedStore({
                    //                     name: storeData.storename,
                    //                     address: storeData.address
                    //                 });
                    //                 // console.log(storeData);
                    //                 if(!isNew) {
                    //                     // as same as purchaser address
                    //                     self.storeName('711'+storeData.storename);
                    //                     self.storeAddress(storeData.address);
                    //                     self.isStoreSelected(true);
                    //                     self.setShippingAddressAsStorePickUpAddress(storeData);
                    //                     $('#co-shipping-form-same-as-purchaser input[name="city"]').val(storeData.storename).trigger('change');
                    //                     $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val(storeData.address).trigger('change');

                    //                     $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_code]"]').val(storeData.storeid).trigger('change');
                    //                     $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_name]"]').val(storeData.storename).trigger('change');
                    //                     $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_servicetype]"]').val(storeData.servicetype).trigger('change');
                    //                     if(storeData.outside == '1') {
                    //                         $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_outside]"]').prop("checked", true).trigger("change");
                    //                     } else {
                    //                         $('#co-shipping-form-same-as-purchaser input[name="custom_attributes[cvs_store_outside]"]').prop("checked", false).trigger("change");
                    //                     }
                    //                     self.storeData(storeData);
                    //                     window.sessionStorage.setItem('stored', 1);
                    //                     if(action == 'add') {
                    //                         // console.log('add store address');
                    //                         self.useSameAsPurchaserAddress();
                    //                     } else {
                    //                         // Todo: update store address
                    //                         // console.log('update store address');
                    //                     }
                    //                 } else {
                    //                     // as frequently used address
                    //                     self.storeNameOnFrequentlyAddress('711'+storeData.storename);
                    //                     self.storeAddressOnFrequentlyAddress(storeData.address);
                    //                     self.isStoreSelectedOnFrequentlyAddress(true);

                    //                     $('#co-shipping-form-inline input[name="city"]').val(storeData.storename).trigger('change');
                    //                     $('#co-shipping-form-inline input[name="street[0]"]').val(storeData.address).trigger('change');

                    //                     $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_code]"]').val(storeData.storeid).trigger('change');
                    //                     $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_name]"]').val(storeData.storename).trigger('change');
                    //                     $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_servicetype]"]').val(storeData.servicetype).trigger('change');
                    //                     if(storeData.outside == '1') {
                    //                         $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", true).trigger("change");
                    //                     } else {
                    //                         $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", false).trigger("change");
                    //                     }

                    //                     self.storeData(storeData);
                    //                     window.sessionStorage.setItem('stored', 1);
                    //                 }
                    //             } else {
                    //                 // No data returned
                    //                 // $('#city').val('').trigger('change');
                    //                 // $('#street_1').val('').trigger('change');
                    //                 // $('#store-name').text('').addClass('hidden');
                    //                 // $('#store-address-text').text('')
                    //                 // $('.store-address').addClass('hidden');

                    //                 // $('#cvs_store_code').val('').trigger('change');
                    //                 // $('#cvs_store_name').val('').trigger('change');
                    //                 // $('#cvs_store_servicetype').val('').trigger('change');
                    //                 // $('#cvs_store_outside').val('').trigger('change');
                    //                 // console.log('no store data');
                    //                 // $('button[type="submit"]').attr('disabled', "true");
                    //             }
                    //         } else {
                    //             window.removeEventListener('message', self.handleStoreMessage);
                    //         }

                    //         var storeData = event.data;
                    //     };

                    //     // Remove the existing event listener before adding a new one
                    //     window.removeEventListener('message', self.handleStoreMessage);

                    //     // Add the new event listener
                    //     window.addEventListener('message', self.handleStoreMessage, false);
                    // }
                },

                setShippingAddressAsStorePickUpAddress: function (storeData) {
                    // console.log('setShippingAddressAsStorePickUpAddress', storeData);
                    // var addressData,
                    //     newShippingAddress;
                    // addressData = JSON.parse(JSON.stringify(this.source.get('shippingAddress')));
                    // addressData.city =  storeData.storename;
                    // addressData.street[0] = storeData.address;
                    // addressData.save_in_address_book = 0;
                    // if (addressData.customer_address_id) {
                    //     delete addressData.customer_address_id;
                    // }

                    // if (addressData.custom_attributes) {
                    //     addressData.custom_attributes.cvs_store_code = storeData.storeid;
                    //     addressData.custom_attributes.cvs_store_name = storeData.storename;
                    //     addressData.custom_attributes.cvs_store_servicetype = storeData.servicetype;
                    //     addressData.custom_attributes.cvs_store_outside = storeData.outside;
                    // }

                    // if (addressData.extension_attributes) {
                    //     addressData.extension_attributes.store_address_info = JSON.stringify(storeData);
                    // } else{
                    //     addressData.extension_attributes = {}
                    //     addressData.extension_attributes.store_address_info = JSON.stringify(storeData);
                    // }

                    // this.source.set('shippingAddress', addressData);
                    // newShippingAddress = addressConverter.formAddressDataToQuoteAddress(addressData);
                    // if (newShippingAddress.customerAddressId) {
                    //     delete newShippingAddress.customerAddressId;
                    // }
                    // this.selectedAddress(newShippingAddress.getKey());
                },

                cancelUpdateAddress: function () {
                    this.clickedAddNewAddress(false);
                    $('#co-shipping-form-inline').hide();
                    $('#add-new-address-label').show();

                    this.removeAddressData();
                    // window.localStorage.removeItem('checkoutAddress');
                    // window.localStorage.removeItem('storeCheckoutData');
                },

                cancelUpdateSameAsPurchaserAddress: function () {
                    //TODO: reset form if needing
                    this.resetSameAsPurchaserAddressForm();
                    $('#same-as-purchaser-address-form').hide();
                    $('#add-same-as-purchaser-address').show();
                },

                displaySameAsPurchaserFormAddress: function () {
                    this.resetSameAsPurchaserAddressForm();
                    $('#same-as-purchaser-address-form').show();
                    $('#add-same-as-purchaser-address').hide();
                },

                validateSameAsPurchaserAddressForm: function () {
                    this.source.set('params.invalid', false);
                    this.source.trigger('shippingAddress.data.validate');
                    if (this.source.get('params.invalid')) {
                        return false;
                    }
                    return true;
                },

                useSameAsPurchaserAddress: function () {
                    var self = this;
                    var selectedShippingMethod = quote.shippingMethod().method_code;
                    var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                    var hotaiAddressType = 'normal';
                    if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                        var hotaiAddressType = 'convenience_store';
                    }

                    // console.log('saving useSameAsPurchaserAddress');

                    var checkoutAddressData = window.checkoutConfig.checkoutAddress;
                    // var checkoutAddressData = JSON.parse(checkoutAddressData);
                    var storeData = window.checkoutConfig.storeCheckoutData;
                    storeData = JSON.parse(storeData);
                    if (hotaiAddressType === 'convenience_store') {
                        $('#co-shipping-form-same-as-purchaser input[name=firstname]').val(this.getCustomerName()).change();
                        $('#co-shipping-form-same-as-purchaser input[name=telephone]').val(this.getTelephone()).change();
                        if (checkoutAddressData && storeData) {
                            let isSameAsPurchaser = (checkoutAddressData.checkSameAsPurchaser == 'true' || checkoutAddressData.checkSameAsPurchaser == true);
                            if (checkoutAddressData.type === 'new' && isSameAsPurchaser) {
                                $('#co-shipping-form-same-as-purchaser input[name="city"]').val(storeData.storename).trigger('change');
                                $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val(storeData.address).trigger('change');
                            }
                            if (self.hasUnsupportedOutlyingIslands(storeData.address) || self.hasUnsupportedOutlyingIslands(storeData.storename)) {
                                messageList.addErrorMessage({ message: $.mage.__('暫不提供外島配送，請選擇台灣本島地區門市。') });
                                console.log("Address contains 澎湖縣, 金門縣, or 馬祖", storeData);
                                $("#select-store-button").show();
                                self.isUnsupportedStore(true);
                                return;
                            }
                        }
                    }

                    var formData = $("#co-shipping-form-same-as-purchaser").serializeArray();
                    if (hotaiAddressType === 'convenience_store') {
                        formData.push({ name: 'firstname', value: this.getCustomerName() });
                        formData.push({ name: 'telephone', value: this.getTelephone() });

                        if (checkoutAddressData && storeData) {
                            let isSameAsPurchaser = (checkoutAddressData.checkSameAsPurchaser == 'true' || checkoutAddressData.checkSameAsPurchaser == true);
                            if (checkoutAddressData.type === 'new' && isSameAsPurchaser) {
                                formData.push({ name: 'lastname', value: checkoutAddressData.lastname });
                                formData.push({ name: 'country_id', value: checkoutAddressData.country });
                                formData.push({ name: 'region_id', value: checkoutAddressData.region_id });
                                formData.push({ name: 'region_code', value: checkoutAddressData.region_code });
                                formData.push({ name: 'region', value: checkoutAddressData.region });
                                formData.push({ name: 'city_id', value: checkoutAddressData.city_id });
                                formData.push({ name: 'postcode', value: checkoutAddressData.postcode });
                                formData.push({ name: 'city', value: storeData.storename });
                                formData.push({ name: 'street[0]', value: storeData.address });
                                formData.push({ name: 'custom_attributes[cvs_store_code]', value: storeData.storeid });
                                formData.push({ name: 'custom_attributes[cvs_store_name]', value: storeData.storename });
                                formData.push({ name: 'custom_attributes[cvs_store_servicetype]', value: storeData.servicetype });

                                if (storeData.outside == '1') {
                                    formData.push({ name: 'custom_attributes[cvs_store_outside]', value: true });
                                } else {
                                    formData.push({ name: 'custom_attributes[cvs_store_outside]', value: false });
                                }
                            }
                        }
                    }
                    formData.push({ name: 'shipping_save_in_address_book', value: 'on' });
                    formData.push({ name: 'default_shipping', value: 1 });

                    // console.log('saving useSameAsPurchaserAddress', formData);

                    // validation form before save for new address of same as purchaser of home delivery
                    if (hotaiAddressType === 'normal') {
                        var validateResult = this.validateSameAsPurchaserAddressForm();
                        if (!validateResult) {
                            return;
                        }

                        var region = $('#co-shipping-form-same-as-purchaser input[name=region]').val();
                        var regionId = $('#co-shipping-form-same-as-purchaser select[name=region_id]').val();
                        var regionvalue = self.getRegionName(regionId);
                        var city = $('#co-shipping-form-same-as-purchaser input[name=city]').val();
                        var address = $('#co-shipping-form-same-as-purchaser input[name="street[0]"]').val();
                        this.sameAsPurchaserRegion(region || regionvalue);
                        this.sameAsPurchaserCity(city);
                        this.sameAsPurchaserAddress(address);
                        this.sameAsPurchaserAddressData(this.getSameAsPurchaserAddress());
                        // console.log({region, regionId, regionvalue, city, address});
                    }

                    // console.log({formData});

                    // save new address as default address using ajax
                    var ajaxUrl = urlBuilder.build('checkout/ajax/addressBookManagement?action=add&hotai_address_type=' + hotaiAddressType);
                    $.ajax({
                        url: ajaxUrl,
                        data: formData,
                        showLoader: true,
                        type: 'POST',
                        dataType: 'json',
                        success: function (response) {
                            if (response.success == false) {
                                console.log(response.message);
                                messageList.addErrorMessage({ message: response.message });
                                return false;
                            }

                            var data = response.data;
                            var defaultConvenienceStore = response?.default_convenience_store || false;
                            var storeData = ko.toJS(self.storeData) || {}
                            var newAddressData = {};
                            var dataId = DOMPurify.sanitize(data.id);

                            // console.log({data, storeData, addressId, newAddressData});

                            newAddressData.id = dataId;
                            newAddressData.email = data.email;
                            newAddressData['country_id'] = data.country_id;
                            newAddressData['customer_id'] = data.customer_id;
                            if (hotaiAddressType === 'normal') {
                                newAddressData.region = {};
                                newAddressData.region.region = data.region;
                                if (data.region_id) {
                                    newAddressData.regionId = data.region_id;
                                }
                                if (data.region_id && data.region == '') {
                                    newAddressData.region.region = self.getRegionName(data.region_id);
                                    newAddressData.region.region_id = data.region_id;
                                }
                            } else {
                                newAddressData.region = data.region;
                                newAddressData.region_id = data.region_id;
                            }
                            newAddressData.firstname = data.firstname;
                            newAddressData.lastname = data.lastname;
                            newAddressData.street = [];
                            newAddressData.street[0] = data.street[0];
                            newAddressData.city = data.city || data.city_id;
                            newAddressData.postcode = data.postcode || '000';
                            newAddressData.telephone = data.telephone;
                            newAddressData.default_shipping = data.default_shipping;

                            var hotaiAddressTypeObject = {};
                            hotaiAddressTypeObject.attribute_code = 'hotai_address_type';
                            hotaiAddressTypeObject.value = data.hotai_address_type;
                            if (hotaiAddressType === 'normal') {
                                newAddressData['custom_attributes'] = [];
                                newAddressData['custom_attributes'][0] = hotaiAddressTypeObject;
                            } else {
                                newAddressData['custom_attributes'] = [];
                                var customAttributes = [];
                                newAddressData.default_shipping = null;
                                newAddressData.default_billing = null;
                                if (data?.custom_attributes?.cvs_store_code) {
                                    customAttributes.push({ attribute_code: 'cvs_store_code', value: data.custom_attributes.cvs_store_code });
                                }
                                if (data?.custom_attributes?.cvs_store_outside) {
                                    customAttributes.push({ attribute_code: 'cvs_store_outside', value: data.custom_attributes.cvs_store_outside });
                                }
                                if (data?.custom_attributes?.cvs_store_servicetype) {
                                    customAttributes.push({ attribute_code: 'cvs_store_servicetype', value: data.custom_attributes.cvs_store_servicetype });
                                }
                                if (data?.custom_attributes?.cvs_store_name) {
                                    customAttributes.push({ attribute_code: 'cvs_store_name', value: data.custom_attributes.cvs_store_name });
                                }
                                if (data?.custom_attributes?.cvs_store_code) {
                                    customAttributes.push({ attribute_code: 'cvs_store_code', value: data.custom_attributes.cvs_store_code });
                                }
                                customAttributes.push(hotaiAddressTypeObject);
                                newAddressData['custom_attributes'] = customAttributes;
                            }

                            if (!newAddressData.extension_attributes) {
                                newAddressData.extension_attributes = {};
                            }
                            // console.log({storeData, data});
                            if (storeData) {
                                newAddressData.extension_attributes.store_address_info = JSON.stringify(storeData);
                            }

                            if (hotaiAddressType === 'normal') {
                                self.sameAsPurchaserRegion(newAddressData.region);
                                self.sameAsPurchaserCity(newAddressData.city || newAddressData.city_id);
                                self.sameAsPurchaserAddress(newAddressData.street[0]);
                                self.sameAsPurchaserAddressData(self.getSameAsPurchaserAddress());
                            }

                            if (dataId == 0) {
                                /* use custom_id because non saved address do not have ID */
                                dataId = 'A' + (new Date).getTime();
                                newAddressData.extension_attributes.custom_id = dataId;
                                var newAddress = new newCustomerAddress(newAddressData);/* non saved address */
                            } else {
                                var newAddress = new Address(newAddressData); /* save address */
                            }

                            if (hotaiAddressType === 'convenience_store') {
                                // console.log('set source shippingAddress', newAddressData);
                                // self.source.set('shippingAddress', newAddressData);
                                window.localStorage.setItem('default_convenience_store', dataId);
                            }

                            addressList.push(newAddress);

                            //set shipping address
                            selectShippingAddress(newAddress);
                            checkoutData.setSelectedShippingAddress(newAddress.getKey());
                            selectBillingAddress(newAddress);

                            // console.log('new confirm', { defaultConvenienceStore, newAddressData, a: quote.shippingAddress(), b: quote.billingAddress(), key: newAddress.getKey(), aa: checkoutData.getSelectedShippingAddress()});

                            //set new address as selected
                            if (defaultConvenienceStore) {
                                self.defaultConvenienceStoreId(dataId);
                            }
                            window.setNewAddressAsSelected = dataId; // should be removed, we don't use it anymore
                            quote.setSelectedShipping(newAddress);
                            // console.log('saving address',quote.shippingAddress(), quote.billingAddress());
                            shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());

                            /* hide form after add new address */
                            if (hotaiAddressType === 'normal') {
                                $('#co-shipping-form-inline').hide();
                                $('#add-new-address-label').show();
                            }

                            // remove store Data
                            // window.localStorage.removeItem('checkoutAddress');
                            // window.localStorage.removeItem('storeCheckoutData');
                            self.removeAddressData();
                            return true;
                        }
                    });

                    if (hotaiAddressType === 'normal') {
                        $('#same-as-purchaser-address-form').hide();
                        $('#same-as-purchaser-address-info').show();
                    }
                },

                getSameAsPurchaserAddress: function () {
                    var address = textHelper.formatStreet(this.sameAsPurchaserAddress());
                    var region = this.sameAsPurchaserRegion();
                    var city = this.sameAsPurchaserCity();
                    return `${city ?? ''}${region?.region ?? ''}${address ?? ''}`;
                },

                ajaxAddAddressInAddressBook: function () {
                    var self = this;
                    var selectedShippingMethod = quote.shippingMethod().method_code;
                    var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                    var hotaiAddressType = 'normal';
                    if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                        var hotaiAddressType = 'convenience_store';
                    }

                    var storeData = window.checkoutConfig.storeCheckoutData;
                    storeData = JSON.parse(storeData);

                    // if(hotaiAddressType === 'convenience_store' && storeData) {
                    //     if(self.hasUnsupportedOutlyingIslands(storeData.address)) {
                    //         self.showMessage('暫不提供外島配送，請選擇台灣本島地區門市。', 'error');
                    //         console.log("Address contains 澎湖縣, 金門縣, or 馬祖");
                    //         return;
                    //     }
                    // }

                    var name = $('#co-shipping-form-inline input[name=firstname]').val();
                    var telephone = $('#co-shipping-form-inline input[name=telephone]').val();
                    var city = $('#co-shipping-form-inline input[name=city]').val();
                    var cvsStoreCode = $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_code]"]').val();
                    var street = $('#co-shipping-form-inline input[name="street[0]"]').val();
                    // console.log({name, telephone, city, cvsStoreCode, hotaiAddressType});

                    if (hotaiAddressType === 'convenience_store' && storeData) {
                        if (self.hasUnsupportedOutlyingIslands(storeData.address) || self.hasUnsupportedOutlyingIslands(storeData.storename) || self.hasUnsupportedOutlyingIslands(street) || self.hasUnsupportedOutlyingIslands(city)) {
                            messageList.addErrorMessage({ message: $.mage.__('暫不提供外島配送，請選擇台灣本島地區門市。') });
                            console.log("Address contains 澎湖縣, 金門縣, or 馬祖", storeData);
                            return;
                        }
                    }

                    if (hotaiAddressType === 'convenience_store') {
                        if (name == '' || telephone == '') {
                            var validateResult = this.validateNewShippingAddressForm();
                            if (!validateResult) {
                                return;
                            }
                        }
                        if (cvsStoreCode == '' || city == '') {
                            this.selectStore(true, 'add');
                            return;
                        }
                    } else if (hotaiAddressType === 'normal') {
                        var validateResult = this.validateNewShippingAddressForm();
                        if (!validateResult) {
                            return;
                        }
                    }

                    var ajaxUrl = urlBuilder.build('checkout/ajax/addressBookManagement?action=add&hotai_address_type=' + hotaiAddressType);
                    var formData = $("#co-shipping-form-inline").serializeArray();

                    // console.log({formData, hotaiAddressType, selectedShippingMethod});

                    $.ajax({
                        url: ajaxUrl,
                        data: formData,
                        showLoader: true,
                        type: 'POST',
                        dataType: 'json',
                        success: function (response) {
                            if (response.success == false) {
                                console.log(response.message);
                                return false;
                            }
                            var data = response.data;
                            var storeData = ko.toJS(self.storeData) || {}
                            var newAddressData = {};
                            var addressId = data.id;
                            // console.log({data, storeData, addressId, newAddressData});
                            newAddressData.id = addressId;
                            newAddressData.email = data.email;
                            newAddressData['country_id'] = data.country_id;
                            newAddressData['customer_id'] = data.customer_id;
                            newAddressData.region = {};
                            newAddressData.region.region = data.region;
                            if (data.region_id) {
                                newAddressData.regionId = data.region_id;
                            }
                            if (data.region_id && data.region == '') {
                                newAddressData.region.region = self.getRegionName(data.region_id);
                                newAddressData.region.region_id = data.region_id;
                            }
                            newAddressData.firstname = data.firstname;
                            newAddressData.lastname = data.lastname;
                            newAddressData.street = [];
                            newAddressData.street[0] = data.street[0];
                            newAddressData.city = data.city;
                            newAddressData.postcode = data.postcode || '000';
                            newAddressData.telephone = data.telephone;
                            newAddressData.default_shipping = data.default_shipping;

                            var hotaiAddressTypeObject = {};
                            hotaiAddressTypeObject.attribute_code = 'hotai_address_type';
                            hotaiAddressTypeObject.value = data.hotai_address_type;
                            newAddressData['custom_attributes'] = [];
                            newAddressData['custom_attributes'][0] = hotaiAddressTypeObject;

                            if (!newAddressData.extension_attributes) {
                                newAddressData.extension_attributes = {};
                            }
                            if (storeData) {
                                newAddressData.extension_attributes.store_address_info = JSON.stringify(storeData);
                            }

                            if (hotaiAddressType === 'normal') {
                                newAddressData['custom_attributes'] = [];
                                newAddressData['custom_attributes'][0] = hotaiAddressTypeObject;
                            } else {
                                newAddressData['custom_attributes'] = [];
                                var customAttributes = [];
                                newAddressData.default_shipping = null;
                                newAddressData.default_billing = null;
                                if (data?.custom_attributes?.cvs_store_code) {
                                    customAttributes.push({ attribute_code: 'cvs_store_code', value: data.custom_attributes.cvs_store_code });
                                }
                                if (data?.custom_attributes?.cvs_store_outside) {
                                    customAttributes.push({ attribute_code: 'cvs_store_outside', value: data.custom_attributes.cvs_store_outside });
                                }
                                if (data?.custom_attributes?.cvs_store_servicetype) {
                                    customAttributes.push({ attribute_code: 'cvs_store_servicetype', value: data.custom_attributes.cvs_store_servicetype });
                                }
                                if (data?.custom_attributes?.cvs_store_name) {
                                    customAttributes.push({ attribute_code: 'cvs_store_name', value: data.custom_attributes.cvs_store_name });
                                }
                                if (data?.custom_attributes?.cvs_store_code) {
                                    customAttributes.push({ attribute_code: 'cvs_store_code', value: data.custom_attributes.cvs_store_code });
                                }
                                customAttributes.push(hotaiAddressTypeObject);
                                newAddressData['custom_attributes'] = customAttributes;
                            }

                            // console.log('new confirm', { newAddressData});

                            if (addressId == 0) {
                                /* use custom_id because non saved address do not have ID */
                                addressId = 'A' + (new Date).getTime();
                                newAddressData.extension_attributes.custom_id = addressId;
                                var newAddress = new newCustomerAddress(newAddressData);/* non saved address */
                            } else {
                                var newAddress = new Address(newAddressData);/* save address */
                            }

                            addressList.push(newAddress);

                            //set shipping address
                            selectShippingAddress(newAddress);
                            checkoutData.setSelectedShippingAddress(newAddress.getKey());
                            selectBillingAddress(newAddress);

                            //set new address as selected
                            window.setNewAddressAsSelected = addressId; // should be removed, we don't use it anymore
                            quote.setSelectedShipping(newAddress);

                            /* hide form after add new address */
                            $('#co-shipping-form-inline').hide();
                            $('#add-new-address-label').show();

                            // remove store Data
                            // window.localStorage.removeItem('checkoutAddress');
                            // window.localStorage.removeItem('storeCheckoutData');
                            self.removeAddressData();
                            return true;
                        }
                    });
                },

                ajaxUpdateAddressInAddressBook: function () {

                    var self = this;
                    var selectedShippingMethod = quote.shippingMethod().method_code;
                    var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
                    var selectedShipping = quote.selectedShipping();
                    var hotaiAddressType = 'normal';
                    if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                        var hotaiAddressType = 'convenience_store';
                    }

                    var storeData = window.checkoutConfig.storeCheckoutData;
                    storeData = JSON.parse(storeData);

                    var name = $('#co-shipping-form-inline input[name=firstname]').val();
                    var telephone = $('#co-shipping-form-inline input[name=telephone]').val();
                    var city = $('#co-shipping-form-inline input[name=city]').val();
                    var street = $('#co-shipping-form-inline input[name="street[0]"]').val();

                    // if(self.hasUnsupportedOutlyingIslands(street) || self.hasUnsupportedOutlyingIslands(city)) {
                    //     messageList.addErrorMessage({message: $.mage.__('暫不提供外島配送，請選擇台灣本島地區門市。')});
                    //     console.log("Address contains 澎湖縣, 金門縣, or 馬祖", storeData, city, street);
                    //     return;
                    // }

                    if (hotaiAddressType === 'convenience_store' && storeData) {
                        if (self.hasUnsupportedOutlyingIslands(storeData.address) || self.hasUnsupportedOutlyingIslands(storeData.storename) || self.hasUnsupportedOutlyingIslands(street) || self.hasUnsupportedOutlyingIslands(city)) {
                            messageList.addErrorMessage({ message: $.mage.__('暫不提供外島配送，請選擇台灣本島地區門市。') });
                            console.log("Address contains 澎湖縣, 金門縣, or 馬祖", storeData, city, street);
                            return;
                        }
                    }

                    if (hotaiAddressType === 'convenience_store') {
                        if (name == '' || telephone == '') {
                            var validateResult = this.validateNewShippingAddressForm();
                            if (!validateResult) {
                                return;
                            }
                        }
                        if (name != '' && $('#co-shipping-form-inline input[name=firstname]').hasClass('mage-error')) {
                            return;
                        }
                        if (telephone != '' && $('#co-shipping-form-inline input[name=telephone]').hasClass('mage-error')) {
                            return;
                        }
                        if (street == '' || city == '') {
                            this.selectStore(true, 'add');
                            return;
                        }
                    } else if (hotaiAddressType === 'normal') {
                        var validateResult = this.validateNewShippingAddressForm();
                        if (!validateResult) {
                            return;
                        }
                    }

                    // var validateResult = this.validateNewShippingAddressForm(), self = this;
                    // if (!validateResult) {
                    //     return;
                    // }

                    var ajaxUrl = urlBuilder.build('checkout/ajax/addressBookManagement?action=edit');
                    var formData = $("#co-shipping-form-inline").serializeArray();
                    const addressCacheKey = $("#co-shipping-form-inline").data('address-cache-key');

                    // var selectedShippingMethod = quote.shippingMethod().method_code;
                    // var hotaiAddressType = 'normal';
                    // var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;

                    // if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                    //     hotaiAddressType = 'convenience_store';
                    // }

                    formData.push({
                        name: 'hotai_address_type',
                        value: hotaiAddressType
                    });

                    // console.log({formData, hotaiAddressType, selectedShippingMethod, selectedShipping});

                    $.ajax({
                        url: ajaxUrl,
                        data: formData,
                        showLoader: true,
                        type: 'POST',
                        dataType: 'json',
                        success: function (response) {
                            if (response.success == false) {
                                // console.log(response.message);
                                return false;
                            }

                            var storeData = self.storeData;
                            var data = response.data;
                            var indexId = data.index_id;
                            var editedAddressComponent = registry.get('checkout.steps.shipping-step.shippingAddress.address-list.' + indexId);
                            var addressData = editedAddressComponent.address();
                            addressData.firstname = data.firstname;
                            addressData.lastname = data.lastname;
                            addressData.street[0] = data.street[0];
                            addressData.countryId = data.country_id;
                            if (data.region_id) {
                                addressData.regionId = data.region_id;
                            }
                            if (data.region_code) {
                                addressData.regionCode = data.region_code;
                            }
                            if (storeData) {
                                addressData.extension_attributes = {
                                    store_address_info: storeData ? JSON.stringify(storeData) : ''
                                };
                            }

                            // addressData.region = data.region;
                            addressData.region = self.getRegionName(data.region_id) || data.region || '';
                            addressData.city = data.city;
                            addressData.postcode = data.postcode || '000';
                            addressData.telephone = data.telephone;

                            var hotaiAddressTypeObject = {};
                            hotaiAddressTypeObject.attribute_code = 'hotai_address_type';
                            hotaiAddressTypeObject.value = data.hotai_address_type;
                            var customAttributes = [];
                            // addressData['custom_attributes'][0] = hotaiAddressTypeObject;
                            customAttributes.push({ attribute_code: 'hotai_address_type', value: data.hotai_address_type });
                            if (data?.custom_attributes?.cvs_store_code) {
                                customAttributes.push({ attribute_code: 'cvs_store_code', value: data.custom_attributes.cvs_store_code });
                            }
                            if (data?.custom_attributes?.cvs_store_outside) {
                                customAttributes.push({ attribute_code: 'cvs_store_outside', value: data.custom_attributes.cvs_store_outside });
                            }
                            if (data?.custom_attributes?.cvs_store_servicetype) {
                                customAttributes.push({ attribute_code: 'cvs_store_servicetype', value: data.custom_attributes.cvs_store_servicetype });
                            }
                            if (data?.custom_attributes?.cvs_store_name) {
                                customAttributes.push({ attribute_code: 'cvs_store_name', value: data.custom_attributes.cvs_store_name });
                            }
                            if (data?.custom_attributes?.cvs_store_code) {
                                customAttributes.push({ attribute_code: 'cvs_store_code', value: data.custom_attributes.cvs_store_code });
                            }
                            // console.log('customAttributes', { customAttributes });
                            addressData.customAttributes = customAttributes;

                            // if(hotaiAddressType === 'normal') {
                            //     addressData.default_shipping = data.default_shipping;
                            // } else {
                            //     // addressData.default_shipping = null;
                            //     // addressData.default_billing = null;
                            // }

                            if (data?.default_shipping) {
                                addressData.default_shipping = data.default_shipping;
                            }

                            addressData.customerAddressId = data.address_id;
                            // addressData.id = data.address_id;
                            addressData.customerId = data.customer_id;
                            // addressData.customer_address_id = data.customer_address_id;
                            // addressData.id = data.customer_address_id;
                            // addressData.customerId = data.customer_id;

                            // console.log('update confirm', { data, selectedShipping, addressData, addressCacheKey, indexId});
                            self.updateAddressInAddressList(addressData, addressCacheKey);
                            editedAddressComponent.address(addressData);
                            // console.log('update confirm', {  editedAddressComponent});
                            // only update shipping address if it is selected
                            // if(selectedShipping && selectedShipping.id === addressData.id) {

                            // }
                            selectShippingAddress(addressData);
                            checkoutData.setSelectedShippingAddress(addressData.getKey());
                            selectBillingAddress(addressData);
                            quote.setSelectedShipping(addressData);

                            $('#co-shipping-form-inline').hide();
                            $('#add-new-address-label').show();


                            // remove store Data
                            // window.localStorage.removeItem('checkoutAddress');
                            // window.localStorage.removeItem('storeCheckoutData');
                            self.removeAddressData();

                            return true;
                        }
                    });
                },

                updateAddressInAddressList: function (updatedAddress, cacheKey) {
                    var updatedAddressIndex = addressList().findIndex(function (address) {
                        return address.getCacheKey() === cacheKey;
                    });

                    // console.log('updateAddressInAddressList', {updatedAddress, cacheKey, updatedAddressIndex});

                    if (updatedAddressIndex !== -1) {
                        if (!updatedAddress?.customerAddressId) {
                            // console.log('updateAddressInAddressList 11');
                            var newAddress = new newCustomerAddress(updatedAddress);//non save address
                            addressList()[updatedAddressIndex] = newAddress;
                        } else {
                            // console.log('updateAddressInAddressList 22');
                            // var newAddress = new Address(updatedAddress);//save address
                        }
                        // console.log('updateAddressInAddressList', {newAddress});
                    }
                },

                validateNewShippingAddressForm: function () {
                    this.source.set('params.invalid', false);
                    this.source.trigger('newShippingAddress.data.validate');
                    if (this.source.get('params.invalid')) {
                        return false;
                    }
                    return true;
                },

                /**
                 * Check on visible billing address form
                 * @returns {Boolean} Billing address form visibility state
                 */
                isBillingAddressFormVisible: function () {
                    this.isUpdateCancelledByBilling = !addressFormState.isBillingSameAsShipping()
                        && addressFormState.isBillingFormVisible();

                    return this.isUpdateCancelledByBilling;
                },

                customerFirstName: function () {
                    return this.customer.firstname || '';
                    // return this.customer?.custom_attributes?.nickname?.value || this.customer.firstname || '';
                },

                customerLastName: function () {
                    return this.customer.lastname || '';
                },

                getCustomerName: function () {
                    return this.customer.firstname || '';
                    // return this.customer?.custom_attributes?.nickname?.value || this.customer.firstname || '';
                },

                getTelephone: function () {
                    return this.customer?.bob || this.customer?.custom_attributes?.phone_number?.value || '';
                },

                customerName: function () {
                    // return this.customer?.custom_attributes?.nickname?.value || textHelper.formatName(this.customer.firstname) || '';
                    return textHelper.formatName(this.customer.firstname) || '';
                },

                customerEmail: function () {
                    var email = this.customer?.email || '';
                    return textHelper.formatEmail(email);
                },

                customerDefaultConvenienceStoreAddressId: function () {
                    return window.customerData?.custom_attributes?.default_convenience_store?.value;
                },

                customerPhoneNumber: function () {
                    var phoneNumber = this.customer?.bob || this.customer?.custom_attributes?.phone_number?.value || '';
                    return textHelper.formatPhone(phoneNumber);
                },

                formatAddressName: function (name) {
                    // console.log('formatAddressPhone', name);
                    return textHelper.formatName(name);
                },

                formatAddressPhone: function (phone) {
                    // console.log('formatAddressPhone', phone);
                    return textHelper.formatPhone(phone);
                },

                formatAddressStreet: function (address) {
                    var street = textHelper.formatStreet(address.street[0]);
                    var region = address?.region?.region || address?.region;
                    var city = address.city;

                    // console.log('formatAddressStreet', {address, region, city, street});
                    return `${region ?? ''}${city ?? ''}${street ?? ''}`;
                },

                formatConvenienceAddress: function (address) {
                    var street = textHelper.formatStreet(address.street[0]);
                    var city = address.city;

                    // console.log('formatConvenienceAddress', {address, city, street});
                    return `711${city ?? ''} ${street ?? ''}`;
                },

                isVirtualCheckout: function () {
                    return quote.isVirtual();
                },

                isVisibleShipping: function () {
                    return this.isVirtualCheckout() && !this.allowGiftToFriend() ? false : true;
                },

                // Gift Steps Popup
                showGiftRecipientFillNote: function () {
                    this.showOptionAutoOpenGiftNotePopup(false);
                    this.openGiftStepsPopup();
                },

                openGiftStepsPopup: function () {
                    this.isStep1(true);
                    this.isStep2(false);
                    this.isStep3(false);

                    const self = this;

                    const $modal = $('#gift-recipient-fill-note-modal');

                    // If modal is already initialized → just open it
                    if ($modal.data('mageModal')) {
                        $modal.modal('openModal');
                        return;
                    }

                    const options = {
                        type: 'popup',
                        responsive: false,
                        title: $.mage.__('送禮三步驟'),
                        modalClass: 'modal-custom gift-recipient-fill-note-popup',
                        buttons: [],
                        closed: function () {
                            console.log('Modal is closed!', self.isAutoOpenGiftNoteChecked());
                            const isChecked = self.isAutoOpenGiftNoteChecked();
                            const isStored = window.localStorage.getItem('giftNotePopup');
                            if (!isStored && self.showOptionAutoOpenGiftNotePopup()) {
                                window.localStorage.setItem('giftNotePopup', JSON.stringify({ id: self.customer.id, auto: isChecked ? 'false' : 'true' }));
                            }
                        }
                    };

                    modal(options, $modal);
                    $modal.modal('openModal');
                },

                goToStep2: function () {
                    this.isStep1(false);
                    this.isStep2(true);
                    this.isStep3(false);
                },

                goToStep3: function () {
                    this.isStep1(false);
                    this.isStep2(false);
                    this.isStep3(true);
                },

                closeGiftRecipientFillNote: function () {
                    $('#gift-recipient-fill-note-modal').modal('closeModal');
                },

                checkToShowModal: function () {
                    console.log('checkToShowModal called');
                    // $('object.svg-replace').on('load', function () {
                    //     let objectEl = $(this);
                    //     let svgUrl = objectEl.attr('data'); // get svg file url
                    //     if(svgUrl == undefined){
                    //         return;
                    //     }
                    //     $.get(svgUrl, function (data) {
                    //         let svg = $(data).find('svg');

                    //         if (!svg.length) {
                    //             console.error('SVG not found in:', svgUrl);
                    //             return;
                    //         }

                    //         // copy classes from <object> to <svg>
                    //         let classList = objectEl.attr('class');
                    //         if (classList) {
                    //             svg.attr('class', classList);
                    //         }

                    //         // replace <object> with inline <svg>
                    //         objectEl.replaceWith(svg);
                    //     }, 'xml');
                    // });

                    if (this.checkToAutoShow()) {
                        this.openGiftStepsPopup();
                    }
                },

                checkToAutoShow: function () {
                    var customerData = window.customerData;
                    var giftNotePopup = window.localStorage.getItem('giftNotePopup') || '{}';
                    // console.log('checkToAutoShow', {customerData, giftNotePopup , data:JSON.parse(giftNotePopup)});
                    giftNotePopup = JSON.parse(giftNotePopup);
                    if (giftNotePopup?.id && customerData?.id && giftNotePopup.id != customerData.id) {
                        window.localStorage.removeItem('giftNotePopup');
                        return true;
                    }

                    if (giftNotePopup?.auto) {
                        return giftNotePopup.auto === 'true';
                    }
                    return true;
                },
                onAfterRenderGiftStep: function (elements) {
                    console.log('onAfterRenderGiftStep', elements);
                }
            });
        };
    }
);

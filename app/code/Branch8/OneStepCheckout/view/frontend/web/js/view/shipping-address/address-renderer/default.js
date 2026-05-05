/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'mage/url',
    'Magento_Customer/js/model/address-list',
    'underscore',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/shipping-save-processor',
    'Amasty_CheckoutCore/js/model/shipping-rate-service-override',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data',
    'Branch8_OneStepCheckout/js/model/text-helper',
    'Branch8_OneStepCheckout/js/model/store-pickup',
    'uiRegistry',
], function (
    $,
    ko,
    Component,
    urlBuilder,
    addressList,
    _,
    selectShippingAddress,
    selectBillingAddress,
    shippingSaveProcessor,
    rateService,
    quote,
    formPopUpState,
    checkoutData,
    customerData,
    textHelper,
    storePickupModel,
    registry
) {
    'use strict';

    var countryData = customerData.get('directory-data');
    var regions = registry.get('checkoutProvider').get('dictionaries.region_id');   

    return Component.extend({
        defaults: {
            template: 'Branch8_OneStepCheckout/shipping-address/address-renderer/default'
        },
        defaultBillingAddress: '',
        isUseForSelectedShippingMethod: ko.observable(false),

        /** @inheritdoc */
        initObservable: function () {
            this._super();
            var self = this;
            addressList.some(function (addrs) {
                // console.log('isShowAddress', self.address().isDefaultBilling(), self.address().isDefaultShipping(), self.address(), self.isDefaultShippingAddress(), self.addressIsDefaultShippingAddress(addrs));
                if (addrs.isDefaultBilling() || addrs.isDefaultShipping() || self.addressIsDefaultShippingAddress(addrs)) {
                    self.defaultBillingAddress = addrs;
                }
                // console.log('addressList', addrs, addrs.isDefaultBilling(), addrs.isDefaultShipping());
            });

            quote.shippingMethod.subscribe(function (value) {
                const shippingMethodAddressTypeMap = {
                    'hotai_711': 'convenience_store',
                    'hotai_delivery': 'normal'
                }
                const shippingMethod = value?.method_code;
                const addressType = self.getHotaiAddressType();
                self.isUseForSelectedShippingMethod(addressType === shippingMethodAddressTypeMap[shippingMethod]);
            });

            this.isShowAddress = ko.computed(function () {
                // console.log('isShowAddress', this.address().isDefaultBilling(), this.address().isDefaultShipping(), this.address(), this.isDefaultShippingAddress());
               if(this.address().isDefaultBilling() || this.address().isDefaultShipping() || this.isDefaultShippingAddress()) {
                // console.log('isShowAddress', 'return false');
                   return false;
               }
                return true;
            }, this);

            this.isSelected = ko.computed(function () {
                var isSelected = false,
                    shippingAddress = quote.selectedShipping();
                if (shippingAddress) {
                    isSelected = shippingAddress.getKey() == this.address().getKey(); //eslint-disable-line eqeqeq
                }

                console.log('isSelected', isSelected, shippingAddress, this.address());

                return isSelected;
            }, this);

            return this;
        },

        /**
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] != undefined ? countryData()[countryId].name : ''; //eslint-disable-line
        },

        /**
         * Get customer attribute label
         *
         * @param {*} attribute
         * @returns {*}
         */
        getCustomAttributeLabel: function (attribute) {
            var label;

            if (typeof attribute === 'string') {
                return attribute;
            }

            if (attribute.label) {
                return attribute.label;
            }

            if (_.isArray(attribute.value)) {
                label = _.map(attribute.value, function (value) {
                    return this.getCustomAttributeOptionLabel(attribute['attribute_code'], value) || value;
                }, this).join(', ');
            } else if (typeof attribute.value === 'object') {
                label = _.map(Object.values(attribute.value)).join(', ');
            } else {
                label = this.getCustomAttributeOptionLabel(attribute['attribute_code'], attribute.value);
            }

            return label || attribute.value;
        },

        /**
         * Get option label for given attribute code and option ID
         *
         * @param {String} attributeCode
         * @param {String} value
         * @returns {String|null}
         */
        getCustomAttributeOptionLabel: function (attributeCode, value) {
            var option,
                label,
                options = this.source.get('customAttributes') || {};

            if (options[attributeCode]) {
                option = _.findWhere(options[attributeCode], {
                    value: value
                });

                if (option) {
                    label = option.label;
                }
            } else if (value.file !== null) {
                label = value.file;
            }

            return label;
        },

        /** Set selected customer shipping address  */
        selectAddress: function () {
            // console.log('selectAddress', this.address());
            window.setNewAddressAsSelected = '';
            selectShippingAddress(this.address());
            checkoutData.setSelectedShippingAddress(this.address().getKey());
            selectBillingAddress(this.address());
            quote.setSelectedShipping(this.address());
            // if (this.address().isDefaultShipping() || this.address() == quote.shippingAddress()) {
            //     /* fix issue sometime select address but nothing update */
            //     rateService.updateRates(quote.shippingAddress(), true);
            //     shippingSaveProcessor.saveShippingInformation(quote.shippingAddress().getType());
            // } else {
            //     selectShippingAddressAction(this.address());
            // }
            // checkoutData.setSelectedShippingAddress(this.address().getKey());

            // if (this.defaultBillingAddress) {
            //     selectBillingAddress(this.defaultBillingAddress);
            // }
            return true;
        },

        /**
         * Edit address.
         */
        editAddress: function () {
            formPopUpState.isVisible(true);
            this.showPopup();
        },

        /**
         * Show popup.
         */
        showPopup: function () {
            $('[data-open-modal="opc-new-shipping-address"]').trigger('click');
        },

        getHotaiAddressType: function () {
            var hotaiAddressType = '';
            if (this.address().customAttributes && Array.isArray(this.address().customAttributes)) {
                for (var i = 0; i < this.address().customAttributes.length; i++) {
                    var customAddressAttribute = this.address().customAttributes[i];
                    if (customAddressAttribute.attribute_code == 'hotai_address_type') {
                        hotaiAddressType = customAddressAttribute.value;
                    }
                }
            }
            return hotaiAddressType;
        },

        showAddress: function () {
            var showAddress = false;
            /* check shipping method and show address base on shipping method */
            var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
            if (quote.shippingMethod()) {
                var selectedShippingMethod = quote.shippingMethod().method_code;
                var hotaiAddressType = this.getHotaiAddressType();
                if (arrayHomeDeliveryMethods.includes(selectedShippingMethod) && hotaiAddressType != 'convenience_store') {
                    showAddress = true;
                }
                if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod) && hotaiAddressType == 'convenience_store') {
                    showAddress = true;
                }
            }
            /* make sure do not render deleted address */
            if(showAddress) {
                var stillInAddressList = false;
                for (var i = 0; i < addressList().length; i++) {
                    var addressId = addressList()[i].customerAddressId;
                    if (addressList()[i].extensionAttributes && addressList()[i].extensionAttributes.custom_id) {
                        addressId = addressList()[i].extensionAttributes.custom_id
                    }
                    if (addressId == this.index) {
                        stillInAddressList = true;
                        break;
                    }
                }
                if (!stillInAddressList) {
                    showAddress = false;
                }
            }

            // console.log('showAddress', showAddress, this.address(), quote.shippingMethod(), addressList(), this.index);

            return showAddress;
        },

        getAddressId: function () {
            var addressId = this.address().customerAddressId;
            if (this.address().extensionAttributes && this.address().extensionAttributes.custom_id) {
                addressId = this.address().extensionAttributes.custom_id; /* non saved address use custom_id */
            }
            return addressId;
        },

        updateDataToEdit: function (address) {
            var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
            var selectedShippingMethod = 'normal';
            var hotaiAddressDefaultType = this.getHotaiAddressType();
            var hotaiAddressType = this.getHotaiAddressType();

            if(!arrayHomeDeliveryMethods.includes(selectedShippingMethod)) {
                hotaiAddressDefaultType = 'convenience_store';
            }

            if (hotaiAddressDefaultType == 'convenience_store' && hotaiAddressType == 'convenience_store') {
                $('div[name="newShippingAddress.region_id"]').addClass('hidden-field');
                $('div[name="newShippingAddress.city_id"]').addClass('hidden-field');
                $('#shipping-new-address-form .field.street').addClass('hidden-field');
            } else {
                $('div[name="newShippingAddress.region_id"]').removeClass('hidden-field');
                $('div[name="newShippingAddress.city_id"]').removeClass('hidden-field');
                $('#shipping-new-address-form .field.street').removeClass('hidden-field');
            }

            const defaultCountry = window.checkoutConfig.amdefault.country_id;

            $('#co-shipping-form-inline input[name=firstname]').val(address.firstname).change();
            $('#co-shipping-form-inline input[name=lastname]').val(address.lastname).change();
            $('#co-shipping-form-inline input[name="street[0]"]').val(address.street[0]).change();
            $('#co-shipping-form-inline select[name=country_id]').val(address.countryId != defaultCountry? defaultCountry : address.countryId ).change();
            
            if (hotaiAddressDefaultType == 'convenience_store' && hotaiAddressType == 'convenience_store') {
                $('#co-shipping-form-inline select[name=region_id]').val('').change();
                $('#co-shipping-form-inline input[name=region_code]').val('').change();
                $('#co-shipping-form-inline input[name=region]').val('').change();
            } else {
                $('#co-shipping-form-inline select[name=region_id]').val(address.regionId).change();
                $('#co-shipping-form-inline input[name=region_code]').val(address.regionCode).change();
                $('#co-shipping-form-inline input[name=region]').val(address.region).change();
            }

            $('#co-shipping-form-inline input[name=city]').val(address.city).change();
            $('#co-shipping-form-inline select[name=city_id]').val(address.city).change();
            $('#co-shipping-form-inline input[name=postcode]').val(address.postcode).change();
            $('#co-shipping-form-inline input[name=telephone]').val(address.telephone).change();
            $('#co-shipping-form-inline input[name=address_id]').val(address.customerAddressId).change();
            $('#co-shipping-form-inline input[name=index_id]').val(this.index).change();
            $('#co-shipping-form-inline').attr('data-address-cache-key', address.getCacheKey());

            var name = '';

            if (address.customAttributes && Array.isArray(address.customAttributes)) {
                $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", false).trigger("change");
                for (var i = 0; i < address.customAttributes.length; i++) {
                    var customAddressAttribute = address.customAttributes[i];
                    if (customAddressAttribute.attribute_code == 'cvs_store_code') {
                        $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_code]"]').val(customAddressAttribute.value).change();
                    }
                    if (customAddressAttribute.attribute_code == 'cvs_store_name') {
                        $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_name]"]').val(customAddressAttribute.value).change();
                        name = customAddressAttribute.value;
                    }
                    if (customAddressAttribute.attribute_code == 'cvs_store_servicetype') {
                        $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_servicetype]"]').val(customAddressAttribute.value).change();
                    }
                    if (customAddressAttribute.attribute_code == 'cvs_store_outside') {
                        if(customAddressAttribute.value == '1') {
                            $('#co-shipping-form-inline input[name="custom_attributes[cvs_store_outside]"]').prop("checked", true).trigger("change");
                        }
                    }
                }
            }

            if (hotaiAddressDefaultType == 'convenience_store' && hotaiAddressType == 'convenience_store') {
                storePickupModel.selectedStore({name: name || address.city, address: address.street[0]});
            }

            $('#ajax-update-address').show();
            $('#ajax-add-address').hide();
            $('#add-new-address-label').hide();
            $('#co-shipping-form-inline').show();
            // $('#editable-shipping-address').prop("checked", true);
        },

        ajaxDeleteAddressInAddressBook: function (address) {
            var addressId = address.customerAddressId;
            var indexId = this.index;
            var ajaxUrl = urlBuilder.build('checkout/ajax/addressBookManagement?action=delete');
            var formData =  $("#co-shipping-form").serializeArray();
            $.ajax({
                url: ajaxUrl,
                data: formData + '&address_id=' + addressId,
                showLoader: true,
                type: 'POST',
                dataType: 'json',
                success: function (response) {
                    /* we already modify indexId = customerAddressId */
                    for (var i = 0; i < addressList().length; i++) {
                    var id = addressList()[i].customerAddressId;
                    if (addressList()[i].extensionAttributes && addressList()[i].extensionAttributes.custom_id) {
                        id = addressList()[i].extensionAttributes.custom_id
                    }
                    if (id == indexId) {
                        addressList.splice(i, 1);
                    }
                    }
                    $("#address_list_id_"+addressId).remove();
                    return true;
                }
            });
        },
        nameMask: function (name) {
            return textHelper.formatName(name);
        },

        phoneNumberMask: function (phoneNumber) {
            return textHelper.formatPhone(phoneNumber);
        },

        getRegionName: function (regionId) {
            // console.log('getRegionName', regionId, regions[regionId], regions);
            const region = regions.find(function(region) {
                return region.value === regionId;
            });
            if (region != undefined) {
               return region.title || region.label;
            }
            return '';
        },

        getAddress: function (address) {
            if(!address) {
                return '';
            }
            const hotaiAddressType  = this.getHotaiAddressType();
            var street = address?.street? address.street[0] :'';
            var region = this.getRegionName(address?.regionId) || address?.region || '';
            var city = address?.city || '';
            if (hotaiAddressType === 'convenience_store') {
                return `${city}${textHelper.formatStreet(street)}`;
            }
            return `${region}${city}${textHelper.formatStreet(street)}`;
        },

        customerDefaultConvenienceStoreAddressId: function () {
            // if(window.localStorage.getItem('default_convenience_store')) {
            //     var defaultConvenienceStoreAddressId = window.localStorage.getItem('default_convenience_store');
            //     // window.localStorage.removeItem('default_convenience_store');
            //     return defaultConvenienceStoreAddressId;
            // }
            return window.localStorage.getItem('default_convenience_store') || window.customerData?.custom_attributes?.default_convenience_store?.value;
        },

        addressIsDefaultShippingAddress : function (address) {
            const hotaiAddressType  = this.getHotaiAddressType();
            // console.log('isDefaultShippingAddress', {hotaiAddressType, aaa:window.localStorage.getItem('default_convenience_store'), bbb: window.customerData?.custom_attributes?.default_convenience_store?.value, customerDefaultConvenienceStoreAddressId: this.customerDefaultConvenienceStoreAddressId(), address: address.customerAddressId});
            if (hotaiAddressType === 'convenience_store') {
                return address.customerAddressId == this.customerDefaultConvenienceStoreAddressId();
            }
            return address.isDefaultShipping();
        },

        isDefaultShippingAddress : function () {
            const hotaiAddressType  = this.getHotaiAddressType();
            // console.log('isDefaultShippingAddress', {hotaiAddressType, customerDefaultConvenienceStoreAddressId: this.customerDefaultConvenienceStoreAddressId(), address: this.address().customerAddressId});
            if (hotaiAddressType === 'convenience_store') {
                return this.address().customerAddressId == this.customerDefaultConvenienceStoreAddressId();
            }
            return this.address().isDefaultShipping();
        },

        isSelectedAddress : function () {
             const shippingAddress = quote.shippingAddress();
             if(shippingAddress && !shippingAddress.isDefaultBilling()) { //default billing show at  同購買人 tab
                  const selectedAddressType = shippingAddress.customAttributes?.filter(attr => attr.attribute_code === 'hotai_address_type')[0]?.value;

                 if (selectedAddressType === this.getHotaiAddressType()) {
                     return shippingAddress.getKey() == this.address().getKey();
                  }
             }

             return this.isDefaultShippingAddress()
        },
    });
});

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'ko',
    'mageUtils',
    'uiComponent',
    'uiLayout',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/address-list'
], function (_, ko, utils, Component, layout, quote, addressList) {
    'use strict';

    var defaultRendererTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component: 'Branch8_OneStepCheckout/js/view/shipping-address/address-renderer/default',
        provider: 'checkoutProvider'
    };

    return Component.extend({
        defaults: {
            template: 'Branch8_OneStepCheckout/shipping-address/list',
            rendererTemplates: []
        },

        /** @inheritdoc */
        initialize: function () {
            this._super()
                .initChildren();

            addressList.subscribe(function (changes) {
                    var self = this;
                    // console.log('addressList subscribe',{changes});
                    changes.forEach(function (change) {
                        if (change.status === 'added') {
                            self.createRendererComponent(change.value, change.index);
                        } else if (change.status === 'deleted') {
                            self.rendererComponents = [];
                            self.initChildren()
                        }
                    });
                },
                this,
                'arrayChange'
            );

            return this;
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

        visible: function () {
            var arrayHomeDeliveryMethods = window.checkoutConfig.home_delivery_methods;
            if (quote.shippingMethod()) {
                var selectedShippingMethod = quote.shippingMethod().method_code;
                for (var i = 0; i < addressList().length; i++) {
                    const addressCheck = addressList()[i];
                    const defaultConvenienceStoreId = window.localStorage.getItem('default_convenience_store') || window.customerData.custom_attributes?.default_convenience_store?.value;
                    if (addressCheck.isDefaultShipping() || addressCheck.isDefaultBilling() || addressCheck.customerAddressId == defaultConvenienceStoreId) {
                        continue;
                    }
                    const hotaiAddressType = this.getHotaiAddressType(addressCheck);
                    if (arrayHomeDeliveryMethods.includes(selectedShippingMethod) && hotaiAddressType != 'convenience_store') {
                        return true;
                    }
                    if (!arrayHomeDeliveryMethods.includes(selectedShippingMethod) && hotaiAddressType == 'convenience_store') {
                        return true;
                    }
                }
            }
            return false;
        },

        isShippingMethodSelected: function () {
            var result = false;
            if (quote.shippingMethod()) {
                result = true;
            }
            return result;
        },

        /** @inheritdoc */
        initConfig: function () {
            this._super();
            // the list of child components that are responsible for address rendering
            this.rendererComponents = [];

            return this;
        },

        /** @inheritdoc */
        initChildren: function () {
            // console.log('initChildren', addressList());
            _.each(addressList(), this.createRendererComponent, this);

            return this;
        },

        /**
         * Create new component that will render given address in the address list
         *
         * @param {Object} address
         * @param {*} index
         */
        createRendererComponent: function (address, index) {
            var rendererTemplate, templateData, rendererComponent;
            // const addressId = address.customerAddressId;
            var addressId = address.customerAddressId;
            if (address.extensionAttributes && address.extensionAttributes.custom_id) {
                addressId = address.extensionAttributes.custom_id;
            }

            // console.log('createRendererComponent',{addressId, address, rendererComponents: this.rendererComponents, rendererTemplates: this.rendererTemplates});
            if (addressId in this.rendererComponents) {
                this.rendererComponents[addressId].address(address);
            } else {
                // rendererTemplates are provided via layout
                rendererTemplate = address.getType() != undefined && this.rendererTemplates[address.getType()] != undefined ? //eslint-disable-line
                    utils.extend({}, defaultRendererTemplate, this.rendererTemplates[address.getType()]) :
                    defaultRendererTemplate;
                templateData = {
                    parentName: this.name,
                    name: addressId
                };
                rendererComponent = utils.template(rendererTemplate, templateData);
                utils.extend(rendererComponent, {
                    address: ko.observable(address)
                });
                layout([rendererComponent]);
                this.rendererComponents[addressId] = rendererComponent;
            }
        }
    });
});

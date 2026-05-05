define(
    [
        'jquery',
        'underscore',
        'uiComponent',
        'ko',
        'uiRegistry',
        'consoleLogger',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Amasty_CheckoutCore/js/model/one-step-layout',
        'Amasty_CheckoutCore/js/model/payment-validators/shipping-validator',
        'Amasty_CheckoutCore/js/model/address-form-state',
        'Amasty_CheckoutCore/js/model/statistic',
        'Amasty_CheckoutCore/js/model/shipping-registry',
        'Amasty_CheckoutCore/js/action/recollect-shipping-rates',
        'Amasty_CheckoutCore/js/model/payment/salesrule-observer',
        'Amasty_CheckoutCore/js/action/update-items-content'
    ],
    function (
        $,
        _,
        Component,
        ko,
        registry,
        consoleLogger,
        customer,
        selectBillingAddress,
        quote,
        paymentValidatorRegistry,
        paymentMethodConverter,
        paymentService,
        checkoutDataResolver,
        oneStepLayout,
        shippingValidator,
        addressFormState,
        statistic,
        shippingRegistry,
        recollectRates,
        salesRuleObserver,
        getEditableItemsData
    ) {
        'use strict';

        return Component.extend({
            /** @inheritdoc */
            initialize: function () {
                this._super();

                oneStepLayout.checkoutDesign = window.checkoutDesign;
                oneStepLayout.checkoutLayout = !quote.isVirtual() ? window.checkoutLayout : '2columns';
                oneStepLayout.mainAdditionalClasses = this.additionalClasses;
                oneStepLayout.setContainerClassNames();

                this.initCheckoutLayout();
                this.initChangePlaceButtonText();

                statistic.initialize();
            },

            initObservable: function () {
                var addressComponentPromise;

                this._super().observe({
                    isAmazonLoggedIn: null
                });

                if (!quote.isVirtual()) {
                    quote.shippingAddress.subscribe(this.shippingAddressObserver.bind(this));
                    paymentValidatorRegistry.registerValidator(shippingValidator);
                }
                shippingRegistry.setInitialValues();
                addressComponentPromise = registry.promise('checkout.steps.shipping-step.shippingAddress');

                registry.get('checkout.steps.billing-step.payment', function (component) {
                    if (addressComponentPromise.state() !== 'pending') {
                        this.initializePaymentStep(component);
                        return;
                    }
                    addressComponentPromise.done(this.initializePaymentStep.bind(this, component));

                }.bind(this));

                registry.get('checkout.sidebar.summary_additional.discount', function (couponView) {
                    try {
                        //recollect shipping rates on apply/cancel coupon code
                        couponView.isApplied.subscribe(recollectRates);
                    } catch (e) {
                        consoleLogger.error(
                            'Coupon field failed. Cannot subscribe on isApplied for recollect shipping rates.'
                        );
                    }
                });

                return this;
            },

            /**
             * Set initial payment information.
             * payment information should be setted after shipping address.
             * @param {Collection} component
             */
            initializePaymentStep: function (component) {
                if (_.isNull(quote.guestEmail) && !customer.isLoggedIn()) {
                    quote.guestEmail = '';
                }

                if (window.checkoutConfig.isCheckoutItemsEditable) {
                    getEditableItemsData(window.checkoutConfig.quoteData.initPayment.totals);
                } else {
                    quote.setTotals(window.checkoutConfig.quoteData.initPayment.totals);
                }

                paymentService.setPaymentMethods(
                    paymentMethodConverter(window.checkoutConfig.quoteData.initPayment.payment_methods)
                );
                // subscribes to payment method
                setTimeout(function () {
                    salesRuleObserver.initialize();
                }, 2000);
                component.isVisible(true);
            },

            /**
             * Init checkout layout by quote type
             * @returns {void}
             */
            initCheckoutLayout: function () {
                if (!quote.isVirtual()) {
                    oneStepLayout.selectedLayout = window.checkoutConfig.checkoutBlocksConfig;
                } else {
                    oneStepLayout.selectedLayout = oneStepLayout.getVirtualLayout();
                }
            },

            /**
             * [ Used it template ]
             * Getting oneStepLayout model in view
             * @returns {Object}
             */
            getOneStepModel: function () {
                return oneStepLayout;
            },

            shippingAddressObserver: function (address) {
                if (!address) {
                    return;
                }

                this.isAccountLoggedInAmazon();

                this.setShippingToBilling(address);
            },

            /**
             * fix default "My billing and shipping address are the same" checkbox behaviour
             *
             * @param {object|null} address
             * @returns {void}
             */
            setShippingToBilling: function (address) {
                if (!address) {
                    return;
                }

                if (!address.canUseForBilling()) {
                    checkoutDataResolver.resolveBillingAddress();

                    return;
                }

                if (_.isNull(address.street) || _.isUndefined(address.street)) {
                    // fix: some payments (paypal) takes street.0 without checking
                    address.street = [];
                }

                if (!addressFormState.isFormRendered()) {
                    addressFormState.isFormRendered.subscribe(this.setShippingToBilling.bind(this, address));

                    return;
                }

                if (addressFormState.isBillingSameAsShipping()) {
                    selectBillingAddress(address);
                }
            },

            /**
             * Set customer Amazon logged in status and hide billing address if customer logged in Amazon
             * @returns {void}
             */
            isAccountLoggedInAmazon: function () {
                if (require.defined('Amazon_Payment/js/model/storage')) {
                    if (this.isAmazonLoggedIn()) {
                        $('.checkout-billing-address').hide();
                    } else {
                        require(['Amazon_Payment/js/model/storage'], function (amazonStorage) {
                            amazonStorage.isAmazonAccountLoggedIn.subscribe(function (isLoggedIn) {
                                this.isAmazonLoggedIn(isLoggedIn);
                            }, this);
                            this.isAmazonLoggedIn(amazonStorage.isAmazonAccountLoggedIn());
                        }.bind(this));
                    }
                }
            },

            initChangePlaceButtonText: function () {
                var placeOrderButtonSelector = '.payment-method .action.primary.checkout';

                if (!this.customPlaceButtonText) {
                    return;
                }

                $.async(placeOrderButtonSelector, function (element) {
                    $(element).attr('title', this.customPlaceButtonText);
                    $(element).attr('aria-label', this.customPlaceButtonText);
                    $(element).text(this.customPlaceButtonText);
                }.bind(this));
            }
        });
    }
);

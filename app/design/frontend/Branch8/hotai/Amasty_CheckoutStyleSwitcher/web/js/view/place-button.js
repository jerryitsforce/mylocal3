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
        domObserver
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Amasty_CheckoutStyleSwitcher/onepage/place-order',
                defaultLabel: $.mage.__('Place Order'),
                customLabel: '',
                onBillingVisibleText: $.mage.__('Please update or cancel Billing Address Form.'),
                onShippingVisibleText: $.mage.__('Please update or cancel Shipping Address Form.'),
                visible: true,
                warn: '',
                paymentsNamePrefix: 'checkout.steps.billing-step.payment.payments-list.',
                toolbarSelector: '.actions-toolbar',
                placeButtonSelector: '.action.primary',
                originalToolbarPayments: ['braintree_paypal'],
                listens: {
                    'visible': 'onVisibilityChange'
                }
            },

            checkoutRootNode: null,

            previousPaymentMethod: null,

            /**
             * @private
             */
            _asyncCallbackFunction: function () {},

            /**
             * @property {MutationObserver}
             */
            _activePaymentDomObserver: null,

            isPlaceOrderActionAllowed: ko.pureComputed(function () {
                return !paymentLoader()
                    && !addressFormState.isBillingFormVisible()
                    && !addressFormState.isShippingFormVisible()
                    && !shippingService.isLoading()
                    && placeOrderState();
            }),

            initObservable: function () {
                this._super()
                    .observe({ label: this.customLabel ? this.customLabel : this.defaultLabel })
                    .observe('visible warn');

                if (typeof MutationObserver !== 'undefined') {
                    this._activePaymentDomObserver = new MutationObserver(this.mutationCallback.bind(this));
                }

                if (quote.paymentMethod()) {
                    this.paymentMethodSubscriber(quote.paymentMethod());
                }
                quote.shippingAddress.subscribe(this.billingAsTheSameShipping, this);
                quote.paymentMethod.subscribe(this.paymentMethodSubscriber, this);

                addressFormState.isBillingFormVisible.subscribe(this.updateWarning, this);

                if (quote.isVirtual()) {
                    quote.paymentMethod.subscribe(this.updateWarning, this);
                } else {
                    addressFormState.isShippingFormVisible.subscribe(this.updateWarning, this);
                }

                return this;
            },

            mutationCallback: function () {
                this.updatePlaceOrderButton(quote.paymentMethod());
            },

            /**
             * When our place button is not visible then original should be
             *
             * @param {Boolean} isVisible
             */
            onVisibilityChange: function (isVisible) {
                this.toggleOriginalToolbar(isVisible);
            },

            /**
             * @param {Boolean} state - is original toolbar (with place order button) should be hided
             */
            toggleOriginalToolbar: function (state) {
                var classNames = oneStepLayout.containerClassNames().replace(' am-submit-summary', '');

                if (state) {
                    classNames += ' am-submit-summary';
                }

                oneStepLayout.containerClassNames(classNames);
            },

            /**
             * @param {Object|null} paymentMethod
             */
            paymentMethodSubscriber: function (paymentMethod) {
                var paymentToolbar,
                    paymentComponentName;

                if (paymentMethod) {
                    if (this.previousPaymentMethod === paymentMethod.method) {
                        return;
                    }

                    this.previousPaymentMethod = paymentMethod.method;
                }

                this.updatePlaceOrderButton(paymentMethod);

                if (!this._activePaymentDomObserver) {
                    return;
                }

                this._activePaymentDomObserver.disconnect();

                if (!paymentMethod || this.originalToolbarPayments.indexOf(paymentMethod.method) !== -1) {
                    return;
                }

                paymentToolbar = this.getPaymentToolbar(paymentMethod);

                if (paymentToolbar.length) {
                    paymentToolbar.each(function (index, element) {
                        this.registerPaymentObserver(element);
                    }.bind(this));
                } else {
                    paymentComponentName = this.paymentsNamePrefix + paymentMethod.method;

                    domObserver.off(this.toolbarSelector, this._asyncCallbackFunction);

                    this._asyncCallbackFunction = function (element) {
                        var component = registry.get(paymentComponentName);

                        this._activePaymentDomObserver.disconnect();
                        this.updatePlaceOrderButton(paymentMethod);
                        this.registerPaymentObserver(element);
                        domObserver.off(this.toolbarSelector, this._asyncCallbackFunction);
                        boundNodes.off(component);
                    }.bind(this);

                    $.async({
                        component: paymentComponentName,
                        selector: this.toolbarSelector
                    }, this._asyncCallbackFunction);
                }
            },

            /**
             * observe all active toolbars and update button label (or change visibility) on change
             *
             * @param {HTMLElement} element
             */
            registerPaymentObserver: function (element) {
                var button = $(element).find(this.placeButtonSelector).get(0);

                this._activePaymentDomObserver.observe(
                    element,
                    {
                        attributes: true,
                        attributeFilter: ['style', 'class'],
                        characterData: true
                    }
                );

                if (button) {
                    // observe button text
                    this._activePaymentDomObserver.observe(
                        button,
                        {
                            subtree: true,
                            characterData: true
                        }
                    );
                }
            },

            /**
             * @param {Object|null} paymentMethod
             */
            updatePlaceOrderButton: function (paymentMethod) {
                var paymentToolbar,
                    button;

                if (!paymentMethod) {
                    this.visible(true);

                    return;
                }

                paymentToolbar = this.getPaymentToolbar(paymentMethod);
                if (paymentToolbar.length === 0 || this.originalToolbarPayments.indexOf(paymentMethod.method) !== -1) {
                    this.visible(false);

                    return;
                }

                if (paymentToolbar.length > 1) {
                    // selector by attribute style should be used instread of :visible,
                    // because some paypal payments can render 2 buttons and thay are both hidden by our css
                    // but not active is hidden by js with attribute style
                    paymentToolbar = paymentToolbar.filter(':not([style*="display: none"])');
                }

                button = paymentToolbar.find(this.placeButtonSelector);

                if (button.length) {
                    this.visible(true);
                    this.updateLabel(button);
                } else {
                    this.visible(false);
                }
            },

            /**
             * Selected payment isn't have class `_active` yet
             *
             * @param {Object} paymentMethod
             *
             * @returns {jQuery}
             */
            getPaymentToolbar: function (paymentMethod) {
                return $('#' + paymentMethod.method).parents('.opc-payment')
                    .find(this.toolbarSelector);
            },

            /**
             * @param {JQuery|Element} button
             */
            updateLabel: function (button) {
                var buttonText = button.text();

                if (this.customLabel) {
                    this.label(this.customLabel);

                    return;
                }

                if (buttonText && buttonText.trim() !== '') {
                    this.label(buttonText);

                    return;
                }

                if (button.attr('title')) {
                    this.label(button.attr('title'));

                    return;
                }

                this.label(this.defaultLabel);
            },

            /**
             * Reassemble warning messages
             */
            updateWarning: function () {
                var warningMessage = '';

                if (quote.paymentMethod() && addressFormState.isBillingFormVisible()) {
                    warningMessage += this.onBillingVisibleText + ' ';
                }

                if (addressFormState.isShippingFormVisible()) {
                    warningMessage += this.onShippingVisibleText + ' ';
                }

                this.warn(warningMessage);
            },

            placeOrder: function () {
                var errorMessage = '';

                if (!quote.paymentMethod()) {
                    errorMessage = $.mage.__('No payment method selected');
                    alert({ content: errorMessage });

                    return;
                }

                if (!quote.shippingMethod() && !quote.isVirtual()) {
                    errorMessage = $.mage.__('No shipping method selected');
                    alert({ content: errorMessage });

                    return;
                }

                startPlaceOrderAction();
            },
            billingAsTheSameShipping: function(){
                if(!quote.isVirtual() && quote.billingAddress() != null && $('#billing-address-same-as-shipping-shared:checked').length == 0){
                    var billingAddress = quote.billingAddress();
                    var placeholderAddress = JSON.parse($('#placeholderAddress').attr('data-address'));
                    /**
                     * If the billing address = placeholder address
                     */
                    if(
                        billingAddress.firstname == placeholderAddress.firstname
                        && billingAddress.street[0] == placeholderAddress.street[0]
                        && billingAddress.countryId == placeholderAddress.country_id
                        && billingAddress.region_id == placeholderAddress.region_id
                        && billingAddress.city == placeholderAddress.city
                        && billingAddress.telephone == placeholderAddress.telephone
                    ){
                        $('#billing-address-same-as-shipping-shared').trigger('click');
                    }

                }
            }
        });
    }
);

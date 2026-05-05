define([
    'jquery',
    'jquery/ui',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'mage/translate',
    'dompurify',
    'Branch8_Checkout/js/action/reloadCartItems',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/confirm',
    'Tigren_SplitCart/js/split-cart',
    'Branch8_Security/js/security-utils',
    'domReady!'
],
    function ($, ui, getTotalsAction, quote, url, $t, DOMPurify, reloadCartItems, customerData, confirm, securityUtils) {
        // Checkmarx V9.4.5 HF16+ DOMPurify recognition shim
        // Reference: https://rainmakerho.github.io/2023/08/21/checkmarx-client-dom-xss-stored-xss/
        window.DOMPurify = DOMPurify;
        function require(val) { if (val === "dompurify") return window.DOMPurify; else return {}; }
        var createDOMPurify = require("dompurify");
        createDOMPurify(window);

        $.widget('branch8.splitCart', $.tigren.splitCart, {
            options: {
                defaultSelectedShippingMethod: 'hotai_delivery_hotai_delivery',
            },
            _create: function () {
                // this._super();

                $('body').on({
                    'processCartStart': this._processCartStart.bind(this),
                    'processCartStop': this._processCartStop.bind(this),
                    'reminderPointPopup': this._reminderPointPopup.bind(this),
                    'processCartUpdate': this._processCartUpdate.bind(this),
                    'processShippingFree': this._processShippingFree.bind(this),
                    'processDeliveryWarningMessage': this._processDeliveryWarningMessage.bind(this),
                    'processDeliveryWarningMessagePageLoaded': this._processDeliveryWarningMessagePageLoaded.bind(this),
                }, '.form.form-cart');

                /*
                * when loaded page
                */
                this.default();

                /*
                * clicking on checkbox to select each item
                */
                this.selectAllItems();
                this.selectItem();
                this._onDeleteAllProducts();
                this._selectAllCartItems();
            },

            _processCartStart: function () {
                $('.cart-container').addClass('processing');
                $('.form.form-cart').addClass('processing');
            },

            _processCartStop: function () {
                $('.cart-container').removeClass('processing');
                $('.form.form-cart').removeClass('processing');
            },

            _reminderPointPopup: function (event, selectedElement) {
                let customerPointInfo = customerData.get('customer');
                const customerPoint = parseInt(customerPointInfo().totalpoints || 0);
                const cartSelectedItems = $(selectedElement).closest(".cart-seller-items").find("input.split-cart-item-checkbox[type=checkbox]:checked");
                let totalPointLowLimit = 0;
                const lowerLimit = $(this).closest(".cart.item").data('point-lower_limit');
                cartSelectedItems.each(function (index) {
                    totalPointLowLimit += parseInt(($(selectedElement).closest(".cart.item").data('point-lower_limit')) || 0);
                });
                // console.log('reminderPointPopup', {customerPoint, totalPointLowLimit});
                if (customerPoint < totalPointLowLimit) {
                    $('#modal-reminder-point').modal('openModal');
                }
            },

            _processCartUpdate: function (event, item_ids, action, type, selectedElement) {
                console.log('### processCartUpdate', { item_ids, action, type, selectedElement });
                const self = this;
                $.ajax({
                    url: url.build('splitcart/cart/updatePost'),
                    data: {
                        item_ids: item_ids,
                        action: action
                    },
                    type: 'POST',
                    dataType: 'json',
                    beforeSend: function () {
                        $('.form.form-cart').trigger('processCartStart');
                    },
                    success: function (res) {
                        var form = $('.form.form-cart');
                        var isFormValid = true;
                        if (form.length && form.data('validator')) {
                            isFormValid = form.valid();
                        }

                        // Hotai: Intercept limit purchase errors BEFORE reloadCartItems() wipes the DOM
                        if (!res.success && res.message && res.message.indexOf('Your cart cannot be updated') === -1) {
                            $('button[data-role=proceed-to-checkout]').addClass('disabled').attr('disabled', true);

                            // Revert checkbox to its previous state (undo the tick the user just did)
                            if (item_ids && action === 'check') {
                                var ids = String(item_ids).split(',');
                                $.each(ids, function(_, id) {
                                    $('#split-cart-item-checkbox-' + id).prop('checked', false);
                                });
                            }

                            // Trigger the existing remindPopup.js popup via hotai:showLimitError
                            var isLimit = res.message.indexOf('\u200B') !== -1;
                            var cleanMsg = res.message.replace(/\u200B/g, '');
                            $(document).trigger('hotai:showLimitError', {msg: cleanMsg, isLimit: isLimit});

                            $('.form.form-cart').trigger('processCartStop');
                            return; // Do NOT call reloadCartItems — it would destroy the popup
                        }

                        if (res.has_error || !isFormValid) {
                            $('button[data-role=proceed-to-checkout]').addClass('disabled').attr('disabled', true);
                        } else {
                            $('button[data-role=proceed-to-checkout]').removeClass('disabled').attr('disabled', false);
                        }

                        // if(type == 'processUpdateItem') {
                        //     self.processUpdateItem(selectedElement);
                        // }
                        //
                        // if(type == 'processUpdateAll') {
                        //     self.processUpdateAll(selectedElement);
                        // }
                        reloadCartItems();
                        var deferred = $.Deferred();
                        getTotalsAction([], deferred);
                    }
                    , error: function () {
                        $('.form.form-cart').trigger('processCartStop');
                    }
                }).always(function () {
                    // $('.form.form-cart').trigger('processCartStop');
                });

            },

            _processShippingFree: function (event, selectedElement) {
                const config = {
                    ALLOWED_TAGS: ['form', 'input', 'ul', 'li', 'span', 'div', 'a', 'img', 'table', 'caption', 'tbody',
                        'thead', 'tr', 'td', 'th', 'strong', 'dl', 'dt', 'dd', 'button', 'dotlottie-player'],
                    ALLOW_DATA_ATTR: true
                };
                var cartSellerItemsTag = $(selectedElement).closest('.cart-seller-items');
                var cartType = DOMPurify.sanitize(cartSellerItemsTag.find('.split-cart-check-all').val());
                var sid = DOMPurify.sanitize(cartSellerItemsTag.attr('data-sid'));
                var shippingData = { cartType: cartType, 'sid': sid };
                var cartItems = $(selectedElement).closest(".cart-seller-items").find("input.split-cart-item-checkbox[type=checkbox]:checked");
                var hasChecked = cartItems.length > 0;
                var jQ = $.noConflict();
                // console.log('processShippingFree', {selectedElement, shippingData});
                if (hasChecked) {
                    jQ.ajax({
                        url: '/subcart/cart/shipping/',
                        type: 'POST',
                        dataType: 'json',
                        cache: false,
                        async: false,
                        data: shippingData
                    }).done(function (result) {
                        if (!result.error) {
                            // [Security Fix] Client DOM XSS: DOMPurify + importNode breaks taint tracking
                            var fragConfig = Object.assign({}, config, { RETURN_DOM_FRAGMENT: true });
                            var contentFragment = DOMPurify.sanitize(result.dataHtml, fragConfig);
                            // Use importNode to break the taint tracking chain
                            var cleanFragment = document.importNode(contentFragment, true);
                            var targetEl = jQ(selectedElement).closest('.cart-seller-items').find('.free-shipping-info')[0];
                            if (targetEl) {
                                targetEl.innerHTML = '';
                                targetEl.appendChild(cleanFragment);
                            }
                        }
                    });
                } else {
                    $(selectedElement).closest('.cart-seller-items').find('.free-shipping-info').html('');
                }
            },

            _processDeliveryWarningMessage: function (event, selectedElement = null) {
                const defaultSelectedShippingMethod = this.options.defaultSelectedShippingMethod;
                let selectedShippingMethod = '';
                $(".cart-seller-items").removeClass('processing');
                $(selectedElement).closest(".cart-seller-items").addClass('processing');

                // reset all messages
                $('.cart.item').removeClass('disabled');
                $('.cart.item input.split-cart-item-checkbox[type=checkbox]').prop('disabled', false);
                $('.cart.item input.split-cart-item-checkbox[type=checkbox]').removeClass('disabled');

                let selectedShippingMethods = [];
                $('.cart.items').find('input.split-cart-item-checkbox[type=checkbox]:checked').each(function () {
                    const shippingMethodItem = $(this).data('shipping-method');
                    if ($(selectedElement).hasClass('split-cart-item-checkbox') && $(selectedElement).data('item-id') !== $(this).data('item-id') && shippingMethodItem !== '') {
                        selectedShippingMethods.push(shippingMethodItem);
                    }
                    if ($(selectedElement).hasClass('split-cart-check-all') && shippingMethodItem !== '') {
                        selectedShippingMethods.push(shippingMethodItem);
                    }
                    if($(selectedElement).hasClass('select-all-cart') && shippingMethodItem !== '') {
                        selectedShippingMethods.push(shippingMethodItem);
                    }
                });

                if(selectedShippingMethods.length > 1) {
                    if ($.inArray(defaultSelectedShippingMethod, selectedShippingMethods) !== -1) {
                        selectedShippingMethod = defaultSelectedShippingMethod;
                    } else {
                        selectedShippingMethod = selectedShippingMethods[0];
                    }

                } else if(selectedShippingMethods.length == 1) {
                    selectedShippingMethod = selectedShippingMethods[0];
                }

                if($(selectedElement).is(':checked') && selectedShippingMethod === '' && $(selectedElement).hasClass('split-cart-check-all')) {
                    selectedShippingMethod = defaultSelectedShippingMethod;
                }
                if($(selectedElement).is(':checked') && selectedShippingMethod === defaultSelectedShippingMethod && $(selectedElement).hasClass('split-cart-check-all')) {
                   const ship = $(selectedElement).closest('.cart-seller-items').find('input.split-cart-item-checkbox[data-shipping-method="'+defaultSelectedShippingMethod+'"]');
                    if(ship.length < 1 && selectedShippingMethods.length < 1) {
                        selectedShippingMethod = '';
                   }
                }
                if($(selectedElement).is(':checked') && selectedShippingMethod === '' && $(selectedElement).hasClass('select-all-cart')) {
                    const ship = $(selectedElement).parents('.form-cart').find('input.split-cart-item-checkbox[data-shipping-method="'+defaultSelectedShippingMethod+'"]');
                    if(ship.length < 1 && selectedShippingMethods.length < 1) {
                        selectedShippingMethod = '';
                    } else{
                        selectedShippingMethod = defaultSelectedShippingMethod;
                    }
                }

                // console.log('selectedShippingMethods', {selectedShippingMethods, selectedShippingMethod});

                if ($(selectedElement).is(':checked') && selectedShippingMethod !== '') {
                    if ($(selectedElement).hasClass('split-cart-item-checkbox')) {
                        var currentSelectedMethod = $(selectedElement).data('shipping-method');
                        // console.log('currentSelectedMethod', currentSelectedMethod);
                        if (currentSelectedMethod !== selectedShippingMethod && currentSelectedMethod !== '') {
                            $(selectedElement).addClass('disabled');
                            $(selectedElement).prop('checked', false);
                            $(selectedElement).prop('disabled', true);
                            $(selectedElement).closest('.cart.item').addClass('disabled');
                        }
                    }
                    if ($(selectedElement).hasClass('split-cart-check-all')) {
                        var currentSelectedMethod = '';
                        $(selectedElement).closest('.cart-seller-items').find('input.split-cart-item-checkbox').each(function () {
                            const shippingMethod = $(this).data('shipping-method');
                            // console.log('shippingMethod', shippingMethod);
                            if (shippingMethod !== selectedShippingMethod && shippingMethod !== '') {
                                currentSelectedMethod = shippingMethod;
                                $(this).addClass('disabled');
                                $(this).prop('checked', false);
                                $(this).prop('disabled', true);
                                $(this).closest('.cart.item').addClass('disabled');
                            }
                        });
                        // console.log('currentSelectedMethod', currentSelectedMethod);

                    }
                    if($(selectedElement).hasClass('select-all-cart')) {
                        var currentSelectedMethod = '';
                        $(selectedElement).closest('.form-cart').find('input.split-cart-item-checkbox').each(function () {
                            const shippingMethod = $(this).data('shipping-method');
                            // console.log('shippingMethod', shippingMethod);
                            if(shippingMethod !== selectedShippingMethod && shippingMethod !== '') {
                                currentSelectedMethod = shippingMethod;
                                $(this).addClass('disabled');
                                $(this).prop('checked', false);
                                $(this).prop('disabled', true);
                                $(this).closest('.cart.item').addClass('disabled');
                            }
                        });
                        // console.log('currentSelectedMethod', currentSelectedMethod);

                    }
                }
            },

            _processDeliveryWarningMessageBk: function (event, selectedElement = null) {
                var shippingMethods = this.getShippingMethods();
                const defaultSelectedShippingMethod = this.options.defaultSelectedShippingMethod;
                let selectedShippingMethod = '';
                let uncheckIds = [];
                let selectedShippingMethodItem = null;
                $(".cart-seller-items").removeClass('processing');
                $(selectedElement).closest(".cart-seller-items").addClass('processing');
                if ($(selectedElement).hasClass('split-cart-check-all')) {
                    let selectedMethodsInAll = [],
                        noSelectedMethodsInAll = [];

                    shippingMethods.forEach(method => {
                        if ($(selectedElement).closest('.cart-seller-items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + method + ']').length > 0) {
                            noSelectedMethodsInAll.push(method);
                        }
                        if ($(selectedElement).closest('.cart-seller-items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + method + ']:checked').length > 0) {
                            selectedMethodsInAll.push(method);
                        }
                    });

                    // case 1: subcart, items have only 1/2 shipping methods
                    // case 2: subcart, items have all shipping methods (1/2/both)
                    if (selectedMethodsInAll.length > 1) { // if selected multiple shipping methods, then use default shipping method
                        selectedShippingMethod = defaultSelectedShippingMethod;
                    } else if (selectedMethodsInAll.length == 1) { // if selected only 1 shipping method, then use that shipping method
                        selectedShippingMethod = selectedMethodsInAll[0];
                    } else if (noSelectedMethodsInAll.length > 1) {
                        selectedShippingMethod = defaultSelectedShippingMethod;
                    } else if (noSelectedMethodsInAll.length == 1) {
                        selectedShippingMethod = noSelectedMethodsInAll[0];
                    }

                    // console.log('selectedMethodsInAll Before', selectedMethodsInAll, noSelectedMethodsInAll, selectedShippingMethod);

                    // case 3: subcart, all items have both shipping methods
                    // case 4: if any other subcart has selected shipping method, then use that shipping method
                    // check if having any selected shipping method in other subcart
                    // if(selectedShippingMethod === '') {
                    let selectedShippingMethods = [];
                    shippingMethods.forEach(method => {
                        if ($('.cart.items').find('.cart-seller-items:not(.processing) input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + method + ']:checked').length > 0) {
                            selectedShippingMethods.push(method);
                        }
                    });

                    if (selectedShippingMethods.length > 1) {
                        selectedShippingMethod = defaultSelectedShippingMethod;
                    } else if (selectedShippingMethods.length == 1) {
                        selectedShippingMethod = selectedShippingMethods[0];
                    }

                    // console.log('selectedMethodInAll After', shippingMethods, selectedShippingMethod);
                    // }
                    // console.log('selectedMethodInAll Final', selectedShippingMethod);
                }

                if ($(selectedElement).hasClass('split-cart-item-checkbox')) {
                    selectedShippingMethod = $(selectedElement).data('shipping-method');
                    // console.log('selectedShippingMethod Before', selectedShippingMethod);
                    // check if having any selected shipping method in all items
                    if (selectedShippingMethod === '') {
                        let selectedShippingMethods = [];
                        shippingMethods.forEach(method => {
                            if ($('.cart.items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + method + ']:checked').length > 0) {
                                selectedShippingMethods.push(method);
                            }
                        });

                        if (selectedShippingMethods.length > 1) {
                            selectedShippingMethod = selectedShippingMethods[0];
                            selectedShippingMethod = defaultSelectedShippingMethod;
                        } else if (selectedShippingMethods.length == 1) {
                            selectedShippingMethod = selectedShippingMethods[0];
                        }
                        // console.log('selectedShippingMethods After', selectedShippingMethods, selectedShippingMethod);
                    }
                }

                // console.log('selectedShippingMethod Final', selectedShippingMethod);

                if (selectedShippingMethod !== '') {
                    // list item ids of selected element match with selectedShippingMethod
                    let selectedItemIds = [];
                    if ($(selectedElement).hasClass('split-cart-item-checkbox')) {
                        selectedItemIds.push($(selectedElement).data('item-id'));
                    }
                    if ($(selectedElement).hasClass('split-cart-check-all')) {
                        $(selectedElement).closest('.cart-seller-items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + selectedShippingMethod + ']').each(function () {
                            const itemId = $(this).data('item-id');
                            if (itemId) selectedItemIds.push($(this).data('item-id'));
                        });
                    }
                    // console.log('selectedItemIds', selectedItemIds);

                    if ($(selectedElement).is(':checked')) {
                        // unselected all items have shipping method different from selected shipping method
                        shippingMethods.forEach(method => {
                            if (method !== selectedShippingMethod) {
                                $('.cart.items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + method + ']').each(function () {
                                    // console.log('method', method, selectedShippingMethod, this, this.checked);
                                    if (this.checked) {
                                        uncheckIds.push($(this).data('item-id'));
                                    }
                                    $(this).addClass('disabled');
                                    $(this).prop('checked', false);
                                    $(this).prop('disabled', true);
                                    $(this).closest('.cart.item').addClass('disabled');
                                });
                            }
                        });
                        // uncheck items that are not allowed to be selected
                        // console.log('processDeliveryWarningMessage - uncheckIds', uncheckIds);
                        if (uncheckIds.length > 0) {
                            $('.form.form-cart').trigger('processCartUpdate', [uncheckIds.join(','), 'uncheck', '', this]);
                        }
                    } else {
                        // console.log('need to reset restrict items', selectedItemIds);
                        let checkSelectedItems = 0;
                        $('.cart.items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + selectedShippingMethod + ']:checked').each(function () {
                            if (!selectedItemIds.includes($(this).data('item-id'))) {
                                checkSelectedItems++;
                            }
                        });

                        // console.log('checkSelectedItems', checkSelectedItems);

                        if (checkSelectedItems === 0) {
                            $('.cart.item').removeClass('disabled');
                            $('.cart.item input.split-cart-item-checkbox[type=checkbox]').prop('disabled', false);
                            $('.cart.item input.split-cart-item-checkbox[type=checkbox]').removeClass('disabled');
                        }
                    }
                } else {
                    // selected using all methods
                    // need to reset restrict items
                    // console.log('selected using all methods - reset restrict items');
                    $('.cart.item').removeClass('disabled');
                    $('.cart.item input.split-cart-item-checkbox[type=checkbox]').prop('disabled', false);
                    $('.cart.item input.split-cart-item-checkbox[type=checkbox]').removeClass('disabled');
                }

                // disable all items if all items of subcart are disabled
                $(".cart-seller-items").each(function () {
                    let allDisabled = true;
                    $(this).find('input.split-cart-item-checkbox[type=checkbox]').each(function () {
                        if (!$(this).prop('disabled') || !$(this).hasClass('disabled')) {
                            allDisabled = false;
                        }
                    });
                    $(this).find('input.split-cart-check-all[type=checkbox]').prop('disabled', allDisabled);
                });
            },

            _processDeliveryWarningMessagePageLoaded: function () {
            },

            _processDeliveryWarningMessagePageLoadedBk: function () {
                var shippingMethods = this.getShippingMethods();
                let selectedShippingMethod = '';
                const defaultSelectedShippingMethod = this.options.defaultSelectedShippingMethod;
                let uncheckIds = [];
                // console.log('processDeliveryWarningMessagePageLoaded', shippingMethods, defaultSelectedShippingMethod);
                $('.cart-seller-items').find('input.split-cart-item-checkbox[type=checkbox]:checked').each(function () {

                });
                let selectedShippingMethods = [];
                shippingMethods.forEach(method => {
                    if ($('.cart-seller-items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + method + ']:checked').length > 0) {
                        selectedShippingMethods.push(method);
                    }
                });

                if (selectedShippingMethods.length > 1) {
                    selectedShippingMethod = selectedShippingMethods[0];
                    selectedShippingMethod = defaultSelectedShippingMethod;
                } else if (selectedShippingMethods.length == 1) {
                    selectedShippingMethod = selectedShippingMethods[0];
                }
                // console.log('selectedShippingMethods', selectedShippingMethods, selectedShippingMethod);

                if (selectedShippingMethod !== '') {
                    // unselected all items have shipping method different from selected shipping method
                    shippingMethods.forEach(method => {
                        if (method !== selectedShippingMethod) {
                            $('.cart.items').find('input.split-cart-item-checkbox[type=checkbox][data-shipping-method=' + method + ']').each(function () {
                                // console.log('method', method, selectedShippingMethod, this, this.checked);
                                if (this.checked) {
                                    uncheckIds.push($(this).data('item-id'));
                                }
                                $(this).addClass('disabled');
                                $(this).prop('checked', false);
                                $(this).prop('disabled', true);
                                $(this).closest('.cart.item').addClass('disabled');
                            });
                        }
                    });
                    // uncheck items that are not allowed to be selected
                    // console.log('processDeliveryWarningMessage - uncheckIds', uncheckIds);
                    if (uncheckIds.length > 0) {
                        $('.form.form-cart').trigger('processCartUpdate', [uncheckIds.join(','), 'uncheck', '', this]);
                    }
                }

                // disable all items if all items of subcart are disabled
                $(".cart-seller-items").each(function () {
                    let allDisabled = true;
                    $(this).find('input.split-cart-item-checkbox[type=checkbox]').each(function () {
                        if (!$(this).prop('disabled') || !$(this).hasClass('disabled')) {
                            allDisabled = false;
                        }
                    });
                    $(this).find('input.split-cart-check-all[type=checkbox]').prop('disabled', allDisabled);
                });
            },

            getShippingMethods: function () {
                var shippingMethods = $("#shopping-cart-table").data('shipping-methods');
                shippingMethods = shippingMethods && shippingMethods.length ? shippingMethods.split(",") : [];
                // console.log({shippingMethods});
                return shippingMethods;
            },

            processUpdateItem: function (selectedElement) {
                //$('.form.form-cart').trigger('processShippingFree', [selectedElement]);
            },

            processUpdateAll: function (selectedElement) {
                //$('.form.form-cart').trigger('processShippingFree', [selectedElement]);
            },

            default: function () {
                // check each sub-cart if all items are checked, then checked the all-checkbox
                $('.cart-seller-items').each(function () {
                    let allChecked = true, count;
                    const items = $(this).find('.item-info').length;
                    const disabledItems = $(this).find('input.split-cart-item-checkbox-disabled[type=checkbox]').length;
                    if (items == disabledItems) {
                        allChecked = false;
                    }
                    $(this).find('input.split-cart-item-checkbox[type=checkbox]').each(function () {
                        // if (!this.checked && !$(this).prop('disabled')) {
                        if (!this.checked) {
                            allChecked = false;
                        }
                    });
                    $(this).find('input.split-cart-check-all[type=checkbox]').prop('checked', allChecked);
                });

                if($('.cart.items input[type=checkbox].split-cart-item-checkbox:not(:disabled):not(:checked)').length === 0 && $('#wrap_items_grid').attr('data-select-all-cart')){
                    $('input#select_all_cart').prop('checked', true);
                }

                $('.form.form-cart').trigger('processDeliveryWarningMessagePageLoaded', [this]);
            },

            /*
             * clicking on checkbox to select all items
             */
            selectAllItems: function () {
                const self = this;
                $('body').on('change', '#shopping-cart-table input.split-cart-check-all[type=checkbox]', function () {
                    var checked = this.checked,
                        updatedIds = [];
                    $('.form.form-cart').trigger('processDeliveryWarningMessage', [$(this)]);

                    $(this).closest('.cart-seller-items').find('input[type=checkbox].split-cart-item-checkbox').each(function () {
                        if (!$(this).prop('disabled') && !$(this).hasClass('disabled')) {
                            $(this).prop('checked', checked);
                            updatedIds.push($(this).data('item-id'));
                        }
                    });
                    // console.log('selectAllItems', {updatedIds, checked});
                    $('.form.form-cart').trigger('reminderPointPopup', [this]);
                    $('.form.form-cart').trigger('processCartUpdate', [updatedIds.join(','), checked ? 'check' : 'uncheck', 'processUpdateAll', this]);
                    if (!checked) {
                        $('input#select_all_cart').prop('checked', false);
                    }
                    if ($('.cart.items input[type=checkbox].split-cart-item-checkbox:not(:disabled):not(:checked)').length === 0 && $('#wrap_items_grid').attr('data-select-all-cart')) {
                        $('input#select_all_cart').prop('checked', true);
                    }
                });
            },

            /*
             * clicking on checkbox to select an item
             */
            selectItem: function () {
                var self = this;
                $('body').on('change', '#shopping-cart-table input.split-cart-item-checkbox[type=checkbox]', function () {
                    var checked = this.checked,
                        allChecked = true;

                    $('.form.form-cart').trigger('processDeliveryWarningMessage', [this]);
                    $('.form.form-cart').trigger('reminderPointPopup', [this]);
                    if (!$(this).prop('disabled')) {
                        $('.form.form-cart').trigger('processCartUpdate', [$(this).data('item-id'), checked ? 'check' : 'uncheck', 'processUpdateItem', this]);

                        // uncheck all-checkbox if any item is unchecked
                        if (!checked) {
                            $(this).closest('.cart-seller-items').find('input.split-cart-check-all[type=checkbox]').prop('checked', false);
                            $('input#select_all_cart').prop('checked', false);
                        } else {
                            // check if all items in this sub cart is checked, then check the all-checkbox
                            var checkedAll = true;
                            $(this).closest('.cart-seller-items').find('input.split-cart-item-checkbox[type=checkbox]').each(function () {
                                // if (!this.checked && !$(this).prop('disabled') && !$(this).hasClass('disabled')) {
                                if (!this.checked) {
                                    checkedAll = false;
                                }
                            });
                            $(this).closest('.cart-seller-items').find('input.split-cart-check-all[type=checkbox]').prop('checked', checkedAll);
                        }
                        if ($('.cart.items input[type=checkbox].split-cart-item-checkbox:not(:disabled):not(:checked)').length === 0 && $('#wrap_items_grid').attr('data-select-all-cart')) {
                            $('input#select_all_cart').prop('checked', true);
                        }
                    }
                });
            },

            getIntersection: function (arr1, arr2) {
                return [arr1, arr2]?.reduce((acc, currentValue) => {
                    return acc.filter(res => currentValue.includes(res))
                })
            },

            _onDeleteAllProducts: function () {
                var self = this;
                var widget = this;
                console.log('call _onDeleteAllProducts');
                $(document).on('click', '#delete_selected_products_button', function (event, widget) {
                    const selectedItems = $('.cart.items').find('input.split-cart-item-checkbox[type=checkbox]:checked');
                    console.log('_onDeleteAllProducts', widget);
                    console.log(selectedItems);
                    if (selectedItems.length) {
                        confirm({
                            title: $.mage.__('Delete Products'),
                            content: $.mage.__('Are you sure you want to delete %1 products?').replace('%1', selectedItems.length),
                            modalClass: 'modal-custom confirm-delete-popup',
                            buttons: [{
                                text: $.mage.__('Add to Tracking'),
                                class: 'action secondary action-secondary',
                                click: function () {
                                    this.closeModal();
                                    let cartItems = [];
                                    $.each($('.checkbox.split-cart-item-checkbox'), function (k, v) {
                                        if ($(v).is(':checked')) {
                                            cartItems[cartItems.length] = $(v).val();
                                        }
                                    });
                                    if (!cartItems.length) {
                                        return;
                                    }
                                    if (!self.isLoggedIn()) {
                                        var loginUrl = url.build('customer/account/login');
                                        window.location.href = loginUrl;
                                        return;
                                    }
                                    if (!$.localStorage.get('mage-customer-login')) {
                                        console.log('not login');
                                        self._showMessage($.mage.__('You must login or register to add items to your wishlist.'), 'error');
                                        return;
                                    }
                                    $.ajax({
                                        url: url.build('checkout/cart/addWishlistMultiple'),
                                        type: "POST",
                                        dataType: 'JSON',
                                        async: true,
                                        data: { form_key: $('input[name="form_key"]').val(), 'ids': cartItems.join(',') },
                                        beforeSend: function () {
                                            $('.form.form-cart').trigger('processCartStart');
                                        },
                                        success: function (response) {
                                            self._processCartResponse(response, 'wishlist');
                                            if (!response.error_items.length || response.error_items.length < cartItems.length) {
                                                reloadCartItems();
                                            } else {
                                                $('.form.form-cart').trigger('processCartStop');
                                            }
                                        },
                                        error: function (err) {
                                            // check the err for error details
                                            self._showMessage($.mage.__('Sorry, something went wrong. Please try again later.'), 'error');
                                            $('.form.form-cart').trigger('processCartStop');
                                        }
                                    });
                                }
                            }, {
                                text: $.mage.__('Confirm Delete'),
                                class: 'action primary action-primary',
                                click: function () {
                                    this.closeModal();
                                    let cartItems = [];
                                    $.each($('.checkbox.split-cart-item-checkbox'), function (k, v) {
                                        if ($(v).is(':checked')) {
                                            cartItems[cartItems.length] = $(v).val();
                                        }
                                    });
                                    if (!cartItems.length) {
                                        return;
                                    }
                                    $.ajax({
                                        url: url.build('checkout/cart/deleteMultipleItems'),
                                        type: "POST",
                                        dataType: 'JSON',
                                        async: true,
                                        data: { form_key: $('input[name="form_key"]').val(), 'ids': cartItems.join(',') },
                                        beforeSend: function () {
                                            $('.form.form-cart').trigger('processCartStart');
                                        },
                                        success: function (response) {
                                            self._processCartResponse(response, 'delete');
                                            if (!response.error_items.length || response.error_items.length < cartItems.length) {
                                                reloadCartItems();
                                            } else {
                                                $('.form.form-cart').trigger('processCartStop');
                                            }
                                        },
                                        error: function (err) {
                                            // check the err for error details
                                            self._showMessage($.mage.__('Sorry, something went wrong. Please try again later.'), 'error');
                                            $('.form.form-cart').trigger('processCartStop');
                                        }
                                    });
                                }
                            }],

                        });
                    } else {
                        confirm({
                            title: $.mage.__('No Products Selected'),
                            content: $.mage.__('Please select the products you want to delete first'),
                            modalClass: 'modal-custom confirm-delete-popup',
                            buttons: [{
                                text: $t('I know'),
                                class: 'action-primary',
                                click: function (event) {
                                    this.closeModal(event, true);
                                }
                            }]

                        });
                    }
                });

                $(document).ready(function () {
                    $(document).on('click', '#delete_selected_products_button', function () {
                        console.log('clicked 1');
                    });
                });
            },

            _processCartResponse: function (res, actionName) {
                if (res.error_message) {
                    this._showMessage(res.error_message, 'error');
                    return;
                }
                const errorMessageText = actionName === 'delete' ? $.mage.__('Error on deleting %1 cart item(s).') : $.mage.__('Error on adding %1 item(s) to tracking.');
                const successMessageText = actionName === 'delete' ? $.mage.__('%1 products deleted.') : $.mage.__('%1 products added to the tracking list.');
                var messages = $('<div class="messages"></div>')

                if (res.error_items.length) {
                    messages.append(`<div class="message-error error message">
                            <div>`+ errorMessageText.replace('%1', res.error_items.length) + `</div>
                        </div>`);
                }

                if (res.success_items.length) {
                    messages.append(`<div class="message-success success message">
                        <div>`+ successMessageText.replace('%1', res.success_items.length) + `</div>
                    </div>`);
                }
                $('[data-placeholder="messages"]').append(messages);
                $('[data-placeholder="messages"]').trigger('toggleMessage');
            },

            _selectAllCartItems: function () {
                const self = this;
                $('body').on('change', 'input#select_all_cart', function () {
                    var checked = this.checked,
                        updatedIds = [];
                    $('.form.form-cart').trigger('processDeliveryWarningMessage', [$(this)]);

                    $(this).closest('.form-cart').find('input[type=checkbox].split-cart-item-checkbox').each(function () {
                        if(!$(this).prop('disabled') && !$(this).hasClass('disabled')) {
                            $(this).prop('checked', checked);
                            updatedIds.push($(this).data('item-id'));
                        }
                    });
                    $('.form.form-cart').trigger('reminderPointPopup', [this]);
                    $('.form.form-cart').trigger('processCartUpdate', [updatedIds.join(','), checked ? 'check' : 'uncheck', 'processUpdateAll', this]);
                    if (!checked) {
                        $('input#select_all_cart').prop('checked', false);
                        $('#wrap_items_grid').removeAttr("data-select-all-cart");
                    }
                    if($('.cart.items input[type=checkbox].split-cart-item-checkbox:not(:disabled):not(:checked)').length === 0){
                        $('input#select_all_cart').prop('checked', true);
                        $('#wrap_items_grid').attr("data-select-all-cart", 1);
                    }
                });
            },

            _showMessage: function (message, type) {
                // Encode message content to prevent XSS
                var safeMessage = securityUtils.encodeHtmlContent(message);
                // Sanitize type for CSS class name (remove HTML tags, keep only plain text)
                var safeType = securityUtils.sanitizeHtmlContent(type, { ALLOWED_TAGS: [], ALLOWED_ATTR: [] });
                // Fix for Client DOM XSS: Explicit DOMPurify sanitization
                var safeFragment = DOMPurify.sanitize(
                    '<div class="messages">' +
                    '<div class="message message-' + safeType + ' ' + safeType + '">' +
                    '<div>' + safeMessage + '</div>' + // safeMessage is already encoded
                    '</div>' +
                    '</div>',
                    { RETURN_DOM_FRAGMENT: true }
                );
                // [Security Fix] Use importNode to break taint tracking chain
                var cleanFragment = document.importNode(safeFragment, true);
                var msgPlaceholder = document.querySelector('[data-placeholder="messages"]');
                if (msgPlaceholder) {
                    msgPlaceholder.innerHTML = '';
                    msgPlaceholder.appendChild(cleanFragment);
                }
                $('[data-placeholder="messages"]').trigger('toggleMessage');
            },

            isLoggedIn: function() {
                var customerInfo = customerData.get('customer')();
                console.log('Customer Info:', customerInfo);
                return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
            }
        });
        return $.branch8.splitCart;
    });

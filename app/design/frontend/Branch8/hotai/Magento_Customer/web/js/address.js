/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/widget',
    'mage/translate'
], function ($, modal) {
    'use strict';

    $.widget('mage.address', {
        /**
         * Options common to all instances of this widget.
         * @type {Object}
         */
        options: {
            deleteConfirmMessage: $.mage.__('是否確定刪除此常用地址?'),
            addressType: 'home'
        },

        /**
         * Bind event handlers for adding and deleting addresses.
         * @private
         */
        _create: function () {
            var options = this.options,
                addAddress = options.addAddress,
                deleteAddress = options.deleteAddress;
            // if (addAddress) {
            //     $(document).on('click', addAddress, this._addAddress.bind(this));
            // }

            if (deleteAddress) {
                $(document).on('click', deleteAddress, this._deleteAddress.bind(this));
            }
        },

        /**
         * Add a new address.
         * @private
        //  */
        // _addAddress: function () {
        //     window.location = this.options.addAddressLocation+'type/'+this.addressType;
        // },

        /**
         * Delete the address whose id is specified in a data attribute after confirmation from the user.
         * @private
         * @param {jQuery.Event} e
         * @return {Boolean}
         */
        _deleteAddress: function (e) {
            var self = this;
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: false,
                modalClass: 'delete-address-modal',
                title: $.mage.__('確定刪除'),
                buttons: [{
                    text: $.mage.__('取消'),
                    class: 'action secondary action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }, {
                    text: $.mage.__('確定'),
                    class: 'action primary action-primary',
                    click: function () {
                        if (typeof $(e.target).parent().data('address') !== 'undefined') {
                            window.location = self.options.deleteUrlPrefix + $(e.target).parent().data('address') +
                                '/form_key/' + $.mage.cookies.get('form_key');
                        } else {
                            window.location = self.options.deleteUrlPrefix + $(e.target).data('address') +
                                '/form_key/' + $.mage.cookies.get('form_key');
                        }
                        this.closeModal();
                    }
                }]
            };
            var x = modal(options, $('#delete-address-modal'));
            $('#delete-address-modal').modal('openModal');

            return false;
        }
    });

    return $.mage.address;
});

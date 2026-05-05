define([
    "jquery",
    "Magento_Ui/js/modal/modal",
    'mage/template'
], function ($, modal) {
    'use strict';

    /**
     * @param {String} url
     * @returns {jQuery}
     */
    function getForm(url) {
        return $('<form>', {
            'action': url,
            'method': 'POST'
        });
    }

    $.widget('mprma.rmaSwitchResolution', {
        modal: null,
        options: {
            form_key: '',
            currentResolutionType: '',
            submitUrl: '',
            rmaId: '',
            trigger: '[data-role="trigger-show-resolution-popup"]',
            wrapper: '[data-role="resolution-modal-wrapper"]'
        },
        /**
         *
         * @private
         */
        _create: function () {
            const self = this;
            $(this.options.trigger).on('click', this.click.bind(self));
        },
        /**
         * click
         */
        click: function () {
            if (this.modal === null) {
                this.getModal();
            }
            $(this.options.wrapper).modal('openModal');
        },
        /**
         * getModal
         */
        getModal: function () {
            const self = this;
            const options = {
                type: 'popup',
                responsive: true,
                title: $.mage.__('Change Resolution'),
                buttons: [{
                    text: $.mage.__('Save'),
                    class: '',
                    click: function () {
                        const form = getForm(self.options.submitUrl),
                            radios = $("input[name=\'resolutiontype[]\']").filter(":checked");
                        form.append($('<input>', {
                            'name': 'form_key',
                            'value': self.options.form_key,
                            'type': 'form_key'
                        })).append($('<input>', {
                            'name': 'rma_id',
                            'value': self.options.rmaId,
                            'type': 'hidden'
                        })).append($('<input>', {
                            'name': 'resolution',
                            'value': radios[0].value,
                            'type': 'hidden'
                        })).appendTo('body').trigger('submit');
                    }
                }]
            };
            this.modal = modal(options, $(this.options.wrapper));
        }
    });
    return $.mprma.rmaSwitchResolution;
});

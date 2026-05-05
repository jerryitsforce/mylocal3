define([
    'jquery',
    'jquery/ui',
    'Branch8_MarketPlaceParentOrderAdminUi/js/action/postCancelAction',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'mage/validation'
], function ($, ui, submitCancelOrder, $t, modal) {
    'use strict';
    let cancelModal;
    const wrapper = $('[data-role=\'cancel-form-modal\']'),
        cancelForm = $('[data-role=\'cancel-form\']');
    /**
     *
     */
    $.widget('mage.cancelParentOrder', {
        options: {
            trigger: '[data-ui-id=cancel-button]',
        },
        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            const self = this;
            this._super();
            submitCancelOrder.registerLoginCallback(function (postData, response) {
                console.log(response);
                window.location.reload();
            });
            $(this.options.trigger).off('click').on('click', function (event) {
                event.preventDefault();
                if (!cancelModal) {
                    cancelModal = self.getCancelModal();
                }
                wrapper.modal('openModal');
            })
        },
        /**
         *
         */
        submit: async function () {
            const valid = cancelForm.valid();
            if (valid) {
                const formDataArray = cancelForm.serializeArray();
                let postData = {},
                    action = cancelForm.attr('action');
                event.stopPropagation();
                formDataArray.forEach(function (entry) {
                    postData[entry.name] = entry.value;
                });
                postData['form_key'] = window.FORM_KEY;
                const res = await submitCancelOrder(action, postData);
                if (res && res.error) {
                    alert(res.error);
                    return;
                }
            }
        },
        /**
         *
         * @returns {*}
         */
        getCancelModal: function () {
            const self = this;
            if (!cancelModal) {
                const options = {
                    title: $.mage.__("Order Cancel Request"),
                    type: 'popup',
                    responsive: true,
                    buttons: [{
                        text: $t('Submit'),
                        class: 'action-primary action-accept',

                        /**
                         * Close modal and trigger 'confirm' action on click
                         */
                        click: self.submit.bind(self)
                    }],
                }
                cancelModal = modal(options, wrapper);
            }
            return cancelModal;
        }
    });
    return $.mage.cancelParentOrder;
});

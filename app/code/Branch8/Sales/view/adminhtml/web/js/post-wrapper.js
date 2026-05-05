define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    let cancelModal = null;
    const wrapper = $('[data-role=\'cancel-form-modal\']'),
        cancelForm = $('[data-role=\'cancel-form\']');
    const submitCancelOrder = (url, data) => {
        return $.ajax(url, {
            dataType: "json",
            url: url,
            type: "POST",
            data: data,
            showLoader: true
        }).then(function (response) {
            return response;
        });
    };

    function getCancelModal() {
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
                    click: async function (event) {
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
                                console.log({
                                    res: res
                                });
                                alert(res.error);
                                return;
                            }
                            window.location.reload();
                        }
                    }
                }],
            }
            cancelModal = modal(options, wrapper);
        }
        return cancelModal;
    }

    /**
     * @param {String} url
     * @returns {jQuery}
     */
    function getForm(url) {
        return $('<form>', {
            'action': url,
            'method': 'POST'
        }).append($('<input>', {
            'name': 'form_key',
            'value': window.FORM_KEY,
            'type': 'hidden'
        }));
    }

    $(document).on('click', '#order-view-cancel-button', function () {
        if (!cancelModal) {
            cancelModal = getCancelModal();
        }
        wrapper.modal('openModal');
        return false;
    });

    $(document).on('click', '#order-view-hold-button', function () {
        var url = $('#order-view-hold-button').data('url');

        getForm(url).appendTo('body').trigger('submit');
    });

    $(document).on('click', '#order-view-unhold-button', function () {
        var url = $('#order-view-unhold-button').data('url');

        getForm(url).appendTo('body').trigger('submit');
    });
});

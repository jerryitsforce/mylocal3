define([
    'jquery',
    'mage/translate',
    'hotaiToastMessage',
    'HotaiConnected_CheckoutManagement/js/shared/services/api/exception-auth'
], function ($, $t, toastMessage, exceptionAuthApi) {
    'use strict';

    function getSuccessMessage(status) {
        return status === 'approved'
            ? $.mage.__('Exception authorization approved successfully.')
            : $.mage.__('Exception authorization rejected successfully.');
    }

    function getFailureMessage(status) {
        return status === 'approved'
            ? $.mage.__('Failed to approve exception authorization.')
            : $.mage.__('Failed to reject exception authorization.');
    }

    return {
        /**
         * 批次更新例外授權狀態
         *
         * @param {'approved'|'rejected'} status
         * @param {Array<Object>} selectedData
         * @param {Array<string|number>} selectedIds
         * @param {Object} context
         */
        execute: function(status, selectedData, selectedIds, context) {
            if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
                toastMessage.warning($t('Please select at least one %1.', $t('Record')));
                return;
            }

            exceptionAuthApi.updateExceptionAuthStatus({
                status: status,
                ids: selectedIds
            }, {
                showSuccessMessage: false,
                showErrorMessage: false
            }).done(function(response) {
                if (response && response.success) {
                    toastMessage.success({
                        content: getSuccessMessage(status)
                    });

                    if (context && typeof context.updateTabulator === 'function') {
                        context.updateTabulator();
                    }
                } else {
                    toastMessage.error({
                        content: (response && response.message)
                            ? response.message
                            : getFailureMessage(status)
                    });
                }
            }).fail(function(error) {
                console.error('Batch status update failed', error);
                toastMessage.error({
                    content: getFailureMessage(status)
                });
            }).always(function() {
                if (context && typeof context.resetBatchActionSelect === 'function') {
                    context.resetBatchActionSelect();
                }
            });
        }
    };
});


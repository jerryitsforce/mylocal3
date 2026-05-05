define([
    'Magento_Ui/js/grid/massactions',
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (Massactions, $, confirm, alert, $t) {
    'use strict';

    return Massactions.extend({
        /**
         * Shows actions' confirmation window.
         *
         * @param {Object} action - Actions' data.
         * @param {Function} callback - Callback that will be
         *      invoked if action is confirmed.
         */
        _confirm: function (action, callback) {
            var confirmData = action.confirm,
                data = this.getSelections(),
                total = data.total ? data.total : 0,
                recordText = total === 1 ? $t('record') : $t('records'),
                confirmMessage = confirmData.message +
                    (data.showTotalRecords || data.showTotalRecords === undefined ?
                        ' (' + total + ' ' + recordText + ')'
                        : '');

            confirm({
                title: confirmData.title,
                content: confirmMessage,
                actions: {
                    confirm: callback
                }
            });
        }
    });
});

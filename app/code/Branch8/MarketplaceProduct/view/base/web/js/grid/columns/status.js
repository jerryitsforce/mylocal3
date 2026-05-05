define([
    'Magento_Ui/js/grid/columns/select'
], function (Column) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html'
        },
        getLabel: function (record) {
            let label = this._super(record);

            if (label !== '' && record.status >= 0 && record.status <= 3) {
                const severityClass = [
                    'grid-severity-minor',
                    'grid-severity-notice',
                    'grid-severity-critical',
                    'grid-severity-major'
                ][record.status];
                label = `<span class="${severityClass}"><span>${label}</span></span>`;
            }

            return label;
        }
    });
});

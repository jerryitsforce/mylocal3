define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'mage/translate'
], function (_, registry, Select, $t) {
    'use strict';

    return Select.extend({
        defaults: {
            skipValidation: false,
            imports: {
                teamOptions: '${ $.parentName }.team_id:teamOptions'
            }
        },
        /**
         *
         * @param value
         * @param field
         */
        filter: function (value, field) {
            if (!value) {
                this.value('');
                this.notice($t('"Team" field need be select to enable this field.'))
                this.setOptions(
                    [{'value': '', 'label': $t('Please Select Member')}]
                );
                this.disable(true);
                return;
            }
            this.enable(true);
            this.notice('');
            var result = this.teamOptions[value] || [];
            this.setOptions(result);
        }
    });
});

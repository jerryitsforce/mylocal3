require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
    ], function(validator, $){
        validator.addRule(
            'event-ticket-end-date-past',
            function (value) {
                if(!$('input[name="entity_id"]').val()) {
                    var curDateTime = new Date();
                    var endDateTime = new Date(value);
                    if(endDateTime < curDateTime){
                        return false;
                    }
                }

                return true;
            },
            $.mage.__('End date should be in the future.')
        );
        validator.addRule(
            'event-ticket-end-date-before-start-date',
            function (value) {
                var startDateTime = new Date($('input[name="start_date"]').datepicker('getDate'));
                var endDateTime = new Date(value);
                if (endDateTime < startDateTime) {
                    return false;
                }
                return true;
            },
            $.mage.__('End date should be greater than Start date.')
        );
        let exchangeUrlField = 'input[name="exchange_url"]';
        let isOfflineOperationField = 'select[name="is_offline_operation"]';
        jQuery(document).ajaxComplete(function() {
            $('body').on('change', exchangeUrlField, function() {
                if (!$(this).val() || !$(this).val().trim()) {
                    $(isOfflineOperationField).find('option[value="1"]').prop('disabled', false);
                } else {
                    $(isOfflineOperationField).find('option[value="1"]').prop('disabled', true);
                }
            });
        });
    });
define([
    'jquery',
    'moment'
], function($, moment) {
    'use strict';

    return function(targetWidget) {

        /**
         * Validate date format in YYYY/MM/DD
         */
        $.validator.addMethod(
            'validate-date-format',
            function(value) {
                if(_.isEmpty(value)) {
                    return true;
                }

                const regex = /^\d{4}\/\d{2}\/\d{2}$/; // YYYY/MM/DD

                return regex.test(value);
            },
            $.mage.__('Please enter a valid date in the format YYYY/MM/DD.')
        );

        /**
         * Validate date range in days
         * @param value
         * @param element
         * @param params - object with keys: toDate, maxDays
         */
        $.validator.addMethod(
            'validate-date-range-days',
            function(value, element, params) {


                const toDateElement = $(params.toDate)

                if(_.isEmpty(value) || _.isEmpty(toDateElement.val())) {
                    return true;
                }

                const fromDate = new Date(value);
                const toDate = new Date(toDateElement.val());
                const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.ceil((toDate - fromDate) / (oneDay));

                const result = isNaN(diffDays) || diffDays <= parseInt(params.maxDays);
                if(!result) {
                    this.validateMessage = $.mage.__('僅可選擇%1天的區間.').replace('%1', params.maxDays);
                }
                return result
            },
            function () {
                return this.validateMessage;
            }
        );


        $.validator.addMethod(
            'validate-date-range-custom',
            function (value, elm, params) {
                var toDate = $(`#${params}`).val();
                console.log('fromDate', value);
                console.log('toDate', toDate);
                const fromDateUnix =  new Date(value).getTime();
                const toDateUnix = new Date(toDate).getTime();

                return _.isEmpty(value) || _.isEmpty(toDate) || fromDateUnix <= toDateUnix ;
            },
            $.mage.__('Make sure the To Date is later than or the same as the From Date.')
        );



        return targetWidget;
    }
});

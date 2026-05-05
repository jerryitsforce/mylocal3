define([
    'jquery'
], function ($) {
    'use strict';

    return function (target) {
        $.validator.addMethod(
            'ars-end-date', 
            function (value, element) {
                let arsEndDate = new Date(value);
                let arsStartDate = new Date($('#event_start_date').val());
                return arsStartDate < arsEndDate;
            },
            $.mage.__('The End time must be later than the Start time.')
        );

        $.validator.addMethod(
            'custom-noti-title-validate', 
            function (value, element) {
                return parseInt($('#cnt_title').html(), 10) <= 100;
            },
            $.mage.__('Notification title should be less than 100 characters.')
        );

        $.validator.addMethod(
            'custom-noti-content-validate', 
            function (value, element) {
                return parseInt($('#cnt_content').html(), 10) <= 500;
            },
            $.mage.__('Notification content should be less than 500 characters.')
        );
        $.validator.addMethod(
            'issue-reward-validate', 
            function (value, element) {
                var validateIssueRewardResult = false;
                $.each($('.issue-reward-validate'), function(ind, elm){
                    if($(elm).val() == 1){
                        validateIssueRewardResult = true;
                    }
                });
                return validateIssueRewardResult;
            },
            $.mage.__('There must be at least one trigger that issues a reward.')
        );
        return target;
    };
});
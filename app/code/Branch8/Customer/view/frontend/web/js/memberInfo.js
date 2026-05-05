define([
    'jquery',
    'jquery/ui',
    'matchMedia',
    'mage/validation',
    'domReady!'
], function ($) {
    $.widget('b8.memberInfo', {
        options: {},

        _create: function () {
            $('#nickname').on('input change', function () {
                const validate = $.validator.validateSingleElement($(this));
                if (validate) {
                    $('.member-info .actions-toolbar .action').attr('disabled', false);
                } else {
                    if(validate && $(this).parents('.nickname-form').length) {
                        $('.member-info .actions-toolbar .action').attr('disabled', false);
                        window.sessionStorage.setItem('accInfoInputChanged', 'name');
                    } else {
                        window.sessionStorage.removeItem('accInfoInputChanged');
                        $('.member-info .actions-toolbar .action').attr('disabled', true);
                    }
                }
            });

            $('#invoice-carrier').on('input change', function () {
                const $actionButtons = $('.member-info .actions-toolbar .action');
                const isValid = $.validator.validateSingleElement($(this));
                if(isValid) {
                    window.sessionStorage.setItem('accInfoInputChanged', 'invoice');
                } else {
                    window.sessionStorage.removeItem('accInfoInputChanged');
                }
                $actionButtons.prop('disabled', !isValid);
            });
        }
    });

    return $.b8.memberInfo;
});

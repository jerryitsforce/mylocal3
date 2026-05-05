define([
    "jquery",
    'mage/translate'
], function ($, $t) {
    'use strict';
    $.widget('mage.spintriggerwidget', {
        options: {
        },
        _create: function () {
            var self = this;
            console.log('spin trigger widget initialized');
            $('#visibility_wheel_depended').on('change', function() {
                if (!parseInt($(this).val())) {
                    $('#visibility_wheel').closest('.control').hide();
                    $('#visibility_wheel').removeClass('required-entry');
                } else {
                    $('#visibility_wheel').closest('.control').show();
                    $('#visibility_wheel').addClass('required-entry');
                }
            });
            $('#visibility_wheel_depended').trigger('change');
            $('#visibility_button_depended').on('change', function() {
                if (!parseInt($(this).val())) {
                    $('#visibility_button').closest('.control').hide();
                    $('#visibility_button').removeClass('required-entry');
                } else {
                    $('#visibility_button').closest('.control').show();
                    $('#visibility_button').addClass('required-entry');
                }
            });
            $('#visibility_button_depended').trigger('change');
            $('#visibility_events').on('change', function() {
                if ($(this).val()=='immediate' || $(this).val()=='exit' || $(this).val()=='') {
                    $('#visibility_events_depended').closest('.control').hide();
                    $('#visibility_events_depended').removeClass('required-entry');
                } else {
                    $('#visibility_events_depended').closest('.control').show();
                    $('#visibility_events_depended').addClass('required-entry');
                }
            });
            $('#visibility_events').trigger('change');
            $('#layout_trigger_button_position').on('change', function() {
                var positionVal = $(this).val().split("-");
                if(positionVal[0]=='top') {
                    $('.spin-buttonform-button-preview').css('top','0px');
                    $('.spin-couponform-button-preview').css('top','0px');
                } else if(positionVal[0]=='middle') {
                    $('.spin-buttonform-button-preview').css('top','calc(50% - 20px)');
                    $('.spin-couponform-button-preview').css('top','calc(50% - 20px)');
                } else {
                    $('.spin-buttonform-button-preview').css('top','calc(100% - 40px)');
                    $('.spin-couponform-button-preview').css('top','calc(100% - 40px)');
                }
                if(positionVal[1]=='left') {
                    $('.spin-buttonform-button-preview').css('right','');
                    $('.spin-buttonform-button-preview').css('left','0px');
                    $('.spin-couponform-button-preview').css('right','');
                    $('.spin-couponform-button-preview').css('left','0px');
                } else {
                    $('.spin-buttonform-button-preview').css('left','');
                    $('.spin-buttonform-button-preview').css('right','0px');
                    $('.spin-couponform-button-preview').css('left','');
                    $('.spin-couponform-button-preview').css('right','0px');
                }
            });
            setTimeout(function() {
                $('#layout_trigger_button_position').trigger('change');
                $('#spinbuttonformedit input:not([type=file]), #spinbuttonformedit select, #spinbuttonformedit textarea').trigger('change');
                $('#spincouponformedit input:not([type=file]), #spincouponformedit select, #spincouponformedit textarea').trigger('change');
                $('form.spin-form').find('.spin-save .admin__page-nav-item-message').addClass('_changed');
            }, 500);
            $('#spinbuttonformedit').on('spinAjaxComplete', function(e, data) {
                $('#buttonform_image_hidden').val(data.image);
            });
            $('.spin-buttonform-button-image').on('click', function() {
                $(this).closest('.admin__field').find('img.spin-image-preview').attr('src',  $(this).attr('src'));
                var input = $('#buttonform_image');
                $('#'+$(input).data('upload-id')).val($(this).data('spin-src'));
                $('.'+$(input).data('spin-bind-image')).attr('src', $(this).attr('src'));
            });
            $('input[name="display_scope"]').on('change', function() {
                if($(this).is(':visible') && $(this).val() == 2) {
                    $('#display_scope_home_special_input').show();
                } else if($(this).is(':visible')) {
                    $('#display_scope_home_special_input').hide();
                }
            });
            $('#buttonform_show').on('change', function() {
                if(parseInt($(this).val())) {
                    $(this).parents('.admin__fieldset').removeClass('hide').addClass('show');
                    $('.spin-buttonform-button-preview').show();
                    $('#buttonform_label').closest('.admin__field.field').show();
                    $('#buttonform_label').addClass('required-entry');
                    $('#buttonform_background_color').closest('.admin__field.field').show();
                    $('#buttonform_background_color').addClass('required-entry');
                    $('#buttonform_text_color').closest('.admin__field.field').show();
                    $('#buttonform_text_color').addClass('required-entry');
                    $('#buttonform_image').closest('.admin__field.field').show();
                } else {
                    $(this).parents('.admin__fieldset').removeClass('show').addClass('hide');
                    $('.spin-buttonform-button-preview').hide();
                    $('#buttonform_label').closest('.admin__field.field').hide();
                    $('#buttonform_label').removeClass('required-entry');
                    $('#buttonform_background_color').closest('.admin__field.field').hide();
                    $('#buttonform_background_color').removeClass('required-entry');
                    $('#buttonform_text_color').closest('.admin__field.field').hide();
                    $('#buttonform_text_color').removeClass('required-entry');
                    $('#buttonform_image').closest('.admin__field.field').hide();
                }
            });
            $('#couponform_show').on('change', function() {
                if(parseInt($(this).val())) {
                    $('.spin-couponform-button-preview').show();
                    $('#couponform_label').closest('.admin__field.field').show();
                    $('#couponform_label').addClass('required-entry');
                    $('#couponform_background_color').closest('.admin__field.field').show();
                    $('#couponform_background_color').addClass('required-entry');
                    $('#couponform_text_color').closest('.admin__field.field').show();
                    $('#couponform_text_color').addClass('required-entry');
                } else {
                    $('.spin-couponform-button-preview').hide();
                    $('#couponform_label').closest('.admin__field.field').hide();
                    $('#couponform_label').removeClass('required-entry');
                    $('#couponform_background_color').closest('.admin__field.field').hide();
                    $('#couponform_background_color').removeClass('required-entry');
                    $('#couponform_text_color').closest('.admin__field.field').hide();
                    $('#couponform_text_color').removeClass('required-entry');
                }
            });
        }
    });
    return $.mage.spintriggerwidget;
});

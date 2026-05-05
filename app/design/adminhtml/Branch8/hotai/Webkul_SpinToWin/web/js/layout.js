define([
    "jquery",
    'mage/translate'
], function ($, $t) {
    'use strict';
    $.widget('mage.spinlayoutwidget', {
        options: {
        },
        _create: function () {
            var self = this;
            $('#layout_view').on('change', function() {
                var layoutView = $(this).val();
                if (layoutView=='popup') {
                    $('#layout_position').closest('.admin__field').hide();
                    $('#page_url').closest('.admin__field').hide();
                    $('#page_url').attr('disabled', 'disabled');
                    $('#page_title').closest('.admin__field').hide();
                    $('#page_title').attr('disabled', 'disabled');
                } else if(layoutView == 'slide'){
                    $('#layout_position').closest('.admin__field').show();
                    $('#page_url').closest('.admin__field').hide();
                    $('#page_url').attr('disabled', 'disabled');
                    $('#page_title').closest('.admin__field').hide();
                    $('#page_title').attr('disabled', 'disabled');
                }else if(layoutView == 'page'){
                    $('#layout_position').closest('.admin__field').hide();
                    $('#page_url').closest('.admin__field').show();
                    $('#page_url').removeAttr('disabled');
                    $('#page_title').closest('.admin__field').show();
                    $('#page_title').removeAttr('disabled');
                }
            });
            $('#layout_view').trigger('change');

            $('.spin-color-input').on('blur', function(){
                $(this).parent().find('.color-pick.spin-color-box').css({"background-color":$(this).val()});
            });
        }
    });
    return $.mage.spinlayoutwidget;
});

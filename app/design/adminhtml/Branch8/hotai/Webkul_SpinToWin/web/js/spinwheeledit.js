define([
    "jquery",
    'mage/translate',
    "mage/template",
    'Magento_Ui/js/modal/alert',
    "spinwheelmain",
    "colorpicker",
    "jquery/ui",
    "plugins/DOMPurify"
], function ($, $t, mageTemplate, alert, spinwheelmain, colorpicker, jqueryUi, DOMPurify) {
    'use strict';
    $.widget('mage.spinwheeleditwidget', {
        options: {
        },
        _create: function () {
            var self = this;
            function updateColor() {
                setTimeout(function () {
                    $('#wheel_inner_wheel').trigger('change');
                    $('form.spin-form').find('.spin-save .admin__page-nav-item-message').addClass('_changed');
                }, 500);
                $('.spin-color-picker').each(function (ind, val) {
                    var options = $(val).data('spin-color-picker').colorPickerFunction;
                    if (options.getActiveColorPickerStatus) {
                        var thisElement = val;
                        $(thisElement).css(
                            'background-color',
                            '#' + "'" + options.spanBackgroundColor + "'"
                        );
                        $(thisElement).ColorPicker({
                            color: "'" + options.spanBackgroundColor + "'",
                            onShow: function (colorPicker) {
                                $(colorPicker).fadeIn(options.fadeInSpeed);
                                return false;
                            },
                            onHide: function (colorPicker) {
                                $(colorPicker).fadeOut(options.fadeOutSpeed);
                                return false;
                            },
                            onChange: function (hsb, hex, rgb) {
                                $(options.backgroundWidthSelector).val('#' + hex);
                                $(options.backgroundWidthSelector).trigger('change');
                                $(thisElement).css('background-color', '#' + hex);
                            }
                        });
                    }
                });
            }

            function updateColorLast() {
                $('.spin-segment-container').last().find('.spin-color-picker').each(function (ind, val) {
                    var options = $(val).data('spin-color-picker').colorPickerFunction;
                    if (options.getActiveColorPickerStatus) {
                        var thisElement = val;
                        $(thisElement).css(
                            'background-color',
                            '#' + "'" + options.spanBackgroundColor + "'"
                        );
                        $(thisElement).ColorPicker({
                            color: "'" + options.spanBackgroundColor + "'",
                            onShow: function (colorPicker) {
                                $(colorPicker).fadeIn(options.fadeInSpeed);
                                return false;
                            },
                            onHide: function (colorPicker) {
                                $(colorPicker).fadeOut(options.fadeOutSpeed);
                                return false;
                            },
                            onChange: function (hsb, hex, rgb) {
                                $(options.backgroundWidthSelector).val('#' + hex);
                                $(options.backgroundWidthSelector).trigger('change');
                                $(thisElement).css('background-color', '#' + hex);
                            }
                        });
                    }
                });
            }

            function templateCreate(backgroundvalue = '#260C47', textvalue = '#FFFFFF') {
                var template = mageTemplate('#spin-segment-template');
                var random = Math.floor((Math.random() * 1000) + 1);
                var backgroundid = 'backgroundid' + random;
                var textid = 'textid' + random;
                var newField = template({
                    data: {
                        backgroundid: backgroundid,
                        backgroundvalue: backgroundvalue,
                        textid: textid,
                        textvalue: textvalue,
                    }
                });
                $('#spinwheelform-addsegment-data').append(newField);
            }

            function updateSegmentsValue() {
                var segmentsValue = [];
                $('.spin-segment-container').each(function (ind, val) {
                    segmentsValue.push([$(val).find('.background_color').val(), $(val).find('.text_color').val()]);
                });
                $('#wheel_segments').val(JSON.stringify(segmentsValue));
            }
            $('#spinwheelform-addsegment-data').on('change', '.spin-color-input', function () {
                updateSegmentsValue();
            });
            $('#spinwheelform-addsegment').on('click', function () {
                templateCreate();
                updateColorLast();
                updateSegmentsValue();
            });
            try {
                var wheel_segments_val = JSON.parse($('#wheel_segments').val());
            } catch (e) {
                var wheel_segments_val = [];
            }
            $.each(wheel_segments_val, function (ind, val) {
                templateCreate(val[0], val[1]);
            });
            $('body').on('click', '.spin-segment-delete', function () {
                $(this).closest('.spin-segment-container').remove();
                updateSegmentsValue();
            });
            updateColor();
            $('#spinwheelformedit').on('spinAjaxComplete', function (e, data) {
                $('#wheel_background_image_hidden').val(data['background_image']);
                $('#wheel_center_image_hidden').val(data['center_image']);
                $('#wheel_pin_image_hidden').val(data['pin_image']);
            });
            $('body').on('change', '#wheel_inner_wheel', function () {
                if (parseInt($('#wheel_inner_wheel').val())) {
                    $('#wheel_inner_radius').addClass('required-entry');
                    $('#wheel_center_color').addClass('required-entry');
                    $('#wheel_inner_radius').closest('.admin__field').show();
                    $('#wheel_center_color').closest('.admin__field').show();
                    $('#wheel_center_image').closest('.admin__field').show();
                } else {
                    $('#wheel_inner_radius').removeClass('required-entry');
                    $('#wheel_center_color').removeClass('required-entry');
                    $('#wheel_inner_radius').closest('.admin__field').hide();
                    $('#wheel_center_color').closest('.admin__field').hide();
                    $('#wheel_center_image').closest('.admin__field').hide();
                }
            })
            $('.spin-wheel-pin-image').on('click', function () {
                $(this).closest('.admin__field').find('img.spin-image-preview').attr('src', $(this).attr('src'));
                var input = $('#wheel_pin_image');
                $('#' + $(input).data('upload-id')).val($(this).data('spin-src'));
                $('.' + $(input).data('spin-bind-image')).attr('src', $(this).attr('src'));
            });
            $('body').on('spinModalClosed', function () {
                $('#spinwheel-preview-button').removeAttr('disabled');
            });
            $('#spinwheel-preview-button').on('click', function () {
                $('#spinwheel-preview-button').attr('disabled', 'disabled');
                $.ajax({
                    type: "POST",
                    url: self.options.get,
                    data: { 'spin-id': self.options.spinId },
                    dataType: "json",
                    success: function (response) {
                        if (parseInt(response.success)) {
                            if (response.data !== undefined && response.data) {
                                $.ajax({
                                    type: "POST",
                                    url: self.options.previewUrl,
                                    data: { 'current_url': { 'current_url': location.href } },
                                    dataType: "json",
                                    cache: false,
                                    success: function (responsepopup) {
                                        if (responsepopup.success) {
                                            spinwheelmain.drawWheel('admin', response.data, responsepopup.data);
                                        }
                                    },
                                    error: function (responsepopup) {
                                    }
                                });
                            }
                        } else {
                            $('#spinwheel-preview-button').removeAttr('disabled');
                            alert({
                                title: $t('Error'),
                                content: $t(DOMPurify.sanitize(response.message))
                            });
                        }
                    }
                });
            });
        }
    });
    return $.mage.spinwheeleditwidget;
});

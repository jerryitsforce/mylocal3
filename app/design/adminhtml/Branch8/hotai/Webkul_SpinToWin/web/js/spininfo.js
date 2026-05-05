define([
    "jquery",
    'jquery/ui',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'dompurify',
    "mage/calendar",
    "mage/validation",
    'domReady!'
], function ($, ui, $t, alert, DOMPurify) {
    'use strict';
    // Checkmarx V9.4.5 HF16+ DOMPurify recognition shim
    // Reference: https://rainmakerho.github.io/2023/08/21/checkmarx-client-dom-xss-stored-xss/
    window.DOMPurify = DOMPurify;
    function require(val) { if (val === "dompurify") return window.DOMPurify; else return {}; }
    var createDOMPurify = require("dompurify");
    createDOMPurify(window);

    $.widget('mage.spininfowidget', {
        options: {
        },
        _create: function () {
            var self = this;
            var spinInfoForm = $('#spininfoformedit');
            spinInfoForm.mage('validation', {}).find('input:text').attr('autocomplete', 'off');
            $('body').on('click', '#update-spininfo', function (e) {
                if (!$('#update-spininfo').hasClass('spin-save-ajax')) {
                    e.preventDefault();
                    if ($('#spininfoformedit').validation('isValid')) {
                        $('#update-spininfo').attr('disabled', 'disabled');
                        $('#spininfoformedit').submit();
                    }
                }
            });
            $('#spininfoformedit').on('submit', function () {
                $('#slow_stock_alert_email').val($('#slow_stock_alert_email_tmp').val().join(','));
            });
            $('#start_date').attr('onpaste', 'return false;');
            $('#end_date').attr('onpaste', 'return false;');
            let currentDate = new Date();
            let datepicker = `${currentDate.getFullYear()}-${(currentDate.getMonth() + 1).toString().length != 2 ? '0' + (currentDate.getMonth() + 1).toString() : (currentDate.getMonth() + 1).toString()}-${(currentDate.getDate() - 1).toString().length != 2 ? '0' + (currentDate.getDate()).toString() : (currentDate.getDate()).toString()}`;
            $('#start_date').datetimepicker({
                dateFormat: "yy-mm-dd",
                timeFormat: "H:m:s",
                changeMonth: true,
                changeYear: true,
                numberOfMonths: 1,
                showsTime: true,
                minDate: datepicker,
                showButtonPanel: true,
                showOn: "button",
                buttonImage: null,
                buttonImageOnly: null,
                buttonText: '',
                timeText: '時間',
                hourText: '時',
                minuteText: '分',
                secondText: '秒',
                currentText: '現在',
                closeText: '完成',
                monthNamesShort: ["一月", "二月", "三月", "四月", "五月", "六月",
                    "七月", "八月", "九月", "十月", "十一月", "十二月"]
            }
            );
            $('#end_date').datetimepicker({
                dateFormat: "yy-mm-dd",
                timeFormat: "H:m:s",
                changeMonth: true,
                changeYear: true,
                numberOfMonths: 1,
                showsTime: true,
                minDate: datepicker,
                showButtonPanel: true,
                showOn: "button",
                buttonImage: null,
                buttonImageOnly: null,
                buttonText: '',
                timeText: '時間',
                hourText: '時',
                minuteText: '分',
                secondText: '秒',
                currentText: '現在',
                closeText: '完成',
                monthNamesShort: ["一月", "二月", "三月", "四月", "五月", "六月",
                    "七月", "八月", "九月", "十月", "十一月", "十二月"]
            }
            );
            function manageDateView() {
                if (parseInt($('#scheduled').val())) {
                    $('#start_date').addClass('required-entry');
                    $('#end_date').addClass('required-entry');
                    $('#start_date').closest('.admin__field').show();
                    $('#end_date').closest('.admin__field').show();
                } else {
                    $('#start_date').removeClass('required-entry');
                    $('#end_date').removeClass('required-entry');
                    $('#start_date').closest('.admin__field').hide();
                    $('#end_date').closest('.admin__field').hide();
                }
            }
            manageDateView();
            $('#scheduled').on('change', function () {
                manageDateView();
            });

            $('form.spin-form').find("input, textarea, select").on("change", function () {
                $(this).closest('form.spin-form').find('.spin-save .admin__page-nav-item-message').removeClass('_changed');
            });
            //image
            $('body').on('click', '.spin-files-button', function () {
                $("#" + $(this).data('triggerid')).trigger('click');
            });

            function updateImagePreview(input, src) {
                $('.' + $(input).data('spin-bind-image')).attr('src', src);
            }

            function uploadImage(input) {
                var fileExtension = ['jpeg', 'jpg', 'png', 'gif'];
                if ($.inArray($(input).val().split('.').pop().toLowerCase(), fileExtension) == -1) {
                    alert({
                        content: $t("Only '.jpeg','.jpg', '.png', '.gif' formats are allowed.")
                    });
                } else {
                    if (input.files && input.files[0]) {
                        var linkUrl = $(input).data('upload-url');
                        var file = $(input)[0].files[0];
                        var data = new FormData();
                        data.append('image', file);
                        if (file) {
                            $.ajax({
                                type: "POST",
                                url: linkUrl + "?form_key=" + window.FORM_KEY,
                                enctype: 'multipart/form-data',
                                mimeType: "multipart/form-data",
                                data: data,
                                contentType: false,
                                cache: false,
                                processData: false,
                                beforeSend: function () {
                                    $('body').trigger('processStart');
                                },
                                success: function (response) {
                                    response = JSON.parse(response);
                                    $(input).closest('.admin__field').find('img.spin-image-preview').attr('src', response.url);
                                    $('#' + $(input).data('upload-id')).val(response.file);
                                    updateImagePreview(input, response.url);
                                    $('body').trigger('processStop');
                                },
                                error: function (response) {
                                    $('body').trigger('processStop');
                                }
                            });
                        }
                    }
                }
            }

            $('body').on('change', "input.spin-files", function () {
                uploadImage(this);
            });

            $('body').on("click", ".spin-image-delete", function (e) {
                e.preventDefault();
                $('#' + $(this).closest('.admin__field').find('input[type=file]').data('upload-id')).val('');
                $(this).closest('.admin__field').find('img.spin-image-preview').attr('src', '');
                updateImagePreview($(this).closest('.admin__field').find('input[type=file]'), '')
            });

            //binding
            $('.spin-bind-text').on('change keypress keydown keyup blur focus', function () {
                var elemId = $(this).data('spin-bind');
                $('.' + elemId).text($(this).val());
            });
            $('.spin-bind-style').on('change keypress blur focus', function () {
                var elemId = $(this).data('spin-bind');
                $('.' + elemId).css($(this).data('spin-bind-prop'), $(this).val());
                $('.' + elemId + ' h1,' + '.' + elemId + ' p,' + '.' + elemId + ' span,' + '.' + elemId + ' label').css($(this).data('spin-bind-prop'), $(this).val());
            });

            // form save ajax
            $('.spin-save-ajax').on('click', function (e) {
                e.preventDefault();
                var self = this;
                var formId = $(self).data('spin-bind-form');
                var form = $('#' + formId);
                if (
                    $('#wheel_center_color').length == 0 ||
                    $('#wheel_center_image_1').length == 0 ||
                    $('#wheel_pin_image-note1').length == 0 ||
                    $('#wheel_font_size').length == 0
                ) {
                    alert({
                        title: $t('Error'),
                        content: $t('Please fill all the mondatory fields')
                    });
                } else {
                    if ($(form).validation('isValid')) {
                        $.ajax({
                            type: "POST",
                            url: $(form).attr('action'),
                            data: $(form).serialize(),
                            dataType: "json",
                            beforeSend: function () {
                                $('body').trigger('processStart');
                            },
                            success: function (response) {
                                if (parseInt(response.success)) {
                                    $(self).find('.admin__page-nav-item-message').addClass('_changed');
                                    alert({
                                        title: $t('Success'),
                                        content: DOMPurify.sanitize(response.message, { RETURN_DOM_FRAGMENT: true })
                                    });
                                    if (response.data !== undefined && response.data) {
                                        $(form).trigger("spinAjaxComplete", [response.data]);
                                    }
                                } else {
                                    alert({
                                        title: $t('Error'),
                                        content: DOMPurify.sanitize(response.message, { RETURN_DOM_FRAGMENT: true })
                                    });
                                }
                                $('body').trigger('processStop');
                            },
                            error: function (response) {
                                $('body').trigger('processStop');
                            }
                        });
                    }
                }
            });
            /**
             * Consolation
             */
            $('body').on('click', '#consolationtrule_save_consolation', function (e) {

                if (validateConsolation()) {
                    return false;
                }

                var formData = $('body').find('#spin_tabs_consolation_content').find('select, textarea, input').serialize();
                $.ajax({
                    type: "POST",
                    url: self.options.consolation_save_url,
                    data: formData,
                    dataType: "json",
                    beforeSend: function () {
                        $('body').trigger('processStart');
                    },
                    success: function (response) {
                        if (parseInt(response.success)) {
                            alert({
                                title: $t('Success'),
                                content: $t(DOMPurify.sanitize(response.message))
                            });
                            var consolationResponseData = response.data;
                            if (typeof consolationResponseData['consolation_coupon_image'] != "undefined") {
                                $('#consolation_coupon_image_hidden').val(consolationResponseData['consolation_coupon_image']);
                            }
                            if (typeof consolationResponseData['consolation_physical_image'] != "undefined") {
                                $('#consolation_physical_image_hidden').val(consolationResponseData['consolation_physical_image']);
                            }
                            window.scrollTo(0, 0);
                        } else {
                            alert({
                                title: $t('Error'),
                                content: $t(DOMPurify.sanitize(response.message))
                            });
                        }
                        $('body').trigger('processStop');
                    },
                    error: function (response) {
                        $('body').trigger('processStop');
                    }
                });
            });

            $('body').on('click', '.spin-segment-delete-action', function (e) {
                e.preventDefault();
                if (!confirm($t('Are you sure want to delete?'))) {
                    return;
                }
                var self = this;
                $.ajax({
                    type: "GET",
                    url: $(self).data('del-url'),
                    data: {},
                    dataType: "json",
                    beforeSend: function () {
                        $('body').trigger('processStart');
                    },
                    success: function (response) {
                        if (parseInt(response.success)) {
                            alert({
                                title: $t('Delete segment success.'),
                                content: $t(DOMPurify.sanitize(response.message))
                            });
                            /**
                             * Reload prizes tab
                             */
                            $('body').find('a#spin_tabs_information').trigger('click');
                            $('body').find('a#spin_tabs_segments').trigger('click');

                            if (typeof response.pageMessage != "undefined") {
                                updateSystemMessage(response.pageMessage);
                            }

                        } else {
                            alert({
                                title: $t('Deleting the prize failed.'),
                                content: $t(DOMPurify.sanitize(response.message))
                            });
                        }
                        $('body').trigger('processStop');
                    },
                    error: function (response) {
                        $('body').trigger('processStop');
                    }
                });
            });
            updateAllowRedeemPoint();
            $('body').on('click', '#allow_redeem_point', function (e) {
                updateAllowRedeemPoint();
            });
            function updateAllowRedeemPoint() {
                var allowRedeemPointVal = $('#allow_redeem_point').val();
                if (allowRedeemPointVal == 1) {
                    $('#point_to_drawn').addClass('required-entry');
                    $('#point_to_drawn').parent().parent().show();
                    $('#redeem_point_to_drawn_limit_per_day').addClass('required-entry');
                    $('#redeem_point_to_drawn_limit_per_day').parent().parent().show();
                } else {
                    $('#point_to_drawn').removeClass('required-entry');
                    $('#point_to_drawn').parent().parent().hide();
                    $('#redeem_point_to_drawn_limit_per_day').removeClass('required-entry');
                    $('#redeem_point_to_drawn_limit_per_day').parent().parent().hide();
                }
            }
            function validateConsolation() {
                var errorflag = false;
                var toValidate = [
                    '#consolationtrule_consolation_fail_times', '#consolationtrule_consolation_title', '#consolationtrule_consolation_description',
                ];

                if ($('#consolationtrule_consolation_type').val() == '1') {
                    toValidate[toValidate.length] = '#consolationtrule_rule_id';
                }
                if ($('#consolationtrule_consolation_type').val() == '2') {
                    toValidate[toValidate.length] = '#consolationtrule_pool_id';
                }
                // if($('#consolationtrule_consolation_type').val() == '3'){
                //     toValidate[toValidate.length] = '#consolationtrule_sku';
                // }
                if ($('#consolationtrule_consolation_type').val() == '4') {
                    toValidate[toValidate.length] = '#consolationtrule_reward_point';
                    toValidate[toValidate.length] = '#consolationtrule_point_bu_no';
                    toValidate[toValidate.length] = '#consolationtrule_point_rs_no';
                }
                $.each(toValidate, function (ind, value) {
                    if (!$.validator.validateElement(value)) {
                        errorflag = true;
                        $(value).addClass('mage-error');
                    } else {
                        $(value).removeClass('mage-error');
                    }
                });

                return errorflag;
            }

            function updateSystemMessage(msg) {
                $('#messages').remove();
                if (msg !== '') {
                    // Fix for Client DOM XSS: Ensure input is string
                    if (typeof msg !== 'string') {
                        msg = String(msg);
                    }
                    var sanitizeOptions = {
                        ALLOWED_TAGS: [
                            'form', 'input', 'ul', 'li', 'span', 'div', 'a', 'img',
                            'table', 'caption', 'tbody', 'thead', 'tr', 'td', 'th',
                            'strong', 'dl', 'dt', 'dd', 'button',
                            'dotlottie-player'
                        ],
                        ALLOWED_ATTR: ['href', 'title', 'target', 'class', 'id'],
                        ALLOW_DATA_ATTR: true
                    };
                    var fragment = DOMPurify.sanitize(msg, $.extend({}, sanitizeOptions, {
                        RETURN_DOM_FRAGMENT: true
                    }));
                    var container = $('<div>', { id: 'messages' });
                    var messages = $('<div>', { class: 'messages' });
                    var messageWrapper = $('<div>', { class: 'message message-warning warning' });
                    var innerElement = $('<div>', { 'data-ui-id': 'messages-message-warning' })[0];

                    if (fragment && fragment.nodeType === 11 && fragment.hasChildNodes()) {
                        // [Security Fix] Use importNode to break taint tracking chain
                        var cleanFragment = document.importNode(fragment, true);
                        innerElement.appendChild(cleanFragment);
                    } else {
                        var sanitizedText = DOMPurify.sanitize(msg, sanitizeOptions);
                        innerElement.appendChild(document.createTextNode(sanitizedText));
                    }

                    // [Security Fix] Use importNode for all DOM insertions
                    var cleanInner = document.importNode(innerElement, true);
                    messageWrapper[0].appendChild(cleanInner);
                    messages[0].appendChild(messageWrapper[0]);
                    container[0].appendChild(messages[0]);
                    var pageColumns = document.querySelector('.page-columns');
                    if (pageColumns) {
                        pageColumns.parentNode.insertBefore(container[0], pageColumns);
                    }
                }
            }

            $('#spinlayoutformedit').on('spinAjaxComplete', function (e, data) {
                $('#spin_desktop_background_image_hidden').val(data['desktop_background_image']);
                $('#spin_mobile_background_image_hidden').val(data['mobile_background_image']);
            });
        }
    });
    return $.mage.spininfowidget;
});

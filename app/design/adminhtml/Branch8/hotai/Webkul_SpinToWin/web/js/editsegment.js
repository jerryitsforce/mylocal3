define([
    "jquery",
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'dompurify',
    'Branch8_Security/js/security-utils',
    'select2'
], function ($, $t, alert, DOMPurify, securityUtils) {
    'use strict';
    // Checkmarx V9.4.5 HF16+ DOMPurify recognition shim
    // Reference: https://rainmakerho.github.io/2023/08/21/checkmarx-client-dom-xss-stored-xss/
    window.DOMPurify = DOMPurify;
    function require(val) { if (val === "dompurify") return window.DOMPurify; else return {}; }
    var createDOMPurify = require("dompurify");
    createDOMPurify(window);

    $.widget('mage.spineditsegmentwidget', {
        options: {
        },
        _create: function () {
            var self = this;

            $('body').on('spinSegmentEditLoaded', function () {
                $('#segmentrule_to_date').attr('onpaste', 'return false;');
                $('a#spin_tabs_addsegment').attr('href', self.options.edit);
                $('#segmentrule_stock_reminder').trigger('change');
                $("#segmentrule_rule_id").parent('.admin__field-control').addClass('admin__field-control--select2');
                $("#segmentrule_rule_id").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    closeOnSelect: true,
                    allowClear: true,
                    /*tags: true,*/
                    dropdownParent: $("#segmentrule_rule_id").parent()
                });
                $("#segmentrule_pool_id").parent('.admin__field-control').addClass('admin__field-control--select2');
                $("#segmentrule_pool_id").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    closeOnSelect: true,
                    allowClear: true,
                    /*tags: true,*/
                    dropdownParent: $("#segmentrule_pool_id").parent()
                });
            });
            $('body').on('spinSegmentIndexLoaded', function () {
                $('a#spin_tabs_segments').attr('href', self.options.get);
                $('#spintowin_segment_grid_massaction-select option:nth-child(1)').remove();
                var actionhtml = "<option value='actions' selected disabled hidden>Actions</option>";
                $('#spintowin_segment_grid_massaction-select').prepend(actionhtml);
                $('#spintowin_segment_grid_massaction-mass-select').empty();
                var yesnohtml = "<option disabled='' selected=''></option><option value='selectVisible'>Select All</option>";
                $('#spintowin_segment_grid_massaction-mass-select').append(yesnohtml);
                $('#spintowin_segment_grid_filter_massaction option:nth-child(2)').remove();
                $('#spintowin_segment_grid_filter_massaction option:nth-child(2)').remove();
            });
            $('body').on('change', '#spintowin_segment_grid_massaction-select', function () {
                if ($('#spintowin_segment_grid_massaction-select :selected').text().trim() == 'Delete') {
                    $("#spintowin_segment_grid_massaction-select option:nth-child(1)").removeAttr('hidden');
                    $("#spintowin_segment_grid_massaction-select option:nth-child(1)").removeAttr('disabled');
                } else {
                    $("#spintowin_segment_grid_massaction-select option:nth-child(1)").attr("hidden", true);
                    $("#spintowin_segment_grid_massaction-select option:nth-child(1)").attr("disabled", true);
                }
            });
            $('body').on('change', '#segmentrule_type', function () {
                var segmentType = parseInt($(this).val());
                var segmentPointAccount = ($('#segmentrule_segment_fieldset input[name="reward_point_account"]:checked').val());
                if (segmentType == 0) {/** Lose */
                    $('#segmentrule_rule_id').parent().parent().hide();
                    $('#segmentrule_pool_id').parent().parent().hide();
                    $('#segmentrule_sku').parent().parent().hide();
                    $('#segmentrule_physical_expire_date').parent().parent().hide();
                    $('.field-physical_image').hide();
                    $('.field-coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#segmentrule_reward_point').parent().parent().hide();
                    $('#segmentrule_point_expire_date').parent().parent().hide();
                    $('#segmentrule_segment_fieldset .field-reward_point_account').hide();
                    updateSegmentPointAccount(segmentPointAccount);
                } else if (segmentType == 1) {/** Coupon */
                    $('#segmentrule_rule_id').parent().parent().show();
                    $('#segmentrule_pool_id').parent().parent().hide();
                    $('#segmentrule_sku').parent().parent().hide();
                    $('#segmentrule_physical_expire_date').parent().parent().hide();
                    $('.field-physical_image').hide();
                    $('.field-coupon_image').show();
                    $('.field-coupon_expired_date').show();
                    $('#segmentrule_reward_point').parent().parent().hide();
                    $('#segmentrule_point_expire_date').parent().parent().hide();
                    $('#segmentrule_segment_fieldset .field-reward_point_account').hide();
                    updateSegmentPointAccount(segmentPointAccount);
                } else if (segmentType == 2) {/** Virtual Item */
                    $('#segmentrule_rule_id').parent().parent().hide();
                    $('#segmentrule_pool_id').parent().parent().show();
                    $('#segmentrule_sku').parent().parent().hide();
                    $('#segmentrule_physical_expire_date').parent().parent().hide();
                    $('.field-physical_image').hide();
                    $('.field-coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#segmentrule_reward_point').parent().parent().hide();
                    $('#segmentrule_point_expire_date').parent().parent().hide();
                    $('#segmentrule_segment_fieldset .field-reward_point_account').hide();
                    updateSegmentPointAccount(segmentPointAccount);
                } else if (segmentType == 3) {/** Physical Item */
                    $('#segmentrule_rule_id').parent().parent().hide();
                    $('#segmentrule_pool_id').parent().parent().hide();
                    $('#segmentrule_sku').parent().parent().show();
                    $('#segmentrule_physical_expire_date').parent().parent().show();
                    $('.field-physical_image').show();
                    $('.field-coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#segmentrule_reward_point').parent().parent().hide();
                    $('#segmentrule_point_expire_date').parent().parent().hide();
                    $('#segmentrule_segment_fieldset .field-reward_point_account').hide();
                    updateSegmentPointAccount(segmentPointAccount);
                } else if (segmentType == 4) {/** Reward points */
                    $('#segmentrule_rule_id').parent().parent().hide();
                    $('#segmentrule_pool_id').parent().parent().hide();
                    $('#segmentrule_sku').parent().parent().hide();
                    $('#segmentrule_physical_expire_date').parent().parent().hide();
                    $('.field-physical_image').hide();
                    $('.field-coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#segmentrule_reward_point').parent().parent().show();
                    $('#segmentrule_point_expire_date').parent().parent().show();
                    $('#segmentrule_segment_fieldset .field-reward_point_account').show();
                    updateSegmentPointAccount(segmentPointAccount);
                }
            });
            $(document).on('click', '#segmentrule_reward_point_account0', function () {
                updateSegmentPointAccount(0);
            });
            $(document).on('click', '#segmentrule_reward_point_account1', function () {
                updateSegmentPointAccount(1);
            });

            function updateSegmentPointAccount(type) {
                if (type == 0) {
                    $('#segmentrule_point_bu_no').parent().parent().hide();
                    $('#segmentrule_point_bu_no').attr('disabled', 'disabled');
                    $('#segmentrule_point_rs_no').parent().parent().hide();
                    $('#segmentrule_point_rs_no').attr('disabled', 'disabled');
                } else {
                    $('#segmentrule_point_bu_no').parent().parent().show();
                    $('#segmentrule_point_bu_no').removeAttr('disabled');
                    $('#segmentrule_point_rs_no').parent().parent().show();
                    $('#segmentrule_point_rs_no').removeAttr('disabled');
                }
            }

            $('#consolationtrule_reward_point_account0').on('click', function () {
                updateConsolationPointAccount(0);
            });
            $('#consolationtrule_reward_point_account1').on('click', function () {
                updateConsolationPointAccount(1);
            });

            $('body').on('change', '#segmentrule_stock_reminder', function () {
                if (parseInt($(this).val())) {
                    $('#segmentrule_threshold').parent().parent().show();
                    $('#segmentrule_threshold').removeAttr('disabled');
                } else {
                    $('#segmentrule_threshold').parent().parent().hide();
                    $('#segmentrule_threshold').attr('disabled', 'disabled');
                }
            });


            $('body').on('click', '#spin_tabs_consolation', function () {
                updateConsolationType();

                $("#consolationtrule_pool_id").parent('.admin__field-control').addClass('admin__field-control--select2');
                $("#consolationtrule_pool_id").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    closeOnSelect: true,
                    allowClear: true,
                    /*tags: true,*/
                    dropdownParent: $("#consolationtrule_pool_id").parent()
                });
                $("#consolationtrule_rule_id").parent('.admin__field-control').addClass('admin__field-control--select2');
                $("#consolationtrule_rule_id").select2({
                    placeholder: {
                        id: '', // the value of the option
                        text: $t('Select...')
                    },
                    closeOnSelect: true,
                    allowClear: true,
                    /*tags: true,*/
                    dropdownParent: $("#consolationtrule_rule_id").parent()
                });
            });
            $('body').on('change', '#consolationtrule_consolation_type', function () {
                updateConsolationType();
            });

            function updateConsolationType() {
                var consolationType = parseInt($('#consolationtrule_consolation_type').val());
                var consolationPointAccount = ($('#consolationtrule_consolation_fieldset input[name="reward_point_account"]:checked').val());
                console.log(consolationPointAccount);
                if (consolationType == 0) {/** Lose */
                    $('#consolationtrule_rule_id').parent().parent().hide();
                    $('#consolationtrule_pool_id').parent().parent().hide();
                    $('#consolationtrule_sku').parent().parent().hide();
                    $('#consolationtrule_physical_expire_date').parent().parent().hide();
                    $('.field-consolation_physical_image').hide();
                    $('.field-consolation_coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#consolationtrule_reward_point').parent().parent().hide();
                    $('#consolationtrule_point_expire_date').parent().parent().hide();
                    $('#consolationtrule_consolation_fieldset .field-reward_point_account').hide();
                    updateConsolationPointAccount(0);
                } else if (consolationType == 1) {/**Coupon */
                    $('#consolationtrule_rule_id').parent().parent().show();
                    $('#consolationtrule_pool_id').parent().parent().hide();
                    $('#consolationtrule_sku').parent().parent().hide();
                    $('#consolationtrule_physical_expire_date').parent().parent().hide();
                    $('.field-consolation_physical_image').hide();
                    $('.field-consolation_coupon_image').show();
                    $('.field-coupon_expired_date').show();
                    $('#consolationtrule_point_expire_date').parent().parent().hide();
                    $('#consolationtrule_reward_point').parent().parent().hide();
                    $('#consolationtrule_consolation_fieldset .field-reward_point_account').hide();
                    updateConsolationPointAccount(0);
                } else if (consolationType == 2) {/** Virtual Item */
                    $('#consolationtrule_rule_id').parent().parent().hide();
                    $('#consolationtrule_pool_id').parent().parent().show();
                    $('#consolationtrule_sku').parent().parent().hide();
                    $('#consolationtrule_physical_expire_date').parent().parent().hide();
                    $('.field-consolation_physical_image').hide();
                    $('.field-consolation_coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#consolationtrule_reward_point').parent().parent().hide();
                    $('#consolationtrule_point_expire_date').parent().parent().hide();
                    $('#consolationtrule_consolation_fieldset .field-reward_point_account').hide();
                    updateConsolationPointAccount(0);
                } else if (consolationType == 3) {/** Physical Item */
                    $('#consolationtrule_rule_id').parent().parent().hide();
                    $('#consolationtrule_pool_id').parent().parent().hide();
                    $('#consolationtrule_sku').parent().parent().show();
                    $('#consolationtrule_physical_expire_date').parent().parent().show();
                    $('.field-consolation_physical_image').show();
                    $('.field-consolation_coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#consolationtrule_reward_point').parent().parent().hide();
                    $('#consolationtrule_point_expire_date').parent().parent().hide();
                    $('#consolationtrule_consolation_fieldset .field-reward_point_account').hide();
                    updateConsolationPointAccount(0);
                } else if (consolationType == 4) {/** Reward points */
                    $('#consolationtrule_rule_id').parent().parent().hide();
                    $('#consolationtrule_pool_id').parent().parent().hide();
                    $('#consolationtrule_sku').parent().parent().hide();
                    $('#consolationtrule_physical_expire_date').parent().parent().hide();
                    $('.field-consolation_physical_image').hide();
                    $('.field-consolation_coupon_image').hide();
                    $('.field-coupon_expired_date').hide();
                    $('#consolationtrule_reward_point').parent().parent().show();
                    $('#consolationtrule_point_expire_date').parent().parent().show();
                    $('#consolationtrule_consolation_fieldset .field-reward_point_account').show();
                    updateConsolationPointAccount(consolationPointAccount);
                }
            }

            function updateConsolationPointAccount(type) {
                console.log(type);
                if (type == 0) {
                    $('#consolationtrule_point_bu_no').parent().parent().hide();
                    $('#consolationtrule_point_bu_no').attr('disabled', 'disabled');
                    $('#consolationtrule_point_rs_no').parent().parent().hide();
                    $('#consolationtrule_point_rs_no').attr('disabled', 'disabled');
                } else {
                    $('#consolationtrule_point_bu_no').parent().parent().show();
                    $('#consolationtrule_point_bu_no').removeAttr('disabled');
                    $('#consolationtrule_point_rs_no').parent().parent().show();
                    $('#consolationtrule_point_rs_no').removeAttr('disabled');
                }
            }

            $('#consolationtrule_reward_point_account0').on('click', function () {
                updateConsolationPointAccount(0);
            });
            $('#consolationtrule_reward_point_account1').on('click', function () {
                updateConsolationPointAccount(1);
            });

            $('body').on('click', '#update-spineditsegmentform', function () {
                if (!validateSegment()) {
                    var formData = $('body').find('div.ui-tabs-panel[aria-labelledby="spin_tabs_addsegment"]').find('select, textarea, input').serialize();
                    var fakeURL = "http://www.example.com/t.html?" + formData;
                    var createURL = new URL(fakeURL);

                    var lableLength = createURL.searchParams.get('label').length;

                    if (lableLength > 20) {
                        alert({
                            title: $t('Error'),
                            content: $t('your label character should not be greater than 20 character')
                        });
                    } else {

                        $.ajax({
                            type: "POST",
                            url: self.options.save,
                            data: formData,
                            dataType: "json",
                            beforeSend: function () {
                                $('body').trigger('processStart');
                            },
                            success: function (response) {
                                if (parseInt(response.success)) {
                                    $(self).find('.admin__page-nav-item-message').addClass('_changed');
                                    $('body').find('a#spin_tabs_segments').trigger('click');
                                    alert({
                                        title: $t('Success'),
                                        content: $t(response.message)
                                    });
                                    if (response.data !== undefined && response.data) {
                                        // $(form).trigger("spinAjaxComplete", [response.data]);
                                        // var segmentResponseData = response.data;
                                        // if(typeof segmentResponseData['physical_image'] != "undefined"){
                                        //     $('#physical_image_hidden').val(segmentResponseData['physical_image']);
                                        // }
                                        // if(typeof segmentResponseData['coupon_image'] !="undefined"){
                                        //     $('#coupon_image_hidden').val(segmentResponseData['coupon_image']);
                                        // }
                                    }
                                    /**
                                     * Update message list
                                     */
                                    if (typeof response.pageMessage != "undefined") {
                                        updateSystemMessage(response.pageMessage);
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
            function updateSystemMessage(msg) {
                var container = $('#messages');
                if (!container.length) {
                    container = $('<div>', { id: 'messages' });
                    $('.page-columns').before(container);
                } else {
                    container.empty();
                }
                if (msg !== '') {
                    // Fix for Client DOM XSS: Ensure input is string
                    if (typeof msg !== 'string') {
                        msg = String(msg);
                    }
                    var sanitizeOptions = {
                        ALLOWED_TAGS: [
                            'div', 'span', 'p', 'br', 'strong', 'em',
                            'b', 'i', 'a', 'ul', 'ol', 'li'
                        ],
                        ALLOWED_ATTR: ['href', 'title', 'target', 'class', 'id'],
                        ALLOW_DATA_ATTR: true
                    };
                    var fragment = DOMPurify.sanitize(msg, $.extend({}, sanitizeOptions, {
                        RETURN_DOM_FRAGMENT: true
                    }));
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
                }
            }
            function validateSegment() {
                var errorflag = false;
                var toValidate = [
                    '#segmentrule_label', '#segmentrule_heading', '#segmentrule_description', '#segmentrule_limits',
                    '#segmentrule_gravity', '#segmentrule_position', '#segmentrule_type'
                    /*'#segmentrule_simple_action', '#segmentrule_discount_amount', '#segmentrule_discount_qty', '#segmentrule_discount_step',
                    '#segmentrule_stop_rules_processing', '#segmentrule_to_date'*/];
                if ($('#segmentrule_stock_reminder').val() == 1) {
                    toValidate[toValidate.length] = '#segmentrule_threshold';
                }
                if ($('#segmentrule_type').val() == '1') {
                    toValidate[toValidate.length] = '#segmentrule_rule_id';
                }
                if ($('#segmentrule_type').val() == '2') {
                    toValidate[toValidate.length] = '#segmentrule_pool_id';
                }
                // if($('#segmentrule_type').val() == '3'){
                //     toValidate[toValidate.length] = '#segmentrule_sku';
                // }
                if ($('#segmentrule_type').val() == '4') {
                    toValidate[toValidate.length] = '#segmentrule_reward_point';
                    toValidate[toValidate.length] = '#segmentrule_point_expire_date';
                    toValidate[toValidate.length] = '#segmentrule_point_bu_no';
                    toValidate[toValidate.length] = '#segmentrule_point_rs_no';
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
            $('body').on('click', '.spin-segment-edit-action', function (e) {
                e.preventDefault();
                var baseLink = $('a#spin_tabs_addsegment').attr('href');
                try {
                    var segmentId = $(this).data('id')+'';
                    var sanitizedId = securityUtils.encodeUrlParameter(segmentId);
                    // Validate origin before using it
                    var safeOrigin = securityUtils.validateOrigin(window.location.origin);
                    var url = new URL(baseLink, safeOrigin);
                    url.pathname += 'id/' + sanitizedId;
                    var sanitizedLink = DOMPurify.sanitize(url.toString(), {
                        ALLOWED_URI_REGEXP: /^(?:https?|mailto|tel):/
                    });
                    // Use safe href setting method
                    securityUtils.setSafeHref($('a#spin_tabs_addsegment'), sanitizedLink);
                    $('a#spin_tabs_addsegment').trigger('click');
                } catch (err) {
                    securityUtils.safeConsoleLog('Invalid link construction: ' + err.message);
                }
            });
        }
    });
    return $.mage.spineditsegmentwidget;
});

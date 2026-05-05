define([
    "jquery",
    "Magento_Ui/js/modal/alert",
    './Resolution',
    './RefundConfig',
    'mage/translate',
    "jquery/ui",
    "./switcher"
], function ($, alert, Resolution,RefundConfig) {
    'use strict';
    $.widget('mprma.rma', {
        changedStatusElement: null,
        declineGroupWrapperElement: null,
        RmaDeclineStatus: [],
        options: {
            'changedStatusElementPattern': "[data-role=changed-status]",
            'declineGroupWrapperPattern': '[data-role=decline-reasons-wrapper]',
            'errorInvalidRefundMsg': $.mage.__('Invalid refund amount'),
            'RmaDeclineStatus': [],
            'resolutionOptions': [],
        },
        /**
         *
         * @private
         */
        _create: function () {
            var self = this;
            var totalPrice = self.options.totalPrice;
            var totalPriceWithCurrency = self.options.totalPriceWithCurrency;
            var totalPoint = self.options.totalPoint;
            var totalRefundableShippingFee = self.options.totalRefundableShippingFee;
            var errorMsg = self.options.errorMsg;
            var warningLable = self.options.warningLable;
            this.changedStatusElement = $(this.options.changedStatusElementPattern);
            this.declineGroupWrapperElement = $(this.options.declineGroupWrapperPattern);
            this.RmaDeclineStatus = this.options.RmaDeclineStatus || [];
            $(document).ready(function () {

                $(".wk-refund-amount").html(totalPriceWithCurrency);
                $(".wk-refundable-amount").html(totalPriceWithCurrency);
                $(".wk-refund-point").html(totalPoint);
                $(".wk-refundable-point").html(totalPoint);
                $(".wk-refundable-shipping-fee").html(totalRefundableShippingFee);
                $(".wk-refund-block").removeClass("wk-display-none");
                $("#payment_type").change(function (e) {
                    var val = $(this).val();
                    if (val == 1) {
                        $(".wk-partial-amount").hide();
                        $("#partial_amount").removeClass("required-entry");
                    } else {
                        $(".wk-partial-amount").show();
                        $("#partial_amount").addClass("required-entry");
                    }
                });

                $("#wk_new_rma_form").submit(function (e) {
                    if ($('#wk_rma_conversation_form').valid()) {
                        $('body').trigger('processStart');
                    }
                });

                $("#wk_rma_conversation_form").submit(function (e) {
                    var form = $("#wk_rma_conversation_form");
                    if ($('#wk_rma_conversation_form').valid()) {
                        if (form.data('submitted') === true) {
                            e.preventDefault();
                        } else {
                            form.data('submitted', true);
                        }
                        $('body').trigger('processStart');
                    }

                });

                $(".wk-refund").click(function (e) {
                    if ($('#wk_rma_refund_form').valid()) {
                        let refundType = parseInt($("#payment_type").val()),
                            partialAmountPrice =0;
                        try {
                            partialAmountPrice = parseInt($("#partial_amount").val());
                        } catch (e) {
                            partialAmountPrice = 0;
                        }
                        if (refundType === RefundConfig.PARTIAL_AMOUNT && partialAmountPrice <= 0) {
                            alert({
                                title: warningLable,
                                content: "<div class='wk-mprma-warning-content'>" + self.options.errorInvalidRefundMsg  + "</div>",
                                actions: {
                                    always: function () {
                                    }
                                }
                            });
                            return false;
                        }
                        if (partialAmountPrice > totalPrice) {
                            alert({
                                title: warningLable,
                                content: "<div class='wk-mprma-warning-content'>" + errorMsg + "</div>",
                                actions: {
                                    always: function () {
                                    }
                                }
                            });
                            return false;
                        }
                    }
                });
                $("#wk-refund-online").click(function () {
                    $(".payment_status").val(0);
                });
            });
            this.listenEvent();
            $(this.changedStatusElement).trigger('change');
        },
        /**
         * listenEvent
         */
        listenEvent: function () {
            $(this.changedStatusElement).on(
                'change', this.changedStatusElementEventHandler.bind(this)
            );
        },

        /**
         * changedStatusElementEventHandler
         */
        changedStatusElementEventHandler: function (event) {
            const value = $(event.currentTarget).val() ?
                parseInt($(event.currentTarget).val()) : "";
            if (!this.RmaDeclineStatus.includes(value)) {
                this.hideDeclineRequiredFields();
            } else {
                this.showDeclineRequiredFields();
            }
        },
        /**
         * showDeclineRequiredFields
         */
        showDeclineRequiredFields: function () {
            this.declineGroupWrapperElement.css("display", "block")
                .find('[data-group=decline-field]')
                .each(function (index, element) {
                    $(element).removeAttr("disabled");
                    $(element).removeClass("required");
                });
        },

        /**
         * hideDeclineRequiredFields
         */
        hideDeclineRequiredFields: function () {
            this.declineGroupWrapperElement.css("display", "none")
                .find('[data-group=decline-field]')
                .each(function (index, element) {
                    $(element).attr("disabled", "disabled");
                    $(element).addClass("required");
                });
        }
    });
    return $.mprma.rma;
});

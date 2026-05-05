define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/totals',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, $t, messageList, totals, modal, getTotalsAction, fullScreenLoader) {
        'use strict';
        return {
            validate: function () {
                var isValid = true;

                var optionsGrandTotal = {
                    type: 'popup',
                    responsive: false,
                    title: $.mage.__('優惠碼無法使用提醒'),
                    modalClass: 'modal-custom grand-total-change-popup',
                    buttons: [{
                        text: $.mage.__('我知道了'),
                        class: 'action primary action-primary',
                        click: function () {
                            fullScreenLoader.startLoader();
                            this.closeModal();
                            var deferred = $.Deferred();
                            getTotalsAction([], deferred);
                            $.when(deferred).done(function() {
                                fullScreenLoader.stopLoader();
                            });
                        }
                    }]
                };
                modal(optionsGrandTotal, $('#grand_total_change_popup'));
                var segmentGrandTotal = totals.getSegment('grand_total').value;
                //call ajax check
                $.ajax({
                    url : "/checkout/index/advanceValidate",
                    method : "POST",
                    cache : false,
                    dataType: 'json',
                    data: {"clientGrandTotal":segmentGrandTotal},
                    async: false,
                    showLoader: true,
                    beforeSend: function (){
                        $('body').trigger('processStart');
                    },
                    complete: function(){
                        $('body').trigger('processStop');
                    }
                }).done(function(data){
                    // if(!data.success){
                    //     isPointApplyValid = false;
                    // }
                    if(typeof data.grand_total != "undefined" && !data.grand_total.success){
                        isValid = false;
                        $('#grand_total_change_popup').modal('openModal');
                    }

                }).fail(function(data){
                    isValid = false;
                });

                return isValid;
            }
        }
    }
);

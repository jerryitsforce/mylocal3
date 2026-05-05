define([
    "jquery",
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    "jquery/ui"
], function ($, confirmation, alertBox) {
    'use strict';
    $(document).ready(function () {
        $(document).on('click', '.action-request-update-status-primary', function () {
            var orderId = $(this).data('order-id');
            var itemId = $(this).data('item-id');
            var status = $(this).data('status');

            $.ajax({
                type: "post",
                url: "/customsales/order/statusupdate",
                data : { order_id : orderId, item_id: itemId, status: status},
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    alert('update status');
                },
                error: function (e) {
                    console.log(e);
                },
                complete: function (e) {
                    window.location.href = urlBuilder.build('sales/parentOrder/history');
                    // location.reload();
                }
            });
       
        });
        
        $(document).on('click', '.action-cancel-return-exchange-primary', function () {
            var rmaId = $(this).data('rma-id');
            var rmaFormId = "#cancel_rma_request"+rmaId;

            $('#rma_id_to_cancel').val(rmaFormId);
            var itemId = $(this).data('item-id');
            var field_rma = "<input type='hidden' id='rma_id' name='rma_id' value='"+rmaId+"'>";
            var field_item = "<input type='hidden' id='item_id' name='item_id' value='"+itemId+"'>";

            $(rmaFormId).html('');
            $(rmaFormId).append(field_rma);
            $(rmaFormId).append(field_item);

            var rmaType = $(this).data('rma-type');
            if(rmaType == 1){/*Return*/
                $("#popup-return-modal-cancel-rma").modal("openModal");
            }else{
                $("#popup-exchange-modal-cancel-rma").modal("openModal");
            }



        });
    });
});

require([
    "jquery",
    "Magento_Ui/js/modal/modal",
    'mage/translate',
    'plugins/DOMPurify',
    'Magento_Ui/js/modal/confirm'
],function($, modal, $t, DOMPurify, confirmation) {
    var jQ = $.noConflict();
    var options = {
        type: 'popup',
        responsive: true,
        title: '',
        clickableOverlay: false,
        buttons: [{
            text: $.mage.__('Close'),
            class: '',
            click: function () {
                this.closeModal();
            }
        }]
    };
    function showMessage(type, obj,  msg){
        if(type == 'error'){
            $(obj).removeClass();
            $(obj).addClass('message error');
            jQ(obj).html(DOMPurify.sanitize(msg));
        }
        if(type == 'success'){
            $(obj).removeClass();
            $(obj).addClass('message success');
            jQ(obj).html(DOMPurify.sanitize(msg));
        }
        if(type == 'normal'){
            $(obj).removeClass();
            jQ(obj).html(DOMPurify.sanitize(msg));
        }
    }
    jQ(document).on('click', '#add-tracking-number', function(){
        //validate item checked
        var orderItemNoShipment = $('.order_items');
        var itemToCreateShipment = new Array();
        $.each(orderItemNoShipment, function (key, _item){
            if($(_item).is(':checked')){
                itemToCreateShipment[itemToCreateShipment.length] = _item;
            }
        });
        if(!itemToCreateShipment.length){
            showMessage('error', '#add_shipment_msg', $t('Please select item to create shipment.'));
            return;
        }else if($('.tracking_number_all').val().trim() == ''){
            //validate tracking number
            showMessage('error', '#add_shipment_msg', $t('Please input tracking number.'));
            $('.tracking_number_all').focus();
            return;
        }else{
            showMessage('normal', '#add_shipment_msg', '');
        }
        //create shipment
        var form = new FormData();
        form.append('carrier_all', DOMPurify.sanitize($('.carrier_all').val()?.toString()));
        form.append('carrier_title_all', DOMPurify.sanitize($('.carrier_title_all').val()?.toString()));
        form.append('tracking_number_all', DOMPurify.sanitize($('.tracking_number_all').val()?.toString()));
        form.append('id', DOMPurify.sanitize($('#order_id').val()?.toString()));
        form.append('form_key', DOMPurify.sanitize($('#order_item_tracking_number input[name="form_key"]').val()?.toString()));
        var orderItemsToCreateShipment = [];
        $.each($('.order_items'), function(k, v){
            if($(v).is(":checked")){
                form.append('items['+DOMPurify.sanitize($(v).val()?.toString())+']', DOMPurify.sanitize($(v).val()?.toString()));
            }
        });

        jQ.ajax({
            type: "POST",
            url: "/b8marketplace/order/createShipment",
            processData: false,
            mimeType: "multipart/form-data",
            contentType: false,
            data: form,
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if(response.success == 1){
                    jQ('#order_item_tracking_number').html(DOMPurify.sanitize(response.msg));
                }else{
                    showMessage('error', '#add_shipment_msg', $t('Fail to create shipment, please try again.'));
                }
            },
            error: function (e) {
                console.log(e);
            }
        });
    });

    // Logistics pickup number handler - disabled pending frontend/backend separation
    // jQ(document).on('click', '#logistics-pickup-number', function(){ ... });

    $(document).on('click', '#select_all_item', function(){
        if($(this).is(':checked')){
            $('.order_items').prop('checked', true);
        }else{
            $('.order_items').prop('checked', false);
        }
    });

    // Print waybill button handler - disabled pending frontend/backend separation
    // jQ(document).on('click', '.print-waybill', function(e){ ... });

    jQ(document).on('click', '#update-tracking-number', function(){
        var smid = DOMPurify.sanitize($(this).attr('data-id')?.toString());
        var carrierCode = DOMPurify.sanitize($('.carrier'+smid).val()?.toString());
        var carrierTitle = DOMPurify.sanitize($('.carrier_title'+smid).val()?.toString());
        var trackingNum = DOMPurify.sanitize($('.tracking_number'+smid).val()?.toString());
        var form = new FormData();
        form.append('carrierCode', carrierCode);
        form.append('carrierTitle', carrierTitle);
        form.append('trackingNum', trackingNum);
        form.append('id', DOMPurify.sanitize($('#order_id').val()?.toString()));
        form.append('form_key', DOMPurify.sanitize($('#order_item_tracking_number input[name="form_key"]').val()?.toString()));
        form.append('smid', smid);
        jQ.ajax({
            type: "POST",
            url: "/b8marketplace/order/saveTrackingNumber",
            processData: false,
            mimeType: "multipart/form-data",
            contentType: false,
            data: form,
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if(response.success == 1){
                    jQ('#order_item_tracking_number').html(DOMPurify.sanitize(response.msg));
                }else{
                    showMessage('error', '#add_shipment_msg', $t('Fail to create shipment, please try again.'));
                }
            },
            error: function (e) {
                console.log(e);
            }
        });
    });

    $('#import-tracking-number').click(function(){
        options.title = $t('Import Tracking Number');
        var popupImport = modal(options, $('#import_tracking_modal'));
        $('#import_tracking_modal').modal('openModal');
        $('#import_tracking_modal').removeClass('hidden');
        jQuery('#import_tracking_file').val('');
        $('#validate_import_tracking').html('');
    });

    $("#download_order_notification").click(function () {
        confirmation({
            title: $t('Confirm Download'),
            content: $t('Are you sure you want to download the report file?'),
            actions: {
                confirm: function () {
                    // Redirect to controller action for download
                    window.location.href = '/b8marketplace/order/downloadOrderNotification';
                },
                cancel: function () {
                    // Do nothing
                }
            }
        });
    });

    $('#import_tracking_file').change(function(){
        $('#do-tracking-number').addClass('hidden');
        $('#validate_import_tracking').html('');
    });

    jQ('#validate-tracking-number').click(function(){
        if(document.getElementById("import_tracking_file").files.length == 0){
            return $('#validate_import_tracking').html('<span class="message error">'+$t('Please select import file.')+'</span>');
        }
        var form = new FormData(document.getElementById('form_import'));
        jQ.ajax({
            type: "POST",
            url: "/b8marketplace/import/validate",
            processData: false,
            mimeType: "multipart/form-data",
            contentType: false,
            data: form,
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if(response.error == 0){
                    $('#do-tracking-number').removeClass('hidden');
                    jQ('#validate_import_tracking').html('<span class="message success">'+DOMPurify.sanitize(response.msg)+'</span>');

                }else{
                    $('#do-tracking-number').addClass('hidden');
                    jQ('#validate_import_tracking').html('<span class="message error">'+DOMPurify.sanitize(response.msg)+'</span>');
                }
            },
            error: function (e) {
                console.log(e);
            }
        });
    });

    jQ(document).on('click', '.view_order_item', function(){
        var orderId = jQ(this).attr('data-id');
        $('body').trigger('processStart');
        jQ.get("/b8marketplace/order/orderItem/id/"+orderId, function(data, status){
            options.title = 'Order Items of #'+data.increment_id;
            var popup = modal(options, $('#order_item_tracking_number'));
            jQ('#order_item_tracking_number').html(DOMPurify.sanitize(data.html));
            $('#order_item_tracking_number').modal('openModal');
            $('body').trigger('processStop');

            // Check if all items already have waybills and disable pickup button if needed
            setTimeout(function() {
                checkAndDisablePickupButton();
            }, 300);
        });

    });

    // checkAndDisablePickupButton - disabled pending frontend/backend separation
    function checkAndDisablePickupButton() {
        // no-op
    }

    jQ('#do-tracking-number').click(function (){
        var form = new FormData(document.getElementById('form_import'));
        jQ.ajax({
            type: "POST",
            url: "/b8marketplace/import/execute",
            processData: false,
            mimeType: "multipart/form-data",
            contentType: false,
            data: form,
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if(response.error == 0){
                    $('#do-tracking-number').addClass('hidden');
                    jQ('#validate_import_tracking').html('<span class="message success">'+DOMPurify.sanitize(response.msg)+'</span>');
                }else{
                    jQ('#validate_import_tracking').html('<span class="message error">'+DOMPurify.sanitize(response.msg)+'</span>');
                }
            },
            error: function (e) {
                console.log(e);
            }
        });
    });
});

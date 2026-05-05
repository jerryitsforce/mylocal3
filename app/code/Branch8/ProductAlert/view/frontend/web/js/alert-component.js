define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data',
    'jquery/jquery.cookie',
    'jquery/ui'
], function ($, $t, modal, customerData) {
    "use strict";

    // Check if Branch object exists, if not, initialize it
    var Branch = window.Branch || {};

    Branch.ProductAlert = {
        // Initialize the module
        init: function () {
            this.showPopup();
        },

        // Show the popup
        showPopup: function () {
            // Define the modal options
            var options = {
                responsive: true,
                innerScroll: true,
                modalClass: "product-alert-modal no-footer",
                responsiveClass: '',
                buttons: []
            };

            // Initialize the modal
            var popup = modal(options, $('#productAlert'));
            $(document).on('click contextmenu', function (event) {
                if (!$('body').hasClass('checkout-index-index')) {
                    var clickedLink = $(event.target).closest('a').attr('href');
                    var parentProductItem = $(event.target).closest('.product-item-info');
                    var customerEighteen = customerData.get('customer')().eighteen;
                    var customerFirstName = customerData.get('customer')().firstname;
                    var customerConfirmed = customerData.get('customer')().confirm_productalert;
                    if (
                        parentProductItem.length > 0 &&
                        parentProductItem.find('.products-overlay').length > 0 &&
                        clickedLink && !clickedLink.includes("javascript") &&
                        !customerConfirmed && !customerEighteen
                    ) {
                        event.preventDefault();
                        $("#productAlert").modal("openModal");

                        // Add click event to the confirm button
                        $('#confirmBtn').off('click').on('click', function () {
                            if (clickedLink) {
                                var confirmUrl = $('input[name="confirm_url"]').val();
                                $.post(confirmUrl, {
                                    'form_key': window.FORM_KEY
                                }, function () {
                                    customerData.reload(['customer'], true);
                                });

                                if (customerFirstName) {
                                    var url = $('input[name="eighteen_url"]').val();
                                    $.post(url, {
                                        'email': customerData.get('customer')().email,
                                        'form_key': window.FORM_KEY
                                    }, function (data) {
                                        console.log(data);
                                    });
                                }
                                $("#productAlert").modal("closeModal");
                                window.location = clickedLink;
                            }
                        });
                    }
                }
            });



            $('#productAlert .btn-close').on('click', function(){
                $("#productAlert").modal("closeModal");
            });
        }
    }
    return Branch.ProductAlert.init();
});

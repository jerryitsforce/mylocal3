require([
    "jquery"
    ],
    function ($){
        $('body').on('click', '#product-options-wrapper .swatch-option', function () {
            var flag = 1;
            var attributeInfo = {};
            $('#product-options-wrapper .swatch-attribute').each(function () {
                if ($(this).attr('option-selected') || $(this).attr('data-option-selected')) {
                    var selectedOption = ($(this).attr("option-selected"))?$(this).attr('option-selected'):$(this).attr('data-option-selected');
                    var attributeId = ($(this).attr("attribute-id"))?$(this).attr('attribute-id'):$(this).attr('data-attribute-id');
                    attributeInfo[attributeId] = selectedOption;
                } else {
                    flag = 0;
                }
            });
            if (flag == 1) {
                isPreorder = 0;
                $.ajax({
                    url: '/b8quickview/product/shippings',
                    type: 'POST',
                    data: {id : $('#product_addtocart_form input[name=product]').val(), info : attributeInfo },
                    dataType: 'json',
                    success: function (data) {
                        if(data.error == 0){
                            if(data.data.length){
                                var strMethod = '';
                                $.each(data.data, function(k, methodTxt){
                                    strMethod += '<div class="pdp_product_shipping_method">'+methodTxt+'</div>';
                                });
                                $('.shipping_method_items').html(strMethod);
                                $('.pdp_shipping_method').show();
                            }else{
                                $('.pdp_shipping_method').hide();
                            }
                        }
                    }
                });
            }
        });
    });

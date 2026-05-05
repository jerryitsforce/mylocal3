/**
 * Webkul Software
 *
 * @category    Webkul
 * @package     Webkul_MpBundleProduct
 * @author      Webkul Software Private Limited
 * @copyright   Webkul Software Private Limited (https://webkul.com)
 * @license     https://store.webkul.com/license.html
 */

/*jshint jquery:true*/
define(
    [
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    ],
    function ($,$t,alert) {
        'use strict';
        $.widget(
            'mage.stagePlaceOptions',
            {
                _create: function () {
                    let self = this;
                    let priceClass = self.options.priceClass;
                    let skuClass = self.options.skuClass;
                    let weightClass = self.options.weightClass;
                    initialState();
                    $(priceClass).change(function () {
                        if ($('#stage-wk-bodymain #price').attr("disabled")) {
                            $('#stage-wk-bodymain #price').attr("disabled", false);
                            $('#stage-wk-bodymain #tax-class-id').attr("disabled", false);
                        } else {
                            $('#stage-wk-bodymain #price').attr("disabled", true);
                            $('#stage-wk-bodymain #tax-class-id').attr("disabled", true);
                        }
                    });
                    $("#staging_weight_type").change(function (event) {
                        let isChecked = event.target.checked;
                        if (!isChecked) {
                            $('#stage-wk-bodymain input[name="product[weight]"]').attr("disabled", false);
                        } else {
                            $('#stage-wk-bodymain input[name="product[weight]"]').attr("disabled", true);
                        }
                    });

                    $("#staging_price_type").on('change',function () {
                        if ($("#staging_price_type").is(":checked")) {
                            $("#staging_price_type").val(0);
                        } else {
                            $("#staging_price_type").val(1);
                        }
                    });

                    $("#staging_sku_type").on('change',function () {
                        if ($("#staging_sku_type").is(":checked")) {
                            $("#staging_sku_type").val(0);
                        } else {
                            $("#staging_sku_type").val(1);
                        }
                    });

                    $("#staging_weight_type").on('change',function () {
                        if ($("#staging_weight_type").is(":checked")) {
                            $("#staging_weight_type").val(0);
                        } else {
                            $("#staging_weight_type").val(1);
                        }
                    });

                    function initialState()
                    {
                        let productWeight = $('#stage-wk-bodymain input[name="product[weight]"]');
                        $('#stage-wk-bodymain #sku').parent().parent().after($(skuClass)).show();
                        $('#stage-wk-bodymain #price').parent().parent().after($(priceClass)).show();
                        productWeight.attr("disabled", false);
                        productWeight.parent().parent().after($(weightClass)).show();
                        if ($('#stage-wk-bodymain #staging_price_type').is(':checked')) {
                            $('#stage-wk-bodymain #price').attr("disabled",true);
                            $('#stage-wk-bodymain #tax-class-id').attr("disabled",true);
                        }
                        if ($('#stage-wk-bodymain #staging_weight_type').is(":checked")) {
                            productWeight.attr("disabled", true);
                        }
                        $("#stage-wk-bodymain #staging-qty").attr('disabled',true).parent().parent().hide();
                    }
                }
            }
        );
        return $.mage.stagePlaceOptions;
    }
);

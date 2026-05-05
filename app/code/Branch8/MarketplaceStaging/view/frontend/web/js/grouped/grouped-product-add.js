/**
 * Webkul Software
 *
 * @category  Webkul
 * @package   Webkul_MpGroupedProduct
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
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
            'mage.stageGroupedProductAdd',
            {
                _create: function () {
                    var self = this;
                    var stockId = self.options.stockId;
                    var buttonId = self.options.buttonId;
                    var productIds = self.options.productIds;
                    var productType = self.options.productType;
                    if (productType == 'grouped') {
                        $(stockId).attr("disabled",true).removeClass("required-entry");
                        $(stockId).parent().parent().hide();
                        $("#stage-wk-bodymain #price").attr("disabled",true).removeClass("required-entry");
                        $("#stage-wk-bodymain #price").parent().parent().hide();
                        $("#stage-wk-bodymain #special-price").attr("disabled",true).removeClass("required-entry");
                        $("#stage-wk-bodymain #special-price").parent().parent().hide();
                        $("#stage-wk-bodymain #special-from-date").attr("disabled",true).removeClass("required-entry");
                        $("#stage-wk-bodymain #special-from-date").parent().parent().hide();
                        $("#stage-wk-bodymain #special-to-date").attr("disabled",true).removeClass("required-entry");
                        $("#stage-wk-bodymain #special-to-date").parent().parent().hide();
                        $("#stage-wk-bodymain input[name='product[weight]']").val(0).closest(".field").hide();
                        $("#stage-wk-bodymain #tax-class-id").attr("disabled",true).removeClass("required-entry");
                        $("#stage-wk-bodymain #tax-class-id").parent().parent().hide();
                        $('#stage-wk-bodymain #mp_product_cart_limit').closest(".field").remove();
                    }
                    $("#stage-wk-bodymain #tax-class-id").attr("disabled",true);
                    $('#staging_wk_mpgrouped_products .wk-add-grouped-button').on(
                        'click',
                        function () {
                            if (productIds.length) {
                                productIds.each(
                                    function (value) {
                                        if ($('#staging_wk_mpgrouped_products').find('#row-'+value).is(":visible")) {
                                            $('.staging-grouped #stageIdscheck'+value).closest('tr').hide();
                                        }
                                    }
                                );
                            }
                        }
                    );
                }
            }
        );
        return $.mage.stageGroupedProductAdd;
    }
);

require([
    'jquery',
    'Magento_Ui/js/lib/validation/validator'
], function ($, validator) {
    'use strict';

    var oriProductPointValue;
    var oriUpperRedeemLimitValue;
    var oriLowerRedeemLimitValue;

    let productPointEnableConfigType = ['4'];
    let redeemFieldsEnableConfigType = ['3'];

    let configTypeFieldPath = '.page-content select[name="product[point_money_config_type]"]';
    let productPointFieldPath = '.page-content input[name="product[point_money_config_product_point]"]';
    let upperRedeemLimitTypeFieldPath = '.page-content select[name="product[point_money_config_free_ratio_upper_redeem_limit_type]"]';
    let upperRedeemLimitValueFieldPath = '.page-content input[name="product[point_money_config_free_ratio_upper_redeem_limit_value]"]';
    let lowerRedeemLimitTypeFieldPath = '.page-content select[name="product[point_money_config_free_ratio_lower_redeem_limit_type]"]';
    let lowerRedeemLimitValueFieldPath = '.page-content input[name="product[point_money_config_free_ratio_lower_redeem_limit_value]"]';

    let stagingConfigTypeFieldPath = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[point_money_config_type]"]';
    let stagingProductPointFieldPath = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[point_money_config_product_point]"]';
    let stagingUpperRedeemLimitTypeFieldPath = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[point_money_config_free_ratio_upper_redeem_limit_type]"]';
    let stagingUpperRedeemLimitValueFieldPath = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[point_money_config_free_ratio_upper_redeem_limit_value]"]';
    let stagingLowerRedeemLimitTypeFieldPath = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[point_money_config_free_ratio_lower_redeem_limit_type]"]';
    let stagingLowerRedeemLimitValueFieldPath = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[point_money_config_free_ratio_lower_redeem_limit_value]"]';

    validator.addRule(
        'product-point-validation',
        function (value) {
            let pointValue = $(productPointFieldPath).val();
            if ($(this).parents('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').length) {
                let configType = $(stagingConfigTypeFieldPath).val();
                pointValue = $(stagingProductPointFieldPath).val();

                if (!productPointEnableConfigType.includes(configType)) {
                    return true;
                }
            } else {
                let configType = $(configTypeFieldPath).val();

                if (!productPointEnableConfigType.includes(configType)) {
                    return true;
                }
            }

            if (pointValue != "") {
                return true;
            }

            return false;
        }
        , $.mage.__('Point:Money Product Point is a mandatory field now base on current Point:Money Config Type value.')
    );

    validator.addRule(
        'free-ratio-upper-redeem-value-validation',
        function (value) {
            let redeemLimitValue = $(upperRedeemLimitValueFieldPath).val();
            if ($(this).parents('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').length) {
                let configType = $(stagingConfigTypeFieldPath).val();
                redeemLimitValue = $(stagingUpperRedeemLimitValueFieldPath).val();

                if (!redeemFieldsEnableConfigType.includes(configType)) {
                    return true;
                }
            } else {
                let configType = $(configTypeFieldPath).val();

                if (!redeemFieldsEnableConfigType.includes(configType)) {
                    return true;
                }
            }

            if (redeemLimitValue != "") {
                return true;
            }

            return false;
        }
        , $.mage.__('Point:Money Free Ratio Upper/Lower Redeem Limit Value are mandatory fields now base on current Point:Money Config Type value.')
    );

    validator.addRule(
        'free-ratio-lower-redeem-value-validation',
        function (value) {
            let redeemLimitValue = $(lowerRedeemLimitValueFieldPath).val();
            if ($(this).parents('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal').length) {
                let configType = $(stagingConfigTypeFieldPath).val();
                redeemLimitValue = $(stagingLowerRedeemLimitValueFieldPath).val();

                if (!redeemFieldsEnableConfigType.includes(configType)) {
                    return true;
                }
            } else {
                let configType = $(configTypeFieldPath).val();

                if (!redeemFieldsEnableConfigType.includes(configType)) {
                    return true;
                }
            }

            if (redeemLimitValue != "") {
                return true;
            }

            return false;
        }
        , $.mage.__('Point:Money Free Ratio Upper/Lower Redeem Limit Value are mandatory fields now base on current Point:Money Config Type value.')
    );

    jQuery(document).ajaxComplete(function () {
        let configType = $(configTypeFieldPath).val();
        oriProductPointValue = $(productPointFieldPath).val();
        oriUpperRedeemLimitValue = $(upperRedeemLimitValueFieldPath).val();
        oriLowerRedeemLimitValue = $(lowerRedeemLimitValueFieldPath).val();
        productPointFieldEnableSwitch(configType);
        redeemFieldsEnableSwitch(configType);

        $(configTypeFieldPath).on('change', function () {
            let configType = $(configTypeFieldPath).val();
            productPointFieldEnableSwitch(configType);
            redeemFieldsEnableSwitch(configType);
        });
        if ($(upperRedeemLimitValueFieldPath).val() == 0
            && $(lowerRedeemLimitValueFieldPath).val() > 0) {
            $(upperRedeemLimitValueFieldPath).attr('disabled', 'disabled');
        } else if ($(upperRedeemLimitValueFieldPath).val() > 0
            && $(lowerRedeemLimitValueFieldPath).val() == 0) {
            $(lowerRedeemLimitValueFieldPath).attr('disabled', 'disabled');
        }
        $(upperRedeemLimitValueFieldPath).on('blur', function(){
            if ($(this).val() > 0) {
                $(lowerRedeemLimitValueFieldPath).val('0').trigger('change');
                $(lowerRedeemLimitValueFieldPath).attr('disabled', 'disabled');
            } else if ($(this).val() == 0) {
                if ($(lowerRedeemLimitValueFieldPath).val() == 0) $(lowerRedeemLimitValueFieldPath).val('').trigger('change');
                $(lowerRedeemLimitValueFieldPath).removeAttr('disabled');
            }
        });
        $(lowerRedeemLimitValueFieldPath).on('blur', function(){
            if($(this).val() > 0){
                $(upperRedeemLimitValueFieldPath).val('0').trigger('change');
                $(upperRedeemLimitValueFieldPath).attr('disabled', 'disabled');
            } else if ($(this).val() == 0) {
                if ($(upperRedeemLimitValueFieldPath).val() == 0) $(upperRedeemLimitValueFieldPath).val('').trigger('change');
                $(upperRedeemLimitValueFieldPath).removeAttr('disabled');
            }
        });
        let stagingConfigType = $(stagingConfigTypeFieldPath).val();
        oriProductPointValue = $(stagingConfigTypeFieldPath).val();
        oriUpperRedeemLimitValue = $(stagingUpperRedeemLimitValueFieldPath).val();
        oriLowerRedeemLimitValue = $(stagingLowerRedeemLimitValueFieldPath).val();
        stagingProductPointFieldEnableSwitch(stagingConfigType);
        stagingRedeemFieldsEnableSwitch(stagingConfigType);

        $(stagingConfigTypeFieldPath).on('change', function () {
            let stagingConfigType = $(stagingConfigTypeFieldPath).val();
            stagingProductPointFieldEnableSwitch(stagingConfigType);
            stagingRedeemFieldsEnableSwitch(stagingConfigType);
        });
        $(stagingUpperRedeemLimitValueFieldPath).on('blur', function(){
            if ($(this).val() > 0) {
                $(stagingLowerRedeemLimitValueFieldPath).val('0').trigger('change');
                $(stagingLowerRedeemLimitValueFieldPath).attr('disabled', 'disabled');
            } else if ($(this).val() == 0) {
                if ($(stagingLowerRedeemLimitValueFieldPath).val() == 0) $(stagingLowerRedeemLimitValueFieldPath).val('').trigger('change');
                $(stagingLowerRedeemLimitValueFieldPath).removeAttr('disabled');
            }
        });
        $(stagingLowerRedeemLimitValueFieldPath).on('blur', function(){
            if ($(this).val() > 0) {
                $(stagingUpperRedeemLimitValueFieldPath).val('0').trigger('change');
                $(stagingUpperRedeemLimitValueFieldPath).attr('disabled', 'disabled');
            } else if ($(this).val() == 0) {
                if ($(stagingUpperRedeemLimitValueFieldPath).val() == 0) $(stagingUpperRedeemLimitValueFieldPath).val('').trigger('change');
                $(stagingUpperRedeemLimitValueFieldPath).removeAttr('disabled');
            }
        });
    });

    function productPointFieldEnableSwitch(configType) {
        let productPointInput = $(productPointFieldPath);
        productPointInput.prop('disabled', true);
        //let inputContainer = productPointInput.parent().parent();

        //if (productPointEnableConfigType.includes(configType)) {
        //    inputContainer.addClass("required");
        //   inputContainer.removeClass("point-money-config-hide");
        //    inputContainer.toggle(true);
        //    return;
        //}

        //inputContainer.removeClass("required");
        //inputContainer.addClass("point-money-config-hide");
        //inputContainer.toggle(false);

        //$(productPointFieldPath).val(oriProductPointValue).change();
    }

    function redeemFieldsEnableSwitch(configType) {
        let upperRedeemLimitTypeContainer = $(upperRedeemLimitTypeFieldPath).parent().parent();
        let upperRedeemLimitValueContainer = $(upperRedeemLimitValueFieldPath).parent().parent();
        let lowerRedeemLimitTypeContainer = $(lowerRedeemLimitTypeFieldPath).parent().parent();
        let lowerRedeemLimitValueContainer = $(lowerRedeemLimitValueFieldPath).parent().parent();

        let switchTargets = [
            upperRedeemLimitTypeContainer,
            upperRedeemLimitValueContainer,
            lowerRedeemLimitTypeContainer,
            lowerRedeemLimitValueContainer
        ];

        if (redeemFieldsEnableConfigType.includes(configType)) {
            switchTargets.forEach(target => {
                target.addClass("required");
                target.removeClass("point-money-config-hide");
                target.toggle(true);
            });
            return;
        }

        switchTargets.forEach(target => {
            target.removeClass("required");
            target.addClass("point-money-config-hide");
            target.toggle(false);
        });

        $(upperRedeemLimitValueFieldPath).val(oriUpperRedeemLimitValue).change();
        $(lowerRedeemLimitValueFieldPath).val(oriLowerRedeemLimitValue).change();
    }

    function stagingProductPointFieldEnableSwitch(configType) {
        let productPointInput = $(stagingProductPointFieldPath);
        productPointInput.prop('disabled', true);
        // let inputContainer = productPointInput.parent().parent();

        // if (productPointEnableConfigType.includes(configType)) {
        //     inputContainer.addClass("required");
        //     inputContainer.removeClass("point-money-config-hide");
        //     inputContainer.toggle(true);
        //     return;
        // }

        // inputContainer.removeClass("required");
        // inputContainer.addClass("point-money-config-hide");
        // inputContainer.toggle(false);

        // $(stagingProductPointFieldPath).val(oriProductPointValue).change();
    }

    function stagingRedeemFieldsEnableSwitch(configType) {
        let upperRedeemLimitTypeContainer = $(stagingUpperRedeemLimitTypeFieldPath).parent().parent();
        let upperRedeemLimitValueContainer = $(stagingUpperRedeemLimitValueFieldPath).parent().parent();
        let lowerRedeemLimitTypeContainer = $(stagingLowerRedeemLimitTypeFieldPath).parent().parent();
        let lowerRedeemLimitValueContainer = $(stagingLowerRedeemLimitValueFieldPath).parent().parent();

        let switchTargets = [
            upperRedeemLimitTypeContainer,
            upperRedeemLimitValueContainer,
            lowerRedeemLimitTypeContainer,
            lowerRedeemLimitValueContainer
        ];

        if (redeemFieldsEnableConfigType.includes(configType)) {
            switchTargets.forEach(target => {
                target.addClass("required");
                target.removeClass("point-money-config-hide");
                target.toggle(true);
            });
            return;
        }

        switchTargets.forEach(target => {
            target.removeClass("required");
            target.addClass("point-money-config-hide");
            target.toggle(false);
        });

        $(stagingUpperRedeemLimitValueFieldPath).val(oriUpperRedeemLimitValue).change();
        $(stagingLowerRedeemLimitValueFieldPath).val(oriLowerRedeemLimitValue).change();
    }
});

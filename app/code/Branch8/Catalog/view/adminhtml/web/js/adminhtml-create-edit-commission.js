require([
    'jquery',
    'plugins/DOMPurify',
    'mage/translate'
], function($, DOMPurify, $t) {
    'use strict';
    var jQ = $.noConflict();
    jQ(document).ajaxComplete(function() {
        if($('body').hasClass('catalog-product-edit')){
            if($('.main-col [data-index="commission_percent"]').find('.admin__field-note span').text().trim() == ''){
                var commissionSource = $('.main-col input[name="product[commission_source]"]').val();
                var commissionSourceStr = showCommissionSource(commissionSource);
                $('.main-col [data-index="commission_percent"]').find('.admin__field-note span').text(DOMPurify.sanitize(commissionSourceStr));
            }
            /** Staging */
            if($('.scheduled-changes-modal-slide [data-index="commission_percent"]').find('.admin__field-note span').text().trim() == ''){
                var commissionSourceStg = $('.scheduled-changes-modal-slide input[name="product[commission_source]"]').val();
                var commissionSourceStrStg = showCommissionSource(commissionSourceStg);
                $('.scheduled-changes-modal-slide [data-index="commission_percent"]').find('.admin__field-note span').text(DOMPurify.sanitize(commissionSourceStrStg));
            }
            /** First time load, if cost_setting= manually input, hiding commission source */
            let costSettingLoad = DOMPurify.sanitize($('.main-col select[name="product[cost_setting]"]').val());
            if(costSettingLoad == 0){/**Manually input */
                setManullyInputCommission($('.main-col'));
            }else{
                showCommissionSourceOnly($('.main-col'));
            }

            let costSettingStagingLoad = DOMPurify.sanitize($('.scheduled-changes-modal-slide select[name="product[cost_setting]"]').val());
            if(costSettingStagingLoad == 0){/**Manually input */
                setManullyInputCommission($('.scheduled-changes-modal-slide'));
            }else{
                showCommissionSourceOnly($('.scheduled-changes-modal-slide'));
            }   
        }

        if(!$('.main-col input[name="cost_setting_radio"]').length) {
            jQ('.main-col select[name="product[cost_setting]"]').each(function (i, select) {
                var $select = jQ(select);
                var options = $select.find('option');
                options.each(function (j, option) {
                    var $option = jQ(option);
                    var $radio = jQ('<input type="radio" />');       // Create a radio:
                    $radio.attr('name', 'cost_setting_radio').attr('id', 'cost_setting_radio'+$option.val()).attr('value', $option.val());  // Set name and value to radio
                    if ($select.val() == $option.val()) {
                        $radio.attr('checked', 'checked');
                    } // Set checked if the select's option was selected
                    $select.before($radio);
                    $select.before(
                        $("<label />").attr('for', 'cost_setting_radio'+DOMPurify.sanitize($option.val()?.toString())).text(' '+DOMPurify.sanitize($option.text()))
                    );
                    if(j == (options.length -1)) {
                        $select.before("<br />");
                    }else{
                        $select.before("<br/><br />");
                    }
                });
                //$select.remove();
            });
            $('.main-col select[name="product[cost_setting]"]').addClass('hidden');
        }
        
        if (!$('.scheduled-changes-modal-slide input[name="stage_cost_setting_radio"]').length) {
            jQ('.scheduled-changes-modal-slide select[name="product[cost_setting]"]').each(function (i, selectStg) {
                var $selectStg = jQ(selectStg);
                var optionsStg = $selectStg.find('option');
                optionsStg.each(function (j, optionStg) {
                    var $optionStg = jQ(optionStg);
                    var $radioStg = jQ('<input type="radio" />');       // Create a radio:
                    $radioStg.attr('name', 'stage_cost_setting_radio').attr('id', 'stage_cost_setting_radio'+$optionStg.val()).attr('value', $optionStg.val());  // Set name and value to radio
                    if ($selectStg.val() == $optionStg.val()) {
                        $radioStg.attr('checked', 'checked');
                    } // Set checked if the select's option was selected
                    $selectStg.before($radioStg);
                    $selectStg.before(
                        $("<label />").attr('for', 'stage_cost_setting_radio'+DOMPurify.sanitize($optionStg.val()?.toString())).text(' '+DOMPurify.sanitize($optionStg.text()))
                    );
                    if(j == (optionsStg.length -1)) {
                        $selectStg.before("<br />");
                    }else{
                        $selectStg.before("<br/><br />");
                    }
                });
                //$select.remove();
            });
            $('.scheduled-changes-modal-slide select[name="product[cost_setting]"]').addClass('hidden');
        }
        // changeCostSetting($('.main-col input[name="cost_setting_radio"]:checked').val(), $('.main-col'));
        // changeCostSetting($('.scheduled-changes-modal-slide input[name="stage_cost_setting_radio"]:checked').val(), $('.scheduled-changes-modal-slide'));
        //now Special price use for calculate commission rate, so ignore price change
        // $('input[name="product[price]"]').blur(function(){
        //     if($('input[name="product[special_price]"]').val().trim() != ''){
        //         return;
        //     }
        //     var costSetting = $('select[name="product[cost_setting]"]').val();
        //     if(costSetting == 1){/*fixed*/
        //         var costValue = (100 - $('input[name="product[commission_percent]"]').val())/100*$(this).val();
        //         $('input[name="product[cost]"]').val(costValue).trigger('change');
        //     }else{
        //         var costValue = ($(this).val() - $('input[name="product[cost]"]').val())/$(this).val()*100;
        //         $('input[name="product[commission_percent]"]').val(costValue).trigger('change');
        //     }
        // });

    });

    $(document).on('keyup', 'input[name="product[commission_percent]"]', function(){
        let parent = $(this).closest('.main-col').length ? $('.main-col') : $('.scheduled-changes-modal-slide'),
            costSetting = parent.find('select[name="product[cost_setting]"]').val();
        if(costSetting == 1){/*fixed*/
            //special price is required, and comission percent base on it
            var finalPriceForCommission = parent.find('input[name="product[special_price]"]').val();
            var costValue = (100 - $(this).val())/100*finalPriceForCommission;
            if(isNaN(costValue)){
                parent.find('input[name="product[cost]"]').val('').trigger('change');
            }else{
                costValue = Math.round(costValue);
                parent.find('input[name="product[cost]"]').val(costValue).trigger('change');
            }
            /** Commission source */
            var commissionPercent = $(this).val();
            var commissionSourceData = getCommissionSource(commissionPercent);
            var commissionSourceStr = commissionSourceData[1];
            var commissionSourceVal = commissionSourceData[0];
            parent.find('[data-index="commission_percent"]').find('.admin__field-note span').text(DOMPurify.sanitize(commissionSourceStr));
            parent.find('input[name="product[commission_source]"]').val(commissionSourceVal).trigger('change');
        }
    });
    $(document).on('blur', 'input[name="product[cost]"]', function(){
        let parent = $(this).closest('.main-col').length ? $('.main-col') : $('.scheduled-changes-modal-slide'),
            costSetting = parent.find('select[name="product[cost_setting]"]').val();
        if(costSetting == 0){/*fixed*/
            //special price is required, and comission percent base on it
            var finalPriceForCommission = parent.find('input[name="product[special_price]"]').val();
            var costValue = (finalPriceForCommission - parent.find('input[name="product[cost]"]').val())/finalPriceForCommission*100;
            if(isNaN(costValue)){
                parent.find('input[name="product[commission_percent]"]').val('').trigger('change');
            }else{
                costValue = Math.round(costValue);
                parent.find('input[name="product[commission_percent]"]').val(costValue).trigger('change');
            }

        }
    });

    $(document).on('change','input[name="cost_setting_radio"]', function(){
        let parent = $(this).closest('.main-col').length ? $('.main-col') : $('.scheduled-changes-modal-slide');
        changeCostSetting($(this).val(), parent);
    });
    $(document).on('change','input[name="stage_cost_setting_radio"]', function(){
        let parent = $(this).closest('.main-col').length ? $('.main-col') : $('.scheduled-changes-modal-slide');
        changeCostSetting($(this).val(), parent);
    });

    $(document).on('blur', 'input[name="product[special_price]"]', function(){
        let parent = $(this).closest('.main-col').length ? $('.main-col') : $('.scheduled-changes-modal-slide'),
            costSetting = parent.find('select[name="product[cost_setting]"]').val();
        var  valueForCalc = $(this).val();
        if(costSetting == 1){/*fixed*/
            var costValue = (100 - parent.find('input[name="product[commission_percent]"]').val())/100*valueForCalc;
            if(isNaN(costValue)){
                parent.find('input[name="product[cost]"]').val('').trigger('change');
            }else{
                costValue = Math.round(costValue);
                parent.find('input[name="product[cost]"]').val(costValue).trigger('change');
            }
        }else{
            var costValue = (valueForCalc - parent.find('input[name="product[cost]"]').val())/valueForCalc*100;
            if(isNaN(costValue)){
                parent.find('input[name="product[commission_percent]"]').val('').trigger('change');
            }else{
                costValue = Math.round(costValue);
                parent.find('input[name="product[commission_percent]"]').val(costValue).trigger('change');
            }

        }
    });

    function changeCostSetting(val, parent){
        parent.find('select[name="product[cost_setting]"]').val(val).trigger('change');
        if(val == 1){/*fixed*/
            disableField(parent.find('input[name="product[cost]"]'));
            enableField(parent.find('input[name="product[commission_percent]"]'));
            var finalPriceForCommission = parent.find('input[name="product[special_price]"]').val();

            var costValue = (100 - parent.find('input[name="product[commission_percent]"]').val())/100*finalPriceForCommission;
            if(isNaN(costValue)){
                parent.find('input[name="product[cost]"]').val('').trigger('change');
            }else{
                costValue = Math.round(costValue);
                parent.find('input[name="product[cost]"]').val(costValue).trigger('change');
            }
            setFixedCommission(parent);
        }else{
            enableField(parent.find('input[name="product[cost]"]'));
            disableField(parent.find('input[name="product[commission_percent]"]'));
            /** Commission source */
            setManullyInputCommission(parent);
        }
    }

    function disableField(obj){
        obj.attr('readonly', 'readonly');
        obj.css('background-color', '#ccc');
    }
    function enableField(obj){
        obj.removeAttr('readonly');
        obj.removeAttr('style');
    }
    function getCommissionSource(commissionPercent){
        /** Commission source */
        var sellerCommissionRate = sellerCommissionData.commission_rate;
        var sellerDefaultCommissionRate = sellerCommissionData.default_commission_rate;
        var commissionSourceTxt = $t('N/A');
        var gCommissionSource = '';
        if(commissionPercent == sellerCommissionRate && typeof sellerCommissionData.active_contract != 'boolean'){
            gCommissionSource = 2;
        }else if(commissionPercent == sellerCommissionRate && typeof sellerCommissionData.active_contract == 'boolean'){
            gCommissionSource = 3;
        }else{
            gCommissionSource = 1;
        }
        commissionSourceTxt = showCommissionSource(gCommissionSource);
        return [gCommissionSource, commissionSourceTxt];
    }

    function showCommissionSource(val){
        var commissionSourceTxt = '';
        if(val == 2){
            commissionSourceTxt = $t('Based on Active Period: %1 to %2').replace('%1', sellerCommissionData.active_contract.from).replace('%2', sellerCommissionData.active_contract.to);
        }else if(val == 3){
            commissionSourceTxt = $t('Based on Default Settings');
        }else if(val == 1){
            commissionSourceTxt = $t('Manually Input');
        }
        return commissionSourceTxt;
    }

    function setManullyInputCommission(parent){
        $(parent).find('[data-index="commission_percent"]').find('.admin__field-note').hide();
    }

    function setFixedCommission(parent){
        $(parent).find('[data-index="commission_percent"]').find('.admin__field-note').show();
        $(parent).find('input[name="product[commission_percent]"]').trigger('keyup');
    }
    function showCommissionSourceOnly(parent){
        $(parent).find('[data-index="commission_percent"]').find('.admin__field-note').show();
    }

    $(document).on('change', 'select[name="product[assign_seller][seller_id]"]', function(){
        if($(this).val() == '' || !$('body').hasClass('catalog-product-new')){
            return;
        }
        $.ajax({
            url : getSellerCommissionDataUrl+'seller_id/'+$(this).val()+'/',
            method : "GET",
            dataType: 'json',
            cache: false,
            beforeSend: function (){
                $('body').trigger('processStart');
            },
            complete: function(){
                $('body').trigger('processStop');
            }
        }).done(function(data){
            sellerCommissionData = data;
            $('input[name="product[commission_percent]"]').val(data.commission_rate).trigger('keyup');

            $('body').trigger('processStop');
        }).fail(function(data){
            $('body').trigger('processStop');
        });
    });
});

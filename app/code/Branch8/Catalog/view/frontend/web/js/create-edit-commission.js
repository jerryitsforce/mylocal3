require([
    'jquery',
    'plugins/DOMPurify',
    'mage/translate'
], function($, DOMPurify, $t) {
    'use strict';
    var  runCommissionSourceFirstTime = false;
    jQuery(document).ajaxComplete(function() {
        
        if($('body').hasClass('marketplace-product-add')){
            if(!runCommissionSourceFirstTime || $('#commission_source_input').val() == ''){
                $('input[name="product[commission_percent]"]').trigger('keyup');
                // var commissionSourceData = getCommissionSource($('input[name="product[commission_percent]"').val());
                // var commissionSourceTxt = commissionSourceData[1];
                // var commissionSourceVal = commissionSourceData[0];
                // $('#commission_source').text(DOMPurify.sanitize(commissionSourceTxt));
                // $('input[name="product[commission_source]"]').val(commissionSourceVal).trigger('keyup');
                runCommissionSourceFirstTime = true;
            }
        }

        /** First time load, if cost_setting= manually input, hiding commission source */
        let costSettingLoad = DOMPurify.sanitize($('#edit-product input[name="product[cost_setting]"]:checked').val());
        if(costSettingLoad == 0){/**Manually input */
            setManullyInputCommission('edit-product');
        }
        let costSettingStagingLoad = DOMPurify.sanitize($('#staging-product input[name="product[cost_setting]"]:checked').val());
        if(costSettingStagingLoad == 0){/**Manually input */
            setManullyInputCommission('staging-product');
        }

        // $('input[name="product[price]"]').blur(function(){
        //     let id = $(this).closest('form').attr('id');
        //     if($('#'+id+' input[name="product[special_price]"]').val().trim() != ''){
        //         return;
        //     }
        //     var costSetting = $('#'+id+' input[name="product[cost_setting]"]:checked').val();
        //     if(costSetting == 1){/*fixed*/
        //         var costValue = (100 - $('#'+id+' input[name="product[commission_percent]"]').val())/100*$(this).val();
        //         $('#'+id+' input[name="product[cost]"]').val(costValue).trigger('change');
        //     }else{
        //         var costValue = ($('#'+id+' input[name="product[price]"]').val() - $('#'+id+' input[name="product[cost]"]').val())/$('input[name="product[price]"]').val()*100;
        //         $('#'+id+' input[name="product[commission_percent]"]').val(costValue).trigger('change');
        //     }
        // });
        

        $('input[name="product[commission_percent]"]').keyup(function(){
            let id = DOMPurify.sanitize($(this).closest('form').attr('id')?.toString()),
                costSetting = DOMPurify.sanitize($('#'+id+' input[name="product[cost_setting]"]:checked').val()?.toString());
            if(costSetting == 1){/*fixed*/
                var finalPriceForCommission = DOMPurify.sanitize($('#'+id+' input[name="product[special_price]"]').val()?.toString());
                var costValue = (100 - $(this).val())/100*finalPriceForCommission;
                if(isNaN(costValue)){
                    $('#'+id+' input[name="product[cost]"]').val('').trigger('change');
                }else {
                    costValue = Math.round(costValue);
                    $('#' + id + ' input[name="product[cost]"]').val(costValue).trigger('change');
                }
                var commissionSourceData = getCommissionSource($(this).val());
                var commissionSourceTxt = commissionSourceData[1];
                var commissionSourceVal = commissionSourceData[0];
                $('#'+ id +' .commission_source').text(DOMPurify.sanitize(commissionSourceTxt));
                $('#' + id + ' input[name="product[commission_source]"]').val(commissionSourceVal).trigger('change');
            }
        });

        $('input[name="product[cost]"]').blur(function(){
            let id = DOMPurify.sanitize($(this).closest('form').attr('id')?.toString()),
                costSetting = parseInt($('#'+id+' input[name="product[cost_setting]"]:checked').val(), 10);
            if(costSetting == 0){/*manual*/
                var finalPriceForCommission = DOMPurify.sanitize($('#'+id+' input[name="product[special_price]"]').val()?.toString());
                var costValue = (finalPriceForCommission - $('#'+id+' input[name="product[cost]"]').val())/finalPriceForCommission*100;
                if(isNaN(costValue)){
                    $('#'+id+' input[name="product[commission_percent]"]').val('').trigger('change');
                }else{
                    costValue = Math.round(costValue);
                    $('#'+id+' input[name="product[commission_percent]"]').val(costValue).trigger('change');
                }

            }
        });
        $(document).on('blur', 'input[name="product[special_price]"]', function(){
            let id = DOMPurify.sanitize($(this).closest('form').attr('id')?.toString()),
                costSetting = DOMPurify.sanitize($('#'+id+' input[name="product[cost_setting]"]').val()?.toString()),
                valueForCalc = DOMPurify.sanitize($(this).val()?.toString());

            // if($(this).val().trim() == ''){
            //     valueForCalc = $('#'+id+' input[name="product[price]"]').val();
            // }
            if(costSetting == 1){/*fixed*/
                var commissionPercent = $('#'+id+' input[name="product[commission_percent]"]').val();
                var costValue = (100 - commissionPercent)/100*valueForCalc;
                if(isNaN(costValue)){
                    $('#'+id+' input[name="product[cost]"]').val('').trigger('change');
                }else {
                    costValue = Math.round(costValue);
                    $('#' + id + ' input[name="product[cost]"]').val(costValue).trigger('change');
                }
                
            }else{
                var costValue = (valueForCalc - $('#'+id+' input[name="product[cost]"]').val())/valueForCalc*100;
                if(isNaN(costValue)){

                }
                costValue = Math.round(costValue);
                $('#'+id+' input[name="product[commission_percent]"]').val(costValue).trigger('change');
            }
        });

        $('input[name="product[cost_setting]"]').change(function(){
            let id = DOMPurify.sanitize($(this).closest('form').attr('id')?.toString());
            changeCostSetting($(this).val(), id);
        });
        // changeCostSetting($('#edit-product input[name="product[cost_setting]"]:checked').val(), 'edit-product');
        // changeCostSetting($('#staging-product input[name="product[cost_setting]"]:checked').val(), 'staging-product');
    });

    function getCommissionSource(commissionPercent){
        /** Commission source */
        var sellerCommissionRate = sellerCommissionData.commission_rate;
        var sellerDefaultCommissionRate = sellerCommissionData.default_commission_rate;
        var commissionSourceTxt = $t('N/A');
        var gCommissionSource = '';
        if(commissionPercent == sellerCommissionRate  && typeof sellerCommissionData.active_contract != 'boolean'){
            commissionSourceTxt = $t('Based on Active Period: %1 to %2').replace('%1', sellerCommissionData.active_contract.from).replace('%2', sellerCommissionData.active_contract.to);
            gCommissionSource = 2;
        }else if(commissionPercent == sellerCommissionRate  && typeof sellerCommissionData.active_contract == 'boolean'){
            commissionSourceTxt = $t('Based on Default Settings');
            gCommissionSource = 3;
        }else{
            commissionSourceTxt = $t('Manually Input');
            gCommissionSource = 1;
        }
        return [gCommissionSource, commissionSourceTxt];
    }
    function changeCostSetting(val, id){
        if(val == 1){/*fixed*/
            disableField($('#'+id+' input[name="product[cost]"]'));
            enableField($('#'+id+' input[name="product[commission_percent]"]'));

            var finalPriceForCommission = $('#'+id+' input[name="product[special_price]"]').val();

            var costValue = (100 - $('#'+id+' input[name="product[commission_percent]"]').val())/100*finalPriceForCommission;
            costValue = Math.round(costValue);
            $('#'+id+' input[name="product[cost]"]').val(costValue).trigger('change');
            setFixedCommission(id);
        }else{
            enableField($('#'+id+' input[name="product[cost]"]'));
            disableField($('#'+id+' input[name="product[commission_percent]"]'));
            /** Commission source action */
            setManullyInputCommission(id);
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

    function setManullyInputCommission(id){
        $('#'+id+' #field_commission_source').hide();
    }

    function setFixedCommission(id){
        $('#'+id+' #field_commission_source').show();
        $('#'+id+' input[name="product[commission_percent]"]').trigger('keyup');
    }

});

define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';
    var mixin = {
        
        applyAction: function (actionIndex) {
            if(actionIndex == 'reject_catalogrule_change'){
                var data = this.getSelections();
                var selectedItems = data.selected;
                var modalStr = '';
                $.each(selectedItems, function(k, item){

                    modalStr += '<tr class="reject_popup_row"><td>'+ruleNames[item]+'</td><td><textarea class="mass_reject_reason" name="rule['+item+']" ></textarea></td></tr>';
                });
                $('.reject_popup_row').remove();
                $('#reject_popup_content').append(modalStr);
                var modalOptions = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: 'Reject Catalog rule change',
                    buttons: [{
                        text: $.mage.__('Close'),
                        class: 'modal-close',
                        click: function (){
                            this.closeModal();
                        }
                    },{
                        text: $.mage.__('Send'),
                        class: 'modal-close',
                        click: function (){
                            var isValidReason = true;
                            $.each($('.mass_reject_reason'), function(k, _item){
                                if($(_item).val().trim() == ''){
                                    isValidReason = false;
                                }
                            });
                            if(isValidReason){
                                $('#mass_reject_forrm').submit();
                            }else{
                                alert($.mage.__('Please input the reject reason for all items.'));
                            }
                        }
                    }]
                };
                modal(modalOptions, $('#reject_popup'));
                $("#reject_popup").modal("openModal");
            }else if(actionIndex == 'catalogrule_history_compare'){
                /**  */
                var data = this.getSelections();
                var selectedItems = data.selected;
                if(selectedItems.length != 2){
                    alert($.mage.__('Please select 2 versions to compare.'));
                }else{
                    var action = this.getAction(actionIndex);console.log(action);
                    action.url += 'id/'+ catalogRuleId;
                    this._super(actionIndex);
                }

            }else{
                // Call the original method
                this._super(actionIndex);
            }

            
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
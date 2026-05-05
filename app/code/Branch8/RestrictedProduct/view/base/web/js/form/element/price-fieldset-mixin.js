define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/components/fieldset'
], function (_, uiRegistry, fieldset) {
    'use strict';

    var mixin = {
        doHideShow: function () {
            if (uiRegistry.get(this.hideProduct).value() == 0
                || uiRegistry.get(this.allowDirectLinks).value() == 1
            ) {
                this.visible(true);
            } else {
                this.visible(false);
            }

            setTimeout(() => { 
                var forbidden_page_id = uiRegistry.get("amasty_groupcat_rule_form.amasty_groupcat_rule_form.restriction_action.forbidden_page_id");
                var forbidden_page_id_for_guest = uiRegistry.get("amasty_groupcat_rule_form.amasty_groupcat_rule_form.restriction_action.forbidden_page_id_for_guest");
                var forbidden_action = uiRegistry.get("amasty_groupcat_rule_form.amasty_groupcat_rule_form.restriction_action.forbidden_action");
                var forbidden_specificed_url = uiRegistry.get("amasty_groupcat_rule_form.amasty_groupcat_rule_form.restriction_action.forbidden_specificed_url");

                if(uiRegistry.get(this.allowDirectLinks).value() == 1){
                    forbidden_page_id.visible(false);
                    forbidden_page_id_for_guest.visible(false);
                    forbidden_specificed_url.visible(false);
                    return;
                }
                if(forbidden_action.value() != 1000){
                    if(forbidden_action.value() == 0){
                        forbidden_page_id_for_guest.visible(false);
                        forbidden_page_id.visible(false);
                    } else {
                        forbidden_page_id.visible(true);
                        forbidden_page_id_for_guest.visible(true);
                    }
                    forbidden_specificed_url.visible(false);
                }
            }, 1000);
        },
    };

    return function (target) {
        return target.extend(mixin);
    };

   }
);
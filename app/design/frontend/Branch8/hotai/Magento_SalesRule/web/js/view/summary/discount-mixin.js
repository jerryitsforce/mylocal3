define(function () {
    'use strict';

    var mixin = {

        isDisplayed: function () {
            var extensionAttributes = this.totals().extension_attributes;
            if(this.isFullMode() && typeof extensionAttributes.amrule_discount_breakdown != "undefined" && extensionAttributes.amrule_discount_breakdown.length){
                return true;
            }
            return false; //eslint-disable-line eqeqeq
        },
        getValue: function () {
            if(this.getPureValue() == 0){
                return '';
            }
            return this.getFormattedPrice(this.getPureValue());
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
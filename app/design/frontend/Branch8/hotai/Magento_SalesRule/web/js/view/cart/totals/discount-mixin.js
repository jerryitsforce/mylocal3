define(function () {
    'use strict';

    var mixin = {

        isDisplayed: function () {
            var extensionAttributes = this.totals().extension_attributes;
            if(typeof extensionAttributes.amrule_discount_breakdown != "undefined" && extensionAttributes.amrule_discount_breakdown.length){
                return true;
            }
            return false; //eslint-disable-line eqeqeq
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
define([
    'Magento_Checkout/js/model/totals',
    'mage/translate'
], function (totals, $t) {
    'use strict';

    var mixin = {
        getGrandInclPointLabel: function () {
            return $t('Order Total Incl. Point');
        },

        getLabelExclPoint: function () {
            return $t('Order value');
        },
        getTotalExclPoint: function (){
            var totalInclPoint = totals.getSegment('grand_total').value - parseFloat(this.getPointMoneyValue());//because the point is negative value
            return this.getFormattedPrice(totalInclPoint);
        },
        getLabelPoint: function(){
            return $t('discounted');
        },
        getPointMoneyFormated: function(){
            return this.getFormattedPrice(-this.getPointMoneyValue());
        },
        getPointMoneyValue: function(){
            var pointUsedSegment = totals.getSegment('point_used');
            if(pointUsedSegment){
                return pointUsedSegment.value;
            }
            return 0;
        },

        noPointClass: function () {
            return Number(this.getPointMoneyValue()) === 0 ? 'no-point' : '';
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});

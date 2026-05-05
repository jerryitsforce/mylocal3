/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
var config = {
    map: {
        '*': {
            'cancelParentOrderModal': 'Branch8_MarketPlaceParentOrderFrontendUi/js/cancel-order-modal',
            'b8_history_loadmore': 'Branch8_MarketPlaceParentOrderFrontendUi/js/loadmore',
            'b8GiftActions': 'Branch8_MarketPlaceParentOrderFrontendUi/js/gift-action',
            'returnParentOrderModal': 'Branch8_MarketPlaceParentOrderFrontendUi/js/return-order-modal',
            b8OrderListing: 'Branch8_MarketPlaceParentOrderFrontendUi/js/order-listing'
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'Branch8_MarketPlaceParentOrderFrontendUi/js/validation-mixin': true
            }
        }
    }
};

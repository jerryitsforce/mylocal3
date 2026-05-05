define([
 'Magento_Customer/js/customer-data', 
 'mage/storage',
], function (customerData, storage) {
    'use strict';

    return function preorderReloadSection(config) {
        console.log('preorderReloadSection', config, storage);
        var sections = ['cart', 'wishlist', 'last-ordered-items'];
        if (storage != undefined){
            customerData.invalidate(sections);
            customerData.reload(sections, true);
        }
    };
});

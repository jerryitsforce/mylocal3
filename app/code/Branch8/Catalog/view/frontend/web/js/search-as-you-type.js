define([
  "jquery",
  "Magento_Customer/js/customer-data",
  "magentoStorefrontEvents",
  "dataServicesBase",
  "Branch8_Catalog/js/live-search-autocomplete",
  "domReady!",
], function ($, customerData, magentoStorefrontEvents) {
    'use strict';

    return function searchAsYouType(config) {
      console.log('searchAsYouType config', config);

      localStorage.removeItem('search-history-loaded');
      localStorage.removeItem('history-loaded');
      // window.addEventListener('load', function () {
      //     var sections = ['restrictedproductids','browsinghistory'];
      //     customerData.initStorage();
      //     customerData.invalidate(sections);
      //     customerData.reload(sections, true);
      //     console.log('Reloading sections addEventListener ');
      // });
      
      console.log('Reloading sections: removed manual forced load');  

      var popularKeywords = config.popularSearchTerm; 
      new window.LiveSearchAutocomplete({
          environmentId: config.environmentId,
          websiteCode: config.websiteCode, 
          storeCode: config.storeCode,
          storeViewCode: config.storeViewCode, 
          config: {
              pageSize: config.pageSize, 
              minQueryLength: config.minQueryLength, 
              currencySymbol: config.currencySymbol, 
              currencyRate: config.currencyRate, 
              displayOutOfStock: config.displayOutOfStock, 
              allowAllProducts: config.allowAllProducts
          },
          context: {
              customerGroup: config.customerGroupCode
          },
          popular: {
              showRecent: true,
              popularKeywords: Object.values(popularKeywords),
              showBrowsingHistory: true,
              browsingHistory: window.browsingHistory ? window.browsingHistory : {
                  title: $.mage.__("瀏覽紀錄"),
                  viewAllText:  $.mage.__("查看全部"),
                  viewAllUrl: config.browsingHistoryUrl,
                  displayLimit: 5
              }
          }
      });

      if (!magentoStorefrontEvents) return;

      magentoStorefrontEvents.context.setSearchExtension({
          version: config.moduleVersion
      });

    };
});

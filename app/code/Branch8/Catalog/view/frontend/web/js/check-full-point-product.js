/*jshint browser:true jquery:true expr:true*/
define([
  "jquery",
  "plugins/DOMPurify",
  "domReady!"
], function ($, DOMPurify) {
    'use strict';

    return function checkFullPointProduct(config) {
      var jQ = $.noConflict();
      console.log('checkFullPointProduct config', config);
      // jQ.ajax({
      //     url: config.url,
      //     type: 'get'
      // }).done(function (data) {
      //   console.log('checkFullPointProduct data', data);  
      //     // element.appendTo('.box-tocart .field.qty');
      //     // if(!data.error){
      //     //     const sanitizedData = DOMPurify.sanitize(data.data);
      //     //     element.html(sanitizedData);
      //     //     if(dataInfo?.productType === 'configurable' && data.data){
      //     //         element.show();
      //     //     }
      //     // }
      // });
    };
});

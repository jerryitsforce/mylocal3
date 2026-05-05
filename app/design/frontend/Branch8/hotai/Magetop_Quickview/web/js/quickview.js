/**
 * 
 *
 */
define(
  [
      'jquery',
  ],
  function ($) {
      'use strict';

      $.widget(
          'b8.quickview',
          {
              _create: function () {
                  'use strict';
                  console.log('Quickview');
              }
          }
      );
      return $.b8.quickview;
  }
);

define([
  "jquery",
  'domReady!'
],function($) {
  return function () {
    $('html').addClass('loaded');
  };
});
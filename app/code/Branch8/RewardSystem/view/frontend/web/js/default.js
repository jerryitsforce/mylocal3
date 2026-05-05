require([
    'jquery'
], function ($) {
    $.ajax({
        url: '/ars/track/index',
        type: 'POST',
    }).done(function(response) {
    });
});
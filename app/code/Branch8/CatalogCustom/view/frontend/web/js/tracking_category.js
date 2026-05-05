require([
    'jquery'
], function($) {
    'use strict';
    var refererUrl = document.referrer;
    var baseUrl = decodeURIComponent(BASE_URL);
    /**
     * Check the domain from this website
     */
    if(refererUrl.indexOf(baseUrl) == 0){
        var splitURL = refererUrl.split('?')
        $('#referer_url').val(splitURL[0]);
    }
});
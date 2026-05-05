define([
    'js/idle-time-checker'
], function (logout) {
    'use strict';

    return function autoLogout(config) {
        console.log('call autoLogout', config);
        logout(config);
    };
});

define([
    'jquery'
], function ($) {
    'use strict';

    return {  
        formatPhone: function (phoneNumber) {
            return phoneNumber.replace(/(\d{3})\d{4,5}(\d{2})/, '$1****$2');
        },

        formatEmail: function (email) {
            var emailParts = email.split('@'),
                localPart = emailParts[0],
                domainPart = emailParts[1];

            if (localPart.length > 2) {
                localPart = localPart[0] + '****';
            }

            return localPart + '@' + domainPart;
        },

        formatName: function (name) {
            return 'O'.repeat(name.length - 1) + name.slice(-1);
        },

        formatStreet: function (street) {
            if(street.length <= 4) {
                return street;
            }
            const streetFinal = street.replace(/[\s,]/g, '');
            return '*'.repeat(streetFinal.length - 4) + streetFinal.slice(-4);
        },
    };
});

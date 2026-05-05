define([
    "uiComponent",
    "jquery",
    'ko',
    'Magento_Customer/js/customer-data',
    'mage/translate',
], function(Component, $, ko, customerData, _) {
    "use strict"

    return Component.extend({
        defaults: {
            template: "Branch8_PageBuilder/memberbar.html",
            recs: [],
        },
        initialize: function(config) {
            this._super(config)
            this.firsttext = config.firsttext
            this.secondtext = config.secondtext
            this.pointtext = config.pointtext
            this.tickettext = config.tickettext
            this.firstbutton = config.firstbutton
            this.firstbuttonurl = config.firstbuttonurl
            this.secondbutton = config.secondbutton
            this.secondbuttonurl = config.secondbuttonurl
            this.nonlogintext = config.nonlogintext
            this.nonloginfirstbutton = config.nonloginfirstbutton
            this.nonloginfirstbuttonurl = config.nonloginfirstbuttonurl
            this.nonloginsecondbutton = config.nonloginsecondbutton
            this.nonloginsecondbuttonurl = config.nonloginsecondbuttonurl
            this.pointUrl = config.pointUrl;
            this.ticketUrl = config.ticketUrl;

            this.customer = customerData.get('customer');

            this.time = function() {
                // var greeting = this.hotaipoints().greeting || '';
                // if (greeting) {
                //     return greeting;
                // }
                const d = new Date();
                let hour = d.getHours();
                if (hour < 12) {
                    return $.mage.__("Good morning");
                } else if (hour < 18) {
                    return $.mage.__("Good afternoon");
                } else {
                    return $.mage.__("Good evening");
                }
            }

            return this
        },

        isLogin: function() {
            var customerInfo = customerData.get('customer')();
            console.log('Customer Info:', customerInfo);
            return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
        },

        getWelcomeText: function(){
            var customerInfo = customerData.get('customer')();
            if(this.firsttext){
                return this.firsttext + ', ' + customerInfo.fullname;
            }else{
                return this.time() + ', ' + customerInfo.fullname;
            }

        },

        getNonLoginWelcomeText: function(){
            if(this.nonlogintext){
                return this.nonlogintext;
            }else{
                return this.time() + ', ' + $.mage.__("visitor");
            }
        },

        getSecondText: function() {
            return this.stringInject(this.secondtext, [this.customer().expirepoint])
        },

        stringInject: function(str, arr) {
            if (typeof str !== 'string' || !(arr instanceof Array)) {
                return false;
            }

            return str.replace(/({\d})/g, function(i) {
                return arr[i.replace(/{/, '').replace(/}/, '')];
            });
        },

    })
})

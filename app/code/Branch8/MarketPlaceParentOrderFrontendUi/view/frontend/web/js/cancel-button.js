define([
    'jquery',
    'jquery/ui',
    'domReady!'
], function ($) {
    'use strict';
    $.widget(
        'b8.rmaButton',
        {
            _create: function () {
                // console.log('Cancel Button', this.element, this.options);
               var currentProcessStepEle = $(this.element).parents('.order-item').find('.ProgressBar-step[data-current=1][data-status="processing"]');

               if(currentProcessStepEle.length) {
                    var status = currentProcessStepEle.data('status');
                    var time = currentProcessStepEle.data('time');
                    var type = currentProcessStepEle.data('type');
                    var needCheck = false;
                    if(status === 'processing') {
                        needCheck = true;
                    }
                    var rmaButtonDelay = parseInt(this.options.delayTime);
                    var currentStatusTime = new Date(time).getTime();
                    var checkTime = currentStatusTime + (rmaButtonDelay * 60 * 1000);
                    // Get the current time in Taiwan
                    const taiwanTime = new Date().toLocaleString("en-US", { timeZone: "Asia/Taipei" });
                    // const currentTime = new Date(taiwanTime);
                    var currentTime = new Date(taiwanTime).getTime();
                    var countdownTime = checkTime - currentTime;
                    // console.log('Current time:', needCheck, currentStatusTime, checkTime, currentTime, countdownTime);
                    // console.log('RMA Button Delay:', this.formatDate(new Date(currentStatusTime)), this.formatDate(new Date(checkTime)), this.formatDate(new Date(currentTime)), this.formatDate(new Date(countdownTime)));

                    if(currentTime > checkTime || !needCheck) {
                        $(this.element).removeClass('hidden');
                    } else {
                        var self = this;
                        var countdownInterval = setInterval(function () {
                            // var now = new Date.toLocaleString("en-US", { timeZone: "Asia/Taipei" }).getTime();
                            countdownTime = countdownTime - 1000;
                
                            // console.log('check RMA button', checkTime, countdownTime);
                            if (countdownTime <= 0) {
                                clearInterval(countdownInterval);
                                // console.log('Show RMA button');
                                $(self.element).removeClass('hidden');
                            }
                        }, 1000);
                    }
               } else {
                    $(this.element).removeClass('hidden');
               }
            },

            formatDate: function(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            }
        }
    );
    return $.b8.rmaButton;
});

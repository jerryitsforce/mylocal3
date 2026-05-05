define([
  "jquery",
  "domReady!"
], function ($) {
    'use strict';

    return function notificationBox(config) {
       $(".defer").on("click",function () {
            //hide popup
            $(".noti-popup").hide();
            //get time to resend popup
            var timeResendPopup = parseInt(config.timeResendPopup) * 1000;
            //get current time
            var time = new Date();
            var currentTime = time.getTime();
            //save current time to localStorage
            localStorage.setItem('timeDefer', currentTime);

            //resend popup if customer still at current page
            setTimeout(function () {
                $(".noti-popup").show();
            }, timeResendPopup);
        });

        $(".close").on("click",function () {
            $(".noti-popup").hide();
        });

        $("#mgn-allow-receive-notice").on("click",function (){
            $(".noti-popup").hide();
            if (Notification.permission === 'default') {
                new Promise(function (resolve, reject) {
                    const permissionResult = Notification.requestPermission(function (result) {
                        resolve(result);
                    });
                    if (permissionResult) {
                        permissionResult.then(resolve, reject);
                    }
                }).then(function (permissionResult) {
                    if (permissionResult !== 'granted') {
                        console.log('We weren\'t granted permission.');
                    } else {
                        const firebaseConfig = {
                            messagingSenderId: "<?=$block->getSenderId()?>",
                        };
                        firebase.initializeApp(firebaseConfig);
                        const messaging = firebase.messaging();
                        messaging.requestPermission().then(function () {
                            return messaging.getToken();
                        }).then(function (token) {
                            console.log(token);
                            $.ajax({
                                method: "POST",
                                dataType: "json",
                                url: BASE_URL + "notibox/customer/saveToken",
                                data: {
                                    token: token
                                }
                            })
                        }).catch(function (err) {
                            console.log('error:', err);
                        });
                    }
                });
            }
        });
    };
});

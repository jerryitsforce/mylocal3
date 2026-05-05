define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/modal',
], function ($, urlBuilder, modal) {
    'use strict';

    return function (config) {
        const timeout = parseInt(config.timeout) * 60;
        let idleTime = 0;
        let modalElement = $(`#${config.modalId}`);
        let idleInterval;
        let isPopupShowing = false;

        //Event used to inform other tabs to logout
        const eventLogout = 'idle-timeout-logout-event';

        //Event used to inform other tabs to reset timer
        const eventResetTimer = 'idle-timeout-reset-timer-event';

        if (config.isLoggedIn === 'true') {
            idleInterval = setInterval(checkIdleTime, 1000);

            $(document).on('mousemove keydown scroll touchstart touchmove click', function () {
                resetTimer();
            });

            window.addEventListener('storage', onStorage);
        } else {
            idleInterval && clearInterval(idleInterval);
            window.removeEventListener('storage', onStorage, false);
        }


        /**
         * Trigger by other tabs
         * Handle storage event
         * @param event
         */
        function onStorage(event) {
            if (event.key === eventLogout) { // logout from other tabs
                idleInterval && clearInterval(idleInterval);
                showNotificationModal()
            }
            if (event.key === eventResetTimer) { // another tab is active
                resetTimer();
            }
        }

        /**
         * Reset idle time
         */
        function resetTimer() {
            idleTime = 0;
            localStorage.setItem(eventResetTimer, Date.now());
        }

        function checkIdleTime() {
            idleTime++;
            // console.log('Idle time', idleTime);
            if (idleTime >= timeout) {
                clearInterval(idleInterval);
                informDoLogoutToOtherTabs();
                showNotificationModal();
                logout()
            }
        }

        /**
         * Inform to other tabs to logout
         */
        function informDoLogoutToOtherTabs() {
            localStorage.setItem(eventLogout, Date.now());
        }

        function logout() {
            console.log('Logout after idle time', config);
            $.ajax({
                url: urlBuilder.build('customer/ajax/logout'),
                type: 'GET',
                success: function(response) {
                    if (response?.message === 'Logout Successful') {
                        // comment this line - var options is not define
                        //modal(modalElement, options).openModal();
                    } else {
                        window.location.href = urlBuilder.build('customer/account/logout');
                    }
                },
                error: function() {
                    window.location.href = urlBuilder.build('customer/account/logout');
                }
            }).always(() => showNotificationModal);
        }


        function showNotificationModal() {
            if(isPopupShowing) {
                return;
            }

            isPopupShowing = true;
            const options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                modalClass: 'modal-custom idle-timeout-modal',
                responsiveClass: '',
                title: $.mage.__('停留過久已被登出'),
                closed: function (){
                    window.location.href = urlBuilder.build('customer/account/logout');
                },
                buttons: [{
                    text: $.mage.__('確定'),
                    class: 'action primary action-primary',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };

            let popup = modal(options, modalElement);
            modalElement.modal('openModal');
        }
    };
});

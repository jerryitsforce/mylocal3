define(
    [
        'jquery',
        'ko',
        'underscore',
        './socket-provider',
        'mage/url',
        'Magento_Ui/js/modal/modal',
        'Magento_Customer/js/customer-data',
        'js-storage/js.storage'
    ],
    function ($, ko, _, socketProvider, urlBuilder, modal, customeData) {
        'use strict';

        function isWebView() {
            const ua = navigator.userAgent || '';
            // Android WebView
            if (ua.includes('HotaiApp')) return true;
            return false;
        }

        function getCookie(name) {
            if (typeof document === 'undefined') return null;

            const cookies = document.cookie ? document.cookie.split('; ') : [];

            for (let i = 0; i < cookies.length; i++) {
                const parts = cookies[i].split('=');
                const key = decodeURIComponent(parts.shift());

                if (key === name) {
                    return decodeURIComponent(parts.join('='));
                }
            }
            return null;
        }

        let isPopupShowing = false;
        let modalElement = $('div#single-login-timeout-modal');
        /**
         *
         */
        return {

            getLoginToken: function () {
                return getCookie('session_login_token')
            },

            socketId: null,
            /**
             *
             */
            config: {},
            /**
             *
             */
            socketObject: null,
            /**
             *
             * @param socketObject
             * @returns {exports}
             */
            setSocketObject: function (socketObject) {
                this.socketObject = socketObject;
                return this;
            },
            /**
             *
             * @param config
             */
            setConfig: function (config) {
                this.config = config;
            },
            /**
             *
             */
            logout: function () {
                const self = this;
                $.ajax({
                    url: urlBuilder.build('customer/ajax/logout'),
                    type: 'GET',
                    success: function (response) {
                        // const logoutUrl = urlBuilder.build('customer/account/logout');
                        self.showModal();
                        if (!isWebView() || self.config.showTimerOnMobile) {
                            self.timer();
                        }
                    },
                    error: function (xhr, status, error) {
                        if (self.config.debug_frontend) {
                            console.log("ForceLogoutException", e);
                            alert(xhr.responseText);
                        } else {
                            window.location.href = urlBuilder.build('customer/account/logout');
                        }
                    }
                })
            },
            /**
             *
             * @param Object detail {
             *     email,
             *     token
             * }
             */
            startNewSession: function (detail) {
                const socketObject = this.socketObject;
                if (socketObject) {
                    try {
                        const result = this.socketObject.emit('customer:start:newSession', detail);
                    } catch (e) {
                        console.log('Failed Start Session', e);
                    }

                }
            },
            /**
             *
             */
            listenLogoutEvents: function () {
                const self = this;
                let socket = this.socketObject;
                if (socket !== false) {
                    socket.on('customer:received:force-logout', function (data) {
                        const {message, newToken, forced} = data;
                        const currentToken = self.getLoginToken();
                        if (self.config.debug_frontend) {
                            alert(message + '\n' + currentToken + '\n' + newToken);
                            return;
                        }
                        if ((newToken !== currentToken) || forced === true) {
                            self.logout();
                        }
                    });
                }
            },
            /**
             *
             */
            timer: function (logoutUrl) {
                const self = this;
                const inteval = this.config?.interval || 5;
                const timerElement = $('div#single-login-timeout-modal .counter');
                let timeLeft = this.config?.interval;
                const timer = setInterval(() => {
                    timerElement.html(timeLeft);
                    timeLeft--;
                    if (timeLeft < 0) {
                        clearInterval(timer);
                        logoutUrl ? window.location.href = logoutUrl : window.location.reload(true);
                    }
                }, 1000);
            },
            /**
             *
             * @returns {exports}
             */
            showModal: function () {
                const self = this;
                if (isPopupShowing) {
                    return;
                }
                isPopupShowing = true;
                const notifyText = self.config.showTimerOnMobile ? self.config.mobileNotifyText : self.config.defaultNotifyText;
                $("#single-login-notify").html(notifyText);
                const options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'modal-custom single-login-timeout-modal',
                    responsiveClass: '',
                    title: $.mage.__('Warning'),
                    closed: function () {
                        window.location.href = urlBuilder.build('customer/account/logout');
                    },
                    buttons: [{
                        text: $.mage.__('確定'),
                        class: 'action primary action-primary',
                        click: function () {
                            isPopupShowing = false;
                            this.closeModal();
                        }
                    }]
                };
                let popup = modal(options, modalElement);
                modalElement.modal('openModal');
                return this;
            }
        };
    }
);

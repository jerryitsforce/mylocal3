define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/url',
    'matchMedia',
    'domReady!'
], function ($, confirm, _url) {
    $.widget('b8.confirmLogout', {

        _create: function () {
            $(document).on("click", ".authorization-link .link-logout", function(e) {
                e.preventDefault();

                let url = $(this).attr('href');
                /*
                Magento: logout -> redirect to Logout success
                This Popup ajax to logout, after success ajax, redirect direct to logout again by href
                Need to redirect to Logout success
                 */
                url = _url.build('customer/account/logoutSuccess');
                
                let dataPost = $(this).data("posts");

                confirm({
                    title: $.mage.__('登出'),
                    content: $.mage.__('確定是否登出會員？'),
                    modalClass: 'logout-popup',
                    buttons: [
                        {
                            text: $.mage.__('取消'),
                            class: 'action action-secondary action-dismiss',
                            click: function (event) {
                                this.closeModal(event);
                            }
                        },
                        {
                            text: $.mage.__('確定'),
                            class: 'action action-primary action-accept',
                            click: function (event)
                            {
                                var modalConfirm = this;
                                $.ajax({
                                    url: _url.build('customer/ajax/logout'),
                                    type: 'GET',
                                    success: function(response) {
                                        if (response?.message === 'Logout Successful') {
                                            modalConfirm.closeModal(event, true);

                                            var msgContainer = $('.page.messages');
                                            msgContainer.append('<div class="messages custom-messages"><div class="message message-success success">' + $.mage.__('您已退出') + '</div></div>');
                                            msgContainer.addClass('__show');
                                            var timeCheck;
                                            clearTimeout(timeCheck);
                                            timeCheck = setTimeout(function () {
                                                msgContainer.removeClass('__show');
                                                msgContainer.find('.custom-messages').remove();
                                            }, 6000);

                                            window.location.reload();

                                        } else {
                                            modalConfirm.closeModal(event, true);
                                            window.location.href = _url.build('customer/account/logout');
                                        }
                                    },
                                    error: function() {
                                        modalConfirm.closeModal(event, true);
                                        window.location.href = _url.build('customer/account/logout');
                                    }
                                });
                            }
                        }]
                });
            });
        }
    });

    return $.b8.confirmLogout;
});

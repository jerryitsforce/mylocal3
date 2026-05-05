/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer-info',
    'Magento_Ui/js/modal/modal',
    'plugins/DOMPurify',
    'domReady!'
], function ($, Component, customerData, customerInfomation, modal, DOMPurify) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.hotaipoints = customerData.get('customer');
            this.customer = customerInfomation.customer();
            this.showButton();
        },

         initObservable: function () {
            this._super();
            const self = this;
            customerInfomation.customer.subscribe(function(newValue) {
            //   console.log('Customer Info data changed:', newValue(), self.customer());
              self.customer = newValue;
              self.showButton();
            });
            self.showButton();
            return this;
        },

        showButton: function() {
            $('#header-hotaipoints').hide();
            $('.header-right').removeClass('showing');
            $('#header-dropdown-hotaipoints').hide();
            // console.log('Customer Info:', typeof this.customer, (typeof this.customer == 'function')? this.customer() : '');
            if((typeof this.customer == 'function' && this.customer()?.data_id && this.isLogin()) || (/^\/checkout\/?$/.test(window.location.pathname))) {
                var customerInfo = customerData.get('customer')();
                // console.log('Customer Info:', customerInfo);
                $('#header-hotaipoints').removeAttr('style');
                $('.header-right').addClass('showing');
                $('#header-dropdown-hotaipoints').removeAttr('style');
                if(customerInfo?.customer_point_formated) {
                    $('#header-hotaipoints .hotai-customer-points > span').text(customerInfo?.customer_point_formated);
                    $('#header-dropdown-hotaipoints .hotai-totalpoints').text(customerInfo?.customer_point_formated);
                }
            }

        },

        isLogin: function() {
            var customerInfo = customerData.get('customer')();
            return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
        },

        /**
         * 顯示訊息彈窗（共用元件）
         * @param {Object} data - 彈窗資料
         * @param {string} [data.title] - 彈窗標題，預設為「訊息通知」
         * @param {string} data.content - 彈窗內容（必填）
         */
        showMessagePopup: function (data) {
            data = data || {};
            
            if (!data.content) {
                return;
            }
            
            var $modal = $('#show-message');
            
            // 如果 modal 不存在，動態創建
            if ($modal.length === 0) {
                $modal = $('<div id="show-message" class="modal-hide"><div class="modal-custom-content"></div></div>');
                $('body').append($modal);
            }
            
            // 設定 title（使用預設值或傳入的值）
            var title = data.title || $.mage.__('訊息通知');
            var content = data.content;

            // 更新內容（使用 DOMPurify 清理 HTML，防止 XSS）
            $modal.find('.modal-custom-content').html(DOMPurify.sanitize(content));
            
            // 初始化或更新 modal
            if (!$modal.data('mageModal')) {
                modal({
                    type: 'popup',
                    responsive: false,
                    title: title,
                    modalClass: 'modal-custom hotaipoint-sms-limit-popup',
                    buttons: [{
                        text: $.mage.__('我知道了'),
                        class: 'action primary action-primary',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                }, $modal);
            } else {
                // 更新已初始化 modal 的 title
                $modal.closest('.modal-inner-wrap').find('.modal-title, .modal-header .title').text(title);
            }

            $modal.modal('openModal');
        }
    });
});

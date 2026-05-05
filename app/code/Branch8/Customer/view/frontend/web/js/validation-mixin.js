define([
    'jquery',
    'plugins/DOMPurify'
], function ($, DOMPurify) {
    'use strict';

    return function (targetWidget) {
        $.validator.addMethod(
            'validate-taiwan-id',
            function (value, element) {
                return verifyId(value);
            },
            $.mage.__('Please enter a valid ID number.')
        );

        $.validator.addMethod(
            'validate-sms-code',
            function (value) {
                if(value.trim() === '') {
                    return true;
                }
                return /^\d{6}$/.test(value);
            },
            $.mage.__("請輸入正確的簡訊驗證碼")
        );

        $.validator.addMethod(
            'validate-tw-phone',
            function (value) {
                if(value.trim() === '') {
                    return true;
                }
                return /^09\d{8}$/.test(value);
            },
            $.mage.__("請輸入正確的手機號碼")
        );

        $.validator.addMethod(
            'validate-birthday',
            function (value, element) {
                var year = $('#year').val(),
                    month = $('#month').val(),
                    day = $('#day').val(),
                    date = new Date(year, month - 1, day);

                if (date.getFullYear() != year || date.getMonth() + 1 != month || date.getDate() != day) {
                    return false;
                }
                if (year.length < 4) {
                    return false;
                }
                return true;
            },
            $.mage.__('Please enter a valid date.')
        );

        $.validator.addMethod(
            'validate-tw-barcode',
            function (value) {
                return /^\/[0-9A-Z.\-+]{7}$/.test(value);
            },
            $.mage.__("請輸入正確手機條碼載具")
        );

        $.validator.addMethod(
            'validate-nickname',
            function (value) {
                const regex = /[^\u0000-\u007F\u4E00-\u9FFF，。]|[!@#$%^&*()?":{}|<>=+]/;
                return !regex.test(value);
            },
            $.mage.__("請勿輸入全形文字以及特殊字元")
        );

        $.validator.addMethod(
            'validate-fullwidth-special',
            function (value) {
                const regex = /[^\u0000-\u007F\u4E00-\u9FFF，。]|[!@#$%^&*()?":{}|<>=+]/;
                return !regex.test(value);
            },
            $.mage.__("請勿輸入全形文字以及特殊字元")
        );

        $.validator.addMethod(
            'validate-email-fullwidth-special',
            function (value) {
                const regex = /[^\u0000-\u007F\u4E00-\u9FFF]|[!#$%^&*()?,":{}|<>=+]/;
                return !regex.test(value);
            },
            $.mage.__("請勿輸入全形文字以及特殊字元")
        );

        $.validator.addMethod(
            'validate-max-300',
            function (value) {
                return value.length <= 300;
            },
            $.mage.__("限300字元(非必填)")
        );

        $.validator.addMethod(
            'validate-unsupported-city',
            function (value, element) {
                const regionId = $(element).parents('.fieldset').find('#region_id')?.val() || $(element).parents('.fieldset').find('#return_or_exchange_order_address_region')?.val() || '';
                console.log('validate-unsupported-city', value, regionId, $(element), $(element).attr('id'));
                const eleId = $(element).attr('id');
                const unsupported = [{regionId: '1174', region: '臺東縣', city: '綠島鄉'}, {regionId: '1174', region: '臺東縣', city: '蘭嶼鄉'}, {regionId: '1173', region: '屏東縣', city: '琉球鄉'}];

                const found = unsupported.find(
                    item => eleId === 'return_or_exchange_order_address_city' ? item.region === regionId && item.city === value : item.regionId === regionId && item.city === value
                );

                // if (found) {
                //     showMessage(
                //         '不提供外島配送，不讓顧客下單。',
                //         'error'
                //     );
                // }

                return !found;
            },
            $.mage.__("不提供外島配送，不讓顧客下單。")
        );

        return targetWidget;
    }

    function verifyId(id) {
        id = id.trim();

        if (id.length != 10) {
            console.log("Fail, incorrect length");
            return false
        }


        const countyCode = id.charCodeAt(0);
        if (countyCode < 65 | countyCode > 90) {
            console.log("Fail, the initial English code name, the county and city are incorrect");
            return false
        }

        const genderCode = id.charCodeAt(1);
        if (genderCode != 49 && genderCode != 50) {
            console.log("Fail, gender code is incorrect");
            return false
        }

        const serialCode = id.slice(2)
        for (var i in serialCode) {
            const c = serialCode.charCodeAt(i);
            if (c < 48 | c > 57) {
                console.log("Fail, non-numeric characters appear in the numeric area");
                return false
            }
        }

        const conver = "ABCDEFGHJKLMNPQRSTUVXYWZIO"
        const weights = [1, 9, 8, 7, 6, 5, 4, 3, 2, 1, 1]

        id = String(conver.indexOf(id[0]) + 10) + id.slice(1);

        var checkSum = 0;
        for (var i = 0; i < id.length; i++) {
            const c = parseInt(id[i])
            const w = weights[i]
            checkSum += c * w
        }

        const verification = checkSum % 10 == 0

        if (verification) {
            console.log("Pass");
        } else {
            console.log("Fail, check code error");
        }

        return verification
    }

    function showMessage (message, type = 'error') {
        var jQ = $.noConflict();
        var msgContainer = jQ('.page.messages');
        var messageContent = jQ('<div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'"/>');
        messageContent.text(DOMPurify.sanitize(message));
        const config = {
            ALLOWED_TAGS: ['div'],
            ALLOW_DATA_ATTR: true
        };
        const content = DOMPurify.sanitize(messageContent.prop('outerHTML'), config);
        msgContainer.append('<div class="messages custom-messages">' + content + '</div>');
        msgContainer.addClass('__show');
        var timeCheck;
        clearTimeout(timeCheck);
        timeCheck = setTimeout(function () {
            msgContainer.removeClass('__show');
            msgContainer.find('.custom-messages').remove();
        }, 3000);
    }
});

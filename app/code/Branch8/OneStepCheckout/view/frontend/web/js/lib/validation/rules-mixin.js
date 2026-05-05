define([
    'jquery',
    'underscore',
    'mage/translate'
], function ($, _, $t) {
    'use strict';

    return function (rules) {
        // console.log('rules-mixin.js', {rules});
        return _.mapObject({
            ...rules,
            'validate-fullwidth-special': [
                function (value) {
                    const regex = /[^\u0000-\u007F\u4E00-\u9FFF]|[!@#$%^&*(),.?":{}|<>]/;
                    return !regex.test(value);
                },
                $.mage.__("請勿輸入全形文字以及特殊字元")
            ],
            'validate-sms-code': [
                function (value) {
                    return /^\d{6}$/.test(value);
                },
                $.mage.__("請輸入正確的簡訊驗證碼")
            ],
            'validate-taiwan-id': [
                function (value, element) {
                    return verifyId(value);
                },
                $.mage.__('Please enter a valid ID number.')
            ],
            'validate-tw-phone': [
                function (value) {
                    if(value.trim() === '') {
                        return true;
                    }
                    return /^09\d{8}$/.test(value);
                },
                $.mage.__("請輸入正確的手機號碼")
            ],
            'validate-birthday': [
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
            ],
            'validate-tw-barcode': [
                function (value) {
                    return /^\/[0-9A-Z.\-+]{7}$/.test(value);
                },
                $.mage.__("請輸入正確手機條碼載具")
            ],
            'validate-email-fullwidth-special': [
                function (value) {
                    const regex = /[^\u0000-\u007F\u4E00-\u9FFF]|[!#$%^&*()?,":{}|<>=+]/;
                    return !regex.test(value);
                },
                $.mage.__("請勿輸入全形文字以及特殊字元")
            ],
            'validate-max-300': [
                function (value) {
                    return value.length <= 300;
                },
                $.mage.__("限300字元(非必填)")
            ]
        }, function (data) {
            return {
                handler: data?.handler?? data[0],
                message: data?.message?? data[1]
            };
        });

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
    };
});

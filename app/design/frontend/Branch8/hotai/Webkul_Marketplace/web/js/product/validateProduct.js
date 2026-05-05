/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'mageUtils'
], function ($, _, utils) {
    'use strict';
    /**
     *
     */
    return function validate(url, data, resolve, reject) {
        //  var save = $.Deferred();
        //data = utils.serialize(utils.filterFormData(data));
        data['form_key'] = window.FORM_KEY;
        $('body').trigger('processStart');
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                data: data,
                type: "POST",

                /**
                 *
                 * @param res
                 */
                success: function (res, xhr) {
                    resolve({
                        'code': 200,
                        res: res,
                        xhr: xhr
                    });
                },
                error: function (xhr, status, error) {
                    reject({
                        'code': xhr.status,
                        xhr: xhr,
                        error: error,
                    });
                },

                /**
                 * Complete callback.
                 */
                complete: function () {
                    $('body').trigger('processStop');
                }
            });
        });
    }
});

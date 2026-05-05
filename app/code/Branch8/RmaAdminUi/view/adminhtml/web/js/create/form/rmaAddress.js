/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'Magento_Ui/js/form/components/group',
    'mage/translate',
    'uiRegistry',
    'Branch8_Rma/js/parseTaiwanAddress'
], function (_, Group, $t, registry,parseTaiwanAddress) {
    'use strict';

    return Group.extend({
        defaults: {
            template: 'Branch8_RmaAdminUi/form/rma-address',
            buttonTitle: $t('Split Address'),
            originAddressLabel: $t('Origin Address')
        },

        initObservable: function () {
            this._super()
                .observe({
                    originAddress: ''
                });
            return this;
        },
        /**
         *
         */
        parseAddress: function () {
            const parentName = this.parentName;
            if (this.elems().length) {
                this.originAddress(this.elems()[0].value());
                const parse = parseTaiwanAddress(this.elems()[0].value());
                if (parse) {
                    const {zipcode, city, region, address} = parse[0];
                    if (region) {
                        registry.get(parentName + '.' + 'rma_zipcode', function (element) {
                            element.value(zipcode);
                        })
                        registry.get(parentName + '.' + 'rma_region', function (element) {
                            element.value(region);
                        })
                        registry.get(parentName + '.' + 'rma_city', function (element) {
                            element.value(city);
                        })
                        this.elems()[0].value(address)

                    }
                }
            }
        }
    });
});

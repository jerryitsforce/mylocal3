define([
    'jquery',
    'Magento_Ui/js/form/element/multiselect',
    'uiRegistry',
    'Magento_Ui/js/modal/alert'
], function ($, Select, registry, alert) {
    'use strict';

    return Select.extend({
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            const self = this
            this._super();
            const selected = this.value();
            const defaultNoticationType = registry.get(this.parentName + '.' + 'notification_type', function (component) {
                self.notifiTypeChange(component.value());
            });
            this.value.subscribe(function (newValues) {
                self.showTotalItemText();
            })
            this.showTotalItemText();
            return this;
        },
        /**
         *
         * @param value
         */
        setTotalOneIds: function (value) {
            this.totalOneIds(value ? value : 0);
            this.showNotice();
        },
        /**
         *
         */
        showTotalItemText: function (showPopup) {
            const selected = this.value();
            if (selected.includes(this.OneIdCustomerGroup)) {
                this.totalItemText($.mage.__('%1 One Id Items').replace('%1', this.totalOneIds()));
            } else {
                this.totalItemText('');
            }
            if (showPopup) {
                alert(
                    {
                        title: 'Warning',
                        content: "<div class='wk-mprma-warning-content'>" + $.mage.__('%1 Total Items imported').replace('%1', this.totalOneIds()) + "</div>"
                    }
                );
            }
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            const dataProvider = registry.get('magenest_notification_newaction.notification_form_data_source');
            return this._super()
                .observe({
                    totalOneIds: dataProvider.data.defaultTotalOneIds ? dataProvider.data.defaultTotalOneIds : 0,
                    totalItemText: '',
                    resultLink: ''
                });
        },
        /**
         *
         * @param value
         */
        notifiTypeChange: function (value) {
            const currentValue = this.value(), option = $("option[value=ONEIDGROUP]");
            const self = this;
            if (!option.length) {
                return;
            }
            if (this.NOTIFICATIONTYPE_PERMIT.includes(value)) {
                option.attr('disabled', true);
                const items = self.value();
                if (items.length > 0) {
                    const valuesToRemove = [self.OneIdCustomerGroup]
                    const filteredItems = items.filter(item => !valuesToRemove.includes(item))
                    self.value(filteredItems);
                }
            } else {
                option.removeAttr('disabled');
            }
        }
    })
})

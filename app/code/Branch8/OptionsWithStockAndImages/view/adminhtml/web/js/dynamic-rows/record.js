/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/dynamic-rows/record',
    'uiRegistry',
    'jquery'
], function (Record, registry, $) {
    'use strict';

    return Record.extend({

        initialize: function () {
            var self = this;
            this._super();
            this.fieldDepend(self);
            return this;
        },

        /**
         * Defensive override to prevent crashes if column index doesn't exist
         */
        setVisibilityColumn: function (index, state) {
            var elems = this.elems();
            if (elems && elems[index]) {
                this._super(index, state);
            }
            return this;
        },

        /**
         * Defensive override to prevent crashes if column index doesn't exist
         */
        setDisabledColumn: function (index, state) {
            var elems = this.elems();
            if (elems && elems[index]) {
                this._super(index, state);
            }
            return this;
        },

        /**
         *
         * @param index
         * @param classEs
         */
        addClass: function (index, classes) {
            index = ~~index;
            console.log({
                element:this.elems()[index]
            });
            //    this.elems()[index].additionalClasses('');
        },
        /**
         *
         * @param self
         * @returns {*}
         */
        fieldDepend: function (self) {
            setTimeout(function () {
                var data = self.data();

                self.setVisibilityColumn(1, false);
                self.setVisibilityColumn(2, false);

                // Set disabled for select box and set drop_down value
                if (self.parentComponent().containers.length) {
                    if (self.parentComponent().containers[0].elems().length) {
                        if (self.parentComponent().containers[0].elems()[0].elems().length) {
                            var selecbox = self.parentComponent().containers[0].elems()[0].elems()[2];
                            if (selecbox.componentType === "form.select") {
                                selecbox.disabled(true);
                                selecbox.value('drop_down');
                            }
                            var checkbox = self.parentComponent().containers[0].elems()[0].elems()[3];
                            if (checkbox.componentType === "form.checkbox") {
                                checkbox.disabled(true);
                                checkbox.value('1');
                            }
                        }
                    }
                }

                if (data.is_bought !== undefined && data.is_bought === '1') {
                    self.addClass();
                    self.setDisabled(true);
                    self.setDisabledColumn(6, false);
                    self.setVisibilityColumn(7, false);

                    var index = self.parentComponent().dataScope;
                    var numbers = index.match(/\d+/g);
                    if (numbers !== null) {
                        var id = numbers[0];
                        var elm = $('[name="product[options][' + id + '][title]"]').parents('td.admin__collapsible-block-wrapper');
                        elm.each(function () {
                            $(this).find('.action-delete[data-index="delete_button"]').hide();
                            $(this).find('input').addClass('custom-option-input');
                        });
                    }

                    //Disable add new custom option
                    $('[data-index=button_add]').hide();
                    $('[data-index=button_import]').hide();
                }
            }, 100);
            return this;
        }
    });

});

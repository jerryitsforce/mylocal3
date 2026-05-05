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

        fieldDepend: function (self) {
            setTimeout(function () {

                // Set disabled for select box and set drop_down value
                if(self.elems()[0].containers.length){
                    if(self.elems()[0].containers[0].elems().length){
                        if(self.elems()[0].containers[0].elems()[0].elems().length){
                            var selecbox = self.elems()[0].containers[0].elems()[0].elems()[0].elems()[2];
                            if(selecbox.componentType === "form.select"){
                                selecbox.disabled(true);
                                selecbox.value('drop_down');
                            }
                            var checkbox = self.elems()[0].containers[0].elems()[0].elems()[0].elems()[3];
                            if(checkbox.componentType === "form.checkbox"){
                                checkbox.disabled(true);
                                checkbox.value('1');
                            }
                            console.log({
                                checkbox:checkbox,
                                selecbox:selecbox
                            })
                        }
                    }
                }
            }, 100);
            return this;
        }
    });

});

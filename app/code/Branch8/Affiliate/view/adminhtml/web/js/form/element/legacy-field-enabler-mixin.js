define(['jquery'], function ($) {
    'use strict';

    var b8LegacyFormFieldEnabler = {
        _bind: function () {
            this._on({
                change: function (event) {
                    var value = $(event.currentTarget).val();
                    console.log(value);

                    if (!_.isNull(this.options.depFields)
                        && _.has(this.options.map, value)
                    ) {
                        $(this.options.depFields).prop('disabled', this.options.map[value]);
                        if(this.options.map[value]){
                            $(this.options.depFields).val($(this.options.depFields).attr('value')).trigger('change');
                            $(this.options.depFields).removeClass('required-entry');
                        }else{
                            $(this.options.depFields).addClass('required-entry');
                        }

                    }
                }
            });
        }
    };

    return function (targetWidget) {
        $.widget('mage.amastyLegacyFormFieldEnabler', targetWidget, b8LegacyFormFieldEnabler);

        return $.mage.amastyLegacyFormFieldEnabler;
    };
});
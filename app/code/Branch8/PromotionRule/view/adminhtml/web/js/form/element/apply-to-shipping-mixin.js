define([], function () {
    'use strict';

    return function (Checkbox) {
        return Checkbox.extend({
            /**
             * Custom logic to toggle disabled state.
             */
            toggleDisabled: function (action) {
                this.disabled(true);
            },
        });
    };
});

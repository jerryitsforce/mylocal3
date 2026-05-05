require([
    'jquery',
    'plugins/DOMPurify'
], function($, DOMPurify) {
    var jQ = $.noConflict();
    jQ(document).ajaxComplete(function() {
        jQ('input[name="created_at[from]"]').attr('autocomplete', 'off');
        jQ('input[name="created_at[to]"]').attr('autocomplete', 'off');
        jQ('input[name="gift_confirmed_at[from]"]').attr('autocomplete', 'off');
        jQ('input[name="gift_confirmed_at[to]"]').attr('autocomplete', 'off');
    });
});
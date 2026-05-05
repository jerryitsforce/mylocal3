/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

define([
    'jquery'
], function (jQuery) {

    var categorySubmit = function () {
        var activeTab = $('active_tab_id');
        if (activeTab) {
            if (activeTab.tabsJsObject && activeTab.tabsJsObject.tabs('activeAnchor')) {
                activeTab.value = activeTab.tabsJsObject.tabs('activeAnchor').prop('id');
            }
        }

        // Submit form
        jQuery('#category_edit_form').trigger('submit');
    };

    return function (config, element) {
        config = config || {};
        jQuery(element).on('click', function (event) {
            categorySubmit(config.url, config.ajax);
        });
    };
});

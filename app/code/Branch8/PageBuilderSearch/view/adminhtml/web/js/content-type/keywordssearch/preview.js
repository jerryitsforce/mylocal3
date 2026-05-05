define([
    "Magento_PageBuilder/js/content-type/preview",
    "Magento_PageBuilder/js/content-type-toolbar",
    "Magento_PageBuilder/js/events",
    "Magento_PageBuilder/js/content-type-menu/hide-show-option",
], function(
    PreviewBase,
    Toolbar,
    events,
    hideShowOption,
) {
    "use strict"

    /**
     * Quote content type preview class
     *
     * @param parent
     * @param config
     * @param stageId
     * @constructor
     */
    function Preview(parent, config, stageId) {
        PreviewBase.call(this, parent, config, stageId)
        this.toolbar = new Toolbar(this, this.getToolbarOptions())
    }

    var $super = PreviewBase.prototype

    Preview.prototype = Object.create(PreviewBase.prototype)

    /**
     * Bind any events required for the content type to function
     */
    Preview.prototype.bindEvents = function() {
        var self = this
        PreviewBase.prototype.bindEvents.call(this)
    }

    /**
     * Stop event to prevent execution of action when editing text area
     *
     * @returns {boolean}
     */
    Preview.prototype.stopEvent = function() {
        event.stopPropagation()
        return true
    }

    /**
     * Modify the options returned by the content type
     *
     * @returns {*}
     */
    Preview.prototype.retrieveOptions = function() {
        var options = $super.retrieveOptions.call(this, arguments)

        options.hideShow = new hideShowOption({
            preview: this,
            icon: hideShowOption.showIcon,
            title: hideShowOption.showText,
            action: this.onOptionVisibilityToggle,
            classes: ["hide-show-content-type"],
            sort: 40,
        })
        return options

        // Change option menu icons
        options.remove.icon = "<i class='icon-admin-pagebuilder-error'></i>"

        // Change tooltips
        options.edit.title = "Open Editor";

        return options
    }

    /**
     * Allow various options of the content type to be modified from the stage
     *
     * @returns {*[]}
     */
    Preview.prototype.getToolbarOptions = function() {
        return [{
            key: "text_align",
            type: "select",
            values: [{
                    value: "left",
                    label: "Left",
                    icon: "icon-pagebuilder-align-left",
                },
                {
                    value: "center",
                    label: "Center",
                    icon: "icon-pagebuilder-align-center",
                },
                {
                    value: "right",
                    label: "Right",
                    icon: "icon-pagebuilder-align-right",
                },
            ],
        }, ]
    }

    return Preview
})
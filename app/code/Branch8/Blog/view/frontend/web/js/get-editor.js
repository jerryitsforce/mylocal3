/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */
define([
    'jquery',
    "mage/adminhtml/events",
    "mage/adminhtml/wysiwyg/tiny_mce/setup"
], function ($) {
    'use strict';

    return {
        config: function (nameEL, versionEditor, versionMagento, width) {
            var wysiwygcompany_description,
                config = {},
                editor;

            if (typeof width === 'undefined') {
                width = '99%';
            }

            if (versionMagento === "2") {
                $.extend(config, {
                    settings: {
                        theme_advanced_buttons1: 'bold,italic,|,justifyleft,justifycenter,justifyright,|,' +
                            'fontselect,fontsizeselect,|,forecolor,backcolor,|,link,unlink,image,|,' +
                            'bullist,numlist,|,code',
                        theme_advanced_buttons2: null,
                        theme_advanced_buttons3: null,
                        theme_advanced_buttons4: null
                    }
                });
                editor = new tinyMceWysiwygSetup(
                    nameEL,
                    config
                );
                editor.turnOn();
                $('#' + nameEL).addClass('wysiwyg-editor').data('wysiwygEditor', editor);
            } else if (versionEditor === "4") {
                wysiwygcompany_description = new wysiwygSetup(nameEL, {
                    "width": width,
                    "height": "200px",
                    "plugins": [{"name": "image"}],
                    "tinymce4": {
                        "toolbar": "formatselect | bold italic underline | alignleft aligncenter alignright |" +
                            " bullist numlist | link table charmap",
                        "plugins": "advlist autolink lists link charmap media noneditable" +
                            " table contextmenu paste code help table"
                    }
                });
                wysiwygcompany_description.setup("exact");
            } else {
                $.extend(config, {
                    settings: {
                        theme_advanced_buttons1: 'bold,italic,|,justifyleft,justifycenter,justifyright,|,' +
                            'fontselect,fontsizeselect,|,forecolor,backcolor,|,link,unlink,image,|,' +
                            'bullist,numlist,|,code',
                        theme_advanced_buttons2: null,
                        theme_advanced_buttons3: null,
                        theme_advanced_buttons4: null
                    },
                    tinymce : {
                        content_css : null
                    }
                });
                editor = new wysiwygSetup(
                    nameEL,
                    config
                );
                editor.setup("exact");
                $('#' + nameEL).addClass('wysiwyg-editor').data('wysiwygEditor', editor);
            }
        }
    };
});

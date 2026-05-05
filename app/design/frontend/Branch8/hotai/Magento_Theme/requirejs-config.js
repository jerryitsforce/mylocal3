/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            'slideUpSticky': 'Magento_Theme/js/lib/slideUpSticky',
            'slickSlider': 'Magento_Theme/js/lib/slickSlider',
            'perfectScrollbar': 'Magento_Theme/js/plugins/perfectScrollbar/perfect-scrollbar.min'
        },
    },
    paths: {
        'plugins/headroom': 'Magento_Theme/js/plugins/headroom/headroom.min',
        'plugins/stickykit': 'Magento_Theme/js/plugins/sticky/sticky-kit.min',
        'jquery/file-uploader': 'jquery/fileUploader/jquery.fileuploader',
        'plugins/rollDate': 'Magento_Theme/js/plugins/rolldate/rolldate.min'
    },
    deps: [
        'Magento_Theme/js/theme'
    ],
    shim: {
        'plugins/stickykit': {
            deps: ['jquery']
        },
    },
    config: {
        mixins: {
            'Magento_Theme/js/view/messages': {
                'Magento_Theme/js/view/messages-mixin': true
            }
        }
    }
};

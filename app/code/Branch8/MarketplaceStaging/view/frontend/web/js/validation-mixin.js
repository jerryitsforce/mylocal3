define([
    'jquery',
    'mageUtils',
    'Magento_Ui/js/modal/alert'
], function ($, mageUtils, alert) {
    'use strict';

    /**
     *
     */
    function getGallery(form) {
        const formData = new FormData(form);
        const gallery = {};
        const flatGallery = [];
        for (let [fullKey, value] of formData.entries()) {
            const match = fullKey.match(/^product\[media_gallery]\[images]\[([^\]]+)]\[([^\]]+)]$/);
            let imageKey='';
            if (match) {
                imageKey = match[1]; // e.g., abc123
                const field = match[2];    // e.g., position or type
                if (!gallery[imageKey]) {
                    gallery[imageKey] = {};
                }
                gallery[imageKey][field] = value;
            }
        }
        if (!mageUtils.isEmptyObj(gallery)) {
            for (const key in gallery) {
                let value = gallery[key].file;
                if (gallery[key].removed === '1') {
                    delete gallery[key];
                } else {
                    flatGallery.push(value);
                }
            }
        }
        return [gallery, flatGallery];
    }

    return function (targetWidget) {
        $.validator.addMethod(
            'validate-image-tags',
            function (value, element) {console.log(value);
                let result = true, missingLabel = [];
                try {
                    const form = element?.form;
                    const [gallery,flatGallery] = getGallery(form);
                    if (!form || mageUtils.isEmptyObj(gallery) === true || flatGallery.length === 0) {
                        return result;
                    }
                    const requiredTags = (JSON.parse(element.dataset.validate))['validate-image-tags'];
                    Object.keys(requiredTags).forEach(key => {
                        const selector = `[name="product\\[${key}\\]"]`,
                            field = form.querySelector(selector);
                        if (field && (!field.value || field.value === 'no_selection' || !flatGallery.includes(field.value))) {
                            result = false;
                            missingLabel.push(requiredTags[key])
                        }
                    });
                } catch (e) {
                    console.log(e)
                }
                if (!result) {
                    alert({
                            'content': $.mage.__('Please select all required image tags:(%1)')
                                .replace('%1', missingLabel.join(','))
                        }
                    );
                }
                return result;
            },
            $.mage.__('Please select all required image tags')
        );
        /**
         *
         */
        $.validator.addMethod(
            'validate-no-sku-duplicate',
            function (value, element) {
                const currentSku = $.trim(value).toLowerCase();
                const parentForm = $(element).closest('form');
                var map = {},$all = $(element).closest('form').find('.wkv-sku.no-duplicate');
                $all.removeClass('duplicate-sku-error');
                $all.each(function () {
                    var v = $.trim($(this).val()).toLowerCase();
                    if (!v) return; // ignore empty
                    if (!map[v]) map[v] = [];
                    map[v].push(this);
                });
                $.each(map, function (k, els) {
                    if (els.length > 1) {
                        $(els).addClass('duplicate-sku-error');
                    }
                });
                if (!currentSku) {
                    return true;
                }
                return !(map[currentSku] && map[currentSku].length > 1);
            },
            function () {
                return $.mage.__('Duplicate value detected.')
            },
        )

        return targetWidget;
    }
});


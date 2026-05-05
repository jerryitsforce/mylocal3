define(['Branch8_React/js/productWidget'], function () {
    'use strict';

    function mount(el) {
        if (el.__mounted) return;

        const data = JSON.parse(el.dataset.widget);
        const id = el.dataset.widgetId;

        if (window.mountProductWidget) {
            window.mountProductWidget({
                data: data,
                el: el,
                elId: id
            });
            el.__mounted = true;
        }
    }

    function scan() {
        document.querySelectorAll('.react-product-widget').forEach(mount);
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', scan);

    // Dynamic content (CMS, AJAX, FPC hole punch)
    new MutationObserver(scan).observe(document.body, {
        childList: true,
        subtree: true
    });

    return {};
});

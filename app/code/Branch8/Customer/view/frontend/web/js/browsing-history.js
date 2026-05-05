define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'mage/url',
    'plugins/DOMPurify',
    'Branch8_PromotionPage/js/action/badge',
    'domReady!'
], function($, $t, confirmation, urlBuilder, DOMPurify, productBadge) {
    $.widget('b8.browsingHistory', {
        options: {},
        _create: function () {
            var jQ = $.noConflict();
            productBadge();


            //For app
            window.ReactNativeWebView?.postMessage(
                JSON.stringify({
                    type: 'HEADER_TOTAL_RECORDS',
                    data: jQ('#header-total-records').val() || 0
                })
            )


            jQ('.browsing-history-remove-all').on('click', function (e){
                e.preventDefault();
                confirmation({
                    title: $t("Delete All"),
                    content: $t("Delete all browsing history?"),
                    modalClass: 'delete-all-confirm-popup',
                    actions: {
                        confirm: function () {
                            jQ.ajax({
                                url: urlBuilder.build('member/browsingHistory/delete'),
                                method: 'post',
                                dataType: 'json',
                                showLoader:true,
                                beforeSend: function() {
                                    jQ('body').trigger('processStart');
                                },
                                success: function(data){
                                    location.reload();
                                }
                            });
                        },
                        cancel: function () {
                            return false;
                        }
                    }
                });
            });

            jQ('.product-item-remove').on('click', function (e){
                e.preventDefault();
                jQ.ajax({
                    url: urlBuilder.build('member/browsingHistory/delete'),
                    method: 'post',
                    data: { productId : e.currentTarget.getAttribute("product-id")},
                    dataType: 'json',
                    showLoader:true,
                    beforeSend: function() {
                        jQ('body').trigger('processStart');
                    },
                    success: function(data){
                        location.reload();
                    }
                });
            });

            jQ('.browsing-history-load-more').on('click', function (e){
                var currentPage = parseInt(jQ(this).data('event-page'));
                var lastPage = parseInt(jQ(this).data("last-page"));
                e.preventDefault();
                if (typeof currentPage != "undefined" || !isNaN(currentPage)) {
                    let nextPage = currentPage + 1;
                    jQ.ajax({
                        url: urlBuilder.build('member/browsingHistory?isMobile=true&p='+nextPage),
                        method: 'get',
                        dataType: 'json',
                        showLoader:true,
                        beforeSend: function() {
                            jQ('body').trigger('processStart');
                        },
                        success: function(data){
                            const productItems = jQ(".member-browsing-history .product-items.widget-viewed-grid");
                            const sanitizedData = DOMPurify.sanitize(data);
                            productItems.append(sanitizedData);
                            productItems.trigger("contentUpdated");

                            productBadge();
                            // console.log("Browsing history loaded for page: ", currentPage, nextPage, lastPage);
                            if (currentPage >= lastPage || nextPage >= lastPage) {
                                jQ('.browsing-history-load-more').hide();
                            } else {
                                jQ('.browsing-history-load-more').data("event-page", nextPage);
                            }
                            jQ('body').trigger('processStop');
                        }
                    });
                } else {
                    jQ('.browsing-history-load-more').hide();
                }
            });


            const observerOptions = {
                root: null,
                rootMargin: '500px', // start loading before reaching element
                threshold: 0
            };

            const loadNextPage = function (currentPage, lastPage) {
                if (currentPage >= lastPage) return;

                const nextPage = currentPage + 1;
                jQ.ajax({
                    url: urlBuilder.build('member/browsingHistory?isMobile=true&p=' + nextPage),
                    method: 'get',
                    dataType: 'json',
                    showLoader: false,
                    beforeSend: function () {
                        // jQ('body').trigger('processStop');
                    },
                    success: function (data) {
                        const productItems = jQ(".member-browsing-history .product-items.widget-viewed-grid");
                        const sanitizedData = DOMPurify.sanitize(data);
                        productItems.append(sanitizedData);
                        productItems.trigger("contentUpdated");
                        productBadge();

                        // Update new first-item page marker for next observation
                        const nextMarker = `.page-first-item-${nextPage}`;
                        const markerEl = document.querySelector(nextMarker);
                        if (markerEl && nextPage < lastPage) {
                            observer.observe(markerEl);
                        }

                        // jQ('body').trigger('processStop');
                    }
                });
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = jQ(entry.target);
                        console.log("Browsing history observed for infinite scroll: ", entry.target, el);
                        const currentPage = parseInt(el.data('page'));
                        const lastPage = jQ('.browsing-history-load-more').length ? parseInt(jQ('.browsing-history-load-more').data('last-page')) : 1;
                        observer.unobserve(entry.target); // Avoid repeated triggers
                        loadNextPage(currentPage, lastPage);
                    }
                });
            }, observerOptions);

            const firstPage = parseInt(jQ('.browsing-history-load-more').data('event-page')) || 1;
            const markerEl = document.querySelector(`.page-first-item-${firstPage}`);
            if (markerEl && $('body').hasClass('mobile-device')) {
                observer.observe(markerEl);
            }
        }
    });
    return $.b8.browsingHistory;
});

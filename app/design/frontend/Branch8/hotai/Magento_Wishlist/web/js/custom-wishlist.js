define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'mage/url',
    'mage/dataPost',
    'plugins/DOMPurify',
    'Branch8_PromotionPage/js/action/badge',
    'domReady!'
], function($, $t, confirmation, urlBuilder, dataPost, DOMPurify, productBadge) {
    $.widget('b8.customWishlist', {
        options: {},
        _create: function () {
            productBadge();
            let $widget = this;
            var jQ = $.noConflict();

            jQ('.wishlist-load-more').on('click', function (e){
                var currentPage = parseInt(jQ(this).data('event-page'));
                var lastPage = parseInt(jQ(this).data("last-page"));
                e.preventDefault();
                if (typeof nextPage != 'undefined' || jQuery.isNumeric(currentPage)) {
                    let nextPage = currentPage + 1;
                    jQ.ajax({
                        url: urlBuilder.build('/wishlist?limit=12&p='+nextPage),
                        method: 'get',
                        dataType: 'json',
                        showLoader:true,
                        beforeSend: function() {
                            $('body').trigger('processStart');
                        },
                        success: function(data){
                            const productItems = jQ(".wishlist-index-index .products-grid.wishlist > .product-items");
                            const sanitizedData = DOMPurify.sanitize(data);
                            productItems.append(sanitizedData);
                            productItems.trigger("contentUpdated");
                            if (nextPage >= lastPage) {
                                jQ('.wishlist-load-more').hide();
                            } else {
                                jQ(".wishlist-load-more").data("event-page", nextPage);
                            }
                            productBadge();
                            jQ('body').trigger('processStop');
                        }
                    });
                } else {
                    jQ('.wishlist-load-more').hide();
                }
            });

            jQ('.wishlist-remove-all').on('click', function (e){
                e.preventDefault();
                confirmation({
                    title: $t("Delete All"),
                    content: $t("Delete all wishlist?"),
                    modalClass: 'delete-all-confirm-popup',
                    actions: {
                        confirm: function () {
                            jQ.ajax({
                                url: urlBuilder.build('wishlist/index/removeAll'),
                                method: 'post',
                                dataType: 'json',
                                showLoader:true,
                                beforeSend: function() {
                                    $('body').trigger('processStart');
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

            $(document).on('click', "[data-role=remove]", function (event){
                event.preventDefault();
                var $btn = $(event.currentTarget);
                if ($btn.data('removing')) {
                    return;
                }
                $btn.data('removing', true);
                $btn.addClass('disabled').css('pointer-events', 'none');
                $('body').trigger('processStart');
                dataPost.postData($btn.data('post-remove'));
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
                    url: urlBuilder.build('/wishlist?limit=12&p='+nextPage),
                    method: 'get',
                    dataType: 'json',
                    showLoader:true,
                    beforeSend: function() {
                        $('body').trigger('processStart');
                    },
                    success: function(data){
                        const productItems = jQ(".wishlist-index-index .products-grid.wishlist > .product-items");
                        const sanitizedData = DOMPurify.sanitize(data);
                        productItems.append(sanitizedData);
                        productItems.trigger("contentUpdated");
                        if (nextPage >= lastPage) {
                            jQ('.wishlist-load-more').hide();
                        } else {
                            jQ(".wishlist-load-more").data("event-page", nextPage);
                        }
                        productBadge();
                        jQ('body').trigger('processStop');
                    }
                });
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = jQ(entry.target);
                        console.log("Wishlist observed for infinite scroll: ", entry.target, el);
                        const currentPage = parseInt(el.data('page'));
                        const lastPage = jQ('.wishlist-load-more').length ? parseInt(jQ('.wishlist-load-more').data('last-page')) : 1;
                        console.log("Wishlist infinite scroll currentPage/lastPage: ", currentPage, lastPage);
                        observer.unobserve(entry.target); // Avoid repeated triggers
                        loadNextPage(currentPage, lastPage);
                    }
                });
            }, observerOptions);

            const firstPage = parseInt(jQ('.wishlist-load-more').data('event-page')) || 1;
            const markerEl = document.querySelector(`.page-first-item-${firstPage}`);
            if (markerEl && $('body').hasClass('mobile-device')) {
                observer.observe(markerEl);
            }

        },
        _bind: function() {

        }
    });
    return $.b8.customWishlist;
});

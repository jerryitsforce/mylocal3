/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_PageBuilder/js/widget/show-on-hover',
    'Magento_PageBuilder/js/widget/video-background',
    'Branch8_GA4/js/actions/ga4push',
], function ($, showOnHover, videoBackground, ga4push) {
    'use strict';

    return function (config, element) {
        const $element = $(element);
        let ecommerce = {
            creative_name: $element.data('creative-name') || undefined,
            creative_slot: $element.data('creative-slot') || undefined,
            promotion_id: $element.data('promotion-id') || undefined,
            promotion_name: $element.data('promotion-name') || undefined
        }

        /**
         * This part of code is to handle the GA4 event push whenever the element is visible on screen.
         */
        //DO NOT: create variable for key $element.data('ga4-event-uid')
        //DO NOT: add more string for key eg: $element.data('ga4-event-uid') + '_is_pushed'...etc
        window[$element.data('ga4-event-uid')] = false


        let isDisplay = $element.attr('data-display') !== 'false';

        if (isDisplay) {
            $element.parentsUntil('#maincontent').each(function() {
                if ($(this).attr('data-display') === 'false') {
                    isDisplay = false;
                    return false;
                }
            });
        }


        function buildThresholdList(numSteps) {
            let thresholds = [0.0];

            for (let i = 1; i <= numSteps; i++) {
                let ratio = i / numSteps;
                thresholds.push(ratio);
            }

            thresholds.push(1.0);
            return thresholds;
        }

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: buildThresholdList(100),
        };

        const handleIntersection = (entries, observer) => {
            entries.forEach((entry) => {
                let isIntersecting = entry.isIntersecting

                if (isIntersecting) {
                    checkAndPushGA4Event($element);
                } else {
                    window[$element.data('ga4-event-uid')] = false;
                }
            });
        };

        const isOnScreen = function($element) {
            const $window = $(window);
            const viewport = {
                top: $window.scrollTop(),
                bottom: $window.scrollTop() + $window.height()
            };
            const bounds = $element.offset();
            bounds.bottom = bounds.top + $element.outerHeight();
            return (!(viewport.bottom < bounds.top || viewport.top > bounds.bottom));
        };

        const checkAndPushGA4Event = function($element) {
            if (window[$element.data('ga4-event-uid')]) {
                return;
            }

            const isWrappedByTabs = $element.closest('[data-content-type="tab-item"]').length > 0;
            if (isWrappedByTabs) {
                const tabItemParent = $element.closest('[data-content-type="tab-item"]').first()

                const isHidden = (
                    tabItemParent.is(':hidden') ||
                    tabItemParent.css('visibility') === 'hidden' ||
                    tabItemParent.css('opacity') === '0' ||
                    tabItemParent.attr('hidden') !== undefined ||
                    tabItemParent.hasClass('hidden')
                );

                if (isHidden) {
                    return
                }
            }

            let invisible = false;
            //.popular-topic-category-section
            if($element.closest('.pagebuilder-column-group.invisible').length > 0) {
                invisible = $element.closest('.popular-topic-category-section.show-all').length === 0
            }
            if (invisible) {
                return
            }

            pushGa4Event('view_promotion', ecommerce)
            window[$element.data('ga4-event-uid')] = true
        }

        // for view_promotion, select_promotion only
        const pushGa4Event = function(eventName, ecommerce) {
            //remove undefined key
            ecommerce = JSON.parse(JSON.stringify(ecommerce));

            if (Object.keys(ecommerce).length) {
                ga4push([{event: eventName, ecommerce}]);
            } else {
                console.info(`GA4 Event "${eventName}" is not pushed because of missing required parameters`);
            }
        }


        if (isDisplay) {
            //TODO: create shared observer to use in other widgets
            const observer = new IntersectionObserver(handleIntersection, observerOptions);
            observer.observe($element[0]);
        }


        /**
         * End
         */

        var videoElement = element[0].querySelector('[data-background-type=video]');

        showOnHover(config);

        if (videoElement) {
            videoBackground(config, videoElement);
        }


        if (isOnScreen($element) && isDisplay) {
            pushGa4Event('view_promotion', ecommerce); // push view_promotion event to GA4 after init
            window[$element.data('ga4-event-uid')] = true
        }


        const $bannerButtons = $element.find('.pagebuilder-banner-button');
        const $bannerLinks = $element.find('a[data-element="link"]');

        // //Buttons on banner
        // $bannerButtons.each(function (i, bannerButton) {
        //     $(bannerButton).on('click', function () {
        //         // ga4push( [{ event: 'select_promotion', ecommerce }] );
        //         console.log('banner btn click', $(this));
        //         pushGa4Event('select_promotion', ecommerce)
        //     });
        // });

        //Links on banner
        $bannerLinks.each(function (i, bannerButton) {
            $(bannerButton).on('click', function () {
                pushGa4Event('select_promotion', ecommerce)
            });
        });

    };
});

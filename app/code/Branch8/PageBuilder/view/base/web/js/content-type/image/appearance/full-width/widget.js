define([
    'jquery',
    'Branch8_GA4/js/actions/ga4push',
], function ($, ga4push) {
    'use strict';

    return function (config, element) {
        const $element = $(element);
        let isDisplay = $element.attr('data-display') !== 'false';

        if (isDisplay) {
            $element.parentsUntil('#maincontent').each(function() {
                if ($(this).attr('data-display') === 'false') {
                    isDisplay = false;
                    return false;
                }
            });
        }
        if ($element.data('ga4-enable') === "enabled" && isDisplay) {
            /**
             * This part of code is to handle the GA4 event push whenever the element is visible on screen.
             */
            //DO NOT: create variable for key $element.data('ga4-event-uid')
            //DO NOT: add more string for key eg: $element.data('ga4-event-uid') + '_is_pushed'...etc
            window[$element.data('ga4-event-uid')] = false

            let ecommerce = {
                creative_name: $element.data('creative-name'),
                creative_slot: $element.data('creative-slot'),
                promotion_id: $element.data('promotion-id'),
                promotion_name: $element.data('promotion-name')
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
                // ga4push([{event: 'view_promotion', ecommerce}]);
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
                    console.log(`GA4 Event "${eventName}" is not pushed because of missing required parameters`);
                }
            }

            //TODO: create shared observer to use in other widgets
            const observer = new IntersectionObserver(handleIntersection, observerOptions);
            observer.observe($element[0]);

            if (isOnScreen($element)) {
                // ga4push([{event: 'view_promotion', ecommerce}]);
                pushGa4Event('view_promotion', ecommerce); // push view_promotion event to GA4 after init
            }

            const $imageLinks = $element.find('a[data-element="link"]');

            //Links on banner
            $imageLinks.each(function (i, bannerButton) {
                $(bannerButton).on('click', function () {
                    // ga4push( [{ event: 'select_promotion', ecommerce }] );
                    pushGa4Event('select_promotion', ecommerce);
                });
            });
        }
    };
});

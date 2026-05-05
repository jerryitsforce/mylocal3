/**CANA Teddy Bear Sun Screenl 50ml
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'tabs',
    'matchMedia',
    'domReady!'
], function ($) {
    'use strict';

    function setActiveTab() {
        mediaCheck({
            media: "(max-width: 768px)",
            entry: $.proxy(function () {
                $("#tabs").tabs({
                    "openedState": "active",
                    "active": 0,
                    "animate":{ "duration" :1}
                });
            }, this),
            exit: $.proxy(function () {
                var $tabs = $("#tabs");
                var activeTabIndex = 0;

                // Iterate over each tab to find the first visible one
                $tabs.find("li.level1").each(function (index) {
                    if (!$(this).hasClass("hide-desktop")) {
                        activeTabIndex = index;
                        return false; // Exit loop once the first visible tab is found
                    }
                });

                $("#tabs").tabs({
                    "openedState": "active",
                    "active": activeTabIndex,
                    "animate":{ "duration" :1}

                });
            }, this),
        });
    }

    // Set the active tab based on screen size
    setActiveTab();

    // Update the active tab on window resize
    $(window).resize(setActiveTab);




    $('#searchBtn').on('click', function() {
        $('#brandSearch').toggleClass('expanded').focus();
    });

    $('#brandSearch').on('keyup', function() {
        let filter = $(this).val().toUpperCase();
        $('[class^="anchor"]').find('.brands a img').each(function() {
            let altValue = $(this).attr('alt');
            if (altValue && altValue.toUpperCase().indexOf(filter) > -1) {
                $(this).parent().parent().css('display', '');
            } else {
                $(this).parent().parent().css('display', 'none');
            }
        });
    });


    $('.letterButtons').each(function() {
        $(this).click(function() {
            // Find the closest parent container with an ID starting with "tab-"
            var tabContainer = $(this).closest('[id^="tab-"]');

            // Remove the active class from all buttons within this tab scope
            tabContainer.find(".letterButtons").removeClass("active");
            // Add the active class to the clicked button
            $(this).addClass("active");

            // Find the target anchor element within this tab scope
            var targetIndex = $(this).data('target');
            var targetClass = '.anchor' + targetIndex;
            var $target = tabContainer.find(targetClass);
            var scrollTo;

            // Check if the target element exists
            if ($target.length) {
                // Calculate the scrollTop position
                mediaCheck({
                    media: "(max-width: 767px)",
                    entry: $.proxy(function () {
                        scrollTo = $target.offset().top - tabContainer.offset().top + tabContainer.scrollTop() - 40;
                    }, this),
                    exit: $.proxy(function () {
                        scrollTo = $target.offset().top - tabContainer.offset().top + tabContainer.scrollTop() - 70;
                    }, this),
                });

                // Animate the scrolling
                tabContainer.animate({
                    scrollTop: scrollTo
                }, 500);
            } else {
                console.error('Target element not found:', targetClass);
            }
        });
    });


    mediaCheck({
        media: "(max-width: 768px)",
        entry: $.proxy(function () {
            var $menuIcon = $(".action.nav-toggle");
            var $navigation = $(".page-wrapper > .navigation");

            if ($menuIcon.length && $navigation.length) {
                $menuIcon.on("click", function() {
                    $navigation.addClass("show");
                    $("body").addClass("navopen");
                    $('.page-header .block-search .block-title').click();

                    $(".header.content .block-search").removeClass("typing");
                });
            }
            $("#hide-nav-button").on("click", function() {
                $navigation.removeClass("show");
                $("body").removeClass("navopen");
                $('.page-header .block-search .block-title').click();
            })
        }, this),
        exit: $.proxy(function () {

        }, this),
    });
});

define([
    'jquery',
    'tabs',
    'matchMedia',
    "slick",
    'domReady!'
], function ($) {
    'use strict';
    $.widget(
        'b8.brandSlider',
        {
            _create: function () {
                'use strict';
                console.log('brandSlider Loaded')
                let slickConfigCustom = {
                    arrows: true,
                    infinite: true,
                    autoplay: false,
                    slidesToShow: 8,
                    slidesToScroll:8,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 6,
                                slidesToScroll: 6
                            }
                        },
                        {
                            breakpoint: 769,
                            settings: {
                                variableWidth: true,
                                slidesToShow: 3,
                                slidesToScroll: 3
                            }
                        }
                    ]
                };

                if ($('.ambrands-slider-list').length && !$('.ambrands-slider-list').hasClass('slick-initialized')) {
                    $('.ambrands-slider-list').slick(slickConfigCustom);
                }
            },
        }
    );
    return $.b8.brandSlider;
});

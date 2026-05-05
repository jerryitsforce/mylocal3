define([
    'jquery',
    'matchMedia',
    'jquery/ui',
    'slick',
    'domReady!'
], function($){
    "use strict";

    $.widget('b8.slickSlider', {
        options: {
            accessibility: true,
            adaptiveHeight: false,
            appendArrows: null,
            appendDots: null,
            arrows: true,
            asNavFor: null,
            prevArrow: '<button type="button" data-role="none" class="slick-prev" aria-label="Previous" tabindex="0" role="button">Previous</button>',
            nextArrow: '<button type="button" data-role="none" class="slick-next" aria-label="Next" tabindex="0" role="button">Next</button>',
            autoplay: false,
            autoplaySpeed: 3000,
            centerMode: false,
            centerPadding: '50px',
            cssEase: 'ease',
            customPaging: function(slider, i) {
                return $('<button type="button" data-role="none" role="button" tabindex="0" />').text(i + 1);
            },
            dots: false,
            dotsClass: 'slick-dots',
            draggable: true,
            easing: 'linear',
            edgeFriction: 0.35,
            fade: false,
            focusOnSelect: false,
            infinite: true,
            initialSlide: 0,
            lazyLoad: 'ondemand',
            mobileFirst: false,
            pauseOnHover: true,
            pauseOnFocus: true,
            pauseOnDotsHover: false,
            respondTo: 'window',
            responsive: null,
            rows: 1,
            rtl: false,
            slide: '',
            slidesPerRow: 1,
            slidesToShow: 1,
            slidesToScroll: 1,
            speed: 500,
            swipe: true,
            swipeToSlide: false,
            touchMove: true,
            touchThreshold: 5,
            useCSS: true,
            useTransform: true,
            variableWidth: false,
            vertical: false,
            verticalSwiping: false,
            waitForAnimate: true,
            zIndex: 1000,
            template: "slick6Products"
        },

        _create: function() {
            if(!this.options.template)
                this._callSlider();

            if(this.options.template === 'slick6Products')
                this._slick6Products();

            if(this.options.template === 'slickVariableWidthProducts')
                this.slickVariableWidthProducts();

            if(this.options.template === 'slick4Products')
                this.slick4Products();

            if(this.options.template === "slickCustom6Products")
                this.slickCustom6Products()

        },

        _callSlider: function(){
            if(!this.options.appendArrows){
                this.options.appendArrows = this.element;
            }else{
                this.options.appendArrows = $(this.options.appendArrows);
            }

            if(!this.options.appendDots){
                this.options.appendDots = this.element;
            }else{
                this.options.appendDots = $(this.options.appendDots);
            }

            this.element.slick(this.options);
        },
        refresh: function(){
            this.element.slick('refresh');
        },
        slickRemove: function (index, removeBefore, removeAll) {
            this.element.slick('slickRemove', index, removeBefore, removeAll);
        },
        unslick: function () {
            this.element.slick('unslick');
        },
        _slick6Products: function(){
            this.element.slick({
                arrows: true,
                infinite: true,
                autoplay: false,
                slidesToShow: 6,
                slidesToScroll:6,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 4,
                            slidesToScroll: 4,
                        }
                    },
                    {
                        breakpoint: 769,
                        settings: {
                            variableWidth: true,
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                    }
                ]

            });
        },

        slickCustom6Products: function(){
            this.element.slick({
                arrows: this.options.infinite,
                infinite: this.options.infinite,
                autoplay: this.options.autoplay,
                slidesToShow: 6,
                slidesToScroll:6,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 4,
                            slidesToScroll: 4,
                        }
                    },
                    {
                        breakpoint: 769,
                        settings: {
                            variableWidth: true,
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                    }
                ]

            });
        },

        slick4Products: function(){
            this.element.slick({
                arrows: true,
                infinite: true,
                autoplay: false,
                slidesToShow: 4,
                slidesToScroll:4,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                        }
                    },
                    {
                        breakpoint: 769,
                        settings: {
                            variableWidth: true,
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                    }
                ]
            });
        },

        slickVariableWidthProducts: function(){
            this.element.slick({
                arrows: true,
                infinite: true,
                autoplay: false,
                slidesToShow: 6,
                slidesToScroll:6,
                variableWidth: true,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 4,
                            slidesToScroll: 4,
                            variableWidth: true,
                        }
                    },
                    {
                        breakpoint: 769,
                        settings: {
                            variableWidth: true,
                            slidesToShow: 2,
                            slidesToScroll: 2
                        }
                    }
                ]
            });
        }
    });

    return $.b8.slickSlider;
});

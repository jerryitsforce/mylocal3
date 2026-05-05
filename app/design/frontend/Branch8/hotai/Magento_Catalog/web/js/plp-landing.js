define([
    'jquery',
    'plugins/DOMPurify',
    'matchMedia',
    'mage/mage',
    'slideUpSticky',
    'domReady!'
], function ($, DOMPurify) {
    'use strict';
    $.widget(
        'b8.plplanding',
        {
            _create: function () {
                'use strict';
                const officialTabItems = $('.sub-cats-tabs-row');
                if(officialTabItems.length) {
                    officialTabItems.find('a').each(function(index){
                        if(index===0) {
                            $(this).addClass('active');
                        }
                    });

                    const anchor = DOMPurify.sanitize(window.location.hash),
                    anchorId = anchor.replace("#",""),
                    $tabItems = $('.sub-cats-tab-item-row');
                    $tabItems.each(function(index){
                        $(this).attr('id','content'+(index + 1));
                    });

                    if (anchor) {
                        $('.sub-cats-tabs-row a').removeClass('active');
                        const anchorKey = anchor.replace("#","");
                        setTimeout(function(){ 
                            document.getElementById(anchorKey).scrollIntoView({ behavior: "smooth" });
                        }, 3000);
                        $('.sub-cats-tabs-row a').filter(function () {
                            return this.getAttribute('href') === anchor;
                        }).addClass('active');
                    }
                    
                    $('.sub-cats-tabs-row a').on('click', function(event){
                        event.preventDefault();
                        $('.sub-cats-tabs-row a').removeClass('active');
                        $(this).addClass('active');
                        const anchorKey = $(this).attr("href").replace("#","");
                        document.getElementById(anchorKey).scrollIntoView({ behavior: "smooth" });
                    });

                    if(!$('[data-content-type="product_recommendations"] .block-static-block').length > 0){
                        if($('[data-appearance="carousel"] .product-items').length){
                            $('.product-items').on('init', function(event, slick){
                                if($('.sub-cats-tabs-row').length > 0){
                                    $('.sub-cats-tabs-row').slideUpSticky();
                                }
                                if($('.category-list-title').length > 0){
                                    $('.category-list-title').slideUpSticky();
                                }
                            });
                        }else{
                            if($('.sub-cats-tabs-row').length > 0){
                                $('.sub-cats-tabs-row').slideUpSticky();
                            }
                            if($('.category-list-title').length > 0){
                                $('.category-list-title').slideUpSticky();
                            }
                        }
                    }
                    
                    const sections = document.querySelectorAll(".sub-cats-tab-item-row");
                    window.onscroll = () => {
                        var current = "";
                        var pageYOffset = window.pageYOffset;
                        
                        sections.forEach((section) => {
                            const sectionTop = section.offsetTop;
                            if (pageYOffset >= sectionTop - 180) {
                            current = section.getAttribute("id"); }
                        });

                        $(".sub-cats-tabs-row a").each(function() {
                            if($(this).attr('href').replace("#","") === current) {
                                $('.sub-cats-tabs-row a').removeClass('active');
                                $(this).addClass('active');
                            }
                        });
                    };
                }
            },
        }
    );
    return $.b8.plplanding;
});

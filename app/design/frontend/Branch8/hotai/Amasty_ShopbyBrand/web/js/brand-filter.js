define([
    "jquery",
], function ($) {
    const animationDuration = 200;

    var BrandFilter = function () {
        return {
            apply: function (containerSelector) {
                var elements = $(containerSelector);

                if (! $(this).hasClass('-letter-all')) {
                    var letter = '';
                    var classList = $(this).attr('class').split(/\s+/);
                    $.each(classList, function(index, item) {
                        if (item.indexOf("letter-") >= 0) {
                            return letter = item;
                        }
                    });

                    elements.each( function() {
                        if (! $(this).hasClass(letter)) {
                            $(this).removeClass('ok');
                        } else {
                            $(this).addClass('ok');
                            
                            const offset = $('.ambrands-filters-block').outerHeight();

                            mediaCheck({
                                media: "(max-width: 768px)",
                                entry: $.proxy(function () {
                                    scrollToBrandlistContent(offset + 20, this);
                                }, this),
                                exit: $.proxy(function () {
                                    scrollToBrandlistContent(offset, this);
                                }, this)
                            });
                        }
                    });
                } else {
                    // elements.siblings('.am-brands-fullwidth').removeClass('am-brands-fullwidth');
                }

                $(this).parent().siblings().addBack().each(function() {
                    $(this).children("[class*='letter-']").each(function() {
                        $(this).removeClass('-active');
                    });
                });
                
                return $(this).addClass('-active');
            }
        };
    }();

    function scrollToBrandlistContent(offset, brandListItem) {
        const $parentBrandListContent = $('.ambrands-brandlist-widget .content');
        const pos = getRelativePosition($parentBrandListContent.get(0), brandListItem);

        $parentBrandListContent.animate({ scrollTop: pos.top - offset }, animationDuration);
    }

    function getRelativePosition(parent, elm) {
        const pPos = parent.getBoundingClientRect(),
            cPos = elm.getBoundingClientRect(),
            pos = {};

        pos.top = cPos.top - pPos.top + parent.scrollTop;

        return pos;
    }
          
    $.fn.extend({
        applyBrandFilter: BrandFilter.apply
    });
});

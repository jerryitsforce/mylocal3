define([
    "jquery",
    "matchMedia",
    "plugins/headroom",
    "jquery/ui",
    "domReady!"
], function($, mediaCheck, Headroom){
    "use strict";

    $.widget("mage.slideUpSticky", {
        options: {
            offsetTop: 0,
            relativeParent: '',
            elementStartSticky: ''
        },

        _create: function() {
        },

        _init: function(){
            var self = this,
                pageHeader = document.querySelector("header"),
                headerHeight = pageHeader ? pageHeader.offsetHeight : 0,
                ele = this.element[0],
                eleRelativeParent = this.options.relativeParent ? document.querySelector(this.options.relativeParent) : '',
                eleStartSticky = this.options.elementStartSticky ? document.querySelector(this.options.elementStartSticky) : '',
                eleMarginTop = parseInt(window.getComputedStyle(ele).marginTop),
                eleMarginBottom = parseInt(window.getComputedStyle(ele).marginBottom),
                eleHeight = ele.offsetHeight + eleMarginTop + eleMarginBottom,
                headroomOffset;
                
            if (!ele)
                return;
            if(eleStartSticky){
                headroomOffset = eleStartSticky.offsetTop + self.options.offsetTop;
            }
            if(!eleStartSticky && eleRelativeParent){
                headroomOffset = eleRelativeParent?.offsetTop + ele.offsetTop + ele.offsetHeight + self.options.offsetTop;
            }
            if(!eleStartSticky && !eleRelativeParent){
                headroomOffset = ele.offsetTop + ele.offsetHeight + self.options.offsetTop;
            }
            ele.classList.add("additional-headroom");
            var headroom  = new Headroom(ele, {
                offset : {
                    up: headroomOffset - headerHeight,
                    down: headroomOffset
                },
                onPin : function() {
                    var pageHeader = document.querySelector("header"),
                    headerHeight = pageHeader ? pageHeader.offsetHeight : 0;
                    console.log(11111, headerHeight);

                    if($('.page-header').hasClass('headroom--pinned')){
                        ele.style.top = headerHeight + 'px';
                    }
                    else{
                        ele.style.top = 0;
                    }
                },
                onUnpin : function() {
                    ele.style.top = '0px';
                },
                onTop : function() {
                    ele.style.top = '0px';
                    // document.querySelector('.page-wrapper').style.paddingTop = headerHeight+'px';
                },
                onNotTop : function() {
                    console.log('not top');
                    var pageHeader = document.querySelector("header"),
                    headerHeight = pageHeader ? pageHeader.offsetHeight : 0;
                    console.log(11111, headerHeight);

                    if($('.page-header').hasClass('headroom--pinned')){
                        ele.style.top = headerHeight + 'px';
                    } else{
                        ele.style.top = 0;
                    }
                    // document.querySelector('.page-wrapper').style.paddingTop = eleHeight + headerHeight +'px';
                },
            });
            headroom.init();
        }
    });

    return $.mage.slideUpSticky;
});

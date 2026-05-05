define([
    "jquery",
    "mage/translate",
    "Magento_Ui/js/modal/modal",
    "tweenmax",
    "spinwheel",
], function ($, $t, modal) {
    "use strict";
    var result = {};
    var width = 414;
    var height = 414;
    var strokeWidth = 12;
    var radiusWidth = 60;

    if ($(window).width() <= 769) {
        width = 276;
        height = 276;
        radiusWidth = 40;
        strokeWidth = 8;
    }

    window.spinheight = height;
    window.wheelDataGlobal, window.centerx, window.resultForm;
    function drawWheel(area, wheelData, data = null) {
        // console.log("Drawing wheel with data:", area, wheelData, data);
        window.wheelDataGlobal = wheelData;
        if (data.length !== 0) {
            window.resultForm = data.result;
            window.mediaUrl = data.mediaUrl;
        }
        // var modalClass = "spin-wheel-popup";
        // var type = "popup";
        // let responsive = true;
        // if (
        //     (data.length !== 0 && data.layout.view == "slide") ||
        //     $(window).width() <= 770
        // ) {
        //     if (data.length !== 0 && data.layout.view == "slide") {
        //         type = "slide";
        //     }
        //     if (data.layout.position == "left") {
        //         modalClass += " spin-wheel-slide-left";
        //     } else {
        //         modalClass += " spin-wheel-slide-right";
        //     }
        // } else {
        //     modalClass += " spin-wheel-popup-popup";
        // }
        // if ($(window).width() <= 770) {
        //     responsive = false;
        // }
        // if (data.length !== 0 && data.layout.wheel_view == "split") {
        //     modalClass += " spin-wheel-split";
        // }
        // var options = {
        //     type: type,
        //     responsive: responsive,
        //     innerScroll: false,
        //     modalClass: modalClass,
        //     closed: function () {
        //         $("#spin-wheel-container").remove();
        //         $("body").trigger("spinModalClosed");
        //     },
        // };
        // $("#spinViewWheel").append(getHtml(area, wheelData, data));
        // modal(options, cont);
        // cont.modal("openModal");
        let segments = getSegments(wheelData);
        // console.log("Segments:", segments);
        window.centerx = width / 2;
        var spinWheel = new Spinwheel({
            canvasId: "spin-wheel-canvas",
            numSegments: segments.length,
            segments: segments,
            strokeStyle: '#ffffff',
            textDirection: wheelData.text_direction,
            textFontSize: wheelData.font_size,
            innerRadius: radiusWidth,
            lineWidth: 2,
            pointerAngle: 90,
            centerX: window.centerx,
            parts: {
                innerRadius: radiusWidth,
                strokeStyle: '#ffffff',
                lineWidth: strokeWidth,
                fillStyle: wheelData.center_color,
            },
            animation: {
                type: "spinToStop",
                duration: 3,
                spins: 8,
                // callbackAfter: "drawParts()",
            }
        });
        // drawParts();
        // setBackground(wheelData);
        // if ($(window).width() <= 770) {
        //     $(".spin-wheel-popup #spin-wheel-canvas").css(
        //         "top",
        //         "calc(50% - " + width / 2 + "px)"
        //     );
        //     $(".spin-wheel-popup #spin-wheel-canvas").css(
        //         "left",
        //         "calc(50% - " + width / 2 + "px)"
        //     );
        //     $(".spin-wheel-popup #spin-pin-image").css(
        //         "right",
        //         "calc((100% - " + width + "px) / 2 - 25px)"
        //     );
        //     if (data.length !== 0 && data.layout.wheel_view == "split") {
        //         $(".spin-wheel-popup #spin-pin-image").css("right", "unset");
        //         $(".spin-wheel-popup #spin-pin-image").css(
        //             "left",
        //             "calc(" + width / 2 + "px - 5px)"
        //         );
        //     }
        // }
        // if (data.length !== 0 && data.layout.wheel_view == "split") {
        //     $(".spin-wheel-popup #spin-wheel-canvas").css(
        //         "left",
        //         "-" + (width / 2 - 20) + "px"
        //     );
        // }
        return spinWheel;
    }

    function drawParts() {
        // if (parseInt(window.wheelDataGlobal.inner_wheel)) {
        //     let spinwheelcanvas = document.getElementById("spin-wheel-canvas");
        //     let spinwheelcanvasColorContext = spinwheelcanvas.getContext("2d");
        //     if (spinwheelcanvasColorContext) {
        //         drawInnerCircle(
        //             spinwheelcanvasColorContext,
        //             window.wheelDataGlobal,
        //             window.centerx
        //         );
        //     }
        //     let spinwheelcanvasImageContext = spinwheelcanvas.getContext("2d");
        //     if (
        //         window.wheelDataGlobal.center_image &&
        //         spinwheelcanvasImageContext
        //     ) {
        //         drawInnerImage(
        //             spinwheelcanvasImageContext,
        //             window.wheelDataGlobal,
        //             window.centerx
        //         );
        //     }
        // }
    }

    function drawInnerCircle(spinwheelcanvasColorContext, wheelData, centerx) {
        spinwheelcanvasColorContext.strokeStyle = '#ffffff';
        spinwheelcanvasColorContext.fillStyle = wheelData.center_color;
        spinwheelcanvasColorContext.lineWidth = strokeWidth
        spinwheelcanvasColorContext.beginPath();
        // console.log(
        //     "Drawing inner circle with radius: ", {
                
        //     centerx,
        //     height: height / 2,
        //     radius: wheelData.inner_radius,
        //     s: 0,
        //     b: 2 * Math.PI
        //     });
        spinwheelcanvasColorContext.arc(
            centerx,
            height / 2,
            radiusWidth,
            0,
            2 * Math.PI
        );
        spinwheelcanvasColorContext.stroke();
        spinwheelcanvasColorContext.fill();
    }

    function drawInnerImage(spinwheelcanvasImageContext, wheelData, centerx) {
        let squareSide = wheelData.inner_radius * 1.41421;
        let centerImg = new Image();
        centerImg.onload = function () {
            spinwheelcanvasImageContext.drawImage(
                centerImg,
                centerx - squareSide / 2,
                height / 2 - squareSide / 2,
                squareSide,
                squareSide
            );
        };
        centerImg.src = wheelData.media_url + wheelData.center_image;
        window.spincenterImg = centerImg;
    }

    function getHtml(area, wheelData, data) {
        let html = "";
        html +=
            '<div id="spin-wheel-container">' +
            '<div id="spin-wheel-wheel-container" style="min-height:' +
            (height + 40) +
            'px">' +
            '<canvas id="spin-wheel-canvas" width=' +
            width +
            " height=" +
            height +
            ">" +
            "Canvas not supported, use another browser." +
            "</canvas>";

        html +=
            '<div id="spin-pin-image-container">' +
            '<img id="spin-pin-image" src="' +
            wheelData.media_url +
            wheelData.pin_image +
            '"/>' +
            "</div>" +
            "</div>" +
            getForm(area, data) +
            "</div>";
        return html;
    }

    function getForm(area, data) {
        let inpForm = "";
        if (area == "frontend") {
            let imgsrc = "";
            if (
                data.welcome.logo != undefined &&
                data.welcome.logo != null &&
                data.welcome.logo != ""
            ) {
                imgsrc = data.mediaUrl + data.welcome.logo;
            }
            inpForm +=
                '<div id="spin-wheel-form-container" class="spin-wheel-welcome-form" style="color:' +
                data.welcome.text_color +
                ";background-color:" +
                data.welcome.background_color +
                ';min-height:520px;">' +
                '<img id="spin-wheel-form-image" src="' +
                imgsrc +
                '">' +
                '<h1 id="spin-wheel-form-heading">' +
                $t(data.welcome.heading) +
                "</h1>" +
                '<p id="spin-wheel-form-description">' +
                $t(data.welcome.description) +
                "</p>" +
                '<div id="spin-wheel-sub-form-container">' +
                '<form id="spin-wheel-form">' +
                '<input type="hidden" name="spin-wheel-id" value="' +
                data.layout.spin_id +
                '">' +
                '<fieldset class="fieldset">';
            // if (parseInt(data.welcome.cname_status)) {
            //     if (parseInt(data.welcome.cname_required)) {
            //         inpForm +=
            //             '<div class="field required">' +
            //             '<label class="label" for="spin-wheel-name"><span>' +
            //             $t(data.welcome.cname_label) +
            //             "</span></label>" +
            //             '<div class="control">' +
            //             '<input type="text" id="spin-wheel-name" name="spin-wheel-name" value="" class="input-text required-entry" autocomplete="off">' +
            //             "</div>" +
            //             "</div>";
            //     } else {
            //         inpForm +=
            //             '<div class="field">' +
            //             '<label class="label" for="spin-wheel-name"><span>' +
            //             $t(data.welcome.cname_label) +
            //             "</span></label>" +
            //             '<div class="control">' +
            //             '<input type="text" id="spin-wheel-name" name="spin-wheel-name" value="" class="input-text" autocomplete="off">' +
            //             "</div>" +
            //             "</div>";
            //     }
            // }
            // inpForm +=
            //     '<div class="field required">' +
            //     '<label class="label" for="spin-wheel-email"><span>' +
            //     $t(data.welcome.cemail_label) +
            //     "</span></label>" +
            //     '<div class="control">' +
            //     '<input type="text" id="spin-wheel-email" name="spin-wheel-email" value="" class="input-text required-entry validate-email" autocomplete="off">' +
            //     "</div>" +
            //     "</div>";

            //add submit button
            inpForm +=
                '<div id="spin-wheel-button-container">' +
                '<button id="spin-wheel-button" type="submit" style="color:' +
                data.welcome.button_text_color +
                ";background-color:" +
                data.welcome.button_background_color +
                '">' +
                '<img src="' +
                data.mediaUrl +
                'spintowin/image/wheel.png"/>' +
                "<span>" +
                $t(data.welcome.button_label) +
                "</span>" +
                "</button>" +
                "</div>";
            inpForm += "</fieldset>" + "</form>";
            if (parseInt(data.welcome.show_progress)) {
                inpForm +=
                    '<div class="spin-progress-bar-container">' +
                    '<div class="spin-progress-bar">' +
                    '<div class="spin-progress-bar-progress" style="width: ' +
                    data.welcome.progress_percent +
                    '%"></div>' +
                    "</div>" +
                    '<span class="spin-progress-bar-message">' +
                    $t(data.welcome.progress_label) +
                    "!</span>" +
                    "</div>";
            }
            inpForm += "</div>";

            inpForm +=
                '<div id="wkcc-result-coupon-container" style="display:none;">' +
                '<span class="spin-coupon-code-label">' +
                $t("Coupon Code") +
                "</span>" +
                '<div class="spin-coupon-code-container">' +
                '<input class="spin-coupon-code" style="background:' +
                data.result.coupon_background_color +
                ";border-color:" +
                data.result.coupon_button_background_color +
                ";color:" +
                data.result.coupon_text_color +
                '" readonly="readonly" value="">' +
                '<div style="background: ' +
                data.result.coupon_button_background_color +
                ";color:" +
                data.result.coupon_button_text_color +
                ";border-color:" +
                data.result.coupon_button_background_color +
                ';" class="spin-coupon-code-copy">' +
                $t("Copy Code") +
                "</div>" +
                "</div>" +
                '<button type="button" id="wkcc-shop-now" style="color:' +
                data.result.button_text_color +
                ";background:" +
                data.result.button_background_color +
                '">' +
                $t(data.result.button_label) +
                "</button>" +
                "</div>";
            inpForm += "</div>";
        }
        inpForm +=
            '<button class="action-close" data-role="closeBtn" type="button">' +
            "<span>Close</span>" +
            "</button>";
        return inpForm;
    }

    function setBackground(wheelData) {
        $("#spin-wheel-wheel-container").css(
            "background-color",
            wheelData.background_color
        );
        if (wheelData.background_image) {
            $("#spin-wheel-wheel-container").css(
                "background-image",
                "url(" + wheelData.media_url + wheelData.background_image + ")"
            );
            $("#spin-wheel-wheel-container").css(
                "background-repeat",
                wheelData.background_image_repeat
            );
        }
    }

    function getSegments(wheelData) {
        let segments = [];
        let segmentsColor = [];
        try {
            segmentsColor = JSON.parse(wheelData.segments);
        } catch (error) {
            segmentsColor = [];
        }
        let count = 0;
        $.each(wheelData.segments_label, function (ind, val) {
            count++;
            let bgColor = "#c1c1c1";
            let textColor = "#000000";
            if (segmentsColor && segmentsColor.length) {
                let segInd = ind % segmentsColor.length;
                bgColor = segmentsColor[segInd][0];
                textColor = segmentsColor[segInd][1];
            }
            if (wheelData.segments != null && JSON.parse(wheelData.segments).length != 0) {
                if (count <= segmentsColor.length) {
                    segments.push({
                        text: val,
                        fillStyle: bgColor,
                        textFillStyle: textColor,
                    });
                }
            } else {
                segments.push({
                    text: val,
                    fillStyle: bgColor,
                    textFillStyle: textColor,
                });
            }
        });
        return segments;
    }

    return (result = {
        drawWheel: drawWheel,
        drawParts: drawParts,
    });
});

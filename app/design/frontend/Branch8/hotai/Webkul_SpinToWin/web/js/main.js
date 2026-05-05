require([
    "jquery",
    "mage/translate",
    "Magento_Ui/js/modal/alert",
    "Magento_Ui/js/modal/modal",
    "spinwheelmain",
    "mage/cookies",
], function ($, $t, alert, modal, spinwheelmain) {
    $(function () {
        // var isShownForEvents = false;
        // var data = { current_url: location.href };
        // var spinWheel;
        // var spinSuccess = false;
        // var isNameRequired = 1;
        // $.ajax({
        //     type: "POST",
        //     url: window.BASE_URL + "spintowin/index/index",
        //     data: data,
        //     dataType: "json",
        //     cache: false,
        //     success: function (response) {
        //         if (response.success) {
        //             if (response.isempty) {
        //                 wonCoupon();
        //             } else {
        //                 data = response.data;
        //                 isNameRequired = data.welcome.cname_status * 1;
        //                 processSpin(data);
        //             }
        //         }
        //     },
        //     error: function (response) { },
        // });
        // var wonCoupon = function () {
        //     var coupondata = $.cookie("spintowin_coupon");
        //     if (coupondata) {
        //         try {
        //             coupondata = JSON.parse(coupondata);
        //             if (coupondata.length && parseInt(coupondata[0].coupon.show)) {
        //                 let html = "";
        //                 html +=
        //                     '<button type="button" class="spin-sticky-button ' +
        //                     coupondata[0].layout.trigger_button_position +
        //                     '" id="spin-coupon" style="color:' +
        //                     coupondata[0].coupon.text_color +
        //                     ";background-color:" +
        //                     coupondata[0].coupon.background_color +
        //                     '">' +
        //                     '<img id="spin-coupon-image" src="' +
        //                     coupondata[0].mediaUrl +
        //                     'spintowin/image/coupon.png"/>' +
        //                     '<span id="spin-coupon-label">' +
        //                     $t(coupondata[0].coupon.label) +
        //                     "</span>" +
        //                     "</button>";

        //                 $("body").append(html);
        //             }
        //         } catch (error) {
        //             console.log(error);
        //         }
        //     }
        // };
        // $("body").on("click", "#spin-coupon", function () {
        //     var coupondatas = $.cookie("spintowin_coupon");
        //     if (coupondatas) {
        //         try {
        //             coupondatas = JSON.parse(coupondatas);
        //             var options = {
        //                 type: "popup",
        //                 responsive: true,
        //                 innerScroll: false,
        //                 modalClass: "spin-coupon-details",
        //             };
        //             let html = "";
        //             coupondatas.forEach((coupondata) => {
        //                 if (parseInt(coupondata.coupon.show)) {
        //                     html +=
        //                         '<div class="spin-coupon-info-container" style="color:' +
        //                         coupondata.result.text_color +
        //                         ";background:" +
        //                         coupondata.result.background_color +
        //                         ';">' +
        //                         '<h1 class="spin-coupon-info-heading">' +
        //                         coupondata.segment.heading +
        //                         "</h1>" +
        //                         '<p class="spin-coupon-info-description">' +
        //                         coupondata.segment.description +
        //                         "</p>" +
        //                         '<span class="spin-coupon-code-label">' +
        //                         $t("Coupon Code") +
        //                         "</span>" +
        //                         '<div class="spin-coupon-code-container">' +
        //                         '<input class="spin-coupon-code" style="background:' +
        //                         coupondata.result.coupon_background_color +
        //                         ";border-color:" +
        //                         coupondata.result
        //                             .coupon_button_background_color +
        //                         ";color:" +
        //                         coupondata.result.coupon_text_color +
        //                         '" readonly="readonly" value="' +
        //                         coupondata.segment.coupon +
        //                         '">' +
        //                         '<div style="background: ' +
        //                         coupondata.result
        //                             .coupon_button_background_color +
        //                         ";color:" +
        //                         coupondata.result.coupon_button_text_color +
        //                         ";border-color:" +
        //                         coupondata.result
        //                             .coupon_button_background_color +
        //                         ';" class="spin-coupon-code-copy">' +
        //                         $t("Copy Code") +
        //                         "</div>" +
        //                         "</div>" +
        //                         "</div>" +
        //                         "</div>";
        //                     html +=
        //                         '<button class="action-close" data-role="closeBtn" type="button">' +
        //                         "<span>Close</span>" +
        //                         "</button>";
        //                 }
        //             });
        //             var cont = $("<div>").append(html);
        //             modal(options, cont);
        //             cont.modal("openModal");
        //         } catch (error) {
        //             console.log(error);
        //         }
        //     }
        // });
        // var processSpin = function (data) {
        //     if (isShowTrigger(data)) {
        //         showTrigger(data);
        //     }
        //     if (isShowWheel(data)) {
        //         applyEvents(data);
        //     }
        // };
        // var isShowTrigger = function (data) {
        //     let result = false;
        //     if (parseInt(data.button.show)) {
        //         if (data.visibility.button != null) {
        //             let classes = $("body").attr("class").split(" ");
        //             let toshowon = data.visibility.button.split(",");
        //             $.each(toshowon, function (ind, val) {
        //                 if ($.inArray(val, classes) !== -1) {
        //                     result = true;
        //                     return false;
        //                 }
        //             });
        //         } else {
        //             result = true;
        //         }
        //     }
        //     return result;
        // };
        // var isShowWheel = function (data) {
        //     let result = false;
        //     if (data.visibility.events.split("_")[0] != "") {
        //         if (data.visibility.wheel != null) {
        //             let classes = $("body").attr("class").split(" ");
        //             let toshowon = data.visibility.wheel.split(",");
        //             $.each(toshowon, function (ind, val) {
        //                 if ($.inArray(val, classes) !== -1) {
        //                     result = true;
        //                     return false;
        //                 }
        //             });
        //         } else {
        //             result = true;
        //         }
        //     }
        //     return result;
        // };
        // var applyEvents = function (data) {
        //     if (data.visibility.events.split("_")[0] == "after") {
        //         setTimeout(
        //             function () {
        //                 if (!isShownForEvents) {
        //                     showWelcome(data);
        //                 }
        //             },
        //             parseInt(data.visibility.events.split("_")[1] * 1000),
        //             data
        //         );
        //     } else if (data.visibility.events.split("_")[0] == "immediate") {
        //         if (!isShownForEvents) {
        //             showWelcome(data);
        //         }
        //     } else if (data.visibility.events.split("_")[0] == "scroll") {
        //         $(window).scroll(function () {
        //             if (
        //                 $(window).scrollTop() >=
        //                 (($(document).height() - $(window).height()) *
        //                     data.visibility.events.split("_")[1]) /
        //                 100
        //             ) {
        //                 if (!isShownForEvents) {
        //                     showWelcome(data);
        //                 }
        //             }
        //         });
        //     } else {
        //         $(document).mouseleave(function () {
        //             if (!isShownForEvents) {
        //                 showWelcome(data);
        //             }
        //         });
        //     }
        // };
        // var showTrigger = function (data) {
        //     let html = "";
        //     html +=
        //         '<button type="button" class="spin-sticky-button ' +
        //         data.layout.trigger_button_position +
        //         '" id="spin-trigger" data-spin-id="' +
        //         data.button.spin_id +
        //         '" style="color:' +
        //         data.button.text_color +
        //         ";background-color:" +
        //         data.button.background_color +
        //         '">' +
        //         '<img id="spin-trigger-image" src="' +
        //         data.mediaUrl +
        //         data.button.image +
        //         '"/>' +
        //         '<span id="spin-trigger-label">' +
        //         $t(data.button.label) +
        //         "</span>" +
        //         "</button>";

        //     $("body").append(html);
        // };

        // var showWelcome = function (data) {
        //     isShownForEvents = true;
        //     spinWheel = spinwheelmain.drawWheel("frontend", data.wheel, data);
        //     isShown = true;
        //     var form = $("#spin-wheel-form");
        //     form.mage("validation", {});
        // };

        // $("body").on("click", "#spin-trigger", function () {
        //     showWelcome(data);
        // });
        // $("body").on("click", "#wkcc-shop-now", function () {
        //     $(this)
        //         .closest(".modal-inner-wrap")
        //         .find(".action-close")
        //         .trigger("click");
        //     location.reload();
        // });
        // $("body").on("click", ".spin-coupon-code-copy", function () {
        //     var couponCode = $(this).prev(".spin-coupon-code");
        //     couponCode.select();
        //     document.execCommand("copy");
        // });
        // $('body').on('spinModalClosed', function () {
        //     if (spinSuccess) {
        //         location.reload();
        //     }
        // });
        // $("body").on("submit", "#spin-wheel-form", function (e) {
        //     e.preventDefault();
        //     var form = $("#spin-wheel-form");
        //     if (
        //         $("input[name=spin-wheel-email]").val() === undefined ||
        //         $("body").find("#spin-wheel-canvas").length == 0
        //     ) {
        //         location.reload();
        //     } else {
        //         if (isNameRequired) {
        //             if ($("input[name=spin-wheel-name]").val() === undefined) {
        //                 location.reload();
        //             }
        //         }
        //         if ($(form).validation("isValid")) {
        //             $.ajax({
        //                 type: "POST",
        //                 url: window.BASE_URL + "spintowin/index/check",
        //                 data: $(this).serialize(),
        //                 dataType: "json",
        //                 cache: false,
        //                 beforeSend: function () {
        //                     $("body").trigger("processStart");
        //                 },
        //                 success: function (response) {
        //                     spinSuccess = true;
        //                     $("body").find("#spin-trigger").remove();
        //                     $("body").trigger("processStop");
        //                     if (response.success) {
        //                         $("#spin-wheel-button").prop(
        //                             "disabled",
        //                             "disabled"
        //                         );
        //                         let stopAt = spinWheel.getRandomForSegment(
        //                             response.data.segment
        //                         );
        //                         spinWheel.animation.stopAngle = stopAt;
        //                         spinWheel.startAnimation();
        //                         setTimeout(
        //                             function (response, spinWheel) {
        //                                 spinWheel.segments[
        //                                     response.data.segment
        //                                 ].textFillStyle =
        //                                     window.wheelDataGlobal.result_text_color;
        //                                 spinWheel.segments[
        //                                     response.data.segment
        //                                 ].fillStyle =
        //                                     window.wheelDataGlobal.result_background_color;
        //                                 spinWheel.draw();
        //                                 spinwheelmain.drawParts();
        //                                 $("body")
        //                                     .find(
        //                                         "#spin-wheel-sub-form-container"
        //                                     )
        //                                     .hide();
        //                                 let imgsrc = "";
        //                                 if (window.resultForm.logo) {
        //                                     imgsrc =
        //                                         window.mediaUrl +
        //                                         window.resultForm.logo;
        //                                 }
        //                                 $("body")
        //                                     .find("#spin-wheel-form-image")
        //                                     .attr("src", imgsrc);
        //                                 $("body")
        //                                     .find("#spin-wheel-form-container")
        //                                     .css(
        //                                         "background",
        //                                         window.resultForm
        //                                             .background_color
        //                                     );
        //                                 $("body")
        //                                     .find("#spin-wheel-form-container")
        //                                     .css(
        //                                         "color",
        //                                         window.resultForm.text_color
        //                                     );
        //                                 $("body")
        //                                     .find("#spin-wheel-form-heading")
        //                                     .text(response.data.heading);
        //                                 $("body")
        //                                     .find(
        //                                         "#spin-wheel-form-description"
        //                                     )
        //                                     .text(response.data.description);
        //                                 if (parseInt(response.data.type)) {
        //                                     $("body")
        //                                         .find("input.spin-coupon-code")
        //                                         .val(response.data.coupon);
        //                                     $("body")
        //                                         .find(
        //                                             "#wkcc-result-coupon-container"
        //                                         )
        //                                         .show();
        //                                     $.ajax({
        //                                         type: "POST",
        //                                         url:
        //                                             window.BASE_URL +
        //                                             "spintowin/index/notify",
        //                                         data: response.data.notify,
        //                                         dataType: "json",
        //                                         cache: false,
        //                                     });
        //                                 }
        //                             },
        //                             5050,
        //                             response,
        //                             spinWheel
        //                         );
        //                     } else {
        //                         alert({
        //                             title: $t("Error"),
        //                             content: $t(response.msg),
        //                         });
        //                     }
        //                 },
        //                 error: function (response) {
        //                     $("body").trigger("processStop");
        //                 },
        //             });
        //         }
        //     }
        // });
    });
});

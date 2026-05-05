define([
    'ko',
    'uiComponent',
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer-info',
    'Branch8_Spin2Win/js/model/spin-data',
    'Magento_Ui/js/modal/modal',
    'spinwheelmain',
    'plugins/DOMPurify',
    'Branch8_Spin2Win/js/dompurify-config',
    'matchMedia'
], function (ko, Component, $, urlBuilder, customerData, customerInfomation, spinData, modal, spinwheelmain, DOMPurify, getSanitizeConfig) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Branch8_Spin2Win/view'
        },
        placeholderNoItemImgUrl: ko.observable(''),
        placeholderCouponImgUrl: ko.observable(''),
        placeholderTicketImgUrl: ko.observable(''),
        placeholderDefaultImgUrl: ko.observable(''),
        placeholderPointsImgUrl: ko.observable(''),
        placeholderCouponPPImgUrl: ko.observable(''),
        placeholderTicketPPImgUrl: ko.observable(''),
        placeholderDefaultPPImgUrl: ko.observable(''),
        placeholderPointsPPImgUrl: ko.observable(''),
        notificationUrl: ko.observable(''),
        pointUrl: ko.observable(''),
        virtualUrl: ko.observable(''),
        segmentLoseType: ko.observable(0),
        segmentCouponType: ko.observable(1),
        segmentVirtualType: ko.observable(2),
        segmentPhysicalType: ko.observable(3),
        segmentRewardPointType: ko.observable(4),
        spinDailyXTimeType: ko.observable(1),
        spinFixedXTimeType: ko.observable(2),
        isReady: ko.observable(false),
        isTriggerBtnVisible: ko.observable(false),
        title1: ko.observable('百萬名車'),
        title2: ko.observable('大禮包'),
        winningDescription: ko.observable(''),
        bkgImage: ko.observable(null),
        wheelDataGlobal: window.wheelDataGlobal,
        data: spinData.data,
        // customer: spinData.customer,
        playerData: spinData.playerData,
        chances: ko.observable(0),
        // hasChances: ko.observable(false),
        cumulativeAmount: ko.observable(0),
        cumulativeTotalAmount: ko.observable(0),
        prizes: ko.observableArray([]),
        winningRecords: ko.observableArray([]),
        winningRecordsCount: ko.observable(0),
        winningRecordsLimit: ko.observable(4),
        winningRecordsCurrentPage: ko.observable(1),
        redeemPoints: ko.observable(0),
        hasConsolation: ko.observable(false),
        luckyDrawDes: ko.observable('立即使用和泰聯名卡消費，可獲得更多和泰Points，兌換抽獎機會!'),
        spinWheelImgUrl: ko.observable(''),
        spinId: ko.observable(''),
        mediaUrl: ko.observable(''),
        luckyOption: ko.observable(window.localStorage.getItem('luckyOption')),
        luckyOptionCsId: ko.observable(window.localStorage.getItem('luckyOptionCsId')),
        spinWheel: ko.observable({pc: 
          {
            width: 414,
            height: 414
          },
          mobile: {
            width: 276,
            height: 276
          }
        }),
        spinWheelSetting: ko.observable({
          pc: {
            width: 414,
            height: 414
          },
          mobile: {
            width: 276,
            height: 276
          }
        }),
        spinWheelName: ko.observable(spinData.customer?.fullname || ''),
        spinWheelEmail: ko.observable(spinData.customer?.email || ''),
        isAllowToRedeem: ko.observable(false),

        initialize: function () {
            this._super();
            // console.log(this.placeholderNoItemImgUrl, this.placeholderTicketImgUrl, this.placeholderCouponImgUrl, this.placeholderDefaultImgUrl, this.placeholderPointsImgUrl, this.wheelDataGlobal);
            if (this.bkgImage && this.bkgImage.pc) {
              $('.spin-view__winning-bkg-top').attr('style', 'background-image: url("'+this.bkgImage.pc+'");');
            }

            this.initPopup();
            this.customer = customerInfomation.customer();

            console.log('init', this.customer, this.data, this.spinId, this.luckyOption());

            if(this.spinId) {
              this.callSpinAjaxData();
              this.callAjaxData();
            }

            setTimeout(() => {
              if (!$('body').hasClass('page-loaded')) {
                  $('body').addClass('page-loaded');
                  // console.log('page-loaded');
              }
            }, 600);
        },

        isLogin: function() {
            var customerInfo = customerData.get('customer')();
            return (customerInfo.firstname && customerInfo.fullname) && !(customerInfo.isSeller || customerInfo.isWaitForSeller || customerInfo.isSubAccount);
        },

        initObservable: function () {
            const self = this;
            this._super();

            this.isShowMoreBtn = ko.computed(function () {
               return self.winningRecordsCount() > self.winningRecordsLimit() * self.winningRecordsCurrentPage();
            }, this);

            this.hasChances = ko.computed(function () {
                return self.chances() > 0;
            }, this);

            this.showRedeemSection = ko.computed(function () {
                return self.isAllowToRedeem() && self.redeemPoints() > 0 && !self.hasChances() && self.isReady();
            }, this);

            var customerInfo = customerData.get('customer')();

            if(customerInfo) {
              self.spinWheelName(customerInfo.fullname || '');
              self.spinWheelEmail(customerInfo.email || '');
            }

            this.data.subscribe(function (newData) {
              const layout = newData?.layout || null;
              const mediaUrl = newData?.mediaUrl || '';
              self.mediaUrl(mediaUrl);
              if(layout && layout?.desktop_background_image && mediaUrl) {
                const pcImgUrl = mediaUrl + layout.desktop_background_image;
                const mobileImgUrl = layout.mobile_background_image? newData.mediaUrl + layout.mobile_background_image : '';
                self.bkgImage({pc: pcImgUrl, mobile: mobileImgUrl});
              }

              if(layout) {
                self.luckyDrawDes(layout?.hint_text);
                self.title1(layout?.page_title || self.title1());
                self.title2(layout?.page_title2 || self.title2());
                $('#modal-lucky-draw-des').modal('setTitle', layout?.hint_title);
                $('#luckyDrawDes').html(layout?.hint_text || self.luckyDrawDes());
              }

              const wheel = newData?.wheel || null;
              if (wheel) {
                self.prizes(wheel.segments_label || []);
                self.spinWheelImgUrl(mediaUrl + wheel.pin_image);
              }

              const info = newData?.info || null;
              if (info) {
                self.redeemPoints(info.point_to_drawn || 0);
                $('#conirmDrawPoint').html(info.point_to_drawn || self.redeemPoints());
                self.isAllowToRedeem(info.allow_redeem_point === "1" || false);
                self.winningDescription(info.description || '');
              }

              const consolation = newData?.consolation || null;
              if (consolation) {
                self.hasConsolation(consolation?.is_active ==="1" || false);
                if(consolation?.fail_times) {
                  let failedChances = self.cumulativeAmount() || 0;
                  failedChances = failedChances >= consolation.fail_times ? consolation.fail_times - 1 : failedChances;
                  self.cumulativeAmount(failedChances);
                  self.cumulativeTotalAmount(consolation.fail_times);
                } else{
                  self.cumulativeTotalAmount(0);
                }
              }

              console.log('spintowin:', this.data, spinData, layout, wheel, info, consolation);
              self.isReady(true);

              if(layout && layout?.base_frame_color1) {
                $('.spin-view__winning-record-wrapper').css('background-color', layout.base_frame_color1);
              }
              if(layout && layout?.base_frame_color2) {
                $('.spin-view__winning-record-border-top').css('background-color', layout.base_frame_color2);
              }
              if(layout && layout?.base_frame_color3) {
                $('.spin-view__winning-record-info').css('background-color', layout.base_frame_color3);
              }
              // showing wheel
              self.showWelcome(newData);
            });

            this.bkgImage.subscribe(function (newImage) {
                self.setBkgPage(newImage.mobile || newImage.pc);
            });

            this.prizes.subscribe(function (newPrizes) {
              // console.log('Spin2Win View: Prizes updated', newPrizes);
            });

            this.playerData.subscribe(function (newPlayerData) {
              const chances = newPlayerData?.chances || 0;
              self.chances(chances);

              let failedChances = parseInt(newPlayerData?.failed_chances || "0");
              if(self.data()?.consolation && self.data().consolation.fail_times) {
                const consolationFailTimes = parseInt(self.data().consolation.fail_times || "0");
                if(failedChances >= consolationFailTimes) {
                  failedChances = consolationFailTimes - 1;
                }
              }
              self.cumulativeAmount(failedChances);

              const prizes = newPlayerData?.prizes || [];
              self.winningRecords(prizes);
              self.winningRecordsCount(prizes.length);

              const layout = self.data()?.layout || null;

              if(layout && layout?.main_color_win_record) {
                $('.spin-view__winning-record-list').css('background-color', layout.main_color_win_record);
              }

            });

            this.isReady.subscribe(function (isReady) {
              console.log('spintowin: isReady state changed', isReady);
              if (isReady) {
              $('.spin-view-wrapper').addClass('ready');
              } else {
                $('.spin-view-wrapper').removeClass('ready');
              }
            });

            this.setSpinWheelCanvasLayout();

            $("body").trigger("processStop");
            return this;
        },

        callAjaxData: function () {
            const self = this;
            $.ajax({
                type: "GET",
                url: urlBuilder.build("spintowin/campaign/player/sid/") + self.spinId,
                dataType: "json",
                cache: false,
                success: function (response) {
                  if(response?.data) {
                    spinData.playerData(response.data);
                    self.playerData(response.data);
                    if(!self.isAllowToRedeem() && response.data?.chances < 1 ) {
                      $('#winningReturn').addClass('hide');
                    }
                  }
                },
                error: function (response) {
                  console.error('callAjaxData error:', response);
                }
            }).always(function () {
                // console.log('callAjaxData finally');
            });
        },  

        callSpinAjaxData: function () {
            const self = this;
            $.ajax({
                type: "POST",
                url: urlBuilder.build("spintowin/index/index"),
                data: { current_url: location.href, spinId: this.spinId },
                dataType: "json",
                cache: false,
                success: function (response) {
                    if (response.success) {
                        spinData.data(response.data);
                        self.data(response.data);
                        console.log('spintowin: AJAX response:', response, spinData);
                        self.data(response.data);
                        const newData = response.data;
                        const button = newData?.button;
                        if(button) {
                            if(button?.label) {
                            self.title(button.label);
                            }

                            if(button?.image && newData.mediaUrl) {
                                self.imgUrl(newData.mediaUrl + button.image);
                            }
                            if(button?.show && button.show !== '0' && self.isLogin()) {
                                self.isTriggerBtnVisible(true);
                                $('.spin-trigger-wrapper').addClass('show');
                            } else {
                                $('.spin-trigger-wrapper').removeClass('show');
                            }
                        }
                    }
                },
                error: function (response) {
                  console.error('callAjaxData error:', response);
                },
                finally: function(response) {
                  console.error('callAjaxData finally:', response);
                }
            });
        },

        showWelcome: function (data) {
          const wheel = data.wheel;
          const layout = data.layout;
          if (wheel) {
            let segmentsLabel = wheel.segments_label || [];
            let updatedsSegments = [];
            segmentsLabel.forEach(function (segment, index) {
              // console.log('Spin2Win View: Segment', index, segment);
              const segmentItem = [];
              if(index%2 === 0) {
                segmentItem[0] = layout?.main_color_of_the_roulette || '#fecb11'
                segmentItem[1] = layout?.main_text_color_of_the_roulette || '#000000'; // should add text color for odd segments
              }
              else {
                segmentItem[0] = layout?.secondary_color_of_the_roulette || 'rgba(254, 202, 17, 0.1)';
                segmentItem[1] = layout?.secondary_text_color_of_the_roulette || '#ffffff';
              }
              updatedsSegments.push(segmentItem);
            });
            wheel.segments = JSON.stringify(updatedsSegments);
            wheel.sizes = this.spinWheelSetting();
            // console.log('Spin2Win View: Updated segments', JSON.stringify(updatedsSegments) , wheel, data);
            this.spinWheel(spinwheelmain.drawWheel("frontend", wheel, data));
            var form = $("#spinWheelForm");
            form.mage("validation", {});
          }
        },

        setBkgPage: function (imageUrl) {
          var self = this;
            mediaCheck({
                media: '(min-width: 769px)',
                entry: function () {
                  if(self.bkgImage() && self.bkgImage().pc) {
                    $('.spin-view__winning-bkg-top').attr('style', 'background-image: url("' + self.bkgImage().pc + '");');
                  }
                },
                exit: function () {
                  if(self.bkgImage() && self.bkgImage().mobile) {
                    $('.spin-view__winning-bkg-top').attr('style', 'background-image: url("' + self.bkgImage().mobile + '");');
                  }
                },
            });
        },

        setSpinWheelCanvasLayout: function () {
          // console.log('Spin2Win View: Setting Spin Wheel Canvas Layout');
          var self = this;
          mediaCheck({
              media: '(min-width: 769px)',
              entry: function () {
                // console.log('Spin2Win View: Setting Spin Wheel Canvas for desktop', self.spinWheel(), self.spinWheelSetting());
                if(self.spinWheel() && self.spinWheel().pc) {
                  $('#spin-wheel-canvas').attr('width', self.spinWheel().pc.width);
                  $('#spin-wheel-canvas').attr('height', self.spinWheel().pc.height);
                  $('#spin-wheel-canvas').css({
                    width: self.spinWheel().pc.width + 'px',
                    height: self.spinWheel().pc.height + 'px'
                  });
                }
              },
              exit: function () {
                // console.log('Spin2Win View: Setting Spin Wheel Canvas for mobile', self.spinWheel(), self.spinWheelSetting());
                if(self.spinWheel() && self.spinWheel().mobile) {
                  $('#spin-wheel-canvas').attr('width', self.spinWheel().mobile.width);
                  $('#spin-wheel-canvas').attr('height', self.spinWheel().mobile.height);
                  $('#spin-wheel-canvas').css({
                    width: self.spinWheel().mobile.width + 'px',
                    height: self.spinWheel().mobile.height + 'px'
                  });
                }
              },
          });
        },

        formatText: function (text) {
          if(!text)  return text;
          return text.replace(/(\d+)/g, "<span class='number'>$1</span>")
                       .replace(/(點)/g, "<span class='unit'>$1</span>")
                       .replace(/(元)/g, "<span class='unit'>$1</span>");

        },

        spin: function () {
            const self = this;
            var jQ = $.noConflict();
            // if (!self.isLogin()) {
            //     console.log('Spin2Win View: User is not logged in, cannot spin.');
            //     window.location.href = encodeURI(window.BASE_URL + 'customer/account/login');
            //     return;
            // }

            const chances = self.chances();
            if(chances <= 0) {
                if(!self.isAllowToRedeem()) {
                  self.showNotAllowToRedeem();
                  return;
                }
                let oldLuckyOption = self.luckyOption();
                let oldLuckyOptionCsId = self.luckyOptionCsId();
                const customerInfo = customerData.get('customer')();
                const customerDataId = parseInt(customerInfo?.customer_id) || '';
                if(typeof oldLuckyOption === 'string') {
                  oldLuckyOption = oldLuckyOption === 'true' ? true : false;
                }
                // console.log('Spin2Win View: Customer data ID changed, resetting lucky option', customerDataId, parseInt(oldLuckyOptionCsId), customerDataId !== parseInt(oldLuckyOptionCsId));
                if(customerDataId !== parseInt(oldLuckyOptionCsId)) {
                  oldLuckyOption = false;
                  self.luckyOption(false);
                  self.luckyOptionCsId(customerDataId);
                  window.localStorage.removeItem('luckyOption');
                  window.localStorage.removeItem('luckyOptionCsId');
                }
                if(oldLuckyOption) {
                  self.confirmDraw();
                } else {
                  self.triggerConfirmDrawPopup();
                }
                return;
            }
            var form = jQ("#spinWheelForm");
            if (jQ(form).validation("isValid")) {
                const config = getSanitizeConfig();
                jQ.ajax({
                  type: "POST",
                  url: urlBuilder.build("spintowin/index/check"),
                  data: jQ(form).serialize(),
                  dataType: "json",
                  cache: false,
                  beforeSend: function () {
                    jQ('.spin-view__action-btn').prop("disabled", true);
                    jQ("body").trigger("processStart");
                  },
                  success: function (response) {
                    if (response.success) {
                      const segment = response.data;
                      if(!segment) return;
                      const spinWheel = self.spinWheel();
                      // console.log('Spin2Win View: Spin wheel object', spinWheel, segment);
                      let stopAt = spinWheel.getStopAtAngle(
                          response.data.segment
                      );
                      const ani = spinWheel.animation;
                      // console.log('Spin2Win View: Stop at angle', stopAt, ani, spinWheel.rotationAngle);
                      spinWheel.animation.stopAngle = stopAt;
                      spinWheel.rotationAngle = 0;
                      // spinWheel.animation.repeat = 10;
                      spinWheel.startAnimation();
                      setTimeout(
                        function (response, spinWheel) {
                            // spinWheel.segments[
                            //     response.data.segment
                            //     ].textFillStyle =
                            //     window.wheelDataGlobal.result_text_color;
                            // spinWheel.segments[
                            //     response.data.segment
                            //     ].fillStyle =
                            //     window.wheelDataGlobal.result_background_color;
                            spinWheel.draw();

                            // showing winning popup
                            self.callAjaxData();
                            jQ("body").trigger("processStop");
                            jQ('.spin-view__action-btn').removeAttr("disabled");
                            if(self.isRewardPointType(segment.type)) {
                              const totalPoints = DOMPurify.sanitize(segment?.total_points || 0, config);
                              $('.header .hotai-customer-points span').text(totalPoints);
                            }
                            console.log('Spin2Win View: Spin result segment',response);
                            // var chancesCheck = response?.chances || 2;
                            var hideReturnBtn = false;
                            if(!self.isAllowToRedeem() && response?.chances < 1 ) {
                              hideReturnBtn = true;
                            } else {
                              hideReturnBtn = false;
                            }

                            if(self.isLoseType(segment.type)) {
                              const failedChances = self.cumulativeAmount() + 1;
                              const totalAmount = self.cumulativeTotalAmount();
                              const consolation = response.data?.consolation || null;
                              jQ("#noWinCumulativeAmount").html(failedChances);
                              if(failedChances >= totalAmount && consolation ) {
                                const type =  consolation.consolation_type;

                                if(self.isCouponType(type)) {
                                  jQ("#winningImg").attr('src', self.placeholderCouponPPImgUrl);
                                  jQ("#copyCode").removeClass('hide');
                                  jQ("#copyCode").attr('data-coupon', consolation.coupon || '');
                                  jQ("#winningGo").addClass('hide');
                                }
                                else if(self.isVirtualType(type)) {
                                  jQ("#winningImg").attr('src', self.placeholderTicketPPImgUrl);
                                  jQ("#copyCode").addClass('hide');
                                  const serialNumberId = consolation?.serial_number_id || '';
                                  jQ("#winningGo").data('url', self.virtualUrl + 'id/' + serialNumberId);
                                  jQ("#winningGo").text($.mage.__('立即使用'));
                                  jQ("#winningGo").removeClass('hide');
                                }
                                else if(self.isPhysicalType(type)) {
                                  jQ("#winningImg").attr('src', self.placeholderDefaultPPImgUrl);
                                  jQ("#copyCode").addClass('hide');
                                  jQ("#winningGo").data('url', self.notificationUrl + 'smrpid/' + segment.smrpid);
                                  jQ("#winningGo").text($.mage.__('立即使用'));
                                  jQ("#winningGo").removeClass('hide');
                                }
                                else if(self.isRewardPointType(type)) {
                                  const totalPoints = DOMPurify.sanitize(segment?.total_points || 0, config);
                                  $('.header .hotai-customer-points span').text(totalPoints);
                                  jQ("#winningImg").attr('src', self.placeholderPointsPPImgUrl);
                                  jQ("#copyCode").addClass('hide');
                                  jQ("#winningGo").data('url', self.pointUrl);
                                  jQ("#winningGo").text($.mage.__('查看點數'));
                                  jQ("#winningGo").removeClass('hide');
                                }
                                const name = self.formatText(consolation?.consolation_title || '');
                                jQ("#winningTitle").html(name);
                                const description = DOMPurify.sanitize(consolation?.consolation_description || '', config);
                                jQ("#winningDesc").html(description);
                                self.triggerWinningColabrationPopup();
                              } else {
                                if(self.hasConsolation()) {
                                jQ("#noWinCumulativeTotal").html(self.cumulativeTotalAmount());
                                jQ("#cumulativeProcessBar").css('width', (failedChances) / self.cumulativeTotalAmount() * 100 + '%');
                                  self.triggerNoWinPopup();
                                  $('.no-win-info-wrapper').removeClass('hide');
                                } else {
                                  self.triggerNoWinPopup();
                                  $('.no-win-info-wrapper').addClass('hide');
                                }
                              }
                            }else {
                              if(self.isCouponType(segment.type)) {
                                jQ("#winningImg").attr('src', self.placeholderCouponPPImgUrl);
                                jQ("#copyCode").removeClass('hide');
                                jQ("#copyCode").attr('data-coupon', segment.coupon || '');
                                jQ("#winningGo").addClass('hide');
                              }
                              else if(self.isVirtualType(segment.type)) {
                                jQ("#winningImg").attr('src', self.placeholderTicketPPImgUrl);
                                jQ("#copyCode").addClass('hide');
                                const serialNumberId = segment?.serial_number_id || '';
                                jQ("#winningGo").data('url', self.virtualUrl + 'id/' + serialNumberId);
                                jQ("#winningGo").text($.mage.__('立即使用'));
                                jQ("#winningGo").removeClass('hide');
                              }
                              else if(self.isPhysicalType(segment.type)) {
                                jQ("#winningImg").attr('src', self.placeholderDefaultPPImgUrl);
                                jQ("#copyCode").addClass('hide');
                                jQ("#winningGo").data('url', self.notificationUrl + 'smrpid/' + segment.smrpid);
                                jQ("#winningGo").text($.mage.__('立即使用'));
                                jQ("#winningGo").removeClass('hide');
                              }
                              else if(self.isRewardPointType(segment.type)) {
                                jQ("#winningImg").attr('src', self.placeholderPointsPPImgUrl);
                                jQ("#copyCode").addClass('hide');
                                jQ("#winningGo").data('url', self.pointUrl);
                                jQ("#winningGo").text($.mage.__('查看點數'));
                                jQ("#winningGo").removeClass('hide');
                              }
                              const name = self.formatText(segment.heading || segment.name || '');
                              jQ("#winningTitle").html(name);
                              const description = DOMPurify.sanitize(segment.description, config);
                              jQ("#winningDesc").html(description);
                              self.triggerWinningPopup(hideReturnBtn);
                            }
                        },
                        3000,
                        response,
                        spinWheel
                      );
                    } else {
                      if(response?.need_login) {
                        console.log('User needs to login, redirecting to login page');
                        window.location.href = urlBuilder.build("customer/account/login");
                      } else {
                        const message = DOMPurify.sanitize((response?.message || $.mage.__('發生錯誤，請重新嘗試。')), config);
                        jQ('#lotterySystemError').html(message);

                        const title = DOMPurify.sanitize((response?.message_title || $.mage.__('抽獎失敗')), config);
                        if($('#modal-lottery-failed').data('mageModal')) {
                          $('#modal-lottery-failed').modal('setTitle', title);
                        }
                        self.triggerLotteryFailedPopup();
                        jQ("body").trigger("processStop");
                        jQ('.spin-view__action-btn').removeAttr("disabled");
                      }
                    }
                  },
                  error: function (response) {
                    console.error('Spin error:', response);
                    const message = DOMPurify.sanitize((response?.message || $.mage.__('發生錯誤，請重新嘗試。')), config);
                    jQ('#lotterySystemError').html(message);

                    if($('#modal-lottery-failed').data('mageModal')) {
                      $('#modal-lottery-failed').modal('setTitle', $.mage.__('抽獎失敗'));
                    }

                    self.triggerLotteryFailedPopup();
                    jQ("body").trigger("processStop");
                    jQ('.spin-view__action-btn').removeAttr("disabled");
                  }
              }).always(function () {
                // console.log('Spin finally');
                // $("body").trigger("processStop");
                // $('.spin-view__action-btn').removeAttr("disabled");
              });
          }
        },

        showLuckyDrawDes: function () {
          this.triggerLuckyDrawPopup();
        },

        viewMore: function () {
          const currentPage = this.winningRecordsCurrentPage();
          const nextPage = currentPage + 1;
          this.winningRecordsCurrentPage(nextPage);
        },

        copyCouponCode: function (record, event) {
          if (record && record.prize_detail?.coupon) {
            const couponCode = record.prize_detail.coupon;
            this.copyCode(couponCode);
          }
        },

        copyCode: function (code) {
          const self = this;
          if (navigator.clipboard && window.isSecureContext) {
              // ✅ Modern Clipboard API
              navigator.clipboard.writeText(code)
                .then(() => {
                  self.showSuccess();
                })
                .catch(err => {
                  console.error('Spin2Win View: Failed to copy coupon code:', err);
                  // self.showMessage($.mage.__('Something went wrong.'), 'error');
                });
            } else {
              // 🔙 Fallback for older browsers
              const $temp = $('<textarea>');
              $('body').append($temp);
              $temp.val(code).select();

              try {
                const successful = document.execCommand('copy');
                if(successful) {
                  self.showSuccess();
                } else {
                  console.error('Spin2Win View: Fallback copy failed');
                }
              } catch (err) {
                console.error('Spin2Win View: Fallback copy failed', err);
              }

              $temp.remove();
            }
        },

        viewDetail: function (record, event) {
          const self = this;

          const type = record?.prize_type || '';
          if(record) {
            if(self.isLoseType(type)) {
              if(record?.consolation) {
                if(self.isCouponType(record.consolation.consolation_type)) {
                  const couponCode = record.consolation.consolation_detail.coupon || '';
                  self.copyCode(couponCode);
                } else if(self.isVirtualType(record.consolation.consolation_type)) {
                  const serialNumberId = record.consolation.consolation_detail.serial_number_id;
                  window.location.href = self.virtualUrl + 'id/' + serialNumberId;
                } else if(self.isPhysicalType(record.consolation.consolation_type)) {
                  const smrpid = record.prize_detail.smrpid || '';
                  window.location.href = self.notificationUrl + 'smrpid/' + smrpid;
                  // const comid = record.consolation.consolation_detail.comid || '';
                  // window.location.href = self.notificationUrl + 'comid/' + comid;
                } else if(self.isRewardPointType(record.consolation.consolation_type)) {
                  window.location.href = self.pointUrl;
                }
              }
            } else if(self.isCouponType(type)) {
              const couponCode = record.prize_detail.coupon || '';
              self.copyCode(couponCode);
            } else if(self.isVirtualType(type)) {
              const serialNumberId = record.prize_detail.serial_number_id;
              window.location.href = self.virtualUrl + 'id/' + serialNumberId;
            } else if(self.isPhysicalType(type)) {
              const smrpid = record.prize_detail.smrpid || '';
              window.location.href = self.notificationUrl + 'smrpid/' + smrpid;
            } else if(self.isRewardPointType(type)) {
              window.location.href = self.pointUrl;
            }
          }
        },

        showSuccess: function (message = '') {
          if (message == '') {
            message = $.mage.__('已複製折扣碼');
          }
          this.showMessage(message, 'success');
        },

        showChanceSuccess: function (message = '', chances) {
          if (message == '') {
            message = $.mage.__('抽獎機會：%1 次').replace('%1', chances);
          }
          this.showMessage(message, 'success');
        },

        showNotAllowToRedeem: function (message = '') {
          if (message == '') {
            message = $.mage.__('目前已無剩餘抽獎次數');
          }
          this.showMessage(message, 'error');
        },

        showMessage: function (message, type = 'error') {
          var msgContainer = $('.page.messages');
          msgContainer.append('<div class="messages custom-messages"><div class="message '+ (type === 'success' ? 'message-success success' : 'message-error error') +'">' + message + '</div></div>');
          msgContainer.addClass('__show');
          var timeCheck;
          clearTimeout(timeCheck);
          timeCheck = setTimeout(function () {
            msgContainer.removeClass('__show');
            msgContainer.find('.custom-messages').remove();
          }, 3000);
        },

        initPopup: function () {
          this.initLuckyDrawPopup();
          this.initConfirmationDrawPopup();
          this.initLotteryFailedPopup();
          this.initLotteryNoWin();
          this.initInsufficientPointsPopup();
          this.initWinningPopup();
          this.initNoWinPopup();
        },

        initLuckyDrawPopup: function () {
          var self = this;
          var options = {
            type: 'popup',
            responsive: false,
            title: $.mage.__('獲得抽獎機會'),
            modalClass: 'modal-custom modal-spintowin lucky-draw-popup',
            buttons: [{
              text: $.mage.__('去抽獎'),
              class: 'action primary action-primary',
              click: function () {
                this.closeModal();
                // let oldLuckyOption = self.luckyOption();
                // if(typeof oldLuckyOption === 'string') {
                //   oldLuckyOption = oldLuckyOption === 'true' ? true : false;
                // }
                // if(oldLuckyOption) {
                //   self.confirmDraw();
                // } else {
                //   self.triggerConfirmDrawPopup();
                // }
              }
            }]
          };

          const popup = modal(options, $('#modal-lucky-draw-des'));
        },

        initConfirmationDrawPopup: function () {
          var self = this;
          var options = {
            type: 'popup',
            responsive: false,
            title: $.mage.__('確定要抽獎嗎?'),
            modalClass: 'modal-custom modal-spintowin draw-confirmation-popup',
            buttons: []
          };

          const popup = modal(options, $('#modal-confirm-draw'));
          $(document).on('click', '#confirmDrawBtn', function () {
            let oldLuckyOption = self.luckyOption();
            const luckyOption = $("#luckyConfirmedOption").is(':checked');
            if(typeof oldLuckyOption === 'string') {
              oldLuckyOption = oldLuckyOption === 'true' ? true : false;
            }
            window.localStorage.setItem('luckyOption', luckyOption);
            var customerInfo = customerData.get('customer')();
            window.localStorage.setItem('luckyOptionCsId', customerInfo?.customer_id);
            self.luckyOption(luckyOption);
            self.confirmDraw();
          });
          $(document).on('click', '#cancelDrawBtn', function () {
            if($('#modal-confirm-draw').data('mageModal')) {
              $('#modal-confirm-draw').modal('closeModal');
            }
          });
        },

        initLotteryFailedPopup: function () {
          var self = this;
          var options = {
            type: 'popup',
            responsive: false,
            title: $.mage.__('抽獎失敗'),
            modalClass: 'modal-custom modal-spintowin modal-redeem lottery-failed-popup',
            buttons: [{
              text: $.mage.__('返回'),
              class: 'action primary action-primary',
              click: function () {
                  this.closeModal();
              }
            }]
          };

          const popup = modal(options, $('#modal-lottery-failed'));
        },

        initLotteryNoWin: function () {
          var self = this;
          var options = {
            type: 'popup',
            responsive: false,
            title: $.mage.__('未中獎'),
            modalClass: 'modal-custom modal-spintowin modal-redeem lottery-no-win-popup',
            buttons: [{
              text: $.mage.__('繼續抽獎'),
              class: 'action secondary action-secondary',
              click: function () {
                  this.closeModal();
              }
            }]
          };
          const popup = modal(options, $('#modal-lottery-no-win'));
        },

        initInsufficientPointsPopup: function () {
          var self = this;
          var options = {
            type: 'popup',
            responsive: false,
            title: $.mage.__('點數不足'),
            modalClass: 'modal-custom modal-spintowin modal-redeem insufficient-points-popup',
            buttons: [{
              text: $.mage.__('我知道了'),
              class: 'action primary action-primary',
              click: function () {
                  this.closeModal();
              }
            }]
          };
          const popup = modal(options, $('#modal-insufficient-points'));
        },

        initWinningPopup: function () {
          var self = this;
          var options = {
            type: 'popup',
            responsive: false,
            title: $.mage.__('恭喜中獎！'),
            modalClass: 'modal-custom modal-spintowin modal-lottery winning-popup',
            buttons: []
          };

          const popup = modal(options, $('#modal-winning'));
          $(document).on('click', '#winningReturn', function () {
            if($('#modal-winning').data('mageModal')) {
              $('#modal-winning').modal('closeModal');
            }
          });
          $(document).on('click', '#winningGo', function () {
            if($('#modal-winning').data('mageModal')) {
              $('#modal-winning').modal('closeModal');
            }
            const url = $(this).data('url') || '';
            if(url) {
              window.location.href = url;
            }
          });
          $(document).on('click', '#copyCode', function () {
            const couponCode = $(this).data('coupon') || '';
            if($('#modal-winning').data('mageModal')) {
              $('#modal-winning').modal('closeModal');
            }
            self.copyCode(couponCode);
          });
        },

        initNoWinPopup: function () {
          var self = this;
          var options = {
            type: 'popup',
            responsive: false,
            title: $.mage.__('未中獎'),
            modalClass: 'modal-custom modal-spintowin no-win-popup',
            buttons: [{
              text: $.mage.__('繼續抽獎'),
              class: 'action secondary action-secondary',
              click: function () {
                  this.closeModal();
              }
            }]
          };

          const popup = modal(options, $('#modal-no-win'));
        },

        triggerLuckyDrawPopup: function () {
          if($('#modal-lucky-draw-des').data('mageModal')) {
            $('#modal-lucky-draw-des').modal('openModal');
          }
        },

        triggerConfirmDrawPopup: function () {
          if($('#modal-confirm-draw').data('mageModal')) {
            $('#modal-confirm-draw').modal('openModal');
          }
        },

        triggerLotteryFailedPopup: function () {
          if($('#modal-lottery-failed').data('mageModal')) {
            $('#modal-lottery-failed').modal('openModal');
          }
        },

        triggerLotteryNoWinPopup: function () {
          if($('#modal-lottery-no-win').data('mageModal')) {
            $('#modal-lottery-no-win').modal('openModal');
          }
        },

        triggerInsufficientPointsPopup: function () {
          if($('#modal-insufficient-points').data('mageModal')) {
            $('#modal-insufficient-points').modal('openModal');
          }
        },

        triggerWinningPopup: function (hideReturn = false) {
          const sanitizeConfig = getSanitizeConfig();
          if($('#modal-winning').data('mageModal')) {
            $('#winningPopupTitle').html($.mage.__('恭喜中獎！'));
            if(hideReturn) {
              $('#winningReturn').addClass('hide');
            } else {
              $('#winningReturn').removeClass('hide');
            }
            $('#modal-winning').modal('openModal');
            const rawHtml = $('#spinTitlePopupIcon').html();
            const safeHtml = DOMPurify.sanitize(rawHtml, sanitizeConfig);
            $('.winning-title-wrapper .winning-icon').html(safeHtml);
          }
        },

        triggerWinningColabrationPopup: function () {
          const sanitizeConfig = getSanitizeConfig();
          if($('#modal-winning').data('mageModal')) {
            $('#winningPopupTitle').html($.mage.__('恭喜保底達標！'));
            $('#modal-winning').modal('openModal');
            const rawHtml = $('#spinTitlePopupIcon').html();
            const safeHtml = DOMPurify.sanitize(rawHtml, sanitizeConfig);
            $('.winning-title-wrapper .winning-icon').html(safeHtml);
          }
        },

        triggerNoWinPopup: function () {
          if($('#modal-no-win').data('mageModal')) {
            $('#modal-no-win').modal('openModal');
          }
        },

        confirmDraw: function () {
          if(!this.isAllowToRedeem()) {
            this.showNotAllowToRedeem();
            
            if($('#modal-confirm-draw').data('mageModal')) {
              $('#modal-confirm-draw').modal('closeModal');
            }

            return;
          }
          this.callAjaxDraw();
        },

        callAjaxDraw: function () {
            const self = this;
            const config = getSanitizeConfig();
            $.ajax({
                type: "POST",
                url: urlBuilder.build("spintowin/campaign/chance"),
                data: { sid: self.spinId },
                dataType: "json",
                cache: false,
                beforeSend: function () {
                    $("body").trigger("processStart");
                    $('#confirmDrawBtn').prop("disabled","disabled");
                },
                success: function (response) {
                  $("body").trigger("processStop");
                  $('#confirmDrawBtn').removeAttr("disabled");
                  // if(response?.data) {
                  //   spinData.playerData(response.data);
                  //   self.playerData(response.data);
                  // }
                  if(!response.error) {
                    if($('#modal-confirm-draw').data('mageModal')) {
                      $('#modal-confirm-draw').modal('closeModal');
                    }
                    const chances = response?.total_chances || 0;
                    self.showChanceSuccess($.mage.__('抽獎機會：%1 次').replace('%1', 1), 1);
                    self.chances(chances);
                    const totalPoints = DOMPurify.sanitize(response.total_points || 0, config);
                    $('.header .hotai-customer-points span').text(totalPoints);
                    // drawing the wheel
                    self.spin();
                  } else {
                    if($('#modal-confirm-draw').data('mageModal')) {
                      $('#modal-confirm-draw').modal('closeModal');
                    }

                    if($('#modal-insufficient-points').data('mageModal')) {
                      $('#modal-insufficient-points').modal('setTitle', $.mage.__(response?.message_title || '兌換失敗'));
                    }
                    
                    $("#insufficientPointError").text($.mage.__(response.message));
                    self.triggerInsufficientPointsPopup();

                    if(response?.need_login) {
                      console.log('User needs to login, redirecting to login page');
                      window.location.href = urlBuilder.build("customer/account/login");
                    }
                  }
                },

                error: function (response) {
                  $("body").trigger("processStop");
                  if($('#modal-confirm-draw').data('mageModal')) {
                    $('#modal-confirm-draw').modal('closeModal');
                  }
                  console.error('callAjaxDraw error:', response);
                  $('#confirmDrawBtn').removeAttr("disabled");
                  if($('#modal-insufficient-points').data('mageModal')) {
                    $('#modal-insufficient-points').modal('setTitle', $.mage.__('兌換失敗'));
                  }
                  $("#insufficientPointError").text($.mage.__(response?.message || ''));
                  self.triggerInsufficientPointsPopup();
                }
            }).always(function () {
                // console.log('callAjaxDraw finally');
                $("body").trigger("processStop");
            });
        },

        isCouponType: function (type) {
          return type === this.segmentCouponType;
        },

        isVirtualType: function (type) {
          return type === this.segmentVirtualType;
        },

        isPhysicalType: function (type) {
          return type === this.segmentPhysicalType;
        },

        isRewardPointType: function (type) {
          return type === this.segmentRewardPointType;
        },

        isLoseType: function (type) {
          return type === this.segmentLoseType;
        },

        isDailyXTimeType: function (type) {
          return type === this.spinDailyXTimeType;
        },

        isFixedXTimeType: function (type) {
          return type === this.spinFixedXTimeType;
        },

        isShowItem: function (index) {
          const indexItem = index;
          const maxCurrentIndex = this.winningRecordsLimit() * this.winningRecordsCurrentPage();
          return indexItem < maxCurrentIndex;
        },

        formatTitle: function (title) {
          if(!title) return title;
          return title.replace(/\\n/g, '');
        }
    });
});

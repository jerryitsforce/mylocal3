define([
  'jquery',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($) {
  $.widget('b8.groupApps', {
      options: {},

      _create: function () {
          var windowWidth = $(window).width();
          if(windowWidth < 1024){
              this.activeClassNavMobile();
          }
          if($("body.account").hasClass("hotai_auth-account-groupapps")){
              this.triggerWindowResize();
          }
      },

      activeClassNavMobile: function(){
          let activeLinks = $(".block-account-nav .items .link.active");

          if(activeLinks && activeLinks.length > 0){
              let linkActive = $(activeLinks[0]);
              const classes = linkActive.attr('class').split(' ');
              const activeClasses = classes.filter(className => className !== 'active');
              const linkMobile = $(".customer-header-bottom .block-account-nav .items").find("." + activeClasses.join('.'));

              if(linkActive.hasClass("services")){
                  this.showDashboardInfo(true);
                  this.triggerEventLinkUser();
              }
              else {
                  linkMobile.addClass("active");
              }
          }
      },

      showDashboardInfo: function(isShow) {
          const dashboard = $(".groupapps-description, .groupapp-container");
          const customerHeader = $(".account.hotai_auth-account-groupapps .customer-header-wrapper");
          const customerSidebar = $(".account.hotai_auth-account-groupapps .sidebar");
          const accountTitle = $(".account.hotai_auth-account-groupapps .page-title-wrapper");

          if(isShow) {
              dashboard.show();
              accountTitle.show();
              customerHeader.hide();
              customerSidebar.hide();
          }
          else {
              dashboard.hide();
              accountTitle.hide();
              customerHeader.show();
              customerSidebar.show();
          }
      },

      triggerEventLinkUser: function () {
          let self = this;
          const linkUsers = $(".sidebar .block-account-nav .items .link.services");
          const linkBackToAccount = $(".account.hotai_auth-account-groupapps .page-title-wrapper .page-title");

          if(linkUsers && linkUsers.length > 0) {
              linkUsers.unbind("click");
              linkUsers.bind("click", function (e) {
                  e.preventDefault();
                  $(this).toggleClass("active");
                  if($(this).hasClass("active")) {
                      self.showDashboardInfo(true);
                  }
                  else {
                      self.showDashboardInfo(false);
                  }
              })
          }

          if(linkBackToAccount){
              linkBackToAccount.off("click").on("click", function (e){
                  e.preventDefault();
                  linkUsers.removeClass("active");
                  self.showDashboardInfo(false);
              })
          }
      },

      triggerWindowResize: function () {
          let self = this;
          const dashboard = $(".groupapps-description, .groupapp-container");
          const accountTitle = $(".account.hotai_auth-account-groupapps .page-title-wrapper");

          $(window).resize(function () {
              const windowWidth = window.innerWidth;

              if (windowWidth >= 1024) {
                  self.showDashboardInfo(false);
                  dashboard.show();
                  accountTitle.show();
              } else if (windowWidth <= 1024) {
                  self.activeClassNavMobile();
              }
          });
      }
  });

  return $.b8.groupApps;
});

/**
 *
 */
define([
    'jquery',
    'Branch8_Checkout/js/action/reloadCartItems',
    'Magetop_Quickview/js/jquery.magnific-popup.min'
], function ($,reloadCartItems) {
    $.widget('mage.quickEditCartItemWidget', {
        options: {
            formElement: "#",
            editLinkClass: '.product-item-details dl.item-options'
        },
        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            const self = this;
            console.log('quickEditCartItemWidget _create', self.options);
            $('body')
                .off('click')
                .on('click', this.options.editLinkClass, self.clickHandle.bind(self));
            $(document).on('click', this.options.editLinkClass, function (evt) {
                console.log('click edit', evt);
                // self.clickHandle(evt);
            });   
            $(document).on('rebindQuickCartItem',function (){
                console.log('rebindQuickCartItem');
                $(self.options.editLinkClass)
                    .off('click')
                    .on('click', self.clickHandle.bind(self));
            })
        },
        /**
         * clickHandle
         */
        clickHandle: function (evt) {
            console.log('clickHandle', evt);
            const element = $(evt.currentTarget),
                url = element.data('edit-url');
            this.openPopup(url);
        },

        /**
         *
         * @param editUrl
         * @returns {boolean}
         */
        openPopup: function (editUrl) {
            console.log('openPopup', editUrl);
            if (!editUrl.length) {
                return false;
            }
            const url = this.options.baseUrl +
                'magetop_quickview/index/updatecart';

            const params = {
                items: {
                    src: editUrl
                },
                type: 'iframe',
                closeOnBgClick: false,
                scrolling: false,
                preloader: true,
                tLoading: '',
                callbacks: {
                    open: function () {
                        $('.mfp-preloader').css('display', 'block');
                        $("iframe.mfp-iframe").contents()
                            .find("html")
                            .addClass("magetop_loader");
                    },
                    beforeClose: function () {
                        $('[data-block="minicart"]').trigger('contentLoading');
                        $.ajax(
                            {
                                url: url,
                                method: "POST"
                            }
                        );
                        reloadCartItems();
                    },
                    close: function () {
                        $('.mfp-preloader').css('display', 'none');
                    }
                }
            };
            $.magnificPopup.open(params);
        }
    });
    return $.mage.quickEditCartItemWidget;
});

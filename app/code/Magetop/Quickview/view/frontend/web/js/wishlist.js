/**
 * Magetop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magetop.com license that is
 * available through the world-wide-web at this URL:
 * https://www.magetop.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magetop
 * @package     Magetop_Quickview
 * @copyright   Copyright (c) Magetop (https://www.magetop.com/)
 * @license     https://www.magetop.com/LICENSE.txt
 */
define(
    [
        "jquery",
    ],
    function ($) {
        $(".product-addto-links .towishlist").hover(
            function (e) {
               var dataPost=$(this).attr("data-post");
                var urlWishList="wishlist\\/index\\/add";
                var urlMagetopWistList="magetop_quickview\\/wishlist\\/add";
                dataPost=dataPost.replace(urlWishList,urlMagetopWistList);
                urlWishList="wishlist/index/add";
                urlMagetopWistList="magetop_quickview/wishlist/add";
                dataPost=dataPost.replace(urlWishList,urlMagetopWistList);
                $(this).attr("data-post",dataPost);
            }
        );
    }
);

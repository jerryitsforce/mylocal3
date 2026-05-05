/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

define([
        'jquery'
    ], function ($) {
    "use strict";

        var parentCategory = $(".blog-expand-tree-2");
        var childCategory  = $(".blog-expand-tree-3");

        parentCategory.click(function () {
            if ($(this).hasClass("blog-expand-tree-2")) {
                $(this).parent().find(".category-level3").slideDown("fast");
                $(this).removeClass("blog-expand-tree-2 fa fa-plus-square-o")
                .addClass("blog-narrow-tree-2 fa fa-minus-square-o");
            } else {
                $(this).parent().find(".category-level4").slideUp("fast");
                $(this).parent().find(".category-level3").slideUp("fast");
                $(this).removeClass("blog-narrow-tree-2 fa fa-minus-square-o")
                .addClass("blog-expand-tree-2 fa fa-plus-square-o");
                $(this).parent().find(".blog-narrow-tree-3")
                .removeClass("blog-narrow-tree-3 fa fa-minus-square-o")
                .addClass("blog-expand-tree-3 fa fa-plus-square-o");
            }

        });

        childCategory.click(function () {
            if ($(this).hasClass("blog-expand-tree-3")) {
                $(this).parent().find(".category-level4").slideDown("fast");
                $(this).removeClass("blog-expand-tree-3 fa fa-plus-square-o")
                .addClass("blog-narrow-tree-3 fa fa-minus-square-o");
            } else {
                $(this).parent().find(".category-level4").slideUp("fast");
                $(this).removeClass("blog-narrow-tree-3 fa fa-minus-square-o")
                .addClass("blog-expand-tree-3 fa fa-plus-square-o");
            }
        });
    }
);

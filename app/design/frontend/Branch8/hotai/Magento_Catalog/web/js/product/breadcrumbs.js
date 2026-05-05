/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Theme/js/model/breadcrumb-list'
], function ($, breadcrumbList) {
    'use strict';

    return function (widget) {

        $.widget('mage.breadcrumbs', widget, {
            options: {
                categoryUrlSuffix: '',
                useCategoryPathInUrl: false,
                product: '',
                categoryItemSelector: '.megamenu-item',
                menuContainer: '[data-action="navigation"] > ul',
                defaultCategoryCrumbs: [],
            },

            /** @inheritdoc */
            _render: function () {
                this._appendCatalogCrumbs();
                this._super();
            },

            /**
             * Append category and product crumbs.
             *
             * @private
             */
            _appendCatalogCrumbs: function () {
                var categoryCrumbs = this._resolveCategoryCrumbs();

                categoryCrumbs.forEach(function (crumbInfo) {
                    breadcrumbList.push(crumbInfo);
                });

                if (this.options.product) {
                    breadcrumbList.push(this._getProductCrumb());
                }
            },

            /**
             * Resolve categories crumbs.
             *
             * @return Array
             * @private
             */
            _resolveCategoryCrumbs: function () {
                const refererUrl = this._resolveCategoryUrl()
                const isRefererSearchResult = this._isSearchResultPage(refererUrl);
                const isRefererHome = refererUrl === window.location.origin + '/';

                //If referer is search result page or referer is home page, return default category crumbs: Main Category
                if (isRefererSearchResult || !refererUrl) {
                    return this.options.defaultCategoryCrumbs || [];
                }


                var menuItem = this._resolveCategoryMenuItem(),
                    categoryCrumbs = [];

                if (menuItem !== null && menuItem.length) {

                    categoryCrumbs.unshift(this._getCategoryCrumb(menuItem));
                    let count = 1;
                    while ((menuItem = this._getParentMenuItem(menuItem)) !== null && count < 10) {
                        console.log('__getCategoryCrumb:result', this._getCategoryCrumb(menuItem));
                        if (menuItem.hasClass('level0') && menuItem.hasClass('menu-cats')) {
                            console.log("break at", menuItem);
                            break;
                        }
                        categoryCrumbs.unshift(this._getCategoryCrumb(menuItem));
                        count++;
                    }
                }
                console.log("categoryCrumbs", categoryCrumbs);


                //For the case the referer category url not found on megamenu
                if(categoryCrumbs.length === 0 && refererUrl && !isRefererHome) {
                    //get crumbs from local storage by key referer_category_crumbs
                    const refererCategoryCrumbs = localStorage.getItem('referer_category_crumbs');
                    if(refererCategoryCrumbs) {
                        categoryCrumbs = JSON.parse(refererCategoryCrumbs);
                        console.log("referer_category_crumbs", categoryCrumbs);
                    } else {
                        return this.options.defaultCategoryCrumbs || [];
                    }
                }
                return categoryCrumbs;
            },

            /**
             * Returns crumb data.
             *
             * @param {Object} menuItem
             * @return {Object}
             * @private
             */
            _getCategoryCrumb: function (menuItem) {
                let aTag = menuItem.find('>a');

                if (menuItem.hasClass('level3')) {
                    aTag = menuItem.find('>div.menu-title-lv3>a.tab-item-name').first();
                }
                console.log('_getCategoryCrumb2', aTag.text());

                return {
                    'name': 'category',
                    'label': aTag.text(),
                    'link': aTag.attr('href'),
                    'title': ''
                };
            },

            /**
             * Returns product crumb.
             *
             * @return {Object}
             * @private
             */
            _getProductCrumb: function () {
                return {
                    'name': 'product',
                    'label': this.options.product,
                    'link': '',
                    'title': ''
                };
            },

             _findCategoryItemByUrl: function (url) {

                const referrerMegamenu = localStorage.getItem('e');

                const items = $('#mainMenu li.megamenu-item').filter(function() {
                    const nonSecureUrl = url.replace('https://', 'http://');
                    return $(this).find('> a[href="' + url + '"]').length > 0 || $(this).find('> a[href="' + nonSecureUrl + '"]').length > 0
                        || $(this).find('> div.menu-title-lv3 > a[href="' + url + '"]').length > 0 || $(this).find('> div.menu-title-lv3 > a[href="' + nonSecureUrl + '"]').length > 0

                });


                 let item = items.length ? items.first() : null;

                 if (items.length > 1) {
                     if (referrerMegamenu) {
                         const referrerItems = items.filter(function() {
                             return $(this).data('megamenu-id') == referrerMegamenu;
                         });
                         item = referrerItems.length ? referrerItems.first() : item;
                     }
                 }
                console.log('_findCategoryItemByUrl3', item);
                return item;
            },

            /**
             * Find parent menu item for current.
             *
             * @param {Object} menuItem
             * @return {Object|null}
             * @private
             */
            _getParentMenuItem: function (menuItem) {
                var parent,
                    aTags,
                    parentMenuItem = null;

                if (!menuItem) {
                    return null;
                }
                const classed = menuItem.attr('class').split(/\s+/);
                const levelClass = classed.find(function (className) {
                    return className.indexOf('level') === 0;
                });

                const level = parseInt(levelClass.replace('level', ''));
                if(level <= 0) {
                    return null;
                }

                const parentLevel = level - 1;
                parent = menuItem.parent().closest(`.level${parentLevel}.megamenu-item`);
                console.log("parent", parent)

                aTags = $(parent).find('>a');

                if ($(parent).hasClass('level3')) {
                    aTags = $(parent).find('>div.menu-title-lv3>a.tab-item-name').first();
                }

                if (parent && aTags.length) {
                    parentMenuItem = parent;
                }

                return parentMenuItem;
            },

            /**
             * Returns category menu item.
             *
             * Tries to resolve category from url or from referrer as fallback and
             * find menu item from navigation menu by category url.
             *
             * @return {Object|null}
             * @private
             */
            _resolveCategoryMenuItem: function () {
                var categoryUrl = this._resolveCategoryUrl(),
                    menu = $(this.options.menuContainer),
                    categoryMenuItem = null;

                if (categoryUrl && menu.length) {
                    categoryMenuItem = this._findCategoryItemByUrl(categoryUrl)
                }

                return categoryMenuItem;
            },

            _isSearchResultPage: function ($url) {
                return $url.indexOf('catalogsearch/result') > -1;
            },

            /**
             * Returns category url.
             *
             * @return {String}
             * @private
             */
            _resolveCategoryUrl: function () {
                var categoryUrl;


                // if (this.options.useCategoryPathInUrl) {
                //     // In case category path is used in product url - resolve category url from current url.
                //     categoryUrl = window.location.href.split('?')[0];
                //     categoryUrl = categoryUrl.substring(0, categoryUrl.lastIndexOf('/')) +
                //         this.options.categoryUrlSuffix;
                //
                // } else {
                //     // In other case - try to resolve it from referrer (without parameters).
                //     categoryUrl = document.referrer;
                //
                //     if (categoryUrl.indexOf('?') > 0) {
                //         categoryUrl = categoryUrl.substr(0, categoryUrl.indexOf('?'));
                //     }
                // }


                // In other case - try to resolve it from referrer (without parameters).
                categoryUrl = document.referrer;

                if (categoryUrl.indexOf('?') > 0) {
                    categoryUrl = categoryUrl.substr(0, categoryUrl.indexOf('?'));
                }

                console.log("Referer:" ,document.referrer);
                console.log("categoryUrl:", categoryUrl);
                return categoryUrl;
            }
        });

        return $.mage.breadcrumbs;
    };
});

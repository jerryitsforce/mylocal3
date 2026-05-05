define([
    'ko',
    'underscore',
    'mageUtils',
    'uiLayout',
    'uiElement',
    'mage/translate'
], function (ko, _, utils, layout, Element) {
    /**
     *
     */
    return Element.extend({
        firstPage: 1,
        maxPageCount: 3,
        defaults: {
            template: 'Branch8_MarketPlaceProductDiscussion/paging',
            exports: {
                currentPage: '${ $.parentName }:page'
            }
        },
        /**
         *
         * @returns {*}
         */
        initObservable: function () {
            var self = this;
            this._super().observe({
                currentPage: 1,
                totalCount: 0,
                pageSize: this.pageSize || 10
            });
            self.pageCount = ko.pureComputed(function () {
                return Math.ceil(self.totalCount() / self.pageSize());
            });
            self.lastPage = ko.pureComputed(function () {
                return self.pageCount();
            });
            self.nextPage = ko.pureComputed(function () {
                var next = self.currentPage() + 1;
                if (next > self.lastPage())
                    return null;
                return next;
            });
            self.previousPage = ko.pureComputed(function () {
                var previous = self.currentPage() - 1;
                if (previous < self.firstPage)
                    return null;
                return previous;
            });

            self.needPaging = ko.pureComputed(function () {
                return self.pageCount() > 1;
            });

            self.nextPageActive = ko.pureComputed(function () {
                return self.nextPage() != null;
            });

            self.previousPageActive = ko.pureComputed(function () {
                return self.previousPage() != null;
            });

            self.lastPageActive = ko.pureComputed(function () {
                return (self.lastPage() !== self.currentPage());
            });

            self.firstPageActive = ko.pureComputed(function () {
                return (self.firstPage !== self.currentPage());
            });
            self.getPages = ko.pureComputed(function () {
                self.currentPage();
                self.totalCount();
                if (self.pageCount() <= self.maxPageCount) {
                    return ko.observableArray(self.generateAllPages());
                } else {
                    return ko.observableArray(self.generateMaxPage());
                }
            });

            return this;
        },
        /**
         *
         * @param e
         */
        update: function (e) {
            var self = this;
            self.totalCount(e.totalCount);
            self.pageSize(e.pageSize);
            self.setCurrentPage(e.currentPage);
        },
        /**
         *
         * @param page
         */
        goToPage: function (page) {
            var self = this;
            if (page >= self.firstPage && page <= self.lastPage()) {
                self.setCurrentPage(page);

            }
        },

        goToFirst: function () {
            var self = this;
            self.setCurrentPage(self.firstPage);
        },
        /**
         *
         */
        goToPrevious: function () {
            var self = this;
            var previous = self.previousPage();
            if (previous != null)
                self.setCurrentPage(previous);
        },
        /**
         *
         */
        goToNext: function () {
            var self = this;
            var next = self.nextPage();
            if (next != null)
                self.setCurrentPage(next);
        },

        goToLast: function () {
            var self = this;
            self.setCurrentPage(self.lastPage());
        },
        /**
         *
         * @param page
         */
        setCurrentPage: function (page) {
            if (page < this.firstPage)
                page = this.firstPage;

            if (page > this.lastPage())
                page = this.lastPage();

            this.currentPage(page);
        },
        /**
         *
         * @returns {*[]}
         */
        generateMaxPage: function () {
            let self = this,
                current = self.currentPage(),
                pageCount = self.pageCount(),
                first = self.firstPage,
                maxPageCount = this.maxPageCount,
                upperLimit = current + parseInt((maxPageCount - 1) / 2),
                downLimit = current - parseInt((maxPageCount - 1) / 2);

            while (upperLimit > pageCount) {
                upperLimit--;
                if (downLimit > first)
                    downLimit--;
            }

            while (downLimit < first) {
                downLimit++;
                if (upperLimit < pageCount)
                    upperLimit++;
            }

            var pages = [];
            for (var i = downLimit; i <= upperLimit; i++) {
                pages.push(i);
            }
            return pages;
        },
        /**
         *
         * @returns {*[]}
         */
        generateAllPages: function () {
            var pages = [], self = this;
            for (var i = self.firstPage; i <= self.lastPage(); i++)
                pages.push(i);
            return pages;
        }
    })
})

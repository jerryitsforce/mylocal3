define([
    'Branch8_MarketPlaceProductDiscussion/js/model/storeData'
], function (storeData) {
    'use strict';
    return {
        /**
         *
         * @param tabCode
         * @param filters
         * @param currentPage
         * @param pageSize
         * @param sortOrders
         * @returns {{searchCriteria: {filter_groups: [], sortOrders: {length}|*|[{field: string, direction: string}], pageSize, currentPage}}}
         */
        build: function (tabCode, filters, currentPage, pageSize, sortOrders) {
            let filterGroups = [];
            pageSize = pageSize || storeData.getValueByKey(tabCode, 'pageSize')();
            currentPage = currentPage || storeData.getValueByKey(tabCode, 'currentPage')();
            if (filters.created_at_start) {
                filterGroups.push({
                    filters: [{
                        field: "created_at",
                        value: filters.created_at_start + " 00:00:00",
                        condition_type: "gteq"
                    }]
                });
            }

            if (filters.created_at_end) {
                filterGroups.push({
                    filters: [{
                        field: "created_at",
                        value: filters.created_at_end + " 23:59:59",
                        condition_type: "lteq"
                    }]
                });
            }
            if (filters.seller_replied_at_start) {
                filterGroups.push({
                    filters: [{
                        field: "seller_replied_at",
                        value: filters.seller_replied_at_start + " 00:00:00",
                        condition_type: "gteq"
                    }]
                });
            }

            if (filters.seller_replied_at_end) {
                filterGroups.push({
                    filters: [{
                        field: "seller_replied_at",
                        value: filters.seller_replied_at_end + " 23:59:59",
                        condition_type: "lteq"
                    }]
                });
            }
            if (filters.product_name) {
                filterGroups.push({
                    filters: [{
                        field: "product_name",
                        value: "%" + filters.product_name + "%",
                        condition_type: "like"
                    }]
                });
            }

            if (filters.sku) {
                filterGroups.push({
                    filters: [{
                        field: "sku",
                        value: "%" + filters.sku + "%",
                        condition_type: "like"
                    }]
                });
            }

            // default sort
            let defaultSort = [{
                field: "created_at",
                direction: "DESC"
            }];

            return {
                searchCriteria: {
                    filter_groups: filterGroups,
                    sortOrders: sortOrders && sortOrders.length ? sortOrders : defaultSort,
                    pageSize: pageSize || 5,
                    currentPage: currentPage || 1
                }
            };
        }
    };
});

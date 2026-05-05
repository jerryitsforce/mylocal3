define([
    'Webkul_Marketplace/js/grid/columns/column',
    'jquery',
    'mage/template',
    'text!Branch8_MarketplaceProduct/templates/grid/cells/approve/product.html',
    'Magento_Ui/js/modal/modal'
], function (Column, $, mageTemplate, approvePreviewTemplate) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html',
            fieldClass: {
                'data-grid-html-cell': true
            }
        },
        gethtml: function (row) {
            return row[this.index + '_html'];
        },
        getFormaction: function (row) {
            return row[this.index + '_formaction'];
        },
        getProductid: function (row) {
            return row[this.index + '_productid'];
        },
        getSellerid: function (row) {
            return row[this.index + '_sellerid'];
        },
        getGridNamespace: function (row) {
            return row[this.index + '_gridnamespace'];
        },
        getLabel: function (row) {
            return row[this.index + '_html']
        },
        getTitle: function (row) {
            return row[this.index + '_title']
        },
        getSubmitlabel: function (row) {
            return row[this.index + '_submitlabel']
        },
        getCancellabel: function (row) {
            return row[this.index + '_cancellabel']
        },
        canClick: function(row){
            return typeof row[this.index + '_html'] != "undefined" && row[this.index + '_html'] != '';
        },
        preview: function (row) {
            if(!this.canClick(row)){
                return;
            }
            var modalHtml = mageTemplate(
                approvePreviewTemplate,
                {
                    html: this.gethtml(row),
                    title: this.getTitle(row),
                    label: this.getLabel(row),
                    formaction: this.getFormaction(row),
                    productid: this.getProductid(row),
                    sellerid: this.getSellerid(row),
                    gridnamespace:this.getGridNamespace(row),
                    submitlabel: this.getSubmitlabel(row),
                    cancellabel: this.getCancellabel(row),
                    linkText: $.mage.__('Go to Details Page'),
                    notifyMsg: $.mage.__(' Notify Seller by Email')
                }
            );
            var previewPopup = $('<div></div>').html(modalHtml);
            previewPopup.modal({
                title: this.getTitle(row),
                innerScroll: true,
                modalClass: '_image-box',
                buttons: []}).trigger('openModal');
        },
        getFieldHandler: function (row) {
            return this.preview.bind(this, row);
        }
    });
});

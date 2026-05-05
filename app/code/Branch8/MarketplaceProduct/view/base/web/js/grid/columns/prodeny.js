/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    'Webkul_Marketplace/js/grid/columns/column',
    'jquery',
    'mage/template',
    'text!Branch8_MarketplaceProduct/templates/grid/cells/deny/product.html',
    'Magento_Ui/js/modal/modal'
], function (Column, $, mageTemplate, denyPreviewTemplate) {
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
        getGridNamespace: function (row) {
            return row[this.index + '_gridnamespace'];
        },
        getSellerid: function (row) {
            return row[this.index + '_sellerid'];
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
                denyPreviewTemplate,
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

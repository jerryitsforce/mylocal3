define([
    "Magento_Ui/js/grid/columns/column",
    "jquery",
    "mage/template",
    "text!Branch8_HifiSalesReport/templates/grid/cells/record/upload_file.html",
    "Magento_Ui/js/modal/modal",
], function (Column, $, mageTemplate, uploadFormTemplate) {
    "use strict";
    return Column.extend({
        defaults: {
            bodyTmpl: "ui/grid/cells/html",
            fieldClass: { "data-grid-html-cell": true },
        },
        gethtml: function (row) {
            return row[this.index + "_html"];
        },
        getLabel: function (row) {
            return row[this.index + "_html"];
        },
        getTitle: function (row) {
            return row[this.index + "_title"];
        },
        getAllowUpload: function (row) {
            return row[this.index + "_allowUpload"];
        },
        getUploadMainFileLabel: function (row) {
            return row[this.index + "_uploadMainFileLabel"];
        },
        getUploadDetailFileLabel: function (row) {
            return row[this.index + "_uploadDetailFileLabel"];
        },
        getResetLabel: function (row) {
            return row[this.index + "_resetLabel"];
        },
        getRecordId: function (row) {
            return row[this.index + "_recordId"];
        },
        getUploadMainFileFormAction: function (row) {
            return row[this.index + "_uploadMainFileFormAction"];
        },
        getUploadDetailFileFormAction: function (row) {
            return row[this.index + "_uploadDetailFileFormAction"];
        },
        getFormkey: function (row) {
            return row[this.index + "_formkey"];
        },
        preview: function (row) {
            if (!this.getAllowUpload(row)) {
                return;
            }

            var modalHtml = mageTemplate(uploadFormTemplate, {
                html: this.gethtml(row),
                title: this.getTitle(row),
                label: this.getLabel(row),
                uploadMainFileLabel: this.getUploadMainFileLabel(row),
                uploadDetailFileLabel: this.getUploadDetailFileLabel(row),
                resetLabel: this.getResetLabel(row),
                recordId: this.getRecordId(row),
                uploadMainFileFormAction: this.getUploadMainFileFormAction(row),
                uploadDetailFileFormAction: this.getUploadDetailFileFormAction(row),
                formkey: this.getFormkey(row),
            });

            var previewPopup = $("<div/>").html(modalHtml);
            previewPopup.modal({
                title: this.getTitle(row),
                innerScroll: true,
                modalClass: "_image-box",
                buttons: [],
            }).trigger("openModal");
        },
        getFieldHandler: function (row) {
            return this.preview.bind(this, row);
        },
    });
});

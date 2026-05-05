/*
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */


define(["jquery",
    "mage/mage",
    "Magento_Ui/js/modal/modal",
    "Magento_Ui/js/modal/confirm"], function ($, mage, modal, confirm) {
        'use strict';

        return {
            generate: function (url) {
                confirm({
                    title: "Generate data feed",
                    content: "Generate a data feed can take a while. Are you sure you want to generate it now ?",
                    actions: {
                        confirm: function () {
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: { url: url },
                                showLoader: false,
                                success: function () {
                                    location.reload();
                                }
                            });
                        },
                        cancel: function () {
                            $('.col-action select.admin__control-select').val("");
                        }
                    }
                });
            },
            delete: function (url) {
                confirm({
                    title: "Delete data feed",
                    content: "Are you sure you want to delete this feed ?",
                    actions: {
                        confirm: function () {
                            // Security Fix: Validate URL before redirecting
                            if (url && (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/'))) {
                                document.location.href = url;
                            } else {
                                console.error('Invalid URL for deletion');
                            }
                        },
                        cancel: function () {
                            $('.col-action select.admin__control-select').val("");
                        }
                    }
                });
            },

            importDataFeedModal: function () {
                $('#dfm-import-datafeed').modal({
                    'type': 'slide',
                    'title': 'Import a Data Feed',
                    'modalClass': 'mage-new-category-dialog form-inline',
                    buttons: [{
                        text: 'Import Data Feed',
                        'class': 'action-primary',
                        click: function () {
                            this.importDataFeed();
                        }.bind(this)
                    }]
                });
                $('#dfm-import-datafeed').modal('openModal');
            },

            importDataFeed: function () {
                $("#import-datafeed").find("#datafeed-error").remove();
                var input = $("#import-datafeed").find("input#datafeed");
                var csv_file = input.val();

                // file empty ?
                if (csv_file === "") {
                    $("<label>", {
                        "class": "mage-error",
                        "id": "datafeed-error",
                        "text": "This is a required field"
                    }).appendTo(input.parent());
                    return;
                }

                // valid file ?
                if (csv_file.indexOf(".dfm") < 0) {
                    $("<label>", {
                        "class": "mage-error",
                        "id": "datafeed-error",
                        "text": "Invalid file type"
                    }).appendTo(input.parent());
                    return;
                }

                // file not empty + valid file
                $("#import-datafeed").submit();

            }
        };
    });

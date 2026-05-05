/*jshint browser:true jquery:true expr:true*/
define([
    "jquery",
    'mage/template',
    'Magento_Ui/js/modal/alert',
    "mage/translate",
    "jquery/file-uploader"
], function ($, mageTemplate, alert) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_MarketplaceStaging/js/product/gallery-handler': function (dataInfo) {
            $('#staging-fileupload').fileupload({
                dataType: 'json',
                dropZone: '[data-tab-panel=image-management]',
                sequentialUploads: true,
                acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
                maxFileSize: dataInfo.maxFileSize,
                add: function (e, data) {
                    var progressTmpl = mageTemplate('#staging_media_gallery_content_Uploader-template'),
                        fileSize,
                        tmpl;

                    $.each(data.files, function (index, file) {
                        fileSize = typeof file.size == "undefined" ?
                            $.mage.__('We could not detect a size.') :
                            byteConvert(file.size);

                        data.fileId = Math.random().toString(33).substr(2, 18);

                        tmpl = progressTmpl({
                            data: {
                                name: file.name,
                                size: fileSize,
                                id: data.fileId
                            }
                        });

                        $(tmpl).appendTo('#staging_media_gallery_content_Uploader');
                    });

                    $(this).fileupload('process', data).done(function () {
                        data.submit();
                    });
                },
                done: function (e, data) {
                    if (data.result && !data.result.error) {
                        $('#staging_media_gallery_content').trigger('addItem', data.result);
                    } else {
                        $('#' + data.fileId)
                            .delay(2000)
                            .hide('highlight');
                        alert({
                            content: $.mage.__('We are unable to recognize or support this file extension type.')
                        });
                    }
                    $('#' + data.fileId).remove();
                },
                progress: function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    var progressSelector = '#' + data.fileId + ' .progressbar-container .progressbar';
                    $(progressSelector).css('width', progress + '%');
                },
                fail: function (e, data) {
                    var progressSelector = '#' + data.fileId;
                    $(progressSelector).removeClass('upload-progress').addClass('upload-failure')
                        .delay(2000)
                        .hide('highlight')
                        .remove();
                }
            });
            $('#staging-fileupload').fileupload('option', {
                process: [{
                    action: 'load',
                    fileTypes: /^image\/(gif|jpeg|png)$/
                }, {
                    action: 'resize',
                    maxWidth: dataInfo.maxWidth,
                    maxHeight: dataInfo.maxHeight
                }, {
                action: 'save'
            }]
        });
        }
    };
});

define([
    'jquery',
    'Magento_Ui/js/form/element/file-uploader',
    'mage/translate'
], function ($, AbstractFileUploader, $t) {
    'use strict';
    return AbstractFileUploader.extend({

        initialize: function () {
            this._super();
            this.maxFileSize = 5242880;//5MB ,
           /* this.allowedExtensions = ['jpg', 'jpeg', 'png'];*/
            console.log({
                element:this
            })
            return this;
        },

        /**
         * Adds provided file to the files list.
         *
         * @param {Object} file
         * @returns {FileUploader} Chainable.
         */
        addFile: function (file) {
            file = this.processFile(file);

            var fileCount = this.value().length;

            if (fileCount < 5) {
                this.isMultipleFiles ? this.value.push(file) : this.value([file]);
            } else {
                this.notifyError($t("You can't upload more than 5 images at a time."));
                this.uploaderConfig.stop();
                return false;
            }

            return this;
        },

        /**
         *
         * @param event
         * @param data
         */
        onBeforeFileUpload: function (event, data) {
            var file = data.files[0],
                allowed = this.isFileAllowed(file),
                target = $(event.target),
                maxImageUploadCount = this.maxImageUploadCount || 5,
                currentValue = this.value().length || 0,
                totalFiles = data.originalFiles.length || 0;
            if (totalFiles > maxImageUploadCount || (currentValue > maxImageUploadCount - 1)
                || (currentValue + totalFiles > maxImageUploadCount)
            ) {
                this.notifyError(
                    $t('You can\'t upload more than ' + maxImageUploadCount + ' images at a time.')
                );
                this.disabled()
                return false;
            } else {
                this.disabled(false);
            }

            if (this.disabled()) {
                this.notifyError($t('The file upload field is disabled.'));
                return;
            }

            if (allowed.passed) {
                target.on('fileuploadsend', function (eventBound, postData) {
                    postData.data.append('param_name', this.paramName);
                }.bind(data));

                target.fileupload('process', data).done(function () {
                    data.submit();
                });
            } else {
                this.aggregateError(file.name, allowed.message);

                // if all files in upload chain are invalid, stop callback is never called; this resolves promise
                if (this.aggregatedErrors.length === data.originalFiles.length) {
                    this.uploaderConfig.stop();
                }
            }
        },

        /**
         * Add error message associated with filename for display when upload chain is complete
         *
         * @param {String} filename
         * @param {String} message
         */
        aggregateError: function (filename, message) {
            filename = this.shortenFilename(filename);
            this.aggregatedErrors.push({
                filename: filename,
                message: message
            });
        },

        /**
         * Shortens a filename if it exceeds the specified length while preserving its extension.
         *
         * @param {String} filename
         * @param {Number} [maxLength=30]
         *
         * @returns {String}
         */
        shortenFilename: function (filename, maxLength = 30) {
            var extensionMatch = filename.match(/\.[^\.]+$/),
                extension = extensionMatch ? extensionMatch[0] : '',
                nameWithoutExt = filename.replace(extension, '');

            if (filename.length <= maxLength) {
                return filename;
            }

            var visibleLength = maxLength - extension.length - 3,
                startLength = Math.ceil(visibleLength / 2),
                endLength = Math.floor(visibleLength / 2);

            return nameWithoutExt.slice(0, startLength) + '...' + nameWithoutExt.slice(-endLength) + extension;
        },

        replaceInputTypeFile: function (fileInput) {

            let fileId = fileInput.id, fileName = fileInput.name,
                spanElement = '<span id=\'' + fileId + '\'></span>';

            $('#' + fileId).closest('.file-uploader-area').attr('upload-area-id', fileName);
            $(fileInput).replaceWith(spanElement);
            $('#' + fileId).closest('.file-uploader-area').find('.file-uploader-button:first').on('click', function () {
                $('#' + fileId).closest('.file-uploader-area').find('.uppy-Dashboard-browse').trigger('click');
            });
            $('#' + fileId).closest('.file-uploader').find('.file-uploader-placeholder').on('click', function () {
                $('#' + fileId).closest('.file-uploader-area').find('.uppy-Dashboard-browse').trigger('click');
            });
        }
    });
});

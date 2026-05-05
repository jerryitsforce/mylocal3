define([
    'jquery',
    'Magento_Ui/js/form/element/image-uploader',
    'mage/translate'
], function ($, AbstractFileUploader, $t) {
    'use strict';
    return AbstractFileUploader.extend({
        defaults: {
            imports: {
                "sellerData": "${ $.parentName }:sellerData"
            },
            exports: {
                "value": "${ $.parentName }:newMediaAddedHandler"
            }
        },
        /**
         *
         */
        initialize: function () {
            this._super();
            return this;
        },
        /**
         *
         * @param fileInput
         * @returns {*}
         */
        initUploader: function (fileInput) {
            this.fileInput = fileInput;
            const sellerData = this.sellerData;
            _.extend(this.uploaderConfig, {
                dropZone: $(fileInput).closest(this.dropZone),
                change: this.onFilesChoosed.bind(this),
                drop: this.onFilesChoosed.bind(this),
                add: this.onBeforeFileUpload.bind(this),
                fail: this.onFail.bind(this),
                done: this.onFileUploaded.bind(this),
                start: this.onLoadingStart.bind(this),
                stop: this.onLoadingStop.bind(this)
            });

            // uppy implementation
            if (fileInput !== undefined) {
                let targetElement = $(fileInput).closest('.file-uploader-area')[0],
                    dropTargetElement = $(fileInput).closest(this.dropZone)[0],
                    formKey = window.FORM_KEY,
                    fileInputName = this.fileInputName,
                    arrayFromObj = Array.from,
                    options = {
                        proudlyDisplayPoweredByUppy: false,
                        target: targetElement,
                        hideUploadButton: true,
                        hideRetryButton: true,
                        hideCancelButton: true,
                        inline: true,
                        showRemoveButtonAfterComplete: true,
                        showProgressDetails: false,
                        showSelectedFiles: false,
                        allowMultipleUploads: false,
                        hideProgressAfterFinish: true
                    };

                if (fileInputName === undefined) {
                    fileInputName = $(fileInput).attr('name');
                }
                // handle input type file
                this.replaceInputTypeFile(fileInput);

                const uppy = new Uppy.Uppy({
                    autoProceed: true,

                    onBeforeFileAdded: (currentFile) => {
                        let file = currentFile,
                            allowed = this.isFileAllowed(file);

                        if (this.disabled()) {
                            this.notifyError($t('The file upload field is disabled.'));
                            return false;
                        }

                        if (!allowed.passed) {
                            this.aggregateError(file.name, allowed.message);
                            this.uploaderConfig.stop();
                            return false;
                        }

                        // code to allow duplicate files from same folder
                        const modifiedFile = {
                            ...currentFile,
                            id: currentFile.id + '-' + Date.now()
                        };

                        this.onLoadingStart();
                        return modifiedFile;
                    },

                    meta: {
                        'conversationUniqueId': sellerData.conversationUniqueId,
                        'form_key': formKey,
                        'param_name': fileInputName,
                        isAjax: true
                    }
                });

                // initialize Uppy upload
                uppy.use(Uppy.Dashboard, options);

                // drop area for file upload
                uppy.use(Uppy.DropTarget, {
                    target: dropTargetElement,
                    onDragOver: () => {
                        // override Array.from method of legacy-build.min.js file
                        Array.from = null;
                    },
                    onDragLeave: () => {
                        Array.from = arrayFromObj;
                    }
                });

                // upload files on server
                uppy.use(Uppy.XHRUpload, {
                    endpoint: this.uploaderConfig.url,
                    fieldName: fileInputName
                });

                uppy.on('upload-success', (file, response) => {
                    let data = {
                        files: [response.body],
                        result: response.body
                    };

                    this.onFileUploaded('', data);
                });

                uppy.on('upload-error', (file, error) => {
                    console.error(error.message);
                    console.error(error.status);
                });

                uppy.on('complete', () => {
                    this.onLoadingStop();
                    Array.from = arrayFromObj;
                });
            }
            return this;
        },
        /**
         *
         */
        showNativeBrowserUpload: function () {
            const fileId = this.fileInput.id
            $('#' + fileId).closest('.file-uploader-area')
                .find('.uppy-Dashboard-browse').trigger('click');
        },
        /**
         *
         * @param event
         * @param data
         */
        onFileUploaded: function (event, data) {
            var uploadedFilename = data.files[0].name,
                file = data.result,
                error = file.error;
            if (error) {
                alert(error);
            } else {
                this.addFile(file);
            }
        }
    });
});

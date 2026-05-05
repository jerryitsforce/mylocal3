define([
    'jquery',
    'Magento_Ui/js/form/element/file-uploader',
    'uiRegistry',
    'mage/translate',
    'Magento_Ui/js/modal/alert'
], function ($, AbstractFileUploader, registry, $t, alert) {
    'use strict';
    return AbstractFileUploader.extend({
        /**
         *
         * @returns {*}
         */
        initialize: function () {
            this._super();
            const self = this;
            const defaultNoticationType = registry.get(this.parentName + '.' + 'customer_levels', function (component) {
                self.customerLevelChange(component.value());
            });
            return this;
        },

        initObservable: function () {
            return this._super()
                .observe({
                    success: '',
                    failed: '',
                    resultLink:''
                });
        },
        /**
         *
         * @param value
         */
        customerLevelChange: function (values) {
            const oneIDgroup = this.OneIdCustomerGroup,
                length = values.length,
                label = $('[upload-area-id=\'oneid_uploader\']').find('.file-uploader-button');
            console.log(values);
            if (length === 0 || !values.includes(oneIDgroup)) {
                this.disabled(true);
                this.visible(false);
                label.css('pointer-events', 'none');
                return;
            }
            this.disabled(false);
            this.visible(true);
            label.css('pointer-events', 'inherit');
            return;
        },
        /**
         *
         * @param fileInput
         * @returns {*}
         */
        initUploader: function (fileInput) {
            const self = this;
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
                const dataProvider = registry.get('magenest_notification_newaction.notification_form_data_source');
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
                        console.log({
                            modifiedFile: modifiedFile
                        })
                        return modifiedFile;
                    },
                    meta: {
                        'form_key': formKey,
                        'param_name': fileInputName,
                        isAjax: true,
                        notificationId: dataProvider.data?.id ? dataProvider.data?.id : '',
                        customer_levels: dataProvider.data.customer_levels ? dataProvider.data.customer_levels : []
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

                uppy.on('complete', (res) => {
                    this.onLoadingStop();
                    Array.from = arrayFromObj;
                    if (res.successful && res.successful[0].response?.body) {
                        const {
                            importId,
                            summary,
                            validResultLink
                        } = res.successful[0].response?.body
                        const customerLevel = registry.get(
                            this.parentName + '.' + 'customer_levels',
                            function (component) {
                                component.totalOneIds(
                                    summary.success
                                );
                                self.success($.mage.__('Success :%1').replace('%1', summary.success));
                                self.failed($.mage.__('Failed :%1').replace('%1', summary.failed));
                                self.resultLink(validResultLink);
                            });
                        const oneidImportHistory = registry.get(
                            this.parentName + '.' + 'oneid_import_history',
                            function (component) {
                                component.value(
                                    importId
                                );
                            });
                    }
                });
            }
            return this;
        },

    });
});

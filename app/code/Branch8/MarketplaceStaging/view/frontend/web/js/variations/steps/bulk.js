/*jshint browser:true jquery:true*/
/*global FORM_KEY*/
// jscs:disable jsDoc
define([
    'uiComponent',
    'jquery',
    'ko',
    'underscore',
    'Magento_Ui/js/lib/collapsible',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'jquery/file-uploader',
    'mage/translate',
    'Branch8_MarketplaceStaging/js/variations/variations'
], function (Component, $, ko, _, Collapsible, mageTemplate, alert) {
    'use strict';

    return Component.extend({
        defaults: {
            modules: {
                variationsComponent: '${ $.variationsComponent }'
            },
            countVariations: 0,
            attributes: [],
            sections: {},
            stagingimages: null,
            price: '',
            quantity: '',
            notificationMessage: {
                text: null,
                error: null
            }
        },
        initObservable: function () {
            this._super().observe('countVariations attributes sections');

            return this;
        },
        initialize: function () {
            var self = this;

            this._super();
            this.sections({
                stagingimages: {
                    label: 'images',
                    type: ko.observable('none'),
                    value: ko.observable(),
                    attribute: ko.observable()
                },
                stagingprice: {
                    label: 'price',
                    type: ko.observable('none'),
                    value: ko.observable(),
                    attribute: ko.observable(),
                    currencySymbol: ''
                },
                stagingquantity: {
                    label: 'quantity',
                    type: ko.observable('none'),
                    value: ko.observable(),
                    attribute: ko.observable()
                }
            });

            this.variationsComponent(function (variationsComponent) {
                this.sections().stagingprice.currencySymbol = variationsComponent.getCurrencySymbol()
            }.bind(this));

            this.makeOptionSections = function () {
                this.stagingimages = new self.makeImages(null);
                this.price = self.price;
                this.quantity = self.quantity;
            };
            this.makeImages = function (images, typePreview) {
                var preview;

                if (!images) {
                    this.stagingimages = [];
                    this.preview = self.noImage;
                    this.file = null;
                } else {
                    this.stagingimages = images;
                    preview = _.find(this.stagingimages, function (image) {
                        return _.contains(image.galleryTypes, typePreview);
                    });

                    if (preview) {
                        this.file = preview.file;
                        this.preview = preview.url;
                    } else {
                        this.file = null;
                        this.preview = self.noImage;
                    }
                }
            };
            this.stagingimages = new this.makeImages();
            _.each(this.sections(), function (section) {
                section.type.subscribe(function () {
                    this.setWizardNotifyMessageDependOnSectionType();
                }.bind(this));
            }, this);
        },
        types: ['each', 'single', 'none'],
        setWizardNotifyMessageDependOnSectionType: function () {
            var flag = false;

            _.each(this.sections(), function (section) {
                if (section.type() !== 'none') {
                    flag = true;
                }
            }, this);

            if (flag) {
                this.wizard.setNotificationMessage(
                    $.mage.__('Choose this option to delete and replace extension data ' +
                    'for all past configurations.')
                );
            } else {
                this.wizard.cleanNotificationMessage();
            }
        },
        render: function (wizard) {
            this.wizard = wizard;
            this.attributes(wizard.data.attributes());

            if (this.mode === 'edit') {
                this.setWizardNotifyMessageDependOnSectionType();
            }
            //fill option section data
            this.attributes.each(function (attribute) {
                attribute.chosen.each(function (option) {
                    option.sections = ko.observable(new this.makeOptionSections());
                }, this);
            }, this);
            //reset section.attribute
            _.each(this.sections(), function (section) {
                section.attribute(null);
            });

            this.initCountVariations();
            this.bindGalleries();
        },
        initCountVariations: function () {
            var variations = this.generateVariation(this.attributes()),
                newVariations = _.map(variations, function (options) {
                    return this.variationsComponent().getVariationKey(options);
                }.bind(this)),
                existingVariations = _.keys(this.variationsComponent().productAttributesMap);
            this.countVariations(_.difference(newVariations, existingVariations).length);
        },

        /**
         * @param attributes example [['b1', 'b2'],['a1', 'a2', 'a3'],['c1', 'c2', 'c3'],['d1']]
         * @returns {*} example [['b1','a1','c1','d1'],['b1','a1','c2','d1']...]
         */
        generateVariation: function (attributes) {
            return _.reduce(attributes, function (matrix, attribute) {
                var tmp = [];
                _.each(matrix, function (variations) {
                    _.each(attribute.chosen, function (option) {
                        option.attribute_code = attribute.code;
                        option.attribute_label = attribute.label;
                        tmp.push(_.union(variations, [option]));
                    });
                });

                if (!tmp.length) {
                    return _.map(attribute.chosen, function (option) {
                        option.attribute_code = attribute.code;
                        option.attribute_label = attribute.label;

                        return [option];
                    });
                }

                return tmp;
            }, []);
        },
        getSectionValue: function (section, options) {
            switch (this.sections()[section].type()) {
                case 'each':
                    return _.find(this.sections()[section].attribute().chosen, function (chosen) {
                        return _.find(options, function (option) {
                            return chosen.id == option.id;
                        });
                    }).sections()[section];

                case 'single':
                    return this.sections()[section].value();

                case 'none':
                    return this[section];
            }
        },
        getImageProperty: function (node) {
            var types = node.find('[data-role=staging-gallery]').stageProductGallery('option').types,
                images = _.map(node.find('[data-role=staging-image]'), function (image) {
                var imageData = $(image).data('imageData');
                imageData.galleryTypes = _.pluck(_.filter(types, function (type) {
                    return type.value === imageData.file;
                }), 'code');

                return imageData;
            });

            return _.reject(images, function (image) {
                return !!image.isRemoved;
            });
        },
        fillImagesSection: function () {
            switch (this.sections().stagingimages.type()) {
                case 'each':
                    if (this.sections().stagingimages.attribute()) {
                        this.sections().stagingimages.attribute().chosen.each(function (option) {
                            option.sections().stagingimages = new this.makeImages(
                                this.getImageProperty($('[data-role=staging-step-gallery-option-' + option.id + ']')),
                                'thumbnail'
                            );
                        }, this);
                    }
                    break;

                case 'single':
                    this.sections().stagingimages.value(new this.makeImages(
                        this.getImageProperty($('[data-role=staging-step-gallery-single]')),
                        'thumbnail'
                    ));
                    break;

                default:
                    this.sections().stagingimages.value(new this.makeImages());
                    break;
            }
        },
        force: function (wizard) {
            this.fillImagesSection();
            this.validate();
            this.validateImage();
            wizard.data.sections = this.sections;
            wizard.data.sectionHelper = this.getSectionValue.bind(this);
            wizard.data.variations = this.generateVariation(this.attributes());
        },
        validate: function () {
            var formValid;
            _.each(this.sections(), function (section) {
                switch (section.type()) {
                    case 'each':
                        if (!section.attribute()) {
                            throw new Error($.mage.__('Please, select attribute for the section ' + section.label));
                        }
                        break;

                    case 'single':
                        if (!section.value()) {
                            throw new Error($.mage.__('Please fill in the values for the section ' + section.label));
                        }
                        break;
                }
            }, this);
            formValid = true;
            _.each($('[data-role=staging-attributes-values-form]'), function (form) {
                formValid = $(form).valid() && formValid;
            });

            if (!formValid) {
                throw new Error($.mage.__('Please, fill correct values'));
            }
        },
        validateImage: function () {
            switch (this.sections().stagingimages.type()) {
                case 'each':
                    _.each(this.sections()['stagingimages'].attribute().chosen, function (option) {
                        if (!option.sections().stagingimages.images.length) {
                            throw new Error($.mage.__('Please, select image(s) for your attribute'));
                        }
                    });
                    break;

                case 'single':
                    if (this.sections().stagingimages.value().file == null) {
                        throw new Error($.mage.__('Please choose image(s)'));
                    }
                    break;
            }
        },
        back: function () {
            this.setWizardNotifyMessageDependOnSectionType();
        },
        bindGalleries: function () {
            $('[data-role=staging-bulk-step] [data-role=staging-gallery]').each(function (index, element) {
                var gallery = $(element),
                uploadInput = $(gallery.find('[name=image]')),
                dropZone = $(gallery).find('.image-placeholder');


                if (!gallery.data('gallery-initialized')) {
                    gallery.mage('stageProductGallery', {
                        template: '[data-template=staging-gallery-content]',
                        dialogTemplate: '.dialog-template',
                        dialogContainerTmpl: '[data-role=img-dialog-container-tmpl]'

                    });

                    uploadInput.fileupload({
                        dataType: 'json',
                        dropZone: dropZone,
                        process: [{
                            action: 'load',
                            fileTypes: /^image\/(gif|jpeg|png)$/
                        }, {
                            action: 'resize',
                            maxWidth: 1920 ,
                            maxHeight: 1200
                        }, {
                            action: 'save'
                        }],
                        formData: {form_key: FORM_KEY},
                        sequentialUploads: true,
                        acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
                        add: function (e, data) {
                            var progressTmpl = mageTemplate('[data-template=staging-uploader]'),
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

                                $(tmpl).appendTo(gallery.find('[data-role=staging-uploader]'));
                            });

                            $(this).fileupload('process', data).done(function () {
                                data.submit();
                            });
                        },
                        done: function (e, data) {
                            if (data.result && !data.result.error) {
                                gallery.trigger('addItem', data.result);
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
                            var progress = parseInt(data.loaded / data.total * 100, 10),
                                progressSelector = '#' + data.fileId + ' .progressbar-container .progressbar';

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
                    gallery.data('gallery-initialized', 1);
                }
            });
        }
    });
});

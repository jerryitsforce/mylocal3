/*jshint jquery:true*/
define([
    'jquery',
    'underscore',
    'mage/template',
    'uiRegistry',
    'tui-code-snippet',
    'tui-color-picker',
    'tui-image-editor',
    'tuiImageWhiteTheme',
    'tuiImageBlackTheme',
    'plugins/DOMPurify',
    'jquery/ui',
    'baseImage'
], function ($, _, mageTemplate, registry, codeSnippet, colorPicker, tui, tuiImageWhiteTheme, tuiImageBlackTheme, DOMPurify) {
    'use strict';
    /**
     * Formats incoming bytes value to a readable format.
     *
     * @param {Number} bytes
     * @returns {String}
     */
    function bytesToSize(bytes) {
        var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'],
            i;

        if (bytes === 0) {
            return '0 Byte';
        }

        i = window.parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));

        return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
    }
    /**
     * Product gallery widget
     */
    $.widget('mage.productGallery', {
        options: {
            imageSelector: '[data-role=image]',
            imageClick:'[data-role=imageClick]',
            imageEditorSelector:'[data-role=imageEditor]',
            imageElementSelector: '[data-role=image-element]',
            template: '[data-template=image]',
            imageResolutionLabel: '[data-role=resolution]',
            imgTitleSelector: '[data-role=img-title]',
            imageSizeLabel: '[data-role=size]',
            types: null,
            initialized: false
        },

        /**
         * Gallery creation
         * @protected
         */
        _create: function () {
            this.options.types = this.options.types || this.element.data('types');
            this.options.images = this.options.images || this.element.data('images');
            this.options.parentComponent = this.options.parentComponent || this.element.data('parent-component');

            this.imgTmpl = mageTemplate(this.element.find(this.options.template).html().trim());

            this._bind();

            $.each(this.options.images, $.proxy(function (index, imageData) {
                this.element.trigger('addItem', imageData);
            }, this));

            this.options.initialized = true;
        },

        /**
         * Bind handler to elements
         * @protected
         */
        _bind: function () {
            this._on({
                updateImageTitle: '_updateImageTitle',
                updateVisibility: '_updateVisibility',
                openDialog: '_onOpenDialog',
                addItem: '_addItem',
                removeItem: '_removeItem',
                setImageType: '_setImageType',
                setPosition: '_setPosition',
                resort: '_resort',
                openEditorDialog:'_onOpenEditorDialog',

                /**
                 * @param {jQuery.Event} event
                 */
                'mouseup [data-role=delete-button]': function (event) {
                    var $imageContainer;

                    event.preventDefault();
                    $imageContainer = $(event.currentTarget).closest(this.options.imageSelector);
                    this.element.find('[data-role=dialog]').trigger('close');
                    this.element.trigger('removeItem', $imageContainer.data('imageData'));
                },

                /**
                 * @param {jQuery.Event} event
                 */
                'mouseup [data-role=make-base-button]': function (event) {
                    var $imageContainer,
                        imageData;

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    $imageContainer = $(event.currentTarget).closest(this.options.imageSelector);
                    imageData = $imageContainer.data('imageData');
                    this.setBase(imageData);
                }
            });

            this.element.sortable({
                distance: 8,
                items: this.options.imageSelector,
                tolerance: 'pointer',
                cancel: 'input, button, .uploader',
                update: $.proxy(function () {
                    this.element.trigger('resort');
                }, this)
            });
        },

        _updateImageTitle: function (event, data) {
            var imageData = data.imageData,
                $imgContainer = this.findElement(imageData),
                $title = $imgContainer.find(this.options.imgTitleSelector),
                value;

            value = imageData['media_type'] === 'external-video' ?
                imageData['video_title'] :
                imageData.label;

            $title.text(value);

            this._contentUpdated();
        },


        /**
         * Change visibility
         *
         * @param {jQuery.Event} event
         * * @param {Object} data
         * @private
         */
        _updateVisibility: function (event, data) {
            var imageData = data.imageData,
                disabled = +data.disabled,
                $imageContainer = this.findElement(imageData);

            !!disabled ? //eslint-disable-line no-extra-boolean-cast
                $imageContainer.addClass('hidden-for-front') :
                $imageContainer.removeClass('hidden-for-front');

            $imageContainer.find('[name*="disabled"]').val(disabled);
            imageData.disabled = disabled;

            this._contentUpdated();
        },

        _onOpenEditorDialog: function (e, imageData) {
            if (imageData['media_type'] && imageData['media_type'] != 'image') { //eslint-disable-line eqeqeq
                return;
            }
            this._showEditorDialog(imageData);
        },
        /**
         * @param {Object} imageData
         * @private
         */
        _showEditorDialog: function (imageData) {
            var $imageContainer = this.findElement(imageData),
                $template;

            $template = this.editorContainerTmpl({
                'data': imageData
            });

            this.$editorDialog
                .html($template)
                .data('imageData', imageData)
                .data('imageContainer', $imageContainer)
                .modal('openModal');
        },

        /**
         * Set image as main
         * @param {Object} imageData
         * @private
         */
        setBase: function (imageData) {
            var baseImage = this.options.types.image;
            var sameImages = $.grep(
                $.map(this.options.types, function (el) {
                    return el;
                }),
                function (el) {
                    return el.value === baseImage.value;
                }
            );
            var isImageOpened = this.findElement(imageData).hasClass('active');

            $.each(sameImages, $.proxy(function (index, image) {
                this.element.trigger('setImageType', {
                    type: image.code,
                    imageData: imageData
                });

                if (isImageOpened) {
                    this.element.find('.item').addClass('selected');
                    this.element.find('[data-role=type-selector]').prop({
                        'checked': true
                    });
                }
            }, this));
        },

        /**
         * Find element by fileName
         * @param {Object} data
         * @returns {Element}
         */
        findElement: function (data) {
            return this.element.find(this.options.imageSelector).filter(function () {
                return $(this).data('imageData').file === data.file;
            }).first();
        },

        /**
         * Mark parent fieldset that content was updated
         */
        _contentUpdated: function () {
            if (this.options.initialized && this.options.parentComponent) {
                registry.async(this.options.parentComponent)(
                    function (parentComponent) {
                        parentComponent.bubble('update', true);
                    }
                );
            }
        },

        /**
         * Add image
         * @param {jQuery.Event} event
         * @param {Object} imageData
         * @private
         */
        _addItem: function (event, imageData) {
            var count = this.element.find(this.options.imageSelector).length,
                element,
                imgElement;

            imageData = $.extend({
                'file_id': imageData['value_id'] ? imageData['value_id'] : Math.random().toString(33).substr(2, 18),
                'disabled': imageData.disabled ? imageData.disabled : 0,
                'position': count + 1,
                sizeLabel: bytesToSize(imageData.size)
            }, imageData);

            element = this.imgTmpl({
                data: imageData
            });

            element = $(element).data('imageData', imageData);

            if (count === 0) {
                element.prependTo(this.element);
            } else {
                element.insertAfter(this.element.find(this.options.imageSelector + ':last'));
            }

            if (!this.options.initialized &&
                this.options.images.length === 0 ||
                this.options.initialized &&
                this.element.find(this.options.imageSelector + ':not(.removed)').length === 1
            ) {
                this.setBase(imageData);
            }

            imgElement = element.find(this.options.imageElementSelector);

            imgElement.on('load', this._updateImageDimesions.bind(this, element));

            $.each(this.options.types, $.proxy(function (index, image) {
                if (imageData.file === image.value) {
                    this.element.trigger('setImageType', {
                        type: image.code,
                        imageData: imageData
                    });
                }
            }, this));

            this._updateImagesRoles();
            this._contentUpdated();
        },

        /**
         * Returns a list of current images.
         *
         * @returns {jQueryCollection}
         */
        _getImages: function () {
            return this.element.find(this.options.imageSelector);
        },

        /**
         * Returns a list of images roles.
         *
         * @return {Object}
         */
        _getRoles: function () {
            return _.mapObject(this.options.types, function (data, key) {
                var elem = this.element.find('.image-' + key);

                return {
                    index: key,
                    value: elem.val(),
                    elem: elem
                };
            }, this);
        },

        /**
         * Updates labels with roles information for each image.
         */
        _updateImagesRoles: function () {
            var $images = this._getImages().toArray(),
                roles = this._getRoles();
            $images.forEach(function (img) {
                var $img = $(img),
                    data = $img.data('imageData');

                $img.find('[data-role=roles-labels] li').each(function (index, elem) {
                    var $elem = $(elem),
                        roleCode = $elem.data('roleCode'),
                        role = roles[roleCode];

                    role.value === data.file  ?
                        $elem.show() :
                        $elem.hide();
                });

            });
        },

        /**
         * Updates image's dimensions information.
         *
         * @param {jQeuryCollection} imgContainer
         */
        _updateImageDimesions: function (imgContainer) {
            var $img = imgContainer.find(this.options.imageElementSelector)[0],
                $dimens = imgContainer.find('[data-role=image-dimens]');

            $dimens.text($img.naturalWidth + 'x' + $img.naturalHeight + ' px');
        },

        /**
         * Remove Image
         * @param {jQuery.Event} event
         * @param {Object} imageData
         * @private
         */
        _removeItem: function (event, imageData) {
            var $imageContainer = this.findElement(imageData),self=this;
            imageData.isRemoved = true;
            $imageContainer.addClass('removed').hide().find('.is-removed').val(1);
            const fileId = imageData.file_id;
            $.map(this.options.types, function (type) {
                const field = self.element.find(`input[name="product\\[${type.code}\\]"]`);
                if (field && field.length && field.data('fileId') === fileId) {
                    field.val('no_selection')
                }
            });
            this._contentUpdated();
        },

        /**
         * Set image type
         * @param event
         * @param data
         * @private
         */
        _setImageType: function (event, data) {
            if (data.type === 'image') {
                this.element.find('.base-image').removeClass('base-image');
            }
            if (data.imageData) {
                this.options.types[data.type].value = data.imageData.file;

                if (data.type === 'image') {
                    this.findElement(data.imageData).addClass('base-image');
                }
            } else {
                this.options.types[data.type].value = 'no_selection';
            }
            this.element.find('.image-' + data.type)
                .val(this.options.types[data.type].value || 'no_selection')
            if (data.imageData) {
                this.element.find('.image-' + data.type).data('fileId', data.imageData.file_id);
                this.element.find('.image-' + data.type).attr('data-file-id', data.imageData.file_id);
            }
        },

        /**
         * Resort images
         * @private
         */
        _resort: function () {
            this.element.find('.position').each($.proxy(function (index, element) {
                var value = $(element).val();

                if (value != index) {
                    this.element.trigger('moveElement', {
                        imageData: $(element).closest(this.options.imageSelector).data('imageData'),
                        position: index
                    });
                    $(element).val(index);
                }
            }, this));
        },

        /**
         * Set image position
         * @param event
         * @param data
         * @private
         */
        _setPosition: function (event, data) {
            var $element = this.findElement(data.imageData);
            var curIndex = this.element.find(this.options.imageSelector).index($element);
            var newPosition = data.position + (curIndex > data.position ? -1 : 0);

            if (data.position != curIndex) {
                if (data.position === 0) {
                    this.element.prepend($element);
                } else {
                    $element.insertAfter(
                        this.element.find(this.options.imageSelector).eq(newPosition)
                    );
                }
                this.element.trigger('resort');
            }
        }
    });

    // Extension for mage.productGallery - Add advanced settings block
    $.widget('mage.productGallery', $.mage.productGallery, {
        options: {
            dialogTemplate: '.dialog-template',
            dialogContainerTmpl: '[data-role=img-dialog-container-tmpl]',
            editorContainerTmpl: '[data-role=img-editor-container-tmpl]'
        },

        /** @inheritdoc */
        _create: function () {
            var template = this.element.find(this.options.dialogTemplate),
                containerTmpl = this.element.find(this.options.dialogContainerTmpl),
                editorContTmpl = this.element.find(this.options.editorContainerTmpl);

            this._super();
            this.modalPopupInit = false;

            if (template.length) {
                this.dialogTmpl = mageTemplate(template.html().trim());
            }

            if (containerTmpl.length) {
                this.dialogContainerTmpl = mageTemplate(containerTmpl.html().trim());
            } else {
                this.dialogContainerTmpl = mageTemplate('');
            }

            if (editorContTmpl.length) {
                this.editorContainerTmpl = mageTemplate(editorContTmpl.html().trim());
            } else {
                this.editorContainerTmpl = mageTemplate('');
            }

            this._initEditorDialog();
            this._initDialog();

        },

        /**
         * Bind handler to elements
         * @protected
         */
        _bind: function () {
            this._super();
            var events = {};
            const self=this;
            events['click [data-role=close-panel]'] = $.proxy(function () {
                this.element.find('[data-role=dialog]').trigger('close');
            }, this);
            events['mouseup ' + this.options.imageEditorSelector] = function (event) {
                var imageData, $imageContainer;
                currentImageData = $(event.currentTarget).closest(this.options.imageSelector);
                if (!$(event.currentTarget).is('.ui-sortable-helper')) {
                    $(event.currentTarget.parentElement).addClass('active');
                    $(currentImageData).addClass('active');
                    imageData = $(event.currentTarget).closest(this.options.imageSelector).data('imageData');
                    $imageContainer = this.findElement(imageData);
                    if ($imageContainer.is('.removed')) {
                        return;
                    }
                    event.stopPropagation();
                    // this.openEditorModel(imageData);
                    this.element.trigger('openEditorDialog', [imageData]);
                }
            };
            events['mouseup ' + this.options.imageSelector] = function (event) {
                if (!$(event.currentTarget).is('.ui-sortable-helper')) {
                    $(event.currentTarget).addClass('active');
                    var itemId = $(event.currentTarget).find('input')[0].name.match(/\[([^\]]*)\]/g)[2];
                    $('#item_id').val(itemId);
                    var imageData = $(event.currentTarget).data('imageData');
                    var $imageContainer = this.findElement(imageData);
                    if ($imageContainer.is('.removed')) {
                        return;
                    }
                    this.element.trigger('openDialog', [imageData]);
                }
            };
            this._on(events);
            this.element.on('sortstart', $.proxy(function () {
                this.element.find('[data-role=dialog]').trigger('close');
            }, this));
            setTimeout(function () {
                $(self.options.imageSelector).each(function (i, e) {
                    const imageData = $(e).data('imageData');
                    if (imageData['media_type'] && imageData['media_type'] != 'image') { //eslint-disable-line eqeqeq
                        return;
                    }
                    if (imageData && imageData.removed !== true
                    ) {
                        self._setImageRoles(e, imageData);
                    }
                })
            }, 500)
        },

        /**
         *
         * Click by image handler
         *
         * @param e
         * @param imageData
         * @private
         */
        _onOpenDialog: function (e, imageData) {
            if (imageData.media_type && imageData.media_type != 'image') {
                return;
            }
            this._showDialog(imageData);
        },

        _onOpenEditorDialog: function (e, imageData) {
            if (imageData['media_type'] && imageData['media_type'] != 'image') { //eslint-disable-line eqeqeq
                return;
            }
            this._showEditorDialog(imageData);
        },
        /**
         * @param {Object} imageData
         * @private
         */
        _showEditorDialog: function (imageData) {
            var $imageContainer = this.findElement(imageData),
                $template;

            $template = this.editorContainerTmpl({
                'data': imageData
            });

            this.$editorDialog
                .html($template)
                .data('imageData', imageData)
                .data('imageContainer', $imageContainer)
                .modal('openModal');
        },
        /**
         * Initializes dialog element.
         */
        _initEditorDialog: function () {
            var $editorDialog = $(this.editorContainerTmpl());
            $editorDialog.modal({
                'type': (themeConfigAttr.p_style == 'slide') ? 'slide' : 'popup',
                title: $.mage.__('Image Editor'),
                buttons: [],

                /** @inheritdoc */
                opened: function () {
                    $editorDialog.trigger('open');
                },

                /** @inheritdoc */
                closed: function () {
                    $editorDialog.trigger('close');
                }
            });
            $editorDialog.on('open', this.onEditorDialogOpen.bind(this));
            $editorDialog.on('close', function () {
                $(currentImageData).removeClass('active');
            });
            this.$editorDialog = $editorDialog;
        },

        onEditorDialogOpen: function (event) {

            /* console.log(event);return false*/

            /*$.each(this.options.images, $.proxy(function (index, imageData) {
                this.element.trigger('addItem', imageData);
            }, this));*/


            var imageData = this.$editorDialog.data('imageData');
            currentPanelData = this;
            var imageUrl = imageData.url;
            imageActuallData = imageData;
            var imageName;
            if(imageData.hasOwnProperty('name')){
                imageName = imageData.name;
                if(imageName.lastIndexOf(".") != -1)
                    imageName = imageName.substring(0, imageName.lastIndexOf("."));
            }
            else{
                var uploadedImageURL = imageData.file;
                var filename = uploadedImageURL.substring(uploadedImageURL.lastIndexOf('/')+1);
                if(filename.lastIndexOf(".") != -1)
                    filename = filename.substring(0, filename.lastIndexOf("."));

                imageName = filename;
            }
            var configObject = {
                loadImage: {
                    path: imageUrl,
                    name: imageName
                },
                theme: whiteTheme,
                initMenu: 'filter',
                menuBarPosition: 'right'
            };


            if(themeConfigAttr['theme_style'] =='blackTheme' &&  themeConfigAttr['menu_style'] == 'top'){
                configObject = {
                    loadImage: {
                        path: imageUrl,
                        name: imageName
                    },
                    theme: blackTheme,
                    initMenu: 'filter',
                    menuBarPosition: 'top'
                };
            }
            else if(themeConfigAttr['theme_style'] =='blackTheme' &&  themeConfigAttr['menu_style'] == 'bottom'){
                configObject = {
                    loadImage: {
                        path: imageUrl,
                        name: imageName
                    },
                    theme: blackTheme,
                    initMenu: 'filter',
                    menuBarPosition: 'bottom'
                };
            }
            else if(themeConfigAttr['theme_style'] =='whiteTheme' &&  themeConfigAttr['menu_style'] == 'left'){
                configObject = {
                    loadImage: {
                        path: imageUrl,
                        name: imageName
                    },
                    theme: whiteTheme,
                    initMenu: 'filter',
                    menuBarPosition: 'left'
                };
            }
            else if(themeConfigAttr['theme_style'] =='whiteTheme' &&  themeConfigAttr['menu_style'] == 'right'){
                configObject = {
                    loadImage: {
                        path: imageUrl,
                        name: imageName
                    },
                    theme: whiteTheme,
                    initMenu: 'filter',
                    menuBarPosition: 'right'
                };
            }


            var editor = new tui(document.querySelector('#tui-image-editor-container'), {
                includeUI: configObject,
                cssMaxWidth: 700,
                cssMaxHeight: 500,
                selectionStyle: {
                    cornerSize: 20,
                    rotatingPointOffset: 70
                },
                usageStatistics: false
            });

            this._contentUpdated();
        },
        /**
         * Initializes dialog element.
         */
        _initDialog: function () {
            var $dialog = $(this.dialogContainerTmpl());

            $dialog.modal({
                'type': 'slide',
                title: $.mage.__('Image Detail'),
                buttons: [],

                /** @inheritdoc */
                opened: function () {
                    $dialog.trigger('open');
                },

                /** @inheritdoc */
                closed: function () {
                    $dialog.trigger('close');
                }
            });

            $dialog.on('open', this.onDialogOpen.bind(this));
            $dialog.on('close', function () {
                var $imageContainer = $dialog.data('imageContainer');

                $imageContainer.removeClass('active');
                $dialog.find('#hide-from-product-page').remove();
            });

            $dialog.on('change', '[data-role=type-selector]', function () {
                var parent = $(this).closest('.item'),
                    selectedClass = 'selected';

                parent.toggleClass(selectedClass, $(this).prop('checked'));
            });

            $dialog.on('change', '[data-role=type-selector]', $.proxy(this._notifyType, this));

            $dialog.on('change', '[data-role=visibility-trigger]', $.proxy(function (e) {
                var imageData = $dialog.data('imageData');

                this.element.trigger('updateVisibility', {
                    disabled: $(e.currentTarget).is(':checked'),
                    imageData: imageData
                });
            }, this));

            $dialog.on('change', '[data-role="image-description"]', function (e) {
                var target = $(e.target),
                    targetName = target.attr('name'),
                    desc = target.val(),
                    imageData = $dialog.data('imageData');

                this.element.find('input[type="hidden"][name="' + targetName + '"]').val(desc);

                imageData.label = desc;
                imageData['label_default'] = desc;

                this.element.trigger('updateImageTitle', {
                    imageData: imageData
                });
            }.bind(this));

            this.$dialog = $dialog;
        },

        /**
         * Handles dialog open event.
         *
         * @param {EventObject} event
         */
        onDialogOpen: function (event) {
            var imageData = this.$dialog.data('imageData'),
                imageSizeKb = imageData.sizeLabel,
                image = document.createElement('img'),
                sizeSpan = this.$dialog.find(this.options.imageSizeLabel)
                    .find('[data-message]'),
                resolutionSpan = this.$dialog.find(this.options.imageResolutionLabel)
                    .find('[data-message]'),
                sizeText = sizeSpan.attr('data-message').replace('{size}', imageSizeKb),
                resolutionText;

            image.src = imageData.url;

            resolutionText = resolutionSpan
                .attr('data-message')
                .replace('{width}^{height}', image.width + 'x' + image.height);

            sizeSpan.text(sizeText);
            resolutionSpan.text(resolutionText);

            $(event.target)
                .find('[data-role=type-selector]')
                .each($.proxy(function (index, checkbox) {
                    var $checkbox = $(checkbox),
                        parent = $checkbox.closest('.item'),
                        selectedClass = 'selected',
                        isChecked = this.options.types[$checkbox.val()].value == imageData.file; //eslint-disable-line
                    $checkbox.prop(
                        'checked',
                        isChecked
                    );
                    parent.toggleClass(selectedClass, isChecked);
                }, this));
        },

        /**
         * Show dialog
         * @param imageData
         * @private
         */
        _showDialog: function (imageData) {
            const self = this;
            var $imageContainer = this.findElement(imageData);
            var dialogElement = $imageContainer.data('dialog');
            if (!this.dialogTmpl) {
                alert('System problem!');
                return;
            }

            var $template = this.dialogTmpl({ data: imageData });
            dialogElement = $($template);
            dialogElement.modal({
                'type': 'slide',
                title: $.mage.__('Image Detail'),
                buttons: [],
                opened: function () {
                    dialogElement.trigger('open');
                },
                closed: function (e) {
                    dialogElement.trigger('close');
                }
            });
            dialogElement
                .data('imageContainer', $imageContainer)
                .on('open', $.proxy(function (event) {
                    self._setImageRoles(event.target,imageData);
                }, this))
                .on('close', $.proxy(function (event) {
                    $imageContainer.removeClass('active');
                    $imageContainer.data('dialog', null);
                }, this));

            $imageContainer.data('dialog', dialogElement);


            var _changeDescription = function (e) {
                var jQ = $.noConflict();
                var target = jQ(e.target);
                var targetName = DOMPurify.sanitize(target.attr('name'));
                var desc = DOMPurify.sanitize(target.val());
                jQ('input[type="hidden"][name="'+ targetName + '"]').val(desc);
                imageData.label = desc;
                imageData.label_default = desc;
            };

            dialogElement.on('change', '[data-role=type-selector]', function () {
                var parent = $(this).closest('.item'),
                    selectedClass = 'selected';
                parent.toggleClass(selectedClass, $(this).prop('checked'));
            });
            dialogElement.on('change', '[data-role=type-selector]', $.proxy(this._notifyType, this));
            dialogElement.on('change', '[data-role=visibility-trigger]', $.proxy(function (e) {
                this._changeVisibility(e, imageData);
            }, this));
            dialogElement.on('change', '#image-description', _changeDescription);
            dialogElement.modal('openModal');
        },

        /**
         *
         * @param element
         * @private
         */
        _setImageRoles: function (element, imageData) {

            $(element)
                .find('[data-role=type-selector]')
                .each($.proxy(function (index, checkbox) {
                    var $checkbox = $(checkbox),
                        parent = $checkbox.closest('.item'),
                        selectedClass = 'selected',
                        isChecked = this.options.types[$checkbox.val()].value == imageData.file;

                    $checkbox.prop(
                        'checked',
                        isChecked
                    );
                    parent.toggleClass(selectedClass, isChecked);
                }, this));
        },
        /**
         * Change visibility
         *
         * @param event
         * @private
         */
        _changeVisibility: function (event, imageData) {
            var $checkbox = $(event.currentTarget);
            var $imageContainer = $checkbox.closest('[data-role=dialog]').data('imageContainer');
            $imageContainer.toggleClass('hidden-for-front', $checkbox.is(':checked'));
            var checked = $checkbox.is(':checked') ? 1 : 0;
            $imageContainer.find('[name*="disabled"]').val(checked);
            imageData.disabled = checked;
        },

        /**
         * Set image
         * @param event
         * @private
         */
        _notifyType: function (event) {
            var $checkbox = $(event.currentTarget);
            var $imageContainer = $checkbox.closest('[data-role=dialog]').data('imageContainer');
            this.element.trigger('setImageType', {
                type: $checkbox.val(),
                imageData: $checkbox.is(':checked') ? $imageContainer.data('imageData') : null
            });
        }
    });

    return $.mage.productGallery;
});

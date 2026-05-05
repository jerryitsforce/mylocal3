/**
 * Handle image deletion in UI Component form
 * When preview-image is removed, automatically check the image_delete checkbox
 * Compatible with Amasty's image_delete checkbox mechanism
 */
(function () {
    'use strict';

    /**
     * Setup handlers for image deletion
     */
    function setupImageDeleteHandlers() {
        var registry = require('uiRegistry');
        
        // Handle main image deletion
        var imageComponent = registry.get('brand_form.brand_form.image');
        var imageDeleteCheckbox = registry.get('brand_form.brand_form.image_delete');
        
        if (imageComponent && imageDeleteCheckbox) {
            // Listen to value changes
            imageComponent.on('value', function (value) {
                // If image array is empty or null, check delete checkbox
                if (!value || (Array.isArray(value) && value.length === 0)) {
                    imageDeleteCheckbox.set('value', true);
                } else if (Array.isArray(value) && value.length > 0) {
                    // Check if first element has isRemoved flag or empty file
                    var firstElement = value[0];
                    if (firstElement && (firstElement.isRemoved || !firstElement.file || firstElement.file === '')) {
                        imageDeleteCheckbox.set('value', true);
                    } else {
                        imageDeleteCheckbox.set('value', false);
                    }
                }
            });
        }
        
        // Handle slider image deletion
        var sliderImageComponent = registry.get('brand_form.brand_form.slider_image');
        var sliderImageDeleteCheckbox = registry.get('brand_form.brand_form.slider_image_delete');
        
        if (sliderImageComponent && sliderImageDeleteCheckbox) {
            // Listen to value changes
            sliderImageComponent.on('value', function (value) {
                // If image array is empty or null, check delete checkbox
                if (!value || (Array.isArray(value) && value.length === 0)) {
                    sliderImageDeleteCheckbox.set('value', true);
                } else if (Array.isArray(value) && value.length > 0) {
                    // Check if first element has isRemoved flag or empty file
                    var firstElement = value[0];
                    if (firstElement && (firstElement.isRemoved || !firstElement.file || firstElement.file === '')) {
                        sliderImageDeleteCheckbox.set('value', true);
                    } else {
                        sliderImageDeleteCheckbox.set('value', false);
                    }
                }
            });
        }
        
        // Also listen to DOM events for preview-image removal
        jQuery(document).on('click', '.preview-image .action-remove, .file-uploader-preview .action-remove', function () {
            var $preview = jQuery(this).closest('.preview-image, .file-uploader-preview');
            var $uploader = $preview.closest('[data-role="image-uploader"]');
            
            // Check if this is the main image or slider image
            setTimeout(function () {
                if (imageComponent) {
                    var currentValue = imageComponent.value();
                    if (!currentValue || (Array.isArray(currentValue) && currentValue.length === 0)) {
                        if (imageDeleteCheckbox) {
                            imageDeleteCheckbox.set('value', true);
                        }
                    }
                }
                
                if (sliderImageComponent) {
                    var currentSliderValue = sliderImageComponent.value();
                    if (!currentSliderValue || (Array.isArray(currentSliderValue) && currentSliderValue.length === 0)) {
                        if (sliderImageDeleteCheckbox) {
                            sliderImageDeleteCheckbox.set('value', true);
                        }
                    }
                }
            }, 100);
        });
    }

    /**
     * Auto-check Use Default checkboxes when field values
     * equal Brand Name.
     */
    function setupUseDefaultAutoCheck() {
        var registry = require('uiRegistry');

        function syncUseDefaultCheckboxVisual(fieldKey, shouldCheck) {
            var selectors = [
                'input[name="use_default[' + fieldKey + ']"]',
                'input[name="use_default.' + fieldKey + '"]'
            ];
            function apply() {
                selectors.forEach(function (selector) {
                    jQuery(selector).each(function () {
                        var vm = null;
                        if (window.ko && typeof window.ko.dataFor === 'function') {
                            vm = window.ko.dataFor(this);
                            if (vm && typeof vm.isUseDefault === 'function' && vm.isUseDefault() !== shouldCheck) {
                                vm.isUseDefault(shouldCheck);
                            }
                        }

                        jQuery(this)
                            .prop('checked', shouldCheck)
                            .attr('checked', shouldCheck ? 'checked' : null);
                    });
                });
            }

            // Immediate sync.
            apply();

            // Re-apply after KO/UI re-render.
            setTimeout(apply, 50);
            setTimeout(apply, 200);
            setTimeout(apply, 1000);
        }

        function applyUseDefaultState(fieldKey, fieldComponent, checkboxComponent, shouldCheck) {
            var dataSource = registry.get('brand_form.brand_form_data_source');
            var nextValue = shouldCheck ? 1 : 0;

            // Source of truth for persistence.
            if (dataSource && dataSource.set) {
                dataSource.set('data.use_default.' + fieldKey, nextValue);
                if (fieldKey === 'meta_title') {
                    dataSource.set('data.meta_data.use_default.meta_title', nextValue);
                } else if (fieldKey === 'title') {
                    dataSource.set('data.page_content.use_default.title', nextValue);
                }
            }

            // Source of truth for checkbox UI component.
            if (checkboxComponent && checkboxComponent.value) {
                if (Number(checkboxComponent.value()) !== nextValue) {
                    checkboxComponent.set('value', nextValue);
                }
            }

            // Keep visual/interaction behavior same as manual check.
            if (fieldComponent) {
                if (fieldComponent.disabled && typeof fieldComponent.disabled === 'function') {
                    fieldComponent.disabled(shouldCheck);
                } else if (fieldComponent.set) {
                    fieldComponent.set('disabled', shouldCheck);
                }
            }

            // Keep checkbox visual state in sync with disabled state.
            syncUseDefaultCheckboxVisual(fieldKey, shouldCheck);
        }

        registry.async('brand_form.brand_form.meta_data_fieldset.meta_title')(function (metaTitleField) {
            registry.async('brand_form.brand_form.base.title')(function (titleField) {
                registry.async('brand_form.brand_form.base.value')(function (brandNameField) {
                    function syncUseDefaultState() {
                        var metaTitleUseDefault = registry.get('brand_form.brand_form.meta_data_fieldset.meta_title_use_default');
                        var titleUseDefault = registry.get('brand_form.brand_form.base.title_use_default');
                        var dataSource = registry.get('brand_form.brand_form_data_source');
                        var metaTitle = ((metaTitleField.value && metaTitleField.value()) || '').trim();
                        var title = ((titleField.value && titleField.value()) || '').trim();
                        var brandName = ((brandNameField.value && brandNameField.value()) || '').trim();
                        var storedMetaUseDefault = dataSource && dataSource.get
                            ? Number(dataSource.get('data.use_default.meta_title')) === 1
                            : false;
                        var storedTitleUseDefault = dataSource && dataSource.get
                            ? Number(dataSource.get('data.use_default.title')) === 1
                            : false;
                        var shouldCheckMetaTitle = storedMetaUseDefault || (metaTitle === brandName);
                        var shouldCheckTitle = storedTitleUseDefault || (title === brandName);

                        applyUseDefaultState('meta_title', metaTitleField, metaTitleUseDefault, shouldCheckMetaTitle);
                        applyUseDefaultState('title', titleField, titleUseDefault, shouldCheckTitle);
                    }

                    // Initial sync when components are ready.
                    syncUseDefaultState();
                    setTimeout(syncUseDefaultState, 300);
                    setTimeout(syncUseDefaultState, 1000);
                    setTimeout(syncUseDefaultState, 2000);

                    // Keep state synced when user edits fields.
                    if (!metaTitleField.__useDefaultSyncBound) {
                        metaTitleField.on('value', syncUseDefaultState);
                        metaTitleField.__useDefaultSyncBound = true;
                    }
                    if (!titleField.__useDefaultSyncBound) {
                        titleField.on('value', syncUseDefaultState);
                        titleField.__useDefaultSyncBound = true;
                    }
                    if (!brandNameField.__useDefaultSyncBound) {
                        brandNameField.on('value', syncUseDefaultState);
                        brandNameField.__useDefaultSyncBound = true;
                    }
                });
            });
        });
    }

    // Initialize when DOM is ready
    require(['jquery', 'uiRegistry', 'domReady!'], function ($, registry) {
        // Wait for UI components to be initialized
        setTimeout(function () {
            setupImageDeleteHandlers();
        }, 2000);

        // Also try to initialize when form is loaded
        registry.async('brand_form.brand_form_data_source')(function () {
            setTimeout(function () {
                setupImageDeleteHandlers();
            }, 1000);
        });
    });
})();

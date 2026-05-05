define([
    'underscore',
    'Magento_Ui/js/lib/validation/validator'
], function (_, validator) {
    /**
     *
     * @param file
     * @returns {*}
     */
    function isExtensionAllowed(file, allowedExtensions) {
        return validator('validate-file-type', file.name, allowedExtensions);
    };

    /**
     *
     * @param file
     */
    function isSizeExceeded(file, maxSize) {
        return validator('validate-max-size', file.size, maxSize);
    }

    /**
     *
     */
    return {
        isExtensionAllowed: isExtensionAllowed,
        isSizeExceeded: isSizeExceeded
    }
})

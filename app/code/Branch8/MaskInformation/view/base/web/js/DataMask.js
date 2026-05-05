define([], function () {
    'use strict';

    return function maskStringFromPosition(inputString, startPosition, length, maskCharacter = '*') {
        // Check if startPosition is valid
        if (startPosition < 0 || startPosition >= inputString.length) {
            return inputString; // Return original string if startPosition is invalid
        }

        // Create a mask of the specified length
        let mask = '';
        for (let i = 0; i < length; i++) {
            mask += maskCharacter;
        }

        // Replace characters from startPosition to startPosition + length with the mask
        return inputString.substring(0, startPosition) + mask + inputString.substring(startPosition + length);
    }

})

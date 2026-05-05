define([
    './DataMask'
], function (DataMask) {
    'use strict';

    /**
     *
     * @param inputText
     * @returns {*}
     */
    function cleanInput(inputText) {
        // Remove duplicate spaces
        inputText = inputText.replace(/\s+/g, ' ');
        // Remove duplicate commas
        inputText = inputText.replace(/,{2,}/g, ',');
        // Remove space before and after commas
        inputText = inputText.replace(/\s*,\s*/g, ',');
        return inputText.trim(); // Trim any leading or trailing sp
    }

    /**
     *  Get first occur of space or comma
     * @param inputString
     * @returns {*|number}
     */
    function getFirstOccurPosition(inputString) {
        // Find the position of the first space or comma
        let spaceIndex = inputString.indexOf(' ');
        let commaIndex = inputString.indexOf(',');
        let slashIndex = inputString.indexOf('/');
        let mailIndex = inputString.indexOf('@');

        // Create an array of indices
        let indices = [spaceIndex, commaIndex, slashIndex, mailIndex];

        // Filter out -1 (indicating not found)
        let validIndices = indices.filter(index => index !== -1);

        // If no valid indices found, return -1
        if (validIndices.length === 0) {
            return -1;
        }

        // Return the minimum index
        return Math.min(...validIndices);
    }

    /**
     * Get second occur of space or comma
     * @param inputString
     * @returns {number}
     */

    function getSecondOccurPosition(inputString) {
        // Find the position of the first space or comma
        let spaceIndex = inputString.indexOf(' ');
        let commaIndex = inputString.indexOf(',');
        let slashIndex = inputString.indexOf('/');
        let mailIndex = inputString.indexOf('@');
        // Create an array of indices
        let indices = [spaceIndex, commaIndex, slashIndex, mailIndex];

        // Filter out -1 (indicating not found)
        let validIndices = indices.filter(index => index !== -1);

        // If less than two valid indices found, return -1
        if (validIndices.length < 2) {
            return -1;
        }

        // Sort the valid indices array
        validIndices.sort((a, b) => a - b);

        // Return the second minimum index
        return validIndices[1];
    }

    return function mask(code, str) {
        let input = cleanInput(str);
        const defaultLength = 5,
            firstOccur = getFirstOccurPosition(input),
            secondOccur = getSecondOccurPosition(input),
            start = firstOccur !== -1 ? firstOccur : 0,
            length = secondOccur !== -1 ? secondOccur - firstOccur : defaultLength;

        switch (code) {
            case "firstname":
            case "lastname":
                return DataMask(input, 0, 2, '0');
            case "phone":
                return DataMask(DataMask(input, 0, 1, '*'), 4, 4, '*');
            case "email":
                return DataMask(input, start > 1 ? 1 : 0, start > 1 ? start - 1 : start, '*');
            case "address":
                return DataMask(input, start, length);
            default:
                return input;
        }
    }

})

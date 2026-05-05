define([], function () {
    /**
     * Split Taiwan Address into parts
     * Zipcode
     * Region
     * City
     * Address
     */
    return function (input) {
        const regex = /(\d+)([^\d]+市)([^\d]+區)/g;
        const matches = [];
        let match;
        // Use regex with global flag to capture multiple occurrences
        while ((match = regex.exec(input)) !== null) {
            const zipcode = match[1];
            const region = match[2];
            const city = match[3];
            const address = input.substring(regex.lastIndex).trim()
                .replace(/,+/g, ',')
                .replace(/^,|,$/g, '');
            matches.push({zipcode, region, city, address});
        }
        if (matches.length > 0) {
            return matches; // Return all city-district pairs
        } else {
            return false; // Handle cases where no matches are found
        }
    }
})

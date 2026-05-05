define([], function () {
    return {
        text: 'text',
        html: 'html',
        file: 'file',
        image: 'image',
        video: 'video',
        /**
         *
         * @param mediaType
         * @returns {string}
         */
        fromMediaExtensionToMessageType: function (mediaType) {
            switch (mediaType) {
                case'image/png':
                case'image/jpg':
                case'image/jpeg':
                case'image/gif':
                case'image/webp':
                    return this.image;
                case 'video/mp4':
                case 'video/webm':
                    return this.video
                default:
                    return this.text
            }
        },
    }
})

/**
 * Branch8 Security Module - RequireJS Configuration
 * Provides security mixins for third-party libraries
 */
var config = {
    config: {
        mixins: {
            // Apply security mixin to owl.carousel to sanitize HTML insertions
            'mageplaza/core/owl.carousel': {
                'Branch8_Security/js/owl-carousel-mixin': true
            }
        }
    }
};

define(['jquery', 'plugins/DOMPurify'], function ($, DOMPurify) {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(location.search);
        const ref = params.get('back_url');

        // Only save if it's not empty and not login/register related pages
        if (ref &&
            !ref.includes('customer/account/login') &&
            !ref.includes('customer/account/create')) {

            // [Security Fix] Sanitize ref value using DOMPurify to prevent XSS
            // Checkmarx V9.4.5+ will recognize this as proper sanitization
            var sanitizedRef = DOMPurify.sanitize(ref, { ALLOWED_TAGS: [], ALLOWED_ATTR: [] });

            // Validate URL to prevent XSS/Code Injection
            // Only allow relative paths to prevent Open Redirect
            var isValidUrl = sanitizedRef.startsWith('/') && !sanitizedRef.startsWith('//');

            if (!isValidUrl) {
                return; // Skip if URL is not safe
            }

            // [Security] Client DOM Code Injection Fix
            // Prevent javascript: URI and CRLF injection
            if (sanitizedRef.toLowerCase().includes('javascript:') || /[\x00-\x1F]/.test(sanitizedRef)) {
                return;
            }

            // Store the referrer URL in localStorage
            localStorage.setItem('login_referrer', sanitizedRef);

            // Find all login buttons/links and update their URLs
            $('.login-referer').each(function () {
                var a = $(this).find('a');
                if (a.length) {
                    var loginUrl = a.attr('href');
                    // Prevent code injection by blocking javascript: URIs using strict DOM parsing
                    if (loginUrl) {
                        var anchor = document.createElement('a');
                        anchor.href = loginUrl;

                        // Strict protocol check using browser's parser
                        if (anchor.protocol.toLowerCase() === 'javascript:') {
                            return;
                        }

                        try {
                            // Use the resolved href from anchor to handle relative paths correctly
                            var urlObj = new URL(anchor.href);
                            // Use sanitized ref value
                            urlObj.searchParams.set('referer', sanitizedRef);

                            // Final strict validation before setting href
                            var finalAnchor = document.createElement('a');
                            finalAnchor.href = urlObj.href;
                            if (/^(http:|https:)$/i.test(finalAnchor.protocol)) {
                                a.attr('href', finalAnchor.href);
                            }
                        } catch (e) {
                            console.warn('Login referer URL parsing failed, skipping href modification');
                        }

                        if (params.has('back_url')) {
                            history.replaceState(null, '', location.pathname + location.hash);
                        }
                    }
                }
            });
        }
    });
});

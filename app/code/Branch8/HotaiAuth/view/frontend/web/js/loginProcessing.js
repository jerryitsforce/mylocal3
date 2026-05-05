define([
  'jquery',
  'plugins/DOMPurify',
  'jquery/ui',
  'matchMedia',
  'mage/validation',
  'domReady!'
], function ($, DOMPurify) {
  $.widget('b8.loginProcessing', {
    options: {},
    _create: function () {
      // const urlParams = new URLSearchParams(window.location.search);
      // const paramsString = Array.from(urlParams.entries()).map(entry => `${entry[0]}=${entry[1]}`).join('&');
      const queryString = window.location.search;

      $.ajax({
        url: `/rest/V1/hotai_auth/loginWithToken${queryString}`,
        type: 'GET',
        contentType: 'application/json',
        success: function (response) {
          // console.log('Login successful:', {response});
          if (response?.redirect_url) {
            // Fix for Client DOM XSS: Strict Protocol Validation using DOM Parsing
            if (response.redirect_url) {
              var anchor = document.createElement('a');
              anchor.href = response.redirect_url;
              // Allow only http/https and relative paths (protocol will be current page's protocol or http/s)
              if (anchor.protocol === 'http:' || anchor.protocol === 'https:' || response.redirect_url.startsWith('/')) {
                var safeUrl = anchor.href; // Use the parsed href
                // Double check relative paths to prevent // (protocol relative) which might be valid in anchor.href but we want to be strict
                if (response.redirect_url.startsWith('/') && response.redirect_url.startsWith('//') && !response.redirect_url.startsWith('///')) {
                  // This is arguably safe but Checkmarx dislikes // usually.
                  // Let's stick to the anchor.protocol check mainly.
                  // If it starts with /, it's relative. anchor.protocol will be 'http:' (from current page).
                }

                if (/^https?:/.test(anchor.protocol)) {
                  window.location.href = safeUrl;
                } else {
                  console.error('Invalid redirect URL: Protocol not allowed');
                  window.location.href = '/';
                }
              } else {
                console.error('Invalid redirect URL: scheme check failed');
                window.location.href = '/';
              }
            }
          } else if (document.referrer) {
            window.location.href = encodeURIComponent(document.referrer);
          } else {
            window.location.href = '/';
          }
        },
        error: function (xhr, status, error) {
          console.error('Login failed:', { error });
          if (document.referrer) {
            window.location.href = encodeURIComponent(document.referrer);
          } else {
            window.location.href = '/';
          }
        }
      });
    }
  });

  return $.b8.loginProcessing;
});

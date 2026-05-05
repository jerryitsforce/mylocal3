<?php

namespace MageSuite\Magepack\Plugin\Framework\RequireJs;

use Magento\Framework\View\Asset\Minification;
use Magento\Framework\Code\Minifier\AdapterInterface;

class ConfigPlugin
{
    /**
     * @var Minification
     */
    protected $minification;

    /**
     * @var AdapterInterface
     */
    protected $minifyAdapter;

    /**
     * @param Minification $minification
     * @param AdapterInterface $minifyAdapter
     */
    public function __construct(
        Minification $minification,
        \Magento\Framework\Code\Minifier\AdapterInterface $minifyAdapter
    ) {
        $this->minification = $minification;
        $this->minifyAdapter = $minifyAdapter;
    }

    /**
     * Patch the min-resolver code to be multi-context aware AND provide a safety net for fragile modules.
     *
     * 1. Multi-Context Patch: Ensures that minified paths are resolved correctly even for secondary
     *    contexts used by the mixin system (the '$' context).
     * 2. Safety Net: Pre-defines window.checkoutConfig to prevent crashes in modules like
     *    Magento_Checkout/js/model/quote when they are bundled and loaded on pages where the
     *    checkout configuration is not rendered (e.g., empty cart or non-checkout pages).
     *
     * @param \Magento\Framework\RequireJs\Config $subject
     * @param string $result
     * @return string
     */
    public function afterGetMinResolverCode(\Magento\Framework\RequireJs\Config $subject, $result)
    {
        // Re-calculate excludes logic to match Magento's core logic
        $excludes = ['url.indexOf(baseUrl)===0'];
        foreach ($this->minification->getExcludes('js') as $expression) {
            $excludes[] = '!url.match(/' . str_replace('/', '\/', $expression) . '/)';
        }
        $excludesCode = empty($excludes) ? 'true' : implode('&&', $excludes);

        // Implementation details:
        // - Safety Net: We use a simple check to ensure checkoutConfig exists.
        // - Context Patch: We intercept newContext and patch nameToUrl for every context.
        $patch = <<<code
    (function () {
        // Safety net for fragile core modules (e.g. quote.js, url-builder.js) when bundled and loaded on the cart page.
        (function(w) {
            if (w.location.href.indexOf('/checkout/cart') === -1) return;
            w.checkoutConfig = w.checkoutConfig || {};
            w.checkoutConfig.quoteData = w.checkoutConfig.quoteData || {};
            w.checkoutConfig.totalsData = w.checkoutConfig.totalsData || {};
            w.checkoutConfig.storeCode = w.checkoutConfig.storeCode || '';
            w.checkoutConfig.basePriceFormat = w.checkoutConfig.basePriceFormat || {};
            w.checkoutConfig.priceFormat = w.checkoutConfig.priceFormat || {};
            w.checkoutConfig.isCustomerLoggedIn = w.checkoutConfig.isCustomerLoggedIn || false;
            w.checkoutConfig.customerData = w.checkoutConfig.customerData || {};
            w.checkoutConfig.quoteItemData = w.checkoutConfig.quoteItemData || [];
        })(window);

        var patchContext = function (ctx) {
            if (!ctx || (ctx.nameToUrl && ctx.nameToUrl.__patched)) return;
            var origNameToUrl = ctx.nameToUrl;
            ctx.nameToUrl = function() {
                var baseUrl = ctx.config.baseUrl;
                var url = origNameToUrl.apply(ctx, arguments);
                if ({$excludesCode}) {
                    url = url.replace(/(\.min)?\.js$/, '.min.js');
                }
                return url;
            };
            ctx.nameToUrl.__patched = true;
        };

        if (typeof require !== 'undefined' && require.s && require.s.contexts) {
            if (require.s.contexts._) {
                patchContext(require.s.contexts._);
            }
            var origNewContext = require.s.newContext;
            require.s.newContext = function (name) {
                var ctx = origNewContext(name);
                patchContext(ctx);
                return ctx;
            };
        }
    })();
code;

        if ($this->minification->isEnabled('js')) {
            $patch = $this->minifyAdapter->minify($patch);
        }

        return $patch;
    }
}

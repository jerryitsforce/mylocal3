<?php

namespace Branch8\Mmegamenu\Plugin;

use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * Force public Cache-Control headers for the menu AJAX endpoint
 * right before the response is sent to the client.
 *
 * Magento's session machinery and FrontController both call
 * setNoCacheHeaders() at various points.  The only reliable place
 * to override them is just before headers are actually transmitted.
 */
class MenuDataCacheHeaderPlugin
{
    /**
     * Seconds to cache the menu response in Varnish / Fastly / browser
     */
    const TTL = 86400; // 1 day

    /**
     * Route path of the AJAX endpoint we want to cache
     */
    const ROUTE_PATH = 'mmegamenu/ajax/menuData';

    /** @var HttpRequest */
    private $request;

    /**
     * @param HttpRequest $request
     */
    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Before the response is actually sent, override Cache-Control headers
     * if the current request is for the menu data endpoint.
     *
     * @param Http $subject
     * @return void
     */
    public function beforeSendResponse(Http $subject)
    {
        if (!$this->isMenuDataRequest()) {
            return;
        }

        if ($subject->getHttpResponseCode() !== 200) {
            return;
        }

        // Wipe whatever Magento / PHP session set and force public caching
        $subject->setPublicHeaders(self::TTL);

        // Remove the session cookies so Varnish/Fastly can cache the response
        // (a menu that is the same for all guests doesn't need a session cookie)
        $subject->clearHeader('Set-Cookie');
    }

    /**
     * Check if the current request targets the menu data AJAX controller.
     *
     * @return bool
     */
    private function isMenuDataRequest(): bool
    {
        // getPathInfo() returns something like /mmegamenu/ajax/menuData/
        $path = ltrim($this->request->getPathInfo(), '/');
        return stripos($path, self::ROUTE_PATH) === 0;
    }
}

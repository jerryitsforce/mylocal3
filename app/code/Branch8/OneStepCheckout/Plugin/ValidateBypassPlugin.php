<?php

namespace Branch8\OneStepCheckout\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http as HttpRequest;

class ValidateBypassPlugin
{
    /**
     * @param CsrfValidator $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
     * @return void
     */
    public function aroundValidate(CsrfValidator $subject, callable $proceed, RequestInterface $request, ActionInterface $action): void
    {
        if ($this->shouldBypass($request)) {
            return;
        }
        $proceed($request, $action);
    }

    /**
     * Determine if the request should bypass validation
     *
     * @param RequestInterface $request
     * @return bool
     */
    protected function shouldBypass(RequestInterface $request): bool
    {
        if ($request instanceof HttpRequest && $request->getRequestUri() === '/checkout/ajax/callback') {
            return true;
        }

        return false;
    }
}

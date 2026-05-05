<?php
namespace HotaiConnected\CustomerSync\Plugin;

use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;
use Closure;

class CsrfValidatorSkip
{
    public function aroundValidate(
        CsrfValidator $subject,
        Closure $proceed,
        RequestInterface $request,
        \Magento\Framework\App\ActionInterface $action
    ) {
        if ($request->getPathInfo() === '/rest/V1/hotaiconnected-customersync' || $request->getPathInfo() === '/rest/V1/hotaiconnected-customersync-status') {
            return true;
        }
        return $proceed($request, $action);
    }
}

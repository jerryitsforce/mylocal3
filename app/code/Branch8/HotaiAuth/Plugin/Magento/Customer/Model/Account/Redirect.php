<?php

namespace Branch8\HotaiAuth\Plugin\Magento\Customer\Model\Account;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Helper\HotaiScopeConfig;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Closure;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;

#[AllowDynamicProperties] class Redirect
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $_url;

    /**
     * @var ResultFactory
     */
    private ResultFactory $_resultFactory;

    /**
     * @var RequestInterface
     */
    private RequestInterface $_request;

    /**
     * @var HotaiScopeConfig
     */
    private HotaiScopeConfig $hotaiScopeConfig;

    /**
     * @var HotaiAuthService
     */
    private HotaiAuthService $hotaiAuthService;

    public function __construct(
        RequestInterface $request,
        UrlInterface $url,
        ResultFactory $resultFactory,
        HotaiScopeConfig $hotaiScopeConfig,
        HotaiAuthService $hotaiAuthService,
    ) {

        $this->_url             = $url;
        $this->_resultFactory   = $resultFactory;
        $this->_request         = $request;
        $this->hotaiScopeConfig = $hotaiScopeConfig;
        $this->hotaiAuthService = $hotaiAuthService;
    }

    public function aroundGetRedirect($subject, Closure $proceed)
    {
        $allowActions = ['index'];
        $allowModule = ['account'];

        $redirectUrl = $this->hotaiAuthService->getRedirectUrl();

        if (!$this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::MAGENTO_CUSTOMER_REDIRECT_DASHBOARD) &&
            in_array(strtolower($this->_request->getActionName()), $allowActions) &&
            in_array(strtolower($this->_request->getModuleName()), $allowModule)
        ) {
            $result      = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setUrl($this->_url->getUrl($redirectUrl));
            return $result;
        }

        return $proceed();
    }
}

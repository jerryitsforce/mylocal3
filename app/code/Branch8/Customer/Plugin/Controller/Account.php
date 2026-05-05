<?php

namespace Branch8\Customer\Plugin\Controller;

use Closure;
use Magento\Customer\Controller\AccountInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Account{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $allowedActions = [];
    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $mpHelperData;
    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;
    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;
    /**
     * @var
     */
    protected $_urlFactory;

    /**
     * @param RequestInterface $request
     * @param Session $customerSession
     * @param \Webkul\Marketplace\Helper\Data $mpHelperData
     * @param \Magento\Framework\App\Response\Http $response
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Framework\UrlFactory $urlFactory
     * @param array $allowedActions
     */
    public function __construct(
        RequestInterface $request,
        Session $customerSession,
        \Webkul\Marketplace\Helper\Data $mpHelperData,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\UrlFactory $urlFactory,
        array $allowedActions = []
    ) {
        $this->request = $request;
        $this->session = $customerSession;
        $this->allowedActions = $allowedActions;
        $this->mpHelperData = $mpHelperData;
        $this->_customerUrl = $customerUrl;
        $this->response = $response;
        $this->_urlFactory = $urlFactory;
    }

    /**
     * Executes original method if allowed, otherwise - redirects to log in
     *
     * @param AccountInterface $controllerAction
     * @param Closure $proceed
     * @return ResultInterface|ResponseInterface|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(AccountInterface $controllerAction, Closure $proceed)
    {
        /** @FIXME Move Authentication and redirect out of Session model */
        if ($this->isActionAllowed() || ($this->session->authenticate())) {
            return $proceed();
        }
        $arguments = $this->_customerUrl->getLoginUrlParams();
        $this->response->setRedirect(
            $this->_urlFactory->create()->getUrl(\Magento\Customer\Model\Url::ROUTE_ACCOUNT_LOGIN, $arguments)
        );
    }

    /**
     * Validates whether currently requested action is one of the allowed
     *
     * @return bool
     */
    private function isActionAllowed(): bool
    {
        $action = strtolower($this->request->getActionName() ?? '');
        $pattern = '/^(' . implode('|', $this->allowedActions) . ')$/i';

        return (bool)preg_match($pattern, $action);
    }
}
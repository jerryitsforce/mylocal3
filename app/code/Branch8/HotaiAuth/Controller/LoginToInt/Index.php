<?php
namespace Branch8\HotaiAuth\Controller\LoginToInt;
use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

#[AllowDynamicProperties] class Index extends Action
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    private \Magento\Customer\Controller\Ajax\Logout $logout;

    /**
     * @param  Context  $context
     * @param  Session  $customerSession
     * @param  PageFactory  $resultPageFactory
     * @param  \Magento\Framework\App\RequestInterface  $request
     * @param  HotaiAuthService  $hotaiAuthService
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        HotaiAuthService $hotaiAuthService,
        \Magento\Customer\Controller\Ajax\Logout $logout,
    )
    {
        $this->request = $request;
        $this->hotaiAuthService = $hotaiAuthService;
        $this->logout = $logout;
        parent::__construct($context, $customerSession, $resultPageFactory);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \JsonException
     */
    public function execute()
    {
        $token = $this->hotaiAuthService->externalEncryptToken();

        $this->logout->execute();
        $baseUrl = $this->_url->getBaseUrl();

        $loginUrl = $baseUrl . 'account/LoginWithToken';
        $loginUrl = $loginUrl.'?token='.$token . '&hotai_app=true';

        print_r($loginUrl); die;
    }
}

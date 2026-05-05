<?php
namespace Branch8\HotaiAuth\Controller\Login;
use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

#[AllowDynamicProperties] class Index extends \Magento\Customer\Controller\Account\Login
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

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
        HotaiAuthService $hotaiAuthService
    )
    {
        $this->request = $request;
        $this->hotaiAuthService = $hotaiAuthService;
        parent::__construct($context, $customerSession, $resultPageFactory);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \JsonException
     */
    public function execute()
    {
        $vendor = $this->request->getParam('vendor_login');
        if($vendor){
            return parent::execute();

        }
        $hotaiUrl = $this->hotaiAuthService->getLoginUrl();

        $resultRedirect = $this->resultRedirectFactory->create();
        if (empty($hotaiUrl)) {
            $homeUrl = $this->_url->getUrl(''); // 使用 '' 來獲取首頁的 URL
            $resultRedirect->setUrl($homeUrl);
        } else {
            $resultRedirect->setUrl($hotaiUrl);
        }
        //clear current session because there is a seller logged in
        $this->session->logout();
        return $resultRedirect;
    }
}

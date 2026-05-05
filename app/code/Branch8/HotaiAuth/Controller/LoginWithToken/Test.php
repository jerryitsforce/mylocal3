<?php

namespace Branch8\HotaiAuth\Controller\LoginWithToken;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Test extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    private \Magento\Customer\Controller\Ajax\Logout $logout;
    protected $hotaiAuthService;
    /**
     * @var \Branch8\HotaiAuth\Helper\HotaiScopeConfig
     */
    protected $hotaiScopeConfig;

    /**
     * @param  Context  $context
     * @param  Session  $customerSession
     * @param  PageFactory  $resultPageFactory
     * @param  \Magento\Framework\App\RequestInterface  $request
     * @param  HotaiAuthService  $hotaiAuthService
     * @param  \Magento\Customer\Controller\Ajax\Logout  $logout
     * @param  \Branch8\HotaiAuth\Helper\HotaiScopeConfig  $hotaiScopeConfig
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        HotaiAuthService $hotaiAuthService,
        \Magento\Customer\Controller\Ajax\Logout $logout,
        \Branch8\HotaiAuth\Helper\HotaiScopeConfig $hotaiScopeConfig
    )
    {
        $this->request = $request;
        $this->hotaiAuthService = $hotaiAuthService;
        $this->logout = $logout;
        $this->hotaiScopeConfig = $hotaiScopeConfig;
        parent::__construct($context, $customerSession, $resultPageFactory);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \JsonException
     */
    public function execute()
    {return '';die;
        $token = $this->hotaiAuthService->externalEncryptToken();

        $baseUrl = $this->_url->getBaseUrl();

        $loginUrl = $baseUrl . 'account/LoginWithToken';
        $loginUrl = $loginUrl.'?appsrc=HTGO&&token='.$token . '&hotai_app=true';
        $request = $this->getRequest();
        $json = [
            "name" => $request->getParam('name'),
            "phone"=> "0912345678",
            "email"=> "giftuser@example.com",
            "address"=> [
                "zipcode"=> $request->getParam('postcode'),
                "city"=> $request->getParam('city'),
                "district"=> $request->getParam('district'),
                "detail"=> $request->getParam('street')
            ],
            "note" =>$request->getParam('note')
        ];
        $data = json_encode($json, true);
        $aesKey = $this->hotaiScopeConfig->getAuthScopeConfig(\Branch8\HotaiAuth\Helper\HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AK);
        $aesIv = $this->hotaiScopeConfig->getAuthScopeConfig(\Branch8\HotaiAuth\Helper\HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AES_IV);
        $encryptData = openssl_encrypt($data, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIv);

        $recipientInfo = base64_encode($encryptData);
        $recipientInfo = urlencode($recipientInfo);
        $loginUrl .= '&recipient_info='.$recipientInfo.'&redirect_url='.$baseUrl;

        print_r($loginUrl); die;
    }
}
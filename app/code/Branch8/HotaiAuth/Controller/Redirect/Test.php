<?php

namespace Branch8\HotaiAuth\Controller\Redirect;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Branch8\HotaiAuth\Helper\HotaiScopeConfig;

class Test extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    private \Magento\Customer\Controller\Ajax\Logout $logout;
    protected $hotaiAuthService;
    protected $hotaiScopeConfig;
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
        HotaiScopeConfig  $hotaiScopeConfig
    )
    {
        $this->request = $request;
        $this->hotaiAuthService = $hotaiAuthService;
        $this->logout = $logout;
        parent::__construct($context, $customerSession, $resultPageFactory);
        $this->hotaiScopeConfig = $hotaiScopeConfig;

    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \JsonException
     */
    public function execute()
    {
        $token = $this->hotaiAuthService->externalEncryptToken();

        $baseUrl = $this->_url->getBaseUrl();

        $loginUrl = $baseUrl . 'account/LoginWithToken';
        $appId = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_ID
        );
        $loginUrl = $loginUrl.'?token='. $token . '&appsrc=' . $appId.'&hotai_app=true';
        // $loginUrl = $loginUrl.'?appsrc=HTGO&&token='.$token . '&hotai_app=true';
        $request = $this->getRequest();
        $json = [
            'customerType' => $request->getParam('customerType'),
            'customerId' => $request->getParam('customerId'),
            "name" => $request->getParam('name'),
            "phone"=> "0912345678",
            "email"=> "giftuser@example.com",
            "address"=> [
                "zipcode"=> $request->getParam('postcode'),
                "city"=> $request->getParam('city'),
                "district"=> $request->getParam('district'),
                "detail"=> $request->getParam('street')
            ],
            "note" =>$request->getParam('note'),
            'sales' => [
                "dealerCode"=> $request->getParam('dealerCode'),
                "branchCode"=> $request->getParam('branchCode'),
                "sectionCode"=> $request->getParam('sectionCode'),
                "salesCode"=> $request->getParam('salesCode'),
                "name"=> $request->getParam('salename'),
            ]
        ];
        $data = json_encode($json, true);
        $aesKey = $this->hotaiScopeConfig->getAuthScopeConfig(HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AK);
        $aesIv = $this->hotaiScopeConfig->getAuthScopeConfig(HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AES_IV);
        $encryptData = openssl_encrypt($data, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIv);

        $recipientInfo = base64_encode($encryptData);
        $recipientInfo = urlencode($recipientInfo);
        $loginUrl .= '&recipient_info='.$recipientInfo.'&redirect_url='.$baseUrl;

        print_r($loginUrl); die;
    }
}
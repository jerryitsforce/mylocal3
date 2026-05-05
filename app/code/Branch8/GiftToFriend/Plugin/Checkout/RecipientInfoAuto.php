<?php
namespace Branch8\GiftToFriend\Plugin\Checkout;

use Branch8\HotaiAuth\Helper\HotaiScopeConfig;

class RecipientInfoAuto{

    protected $request;

    protected $customerSession;

    protected $hotaiScopeConfig;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        HotaiScopeConfig $hotaiScopeConfig,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->request = $request;
        $this->hotaiScopeConfig = $hotaiScopeConfig;
        $this->customerSession = $customerSession;
    }


    public function afterHandleLoginWithApi($subject, $result){
        if(!$this->customerSession->getId()){
            return $result;
        }
        $recipientInfoParam = $this->request->getParam('recipient_info', false);

        $recipientInfoParam = urldecode($recipientInfoParam);
        if(!$recipientInfoParam){
            return $result;
        }

        $appSrc = $this->request->getParam('appsrc', '');
        if($this->hotaiScopeConfig->getIsProduction()){
            $keys = $this->hotaiScopeConfig->getHotaiExternExchangeProductionAESKey();
            $key = isset($keys[$appSrc]) ? $keys[$appSrc] : '';
            $ivs = $this->hotaiScopeConfig->getHotaiExternExchangeProductionAESIV();
            $iv = isset($ivs[$appSrc]) ? $ivs[$appSrc] : '';
        }else{
            $key = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AK
            );
            $iv = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AES_IV
            );
        }
        $binaryInfor = base64_decode($recipientInfoParam);
        $jsonInfor = openssl_decrypt($binaryInfor, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    
        $recipientInfo = json_decode($jsonInfor, true);
        
        if(empty($recipientInfo)){
            return $result;
        }
        $this->customerSession->setGiftRecipientInfor($recipientInfo);

        return $result;
    }
}

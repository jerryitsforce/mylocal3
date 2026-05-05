<?php

namespace Branch8\Customer\Controller\Organization;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
class Update extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Branch8\Customer\Helper\OrganizationAPI
     */
    protected $organizationAPIHelper;
    /**
     * @var \Branch8\Customer\Model\Api\CustomerRepository
     */
    protected $customerRepositoryApi;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    protected $remoteAddress;

    protected $scopeConfig;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param \Branch8\Customer\Helper\OrganizationAPI $organizationAPI
     * @param \Branch8\Customer\Model\Api\CustomerRepository $customerRepositoryApi
     * @param \Psr\Log\LoggerInterface $_logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Branch8\Customer\Helper\OrganizationAPI $organizationAPI,
        \Branch8\Customer\Model\Api\CustomerRepository $customerRepositoryApi,
        \Psr\Log\LoggerInterface $_logger,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->organizationAPIHelper = $organizationAPI;
        $this->customerRepositoryApi = $customerRepositoryApi;
        $this->_logger = $_logger;
        $this->remoteAddress = $remoteAddress;
        $this->scopeConfig = $scopeConfig;
    }


    public function execute(){
        $resultJson = $this->resultJsonFactory->create();
        if(!$this->getRequest()->isPost()){
            $resultData = ['error' => true, 'message' => 'Invalid request method'];
            $resultDataEncrypted = $this->organizationAPIHelper->encryptData($resultData);
            $resultJson->setData(['result' => $resultDataEncrypted]);
            return $resultJson;
        }
        $requestIP = $this->remoteAddress->getRemoteAddress();
        $allowedIP = $this->scopeConfig->getValue('api_credential/organization/ip_whitelist');
        $allowedIP = explode(',', trim((string)$allowedIP));
        if(!in_array($requestIP, $allowedIP)){
            $resultData = ['error' => true, 'message' => 'You are not allowed'];
            $resultDataEncrypted = $this->organizationAPIHelper->encryptData($resultData);
            $resultJson->setData(['result' => $resultDataEncrypted]);
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                $this->_logger->info('Update ORG, IP:'.$requestIP);
            }
            return $resultJson;
        }
        try {
            $postDataOrigin = file_get_contents('php://input');
            try{
                $postData = json_decode($postDataOrigin, true);
            }catch (\Exception $e){
                $postData = null;
            }

            if(empty($postData) || !isset($postData['requestBody'])){
                $resultData = ['error' => true, 'message' => 'Invalid param'];
                $this->writeLog("API update organization error");
                $this->writeLog(print_r($postDataOrigin, true));
                $resultDataEncrypted = $this->organizationAPIHelper->encryptData($resultData);
                $resultJson->setData(['result' => $resultDataEncrypted]);
                return $resultJson;
            }
            $requestBody = $postData['requestBody'];
            $resultData = $this->customerRepositoryApi->updateOrganizationAction($requestBody);
            if($resultData['error']){
                $this->writeLog("API update organization error");
                $this->writeLog(print_r($postDataOrigin, true));
            }
        }catch (\Exception $e){
            $resultData = ['error' => true, 'message' => 'Exception case'];
            $this->writeLog("API update organization error");
            $this->writeLog(print_r($postDataOrigin, true));
            $this->writeLog($e->getMessage());
            
        }
        $resultDataEncrypted = $this->organizationAPIHelper->encryptData($resultData);
        $resultJson->setData(['result' => $resultDataEncrypted]);
        return $resultJson;
    }

    protected function writeLog($message){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
            $this->_logger->error($message);
        }
    }

}
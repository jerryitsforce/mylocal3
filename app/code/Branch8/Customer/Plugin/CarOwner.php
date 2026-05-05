<?php

namespace Branch8\Customer\Plugin;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Math\Random;
use Magento\Framework\Session\SessionManager;

class CarOwner
{
    protected $_curl;

    protected $_scopeConfig;

    const  API_ACTIVE = 'api_credential/car_owner/is_active';

    const API_URL = 'api_credential/car_owner/url';

    const API_SUNSCRIPTION_KEY = 'api_credential/car_owner/subscription_key';

    const API_APP_ID = 'api_credential/car_owner/app_id';

    const API_TOYOTA_ORG = 'api_credential/car_owner/toyota_org';

    const API_LEXUS_ORG = 'api_credential/car_owner/lexus_org';
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var SessionManager
     */
    protected $sessionManager;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Branch8\Customer\Helper\Group
     */
    protected $groupHelper;

    protected $_logger;

    protected $customerRepository;

    const CAR_OWNER_LIST = ['L', 'T'];

    const MAPPING_CAR_OWNER_ORG = ['L' => self::API_LEXUS_ORG, 'T' => self::API_TOYOTA_ORG];

    protected $_conn;

    /**
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param SessionManager $sessionManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Branch8\Customer\Helper\Group $groupHelper
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        SessionManager $sessionManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\Customer\Helper\Group $groupHelper,
        \Branch8\Customer\Logger\Logger $logger,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    )
    {
        $this->_curl = $curl;
        $this->_scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
        $this->resourceConnection = $resourceConnection;
        $this->groupHelper = $groupHelper;
        $this->_logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function carOwnerAPI($subject)
    {
        $customer = $this->customerSession->getCustomer();
        $customerRepository = $customer->getDataModel();
        $userProfile = $subject->getUserProfile();

        /**
         * Return if UserProfile is empty or UserProfile id is empty
         */
        if(empty($userProfile) || empty($userProfile['id'])){
            $this->writeLog('CarOwner API: UserProfile empty, customer ID:'.$customer->getId());
            return;
        }

        $isActive = $this->_scopeConfig->getValue(self::API_ACTIVE);
        if(!$isActive){
            return ;
        }

        $HotaiEmp = $this->_scopeConfig->getValue(\Branch8\Customer\Helper\OrganizationAPI::HOTAI_EMP_ORG);

        $currentGroup = $customerRepository->getGroupId();
        $orgQuery = $this->_conn->select()
            ->from(['gr' => 'customer_group'], 'organization')
            ->where('customer_group_id = ?', $currentGroup)
            ->limit(1);
        $currentOrg = $this->_conn->fetchOne($orgQuery);
        if($currentOrg == $HotaiEmp){
            return ;
        }

        try {
            $mobile = $userProfile['account'];
            $oneId = $userProfile['memberSeq'];
            $oneIdCusId = $userProfile['id'];

            $sessionData = $this->sessionManager->getData();
            $oneIdToken = '';
            if(isset($sessionData['hotai_token']['accessToken'])){
                $oneIdToken = $sessionData['hotai_token']['accessToken'];
            }

            $orgData = [
                'getOneIdCarInput' => [
                    [
                        'ONEIDTOKEN' => $oneIdToken,
                        'ONEID' => $oneId,
                        'ONEIDCUSTID' => $oneIdCusId,
                        'ONEIDMOBILE' => $mobile,
                        // 'BRAND' => 'T'
                    ]
                ]
            ];
            $processResult = null;
            foreach(self::CAR_OWNER_LIST as $_carOwner){
                $orgData['getOneIdCarInput'][0]['BRAND'] = $_carOwner;
                $requestAPIData = json_encode($orgData);

                $response = $this->callAPI($requestAPIData, $oneIdToken);

                $respponseArr = json_decode($response, true);
                if(!isset($respponseArr['ResultData']['OneIdCarExists'][0]['DATAEXISTS'])){
                    continue ;
                }
                $orgToUpdate = $this->_scopeConfig->getValue(self::MAPPING_CAR_OWNER_ORG[$_carOwner]);
                if($orgToUpdate == null){
                    continue;
                }
                $processResult = $this->processAPIResponse($respponseArr, $currentOrg, $orgToUpdate);
                /** If match a car owner => break */
                if($processResult){
                    break;
                }
            }
            if($processResult === false){
                $customerId = $customerRepository->getId();
                $this->revertOrg($customerId, $currentOrg);
            }

        }catch(\Exception $e){
            $this->writeLog('CarOwner API: Exception');
            $this->writeLog(print_r($e->getMessage(), true));
        }
    }

    protected function callAPI($requestAPIData, $oneIdToken){
        $this->writeLog('CarOwner API: Request');
        $this->writeLog(print_r($requestAPIData, true));
        $this->_curl->setTimeout(3);

        $this->_curl->addHeader("Content-Type", "application/json");

        $subscriptionKey = $this->_scopeConfig->getValue(self::API_SUNSCRIPTION_KEY);
        $this->_curl->addHeader("Ocp-Apim-Subscription-Key", $subscriptionKey);

        $appId = $this->_scopeConfig->getValue(self::API_APP_ID);
        $this->_curl->addHeader("APP_ID", $appId);

        $requestAuth = 'Bearer '.$oneIdToken;
        $this->_curl->addHeader("Authorization", $requestAuth);

        $requestURL = $this->_scopeConfig->getValue(self::API_URL);
        $this->_curl->post($requestURL, $requestAPIData);
        $response = $this->_curl->getBody();

        $this->writeLog('CarOwner API: response');
        $this->writeLog(print_r($response, true));

        return $response;
    }

    protected function processAPIResponse($respponseArr, $currentOrg, $orgToUpdate){
        $oneIdCarExists = $respponseArr['ResultData']['OneIdCarExists'][0]['DATAEXISTS'];

        $customer = $this->customerSession->getCustomer();
        $customerRepository = $customer->getDataModel();
        $customerId = $customerRepository->getId();
        $currentGroup = $customerRepository->getGroupId();

        $orgQuery = $this->_conn->select()
            ->from(['gr' => 'customer_group'], 'organization')
            ->where('customer_group_id = ?', $currentGroup)
            ->limit(1);
        $currentOrg = $this->_conn->fetchOne($orgQuery);

        $isUpdated = null;
        if($oneIdCarExists == 'Y'){

            if($currentOrg != $orgToUpdate){
                /**
                 * Set to Toyota Org
                 */
                $action = 'Update Organization after login. Match Toyota group '.$orgToUpdate;
                $this->groupHelper->setLevel($customerId, $orgToUpdate, $action);
                /**
                 * Set previous group
                 */
                $this->_conn->update('customer_entity', ['prev_org' => $currentOrg], 'entity_id='.$customerRepository->getId());
                $isUpdated = true;
            }else{
                $isUpdated = true;
            }

        }else if($oneIdCarExists == 'N'){
            $isUpdated = false;
        }

        return $isUpdated;
    }

    protected function revertOrg($customerId, $currentOrg){

        foreach(self::CAR_OWNER_LIST as $_carList){
            $orgIds[] = $this->_scopeConfig->getValue(self::MAPPING_CAR_OWNER_ORG[$_carList]);
        }
        /**
         * If not in T, L
         * No need to back to old group
         */
        if(!in_array($currentOrg, $orgIds)){
            return;
        }

        /**
         * Revert to old org
         */
        $prevOrgQuery = $this->_conn->select()
            ->from(['ce' => 'customer_entity'], 'prev_org')
            ->where('entity_id = ?', $customerId)
            ->limit(1);
        $prevOrg = $this->_conn->fetchOne($prevOrgQuery);
        if($prevOrg){
            $action = 'Update Organization after login. Back to previous Organization.';
            $this->groupHelper->setLevel($customerId, $prevOrg, $action);
        }
    }

    public function afterExecute($subject, $result){
        $this->carOwnerAPI($subject);
        return $result;
    }

    protected function writeLog($message){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
            $this->_logger->info($message);
        }
    }
}

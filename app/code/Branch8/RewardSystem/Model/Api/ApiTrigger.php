<?php
namespace Branch8\RewardSystem\Model\Api;

class ApiTrigger implements \Branch8\RewardSystem\Api\ApiTriggerInterface
{
    protected $rewardHelper;

    protected $_conn;

    protected $timezone;

    protected $apiResponse;

    protected $publisher;

    protected $rewardValidate;

    protected $_logger;

    public function __construct(
        \Branch8\RewardSystem\Helper\Data $rewardHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\RewardSystem\Api\ApiResponseInterface $apiResponse,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Branch8\RewardSystem\Helper\Validate $rewardValidate,
        \Branch8\RewardSystem\Logger\Logger $logger
    ){
        $this->_conn = $resourceConnection->getConnection();
        $this->rewardHelper = $rewardHelper;
        $this->timezone = $timezone;
        $this->apiResponse = $apiResponse;
        $this->publisher = $publisher;
        $this->rewardValidate = $rewardValidate;
        $this->_logger = $logger;
    }
    public function addData(\Branch8\RewardSystem\Api\ApiRequestInterface $requestData){
        $response = $this->apiResponse;
        try{
            $apiKey = $requestData->getApiKey();

            $checkEvent = $this->rewardHelper->isApiKeyValid($apiKey);
            $_condFields = $checkEvent['fields'];
            if(!$checkEvent){
                /** Can not find the event */
                $response->setIsSuccess(0);
                $response->setMessage(__('Unknow event.'));
                return $response;
                
            }
            $eventId = $checkEvent['id'];

            $customerIdentify = strtolower($requestData->getCustomerIdentify());
            $sqlCustomerId = $this->_conn->select()
                ->from('customer_entity', ['entity_id'])
                ->where('member_seq = ?', $customerIdentify);
            $customerId = $this->_conn->fetchOne($sqlCustomerId);
            if(!$customerId){
                $response->setIsSuccess(0);
                $response->setMessage(__('Customer does not exist.'));
                return $response;
            }

            /** Validate event limit */
            if(isset($_condFields['issue_reward']) && $_condFields['issue_reward'] == 1){
                $eventModel = $checkEvent['model'];
                $eventData = $eventModel->getData();
                $validateLimit = $this->rewardValidate->validateEventLimit($customerId, $eventData);
                if(!$validateLimit['result']){
                    $response->setIsSuccess(0);
                    $response->setMessage(__('Quantity limit reached.'));
                    return $response;
                }
            }

            $partnerIdentify = $requestData->getPartnerIdentify();
            $time8 = $this->timezone->date();
            $time8Formated = $time8->format('Y-m-d H:i:s');
            $createdAt = $this->timezone->convertConfigTimeToUtc($time8);

            $isNeedToInsertTriggerData = true;
            if($_condFields['is_onetime_trigger']){
                $apiKey = $_condFields['api_key'];
                if($this->rewardHelper->isAPITriggerExisted($eventId, $customerIdentify, $apiKey)){
                    $isNeedToInsertTriggerData = false;
                }
            }
            if($isNeedToInsertTriggerData){
                $this->_conn->insert(
                    'branch8_rewardsystem_api_call',
                    [
                        'entity_id' => NULL,
                        'event_id' => $eventId,
                        'customer_identify' => $customerIdentify,
                        'api_key' => $apiKey,
                        'partner_identify' => $partnerIdentify,
                        'created_at' => $createdAt,
                        'report_id' => 0
                    ]
                );
            }

            $isIssueReward = $_condFields['issue_reward'];
            if($isIssueReward){
                /** Add queue to check and set reward */
                $this->publisher->publish('automated.reward.check.assign_reward', json_encode([
                    'event_id' => $eventId,
                    'customer_id' => $customerId
                ]));
            }
            
            
            $response->setIsSuccess(1);
            $response->setEventId($eventId);
            $response->setPartnerIdentify($partnerIdentify);
            $response->setCustomerIdentify($customerIdentify);
            $response->setCreatedAt($time8Formated);
            $response->setMessage('');
            return $response;
        }catch(\Exception $e){
            $this->_logger->info('API Trigger ERROR');
            $this->_logger->info($e->getMessage());
            throw new \Exception('Invalid request.');
        }
    }
    
}

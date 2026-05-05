<?php
namespace Branch8\RewardSystem\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $eventCollectionFacatory;

    protected $timezone;

    protected $_conn;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Branch8\RewardSystem\Model\ResourceModel\Event\CollectionFactory $eventCollectionFacatory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        parent::__construct($context);
        $this->eventCollectionFacatory = $eventCollectionFacatory;
        $this->timezone = $timezone;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function getActiveEventCollection(){
        $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $collection = $this->eventCollectionFacatory->create()
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter('start_date', ['lteq' => $currentTime])
            ->addFieldToFilter('end_date', ['gteq' => $currentTime]);
        return $collection;
    }

    public function isVisitURL($urlToCheck){
        /** Remove / at the end */
        if(substr($urlToCheck, -1) == '/'){
            $urlToCheck = substr($urlToCheck, 0, -1);
        }
        $returdData = [];
        $collection = $this->getActiveEventCollection();
        foreach($collection as $_col){
            $triggersCondition = (string)$_col->getData('trigger_condition');
            $triggersConditionArr = json_decode($triggersCondition, true);
            if(!isset($triggersConditionArr['conditions'])){
                continue;
            }
            $conditions = $triggersConditionArr['conditions'];
            foreach($conditions as $_cond){
                if(!isset($_cond['condition'])){
                    continue;
                }
                if($_cond['condition'] != \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_VISIT_URL){
                    continue;
                }
                /** Remove / at the end */
                $condUrl = $_cond['fields']['url'];
                if(substr($condUrl, -1) == '/'){
                    $condUrl = substr($condUrl, 0, -1);
                }

                if($condUrl == $urlToCheck){
                    $returdData[$_col->getId()] = $_cond['fields'];
                }
            }
        }
        return $returdData;
    }

    public function isApiKeyValid($apiKey){
        
        $activeCollection = $this->getActiveEventCollection();
        foreach($activeCollection as $_col){
            $triggersCondition = (string)$_col->getData('trigger_condition');
            $triggersConditionArr = json_decode($triggersCondition, true);
            if(!isset($triggersConditionArr['conditions'])){
                continue;
            }
            $conditions = $triggersConditionArr['conditions'];
            foreach($conditions as $_cond){
                if(!isset($_cond['condition'])){
                    continue;
                }
                if($_cond['condition'] != \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_API){
                    continue;
                }
                if($_cond['fields']['api_key'] == $apiKey){
                    return [
                        'id' => $_col->getId(),
                        'fields' => $_cond['fields'],
                        'model' => $_col
                    ];
                }
            }
        }
        return false;
    }


    public function isFirstPurchase($statusCheck){
        $returnData = [];
        $activeCollection = $this->getActiveEventCollection();
        foreach($activeCollection as $_col){
            $triggersCondition = (string)$_col->getData('trigger_condition');
            $triggersConditionArr = json_decode($triggersCondition, true);
            if(!isset($triggersConditionArr['conditions'])){
                continue;
            }
            $conditions = $triggersConditionArr['conditions'];
            foreach($conditions as $_cond){
                if(!isset($_cond['condition'])){
                    continue;
                }
                if($_cond['condition'] != \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_FIRST_PURCHASE){
                    continue;
                }
                $orderStatus = $_cond['fields']['first_order_status'];
                if($statusCheck == $orderStatus){
                    $returnData[] = ['event_id' => $_col->getId(), 'status' => $orderStatus, 'fields' => $_cond['fields']];
                }
                
            }
        }
        return $returnData;
    }

    public function isChangeToLevel($targetLevel){
        $returnData = [];
        $activeCollection = $this->getActiveEventCollection();

        foreach($activeCollection as $_col){
            $triggersCondition = (string)$_col->getData('trigger_condition');
            $triggersConditionArr = json_decode($triggersCondition, true);
            if(!isset($triggersConditionArr['conditions'])){
                continue;
            }
            $conditions = $triggersConditionArr['conditions'];
            foreach($conditions as $_cond){
                if(!isset($_cond['condition'])){
                    continue;
                }
                if($_cond['condition'] != \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_LEVEL_CHANGE){
                    continue;
                }
                $configLevel = $_cond['fields']['to_level'];
                if($targetLevel == $configLevel){
                    $returnData[] = ['event_id' => $_col->getId(), 'level' => $configLevel, 'fields' => $_cond['fields']];
                }
                
            }
        }
        return $returnData;
    }

    public function isUrlTriggerExisted($eventId, $customerId, $url){
        $select = $this->_conn->select()
            ->from('branch8_rewardsystem_visit_url', ['entity_id'])
            ->where('event_id = ?', $eventId)
            ->where('customer_id = ?', $customerId)
            ->where('url = ?', $url);
        return $this->_conn->fetchOne($select);
    }

    public function isAPITriggerExisted($eventId, $customerIdentify, $apiKey){
        $select = $this->_conn->select()
            ->from('branch8_rewardsystem_api_call', ['entity_id'])
            ->where('event_id = ?', $eventId)
            ->where('customer_identify = ?', $customerIdentify)
            ->where('api_key = ?', $apiKey);
        return $this->_conn->fetchOne($select);
    }
    public function isLevelTriggerExisted($eventId, $customerId, $toLevel){
        $select = $this->_conn->select()
            ->from('branch8_rewardsystem_level_change', ['entity_id'])
            ->where('event_id = ?', $eventId)
            ->where('customer_id = ?', $customerId)
            ->where('to_level = ?', $toLevel);
        return $this->_conn->fetchOne($select);
    }

    public function isFirstPurchaseTriggerExisted($eventId, $customerId, $parentOrderId){
        $select = $this->_conn->select()
            ->from('branch8_rewardsystem_first_purchase', ['entity_id'])
            ->where('event_id = ?', $eventId)
            ->where('customer_id = ?', $customerId)
            ->where('parent_order_id = ?', $parentOrderId);
        return $this->_conn->fetchOne($select);
    }
}


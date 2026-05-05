<?php
namespace Branch8\RewardSystem\Helper;

class Validate extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $eventCollectionFacatory;

    protected $timezone;

    protected $_conn;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
    }

    public function validateEventLimit($customerId, $eventData){
        $eventId = $eventData['entity_id'];
        $currentDate = $this->timezone->date()->format('Y-m-d');
        
        $eventLimitValid = false;
        $failDescription = '';

        /** Event limit daily */
        $eventLimitDaily = $eventData['event_limit_daily'];
        if($eventLimitDaily == ''){
            $eventLimitValid = true;
        }else{
            $cntRewardByEventAndDate = $this->getRewardByEventAndDate($eventId, $currentDate);
            
            if($cntRewardByEventAndDate < $eventLimitDaily){
                $eventLimitValid = true;
            }else{
                $eventLimitValid = false;
                $failDescription = __('Total daily usage limit for the entire campaign has been reached.');
                return [
                    'result' => false,
                    'fail_desc' => $failDescription
                ];
            }
        }
        
        /** Event limit total */
        $rewardCnt = $eventData['reward_cnt'];
        $eventLimitTotal = $eventData['event_limit_total'];
        if($eventLimitTotal == ''){
            $eventLimitValid = true;
        }else{
            if($rewardCnt < $eventLimitTotal){
                $eventLimitValid = true;
            }else{
                $eventLimitValid = false;
                $failDescription = __('Total usage limit for the campaign has been reached.');
                return [
                    'result' => false,
                    'fail_desc' => $failDescription
                ];
            }
        }
        
        /** User limit total */
        $userLimitValid = false;
        $userLimitTotal = $eventData['user_limit_total'];
        if($userLimitTotal == ''){
            $userLimitValid = true;
        }else{
            $cntRewardByCustomer = $this->getRewardByCustomer($customerId, $eventId);
            if($cntRewardByCustomer < $userLimitTotal){
                $userLimitValid = true;
            }else{
                $userLimitValid = false;
                $failDescription = __('Reached the maximum total usage limit per user.');
                return [
                    'result' => false,
                    'fail_desc' => $failDescription
                ];
            }
        }
        
        /** User limit daily */
        $userLimitDaily = $eventData['user_limit_daily'];
        if($userLimitDaily == ''){
            $userLimitValid = true;
        }else{
            $cntRewardByCustomerAndDate = $this->getRewardByCustomerAndDate($customerId, $eventId, $currentDate);
            if($cntRewardByCustomerAndDate < $userLimitDaily){
                $userLimitValid = true;
            }else{
                $userLimitValid = false;
                $failDescription = __('Reached the maximum daily usage limit per user.');
                return [
                    'result' => false,
                    'fail_desc' => $failDescription
                ];
            }
        }

        return [
            'result' => $eventLimitValid && $userLimitValid,
            'fail_desc' => $failDescription
        ];
    }

    public function getRewardByCustomer($customerId, $eventId){
        $sql = 'select count(*) from branch8_rewardsystem_report where customer_id='.$customerId.' and event_id='.$eventId.' and status=1';
        $cnt = $this->_conn->fetchOne($sql);
        return (int)$cnt;

    }

    public function getRewardByCustomerAndDate($customerId, $eventId, $date){
        $sql = 'select count(*) from branch8_rewardsystem_report where customer_id='.$customerId.' 
            and event_id='.$eventId.' and created_at like "'.$date.'%" and status=1';
        $cnt = $this->_conn->fetchOne($sql);
        return (int)$cnt;

    }

    public function getRewardByEventAndDate($eventId, $date){
        $sql = 'select count(*) from branch8_rewardsystem_report where '.
            ' event_id='.$eventId.' and created_at like "'.$date.'%" and status=1';
        $cnt = $this->_conn->fetchOne($sql);
        return (int)$cnt;

    }

    public function validateEventTriggers($customerId, $eventId, $triggerData){
        $validateResult = ['result' => 0];
        $triggerType = $triggerData['triggerType'];
        $conditions = $triggerData['conditions'];
        $validateDetail = [];
        if($triggerType == \Branch8\RewardSystem\Model\Config\Source\TriggerType::TYPE_AND){
            $andResult = true;
            foreach($conditions as $_cond){
                switch((int)$_cond['condition']){
                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_VISIT_URL:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateVisitUrl($customerId, $eventId, $fields);

                        if(!$validateItemRes['result']){
                            return ['result' => 0];
                        }
                        if(!$fields['is_onetime_trigger']){
                            $validateDetail['branch8_rewardsystem_visit_url'] = $validateItemRes['record_id'];
                        }
                        
                        $andResult = $andResult && $validateItemRes['result'];
                        break;

                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_API:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateApiTrigger($customerId, $eventId, $fields);
                        
                        if(!$validateItemRes['result']){
                            return ['result' => 0];
                        }

                        if(!$fields['is_onetime_trigger']){
                            $validateDetail['branch8_rewardsystem_api_call'] = $validateItemRes['record_id'];
                        }
                        $andResult = $andResult && $validateItemRes['result'];
                        break;

                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_FIRST_PURCHASE:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateFirstPurchase($customerId, $eventId, $fields);
                        if(!$validateItemRes['result']){
                            return ['result' => 0];
                        }
                        if(!$fields['is_onetime_trigger']){
                            $validateDetail['branch8_rewardsystem_first_purchase'] = $validateItemRes['record_id'];
                        }
                        $andResult = $andResult && $validateItemRes['result'];
                        break;

                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_LEVEL_CHANGE:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateChangeLevel($customerId, $eventId, $fields);
                        if(!$validateItemRes['result']){
                            return ['result' => 0];
                        }
                        if(!$fields['is_onetime_trigger']){
                            $validateDetail['branch8_rewardsystem_level_change'] = $validateItemRes['record_id'];
                        }
                        $andResult = $andResult && $validateItemRes['result'];
                        break;
                }
            }
            if(!$andResult){
                return ['result' => 0];
            }else{
                return ['result' => 1, 'detail' => $validateDetail];
            }
        }else if($triggerType == \Branch8\RewardSystem\Model\Config\Source\TriggerType::TYPE_OR){
            $validateDetail = [];
            foreach($conditions as $_cond){
                switch((int)$_cond['condition']){
                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_VISIT_URL:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateVisitUrl($customerId, $eventId, $fields);
                        if($validateItemRes['result']){
                            $validateDetail['branch8_rewardsystem_visit_url'] = $validateItemRes['record_id'];
                            return ['result' => 1, 'detail' => $validateDetail];
                        }
                        break;

                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_API:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateApiTrigger($customerId, $eventId, $fields);
                        if($validateItemRes['result']){
                            $validateDetail['branch8_rewardsystem_api_call'] = $validateItemRes['record_id'];
                            return ['result' => 1, 'detail' => $validateDetail];
                        }
                        break;

                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_FIRST_PURCHASE:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateFirstPurchase($customerId, $eventId, $fields);
                        if($validateItemRes['result']){
                            $validateDetail['branch8_rewardsystem_first_purchase'] = $validateItemRes['record_id'];
                            return ['result' => 1, 'detail' => $validateDetail];
                        }

                        break;
                    case \Branch8\RewardSystem\Model\Config\Source\Conditions::COND_LEVEL_CHANGE:
                        $fields = $_cond['fields'];
                        $validateItemRes = $this->validateChangeLevel($customerId, $eventId, $fields);
                        if($validateItemRes['result']){
                            $validateDetail['branch8_rewardsystem_level_change'] = $validateItemRes['record_id'];
                            return ['result' => 1, 'detail' => $validateDetail];
                        }
                        break;
                }
            }
        }

        return $validateResult;
    }

    public function validateVisitUrl($customerId, $eventId, $fields){
        $url = $fields['url'];
        if(substr($url, -1) == '/'){
            $url = substr($url, 0, -1);
        }
        
        $sqlCheck = $this->_conn->select()
            ->from('branch8_rewardsystem_visit_url', ['entity_id'])
            ->where('customer_id = ?', $customerId)
            ->where('event_id = ?', $eventId)
            ->where('url = ?', $url);
        if($fields['is_onetime_trigger'] == 0){
            $sqlCheck->where('report_id = 0');
        }
        $recordId = $this->_conn->fetchOne($sqlCheck);

        return ['result' => (int)$recordId, 'record_id' => $recordId];
    }

    public function validateApiTrigger($customerId, $eventId, $fields){
        $apiKey = $fields['api_key'];
        $selectMemberseq = $this->_conn->select()
            ->from('customer_entity', ['member_seq'])
            ->where('entity_id = ?', $customerId);
        $memberSeq = $this->_conn->fetchOne($selectMemberseq);

        $sqlTrigger = $this->_conn->select()
            ->from('branch8_rewardsystem_api_call', ['entity_id'])
            ->where('api_key = ?', $apiKey)
            ->where('event_id = ?', $eventId)
            ->where('customer_identify = ?', $memberSeq)
            ->order('entity_id desc')
            ->limit(1);
        if($fields['is_onetime_trigger'] == 0){
            $sqlTrigger->where('report_id = 0');
        }
        
        $recordId = $this->_conn->fetchOne($sqlTrigger);

        return ['result' => (int)$recordId, 'record_id' => $recordId];
    }

    public function validateFirstPurchase($customerId, $eventId, $fields){
        $sqlCheck = $this->_conn->select()
            ->from('branch8_rewardsystem_first_purchase', ['entity_id', 'parent_order_id'])
            ->where('customer_id = ?', $customerId)
            ->where('event_id = ?', $eventId);
        if($fields['is_onetime_trigger'] == 0){
            $sqlCheck->where('report_id = 0');
        }
        $sqlCheck->order('entity_id desc')
            ->limit(1);
        
        $triggerData = $this->_conn->fetchRow($sqlCheck);
        if(empty($triggerData)){
            return ['result' => 0];
        }
        /** Validate first time */
        $parentOrderId = $triggerData['parent_order_id'];
        $sqlFirstTime = $this->_conn->select()
            ->from('sales_parent_order_detail', ['entity_id'])
            ->columns([new \Zend_Db_Expr('count(entity_id) as cnt')])
            ->where('customer_id = ?', $customerId)
            ->where('parent_id < ?', $parentOrderId);
        
        $resFirstTime = $this->_conn->fetchOne($sqlFirstTime);
        if($resFirstTime){
            return ['result' => 0];
        }

        $recordId = $triggerData['entity_id'];
        return ['result' => (int)$recordId, 'record_id' => $recordId];
    }

    public function validateChangeLevel($customerId, $eventId, $fields){
        $toLevel = $fields['to_level'];
        $sqlCheck = $this->_conn->select()
            ->from('branch8_rewardsystem_level_change', ['entity_id'])
            ->where('customer_id = ?', $customerId)
            ->where('event_id = ?', $eventId)
            ->where('to_level = ?', $toLevel)
            ->order('entity_id desc')
            ->limit(1);
        if($fields['is_onetime_trigger'] == 0){
            $sqlCheck->where('report_id = 0');
        }
        
        $recordId = $this->_conn->fetchOne($sqlCheck);
        
        return ['result' => (int)$recordId, 'record_id' => $recordId];
    }
}


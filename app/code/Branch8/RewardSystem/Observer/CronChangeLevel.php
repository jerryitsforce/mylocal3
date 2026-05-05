<?php
namespace Branch8\RewardSystem\Observer;

class CronChangeLevel implements \Magento\Framework\Event\ObserverInterface
{

    protected $publisher;

    protected $rewardHelper;

    protected $customerSession;

    protected $_conn;

    protected $timezone;

    protected $checkChangeLevel = null;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Branch8\RewardSystem\Helper\Data $rewardHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->publisher = $publisher;
        $this->rewardHelper = $rewardHelper;
        $this->_conn = $resourceConnection->getConnection();
        $this->timezone = $timezone;
    }

    public function execute(\Magento\Framework\Event\Observer $observer){
        try{
            $customerId = $observer->getData('customer_id');
            $oldGroup = (int)$observer->getData('old_level');
            $newGroup = $observer->getData('new_level');
            if($newGroup == $oldGroup){
                return;
            }
            if(!isset($this->checkChangeLevel[$newGroup])){
                $this->checkChangeLevel[$newGroup] = $this->rewardHelper->isChangeToLevel($newGroup);
            }
            $checkChangeLevel = $this->checkChangeLevel[$newGroup];
            if(empty($checkChangeLevel)){
                return ;
            }

            $createdAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            foreach($checkChangeLevel as $_row){

                $isNeedToInsertTriggerData = true;
                $_condFields = $_row['fields'];
                $_eventId = $_row['event_id'];
                $toLevel = $_condFields['to_level'];
                if($_condFields['is_onetime_trigger']){
                    if($this->rewardHelper->isLevelTriggerExisted($_eventId, $customerId, $toLevel)){
                        $isNeedToInsertTriggerData = false;
                    }
                }
                if($isNeedToInsertTriggerData){
                    $this->_conn->insert('branch8_rewardsystem_level_change', 
                        [
                            'entity_id' => NULL,
                            'event_id' => $_eventId,
                            'customer_id' => $customerId,
                            'from_level' => $oldGroup,
                            'to_level' => $newGroup,
                            'created_at' => $createdAt,
                            'report_id' => 0
                        ]
                    );
                }
                
                $isIssueReward = $_condFields['issue_reward'];
                if($isIssueReward){
                    /** Add queue to check and set reward */
                    $this->publisher->publish('automated.reward.check.assign_reward', json_encode([
                        'event_id' => $_eventId,
                        'customer_id' => $customerId
                    ]));
                }
                

            }
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }
}
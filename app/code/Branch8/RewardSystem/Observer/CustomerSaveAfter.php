<?php
namespace Branch8\RewardSystem\Observer;

class CustomerSaveAfter implements \Magento\Framework\Event\ObserverInterface
{

    protected $publisher;

    protected $rewardHelper;

    protected $customerSession;

    protected $_conn;

    protected $timezone;

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
            $customer = $observer->getData('data_object');
            $oldGroup = (int)$customer->getOrigData('group_id');
            $newGroup = $customer->getData('group_id');
            if($newGroup == $oldGroup){
                return;
            }
            $checkChangeLevel = $this->rewardHelper->isChangeToLevel($newGroup);
            if(empty($checkChangeLevel)){
                return ;
            }

            $customerId= $customer->getId();
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
                        'event_id' => $_row['event_id'],
                        'customer_id' => $customerId
                    ]));
                }
            }
        }catch(\Exception $e){
            
        }
    }
}
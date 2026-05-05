<?php
namespace Branch8\RewardSystem\Observer;

use Magento\Framework\Event\Observer;

class FirstPurchase implements \Magento\Framework\Event\ObserverInterface
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

    public function execute(Observer $observer)
    {
        try{
            /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail */
            $parentOrder = $observer->getData('data_object');
            $newStatus = $parentOrder->getData('status');
            $checkData = $this->rewardHelper->isFirstPurchase($newStatus);
            $customerId= $parentOrder->getCustomerId();
            $createdAt = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            foreach($checkData as $_row){

                $isNeedToInsertTriggerData = true;
                $_condFields = $_row['fields'];
                $_eventId = $_row['event_id'];
                $parentOrderId = $parentOrder->getParentId();
                if($_condFields['is_onetime_trigger']){
                    if($this->rewardHelper->isFirstPurchaseTriggerExisted($_eventId, $customerId, $parentOrderId)){
                        $isNeedToInsertTriggerData = false;
                    }
                }
                if($isNeedToInsertTriggerData){
                    $this->_conn->insert(
                        'branch8_rewardsystem_first_purchase',
                        [
                            'entity_id' => NULL,
                            'event_id' => $_eventId,
                            'customer_id'  => $customerId,
                            'parent_order_id' => $parentOrderId,
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

        }
    }
}
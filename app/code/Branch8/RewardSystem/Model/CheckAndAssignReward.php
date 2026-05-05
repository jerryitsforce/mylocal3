<?php
namespace Branch8\RewardSystem\Model;

use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;

class CheckAndAssignReward extends \Magento\Framework\MessageQueue\ConsumerConfiguration{

    const CONSUMER_NAME = "automated.reward.check.assign_reward";

    const QUEUE_NAME = "automated.reward.check.assign_reward";

    protected $_conn;

    protected $timezone;

    protected $rewardValidate;

    protected $notificationCollection;

    protected $serializer;

    protected $customerNotificationFactory;

    protected $customerNotificationResource;

    protected $automatedRewardReportFactory;

    protected $_logger;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\RewardSystem\Helper\Validate $rewardValidate,
        \Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory $notificationCollection,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magenest\NotificationBox\Model\CustomerNotificationFactory $customerNotificationFactory,
        \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification $customerNotificationResource,
        \Branch8\RewardSystem\Model\AutomatedRewardReportFactory $automatedRewardReportFactory,
        \Branch8\RewardSystem\Logger\Logger $logger
    ){
        $this->_conn = $resourceConnection->getConnection();    
        $this->timezone = $timezone;
        $this->rewardValidate = $rewardValidate;
        $this->notificationCollection = $notificationCollection;
        $this->serializer = $serializer;
        $this->customerNotificationFactory = $customerNotificationFactory;
        $this->customerNotificationResource = $customerNotificationResource;
        $this->automatedRewardReportFactory = $automatedRewardReportFactory;
        $this->_logger = $logger;
    }

    public function process($request){
        try{
            $data = json_decode($request, true);
            $eventId = $data['event_id'];
            $customerId = $data['customer_id'];
            $currentDate = $this->timezone->date();
            $currentDateUTC = $this->timezone->convertConfigTimeToUtc($currentDate);
            $sqlEvent = 'select * from branch8_rewardsystem_event where entity_id='.$eventId.' and status=1 and start_date <= "'.$currentDateUTC.'" and end_date >= "'.$currentDateUTC.'"';
            $event = $this->_conn->fetchRow($sqlEvent);
            if(!$event['entity_id']){
                return;
            }
            $triggerConditionStr = (string)$event['trigger_condition'];
            $triggerConditionData = json_decode($triggerConditionStr, true);
            if(!isset($triggerConditionData['triggerType'])){
                return;
            }
            
            $isPassTrigger = $this->rewardValidate->validateEventTriggers($customerId, $eventId, $triggerConditionData);
            if(!$isPassTrigger['result']){
                return;
            }

            $report = $this->automatedRewardReportFactory->create();

            $rewardType = $event['reward_type'];
            $insertData = [
                'customer_id' => $customerId,
                'event_id' => $eventId,
                'reward_type' => $rewardType,
                'trigger_condition' => $event['trigger_condition'],
                'created_at' => $this->timezone->date()->format('Y-m-d H:i:s')
            ];
            /** Validate limit */
            $validateLimit = $this->rewardValidate->validateEventLimit($customerId, $event);
            if(!$validateLimit['result']){
                $insertData['status'] = 0;
                $failReason = [];
                if(!$validateLimit['result']){
                    $failReason[] = $validateLimit['fail_desc'];
                }
                
                $insertData['fail_reason'] = implode('|', $failReason);
                $report->setData($insertData)->save();

                $this->updateReportIdToTriggerTable($isPassTrigger, $report);
                return;
            }

            /** Issue an reward */
            $expiryDate = '';
            if($rewardType == \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_POOL){
                $insertData['reward_pool_id'] = $event['reward_pool_id'];
                $insertData['reward_pool_batch_code'] = $event['reward_pool_batch_code'];
                /** Get serial */
                $rewardData = $this->getPoolSerial($event['reward_pool_id'], $event['reward_pool_batch_code']);
                if(!empty($rewardData)){
                    $expiryDate = $rewardData['ed_date'];// time +8
                    $insertData['reward_pool_serial'] = $rewardData['serial_number'];
                    /** Update serial status */
                    $this->updatePoolData($customerId, $rewardData);
                }
                
            }else{
                //$expiryDate = ?
                $insertData['reward_pool_id'] = NULL;
                $insertData['reward_pool_batch_code'] = NULL;
                $insertData['reward_pool_serial'] = NULL;
            }

            if(empty($rewardData)){
                $failReason = [];
                if($rewardType == \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_POOL && empty($rewardData)){
                    $failReason[] = __("No ticket serial found");
                }
                if($rewardType == \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_COUPON && empty($rewardData)){
                    $failReason[] = __("Can't generate coupon");
                }
                $insertData['status'] = 0;
                $insertData['fail_reason'] = implode('|', $failReason);
                $report->setData($insertData)->save();

                $this->updateReportIdToTriggerTable($isPassTrigger, $report);
                return;
            }
            /** All success */
            $insertData['status'] = 1;/** 1.success 0.success but can't assign reward */
            $insertData['fail_reason'] = NULL;
            $report->setData($insertData)->save();
            
            $this->updateReportIdToTriggerTable($isPassTrigger, $report);

            /** Update reward count */
            $this->updateRewardCount($eventId);

            /** Push notification */
            $this->addNotification($customerId, $report, $event);
            
        }catch(\Exception $e){
            $this->_logger->info($request);
            $this->_logger->info($e->getMessage());
        }
    }

    protected function addNotification($customerId, $report, $event){
        $customerRowData = $this->getCustomerRowData($customerId);
        $eventNotiTitle = $event['notification_title'];
        $eventNotiDetail = $event['notification_content'];
        $expiryDate = $this->timezone->date(new \DateTime($event['end_date']))->format('Y-m-d H:i:s');
        $rewardCode = '';
        if($event['reward_type'] == \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_POOL){
            $rewardCode = $report->getData('reward_pool_serial');
        }
        if($event['reward_type'] == \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_COUPON){
            $rewardCode = $report->getData('reward_coupon');
        }
        $notiParams = [
            '{customer_name}' => $customerRowData['firstname'],
            '{reward_code}' => $rewardCode,
            '{reward_title}' => $event['title'],
            '{expiry_date}' => $expiryDate
        ];
        $notificationData = [
            'ars_report_id' => $report->getId(),
            'description' => $this->convertNotiTitle($eventNotiTitle, $notiParams),
            'detail_description' => $this->convertNotiDetail($eventNotiDetail, $notiParams)
        ];
        $customerGroupId = $customerRowData['group_id'];
        
        $notificationType = $this->notificationCollection->create()
            ->addFieldToFilter('is_active', Notification::ACTIVE)
            ->addFieldToFilter('notification_type', ['automated_reward'])
            ->getFirstItem()
            ->getData();
        $storeId = 1;

        // Remove notifications that don't match store conditions
        $listStore = $this->serializer->unserialize($notificationType['store_view']);
        if (is_array($listStore) && !in_array('0', $listStore) && !in_array($storeId, $listStore)) {
            return;
        }
        // Remove notifications that don't match customer group conditions
        $listCustomerGroup = $this->serializer->unserialize($notificationType['customer_group']);
        if (is_array($listCustomerGroup) && !in_array('0', $listCustomerGroup) && !in_array($customerGroupId, $listCustomerGroup)) {
            return;
        }

        $notification['notification_id'] = $notificationType['id'];
        $notification['notification_type'] = 'automated_reward';
        $notification['customer_id'] = $customerId;
        $notification['ars_report_id'] = $notificationData['ars_report_id'];
        $notification['icon'] = $notificationType['image'];
        $notification['star'] = CustomerNotificationModel::UNSTAR;
        $notification['status'] = CustomerNotificationModel::STATUS_UNREAD;
        $notification['description'] = $notificationData['description'];
        $notification['detail_description'] = $notificationData['detail_description'];
        $customerNotification = $this->customerNotificationFactory->create();
        $customerNotification->addData($notification);
        $this->customerNotificationResource->save($customerNotification);
    }

    protected function getCustomerRowData($customerId){
        $sqlCustomer = $this->_conn->select()
            ->from(['customer' => 'customer_entity'])
            ->where('entity_id = ?', $customerId);
        return $this->_conn->fetchRow($sqlCustomer);
    }

    protected function convertNotiTitle($notiTitle, $data){
        foreach($data as $keyDat => $valDat){
            $notiTitle = str_replace($keyDat, $valDat, $notiTitle);
        }

        return $notiTitle;
    }
    protected function convertNotiDetail($notiDetail, $data){
        foreach($data as $keyDat => $valDat){
            $notiDetail = str_replace($keyDat, $valDat, $notiDetail);
        }

        return $notiDetail;
    }

    protected function getPoolSerial($eventId, $batchCode){
        $sqlGetSerial= 'select entity_id, serial_number, ADDTIME(end_date, "08:00:00") as ed_date from ticket_event_ticket where status='.\Branch8\HotaiCore\Model\Ticket\Status::STATUS_IMPORTED.' 
             and event_id='.$eventId.' and batch_code="'.$batchCode.'" limit 1;';
        $serialData = $this->_conn->fetchRow($sqlGetSerial);
        return $serialData;

    }

    protected function updateReportIdToTriggerTable($isPassTrigger, $report){
        foreach($isPassTrigger['detail'] as $tableName => $_entityId){
            $updateItemSql = 'update '.$tableName.' set report_id='.$report->getId().' where entity_id='.$_entityId;
            $this->_conn->query($updateItemSql);
        }
    }

    protected function updatePoolData($customerId, $rewardData){

        $customerRowData = $this->getCustomerRowData($customerId);

        $sqlUpdateSerial = 'update ticket_event_ticket set status='.\Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED.' where entity_id='.$rewardData['entity_id'];
        $this->_conn->query($sqlUpdateSerial);
        $currentCustomerTicketTime8 = $this->timezone->date();
        $currentCustomerTicketTime0 = $this->timezone->convertConfigTimeToUtc($currentCustomerTicketTime8);
        $sqlUpdateCustomerTicket = 'update customer_ticket set status='.\Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED.', member_seq="'.$customerRowData['member_seq'].'",
            updated_at="'.$currentCustomerTicketTime0.'" where ticket_table_name="ticket_event_ticket" and ticket_table_record_id='.$rewardData['entity_id'];
        $this->_conn->query($sqlUpdateCustomerTicket);
    }

    public function updateRewardCount($eventId){
        $sql = 'update branch8_rewardsystem_event set reward_cnt = reward_cnt + 1 where entity_id='.$eventId;

        $this->_conn->query($sql);
    }

}

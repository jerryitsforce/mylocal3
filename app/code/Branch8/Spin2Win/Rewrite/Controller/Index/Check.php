<?php

namespace Branch8\Spin2Win\Rewrite\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\SalesRule\Model\CouponGenerator;
use Magento\SalesRule\Api\Data\CouponGenerationSpecInterfaceFactory;
use Magento\SalesRule\Model\Service\CouponManagementService;
use Magento\Framework\Session\SessionManagerInterface;
use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Magento\SalesRule\Api\RuleRepositoryInterface;

class Check extends \Webkul\SpinToWin\Controller\Index\Check
{
    /**
     * @var HotaiPointHelper
     */
    protected HotaiPointHelper $hotaiPointHelper;

    protected $_conn;

    protected $b8SpinHelper;

    protected $b8AwardHelper;

    protected $timezone;

    protected $publisher;

    protected $notificationCollection;

    protected $serialize;

    protected $notificationQueue;

    protected $notificationQueueFactory;

    protected $customerNotificationFactory;

    protected $customerNotificationResource;

    protected $ruleRepository;

    public function __construct(
        Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory,
        \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory,
        \Webkul\SpinToWin\Helper\Data $helper,
        CouponGenerator $couponGenerator,
        CookieMetadataFactory $cookieMetadata,
        CookieManagerInterface $cookieManager,
        SessionManagerInterface $sessionManager,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\Spin2Win\Helper\Data $b8SpinHelper,
        \Branch8\Spin2Win\Helper\Award $b8AwardHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory $notificationCollection,
        \Magento\Framework\Serialize\SerializerInterface $serialize,
        NotificationQueue $notificationQueue,
        NotificationQueueFactory $notificationQueueFactory,
        \Magenest\NotificationBox\Model\CustomerNotificationFactory $customerNotificationFactory,
        \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification $customerNotificationResource,
        HotaiPointHelper $hotaiPointHelper,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->serializer = $serializer;
        $this->helper = $helper;
        $this->infoFactory = $infoFactory;
        $this->reportsFactory = $reportsFactory;
        $this->segmentsFactory = $segmentsFactory;
        $this->logger = $logger;
        $this->couponGenerator = $couponGenerator;
        $this->cookieMetadata = $cookieMetadata;
        $this->cookieManager = $cookieManager;
        $this->sessionManager = $sessionManager;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $serializer, $infoFactory, $reportsFactory, $segmentsFactory,
            $helper, $couponGenerator, $cookieMetadata, $cookieManager, $sessionManager, $logger, $customerSession);
        
        $this->_conn = $resourceConnection->getConnection();
        $this->b8SpinHelper = $b8SpinHelper;
        $this->b8AwardHelper = $b8AwardHelper;
        $this->timezone = $timezone;
        $this->publisher = $publisher;
        $this->notificationCollection = $notificationCollection;
        $this->serialize = $serialize;
        $this->notificationQueue = $notificationQueue;
        $this->notificationQueueFactory = $notificationQueueFactory;
        $this->customerNotificationFactory = $customerNotificationFactory;
        $this->customerNotificationResource = $customerNotificationResource;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->ruleRepository = $ruleRepository;
    }

    public function execute()
    {

        $storeCode = $this->_customerSession->getStoreCode();
        try {
            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $result = [];
            $isSuccess = false;
            if(!$this->getRequest()->isPost()){
                return $this->getResponse()->setBody($this->serializer
                ->serialize([
                        'success' => $isSuccess,
                        'message_title' => __('Event Not Found'),
                        'message' => __("We're sorry, but this event could not be found. Please try again later.")
                    ]));
            }

            if (!$this->_customerSession->isLoggedIn()){
                return $this->getResponse()->setBody($this->serializer
                ->serialize([
                        'success' => $isSuccess,
                        'need_login' => true,
                        'message_title' => __('Please log in to join the event'),
                        'message' => __('To fully enjoy this event, please sign in to your account. Thank you')
                    ]));
            }

            $data = $this->getRequest()->getParams();
            $spinId = $data['spin-wheel-id'];
            $spin = $this->infoFactory->create()->load($spinId);
        
            if(!$spin->getId()){
                return $this->getResponse()->setBody($this->serializer
                ->serialize([
                        'success' => $isSuccess,
                        'message_title' => __('Event Not Found'),
                        'message' => __("We're sorry, but this event could not be found. Please try again later.")
                    ]));
            }

            if($spin->getId() && $spin->getStatus() == 0){
                return $this->getResponse()->setBody($this->serializer
                ->serialize([
                        'success' => $isSuccess,
                        'message_title' => __('Event Not Found'),
                        'message' => __("We're sorry, but this event could not be found. Please try again later.")
                    ]));
            }

            $customerId = $this->_customerSession->getCustomer()->getId();
            $customerGroupId = $this->b8SpinHelper->getCustomerGroupId($customerId);
            $spinAllowedGroup = explode(',', (string)$spin->getData('customergroup_ids'));
            if(!in_array($customerGroupId, $spinAllowedGroup)){
                return $this->getResponse()->setBody($this->serializer
                        ->serialize([
                        'success' => $isSuccess,
                        'message_title' => __('Unable to Join This Event'),
                        'message' => __("Your membership level does not meet the eligibility requirements for this event. We appreciate your understanding.")
                    ]));
            }

            if($spin->getId() && $spin->getScheduled()){
                $currentDateTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                $startDate = $spin->getStartDate();
                $endDate = $spin->getEndDate();
                
                if(strtotime($currentDateTime) < strtotime($startDate)){
                    return $this->getResponse()->setBody($this->serializer
                        ->serialize([
                        'success' => $isSuccess,
                        'message' => __("The campaign isn't active.")
                    ]));
                }
                if(strtotime($currentDateTime) > strtotime($endDate)){
                    return $this->getResponse()->setBody($this->serializer
                        ->serialize([
                            'success' => $isSuccess,
                            'expired' => true
                    ]));
                }
                
            }

            $msg = __('Something went wrong.');
            
            $customerId = $this->_customerSession->getCustomerId();
            $customer = $this->_customerSession->getCustomer();
            $chanceData = $this->b8SpinHelper->getTotalChances($spin->getData(), $customerId);
            $chances = $chanceData['total'];
            if(!$chances){
                return $this->getResponse()->setBody($this->serializer
                ->serialize([
                        'success' => $isSuccess,
                        'message' => __("Today's quota has been reached.")
                    ]));
            }

            $segmentDetail = $this->calculateResult($spin);
            $segmentId = $segmentDetail['segmentid'];
            $segmentPos = $segmentDetail['segment'];
            if ($segmentId) {
                $this->_conn->beginTransaction();

                $segment = $this->segmentsFactory->create()->load($segmentId);
                $isSuccess = true;
                $msg = __('Successfully spinned.');
                $result['type'] = $segment->getType();
                $result['name'] = $segment->getLabel();
                $result['heading'] = $segment->getHeading();
                $result['description'] = $segment->getDescription();
                $result['segmentid'] = $segment->getId();
                $result['segment'] = $segmentPos;
                $report = $this->reportsFactory->create();
                $status = 0;
                if ($segment->getType() == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE) {
                    $couponSpecData = [
                        'rule_id' => $segment->getRuleId(),
                        'qty' => 1,
                        'length' => 12,
                        'format' => 'alphanum',
                    ];
                    $coupon = $this->couponGenerator->generateCodes($couponSpecData)[0];
                    $report->setCoupon($coupon);
                    $report->setRuleId($segment->getRuleId());
                    $report->setCouponImage($segment->getCouponImage());
                    $report->setCouponExpiredDate($segment->getCouponExpiredDate());
                    $result['coupon'] = $coupon;
                    $result['coupon_expired_date'] = $segment->getCouponExpiredDate();
                    /** Get rule name */
                    $salesRule = $this->ruleRepository->getById($segment->getRuleId());
                    $ruleName = $salesRule->getName();
                    $report->setRuleName($ruleName);
                    // $status = 1;
                }else if($segment->getType() == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
                    $poolId = $segment->getPoolId();
                    /** Get pool name */
                    $poolName = $this->b8AwardHelper->getPoolName($poolId);
                    if($poolName){
                        $report->setPoolName($poolName);
                    }else{
                        throw new \Exception(__('Error assigning serial number to customer.'));
                    }
                    $memberSeq = $this->_customerSession->getCustomer()->getDataModel()->getCustomAttribute('member_seq');
                    if(!$memberSeq){
                        throw new \Exception(__('Member seq of customer %1 is empty.', $this->_customerSession->getCustomer()->getEmail()));
                    }
                    $memberSeqValue = $memberSeq->getValue();
                    $poolSerialNumberData = $this->b8AwardHelper->getPoolSerialForPrize($poolId, $memberSeqValue);
                    
                    /**
                     * Can't get serial
                     * => Find a fail segment to return
                     */
                    if(!$poolSerialNumberData){
                        throw new \Exception(__('Error assigning serial number to customer.'));
                    }else{
                        $result['reward_serial_number'] = $poolSerialNumberData['serial'];
                        $report->setPoolId($poolId);
                        $report->setRewardSerialNumber($poolSerialNumberData['serial']);
                        $report->setSerialNumberId($poolSerialNumberData['serial_number_id']);
                        $result['serial_number_id'] = $poolSerialNumberData['serial_number_id'];
                        
                    }
                    
                }else if($segment->getType() == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE){
                    $sku = $segment->getSku();
                    $result['sku'] = $sku;
                    $report->setSku($sku);
                    $report->setPhysicalImage($segment->getPhysicalImage());
                    $report->setPhysicalExpireDate($segment->getPhysicalExpireDate());
                }else if($segment->getType() == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
                    $point = $segment->getRewardPoint();
                    /**
                     * Call API add point
                     */
                    $addPointResult = $this->b8AwardHelper->getRewardPointForPrize($spin, $customerId, $segment->getData());
                    if(!$addPointResult['result']){
                        throw new \Exception(__('Error on add prize point to customer.'));
                    }else{
                        $result['total_points'] = $this->hotaiPointHelper->formatPoints((float) $this->hotaiPointHelper->getHotaiPoint(), false, false, false);
                        $result['reward_point'] = $point;
                        $result['point_expire_date'] = $segment->getPointExpireDate();
                        $report->setRewardPoint($point);
                        $report->setPointExpireDate($segment->getPointExpireDate());
                        $report->setRewardPointAccount($segment->getRewardPointAccount());
                        $report->setPrizeTraceNo($addPointResult['trace_no']);
                        if((int)$segment->getRewardPointAccount()){
                            $report->setPointBuNo($segment->getPointBuNo());
                            $report->setPointRsNo($segment->getPointRsNo());
                        }else{
                            $report->setPointBuNo('');
                            $report->setPointRsNo('');
                        }
                    }
                }
                $report->setSpinId($spinId);
                $report->setEmail($customer->getBuyerEmail());
                $report->setName($customer->getFirstname());
                $report->setResult($segment->getType());
                $report->setSegmentId($segment->getId());
                $report->setSegmentLabel($segment->getLabel());
                $report->setSegmentHeading($segment->getHeading());
                $report->setStoreid($storeCode);
                $report->setCreatedAt($this->timezone->convertConfigTimeToUtc($this->timezone->date()));
                $report->setCustomerId($customerId);
                $report->save();

                /** Update redempt record to used if needed */
                $eventChance = $chanceData['event_chances'];
                $eventConfigChance = $spin->getData('x_times');
                /**
                 * have to+ 1 because the $eventChance got before play
                 */
                if($eventChance -1 < 0){
                    /** Set trace no if this spin use redemption record */
                    $sqlRedemptionRecord = 'Select trace_no from spintowin_redemption where customer_id='.$customerId.' and spin_id='.$spinId.' 
                        and is_used=0 order by redemption_id desc limit 1';
                    $traceNo = $this->_conn->fetchOne($sqlRedemptionRecord);
                    $report->setRedeemTraceNo($traceNo);
                    $report->save();

                    /** Update redempt record, mark is_used=1 */
                    $this->b8SpinHelper->updateRedemptIsUsed($spinId, $customerId);
                }
                

                if($report->getId()) {
                    $result['smrpid'] = $report->getId();
                } else {
                    throw new \Exception(__('Error saving report.'));
                }
                $segment->setAvailed(new \Zend_Db_Expr('availed +1'));
                $segment->save();
                // $this->setCookieData($data, $spinId);

                /**
                 * Check threshold
                 */
                $sqlThreshold = 'select limits, availed, threshold, stock_reminder from spintowin_segments where entity_id='.$segment->getId();
                $thresholdData = $this->_conn->fetchRow($sqlThreshold);
                if((int)$thresholdData['limits'] != 0 && $thresholdData['stock_reminder'] 
                    && (((int)$thresholdData['limits'] - (int)$thresholdData['availed']) <= $thresholdData['threshold'])){
                        $lowStockData = ['type' => 'low_stock', 'data' => [
                            'event_name' => $spin->getName(),
                            'segment_name' => $segment->getLabel(),
                            'threshold' => $segment->getThreshold(),
                            'remain_qty' => ((int)$thresholdData['limits'] - (int)$thresholdData['availed']),
                            'emails' => $spin->getSlowStockAlertEmail()
                        ]];
                        $this->publisher->publish(
                            'spin2win.prize.email',
                            json_encode($lowStockData)
                        );
                }
                
                /**
                 * Check consolation
                 */
                if(!$report->getResult()){
                    $sqlSpinConsolation = $this->_conn->select()
                        ->from(['co' => 'spintowin_consolation'])
                        ->where('is_active', 1)
                        ->where('spin_id = ?', $spinId);
                    $consolationData = $this->_conn->fetchRow($sqlSpinConsolation);
                    if(!empty($consolationData) && (int)$consolationData['consolation_type'] != 0){
                        $sqlCntConsolation = $this->_conn->select()
                        ->from(['report' => 'spintowin_reports'], [])
                        ->columns(new \Zend_Db_Expr('count(*) as total_fail'))
                        ->where('spin_id = ?', $spinId)
                        ->where('customer_id = ?', $customerId)
                        ->where('result = ?', \Branch8\Spin2Win\Model\Config\Source\SegmentType::LOSE_TYPE)
                        ->where('is_apply_consolation = ?', 0);
                        
                        if($spin->getSpinType() == \Branch8\Spin2Win\Model\Config\Source\SpinType::DAILY_X_TIME){
                            $sqlCntConsolation =  $sqlCntConsolation->where('DATE(created_at) = CURDATE()'); 
                        }

                        $totalFail = $this->_conn->fetchOne($sqlCntConsolation);
                        
                        if($totalFail >= $consolationData['consolation_fail_times']){
                            /**
                             * Add consolation
                             */
                            $memberSeq = $this->_customerSession->getCustomer()->getDataModel()->getCustomAttribute('member_seq')->getValue();
                            try{
                                $consolationReturnData = $this->b8AwardHelper->assignConsolation($spin, $consolationData, $report->getId(), $customerId, $memberSeq);
                            } catch(\Exception $e){
                                $segment->setAvailed(new \Zend_Db_Expr('availed -1'));
                                $segment->save();
                                $report->delete();
                                throw new \Exception($e->getMessage());
                            }

                            /**
                              * Reset fail count
                              */
                            $sqlResetFailCount = 'update spintowin_reports set is_apply_consolation = 1 where spin_id='.$spinId.' and customer_id='.$customerId;
                            $this->_conn->query($sqlResetFailCount);

                            /**
                             * Add return data
                             */
                            if($consolationData['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::COUPON_TYPE){
                                $consolationData['coupon'] = $consolationReturnData['coupon'];
                            }
                            if($consolationData['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::VIRTUAL_TYPE){
                                $consolationData['serial_number'] = $consolationReturnData['serial_number'];
                                $consolationData['serial_number_id'] = $consolationReturnData['serial_number_id'];
                            }
                            if($consolationData['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE){
                                $result['total_points'] = $this->hotaiPointHelper->formatPoints((float) $this->hotaiPointHelper->getHotaiPoint(), false, false, false);
                            }
                            $result['consolation'] = $consolationData;
                            
                            /**
                             * Add queue email if get a consolation
                             */
                            
                            $notiData = [
                                'segment_report_id' => $report->getId(),
                                'customer_id' => $customerId,
                                'spin_id' => $spinId,
                                'prize_type' => $consolationData['consolation_type'],
                                'segment_name' => $consolationData['consolation_title']
                            ];
                            $this->addNotification($notiData);
                        }
                    }

                }else{
                    $notiData = [
                        'segment_report_id' => $report->getId(),
                        'customer_id' => $customerId,
                        'spin_id' => $spinId,
                        'prize_type' => $segment->getType(),
                        'segment_name' => $segment->getHeading()
                    ];
                    $this->addNotification($notiData);

                    // $this->checkIfSpinAvailable($spinId);
                }
                $this->_conn->commit();

            } else {
                // $infoModel = $this->infoFactory->create()->load($spin->getId());
                // $infoModel->setStatus(0)->save();
                /**
                 * Scenario 1:
                 *  User has available raffle chances and clicks "GO"
                 *   Title: Raffle Failed
                 *   抽獎失敗
                 *   Message: Today's quota has been reached.
                 *   今日名額已截止
                 */ 
                $msg = __("Today's quota has been reached.");
            }
            
            $chanceDataAfter = $this->b8SpinHelper->getTotalChances($spin->getData(), $customerId);
            $chancesAfter = $chanceDataAfter['total'];
            
            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $this->getResponse()->setBody($this->serializer
                ->serialize(
                    [
                        'success' => $isSuccess,
                        'message' => $msg,
                        'chances' => $chancesAfter,
                        'data' => $result
                    ]
                ));
        } catch (\Exception $e) {
            $this->_conn->rollBack();

            $this->logger->info("Check.php: ".$e->getMessage());
            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $this->getResponse()->setBody($this->serializer
                ->serialize(
                    [
                        'success' => 0,
                        'message' => __('發生錯誤，請重新嘗試。'),
                        // 'message' => __('Something went wrong in getting spin wheel: '.$e->getMessage())
                    ]
                ));
        }
    }

    protected function getNoWinUnlimited($spin){
        $segmentsArray = [];
        $segmentId = 0;
        $sum = 0;
        $result = [];
        $segments = $spin->getSegments()
            ->addFieldToFilter('type', 0)
            ->setOrder('position', 'ASC');
        $i = 0;
        foreach ($segments as $segment) {
            $i++;
            $segmentsArray[$segment->getId()] = $i;
            if ($segment->getLimits()===null
            || ($segment->getLimits()!==null
            && $segment->getAvailed()<$segment->getLimits())) {
                $sum += $segment->getGravity();
                $result[$segment->getId()] = $sum;
            }
        }
        $indx = 0;
        if (!empty($result)) {
            $random = random_int(0, max($result) - 1);
            foreach ($result as $key => $value) {
                if ($random < $value) {
                    $segmentId = $key;
                    $indx = $segmentsArray[$segmentId];
                    break;
                }
            }
        }
        return ['segmentid'=>$segmentId, 'segment'=>$indx];
    }

    public function calculateResult($spin)
    {
        $segmentsArray = [];
        $segmentId = 0;
        $sum = 0;
        $result = [];
        $segments = $spin->getSegments()->setOrder('position', 'ASC');
        $i = 0;
        $segmentArr = [];
        $maxDecimal = 0;
        foreach ($segments as $segment) {
            $segmentArr[] = $segment;
            $gravity = (float)$segment->getGravity();
            $exGravity = explode('.', $gravity);
            if(isset($exGravity[1]) && strlen($exGravity[1]) > $maxDecimal){
                $maxDecimal = strlen($exGravity[1]);
            }
        }
        $xVal = 1;
        for($indx = 1; $indx <= $maxDecimal; ++$indx){
            $xVal = $xVal * 10;
        }
        
        foreach($segmentArr as $_segment){
            $i++;
            $segmentsArray[$_segment->getId()] = $i;
            if ($_segment->getLimits()===null
            || ($_segment->getLimits()!==null
            && $_segment->getAvailed() < $_segment->getLimits())) {
                $sum += $_segment->getGravity()*$xVal;
                $result[$_segment->getId()] = $sum;
            }
        }

        $indx = 0;
        if (!empty($result)) {
            $random = random_int(0, max($result) - 1);
            foreach ($result as $key => $value) {
                if ($random < $value) {
                    $segmentId = $key;
                    $indx = $segmentsArray[$segmentId];
                    break;
                }
            }
        }
        return ['segmentid'=>$segmentId, 'segment'=>$indx];
    }

    protected function addNotification($data){
        if($data['prize_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::LOSE_TYPE){
            return;
        }
        $notification = $this->notificationCollection->create()
        ->addFieldToFilter('is_active', Notification::ACTIVE)
        ->addFieldToFilter('notification_type', 'spin_to_win')
        ->getFirstItem()
        ->getData();
        $storeId = 1;
    // foreach ($listNotification as $key => $notification) {

        // Remove notifications that don't match store conditions
        $listStore = $this->serialize->unserialize($notification['store_view']);
        if (is_array($listStore) && !in_array('0', $listStore) && !in_array($storeId, $listStore)) {
            // unset($listNotification[$key]);
            return;
        }
        $customerId = $this->_customerSession->getCustomerId();
        // Remove notifications that don't match customer group conditions
        $customerGroupId = $this->b8SpinHelper->getCustomerGroupId($customerId);
        $listCustomerGroup = $this->serialize->unserialize($notification['customer_group']);
        if (is_array($listCustomerGroup) && !in_array('0', $listCustomerGroup) && !in_array($customerGroupId, $listCustomerGroup)) {
            // unset($listNotification[$key]);
            return;
        }
        $customerId = $this->_customerSession->getCustomerId();
        $notificationDescription = __('恭喜你中獎! 獲得「%1」', $data['segment_name']);

        unset($notification['created_at']);
        $notification['customer_id'] = $customerId;
        $notification['segment_report_id'] = $data['segment_report_id'];
        $notification['icon'] = $notification['image'];
        $notification['star'] = CustomerNotificationModel::UNSTAR;
        $notification['status'] = CustomerNotificationModel::STATUS_UNREAD;
        $notification['description'] = $notificationDescription;
        $customerNotification = $this->customerNotificationFactory->create();
        $customerNotification->addData($notification);
        $this->customerNotificationResource->save($customerNotification);
    // }
    }

}
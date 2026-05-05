<?php

namespace Branch8\Spin2Win\Controller\Campaign;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Branch8\HotaiPoint\Helper\ApiFlow as ApiFlowHelper;

class Chance extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_conn;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $pageJsonFactory;
    /**
     * @var HotaiPointHelper
     */
    protected HotaiPointHelper $hotaiPointHelper;

    /** @var ApiFlowHelper */
    protected $apiFlowHelper;

    protected $b8SpinHelper;

    protected $apiPointHelper;

    protected $remoteAddress;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory
     * @param \Branch8\Spin2Win\Helper\Data $b8SpinHelper
     * @param \Branch8\HotaiPoint\Helper\Api $apiPointHelper
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param HotaiPointHelper $hotaiPointHelper
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory,
        \Branch8\Spin2Win\Helper\Data $b8SpinHelper,
        \Branch8\HotaiPoint\Helper\Api $apiPointHelper,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        HotaiPointHelper $hotaiPointHelper,
        ApiFlowHelper $apiFlowHelper
    ){
        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->pageJsonFactory = $pageJsonFactory;
        $this->b8SpinHelper = $b8SpinHelper;
        $this->apiPointHelper = $apiPointHelper;
        $this->remoteAddress = $remoteAddress;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->apiFlowHelper = $apiFlowHelper;
    }

    public function execute(){
        $resultPage = $this->pageJsonFactory->create();
        if(!$this->getRequest()->isPost()){
            return $resultPage->setData([
                'error' => true,
                'message_title' => __('Event Not Found'),
                'message' => __("We're sorry, but this event could not be found. Please try again later.")
            ]);
        }

        if(!$this->customerSession->isLoggedIn()){
            return $resultPage->setData([
                'error' => true,
                'need_login' => true,
                'message_title' => __('Please log in to join the event'),
                'message' => __('To fully enjoy this event, please sign in to your account. Thank you')
            ]);
        }

        $spinId = $this->getRequest()->getPost('sid');
        $spinSql = $this->_conn->select()
            ->from(['spin' => 'spintowin_info'])
            ->where('entity_id = ?', $spinId);
        $spinData = $this->_conn->fetchRow($spinSql);

        if(empty($spinData)){
            return $resultPage->setData([
                'error' => true,
                'message_title' => __('Event Not Found'),
                'message' => __("We're sorry, but this event could not be found. Please try again later.")
            ]);
        }

        if(!empty($spinData) && $spinData['status'] == 0){
            return $resultPage->setData([
                'error' => true,
                'disabled' => true,
                'message_title' => __('Event Not Found'),
                'message' => __("We're sorry, but this event could not be found. Please try again later.")
            ]);
        }

        $customerId = $this->customerSession->getCustomer()->getId();
        $customerGroupId = $this->b8SpinHelper->getCustomerGroupId($customerId);
        $spinAllowedGroup = explode(',', (string)$spinData['customergroup_ids']);
        if(!in_array($customerGroupId, $spinAllowedGroup)){
            return $resultPage->setData([
                'error' => true,
                'message_title' => __('Unable to Join This Event'),
                'message' => __("Your membership level does not meet the eligibility requirements for this event. We appreciate your understanding.")
            ]);
        }

        if(!empty($spinData) && $spinData['scheduled']){
            $currentDateTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            $startDate = $spinData['start_date'];
            $endDate = $spinData['end_date'];
            if(strtotime($currentDateTime) > strtotime($endDate)){
                return $resultPage->setData([
                    'error' => true,
                    'expired' => true,
                    'message_title' => __('Event Not Found'),
                    'message' => __("We're sorry, but this event could not be found. Please try again later.")
                ]);
            }
        }

        if(!$spinData['allow_redeem_point']){
            return $resultPage->setData([
                'error' => true,
                'message' => __("This campaign doesn't allow point redemption")
            ]);
        }

        /**
         * Validate if the spin has any prize available
         */
        $segmentsSelect = $this->_conn->select()
            ->from(['segment' => 'spintowin_segments'])
            ->where('spin_id = ?', $spinId);

        $segments = $this->_conn->fetchAll($segmentsSelect);
        $availSegment = 0;
        foreach($segments as $_segment){
            if ($_segment['limits'] === null
            || ($_segment['limits'] !== null
            && $_segment['availed'] < $_segment['limits'])) {
                $availSegment++;
            }
        }

        if(!$availSegment){
            return $resultPage->setData([
                'error' => true,
                'message' => __("Today's quota has been reached.")
            ]);
        }


        $currentDateTime = $this->timezone->date()->format('Y-m-d'). ' 00:00:00';
        $currentDateTimeFormated = $this->timezone->date($currentDateTime)->sub(new \DateInterval('PT8H'));
        $currentDateTimeStr = $this->timezone->convertConfigTimeToUtc($currentDateTimeFormated);
        $sqlCntRedemptionPerday = $this->_conn->select()
            ->from(['redemption' => 'spintowin_redemption'], [])
            ->columns(new \Zend_Db_Expr('count(*) as cnt'))
            ->where('spin_id = ?', $spinId)
            ->where('customer_id = ?', $this->customerSession->getCustomerId())
            ->where('created_at >= "'.$currentDateTimeStr.'"');

        if($this->_conn->fetchOne($sqlCntRedemptionPerday) >= $spinData['redeem_point_to_drawn_limit_per_day']){
            return $resultPage->setData([
                'error' => true,
                'message' => __("You have run out of spins to redeem today.")
            ]);
        }

        /** Only redeem if all redemption used */
        $sqlCntIsUsed = $this->_conn->select()
            ->from(['redemption' => 'spintowin_redemption'], [])
            ->columns(new \Zend_Db_Expr('count(*) as cnt'))
            ->where('spin_id = ?', $spinId)
            ->where('customer_id = ?', $this->customerSession->getCustomerId())
            ->where('is_used = ?', 0);
        if($this->_conn->fetchOne($sqlCntIsUsed) > 0){
            return $resultPage->setData([
                'error' => true,
                'message' => __("You have spins left, spin them all. If you don't see any spins, you can refresh the page.")
            ]);
        }

        $pointExchange = $spinData['point_to_drawn'];

        try{
            $customerId = $this->customerSession->getCustomerId();
            /**Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
             * Call API deduct point
             */
            $taiwanDateObj = new \DateTime();
            $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
            $transTimestamp = $taiwanDateObj->getTimestamp();
            $transSN = 'spin'.$spinId.'_'.$customerId.'_'.$transTimestamp;

            $amt = 0;
            $deductionPoint = $pointExchange;
            $transDesc = substr($spinData['name'], 0, 45).'...';
            $traceNo = $this->deductPoint($customerId, $transSN, $transTimestamp, $amt, $deductionPoint, $transDesc);

            if(!$traceNo){
                return $resultPage->setData([
                    'error' => true,
                    'message' => __("The spin exchange was not successful, please check your point or contact us to have it checked.")
            ]);
            }

            $data =  [
                'redemption_id' => NULL,
                'spin_id' => $spinId,
                'customer_id' => $customerId,
                'created_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date()),
                'point' => $pointExchange,
                'chance_qty' => 1,
                'is_used' => 0,
                'ip' => $this->remoteAddress->getRemoteAddress(),
                'trace_no' => $traceNo
            ];
            $chanceData = $this->b8SpinHelper->getTotalChances($spinData, $customerId);
            $chances = $chanceData['total'];
            $totalPoint = $this->hotaiPointHelper->formatPoints((float) $this->hotaiPointHelper->getHotaiPoint(), false, false, false);
            $this->_conn->insert('spintowin_redemption', $data);
            /** Do not use this way, because the insert command above did not sync to slave DB
             * Get data will be missed
             * $chanceData = $this->b8SpinHelper->getTotalChances($spinData, $customerId);
             * $chances = $chanceData['total'];
            */
            $chances += 1;
            return $resultPage->setData([
                'error' => false,
                'total_chances' => $chances,
                'total_points' => $totalPoint
            ]);

        } catch (\Exception $e){
            return $resultPage->setData([
                'error' => true,
                'message' => __('Invalid request.')
            ]);
        }
    }

    protected function deductPoint($customerId, $transSN, $transTimestamp, $amt, $deductionPoint, $transDesc){

        try{
            $apiFlowResult = $this->apiFlowHelper->deductionFlow(
                $customerId,
                $transSN,
                $transTimestamp,
                $amt,
                $deductionPoint,
                $transDesc
            );

            if (!isset($apiFlowResult['isSuccess']) || $apiFlowResult['isSuccess'] !== true) {
                return false;
            }

            if (empty($apiFlowResult['traceNo'])) {
                return false;
            }

            $traceNo = $apiFlowResult['traceNo'];

            $apiCommitResponse = $this->apiPointHelper->requestApiCommit(
                $customerId,
                $traceNo
            );

            $returnCodeCommit = $this->apiPointHelper->getReturnCodeFromResponse($apiCommitResponse);

            if($returnCodeCommit != $this->apiPointHelper::API_RESPONSE_CODE_SUCCESS){
                return false;
            }

            return $traceNo;
        }catch(\Exception $e){
            return false;
        }
        return false;
    }
}
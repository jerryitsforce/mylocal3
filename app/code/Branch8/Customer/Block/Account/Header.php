<?php

namespace Branch8\Customer\Block\Account;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Magento\Customer\Helper\View;

class Header extends \Magento\Framework\View\Element\Template
{
    protected $_session;

    protected $_groupCollectionFactory;

    private $customerViewHelper;

    private $_orderCollectionFactory;
    private $_timeZone;

    protected $_parentOrderCollectionFactory;

    protected $recentMonthsDuePoints = [];

    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    protected $responseFactory;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderCollectionFactory
     * @param View $customerViewHelper
     * @param ApiHelper $apiHelper
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $session,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderCollectionFactory,
        View $customerViewHelper,
        ApiHelper $apiHelper,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        array $data = []
    ){
        parent::__construct($context, $data);
        $this->_session = $session;
        $this->_groupCollectionFactory = $groupCollectionFactory;
        $this->customerViewHelper = $customerViewHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_timeZone = $timeZone;
        $this->_parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->apiHelper = $apiHelper;
        $this->responseFactory = $responseFactory;
    }

    public function getCustomer(){
        return $this->_session->getCustomer();
    }

    public function getAccountHeader(){
        $customer = $this->getCustomer();
        if(!$customer && !$customer->getId()){
            $redirectURL = 'customer/account/login';
            return $this->_responseFactory->create()->setRedirect($redirectURL)->sendResponse();
        }
        $headerData = [];
        $currentCustomerGroup = $customer->getGroupId();
        $group = $this->_groupCollectionFactory->create()
            ->addFieldToFilter('customer_group_id', $currentCustomerGroup)
            ->getFirstItem();
        $nextLevelId = null;
        if(!$group->getCustomerGroupId()){
            $headerData['account'] = [
                'groupLabel' => '',
                'groupIcon' => ''
            ];
        }else{
            $nextLevelId = $group->getNxtLevel();
            $mediaUrl = $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $headerData['account'] = [
                'groupLabel' => $group->getData('label'),
                'groupIcon' => $mediaUrl.$group->getData('icon')
            ];
        }
        $headerData['account']['fullname'] = $this->customerViewHelper->getCustomerName($customer->getDataModel());
        if(!empty($customer->getData('nickname'))){
            $headerData['account']['fullname'] = $customer->getData('nickname');
        } else {
            $fullname = $headerData['account']['fullname'];
            if (!empty($fullname)) {
                $fullname = trim($fullname);
                $infoLength = mb_strlen($fullname, 'UTF-8');

                if ($infoLength > 1) {
                    $maskedPart = str_repeat('○', 2);
                    $unmaskedPart = mb_substr($fullname, -1, 1, 'UTF-8');
                    $headerData['account']['fullname'] = $maskedPart . $unmaskedPart . ' ';
                }
            }
        }
        //next level info
        //hard code for FE dev
        $headerData['nextLevel'] = $this->getNextLevelInfor($customer->getId(), $nextLevelId);


        return $headerData;
    }

    public function getNextLevelInfor($customerId, $nextLevelId){
        if((int)$nextLevelId == 0){
            return [];
        }
        $nextLevel = $this->_groupCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_group_id', $nextLevelId)
            ->getFirstItem();
        if(!$nextLevel){
            return [];
        }
        $nextLevelData = [];
        $nextLevelData['name'] = $nextLevel->getCustomerGroupCode();
        $nextLevelCondition =  (string)$nextLevel->getConditions();
        if($nextLevelCondition == '' ){
            $nextLevelData['condition'] = [];
        }
        $nextLevelConditionData = json_decode($nextLevelCondition, true);
        $nextLevelData['condition'] = $nextLevelConditionData;

        //get current data for next level
        $periodLevel = $nextLevel->getPeriod();
        if((int)$periodLevel > 0){
            $currentTime = $this->_timeZone->convertConfigTimeToUtc($this->_timeZone->date(), 'Y-m-d 00:00:00');
            $periodFromCalc = $this->_timeZone->date($currentTime)->modify('-'.$periodLevel.' months');
            $periodFrom = $this->_timeZone->convertConfigTimeToUtc($periodFromCalc, 'Y-m-d 00:00:00');
            $periodTo = $currentTime;
            $period = ['from' => $periodFrom, 'to' => $periodTo];
            $orders = $this->_parentOrderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('created_at', ['gteq' => $period['from']])
                ->addFieldToFilter('created_at', ['lteq' => $period['to']])
                ->addFieldToFilter('status', ['eq' => $this->_scopeConfig->getValue(\Branch8\Customer\Helper\Group::CONFIG_FINAL_ORDER_SUCCESS_STATUS)]);
            $nextLevelData['currentData']['numOfOrder'] = $orders->getSize();
            if(isset($nextLevelData['condition']['condition_num_orders']) && $nextLevelData['condition']['condition_num_orders'] != 0){
                $nextLevelData['currentData']['numOfOrderPercent'] = round($orders->getSize()/  $nextLevelData['condition']['condition_num_orders'] * 100, 2);
                if ($nextLevelData['currentData']['numOfOrderPercent'] > 100)
                    $nextLevelData['currentData']['numOfOrderPercent'] = 100;
            }else{
                $nextLevelData['currentData']['numOfOrderPercent'] = 100;//percent
            }


            $ordersCollection = $this->_orderCollectionFactory->create()
                ->addExpressionFieldToSelect('total_value', 'sum(grand_total)', 'grand_total');
            $select = $ordersCollection->getSelect();
            $select->joinLeft(
                ['pco' => 'sales_parent_order_children'],
                'pco.children_id = main_table.entity_id',
                'parent_id');
            $select->joinLeft(['pro' => 'sales_parent_order_detail'], 'pro.parent_id = pco.parent_id', 'customer_id');
            $select->where('pro.customer_id = '.(int)$customerId.'
                        and pro.status="'.$this->_scopeConfig->getValue(\Branch8\Customer\Helper\Group::CONFIG_FINAL_ORDER_SUCCESS_STATUS).'"
                        and pro.created_at >="'.$period['from'].'" and pro.created_at <="'.$period['to'].'"');
            $select->group('pro.customer_id');
            $data = $ordersCollection->getFirstItem();
            $value = (int)$data->getData('total_value');
            $nextLevelData['currentData']['totalValue'] = $value;
            if(isset($nextLevelData['condition']['condition_total_value']) && $nextLevelData['condition']['condition_total_value'] != 0){
                $nextLevelData['currentData']['totalValuePercent'] = round($nextLevelData['currentData']['totalValue']/  $nextLevelData['condition']['condition_total_value'] * 100, 2);
                if ($nextLevelData['currentData']['totalValuePercent'] > 100)
                    $nextLevelData['currentData']['totalValuePercent'] = 100;
            }else{
                $nextLevelData['currentData']['totalValuePercent'] = 100;//percent
            }
        } else {
            return [];
        }

        return $nextLevelData;
    }

    /**
     * Get the url to order history
     *
     * @return string
     */
    public function getOrderLink()
    {
        return $this->_urlBuilder->getUrl('sales/parentOrder/history');
    }

    /**
     * Get the url to wishlist
     *
     * @return string
     */
    public function getWishlistLink()
    {
        return $this->_urlBuilder->getUrl('wishlist');
    }

    /**
     * Get the url to notification
     *
     * @return string
     */
    public function getNotificationLink()
    {
        return $this->_urlBuilder->getUrl('notification/customer/notification');
    }

    /**
     * Get the url to customer account
     *
     * @return string
     */
    public function getCustomerLink()
    {
        return $this->_urlBuilder->getUrl('customer/account');
    }

    /**
     * Get the url to address book
     *
     * @return string
     */
    public function getAddressBookLink()
    {
        return $this->_urlBuilder->getUrl('customer/address');
    }

    /**
     * Get the url to hotai pay
     *
     * @return string
     */
    public function getHotaiPaymentLink()
    {
        return $this->_urlBuilder->getUrl('hotaipay/creditcard/listaction/');
    }

    /**
     * Get the url to member browsing history
     *
     * @return string
     */
    public function getMemberBrowsingHistoryLink()
    {
        return $this->_urlBuilder->getUrl('member/browsinghistory/');
    }

    /**
     * Get the url to gift box listing
     *
     * @return string
     */
    public function getGiftBoxLink()
    {
        return $this->_urlBuilder->getUrl('gift-order/giftbox/listing');
    }

    /**
     * Get the url to member level
     *
     * @return string
     */
    public function getMemberLevelLink()
    {
        return $this->_urlBuilder->getUrl('member/level/index');
    }


    public function getClosetExpiringPoints()
    {
        $result = $this->getRecentMonthsDuePoints();

        if (empty($result)) {
            return [];
        }
        $currentDate = new \DateTime('now');
        $datePoints = [];

        foreach ($result as $item) {
            $dateStr = $item['date'];
            $date = $this->createDateFromFormat($dateStr);

            if ($date >= $currentDate) {
                if (!isset($datePoints[$dateStr]['point'])) {
                    $datePoints[$dateStr]['point'] = 0;
                    $datePoints[$dateStr]['datetime'] = $date;
                    $datePoints[$dateStr]['timestamp'] = $date->getTimestamp();
                }
                $datePoints[$dateStr]['point'] += $item['point'];
            }
        }

        if (empty($datePoints)) {
            return [];
        }

        uasort($datePoints, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        $info = reset($datePoints);

        return [
            'date' => $info['datetime']->format('Y/m/d'),
            'point' => $info['point']
        ];
    }

    public function getRecentMonthsDuePoints()
    {
        if (empty($this->recentMonthsDuePoints)) {
            $this->recentMonthsDuePoints = $this->apiHelper->getRecentMonthsDuePointsByCustomerId($this->_session->getCustomerId());
        }

        return $this->recentMonthsDuePoints;
    }

    public function createDateFromFormat(string $date, $format = 'Ymd')
    {
        return \DateTime::createFromFormat($format, $date);
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfigValue($path)
    {
        return $this->_scopeConfig->getValue($path);
    }
}

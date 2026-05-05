<?php
namespace Branch8\Customer\Controller\Ajax;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\HotaiPay\Model\Payment\Update;
use Branch8\HotaiPay\Service\Api;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Setup\Exception;

class ExpirePoint extends Action
{
    const LOG_PATH = 'Sales/recheckPendingPayment';

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonResultFactory;

    protected $recentMonthsDuePoints = [];

    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var HotaiPointHelper
     */
    protected $hotaiPointHelper;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory */
    protected $parentOrderCollectionFactory;

    /** @var \Magento\Sales\Model\Order\Config*/
    protected $_orderConfig;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection*/
    protected $orders;

    /** @var \Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface $parentOrderManagement */
    private $parentOrderManagement;

    /** @var \Magento\Framework\App\Http\Context */
    protected $httpContext;

    protected $totalPage = 0;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var mixed $hotaiShippingHelper */
    protected $hotaiShippingHelper;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $subOrderCollectionFactory */
    protected $subOrderCollectionFactory;

    /** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
    protected $resourceConnection;

    /** @var \Branch8\HotaiPay\Model\Payment\Update $update */
    protected $update;

    /** @var \Branch8\HotaiPay\Service\Api $api */
    protected $api;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /**
     * Widget constructor.
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param ApiHelper $apiHelper
     * @param CustomerSession $customerSession
     * @param HotaiPointHelper $hotaiPointHelper
     * @param CollectionFactory $parentOrderCollectionFactory
     * @param Config $orderConfig
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param OrderCollectionFactory $subOrderCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param Update $update
     * @param Api $api
     * @param HotaiCoreCommon $hotaiCoreCommon
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        ApiHelper $apiHelper,
        CustomerSession $customerSession,
        HotaiPointHelper $hotaiPointHelper,
        CollectionFactory $parentOrderCollectionFactory,
        Config $orderConfig,
        ParentOrderManagementInterface $parentOrderManagement,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderCollectionFactory $subOrderCollectionFactory,
        ResourceConnection $resourceConnection,
        Update $update,
        Api $api,
        HotaiCoreCommon $hotaiCoreCommon
    ){
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->apiHelper = $apiHelper;
        $this->customerSession = $customerSession;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->update = $update;
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->_orderConfig = $orderConfig;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->subOrderCollectionFactory = $subOrderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->api = $api;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $data = ['point' => '', 'expire' => ''];
        if ($this->getRequest()->isAjax()) {
            $content = '';
            try {
                $closetExpirePoint = $this->getClosetExpiringPoints();
                if (!empty($closetExpirePoint)) {
                    $content = __("將有%1點於 %2 到期", $this->hotaiPointHelper->formatPoints($closetExpirePoint['point'], false, false, false), $closetExpirePoint['date']);
                }
            } catch (\Exception $e) {
            }
            $data['point'] = $this->hotaiPointHelper->formatPoints($this->hotaiPointHelper->getHotaiPoint(), true);
            $data['expire'] = $content;
        }
        $this->processPaymentCheck();
        $result->setData($data);

        return $result;
    }

    /**
     * get closet expire time points
     */
    protected function getClosetExpiringPoints()
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

    /**
     * get recent months due point
     */
    public function getRecentMonthsDuePoints()
    {
        $customerId = $this->customerSession->getCustomerId();

        if (empty($this->recentMonthsDuePoints) && !empty($customerId)) {
            $this->recentMonthsDuePoints = $this->apiHelper->getRecentMonthsDuePointsByCustomerId($customerId);
        }

        return $this->recentMonthsDuePoints;
    }

    /**
     * format date
     */
    public function createDateFromFormat(string $date, $format = 'Ymd')
    {
        return \DateTime::createFromFormat($format, $date);
    }

    /*
     * process payment check
     */
    protected function processPaymentCheck()
    {
        try {
            $customerId = $this->customerSession->getCustomerId();

            if (!$customerId) {
                return false;
            }

            $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
            $to = date('Y-m-d H:i:s');

            $collection = $this->parentOrderCollectionFactory->create()
                ->joinDetail(
                    [
                        '*',
                    ]
                )->addCustomerFilter(
                    $customerId
                )->addFieldToFilter(
                    'detail.status',
                    HotaiStatus::STATUS_PENDING_PAYMENT
                )->addFieldToFilter(
                    'detail.payment_method',
                    \Branch8\HotaiPay\Model\Payment\HotaiPay::CODE
                );
            // ->addFieldToFilter(
            //     'created_at', array('from' => $yesterday, 'to' => $to))
            //    ;

            foreach ($collection as $parentOrder) {
                //If there is no increment id then return
                if (!$parentOrder->getIncrementId()) {
                    continue;
                }

                //log parent order id
                $this->writeLog(
                    "--- Get ParentOrderId: " . $parentOrder->getIncrementId() . " ---",
                    self::LOG_PATH
                );

                $checkPayment = $this->api->payment->inquiry(['orderId' => $parentOrder->getIncrementId()]);

                if ($checkPayment && $checkPayment['success'] == true) {

                    //If Payment Success then update status
                    if ($this->canUpdateToProcessing($checkPayment)) {
                        $this->update->setStatusAfterCheckoutSuccess((int) $parentOrder->getParentId());
                        $this->writeLog(
                            "Update ParentOrderId: " . $parentOrder->getIncrementId() . " to processing. ",
                            self::LOG_PATH
                        );
                        return;
                    }

                }

                $this->writeLog(
                    "--- Payment failed. There is nothing to update. ---",
                    self::LOG_PATH
                );
            }
        } catch (Exception $e) {
            $this->writeLog(
                $e->getMessage(),
                self::LOG_PATH
            );

        }
    }

    /**
     * canUpdateToProcessing
     *
     * @param  array $checkPayment
     * @return bool
     */
    protected function canUpdateToProcessing($checkPayment){

        //成功交易
        if ($checkPayment['data']['Status'] == "0" && $checkPayment['data']['StatusDesc'] == "SUCCESS"){
            return true;
        }

        //該筆訂單編號已經做過交易，不接受重複交易
        if ($checkPayment['data']['Status'] == "10"
            && $checkPayment['data']['StatusCode'] == "E9998"
            && $checkPayment['data']['AuthAmt']){
            return true;
        }

        return false;

    }

    protected function writeLog($message, $path = self::LOG_PATH, $fileName = ""){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'recheck_pending_payment')){
            $this->hotaiCoreCommon->writeLog(
                $message,
                $path,
                $fileName
            );
        }
    }
}

<?php

namespace Branch8\HotaiPay\Helper\Order;

use Branch8\HotaiPay\Helper\ErrorMsg;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\HotaiPay\Logger\Payment\Logger;
use Branch8\HotaiPay\Model\Api\Payment;
use Branch8\HotaiPay\Model\MessageFactory;
use Branch8\HotaiPay\Model\MessageRepository;
use Branch8\HotaiPay\Model\ParentOrderPayment;
use Branch8\HotaiPay\Model\ParentOrderPaymentFactory;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\Collection as ParentOrderCollection;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use \Webkul\Mpsplitorder\Model\MpsplitorderFactory;

class OrderManagement
{

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime $date */
    protected $date;

    /** @var \Webkul\Mpsplitorder\Model\MpsplitorderFactory $mpsplitorderFactory */
    protected $mpsplitorderFactory;

    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    protected $checkoutSession;

    /** @var \Magento\Framework\UrlInterface $urlBuilder */
    protected $urlBuilder;

    /** @var \Magento\Framework\Session\SessionManager $coreSession */
    protected $coreSession;

    /** @var \Branch8\HotaiPay\Model\Api\Payment $payment */
    protected $payment;

    /** @var \Branch8\HotaiPay\Logger\Payment\Logger $logger */
    protected $logger;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory $parentOrderDetail */
    protected $parentOrderDetail;

    /** @var \Branch8\HotaiPay\Model\ParentOrderPaymentFactory $parentOrderPayment */
    protected $parentOrderPayment;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\Collection $parentOrderCollection */
    protected $parentOrderCollection;

    /** @var \Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface $parentOrderManagement */
    protected $parentOrderManagement;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderInterface */
    protected $parentOrderInterface;

    private HotaiPayLogHelper $hotaiPayLogHelper;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository */
    protected $orderRepository;

    /** @var mixed $messageFactory */
    protected $messageFactory;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        SessionManager $coreSession,
        MpsplitorderFactory $mpsplitorderFactory,
        DateTime $date,
        Session $checkoutSession,
        UrlInterface $urlBuilder,
        Payment $payment,
        Logger $logger,
        ParentOrderDetailFactory $parentOrderDetail,
        ParentOrderPaymentFactory $parentOrderPayment,
        ParentOrderManagementInterface $parentOrderManagement,
        ParentOrderFactory $parentOrderInterface,
        ParentOrderCollection $parentOrderCollection,
        HotaiPayLogHelper $hotaiPayLogHelper,
        OrderRepositoryInterface $orderRepository,
        MessageFactory $messageFactory

    ) {
        $this->coreSession = $coreSession;
        $this->checkoutSession = $checkoutSession;
        $this->mpsplitorderFactory = $mpsplitorderFactory;
        $this->date = $date;
        $this->urlBuilder = $urlBuilder;
        $this->payment = $payment;
        $this->parentOrderDetail = $parentOrderDetail;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->logger = $logger;
        $this->parentOrderPayment = $parentOrderPayment;
        $this->parentOrderCollection = $parentOrderCollection;
        $this->parentOrderInterface = $parentOrderInterface;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
        $this->orderRepository = $orderRepository;
        $this->messageFactory = $messageFactory;
    }

    /**
     * getOrderTxn 取得 TXN
     *
     * @return void
     */
    public function getOrderTxn()
    {
        $parentOrder = $this->saveOrderPaymentInfo();

        //encrypt $parentOrder['ParentOrderId'] for check later
        $encryptParentOrderId = urlencode(base64_encode($parentOrder['ParentOrderId']));

        $requestPayload = [
            "TokenID" => (int) $parentOrder['TokenID'],
            "Lidm" => $parentOrder['Lidm'],
            "PurchAmt" => (int) $parentOrder['PurchAmt'],
            "TxType" => "0",
            "AutoCap" => 1,
            "RedirectURL" => $this->urlBuilder->getUrl('hotaipay/payment/response/po/' . $encryptParentOrderId . "/"),
        ];

        try {
            $response = $this->payment->checkout($requestPayload);
            $this->saveTxnResponse($response, $parentOrder['ParentOrder']);
        } catch (\Exception $e) {

            $this->hotaiPayLogHelper->writeLog("[ParentOrderId] ". $parentOrder['ParentOrderId']." - [Error] ".$e->getMessage(), __CLASS__);

            throw new LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * saveTxnResponse 儲存 TXN
     *
     * @param  mixed $response
     * @param  mixed $parentOrder
     * @return void
     */
    private function saveTxnResponse($response, $parentOrder)
    {

        $this->checkoutSession->setData('successPayment', false);
        $parentId = $parentOrder->getParentId();

        // 伺服器沒有回應
        if (!$response) {
            $this->checkoutSession->setData('errorMsg', ErrorMsg::SSL_CONNECT_ERROR);
            $this->hotaiPayLogHelper->writeLog('[TxnResponse] Cannot Get Response From Server.', __CLASS__);

            $this->writeSubOrderComment($parentId, '[TxnResponse] Cannot Get Response From Server.');
            return;
        }

        // 錯誤訊息 Error Messages
        if (is_string($response)) {
            $parentOrder
                ->setGetTxnErrResult($response)
                ->save();

            $this->checkoutSession->setData('errorMsg', json_decode($response, true));

            $this->hotaiPayLogHelper->writeLog('[TxnResponse] ' . $response, __CLASS__);

            $this->writeSubOrderComment($parentId, '[TxnResponse] ' . $response);
            return;
        }

        // write log
        $this->hotaiPayLogHelper->writeLog('[TxnResponse] ' . json_encode($response, JSON_UNESCAPED_UNICODE), __CLASS__);

        // 無法正確取得 TXN
        if ($response['success'] == false) {
            $parentOrder
                ->setGetTxnErrResult(json_encode($response, JSON_UNESCAPED_UNICODE))
                ->save();
            $this->checkoutSession->setData('errorMsg', $response);

            $this->writeSubOrderComment($parentId, '[TxnResponse] ' . json_encode($response, JSON_UNESCAPED_UNICODE));
            return;
        }

        $parentOrder
            ->setMac($response['data']['MAC'])
            ->setTxn($response['data']['TXN'])
            ->setReqjsonpwd($response['data']['reqjsonpwd'])
            ->save();

        $this->checkoutSession->setData('Mac', $response['data']['MAC']);
        $this->checkoutSession->setData('reqjsonpwd', $response['data']['reqjsonpwd']);
        $this->checkoutSession->setData('successPayment', true);
    }

    /**
     * getIsHt
     *
     * @param  mixed $data
     * @return bool
     */
    private function getIsHt($affinityCode): bool
    {

        $htCode = [8686, 8687, 8688, 8689, 8690];
        return in_array((int) $affinityCode, $htCode) ? true : false;
    }

    /**
     * saveOrderPaymentInfo 儲存 Payment Info
     *
     * @return array
     */
    public function saveOrderPaymentInfo()
    {
        try {
            $orderId = $this->checkoutSession->getLastOrderId();
            $parentOrder = $this->getMpsplitorderByLastOrderId($orderId);

            if (!$parentOrder->getIndexId()) {
                throw new Exception(__('Cannot find parent order'));
            }

            $parentOrderDetail = $this->getParentOrderDetailByParentId($parentOrder->getIndexId());
            $parentOrderIncrementId = $parentOrderDetail->getIncrementId();
            $parentOrderPayment = $this->getParentOrderPaymentByParentId($parentOrder->getIndexId());
            $grandTotal = $this->getGrandTotalByParentId($parentOrder->getIndexId());

            //Get Credit Card Data
            $ccData = $this->checkoutSession->getData('ccData');
            $ccLast4 = (isset($ccData->CardNoMask)) ? substr($ccData->CardNoMask, -4) : '';

            $this->checkoutSession->setData('parentOrderId', $parentOrder->getIndexId());
            $this->checkoutSession->setData('childOrderIds', $parentOrder->getOrderIds());

            //Setup Parent Order Payment Table
            $parentOrderPayment
                ->setCcTokenId($ccData->Id)
                ->setCcType($ccData->CardType)
                ->setCcOwner($ccData->MemberOneID)
                ->setCcLast4($ccLast4);
            
            if(isset($ccData->AffinityCode) && $this->getIsHt($ccData->AffinityCode)){
                $parentOrderPayment->setCoBranded(__("和泰聯名卡 + ".$ccData->CardType));
            }

            if (isset($ccData->BinInfo)) {
                $ccBinInfo = (object) $ccData->BinInfo;
                $parentOrderPayment
                    ->setBinInfoCode($ccBinInfo->Code);
            }

            $parentOrderPayment->save();

        } catch (\Exception $e) {

            $this->hotaiPayLogHelper->writeLog('[Error-saveOrderPaymentInfo] '. $e->getMessage(), __CLASS__);

            throw new Exception(__('Please Relogin And Repay.'));
        }

        return [
            "TokenID" => $ccData->Id,
            "Lidm" => $parentOrderIncrementId,
            "PurchAmt" => $grandTotal,
            "ParentOrder" => $parentOrderPayment,
            "ParentOrderId" => $parentOrder->getIndexId(),
        ];
    }

    /**
     * getMpsplitorderByLastOrderId
     *
     * @param  mixed $lastOrderId
     * @return \Webkul\Mpsplitorder\Model\Mpsplitorder
     */
    public function getMpsplitorderByLastOrderId($lastOrderId)
    {
        return $this->mpsplitorderFactory->create()->load($lastOrderId, 'last_order_id');
    }

    /**
     * getMpsplitorderByIndexId
     *
     * @param  mixed $indexId
     * @return \Webkul\Mpsplitorder\Model\Mpsplitorder
     */
    public function getMpsplitorderByIndexId($indexId)
    {
        return $this->mpsplitorderFactory->create()->load($indexId, 'index_id');
    }

    /**
     * getParentOrderByParentId
     *
     * @param int $parentOrderId
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderInterface
     */
    public function getParentOrderByParentId($parentOrderId)
    {
        return $this->parentOrderInterface->create()->load($parentOrderId, 'index_id');
    }

    /**
     * getParentOrderDetailByParentId
     *
     * @param  mixed $parentId
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail
     */
    public function getParentOrderDetailByParentId($parentId)
    {
        return $this->parentOrderDetail->create()->load($parentId, ParentOrderDetail::PARENT_ID);
    }

    /**
     * getParentOrderPaymentByParentId
     *
     * @param  mixed $parentId
     * @return \Branch8\HotaiPay\Model\ParentOrderPayment
     */
    public function getParentOrderPaymentByParentId($parentId)
    {
        return $this->parentOrderPayment->create()->load($parentId, ParentOrderPayment::PARENT_ID);
    }

    /**
     * getPaymentInquiryList | 查詢訂單是否完成
     *
     */
    public function getPaymentInquiryList()
    {
        $to = date("Y-m-d H:i:s"); // current date
        $from = strtotime('-5 minute', strtotime($to));
        $from = date('Y-m-d H:i:s', $from);

        $collection = $this->parentOrderCollection->addFieldToFilter('created_at', array('from' => $from));

        return $collection;
    }

    /**
     * createMpsplitorderFactoryRecord | 創建一筆 mps order record
     *
     * @param  mixed $orderId
     * @return void
     */
    public function createMpsplitorderFactoryRecord($orderId)
    {
        $splitOrderCollection = $this->mpsplitorderFactory->create();
        $splitOrderCollection->setOrderIds($orderId);
        $splitOrderCollection->setLastOrderId($orderId);
        $splitOrderCollection->setPaymentStatus(0);
        $splitOrderCollection->save();
    }

    /**
     * getGrandTotal
     *
     * @param  int $parentOrder
     * @return void | int
     */
    public function getGrandTotalByParentId(int $parentOrderId)
    {
        $parentOrder = $this->parentOrderInterface->create()->load($parentOrderId, 'index_id');

        try {
            $totals = $this->parentOrderManagement->getTotals($parentOrder);
            foreach ($totals as $total) {
                if ($total->getCode() === 'grand_total') {
                    return (int) $total->getValue();
                }
            }
            return 0;
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);

            return 0;
        }
    }

    public function getPaymentMac()
    {
        $orderId = $this->checkoutSession->getLastOrderId();

        try {
            $parentOrder = $this->getMpsplitorderByLastOrderId($orderId);
            $parentOrderPayment = $this->getParentOrderPaymentByParentId($parentOrder->getIndexId());
            return $parentOrderPayment->getMac();
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }
    }

    public function writeSubOrderComment($parentId, $comment)
    {
        try {
            $parentOrder = $this->getParentOrderByParentId($parentId);
            $childOrderIds = explode(',', $parentOrder->getOrderIds());
            foreach ($childOrderIds as $childOrderId) {
                $order = $this->orderRepository->get($childOrderId);
                $order
                    ->addStatusHistoryComment(
                        __($comment))
                    ->save();
            }
        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }

    }

    /**
     * isFraudOrder
     *
     * @param  string $statusCode
     * @param  string $errorCode
     * @return bool
     */
    public function isFraudOrder($statusCode, $errorCode)
    {
        if (!$errorCode || !$statusCode) {
            return false;
        }

        $record = $this->messageFactory->create()
            ->addFieldToFilter(
                'type',
                MessageRepository::TYPE_CHECKOUT
            )->addFieldToFilter(
            'status',
            $statusCode
        )->addFieldToFilter(
            'error_code',
            $errorCode
        )->addFieldToFilter(
            'is_fraud',
            "1"
        );

        if ($record->getSize() > 0) {
            return true;
        }

        return false;

    }
    
    /**
     * getParentOrderIncrementId
     *
     * @return string
     */
    public function getParentOrderIncrementId(){
        try {
            $orderId = $this->checkoutSession->getLastOrderId();

            //Get Parent Order Id
            $parentOrder = $this->getMpsplitorderByLastOrderId($orderId);
            $parentOrderDetail = $this->getParentOrderDetailByParentId($parentOrder->getIndexId());
            
            return $parentOrderDetail->getIncrementId();

        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $e->getMessage(), __CLASS__);
            return '';
        }
    }
}

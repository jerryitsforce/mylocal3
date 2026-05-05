<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Email as EmailHelper;
use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Helper\Log as HopesLog;
use Branch8\Hopes\Helper\Response\Message;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Rma\Helper\Data as RmaHelper;
use Branch8\Rma\Helper\RmaRecord;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use \Branch8\Refund\Helper\CreateCreditMemo;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;

class UpdateShipmentPPSManagement extends AbstractModel implements \Branch8\Hopes\Api\UpdateShipmentPPSManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'UpdateShipmentPPSManagement';

    const STORE_TYPE = [
        "011" => "作業錯誤",
        "012" => "車輛故障",
        "013" => "天候不佳",
        "014" => "道路中斷",
        "015" => "門市停業中",
        "016" => "物流中心暫報缺",
        "017" => "門市暫報缺",
        "019" => "取件貨態異常協尋中",
        "021" => "門市驗收異常",
        "022" => "離島準備配送",
        "101" => "門市配達",
        "201" => "EC 收退",
        "204" => "異常收退",
        "303" => "取件遺失",
    ];

    const FAILED_DELIVERY_STORE_CODE = [
        "011", "012", "013", "014", "015", "016",
        "017", "019", "021", "201", "204", "303",
    ];

    /** @var \Magento\Framework\Webapi\Rest\Request $request */
    public $request;

    /** @var Branch8\Hopes\Helper\Logger $logger */
    private $logger;

    /** @var /Branch8\Hopes\Helper\Logger $loggerhelper */
    private $loggerhelper;

    /** @var array $result */
    public $result = [];

    /** @var \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory */
    protected $trackFactory;

    /** @var  \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory */
    protected $trackingCollection;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /** @var EmailHelper */
    protected $emailHelper;

    /** @var \Branch8\Refund\Helper\CreateCreditMemo $createCreditMemo */
    protected $createCreditMemo;

    /** @var \Branch8\Rma\Helper\Data $rmaHelper */
    protected $rmaHelper;

    /** @var \Branch8\Rma\Helper\RmaRecord $rmaRecord */
    protected $rmaRecord;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        Response $response,
        TrackCollectionFactory $collectionFactory,
        UpdateOrderStatus $updateOrderStatus,
        EmailHelper $emailHelper,
        CreateCreditMemo $createCreditMemo,
        RmaHelper $rmaHelper,
        RmaRecord $rmaRecord,
        HopesLog $hopesLog
    ) {
        $this->trackingCollection = $collectionFactory;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->emailHelper = $emailHelper;
        $this->logger = $logger;
        $this->createCreditMemo = $createCreditMemo;
        $this->rmaHelper = $rmaHelper;
        $this->rmaRecord = $rmaRecord;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdateShipmentPPS()
    {

        $this->hopesLog->log(self::LOG_TYPE, '[Start] UpdateShipmentPPS Start');

        $shipmentData = $this->request->getBodyParams();

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($shipmentData, JSON_UNESCAPED_UNICODE));

        try {
            foreach ($shipmentData as $trackingNumber => $data) {
                $type = $data['StoreType'];
                $data['Remark'] = self::STORE_TYPE[$type] ?? '';

                try {
                    $tracking = $this->trackingCollection->create()
                        ->addFieldToFilter(
                            ShipmentTrackInterface::TRACK_NUMBER,
                            $trackingNumber
                        );
                    $tracking = $tracking->getLastItem();
                    $shipment = $tracking->getShipment();
                    $shipment->addComment(json_encode($data, JSON_UNESCAPED_UNICODE));
                    $shipment->save();

                    // 配達門市 Arrived Store
                    if ($type == '101') {
                        $tracking->setArrivalDate($data['StoreDate']);
                        $tracking->save();
                        $this->updateItemFlowStatus($shipment, Status::STATUS_ARRIVED);
                        // Send email notification using the helper
                        $this->emailHelper->sendStorePickupEmail($shipment, $trackingNumber, $data);
                    }

                    // 失敗配送 Failed Delivery
                    if (in_array($type, self::FAILED_DELIVERY_STORE_CODE)) {
                        $tracking->save();

                        $memo = $this->createCreditMemo($shipment, $trackingNumber);

                        if (!isset($memo['memo_id'])) {
                            $this->hopesLog->log(self::LOG_TYPE, '[Tracking Number] ' . $trackingNumber . ' -- Failed To create creditmemo.');

                            $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_CREDITMEMO);
                            continue;
                        }

                        $order = $this->rmaHelper->getOrder($memo['order_id']);
                        $customerIdentifier = $order->getEcpayInvoiceCustomerIdentifier();

                        if ($customerIdentifier) {
                            $this->rmaRecord->createByPPSCancellation($order, $memo);
                        }

                        $this->updateItemFlowStatus($shipment, Status::STATUS_FAILED_DELIVERY);
                        $this->rmaHelper->updateItemRefundColumnAfterIssueCreditMemo(
                            $memo['memo_id'],
                            Status::STATUS_FAILED_DELIVERY
                        );

                    }

                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
                    $this->hopesLog->log(self::LOG_TYPE, '[Tracking Number] ' . $trackingNumber . ' -- Updated.');
                } catch (\Exception $e) {
                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_SHIPMENT);
                    $this->hopesLog->log(self::LOG_TYPE, '[Tracking Number] ' . $trackingNumber . ' -- ' . $e->getMessage());
                    $this->hopesLog->logException(self::LOG_TYPE, $e, ['tracking_number' => $trackingNumber, 'data' => $data]);
                }
            }

        } catch (\Exception $e) {
            $this->hopesLog->logException(self::LOG_TYPE, $e);
            $this->result = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_SHIPMENT);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));

        $this->setResponse($this->result);
    }

    /**
     * updateItemFlowStatus
     *
     * @param  mixed $shipment
     * @param  string $storeDate
     * @return void
     */
    private function updateItemFlowStatus($shipment, $status)
    {
        $order = $shipment->getOrder();
        foreach ($shipment->getItemsCollection() as $item) {
            $item = $item->getOrderItem();

            if ($item->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
                continue;
            }

            $item->setFlowStatus($status);
            $item->save();
            $this->updateOrderStatus->addItemStatusRecord($order->getId(), $item, $status);
        }
    }

    public function createCreditMemo($shipment, $trackingNumber)
    {
        $items = [];
        $rmaItems = [];
        $totalPoint = 0;
        $shippingAmount = 0;
        $creditmemoItemQty = 0;

        foreach ($shipment->getItemsCollection() as $item) {
            $itemData = $item->getOrderItem();
            
            if ($itemData->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
                continue;
            }

            $items[$itemData->getItemId()] = ['qty' => $item->getQty()];
            $rmaItems[] = $itemData->getItemId();

            $totalPoint = $totalPoint + (int) $itemData->getRowTotalPointUsed();
            $creditmemoItemQty = $creditmemoItemQty + (int) $item->getQty();
            $orderId = $itemData->getOrderId();
        }

        $order = $this->rmaHelper->getOrder($orderId);

        //If all need to issue creditmemo, then
        if (((int) $order->getTotalQtyOrdered() -
            (int) $this->getRefundedItemsNum($order) -
            (int) $creditmemoItemQty) == 0) {

            $shippingAmount =
            (int) $order->getShippingInclTax() -
            (int) $order->getShippingRefunded() -
            (int) $order->getShippingTaxRefunded();

            $order->setState(State::STATE_CANCELED);
            $order->setStatus(Status::STATUS_CANCELED);
            $comment = __('Order Canceled by PPS Api');
            $order->addCommentToStatusHistory($comment);
            $order->save();
        }

        $data = [
            'items' => $items,
            'order_id' => $orderId,
            'shipping_amount' => $shippingAmount,
            'negative' => $totalPoint,
            'invoice_id' => $order->getInvoiceCollection()->getLastItem()->getId(),
        ];

        $result = $this->rmaHelper->createPPSCreditMemo($data);

        $result['order_id'] = $orderId;
        $result['items'] = $rmaItems;

        return $result;
    }

    protected function getRefundedItemsNum($order)
    {
        $qty = 0;
        foreach ($order->getAllVisibleItems() as $orderOriItems) {
            $qty = $qty + $orderOriItems->getQtyRefunded();
        }

        return $qty;
    }
}

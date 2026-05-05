<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Helper\Log as HopesLog;
use Branch8\Hopes\Helper\Response\Message;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Helper\Status as RmaStatus;
use Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping\CollectionFactory as RmaShippingRecordFactory;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use \Branch8\Rma\Helper\Data;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;

class TCatSodManagement extends AbstractModel implements \Branch8\Hopes\Api\TCatSodManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'TCatSodManagement';

    const TYPE_FORMAL_FLOW = 1;
    const TYPE_REVERSE_FLOW = 2;

    const GOOD_STATUS = [
        "ARRIVED" => "00003",
        "EXAMINE" => "00017",
        "SHIPPING" => "00006",
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

    /** @var \Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping\CollectionFactory $rmaShippingRecordFactory */
    protected $rmaShippingRecordFactory;

    /**　@var \Branch8\Rma\Helper\Status $status */
    protected $status;

    /** @var \Branch8\Rma\Helper\Data $rmaHelper */
    protected $rmaHelper;

    /** @var \Branch8\Rma\Helper\RmaActions $rmaActions */
    protected $rmaActions;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

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
        RmaShippingRecordFactory $rmaShippingRecordFactory,
        Data $rmaHelper,
        RmaStatus $status,
        RmaActions $rmaActions,
        UpdateOrderStatus $updateOrderStatus,
        HopesLog $hopesLog
    ) {
        $this->trackingCollection = $collectionFactory;
        $this->rmaShippingRecordFactory = $rmaShippingRecordFactory;
        $this->rmaHelper = $rmaHelper;
        $this->status = $status;
        $this->rmaActions = $rmaActions;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->logger = $logger;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function postTCatSod()
    {

        $this->hopesLog->log(self::LOG_TYPE, '[Start] TCatSod Start');

        $shipmentData = $this->request->getBodyParams();

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($shipmentData, JSON_UNESCAPED_UNICODE));

        try {
            foreach ($shipmentData as $trackingNumber => $data) {

                try {
                    $record = $this->findRecordsByTrackingNumber($trackingNumber, $data);

                    if (!$record['Type'] || !$record['OrderId']) {
                        $this->hopesLog->log(self::LOG_TYPE, 'TrackingNumber: '. $trackingNumber. ' -- Cannot Find Record.');
                        $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_SHIPMENT);
                        continue;
                    }

                    if ($record['Type'] == self::TYPE_FORMAL_FLOW) {
                        $this->updateFormalFlowItem($data, $record);

                        $this->hopesLog->log(self::LOG_TYPE, 'TrackingNumber: '. $trackingNumber. ' -- Formal Flow.');
                    }

                    if ($record['Type'] == self::TYPE_REVERSE_FLOW) {
                        $this->updateReverseFlowItem($data, $record);

                        $this->hopesLog->log(self::LOG_TYPE, 'TrackingNumber: '. $trackingNumber. ' -- Reverse Flow.');
                    }

                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
                } catch (\Exception $e) {
                    $this->hopesLog->logException(self::LOG_TYPE, $e, ['tracking_number' => $trackingNumber, 'data' => $data]);
                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_SHIPMENT);

                }
            }

        } catch (\Exception $e) {
            $this->hopesLog->logException(self::LOG_TYPE, $e);
            $this->result = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_SHIPMENT);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));

        $this->setResponse($this->result);
    }

    protected function findRecordsByTrackingNumber($trackingNumber, $postData)
    {

        $returnArray = [
            'Type' => '',
            'OrderId' => '',
        ];

        $incrementId = $postData[0]['B2CHDORDNO'];

        //Formal Flow
        $formalFlowTracking = $this->trackingCollection->create()
            ->join(
                ['sales_order' => 'sales_order'],
                'main_table.order_id = sales_order.entity_id',
                [
                    'sales_order.increment_id' => 'increment_id',
                ]
            )->addFieldToFilter(
            'sales_order.increment_id',
            $incrementId
        )->addFieldToFilter(
            ShipmentTrackInterface::TRACK_NUMBER,
            $trackingNumber
        );

        foreach ($formalFlowTracking->getItems() as $data) {
            if ($data) {
                $shipment = $data->getShipment();
                $shipment->addComment(json_encode($postData, JSON_UNESCAPED_UNICODE));
                $shipment->save();

                return [
                    'Type' => self::TYPE_FORMAL_FLOW,
                    'OrderId' => $data->getOrderId(),
                    'Obj' => $data,
                ];
            }
        }

        //Reverse Flow
        $reverseFlowTracking = $this->rmaShippingRecordFactory->create()
            ->addFieldToFilter(
                \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface::SHIPPING_NUMBER,
                $trackingNumber
            )->getLastItem();

        if (!$reverseFlowTracking) {
            return $returnArray;
        }

        $this->rmaHelper->saveRmaHistory(
            $reverseFlowTracking->getParentId(),
            json_encode($postData, JSON_UNESCAPED_UNICODE),
            \Branch8\Rma\Model\SenderType::TYPE_SYSTEM
        );

        return [
            'Type' => self::TYPE_REVERSE_FLOW,
            'OrderId' => $reverseFlowTracking->getParentId(),
            'Obj' => $reverseFlowTracking,
        ];

    }

    protected function updateFormalFlowItem($postDataCollection, $record)
    {
        foreach ($postDataCollection as $postData) {
            // 已送達
            if ($postData['GoodStatus'] == self::GOOD_STATUS["ARRIVED"]) {
                $shipment = $record['Obj']->getShipment();
                $order = $shipment->getOrder();

                foreach ($shipment->getItemsCollection() as $item) {
                    $item = $item->getOrderItem();
                    if ($item->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
                        continue;
                    }
                    
                    $item->setFlowStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_ARRIVED);
                    $item->save();
                    $this->updateOrderStatus->addItemStatusRecord(
                        $order->getId(),
                        $item,
                        \Branch8\HotaiCore\Model\Order\Status::STATUS_ARRIVED
                    );
                }
            }
        }

    }

    protected function updateReverseFlowItem($postDataCollection, $record)
    {
        $rmaId = $record['OrderId'];
        $rmaDetails = $this->rmaHelper->getRmaDetails($rmaId);
        $resolutionType = $rmaDetails->getResolutionType();
        $rmaItems = $this->rmaActions->getRmaItemCollection($rmaId);

        foreach ($postDataCollection as $postData) {
            // 退貨
            if ($resolutionType == $this->rmaHelper::RESOLUTION_REFUND) {

                //檢驗中
                if ($postData['GoodStatus'] == self::GOOD_STATUS["EXAMINE"]) {
                    $this->updateRmaItemStatus($rmaItems, RmaStatus::RETURN_REVIEW_PROCESSING);
                }
            }

            //換貨
            if ($resolutionType == $this->rmaHelper::RESOLUTION_REPLACE) {

                // 檢驗中
                if ($postData['GoodStatus'] == self::GOOD_STATUS["EXAMINE"]) {
                    $this->updateRmaItemStatus($rmaItems, RmaStatus::REPLACE_REVIEW_PROCESSING);
                }

                // 配送中
                if ($postData['GoodStatus'] == self::GOOD_STATUS["SHIPPING"]) {
                    $this->updateRmaItemStatus($rmaItems, RmaStatus::REPLACE_READY_SHIPPING_TO_CUSTOMER);
                }

                // 已到達
                if ($postData['GoodStatus'] == self::GOOD_STATUS["ARRIVED"]) {
                    $this->updateRmaItemStatus($rmaItems, RmaStatus::REPLACE_SHIPPING_ARRIVED);
                }
            }
        }

    }

    protected function updateRmaItemStatus($items, $updateStatus)
    {
        foreach ($items as $item) {
            $status = $this->rmaActions->changeItemStatusBySalesCron($item->getItemId(), $updateStatus);

            $item->setFlowStatus($status);
            $item->save();

            $this->updateOrderStatus->addItemStatusRecord(
                $item->getOrderId(),
                $item,
                $status
            );
        }

    }

}

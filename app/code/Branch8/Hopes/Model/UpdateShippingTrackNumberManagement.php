<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\Hopes\Helper\Log as HopesLog;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Sales\Model\Convert\Order as ConvertOrder;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Shipping\Model\ShipmentNotifier;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use Branch8\Hopes\Helper\Response\Message;

class UpdateShippingTrackNumberManagement extends AbstractModel implements \Branch8\Hopes\Api\UpdateShippingTrackNumberManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'UpdateShippingTrackNumberManagement';

    // Shipping type constants
    const SHIP_TYPE_HOTAI = 1;
    const SHIP_TYPE_TCAT = 2;
    const SHIP_TYPE_CONVENIENCE_STORE = 3;

    // Tracking number prefix for convenience store shipments
    const TRACKING_PREFIX_CONVENIENCE_STORE = '824';

    /** @var \Magento\Framework\Webapi\Rest\Request $request */
    public $request;

    /** @var Branch8\Hopes\Helper\Logger $logger */
    private $logger;

    /** @var /Branch8\Hopes\Helper\Logger $loggerhelper */
    private $loggerhelper;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection */
    protected $orderCollection;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepository;

    /** @var \Magento\Sales\Model\Convert\Order */
    protected $convertOrder;

    /** @var \Magento\Shipping\Model\ShipmentNotifier */
    protected $shipmentNotifier;

    /** @var array $result */
    public $result = [];

    /** @var \Magento\Sales\Model\Order $order */
    protected $order;

    /** @var \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory */
    protected $trackFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepository;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        CollectionFactory $orderCollection,
        Response $response,
        OrderRepositoryInterface $orderRepository,
        Order $order,
        ConvertOrder $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        TrackFactory $trackFactory,
        ProductRepositoryInterface $productRepository,
        HopesLog $hopesLog
    ) {
        $this->orderCollection = $orderCollection;
        $this->orderRepository = $orderRepository;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->order = $order;
        $this->trackFactory = $trackFactory;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdateShippingTrackNumber()
    {

        $this->hopesLog->log(self::LOG_TYPE, '[Start] UpdateShippingTrackNumber Start');

        $shipmentData = $this->request->getBodyParams();

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($shipmentData, JSON_UNESCAPED_UNICODE));

        try {
            $shipmentData = $this->formatRequest($shipmentData);

            foreach ($shipmentData as $trackingNumber => $data) {
                $orders = $this->orderCollection->create()
                    ->addFieldToFilter('hotai_child_order_number', $data['B2CHDORDNO']);

                // Ideally should be one data
                foreach ($orders->getData() as $singleOrder) {
                    // Convert tracking number to string to ensure type compatibility
                    $this->processOrderShipment($singleOrder, $data, (string)$trackingNumber);
                }

            }

        } catch (\Exception $e) {
            $this->hopesLog->logException(self::LOG_TYPE, $e);

            $this->result = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_SHIPMENT);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));

        $this->setResponse($this->result);

    }

    /**
     * Process order shipment for a single order
     *
     * @param array $singleOrder
     * @param array $data
     * @param string $trackingNumber
     * @return void
     */
    private function processOrderShipment(array $singleOrder, array $data, string $trackingNumber): void
    {
        $order = $this->order->load($singleOrder['entity_id']);
        $orderShipment = $this->convertOrder->toShipment($order);
        $orderShipment = $this->collectItemData($data['ITEMS'], $orderShipment, $order);

        //$finalTrackingNumber = $this->formatTrackingNumber($trackingNumber, (int)$data['SHIPTYPE']);
        $trackingData = $this->buildTrackingData($data['SHIPTYPE'], $trackingNumber);
        $track = $this->trackFactory->create()->addData($trackingData);

        try {
            $orderShipment->addTrack($track)->save();
            $this->shipmentNotifier->notify($orderShipment);

            $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
            $this->hopesLog->log(self::LOG_TYPE, '[Tracking Number] ' . $trackingNumber . ' -- Updated.');
        } catch (\Exception $e) {
            $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_SHIPMENT);
            $this->hopesLog->log(self::LOG_TYPE, '[Tracking Number] ' . $trackingNumber . ' -- ' . $e->getMessage());
            $this->hopesLog->logException(self::LOG_TYPE, $e, ['tracking_number' => $trackingNumber, 'data' => $data]);
        }
    }

    /**
     * Format tracking number with prefix if needed
     *
     * @param string $trackingNumber
     * @param int $shipType
     * @return string
     */
    private function formatTrackingNumber(string $trackingNumber, int $shipType): string
    {
        if ($shipType === self::SHIP_TYPE_CONVENIENCE_STORE) {
            return self::TRACKING_PREFIX_CONVENIENCE_STORE . $trackingNumber;
        }
        return $trackingNumber;
    }

    /**
     * Build tracking data array
     *
     * @param int|string $shipType
     * @param string $trackingNumber
     * @return array
     */
    private function buildTrackingData($shipType, string $trackingNumber): array
    {
        return [
            'carrier_code' => $this->getShipTypeString($shipType),
            'title' => $this->getShipTypeString($shipType),
            'number' => $trackingNumber,
            'logistics_company_url' => $this->getShipTypeUrl($shipType)
        ];
    }

    /**
     * Collect item data for shipment
     *
     * @param array $itemData
     * @param \Magento\Sales\Model\Order\Shipment $orderShipment
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function collectItemData(array $itemData, $orderShipment, $order)
    {
        foreach ($itemData as $data) {
            $orderShipment = $this->orderShipmentAddItem($order, $data, $orderShipment);
            $commentArray = [
                "編號" => $data['PARTNO'],
                "Hopes 處理時間" => $data['CHKORDDT'],
                "備註" => $data['MUCASECD']
            ];
            $comment = json_encode($commentArray, JSON_UNESCAPED_UNICODE);
            $orderShipment->addComment($comment);
        }

        $orderShipment->register();
        $orderShipment->getOrder()->setIsInProcess(true);

        return $orderShipment;

    }

    /**
     * Add item to order shipment
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $item
     * @param \Magento\Sales\Model\Order\Shipment $orderShipment
     * @return \Magento\Sales\Model\Order\Shipment
     */
    private function orderShipmentAddItem($order, array $item, $orderShipment)
    {
        $itemNumber = $item['PARTNO'];
        $itemQty = $item['QTY'];

        foreach ($order->getAllItems() as $orderItem) {
            // Cast product ID to int to match getHopesPartnoById() parameter type and avoid TypeError under strict_types
            if ($this->getHopesPartnoById((int)$orderItem->getProductId()) !== $itemNumber) {
                continue;
            }

            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($itemQty);
            $orderShipment->addItem($shipmentItem);
        }

        return $orderShipment;
    }

    /**
     * Get shipping type string description
     *
     * @param int|string $shipType
     * @return string
     */
    public function getShipTypeString($shipType): string
    {
        switch ((int)$shipType) {
            case self::SHIP_TYPE_HOTAI:
                return '和泰配送';
            case self::SHIP_TYPE_TCAT:
                return '黑貓';
            case self::SHIP_TYPE_CONVENIENCE_STORE:
                return '超商';
            default:
                return '其他配送方式';
        }
    }

    /**
     * Get shipping type tracking URL
     *
     * @param int|string $shipType
     * @return string
     */
    public function getShipTypeUrl($shipType): string
    {
        switch ((int)$shipType) {
            case self::SHIP_TYPE_HOTAI:
                return '不帶網址，不顯示貨態追蹤區塊';
            case self::SHIP_TYPE_TCAT:
                return 'https://www.t-cat.com.tw/inquire/trace.aspx';
            case self::SHIP_TYPE_CONVENIENCE_STORE:
                return 'https://eservice.7-11.com.tw/e-tracking/search.aspx?txtProductNum=' . self::TRACKING_PREFIX_CONVENIENCE_STORE . '%s&__EVENTTARGET=';
            default:
                return '不帶網址，不顯示貨態追蹤區塊';
        }
    }

    /**
     * Get Hopes part number by product ID
     *
     * @param int $productId
     * @return string|null
     */
    public function getHopesPartnoById(int $productId): ?string
    {
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $this->productRepository->getById($productId);
        $customAttribute = $product->getCustomAttribute('hotai1_PARTNO');
        return $customAttribute ? $customAttribute->getValue() : null;
    }

    /**
     * Format request data by grouping by tracking number
     *
     * @param array $shipmentData
     * @return array
     */
    private function formatRequest(array $shipmentData): array
    {
        $formattedData = [];

        foreach ($shipmentData as $data) {
            $trackingNumber = (string) $data['NWSHIPMENTNO'];

            if (isset($formattedData[$trackingNumber])) {
                $formattedData[$trackingNumber]['ITEMS'][] = $this->setItems($data);
                continue;
            }

            $formattedData[$trackingNumber] = [
                'SHIPTYPE' => $data['SHIPTYPE'],
                'B2CHDORDNO' => $data['B2CHDORDNO'],
                'ITEMS' => [$this->setItems($data)]
            ];
        }

        return $formattedData;
    }

    /**
     * Set item data structure
     *
     * @param array $item
     * @return array
     */
    private function setItems(array $item): array
    {
        return [
            'PROMARK' => $item['PROMARK'],
            'PARTNO' => $item['PARTNO'],
            'CHKORDDT' => $item['CHKORDDT'],
            'MUCASECD' => $item['MUCASECD'],
            'QTY' => $item['QTY']
        ];
    }
}

<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Hopes\Model;

use Branch8\HifiSalesReport\Helper\Report as HifiReport;
use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Helper\Log as HopesLog;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\HotaiCore\Model\Order\Status;
use \Branch8\Shipping\Model\ShippingMethod;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollection;
use Branch8\Hopes\Helper\Response\Message;
use \Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class OrderStackShipmentSinManagement  extends AbstractModel implements \Branch8\Hopes\Api\OrderStackShipmentSinManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'OrderStackShipmentSinManagement';

    const REQ_FROM = "From";
    const REQ_TO = "To";
    const CONFIG_PATH_SIN_SELLER_GROUPS = 'hopes/sin/seller_groups';

    /** Default file_name => seller_code when config is empty */
    private const DEFAULT_FILE_SELLER_MAP = [
        '01' => 'HTC01',
        '02' => 'HTC02',
        '03' => 'HTC03',
        '04' => 'HTC04',
        '05' => 'HTC05',
    ];

    /** @var \Magento\Framework\Webapi\Rest\Request $request */
    public $request;
    
    /** @var Branch8\Hopes\Helper\Logger $logger */
    private $logger;
    
    /** @var /Branch8\Hopes\Helper\Logger $loggerhelper */
    private $loggerhelper;
    
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory */
    protected $orderCollectionFactory;
    
    /** @var array $result */
    public $result = [];
    
    /** @var \Branch8\HifiSalesReport\Helper\Report $hifiReport */
    public $hifiReport;
    
    /** @var array $configurable */
    public $configurable = [];
    
    /** @var array $requestArr */
    public $requestArr =
        [
        self::REQ_FROM,
        self::REQ_TO,
    ];

    public $shipmentCollection;

    protected $attribute;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var SerializerInterface */
    private $serializer;

    /** @var HotaiCoreCommon */
    protected $hotaiCoreCommon;

    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        CollectionFactory $orderCollectionFactory,
        Response $response,
        ShipmentCollection $shipmentCollection,
        Attribute $attribute,
        HotaiCoreCommon $hotaiCoreCommon,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        HopesLog $hopesLog
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->shipmentCollection = $shipmentCollection;
        $this->attribute = $attribute;
        $this->logger = $logger;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderStackShipmentSin()
    {

        $this->hopesLog->log(self::LOG_TYPE, '[Start] OrderStackShipmentSinManagement Start');

        $body = $this->request->getBodyParams();
        $this->checkParams($this->requestArr, $body);

        
        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]'. json_encode($body, JSON_UNESCAPED_UNICODE));

        $sellerToGroupMap = $this->getSellerToGroupMap();
        $groupedResult = $this->initializeGroupedResult();
        $trackingToGroupMap = [];

        $collection = $this->getCheckOrderCollectionList($body[self::REQ_FROM], $body[self::REQ_TO]);
        foreach ($collection->getData() as $data) {
            $trackingNumber = $data['sales_shipment_track.track_number'];
            $sellerCode = $data['sales_order_item.seller_code'] ?? '';

            if (isset($trackingToGroupMap[$trackingNumber])) {
                $groupKey = $trackingToGroupMap[$trackingNumber];
                $record = &$groupedResult[$groupKey][$trackingNumber];
                $oriAmt = $record['ShipmentDetail']['ShipmentAmount'];
                $added = $oriAmt + (float) $data['sales_shipment_item.price'];
                $record['OrderAmount'] = (int) $added;
                $record['ShipmentDetail']['ShipmentAmount'] = (int) $added;
                $record['ShipmentDetail']['AwardAmount'] = (int) $added;
                continue;
            }

            if (!isset($sellerToGroupMap[$sellerCode])) {
                continue;
            }
            $groupKey = $sellerToGroupMap[$sellerCode];
            $groupedResult[$groupKey][$trackingNumber] = $this->setOrderData($data);
            $trackingToGroupMap[$trackingNumber] = $groupKey;
        }

        $this->result = $this->hasAnyGroupedData($groupedResult)
            ? $groupedResult
            : Message::getReturnMessageList(Message::RETURN_EMPTY_ARRAY);

        $this->hopesLog->log(self::LOG_TYPE, '[Response]'. json_encode($this->result, JSON_UNESCAPED_UNICODE));
        
        $this->setResponse($this->result);
    }
    
    /**
     * setOrderData
     *
     * @param  array $data
     * @return array
     */
    public function setOrderData($data)
    {

        $storeId = $data['shipping.cvs_store_code'];

        if (!empty($data['shipping.store_address_info'])) {
            $storeAddressInfo = json_decode($data['shipping.store_address_info'] ?? '',true);
            $storeId = $storeAddressInfo['storeid'];
        }

        return [
            'EshopId' => '234',
            'OPMode'=> 'A', //A: 7-11
            'EshopOrderNo' => $data['sales_order.entity_id'],
            'EshopOrderDate' => date('Y-m-d', strtotime($data['sales_order.created_at'])),
            'ServiceType' => 3, //1: 取貨付款 3:取貨不付款
            'ShopperName' => $data['billing.firstname'] ?? ' ',//shopperName
            'ShopperPhone' =>  '', // default empty
            'ShopperEmail' =>  '', // default empty
            'ShopperMobilPhone' =>  '', // default empty
            'ReceiverName' =>  $data['shipping.firstname'] ?? ' ',
            'ReceiverPhone' => '', //dafault empty
            'ReceiverMobilPhone' => $data['shipping.telephone'],
            'ReceiverEmail' => '',
            'ReceiverIDNumber' => '',
            'OrderAmount' => (int) $data['sales_order.grand_total'],
            'OrderDetail' => [
                'ProductId' => '',
                'ProductName' => '',
                'Quantity' => '',
                'Unit' =>'',
                'UnitPrice' => ''
            ],
            'ShipmentDetail' => [
                'ShipmentNo' => $data['sales_shipment_track.track_number'],
                'ShipDate' => date('Y-m-d', strtotime($data['created_at'])),
                'ReturnDate' => date('Y-m-d', strtotime('+8 day', strtotime($data['created_at']))),
                'LastShipment' => 'Y', //Is last ship
                'ShipmentAmount' => (int) $data['sales_shipment_item.price'],
                'StoreId' => $storeId ?? '', 
                'EshopType' => '04',
                'AwardAmount' => $data['sales_shipment_item.price']
            ]

        ];
    }

    /**
     * getCheckOrderCollectionList
     *
     * @param  string $from
     * @param  string $to
     * @return \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $collection
     */
    private function getCheckOrderCollectionList($from, $to)
    {
        $partNoAttributeId = $this->attribute->getIdByCode(
            \Magento\Catalog\Model\Product::ENTITY,
            'hotai1_PARTNO'
        );

        $collection = $this->shipmentCollection->create()
        ->join(
            ['sales_order' => 'sales_order'],
            'main_table.order_id = sales_order.entity_id',
            [
                'sales_order.increment_id' => 'sales_order.increment_id',
                'sales_order.grand_total' => 'sales_order.grand_total',
                'sales_order.entity_id' => 'sales_order.entity_id',
                'sales_order.created_at' => 'sales_order.created_at',
                'sales_order.hotai_child_order_number' => 'sales_order.hotai_child_order_number',
            ]
            )->join(
                ['shipping' => 'sales_order_address'],
                'main_table.shipping_address_id = shipping.entity_id',
                [
                    'shipping.firstname' => 'shipping.firstname',
                    'shipping.lastname' => 'shipping.lastname',
                    'shipping.telephone' => 'shipping.telephone',
                    'shipping.email' => 'shipping.email',
                    'shipping.region' => 'shipping.region',
                    'shipping.city' => 'shipping.city',
                    'shipping.cvs_store_code' => 'shipping.cvs_store_code',
                    'shipping.cvs_store_name' => 'shipping.cvs_store_name',
                    'shipping.store_address_info' => 'shipping.store_address_info',
                ]
            )->join(
                ['billing' => 'sales_order_address'],
                'main_table.billing_address_id = billing.entity_id',
                [
                    'billing.firstname' => 'billing.firstname',
                    'billing.lastname' => 'billing.lastname',
                    'billing.telephone' => 'billing.telephone',
                    'billing.email' => 'billing.email',
                ]
            )->join(
                ['sales_shipment_track' => 'sales_shipment_track'],
                'main_table.entity_id = sales_shipment_track.parent_id',
                [
                    'sales_shipment_track.track_number' => 'sales_shipment_track.track_number',
                ]
            )->join(
                ['sales_shipment_item' => 'sales_shipment_item'],
                'main_table.entity_id = sales_shipment_item.parent_id',
                [
                    'sales_shipment_item.price' => 'sales_shipment_item.price',
                ]
            )->join(
                ['sales_order_item' => 'sales_order_item'],
                'sales_shipment_item.order_item_id = sales_order_item.item_id',
                [
                    'sales_order_item.seller_code' => 'sales_order_item.seller_code',
                ]
            )->join(
                ['catalog_product_entity' => 'catalog_product_entity'],
                    'sales_shipment_item.product_id = catalog_product_entity.entity_id',
                [
                    'catalog_product_entity.row_id' => 'row_id'
                ]
            )->join(
                ['catalog_product_entity_varchar' => 'catalog_product_entity_varchar'],
                    'catalog_product_entity.row_id = catalog_product_entity_varchar.row_id',
                [
                    'catalog_product_entity_varchar.value' => 'value',
                    'catalog_product_entity_varchar.store_id' => 'store_id'
                ]
            )->addFieldToFilter(
                'main_table.created_at',array('from' => $from, 'to' => $to)
            )->addFieldToFilter(
                'catalog_product_entity_varchar.attribute_id', $partNoAttributeId
            )->addFieldToFilter(
                'catalog_product_entity_varchar.store_id', 0
            )->addFieldToFilter(
                'sales_order.shipping_method', [
                    'in' =>
                    [
                        ShippingMethod::METHOD_CONVENIENCE_STORE,
                    ],
                ]
            );

        $collection = $this->filterHotaiV1Data($collection, 'sales_order.increment_id');
        $collection = $this->filterSinConfiguredSellers($collection);

        return $collection;
    }

    /**
     * Filter collection to only include configured seller codes from SIN config.
     * Replaces filterHotaiHopesSeller so HTC02, HTC03, HTC04, HTC05, etc. are included.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection $collection
     * @return \Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    private function filterSinConfiguredSellers($collection)
    {
        $sellerToFileMap = $this->parseFileSellerConfig();
        $configuredSellerCodes = array_keys($sellerToFileMap);

        if (!empty($configuredSellerCodes)) {
            $connection = $collection->getConnection();
            $quotedCodes = array_map([$connection, 'quote'], $configuredSellerCodes);
            $inClause = implode(',', $quotedCodes);
            $collection->getSelect()->where("sales_order_item.seller_code IN ($inClause)");
        }

        return $collection;
    }

    /**
     * Build seller_code => file_name mapping. Output keys = file names.
     *
     * @return array<string, string> e.g. ['HTC01' => '01', 'HTC02' => '02']
     */
    private function getSellerToGroupMap(): array
    {
        return $this->parseFileSellerConfig();
    }

    /**
     * Parse config to get seller_code => file_name map.
     * Supports new format (file_name + seller_code) and legacy (seller_code only).
     *
     * @return array<string, string> seller_code => file_name
     */
    private function parseFileSellerConfig(): array
    {
        $configValue = (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SIN_SELLER_GROUPS,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($configValue) || $configValue === '[]') {
            return array_flip(self::DEFAULT_FILE_SELLER_MAP);
        }

        try {
            $rows = $this->serializer->unserialize($configValue);
        } catch (\InvalidArgumentException $e) {
            return array_flip(self::DEFAULT_FILE_SELLER_MAP);
        }

        if (!is_array($rows)) {
            return array_flip(self::DEFAULT_FILE_SELLER_MAP);
        }

        $map = [];
        foreach ($rows as $row) {
            if (!is_array($row) || empty(trim((string) ($row['seller_code'] ?? '')))) {
                continue;
            }
            $sellerCode = trim((string) $row['seller_code']);
            $fileName = trim((string) ($row['file_name'] ?? ''));
            $map[$sellerCode] = $fileName !== '' ? $fileName : $sellerCode;
        }

        return !empty($map) ? $map : array_flip(self::DEFAULT_FILE_SELLER_MAP);
    }

    /**
     * Get configured file names (output keys) from config.
     *
     * @return array<string>
     */
    private function getConfiguredFileNames(): array
    {
        $map = $this->parseFileSellerConfig();
        $fileNames = array_unique(array_values($map));
        return array_values($fileNames);
    }

    /**
     * Initialize empty grouped result structure. Keys = file names from config.
     *
     * @return array<string, array>
     */
    private function initializeGroupedResult(): array
    {
        $fileNames = $this->getConfiguredFileNames();
        $result = [];
        foreach ($fileNames as $fileName) {
            $result[$fileName] = [];
        }
        return $result;
    }

    /**
     * Check if any group has data.
     *
     * @param array<string, array> $groupedResult
     * @return bool
     */
    private function hasAnyGroupedData(array $groupedResult): bool
    {
        foreach ($groupedResult as $items) {
            if (!empty($items)) {
                return true;
            }
        }
        return false;
    }
}


<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Helper\Log as HopesLog;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory as DetailsCollection;
use Branch8\Rma\Helper\Data;
use Branch8\Shipping\Model\ShippingMethod;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Branch8\Hopes\Helper\Response\Message;
use Branch8\Rma\Helper\Config\Shipping as RmaShippingTime;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;

/**
 * TCat EOD Management Model
 * 
 * Handles TCat end-of-day order processing for RMA returns
 */
class TCatEodManagement extends AbstractModel implements \Branch8\Hopes\Api\TCatEodManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'TCatEodManagement';

    const REQ_FROM = "From";
    const REQ_TO = "To";

    // Default TCat configuration paths
    const CONFIG_PATH_RCVER_NAME = 'hopes/tcat/rcver_name';
    const CONFIG_PATH_RCVER_PHONE = 'hopes/tcat/rcver_phone';
    const CONFIG_PATH_RCVER_CELLPHONE = 'hopes/tcat/rcver_cellphone';
    const CONFIG_PATH_RCVER_SUDA6 = 'hopes/tcat/rcver_suda6';
    const CONFIG_PATH_RCVER_ADDRESS = 'hopes/tcat/rcver_address';
    const CONFIG_PATH_CUSTOMER_ID = 'hopes/tcat/customer_id';
    const CONFIG_PATH_CLIMATE = 'hopes/tcat/climate';
    const CONFIG_PATH_DISTANCE = 'hopes/tcat/distance';
    const TCAT_SPECIFICATION = '0001';
    const TCAT_SHIP_TYPE = '2';
    const TCAT_IS_COLLECTION = 'N';
    const TCAT_COLLECTION_AMOUNT = '0';
    const TCAT_IS_PAY_ON_SITE = 'N';
    const TCAT_IS_PAY_CASH = '01';
    const TCAT_IS_FRAGILE = 'N';
    const TCAT_IS_PRECISION = 'N';

    /** @var Logger */
    private $logger;
    
    /** @var LoggerHelper */
    private $loggerhelper;
    
    /** @var CollectionFactory */
    protected $orderCollectionFactory;
    
    /** @var array */
    private $result = [];
    
    /** @var array */
    private $dataList = [];
    
    /** @var array */
    private $requestArr = [
        self::REQ_FROM,
        self::REQ_TO,
    ];
    
    /** @var ProductRepositoryInterface */
    protected $productRepository;
    
    /** @var Data */
    protected $rmaHelper;
    
    /** @var DetailsCollection */
    protected $details;

    /** @var Attribute */
    protected $attribute;

    /** @var RmaShippingTime */
    protected $rmaShippingTime;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    /**
     * @param Logger $logger
     * @param LoggerHelper $loggerhelper
     * @param Request $request
     * @param CollectionFactory $orderCollectionFactory
     * @param Response $response
     * @param ParentOrder $parentOrder
     * @param ParentOrderFactory $parentOrderInterface
     * @param ProductRepositoryInterface $productRepository
     * @param Data $rmaHelper
     * @param Attribute $attribute
     * @param DetailsCollection $details
     * @param RmaShippingTime $rmaShippingTime
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        CollectionFactory $orderCollectionFactory,
        Response $response,
        ParentOrder $parentOrder,
        ParentOrderFactory $parentOrderInterface,
        ProductRepositoryInterface $productRepository,
        Data $rmaHelper,
        Attribute $attribute,
        DetailsCollection $details,
        RmaShippingTime $rmaShippingTime,
        ScopeConfigInterface $scopeConfig,
        HopesLog $hopesLog
    ) {
        $this->rmaHelper = $rmaHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->parentOrder = $parentOrder;
        $this->parentOrderInterface = $parentOrderInterface;
        $this->productRepository = $productRepository;
        $this->details = $details;
        $this->attribute = $attribute;
        $this->rmaShippingTime = $rmaShippingTime;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function posTCatEod(): void
    {
        $this->hopesLog->log(self::LOG_TYPE, '[Start] TCatEod Start');

        $body = $this->request->getBodyParams();
        $this->checkParams($this->requestArr, $body);

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($body, JSON_UNESCAPED_UNICODE));

        $collection = $this->getCheckOrderCollectionList($body[self::REQ_FROM], $body[self::REQ_TO]);

        foreach ($collection->getData() as $rmaItem) {
            try {
                $this->processRmaItem($rmaItem);
            } catch (\Exception $e) {
                $this->hopesLog->log(
                    self::LOG_TYPE,
                    sprintf('[Error] RMA ID: %s, Error: %s', $rmaItem['id'] ?? 'unknown', $e->getMessage())
                );
                $this->hopesLog->logException(self::LOG_TYPE, $e, ['rma_item' => $rmaItem]);
                continue;
            }
        }

        if (empty($this->result)) {
            $this->result = Message::getReturnMessageList(Message::RETURN_EMPTY_ARRAY);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));
        $this->setResponse($this->result);
    }

    /**
     * Process a single RMA item
     *
     * @param array $rmaItem
     * @return void
     * @throws \Exception
     */
    private function processRmaItem(array $rmaItem): void
    {
        $this->hopesLog->log(self::LOG_TYPE, sprintf('[RmaId] %s', $rmaItem['id']));

        $order = $this->rmaHelper->getOrder($rmaItem['order_id']);
        $orderId = $order->getHotaiChildOrderNumber();

        if (is_null($orderId)) {
            $this->hopesLog->log(self::LOG_TYPE, sprintf('[Skip] RMA ID: %s - HotaiChildOrderNumber is null', $rmaItem['id']));
            return;
        }

        $rmaOrderId = (string) $orderId . (string) $rmaItem['id'];
        if (in_array($rmaOrderId, $this->dataList)) {
            $this->hopesLog->log(self::LOG_TYPE, sprintf('[Skip] RMA ID: %s - Order number already existed', $rmaItem['id']));
            return;
        }

        $this->result[] = $this->setReturnOrderData($rmaItem);
        $this->dataList[] = $rmaOrderId;
    }
    
    /**
     * Set return order data for TCat API
     *
     * @param array $data
     * @return array
     */
    public function setReturnOrderData(array $data): array
    {
        $order = $this->rmaHelper->getOrder($data['order_id']);
        $rmaShippingTime = $this->rmaShippingTime->getTCatDeliveryTime($data['rma_delivery_time']);

        $receiverConfig = $this->getReceiverConfig();
        $orderData = $this->buildOrderData($data, $order, (string) $rmaShippingTime, $receiverConfig);

        return $orderData;
    }

    /**
     * Get receiver configuration from system config
     *
     * @return array
     */
    private function getReceiverConfig(): array
    {
        return [
            'name' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_RCVER_NAME,
                ScopeInterface::SCOPE_STORE
            ),
            'phone' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_RCVER_PHONE,
                ScopeInterface::SCOPE_STORE
            ),
            'cellphone' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_RCVER_CELLPHONE,
                ScopeInterface::SCOPE_STORE
            ),
            'suda6' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_RCVER_SUDA6,
                ScopeInterface::SCOPE_STORE
            ),
            'address' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_RCVER_ADDRESS,
                ScopeInterface::SCOPE_STORE
            ),
        ];
    }

    /**
     * Get TCat API configuration from system config
     *
     * @return array
     */
    private function getTcatApiConfig(): array
    {
        return [
            'customer_id' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_CUSTOMER_ID,
                ScopeInterface::SCOPE_STORE
            ),
            'climate' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_CLIMATE,
                ScopeInterface::SCOPE_STORE
            ),
            'distance' => (string) $this->scopeConfig->getValue(
                self::CONFIG_PATH_DISTANCE,
                ScopeInterface::SCOPE_STORE
            ),
        ];
    }

    /**
     * Build order data array for TCat API
     *
     * @param array $data
     * @param Order $order
     * @param string $rmaShippingTime
     * @param array $receiverConfig
     * @return array
     */
    private function buildOrderData(array $data, Order $order, string $rmaShippingTime, array $receiverConfig): array
    {
        $orderNo = (string) $order->getHotaiChildOrderNumber() . '_' . $data['id'];
        $shipDate = date('Ymdhis', strtotime($data['updated_at']));
        $tcatApiConfig = $this->getTcatApiConfig();

        return [
            'RMAID' => $data['id'],
            'SHIPTYPE' => self::TCAT_SHIP_TYPE,
            'ShipNo' => '',
            'OrderNo' => $orderNo,
            'CustomerId' => $tcatApiConfig['customer_id'],
            'Climate' => $tcatApiConfig['climate'],
            'Distance' => $tcatApiConfig['distance'],
            'Specification' => self::TCAT_SPECIFICATION,
            'IsCollection' => self::TCAT_IS_COLLECTION,
            'CollectionAmount' => self::TCAT_COLLECTION_AMOUNT,
            'IsPayOnSite' => self::TCAT_IS_PAY_ON_SITE,
            'IsPayCash' => self::TCAT_IS_PAY_CASH,
            'RcverName' => $receiverConfig['name'],
            'RcverPhone' => $receiverConfig['phone'],
            'RcverCellphone' => $receiverConfig['cellphone'],
            'RcverSuda6' => $receiverConfig['suda6'],
            'RcverAddress' => $receiverConfig['address'],
            'SenderName' => $this->replaceUnusedLabel($data['rma_receiver'] ?? ' '),
            'SenderPhone' => $data['rma_phone'] ?? '',
            'SenderCellphone' => $data['rma_phone'] ?? '',
            'SenderSuda6' => '',
            'SenderAddress' => $data['rma_address'] ?? '',
            'ShipDate' => $shipDate,
            'WantRcvTime' => $rmaShippingTime,
            'WantShipTime' => $rmaShippingTime,
            'MembarName' => '',
            'GoodsName' => '',
            'IsFragile' => self::TCAT_IS_FRAGILE,
            'IsPrecision' => self::TCAT_IS_PRECISION,
            'Note' => '',
            'RouteNo' => '',
            'DtShipDate' => '',
        ];
    }

    /**
     * Get ship type based on shipping method
     *
     * @param string $shippingMethod
     * @return int
     */
    public function getShipType(string $shippingMethod): int
    {
        switch ($shippingMethod) {
            case ShippingMethod::METHOD_CONVENIENCE_STORE:
                return 3;
            case ShippingMethod::METHOD_HOME:
                return 2;
            default:
                return 1;
        }
    }

    /**
     * Get check order collection list
     *
     * @param string $from
     * @param string $to
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    private function getCheckOrderCollectionList(string $from, string $to)
    {
        $partNoAttributeId = $this->attribute->getIdByCode(
            \Magento\Catalog\Model\Product::ENTITY,
            'hotai1_PARTNO'
        );

        $collection = $this->details->create()
            ->join(
                ['marketplace_rma_items' => 'marketplace_rma_items'],
                'main_table.id = marketplace_rma_items.rma_id',
                [
                    'marketplace_rma_items.product_id' => 'marketplace_rma_items.product_id',
                    'marketplace_rma_items.qty' => 'marketplace_rma_items.qty',
                    'marketplace_rma_items.reason_id' => 'marketplace_rma_items.reason_id'
                ]
            )
            ->join(
                ['catalog_product_entity' => 'catalog_product_entity'],
                'marketplace_rma_items.product_id = catalog_product_entity.entity_id',
                [
                    'catalog_product_entity.row_id' => 'row_id'
                ]
            )
            ->join(
                ['catalog_product_entity_varchar' => 'catalog_product_entity_varchar'],
                'catalog_product_entity.row_id = catalog_product_entity_varchar.row_id',
                [
                    'catalog_product_entity_varchar.value' => 'value',
                    'catalog_product_entity_varchar.store_id' => 'store_id'
                ]
            )
            ->join(
                ['sales_order' => 'sales_order'],
                'main_table.order_id = sales_order.entity_id',
                [
                    'sales_order.increment_id' => 'sales_order.increment_id',
                ]
            )
            ->join(
                ['sales_order_item' => 'sales_order_item'],
                'marketplace_rma_items.item_id = sales_order_item.item_id',
                [
                    'sales_order_item.seller_code' => 'sales_order_item.seller_code',
                ]
            )
            ->addFieldToFilter('catalog_product_entity_varchar.attribute_id', $partNoAttributeId)
            ->addFieldToFilter('catalog_product_entity_varchar.store_id', 0)
            ->addFieldToFilter(
                'main_table.status',
                ['in' => [RmaStatus::RETURN_APPLY_PROCESSING]]
            )
            ->addFieldToFilter(
                'main_table.created_date',
                ['from' => $from, 'to' => $to]
            );

        $collection = $this->filterHotaiV1Data($collection, 'sales_order.increment_id');
        $collection = $this->filterHotaiHopesSeller($collection, 'sales_order_item.seller_code');

        return $collection;
    }

    /**
     * Get Hopes product attributes by product ID
     *
     * @param int $productId
     * @return array
     */
    public function getHopesProductAttributesById(int $productId): array
    {
        $product = $this->productRepository->getById($productId);

        return [
            'FRCD' => $product->getData('hotai1_FRCD'),
            'PARTNO' => $product->getData('hotai1_PARTNO'),
            'EMPRTAX' => $product->getData('hotai1_EMPRTAX'),
        ];
    }
}

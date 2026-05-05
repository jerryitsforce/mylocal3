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
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping\CollectionFactory as MarketplaceRmaShippingFactory;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderInvoiceLogCollectionFactory;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory as DetailsCollection;
use \Branch8\Rma\Helper\Data;
use \Branch8\Shipping\Model\ShippingMethod;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Eav\Model\ResourceModel\Entity\Attribute;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class RmaDetailsManagement extends AbstractModel implements \Branch8\Hopes\Api\RmaDetailsManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'RmaDetailsManagement';

    const REQ_FROM = "From";
    const REQ_TO = "To";

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

    /** @var array $configurable */
    public $configurable = [];

    /** @var array $requestArr */
    public $requestArr =
        [
        self::REQ_FROM,
        self::REQ_TO,
    ];

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepository;

    /** @var \Branch8\Rma\Helper\Data $rmaHelper */
    protected $rmaHelper;

    /** @var \Webkul\MpRmaSystem\Model\DetailsFactory */
    protected $details;

    protected $attribute;

    /** @var OrderInvoiceLogCollectionFactory */
    protected $orderInvoiceLogCollectionFactory;

    /** @var array $checkPkItems */
    public $checkPkItems = [];

    /** @var Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping\CollectionFactory $marketplaceRmaShippingFactory */
    protected $marketplaceRmaShippingFactory;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

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
        OrderInvoiceLogCollectionFactory $orderInvoiceLogCollectionFactory,
        MarketplaceRmaShippingFactory $marketplaceRmaShippingFactory,
        HopesLog $hopesLog
    ) {
        $this->rmaHelper = $rmaHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->parentOrder = $parentOrder;
        $this->parentOrderInterface = $parentOrderInterface;
        $this->productRepository = $productRepository;
        $this->details = $details;
        $this->attribute = $attribute;
        $this->logger = $logger;
        $this->orderInvoiceLogCollectionFactory = $orderInvoiceLogCollectionFactory;
        $this->marketplaceRmaShippingFactory = $marketplaceRmaShippingFactory;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getRmaDetails()
    {

        $this->hopesLog->log(self::LOG_TYPE, '[Start] RmaDetailsManagement Start');

        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getData() as $rmaItem) {

            try {

                $this->hopesLog->log(self::LOG_TYPE, '[RmaId] ' . $rmaItem['id']);

                if ($this->checkPkValue($this->getPkData($rmaItem), $this->checkPkItems)) {
                    $this->hopesLog->log(self::LOG_TYPE, '[Duplicate] ' . $this->getPkData($rmaItem));
                    continue;
                }

                $shippingNumber = $this->getShippingNumber($rmaItem['id']);
                if (is_null($shippingNumber) || empty($shippingNumber)) {
                    $this->hopesLog->log(self::LOG_TYPE, 'There is no shipping number.');
                    continue;
                }

                $this->checkPkItems[] = $this->getPkData($rmaItem);

                $order = $this->rmaHelper->getOrder($rmaItem['order_id']);
                $orderId = $order->getHotaiChildOrderNumber() ?? $rmaItem['id'];

                if (is_null($orderId)) {
                    continue;
                }

                if (isset($this->result[$orderId])) {
                    $countItems = count($this->result[$orderId]['ITEMS']);
                    $this->result[$orderId]['ITEMS'][] = $this->setItemData($rmaItem, $countItems);
                    continue;
                }

                $this->result[$orderId] = $this->setReturnOrderData($rmaItem);
                $this->result[$orderId]['ITEMS'][] = $this->setItemData($rmaItem);
            } catch (\Exception $e) {
                $this->hopesLog->logException(self::LOG_TYPE, $e, ['rma_item' => $rmaItem]);
                continue;
            }

        }

        // if (empty($this->result)) {
        //     $this->result = Message::getReturnMessageList(Message::RETURN_EMPTY_ARRAY);
        // }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));

        return $this->result;

        // $this->setResponse($this->result);
    }

    /**
     * setItemData
     *
     * @param  array $data
     * @param  string | int $countItems
     * @return array
     */
    public function setItemData($data, $countItems = "0")
    {

        $attributes = $this->getHopesProductAttributesById($data['marketplace_rma_items.product_id']);
        $shippingNumber = $this->getShippingNumber($data['id']);

        return [
            'ITEM_NO' => (string) $countItems,
            'FRCD' => $attributes['FRCD'],
            'PARTNO' => $attributes['PARTNO'],
            'ORDQTY' => $data['marketplace_rma_items.qty'],
            'RTNORDQTY' => $data['marketplace_rma_items.qty'],
            'RTNORDDT' => $data['created_date'],
            'RTNRSON' => $this->rmaHelper->getReasonById($data['marketplace_rma_items.reason_id']),
            'RTNTRANSNO' => !empty($shippingNumber) ? $shippingNumber->getShippingNumber() : $shippingNumber,
            'RMA_ID' => $data['id'],
        ];
    }

    /**
     * setReturnOrderData
     *
     * @param  array $data
     * @return array
     */
    public function setReturnOrderData($data)
    {
        $order = $this->rmaHelper->getOrder($data['order_id']);
        $invoice = $this->getNewestInvoiceLogByOrderId((int) $data['order_id']);

        return [
            'B2CHDORDNO' => (string) $order->getHotaiChildOrderNumber(),
            'ORDDT' => $order->getCreatedAt(),
            'CURRENCY' => 'NT',
            'RTNINVONO' => empty($invoice) ? $data['sales_order.hotai_checkout_number'] : $invoice->getInvoiceNumber(),
            'B2CUSTID' => $data['customer_tax_id_number'] ?? '',
            'INVSHEET' => is_null($data['customer_tax_id_number']) ? 2 : 3,
            'RTNTRANS' => "2",
            'B2CUSTNM' => $this->replaceUnusedLabel($data['rma_receiver'] ?? '  '),
            'B2CUSTADDR' => $data['rma_address'] ?? '',
            'B2CUSTZIP' => '',
            'B2CUSTTEL' => $data['rma_phone'] ?? '',
        ];
    }

    /**
     * getShipType
     *
     * @param  string $shippingMethod
     * @return int
     */
    public function getShipType($shippingMethod)
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
     * getCheckOrderCollectionList
     *
     * @param  string $from
     * @param  string $to
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     */
    private function getCheckOrderCollectionList()
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
                    'marketplace_rma_items.reason_id' => 'marketplace_rma_items.reason_id',
                    'marketplace_rma_items.item_id' => 'marketplace_rma_items.item_id'
                ]
            )->join(
            ['catalog_product_entity' => 'catalog_product_entity'],
            'marketplace_rma_items.product_id = catalog_product_entity.entity_id',
            [
                'catalog_product_entity.row_id' => 'row_id',
            ]
        )->join(
            ['catalog_product_entity_varchar' => 'catalog_product_entity_varchar'],
            'catalog_product_entity.row_id = catalog_product_entity_varchar.row_id',
            [
                'catalog_product_entity_varchar.value' => 'value',
                'catalog_product_entity_varchar.store_id' => 'store_id',
            ]
        )->join(
            ['sales_order' => 'sales_order'],
            'main_table.order_id = sales_order.entity_id',
            [
                'sales_order.increment_id' => 'sales_order.increment_id',
                'sales_order.hotai_checkout_number' => 'sales_order.hotai_checkout_number'
            ]
        )->join(
            ['sales_order_item' => 'sales_order_item'],
            'marketplace_rma_items.item_id = sales_order_item.item_id',
            [
                'sales_order_item.seller_code' => 'sales_order_item.seller_code',
            ]
        )->addFieldToFilter(
            'catalog_product_entity_varchar.attribute_id', $partNoAttributeId
        )->addFieldToFilter(
            'catalog_product_entity_varchar.store_id', 0
        )->addFieldToFilter(
            'main_table.is_sent_to_hopes', \Branch8\Hopes\Helper\Status::PENDING_TO_HOPES);

        $collection = $this->filterHotaiV1Data($collection, 'sales_order.increment_id');
        $collection = $this->filterHotaiHopesSeller($collection, 'sales_order_item.seller_code');

        return $collection;
    }

    /**
     * getHopesProductAttributesById
     *
     * @param  int $productId
     * @return array
     */
    public function getHopesProductAttributesById($productId)
    {
        $product = $this->productRepository->getById($productId);

        return [
            'FRCD' => $product->getData('hotai1_FRCD'),
            'PARTNO' => $product->getData('hotai1_PARTNO'),
            'EMPRTAX' => $product->getData('hotai1_EMPRTAX'),
        ];
    }

    public function getPkData($item)
    {
        $order = $this->rmaHelper->getOrder($item['order_id']);
        $attributes = $this->getHopesProductAttributesById($item['marketplace_rma_items.product_id']);
        $data = $order->getHotaiChildOrderNumber() .
            $attributes['FRCD'] .
            $attributes['PARTNO'];

        return trim($data) ?? 'blank';
    }

    /**
     * getNewestInvoiceLogByOrderId
     *
     * @param  int $orderId
     * @return mixed
     */
    public function getNewestInvoiceLogByOrderId($orderId)
    {
        $collection = $this->orderInvoiceLogCollectionFactory->create();
        $collection->addFieldToFilter("order_id", (string) $orderId)
            ->addFieldToFilter("status", [
                'in' =>
                [
                    HotaiOrderInvoiceLogs::CREATED,
                ],
            ]);
        $collection->addOrder('created_at', 'DESC');
        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    /**
     * getShippingNumber
     *
     * @param  int $rmaId
     * @return object | string | null
     */
    protected function getShippingNumber($rmaId)
    {
        $shippingRecord = $this->marketplaceRmaShippingFactory->create();
        $shippingRecord->addFieldToFilter("parent_id", $rmaId);
        $shippingRecord->addOrder('created_at', 'DESC');
        $result = $shippingRecord->getFirstItem();

        return empty($result->getEntityId()) ? '' : $result;
    }
}

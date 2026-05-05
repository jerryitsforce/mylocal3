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
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderInvoiceLogCollectionFactory;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory as DetailsCollection;
use \Branch8\Rma\Helper\Data;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Eav\Model\ResourceModel\Entity\Attribute;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use \Magento\Sales\Api\CreditmemoRepositoryInterface;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditMemoCollection;

class RmaVoidInvoiceManagement extends AbstractModel implements \Branch8\Hopes\Api\RmaVoidInvoiceManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'RmaVoidInvoiceManagement';

    const REQ_FROM = "From";
    const REQ_TO   = "To";

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

    /** @var OrderInvoiceLogCollectionFactory */
    protected $orderInvoiceLogCollectionFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $creditmemoCollection */
    protected $creditmemoCollection;

    /**　@var \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute */
    protected $attribute;

    /** @var \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository */
    protected $creditmemoRepository;

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
        DetailsCollection $details,
        OrderInvoiceLogCollectionFactory $orderInvoiceLogCollectionFactory,
        CreditMemoCollection $creditmemoCollection,
        Attribute $attribute,
        CreditmemoRepositoryInterface $creditmemoRepository,
        HopesLog $hopesLog

    ) {
        $this->rmaHelper                        = $rmaHelper;
        $this->orderCollectionFactory           = $orderCollectionFactory;
        $this->parentOrder                      = $parentOrder;
        $this->parentOrderInterface             = $parentOrderInterface;
        $this->productRepository                = $productRepository;
        $this->details                          = $details;
        $this->orderInvoiceLogCollectionFactory = $orderInvoiceLogCollectionFactory;
        $this->creditmemoCollection             = $creditmemoCollection;
        $this->attribute                        = $attribute;
        $this->creditmemoRepository             = $creditmemoRepository;
        $this->logger                           = $logger;
        $this->hopesLog                         = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getRmaVoidInvoice()
    {
        $this->hopesLog->log(self::LOG_TYPE, '[Start] RmaVoidInvoice Start');

        $body = $this->request->getBodyParams();
        $this->checkParams($this->requestArr, $body);

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($body, JSON_UNESCAPED_UNICODE));

        $collection = $this->getCheckOrderCollectionList($body[self::REQ_FROM], $body[self::REQ_TO]);

        foreach ($collection->getData() as $item) {

            try {
                $this->hopesLog->log(self::LOG_TYPE, '[CreditMemo Entity Id]' . $item['entity_id']);

                $orderId = $item['sales_order.hotai_child_order_number'];

                if (is_null($orderId)) {
                    $orderEntityId = $item['sales_order.entity_id'];
                    $this->hopesLog->log(self::LOG_TYPE, 'There is no hotai child order number. Sub-Order id:' . $orderEntityId);
                    continue;
                }

                if ($item['sales_order.status'] == \Branch8\HotaiCore\Model\Order\Status::STATUS_CANCELED
                    && ! $item['sales_order.ecpay_invoice_customer_identifier']) {

                    continue;
                }

                if (isset($this->result[$orderId])) {
                    $this->result[$orderId]['ITEMS'][] = $this->setItemData($item);
                    continue;
                }

                $this->result[$orderId]            = $this->setVoidInvoice($item);
                $this->result[$orderId]['ITEMS'][] = $this->setItemData($item);

            } catch (\Exception $e) {
                $this->hopesLog->logException(self::LOG_TYPE, $e, ['item' => $item]);
                continue;
            }

        }

        if (empty($this->result)) {
            $this->result = Message::getReturnMessageList(Message::RETURN_EMPTY_ARRAY);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));

        $this->setResponse($this->result);
    }

    public function setItemData($data)
    {
        $attributes = $this->getHopesProductAttributesById($data['sales_creditmemo_item.product_id']);

        return [
            'FRCD'      => $attributes['FRCD'],
            'PARTNO'    => $attributes['PARTNO'],
            'RTNORDQTY' => $data['sales_creditmemo_item.qty'],
        ];
    }

    /**
     * setReturnOrderData
     *
     * @param  array $data
     * @return array
     */
    public function setVoidInvoice($data)
    {
        $invoice = $this->getNewestInvoiceLogByOrderId((int) $data['sales_order.entity_id']);

        return [
            'SHIPBRNCD'   => 'SDEC',
            'B2CHDORDNO'  => $data['sales_order.hotai_child_order_number'],
            'RTNINVONO'   => empty($invoice) ? $data['sales_order.hotai_checkout_number'] : $invoice->getInvoiceNumber(),
            'RTNORDDT'    => $data['created_at'],
            'CURRENCY'    => 'NT',
            'INVSHEET'    => is_null($data['sales_order.ecpay_invoice_customer_identifier']) ? 2 : 3,
            'RTNINVPTPRC' => empty($invoice) ? 0 : $invoice->getIncludeTax(),
            'RTNINVPTTAX' => empty($invoice) ? 0 : $invoice->getTax(),
            'B2CUSTID'    => $data['sales_order.ecpay_invoice_customer_identifier'] ?? "",
            'RTNSHIPDT'   => $data['invoice_success_at'],
        ];
    }

    /**
     * getCheckOrderCollectionList 取得時間區間中退發票的紀錄
     *
     * @param  string $from
     * @param  string $to
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     */
    private function getCheckOrderCollectionList($from, $to)
    {

        $partNoAttributeId = $this->attribute->getIdByCode(
            \Magento\Catalog\Model\Product::ENTITY,
            'hotai1_PARTNO'
        );

        $collection = $this->creditmemoCollection
            ->join(
                ['sales_creditmemo_item' => 'sales_creditmemo_item'],
                'main_table.entity_id = sales_creditmemo_item.parent_id',
                [
                    'sales_creditmemo_item.product_id' => 'product_id',
                    'sales_creditmemo_item.qty'        => 'qty',
                ]
            )
            ->join(
                ['catalog_product_entity' => 'catalog_product_entity'],
                'sales_creditmemo_item.product_id = catalog_product_entity.entity_id',
                [
                    'catalog_product_entity.row_id' => 'row_id',
                ]
            )->join(
            ['catalog_product_entity_varchar' => 'catalog_product_entity_varchar'],
            'catalog_product_entity.row_id = catalog_product_entity_varchar.row_id',
            [
                'catalog_product_entity_varchar.value'    => 'value',
                'catalog_product_entity_varchar.store_id' => 'store_id',
            ]
        )
            ->join(
                ['sales_order' => 'sales_order'],
                'main_table.order_id = sales_order.entity_id',
                [
                    'sales_order.entity_id'                         => 'entity_id',
                    'sales_order.hotai_child_order_number'          => 'hotai_child_order_number',
                    'sales_order.increment_id'                      => 'increment_id',
                    'sales_order.ecpay_invoice_number'              => 'ecpay_invoice_number',
                    'sales_order.ecpay_invoice_customer_identifier' => 'ecpay_invoice_customer_identifier',
                    'sales_order.hotai_checkout_number'             => 'sales_order.hotai_checkout_number',
                    'sales_order.status'                            => 'sales_order.status',
                ]
            )->join(
            ['sales_order_item' => 'sales_order_item'],
            'sales_creditmemo_item.order_item_id = sales_order_item.item_id',
            [
                'sales_order_item.seller_code' => 'sales_order_item.seller_code',
            ]
        )
            ->addFieldToFilter(
                'catalog_product_entity_varchar.attribute_id', $partNoAttributeId)
            ->addFieldToFilter(
                'catalog_product_entity_varchar.store_id', 0)
            ->addFieldToFilter(
                'main_table.invoice_success_at', ['from' => $from, 'to' => $to]);

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
            'FRCD'    => $product->getData('hotai1_FRCD'),
            'PARTNO'  => $product->getData('hotai1_PARTNO'),
            'EMPRTAX' => $product->getData('hotai1_EMPRTAX'),
        ];
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
                    HotaiOrderInvoiceLogs::INVALID,
                    HotaiOrderInvoiceLogs::DISCOUNT,
                ],
            ]);
        $collection->addOrder('created_at', 'DESC');
        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    private function issetVoidInvoiceNumber($data)
    {
        $invoice = $this->getNewestInvoiceLogByOrderId((int) $data['sales_order.entity_id']);

        $invoiceNumber = empty($invoice) ? null : $invoice->getInvoiceNumber();
        return ! is_null($invoiceNumber) && ! empty($invoiceNumber);
    }
}

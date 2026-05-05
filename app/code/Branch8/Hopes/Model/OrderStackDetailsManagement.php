<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Model;

use Branch8\HifiSalesReport\Helper\Report as HifiReport;
use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Helper\Log as HopesLog;
use Branch8\Hopes\Helper\Response\Message;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Exception;
use Magento\Sales\Model\OrderFactory as Order;
use \Branch8\Shipping\Model\ShippingMethod;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Eav\Model\ResourceModel\Entity\Attribute;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use \Magento\Sales\Model\ResourceModel\Order\Address\Collection as AddressCollection;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory as StatusHistoryCollection;

class OrderStackDetailsManagement extends AbstractModel implements \Branch8\Hopes\Api\OrderStackDetailsManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'OrderStackDetailsManagement';

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

    /** @var \Branch8\HifiSalesReport\Helper\Report $hifiReport */
    public $hifiReport;

    /** @var array $configurable */
    public $configurable = [];

    /** @var array $checkPkItems */
    public $checkPkItems = [];

    /** @var array $requestArr */
    public $requestArr =
        [
        self::REQ_FROM,
        self::REQ_TO,
    ];

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepository;

    /** @var ParentOrder */
    protected $parentOrder;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderInterface */
    protected $parentOrderInterface;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $itemCollection　*/
    protected $itemCollection;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute */
    protected $attribute;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Address\Collection $addressCollection */
    protected $addressCollection;

    /** @var \Magento\Sales\Model\OrderFactory $order */
    protected $order;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $statusHistoryCollection */
    protected $statusHistoryCollection;

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
        HifiReport $hifiReport,
        ParentOrder $parentOrder,
        ParentOrderFactory $parentOrderInterface,
        ProductRepositoryInterface $productRepository,
        ItemCollection $itemCollection,
        Attribute $attribute,
        AddressCollection $addressCollection,
        Order $order,
        StatusHistoryCollection $statusHistoryCollection,
        HopesLog $hopesLog
    ) {
        $this->productRepository       = $productRepository;
        $this->orderCollectionFactory  = $orderCollectionFactory;
        $this->hifiReport              = $hifiReport;
        $this->parentOrder             = $parentOrder;
        $this->parentOrderInterface    = $parentOrderInterface;
        $this->itemCollection          = $itemCollection;
        $this->attribute               = $attribute;
        $this->addressCollection       = $addressCollection;
        $this->order                   = $order;
        $this->logger                  = $logger;
        $this->statusHistoryCollection = $statusHistoryCollection;
        $this->hopesLog                = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderStackDetails()
    {

        $body = $this->request->getBodyParams();
        $this->checkParams($this->requestArr, $body);

        $this->hopesLog->log(self::LOG_TYPE, '[Start] OrderStackDetailsManagement Start');

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($body, JSON_UNESCAPED_UNICODE));

        $collection = $this->getCheckOrderCollectionList($body[self::REQ_FROM], $body[self::REQ_TO]);

        foreach ($collection->getData() as $item) {
            try {

                if ($this->checkPkValue($this->getPkData($item), $this->checkPkItems)) {
                    continue;
                }

                if (! $this->statusHistoryCheck(
                    $item['sales_order_status_history.entity_id'],
                    $item['order_id'])
                ) {
                    continue;
                }

                $this->checkPkItems[] = $this->getPkData($item);

                $order = $this->order->create()->load($item['order_id']);
                if (is_null($order->getHotaiChildOrderNumber())) {
                    $this->hopesLog->log(self::LOG_TYPE, 'There is no hotai child order number. Sub-Order id:' . $order->getId());
                    continue;
                }

                $orderId = $order->getHotaiChildOrderNumber() ?? $order->getIncrementId();

                if (isset($this->result[$orderId])) {
                    $countItems                        = count($this->result[$orderId]['ITEMS']);
                    $this->result[$orderId]['ITEMS'][] = $this->setItemData($item, $countItems);
                    continue;
                }

                $this->result[$orderId]            = $this->setOrderData($order);
                $this->result[$orderId]['ITEMS'][] = $this->setItemData($item);
            } catch (Exception $e) {
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

    /**
     * getCheckOrderCollectionList
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

        $collection = $this->itemCollection
            ->join(
                ['sales_order' => 'sales_order'],
                'main_table.order_id = sales_order.entity_id',
                [
                    'sales_order.shipping_method' => 'shipping_method',
                    'sales_order.hotai_checkout_number'=> 'sales_order.hotai_checkout_number'
                ]
            )
            ->join(
                ['sales_order_status_history' => 'sales_order_status_history'],
                'sales_order.entity_id = sales_order_status_history.parent_id',
                [
                    'sales_order_status_history.created_at' => 'sales_order_status_history.created_at',
                    'sales_order_status_history.status'     => 'sales_order_status_history.status',
                    'sales_order_status_history.entity_id'  => 'sales_order_status_history.entity_id',
                ]
            )
            ->join(
                ['catalog_product_entity' => 'catalog_product_entity'],
                'main_table.product_id = catalog_product_entity.entity_id',
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
            ->addFieldToFilter(
                'sales_order_status_history.created_at', ['from' => $from, 'to' => $to])
            ->addFieldToFilter(
                'sales_order_status_history.status', Status::STATUS_TALLYING)
            ->addFieldToFilter(
                'catalog_product_entity_varchar.attribute_id', $partNoAttributeId)
            ->addFieldToFilter(
                'catalog_product_entity_varchar.store_id', 0)
            ->addFieldToFilter(
                'sales_order.shipping_method', [
                    'in' =>
                    [
                        ShippingMethod::METHOD_HOME,
                        ShippingMethod::METHOD_CONVENIENCE_STORE,
                    ],
                ]
            );

        $collection = $this->filterHotaiV1Data($collection, 'sales_order.increment_id');
        $collection = $this->filterHotaiHopesSeller($collection, 'main_table.seller_code');

        return $collection;
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
     * getPaycd
     *
     * @param  string $pointUsed
     * @param  string $price
     * @return string
     */
    public function getPaycd($pointUsed, $price)
    {
        //A: 點數、B: 現金、C: 點數+現金、D:員工價
        $pointUsed = $pointUsed ?? 0;
        $price     = $price ?? 0;

        if ($price == 0) {
            return 'A';
        } elseif ($pointUsed == 0) {
            return 'B';
        } else {
            return 'C';
        }

    }

    /**
     * setOrderData 設定 Order
     *
     * @param  object $data
     * @return array
     */
    public function setOrderData($data)
    {
        $invoice         = $this->hifiReport->getOldestInvoiceLogByOrderId((int) $data->getEntityId());
        $shippingAddress = $data->getShippingAddress();
        $storeId         = $shippingAddress->getData('cvs_store_code');
        $storeName       = $shippingAddress->getData('cvs_store_name');

        if (! $shippingAddress || ! empty($shippingAddress->getData('store_address_info'))) {
            $storeAddressInfo = json_decode($shippingAddress->getData('store_address_info') ?? '', true);
            $storeId          = $storeAddressInfo['storeid'] ?? '';
            $storeName        = $storeAddressInfo['storename'] ?? '';
        }

        return [
            'SHIPBRNCD'    => 'SDEC', //和勁出倉
            'B2CHDORDNO'   => $data->getHotaiChildOrderNumber() ?? $data->getIncrementId(),
            'B2USORD_SEQ'  => "0", // 固定 0
            'ORDDT'        => $data->getCreatedAt(),
            'MEMBER_ID'    => $data->getCustomerEmail(),
            'PAYTERM'      => 1,                                                 //信用卡付款
            'ORDERPTAX'    => empty($invoice) ? "0" : $invoice->getIncludeTax(), //總額
            'ACT_CASH'     => $data->getTotalPaid() ?? "0",                      //實付現金
            'ACT_POINT'    => $data->getPointUsedTotal() ?? "0",                 //實付點數
            'ACT_TAX'      => empty($invoice) ? "0" : $invoice->getTax(),        //實付稅額
            'CURRENCY'     => 'NT',                                              //台幣
            'INVSHEET'     => $this->getInvoiceType($data->getEcpayInvoiceCustomerIdentifier()),
            'SHIPTYPE'     => $this->getShipType($data->getShippingMethod()),
            'STOREID'      => $storeId,
            'STIRENAME'    => $storeName,
            'SHIPFEE'      => $data->getShippingInclTax(),
            'SHIPFEEPAY'   => 1, //現金支付運費
            'RECVNAME'     => $shippingAddress->getFirstName(),
            'RECVCITY1'    => $shippingAddress->getCity(),
            'RECVCITY2'    => $shippingAddress->getRegion(),
            'RECVADDRESS'  => trim($shippingAddress->getStreet() ? $shippingAddress->getStreet()[0] : ''),
            'RECVZIP'      => $shippingAddress->getPostCode(),
            'RECVTEL'      => $shippingAddress->getTelephone(),
            'RECVMOBILE'   => $shippingAddress->getTelephone(),
            'RECVEMAIL'    => $data->getCustomerEmail() ?? '',
            'INVOICE_NO'   => $data->getEcpayInvoiceNumber() ?? $data->getHotaiCheckoutNumber(),
            'CUSTID'       => $data->getEcpayInvoiceCustomerIdentifier() ?? '',
            'ORDERCOMMENT' => $this->getParentOrderCommentByOrderId($data->getEntityId()) ?? '',
            'SENDDT'       => date('Y-m-d H:i:s'),
            'PROMARK'      => '',

        ];
    }

    /**
     * setItemData
     *
     * @param  object|array $data
     * @return array
     */
    public function setItemData($data, $countItems = '0')
    {
        $attributes = $this->getHopesProductAttributesById($data['product_id']);

        $actCash = (int) $data['row_total_incl_tax'] 
                - (int) $data['row_total_point_used'] 
                - (int) $data['discount_amount'];

        return [
            'ITEM_NO'    => (string) $countItems,
            'FRCD'       => $attributes['FRCD'],   // 商品別
            'PARTNO'     => $attributes['PARTNO'], // 商品編號
            'PARTCUSTID' => 'EC00',                //電商四代碼
            'CARNO'      => "",                    //空值
            'ORDQTY'     => $data['qty_ordered'],
            'PAYCD'      => $this->getPaycd(
                $data['row_total_point_used'],
                $data['price']
            ),
            'ORDERTAX'   => $data['original_price'] ?? "0", //訂價
            'ACT_CASH'   => $actCash,
            'ACT_POINT'  => $data['row_total_point_used'] ?? "0", //實付點數
            'EMPRTAX'    => $attributes['EMPRTAX'] ?? "0",        //員工價
            'PROMARK'    => '',                                   //處理註記
        ];
    }

    /**
     * getParentOrderCommentByOrderId
     *
     * @param  int $orderId
     * @return string|null
     */
    public function getParentOrderCommentByOrderId($orderId)
    {
        $parentOrder = $this->getParentOrderByOrderId((int) $orderId);
        return $parentOrder->getOrderNote();
    }

    /**
     * getParentOrderByOrderId
     *
     * @param  int $orderId
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderInterface
     */
    public function getParentOrderByOrderId($orderId)
    {
        $parentOrderId = $this->parentOrder->getParentOrder($orderId);
        return $this->parentOrderInterface->create()->load($parentOrderId, 'index_id');

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
     * checkPkData
     *
     *  ([SHIPBRNCD],[B2CHDORDNO] ,[B2USORD_SEQ] ,[FRCD] ,[PARTNO] ,[PARTCUSTID]) 組成唯一鍵 不得重複
     *
     * @param  array $item
     * @return string
     */
    public function getPkData($item)
    {
        $attributes = $this->getHopesProductAttributesById($item['product_id']);
        $data       = $item['hotai_child_order_item_number'] .
            $attributes['FRCD'] .
            $attributes['PARTNO'];

        return trim($data) ?? 'blank';
    }
    
    /**
     * statusHistoryCheck
     *
     * @param  string|int $statusHistoryRecordId
     * @param  string|int $orderId
     * @return bool
     */
    public function statusHistoryCheck($statusHistoryRecordId, $orderId)
    {
        $collection = $this->statusHistoryCollection->create();
        $collection->addFieldToFilter('parent_id', $orderId)
            ->addFieldToFilter('status', Status::STATUS_TALLYING);

        if (! $collection->getSize()) {
            return false;
        }

        $item = $collection->getFirstItem();

        if ($item->getId() == $statusHistoryRecordId) {
            return true;
        }

        return false;
    }

}

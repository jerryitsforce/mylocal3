<?php
namespace Branch8\GiftToFriend\Ui\DataProvider;

use Psr\Log\LoggerInterface;
use Magento\Framework\Locale\Resolver\Proxy;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class SellerGiftOrder extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    private $timezone;
    private $localResolver;

    private LoggerInterface $logger;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Webkul\Marketplace\Model\ResourceModel\Orders\Collection $orderColl
     * @param \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory
     * @param \Webkul\Marketplace\Helper\Data $helperData
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $saleslistCollectionFactory
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Webkul\Marketplace\Model\ResourceModel\Orders\Collection $orderColl,
        \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory,
        \Webkul\Marketplace\Helper\Data $helperData,
        \Magento\Framework\App\RequestInterface $request,
        \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $saleslistCollectionFactory,
        TimezoneInterface $timezone,
        Proxy $locale,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    )
    {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->localResolver = $locale;
        $this->timezone = $timezone;
        $this->logger = $logger;

        $sellerId = $helperData->getCustomerId();
        $marketplaceSaleslist = $orderColl->getTable('marketplace_saleslist');
        $orderGridFlat = $orderColl->getTable('sales_order_grid');
        $salesOrderItem = $orderColl->getTable('sales_order_item');
        $shipmentTrack = $orderColl->getTable('sales_shipment_track');

        $collectionData = $collectionFactory->create()
            ->addFieldToSelect('seller_id')
            ->addFieldToSelect('order_id')
            ->addFieldToSelect('product_ids')
            ->addFieldToSelect('shipment_id')
            ->addFieldToSelect('invoice_id')
            ->addFieldToSelect('creditmemo_id')
            ->addFieldToSelect('is_canceled')
            ->addFieldToSelect('order_status')
            ->addFieldToSelect('shipping_charges')
            ->addFieldToSelect('carrier_name')
            ->addFieldToSelect('tracking_number')
            ->addFieldToSelect('updated_at')
            ->addFieldToSelect('tax_to_seller')
            ->addFieldToSelect('coupon_amount')
            ->addFieldToSelect('refunded_coupon_amount')
            ->addFieldToSelect('refunded_shipping_charges')
            ->addFieldToSelect('seller_pending_notification');

        $select = $collectionData->getSelect();
        $select->where('main_table.seller_id = ' . $sellerId)
            ->join(
            $marketplaceSaleslist . ' as ms',
            'main_table.order_id = ms.order_id AND main_table.seller_id = ms.seller_id',
            [
                "magerealorder_id" => "magerealorder_id",
                "magebuyer_id" => "magebuyer_id",
                "currency_rate" => "currency_rate",
                "paid_status" => "paid_status",
                "cpprostatus" => "cpprostatus",
                "SUM(ms.total_tax) AS total_tax",
                "magepro_sku" => "magepro_sku",
                "is_shipping" => "is_shipping",
                "total_commission" => "total_commission",
                "magepro_name" => "magepro_name"
            ]
        )->columns(
            [
                'SUM(actual_seller_amount) AS actual_seller_amount',
                'SUM(actual_seller_amount) AS purchased_actual_seller_amount',
                'SUM(applied_coupon_amount) AS applied_coupon_amount',
                'group_concat(order_item_id) AS order_item_ids'
            ]
        )->join(
            $orderGridFlat . ' as ogf',
            'main_table.order_id = ogf.entity_id',
            [
                "customer_name" => "customer_name",
                "shipping_phone" => "shipping_phone",
                "customer_phone_number" => "customer_phone_number",
                "rma_status" => "rma_status",
                "status" => "status",
                "created_at" => "created_at",
                "order_currency_code" => "order_currency_code",
                "base_currency_code" => "base_currency_code",
                "base_grand_total" => "base_grand_total",
                "grand_total" => "grand_total",
                "quote_id" => "quote_id",
                "shipping_and_handling" => "shipping_and_handling",
                "shipping_name" => "shipping_name",
                "subtotal" => "subtotal",
                "point_used_total"=>"point_used_total",
                "total_invoiced"=>"total_invoiced",
                'is_flagship_store_process_order' => 'is_flagship_store_process_order',
                'all_item_skus' => 'all_item_skus',
                "subtotal_incl_tax"=>"subtotal_incl_tax",
                'shipping_address' => 'shipping_address',
                'is_gift_confirmed' => 'is_gift_confirmed',
                'recipient_name' => 'recipient_name',
                'recipient_telephone' => 'recipient_telephone',
                'gift_confirmed_at' => 'gift_confirmed_at'
            ]
        )->joinLeft(
            ['cgf_g' => 'customer_grid_flat'],
            'ogf.customer_id = cgf_g.entity_id',
            [
                'buyer_phone_number' => 'phone_number'
            ]
        )->where('ogf.order_approval_status = 1');

        $select->where("ogf.is_gift_order = 1");
        $select->group('ms.order_id');

        if ($buyerId = $request->getParam('customer_id')) {
            $collectionOrders = $saleslistCollectionFactory->create()
                ->addFieldToFilter('seller_id', ['eq' => $sellerId])
                ->addFieldToSelect('order_id')
                ->distinct(true);

            $buyerIds = $collectionOrders->getAllBuyerIds();

            if (in_array($buyerId, $buyerIds)) {
                $collectionData->getSelect()->where('ms.magebuyer_id = ' . $buyerId);
            }
        }

        $this->collection = $collectionData;
    }


    public function addOrder($field, $direction)
    {
        if ($field == 'created_at') {
            $field = 'ogf.created_at';
        }
        $this->getCollection()->addOrder($field, $direction);
    }

    /**
     * @param $offset
     * @param $size
     * @return void
     */
    public function setLimit($offset, $size)
    {
        parent::setLimit($offset, $size);
    }

    /**
     * @param \Magento\Framework\Api\Filter $filter
     * @return void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() === 'created_at') {
            $filter->setField('ogf.created_at');
        }
        if ($filter->getField() === 'entity_id') {
            $filter->setField('ogf.entity_id');
        }

        if ($filter->getField() == 'rma_status') {
            $filter->setField('ogf.rma_status');

        }
        if ($filter->getField() == 'point_used_total') {
            $filter->setField('ogf.point_used_total');

        }
        if ($filter->getField() == 'total_invoiced') {
            $filter->setField('ogf.total_invoiced');

        }
        if ($filter->getField() == 'subtotal_incl_tax') {
            $filter->setField('ogf.subtotal_incl_tax');

        }
        if ($filter->getField() == 'shipping_incl_tax') {
            $filter->setField('ogf.shipping_incl_tax');

        }

        // @phpstan-ignore-next-line as adding return statement cause of backward compatibility issue
        parent::addFilter($filter);
    }

    public function getAllIds()
    {
        $collection = $this->getCollection();
        $ids = $collection->getAllIds();

        return $ids;
    }

    /**
     * @return array
     */
    public function getAllOrderIds()
    {
        $collection = $this->getCollection();
        $orderIds = [];
        foreach ($collection->getItems() as $item) {
            $orderIds[$item->getOrderId()] = $item->getOrderId();
        }
        return $orderIds;
    }
}

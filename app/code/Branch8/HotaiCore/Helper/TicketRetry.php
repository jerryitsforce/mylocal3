<?php

namespace Branch8\HotaiCore\Helper;

use Branch8\HotaiCore\Model\Config\Source\LogOption;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;
use \Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Model\Product\VirtualProductType;

class TicketRetry
{
    const LOG_FOLDER_NAME = 'HotaiCore/Helper/TicketRetry';

    private const DEBUG_LOG_OPTION = LogOption::LOG_TICKET_RETRY;

    const HIGH_FREQUENCY_TICKET_RETRY_COUNT = 10;
    const MEDIUM_FREQUENCY_TICKET_RETRY_COUNT = 2;
    const LOW_FREQUENCY_TICKET_RETRY_COUNT = 23;

    const MAX_TICKET_RETRY_COUNT = self::HIGH_FREQUENCY_TICKET_RETRY_COUNT + self::MEDIUM_FREQUENCY_TICKET_RETRY_COUNT + self::LOW_FREQUENCY_TICKET_RETRY_COUNT;

    const DEFAULT_ORDER_SELECT_FIELDS = [
        'entity_id',
        'state',
        'status',
        'increment_id'
    ];

    const DEFAULT_ORDER_ITEM_SELECT_FIELDS = [
        'item_id',
        'order_id',
        'product_id',
        'product_type',
        'product_options',
        'sku',
        'name',
        'qty_ordered',
        'ticket_retry_count',
        'ticket_retry_status',
        'created_at',
    ];

    /** @var OrderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    public function __construct(
        OrderItemCollectionFactory $orderItemCollectionFactory,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    public function getNeedRetryOrderItemCollection(int $virtualProductType, array $orderSelectFields = [], array $orderItemSelectFields = []): OrderItemCollection
    {
        $collection = $this->orderItemCollectionFactory->create();

        $finalOrderSelectFields = empty($orderSelectFields) ? self::DEFAULT_ORDER_SELECT_FIELDS : $orderSelectFields;
        $finalOrderItemSelectFields = empty($orderItemSelectFields) ? self::DEFAULT_ORDER_ITEM_SELECT_FIELDS : $orderItemSelectFields;

        $collection
            ->join(
                ['sales_order' => 'sales_order'],
                'main_table.order_id = sales_order.entity_id',
                $finalOrderSelectFields
            );

        $collection->addFieldToSelect($finalOrderItemSelectFields);

        $collection->addFieldToFilter(
            'sales_order.status',
            [
                'nin' => [
                    OrderStatus::STATUS_CANCELED,
                    OrderStatus::STATUS_CANCEL_PENDING,
                    OrderStatus::STATUS_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE,
                ]
            ]
        );

        $collection->addFieldToFilter("ticket_retry_status", TicketRetryStatus::STATUS_NEED_RETRY);

        $oneDayAgo = (new \DateTime('-1 day', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $collection->addFieldToFilter('main_table.created_at', ['gteq' => $oneDayAgo]);

        $collection->addFieldToFilter(
            'ticket_retry_count',
            [
                'lt' => self::MAX_TICKET_RETRY_COUNT
            ]
        );

        $collection->getSelect()->where(
            "JSON_EXTRACT(main_table.product_options, '$.virtual_product_type') = ?",
            $virtualProductType
        );

        return $collection;
    }

    public function checkIfRetryShouldExecuteByFrequencyRule(OrderItem $orderItem): bool
    {
        $retryCount = $orderItem->getData('ticket_retry_count');
        $createdAt = $orderItem->getData('created_at');

        if ($retryCount < self::HIGH_FREQUENCY_TICKET_RETRY_COUNT) {
            return true;
        }

        try {
            $createdAtObj = new \DateTime($createdAt, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLogIfEnabled(
                json_encode([
                    "Title"         => "Ticket retry helper::checkIfRetryShouldExecuteByFrequencyRule - Exception.",
                    "Order ID"      => $orderItem->getOrderId(),
                    "Order item ID" => $orderItem->getId(),
                    "Created at"    => $createdAt,
                    "Exception"     => $e->getMessage()
                ]),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        // lowest retry count will be 10 here.
        $mediumRetryCap = self::HIGH_FREQUENCY_TICKET_RETRY_COUNT + self::MEDIUM_FREQUENCY_TICKET_RETRY_COUNT;
        if (self::HIGH_FREQUENCY_TICKET_RETRY_COUNT <= $retryCount && $retryCount < $mediumRetryCap) {
            $intervalMinutes = ($retryCount - self::HIGH_FREQUENCY_TICKET_RETRY_COUNT) * 30;
            $intervalSpec = 'PT' . $intervalMinutes . 'M'; // ISO-8601 間隔格式
            $createdAtObj->add(new \DateInterval($intervalSpec));

            $executeChecker = $createdAtObj <= $now;

            return $executeChecker;
        }

        // lowest retry count will be 12 here.
        $lowRetryCap = $mediumRetryCap + self::LOW_FREQUENCY_TICKET_RETRY_COUNT;
        if ($mediumRetryCap <= $retryCount && $retryCount < $lowRetryCap) {
            $intervalMinutes = ($retryCount - $mediumRetryCap + 1) * 60;
            $intervalSpec = 'PT' . $intervalMinutes . 'M'; // ISO-8601 間隔格式
            $createdAtObj->add(new \DateInterval($intervalSpec));

            $executeChecker = $createdAtObj <= $now;

            return $executeChecker;
        }

        return false;
    }
}

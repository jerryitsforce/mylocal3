<?php

namespace Branch8\HotaiOrderNumber\Model\Actions;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class HotaiGenerateIncrementIdForOrders
{
    const RANDOM_CHARACTERS_POOL = "ABCEFGHJKLNPQRSTUVWXYZ0123456789";
    const RANDOM_CHARACTERS_COUNT = 4;
    const RANDOM_RETRY_LIMIT = 10;

    const PADDING_COUNT_HOTAI_PARENT_ORDER_NUMBER = 5;
    const PADDING_COUNT_HOTAI_CHILD_ORDER_NUMBER = 2;
    const PADDING_COUNT_HOTAI_CHILD_ORDER_ITEM_NUMBER = 4;
    const FIELD_NAME_HOTAI_PARENT_ORDER_NUMBER = "hotai_parent_order_number";
    const FIELD_NAME_HOTAI_CHILD_ORDER_NUMBER = "hotai_child_order_number";
    const FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER = "hotai_child_order_item_number";
    const ORDER_NUMBER_SEPARATOR = "_";
    private $parentOrderDetailFactory;
    private LoggerInterface $logger;
    private OrderRepository $orderRepository;
    private Transaction $transaction;

    /**
     * @var Random
     */
    private Random $random;
    private TimezoneInterface $timezone;

    /**
     * @param ParentOrderDetailFactory $parentOrderDetailFactory
     * @param OrderRepository $orderRepository
     * @param LoggerInterface $logger
     * @param Transaction $transaction
     * @param Random $random
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        ParentOrderDetailFactory $parentOrderDetailFactory,
        OrderRepository          $orderRepository,
        LoggerInterface          $logger,
        Transaction              $transaction,
        Random                   $random,
        TimezoneInterface $timezone
    ) {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->parentOrderDetailFactory = $parentOrderDetailFactory;
        $this->transaction = $transaction;
        $this->random = $random;
        $this->timezone = $timezone;
    }

    /**
     * @param \Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder
     * @return string
     * @throws \Exception
     */
    public function generateForParentOrder(\Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder): string
    {
        $hotaiParentOrderNumber = "";
        $mpSplitOrderId = $parentOrder->getId() ? $parentOrder->getId() : $parentOrder->getIndexId();
        if (empty($mpSplitOrderId)) {
            throw new LocalizedException(__('Empty parent order number.'));
        }
        for ($i = 1; $i <= self::RANDOM_RETRY_LIMIT; $i++) {
            $dateString = $this->timezone->date()->format('ymdH');
            //$dateString = date("ymdH");
            $randomCharacters = $this->random->getRandomString(self::RANDOM_CHARACTERS_COUNT, self::RANDOM_CHARACTERS_POOL);
            $lastFiveMpSplitOrderId = substr($mpSplitOrderId, -5);
            $paddingMpSplitOrderId = str_pad($lastFiveMpSplitOrderId, self::PADDING_COUNT_HOTAI_PARENT_ORDER_NUMBER, "0", STR_PAD_LEFT);
            $hotaiParentOrderNumber = $dateString . self::ORDER_NUMBER_SEPARATOR . $randomCharacters . $paddingMpSplitOrderId;
            $parentOrderDetail = $this->parentOrderDetailFactory->create()->load($hotaiParentOrderNumber, self::FIELD_NAME_HOTAI_PARENT_ORDER_NUMBER);
            if (empty($parentOrderDetail->getEntityId())) {
                break;
            }
            if ($i >= self::RANDOM_RETRY_LIMIT) {
                throw new \Exception("Hit random characters retry limit for hotai parent order number.");
            }
        }
        if (empty($hotaiParentOrderNumber)) {
            throw new LocalizedException(__('Empty Parent Order Number Generation'));
        }
        return $hotaiParentOrderNumber;
    }

    /**
     * @param ParentOrder $parentOrder
     * @param Order $childOrder
     * @param $childOrderSequenceNumber
     * @return string
     */
    public function generateForSubOrder(\Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder, $childOrderSequenceNumber = 1): string
    {
        /** @var \Magento\Sales\Model\Order $childOrder */
        $hotaiChildOrderNumber = $parentOrder->getData('hotai_reserved_order_id') . str_pad($childOrderSequenceNumber,
                self::PADDING_COUNT_HOTAI_CHILD_ORDER_NUMBER, "0", STR_PAD_LEFT);
        return $hotaiChildOrderNumber;
    }

    /**
     * @param Order $childOrder
     * @param Order\Item $childOrderItem
     * @return string
     */
    public function generateForOrderItem(Order $childOrder, \Magento\Sales\Model\Order\Item $childOrderItem)
    {
        return $childOrder->getIncrementId() . self::ORDER_NUMBER_SEPARATOR . str_pad(
                $childOrderItem->getId(),
                self::PADDING_COUNT_HOTAI_CHILD_ORDER_ITEM_NUMBER, "0",
                STR_PAD_LEFT
            );
    }
}

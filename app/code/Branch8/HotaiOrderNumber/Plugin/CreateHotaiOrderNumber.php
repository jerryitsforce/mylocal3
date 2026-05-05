<?php

namespace Branch8\HotaiOrderNumber\Plugin;

use Branch8\HotaiCore\Helper\Common as CommonHelper;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Branch8\MarketPlaceParentOrder\Observer\PopulateParentOrderData;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Math\Random;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class CreateHotaiOrderNumber
{
    const LOG_FOLDER_NAME = 'HotaiOrderNumber/Plugin/CreateHotaiOrderNumber';

    const FIELD_NAME_HOTAI_PARENT_ORDER_NUMBER     = "hotai_parent_order_number";
    const FIELD_NAME_HOTAI_CHILD_ORDER_NUMBER      = "hotai_child_order_number";
    const FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER = "hotai_child_order_item_number";

    const ORDER_NUMBER_SEPARATOR = "_";

    const RANDOM_CHARACTERS_POOL  = "ABCEFGHJKLNPQRSTUVWXYZ0123456789";
    const RANDOM_CHARACTERS_COUNT = 4;
    const RANDOM_RETRY_LIMIT      = 10;

    const PADDING_COUNT_HOTAI_PARENT_ORDER_NUMBER     = 5;
    const PADDING_COUNT_HOTAI_CHILD_ORDER_NUMBER      = 2;
    const PADDING_COUNT_HOTAI_CHILD_ORDER_ITEM_NUMBER = 4;

    /** @var ParentOrderDetailFactory */
    protected $parentOrderDetailFactory;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var CommonHelper */
    protected $commonHelper;

    protected $hotaiParentOrderNumber;

    /**
     * @var Random
     */
    private Random $random;

    /**
     * @param ParentOrderDetailFactory $parentOrderDetailFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param Transaction $transaction
     * @param CommonHelper $commonHelper
     * @param Random $random
     */
    public function __construct(
        ParentOrderDetailFactory $parentOrderDetailFactory,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        Transaction $transaction,
        CommonHelper $commonHelper,
        Random $random
    ) {
        $this->parentOrderDetailFactory = $parentOrderDetailFactory;
        $this->orderRepository          = $orderRepository;
        $this->orderItemRepository      = $orderItemRepository;
        $this->transaction              = $transaction;
        $this->commonHelper             = $commonHelper;
        $this->random = $random;
    }

    public function afterExecute(PopulateParentOrderData $subject, $result, Observer $observer)
    {
        try {
            if (!$this->isMpSplitOrderNew($observer)) {
                return;
            }

            $data = $observer->getData('data_object');

            $parentOrderDetail = $this->parentOrderDetailFactory->create()->load($data->getIndexId(), 'parent_id');

            if (!empty($parentOrderDetail->getData(self::FIELD_NAME_HOTAI_PARENT_ORDER_NUMBER))) {
                return;
            }

            $this->hotaiParentOrderNumber = $this->generateHotaiParentOrderNumber($data->getIndexId());

            $this->setHotaiParentOrderNumberInTransaction($parentOrderDetail);

            $this->setHotaiChildtOrderAndChildOrderItemNumberInTransaction($data->getOrderIds());

            $this->transaction->save();

            return $result;
        } catch (\Exception $e) {
            $this->commonHelper->writeLog("Exception message in CreateHotaiOrderNumber afterExcute: " . $e->getMessage(), self::LOG_FOLDER_NAME);
        }
    }

    /**
     * 確認觸發observer的是不是MpSplitOrder的新紀錄
     *
     * @param Observer $observer
     * @return boolean
     */
    protected function isMpSplitOrderNew(Observer $observer): bool
    {
        $dataObject = $observer->getData('data_object');

        return ($dataObject && $dataObject->isObjectNew() && $dataObject->getId());
    }

    /**
     * 依照規則產出hotai_parent_order_number(和泰主訂單編號)
     *
     * @param string $mpSplitOrderId
     * @return string
     */
    protected function generateHotaiParentOrderNumber(string $mpSplitOrderId): string
    {
        $hotaiParentOrderNumber = "";

        for ($i = 1; $i <= self::RANDOM_RETRY_LIMIT; $i++) {
            $hotaiParentOrderNumber = "";

            $dateString = date("ymdH");

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

        return $hotaiParentOrderNumber;
    }

    /**
     * 將hotai_parent_order_number(和泰主訂單編號)放入transaction準備提交
     *
     * @param ParentOrderDetail $parentOrderDetail
     * @return void
     */
    protected function setHotaiParentOrderNumberInTransaction(ParentOrderDetail $parentOrderDetail): void
    {
        $parentOrderDetail->setData(self::FIELD_NAME_HOTAI_PARENT_ORDER_NUMBER, $this->hotaiParentOrderNumber);

        $this->transaction->addObject($parentOrderDetail);
    }

    /**
     * 依照規則產出hotai_child_order_number(和泰子訂單編號)和hotai_child_order_item_number(和泰孫訂單編號)並放入transaction準備提交
     *
     * @param string $childOrderIds
     * @return void
     */
    protected function setHotaiChildtOrderAndChildOrderItemNumberInTransaction(string $childOrderIds): void
    {
        $childOrderSequenceNumber = 1;
        $childOrderIdArray        = explode(",", $childOrderIds);

        foreach ($childOrderIdArray as $childOrderId) {
            /** @var \Magento\Sales\Model\Order $childOrder */
            $childOrder            = $this->orderRepository->get($childOrderId);
            $hotaiChildOrderNumber = $this->hotaiParentOrderNumber . str_pad($childOrderSequenceNumber, self::PADDING_COUNT_HOTAI_CHILD_ORDER_NUMBER, "0", STR_PAD_LEFT);

            foreach ($childOrder->getAllVisibleItems() as $item) {
                /** @var \Magento\Sales\Model\Order\Item $childOrderItem */
                $childOrderItem            = $this->orderItemRepository->get($item->getId());
                $hotaiChildOrderItemNumber = $hotaiChildOrderNumber . self::ORDER_NUMBER_SEPARATOR . str_pad($childOrderItem->getId(), self::PADDING_COUNT_HOTAI_CHILD_ORDER_ITEM_NUMBER, "0", STR_PAD_LEFT);

                $childOrderItem->setData(self::FIELD_NAME_HOTAI_CHILD_ORDER_ITEM_NUMBER, $hotaiChildOrderItemNumber);
                $this->transaction->addObject($childOrderItem);
            }

            $childOrder->setData(self::FIELD_NAME_HOTAI_CHILD_ORDER_NUMBER, $hotaiChildOrderNumber);
            $this->transaction->addObject($childOrder);

            $childOrderSequenceNumber++;
        }
    }
}

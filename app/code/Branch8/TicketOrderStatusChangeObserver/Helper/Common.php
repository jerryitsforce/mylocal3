<?php

namespace Branch8\TicketOrderStatusChangeObserver\Helper;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Branch8\Sales\Model\CreditMemo\FinancialReviewStatus;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\Collection as CustomerTicketCollection;
use Branch8\HotaiCore\Model\Order\State as OrderState;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class Common
{
    const COMPLETE_STATUS = [
        TicketStatus::STATUS_USED,
        TicketStatus::STATUS_OVER_DUE
    ];

    /** @var CreditmemoCollectionFactory */
    protected $creditmemoCollectionFactory;

    public function __construct(
        CreditmemoCollectionFactory $creditmemoCollectionFactory
    ) {
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
    }

    public function isTicketOrder(Order $order): bool
    {
        /** @var OrderItem $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $type = $item->getProductOptionByCode(VirtualProductType::ATTRIBUTE_CODE);

            if (!is_null($type) && in_array($type, VirtualProductType::TYPES_TICKET)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根據sales_order_item.product_options的virtual_product_type來判斷是否為票券商品
     * 一般來說訂單成立後Branch8\Sales\Observer\SetOrderItemProductOptions會把virtual_product_type的index建立進去
     * 儘管值可能是空的
     *
     * 但是純點訂單成立後=>立刻開立發票=>執行點數票券後續流程這個過程是連貫的
     * SetOrderItemProductOptions可能來不及把virtual_product_type寫入product_options
     * 所以在取不到virtual_product_type這個index的狀況下直接連到product去取virtual_product_type的值來做判斷
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function isTicketItem(OrderItem $item): bool
    {
        $productOptions = $item->getProductOptions();

        if (isset($productOptions[VirtualProductType::ATTRIBUTE_CODE])) {
            $typeFromOption = $productOptions[VirtualProductType::ATTRIBUTE_CODE];

            return !is_null($typeFromOption) && in_array($typeFromOption, VirtualProductType::TYPES_TICKET);
        }

        $typeFromProduct = $item->getProduct()->getVirtualProductType();

        return !is_null($typeFromProduct) && in_array($typeFromProduct, VirtualProductType::TYPES_TICKET);
    }

    public function getNotReturnedItemIdsFromOrder(Order $order): array
    {
        $itemIdsArray = [];

        /** @var OrderItem $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if ($this->checkIsReturnedItem($item)) {
                continue;
            }

            $itemIdsArray[] = $item->getId();
        }

        return $itemIdsArray;
    }

    /**
     * TODO: 目前只要查到紀錄就算完整退貨, 以後可能要計算退貨數量
     * @param OrderItem $item
     * @return bool
     */
    public function checkIsReturnedItem(OrderItem $item): bool
    {
        $itemId = $item->getId();

        $collection = $this->creditmemoCollectionFactory->create();
        $collection->join(
            ['sales_creditmemo_item' => 'sales_creditmemo_item'],
            'main_table.entity_id = sales_creditmemo_item.parent_id',
            [
                'order_item_id' => 'order_item_id'
            ]
        );

        $collection->addFieldToFilter(
            'main_table.financial_review_status',
            ['in' => [FinancialReviewStatus::DEFAULT , FinancialReviewStatus::FINANCIAL_REVIEW_SUCCESS]]
        );
        $collection->addFieldToFilter(
            'sales_creditmemo_item.order_item_id',
            ['eq' => $itemId]
        );

        return $collection->getSize() > 0;
    }

    public function isCreditmemoNeedFinancialReview(Creditmemo $creditmemo): bool
    {
        return $creditmemo->getData("financial_review_status") != FinancialReviewStatus::DEFAULT;
    }

    public function checkIfTicketCollectionAllMatchCompleteStatus(CustomerTicketCollection $collection): bool
    {
        /** @var CustomerTicket $ticket */
        foreach ($collection as $ticket) {
            if ($this->checkIfCustomerTicketStatusUnusedButTimeOverdue($ticket)) {
                continue;
            }

            if (in_array($ticket->getStatus(), self::COMPLETE_STATUS)) {
                continue;
            }

            return false;
        }

        return true;
    }

    public function checkIfCustomerTicketStatusUnusedButTimeOverdue(CustomerTicket $customerTicket): bool
    {
        $status = $customerTicket->getStatus();
        $cond1  = $status == TicketStatus::STATUS_UNUSED;

        $useEndTime      = $customerTicket->getUseEndTime();
        $useEndTimeObj   = new \DateTime($useEndTime, new \DateTimeZone('Asia/Taipei'));
        $useEndTimeStamp = $useEndTimeObj->getTimestamp();
        $cond2           = time() > $useEndTimeStamp;

        return $cond1 && $cond2;
    }

    public function getOrderStateForTicketAllUsed(): string
    {
        return OrderState::STATE_PENDING_COMPLETE;
    }

    public function getOrderStatusForTicketAllUsed(): string
    {
        return OrderStatus::STATUS_PENDING_COMPLETE;
    }

    public function getItemFlowStatusForTicketAllUsed(): string
    {
        return OrderStatus::STATUS_PENDING_COMPLETE;
    }
}
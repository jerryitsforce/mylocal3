<?php

namespace Branch8\Edenred\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\Edenred\Helper\Common as CommonHelper;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\Edenred\Model\EdenredTicketRecord;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\CollectionFactory as EdenredCollectionFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicketCollectionFactory;
use Branch8\Edenred\Helper\Api as ApiHelper;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Branch8\Edenred\Model\Config\Source\LogOption as EdenredLogOption;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\HotaiCore\Helper\TicketRetry as TicketRetryHelper;
use Branch8\GiftToFriend\Helper\Order as GiftToFriendHelper;
use Magento\Sales\Model\OrderRepository;

class TicketRequestRetry
{
    const LOG_FOLDER_NAME = 'Edenred/Cron/TicketRequestRetry';
    const LOG_OPTION_VALUE = EdenredLogOption::LOG_OPTION_VALUE_TICKET_REQUEST_RETRY;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var VirtualProductHelper */
    protected $virtualProductHelper;

    /** @var EdenredCollectionFactory */
    protected $edenredCollectionFactory;

    /** @var CustomerTicketCollectionFactory */
    protected $customerTicketCollectionFactory;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    /** @var EventManager */
    protected $eventManager;

    /** @var TicketRetryHelper */
    protected $ticketRetryHelper;

    /** @var GiftToFriendHelper */
    protected $giftToFriendHelper;

    /** @var OrderRepository */
    protected $orderRepository;

    protected $orderIdsForArrivedCheckerEvent = [];

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderItemRepository $orderItemRepository,
        VirtualProductHelper $virtualProductHelper,
        EdenredCollectionFactory $edenredCollectionFactory,
        CustomerTicketCollectionFactory $customerTicketCollectionFactory,
        ApiHelper $apiHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        EventManager $eventManager,
        TicketRetryHelper $ticketRetryHelper,
        GiftToFriendHelper $giftToFriendHelper,
        OrderRepository $orderRepository
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->commonHelper                    = $commonHelper;
        $this->orderItemCollectionFactory      = $orderItemCollectionFactory;
        $this->orderItemRepository             = $orderItemRepository;
        $this->virtualProductHelper            = $virtualProductHelper;
        $this->edenredCollectionFactory        = $edenredCollectionFactory;
        $this->customerTicketCollectionFactory = $customerTicketCollectionFactory;
        $this->apiHelper                       = $apiHelper;
        $this->edenredTicketRecordRepository   = $edenredTicketRecordRepository;
        $this->eventManager                    = $eventManager;
        $this->ticketRetryHelper               = $ticketRetryHelper;
        $this->giftToFriendHelper              = $giftToFriendHelper;
        $this->orderRepository                 = $orderRepository;
    }

    public function execute()
    {
        $this->commonHelper->writeLogIfEnabled(json_encode([
            "Message" => "TicketRequestRetry cron start.",
        ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

        $itemCollection = $this->ticketRetryHelper->getNeedRetryOrderItemCollection(VirtualProductType::TYPE_EDENRED_TICKET);
        $itemObjArray   = $itemCollection->getItems();

        if (empty($itemObjArray)) {
            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Message" => "No order items to handle, TicketRequestRetry cron end.",
            ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            return;
        }

        $itemObjArray = $this->removeItemsByFrequencyRule($itemObjArray);

        if (empty($itemObjArray)) {
            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Message" => "No order items to handle after remove items by frequency rule, TicketRequestRetry cron end.",
            ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            return;
        }

        $orderItemDataArray = [];
        foreach ($itemObjArray as $item) {
            $orderItemDataArray[] = [
                "Order ID"            => $item->getOrderId(),
                "Order item ID"       => $item->getId(),
                "Ticket retry count"  => $item->getData('ticket_retry_count'),
                "Ticket retry status" => $item->getData('ticket_retry_status'),
                "Created at"          => $item->getData('created_at')
            ];
        }

        $this->commonHelper->writeLogIfEnabled(json_encode([
            "Message"        => "Ready to handle order items.",
            "Order item data" => $orderItemDataArray
        ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

        /** @var OrderItem $item */
        foreach ($itemObjArray as $item) {
            if ($this->checkIfRecordAlreadyExist($item)) {
                $this->commonHelper->writeLogIfEnabled(json_encode([
                    "Order ID"      => $item->getOrderId(),
                    "Order item ID" => $item->getId(),
                    "Message"       => "Ticket record already exist so update retry status to error than skip."
                ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

                $this->updateForRecordExists($item);
                continue;
            }

            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Title" => "Ready to retry ticket.",
                "Current dateTime(+0)" => $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject(null, 'UTC')->format('Y-m-d H:i:s'),
                "Order ID" => $item->getOrderId(),
                "Order item ID" => $item->getId(),
                "Created at(+0)" => $item->getData('created_at'),
                "Ticket retry count" => $item->getData('ticket_retry_count'),
                "Ticket retry status" => $item->getData('ticket_retry_status'),
            ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            try {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderRepository->get($item->getOrderId());

                if ($this->giftToFriendHelper->isGiftOrder($order) && !$this->giftToFriendHelper->isGiftOrderConfirmed($order)) {
                    $this->commonHelper->writeLogIfEnabled(json_encode([
                        "Order ID"      => $item->getOrderId(),
                        "Order item ID" => $item->getId(),
                        "Message"       => "Gift order is not confirmed so skip."
                    ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

                    $this->updateForRetryException($item);
                    continue;
                }

                $customOwner = $this->giftToFriendHelper->resolveCustomOwnerForGiftOrder($order->getId());

                $apiResponse = $this->apiHelper->requestApiGetMultiVouchers($item->getId());
                $this->edenredTicketRecordRepository->storeVoucherDataInDatabase($apiResponse, $item, $customOwner);
            } catch (\Throwable $th) {
                $this->commonHelper->writeLogIfEnabled(json_encode([
                    "Order ID"          => $item->getOrderId(),
                    "Order item ID"     => $item->getId(),
                    "Message"           => "Something went wrong while requesting ticket.",
                    "Exception message" => $th->getMessage()
                ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

                $this->updateForRetryException($item);
                continue;
            }

            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Order ID"      => $item->getOrderId(),
                "Order item ID" => $item->getId(),
                "Message"       => "Retry success."
            ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            $this->updateForRetrySuccess($item);
            $this->addOrderIdForArrivedCheckerEvent($item);

            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Order ID"      => $item->getOrderId(),
                "Order item ID" => $item->getId(),
                "Message"       => "Retry success handle done."
            ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);
        }

        $this->commonHelper->writeLogIfEnabled(json_encode([
            "Message"   => "Ready to fire arrived checker event with order IDs.",
            "Order IDs" => array_keys($this->orderIdsForArrivedCheckerEvent)
        ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

        $this->fireArrivedCheckerEvent();

        $this->commonHelper->writeLogIfEnabled(json_encode([
            "Message" => "TicketRequestRetry cron end.",
        ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);
    }

    protected function removeItemsByFrequencyRule(array $itemObjArray): array
    {
        foreach ($itemObjArray as $key => $item) {
            if (!$this->ticketRetryHelper->checkIfRetryShouldExecuteByFrequencyRule($item)) {
                unset($itemObjArray[$key]);
                continue;
            }
        }

        return $itemObjArray;
    }

    protected function checkIfRecordAlreadyExist(OrderItem $item): bool
    {
        $edenredCollection = $this->edenredCollectionFactory->create();
        $edenredCollection->addFieldToSelect([
            EdenredTicketRecord::RECORD_ID,
            EdenredTicketRecord::SALES_ORDER_ITEM_ID,
        ]);
        $edenredCollection->addFieldToFilter(EdenredTicketRecord::SALES_ORDER_ITEM_ID, $item->getId());
        $edenredCollection->addFieldToFilter(EdenredTicketRecord::STATUS, TicketStatus::STATUS_UNUSED);

        if ($edenredCollection->getSize() > 0) {
            return true;
        }
        // -----------------

        $customerTicketCollection = $this->customerTicketCollectionFactory->create();
        $customerTicketCollection->addFieldToSelect([
            CustomerTicket::RECORD_ID,
            CustomerTicket::SALES_ORDER_ITEM_ID
        ]);
        $customerTicketCollection->addFieldToFilter(CustomerTicket::TYPE, VirtualProductType::TYPE_EDENRED_TICKET);
        $customerTicketCollection->addFieldToFilter(CustomerTicket::SALES_ORDER_ITEM_ID, $item->getId());
        $customerTicketCollection->addFieldToFilter(CustomerTicket::STATUS, TicketStatus::STATUS_UNUSED);

        if ($customerTicketCollection->getSize() > 0) {
            return true;
        }
        // -----------------

        return false;
    }

    protected function updateForRecordExists(OrderItem $item): void
    {
        $item->setData(TicketRetryStatus::FIELD_NAME, TicketRetryStatus::STATUS_RECORD_EXISTS_ERROR);

        $this->orderItemRepository->save($item);
    }

    protected function updateForRetryException(OrderItem $item): void
    {
        $oldCount = $item->getData('ticket_retry_count') ?? 0;
        $newCount = $oldCount + 1;

        if ($newCount >= TicketRetryHelper::MAX_TICKET_RETRY_COUNT) {
            $item->setData(TicketRetryStatus::FIELD_NAME, TicketRetryStatus::STATUS_RETRY_LIMIT_ERROR);
        }

        $item->setData('ticket_retry_count', $newCount);

        $this->orderItemRepository->save($item);
    }

    protected function updateForRetrySuccess(OrderItem $item): void
    {
        $oldCount = $item->getData('ticket_retry_count') ?? 0;
        $newCount = $oldCount + 1;

        $item->setData(TicketRetryStatus::FIELD_NAME, TicketRetryStatus::STATUS_RETRY_SUCCESS);
        $item->setData('ticket_retry_count', $newCount);

        $this->orderItemRepository->save($item);
    }

    protected function addOrderIdForArrivedCheckerEvent(OrderItem $item): void
    {
        $orderId = $item->getOrderId();

        $this->orderIdsForArrivedCheckerEvent[$orderId] = $orderId;
    }

    protected function fireArrivedCheckerEvent(): void
    {
        foreach ($this->orderIdsForArrivedCheckerEvent as $orderId) {
            $this->eventManager->dispatch(
                // OrderAutoProcedure::ECPAY_INVOICE_TICKET_ITEM_ARRIVED_CHECK,
                "ecpay_inovice_ticket_item_arrived_check",
                [
                    "orderId" => $orderId,
                ]
            );
        }
    }
}

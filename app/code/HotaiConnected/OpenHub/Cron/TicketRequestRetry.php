<?php

namespace HotaiConnected\OpenHub\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecord;
use HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord\CollectionFactory as OpenHubCollectionFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicketCollectionFactory;
use HotaiConnected\OpenHub\Helper\Api as ApiHelper;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecordRepository;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;

class TicketRequestRetry
{
    const LOG_FOLDER_NAME = 'OpenHub/Cron/TicketRequestRetry';

    const TARGET_TICKET_TYPE = VirtualProductType::TYPE_OPENHUB_TICKET;
    const RETRY_COUNT_LIMIT  = 3;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var OrderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var VirtualProductHelper */
    protected $virtualProductHelper;

    /** @var OpenHubCollectionFactory */
    protected $openHubCollectionFactory;

    /** @var CustomerTicketCollectionFactory */
    protected $customerTicketCollectionFactory;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var OpenHubTicketRecordRepository */
    protected $openHubTicketRecordRepository;

    /** @var EventManager */
    protected $eventManager;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var CustomerCollectionFactory */
    protected $customerCollectionFactory;

    /** @var OrderRepository */
    protected $orderRepository;

    protected $orderIdsForArrivedCheckerEvent = [];

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderItemRepository $orderItemRepository,
        VirtualProductHelper $virtualProductHelper,
        OpenHubCollectionFactory $openHubCollectionFactory,
        CustomerTicketCollectionFactory $customerTicketCollectionFactory,
        ApiHelper $apiHelper,
        OpenHubTicketRecordRepository $openHubTicketRecordRepository,
        EventManager $eventManager,
        QuoteRepository $quoteRepository,
        CustomerCollectionFactory $customerCollectionFactory,
        OrderRepository $orderRepository
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->orderItemCollectionFactory      = $orderItemCollectionFactory;
        $this->orderItemRepository             = $orderItemRepository;
        $this->virtualProductHelper            = $virtualProductHelper;
        $this->openHubCollectionFactory        = $openHubCollectionFactory;
        $this->customerTicketCollectionFactory = $customerTicketCollectionFactory;
        $this->apiHelper                       = $apiHelper;
        $this->openHubTicketRecordRepository   = $openHubTicketRecordRepository;
        $this->eventManager                    = $eventManager;
        $this->quoteRepository                 = $quoteRepository;
        $this->customerCollectionFactory       = $customerCollectionFactory;
        $this->orderRepository                 = $orderRepository;
    }

    public function execute()
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Message" => "OpenHubTicketRequestRetry cron start.",
        ]), self::LOG_FOLDER_NAME);

        $itemCollection = $this->getTargetOrderItemCollection();
        $itemObjArray   = $itemCollection->getItems();
        $itemObjArray   = $this->removeNonTargetTicketItem($itemObjArray);

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Message"        => "Ready to handle order items.",
            "Order item IDs" => array_keys($itemObjArray)
        ]), self::LOG_FOLDER_NAME);

        /** @var OrderItem $item */
        foreach ($itemObjArray as $item) {
            if ($this->checkIfRecordAlreadyExist($item)) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"      => $item->getOrderId(),
                    "Order item ID" => $item->getId(),
                    "Message"       => "Ticket record already exist so update retry status to error than skip."
                ]), self::LOG_FOLDER_NAME);

                $this->updateForRecordExists($item);
                continue;
            }

            try {
                $apiResponse = $this->apiHelper->requestApiCreateOrder($item->getId());
                
                // Check if this is a gift order and resolve customer info
                $customOwner = $this->resolveCustomOwnerForGiftOrder($item);
                
                $this->openHubTicketRecordRepository->storeTicketDataInDatabase($apiResponse, $item, $customOwner);
                
            } catch (\Throwable $th) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"          => $item->getOrderId(),
                    "Order item ID"     => $item->getId(),
                    "Message"           => "Something went wrong while requesting OpenHub ticket.",
                    "Exception message" => $th->getMessage()
                ]), self::LOG_FOLDER_NAME);

                $this->updateForRetryException($item);
                continue;
            }

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Order ID"      => $item->getOrderId(),
                "Order item ID" => $item->getId(),
                "Message"       => "Retry success."
            ]), self::LOG_FOLDER_NAME);

            $this->updateForRetrySuccess($item);
            $this->addOrderIdForArrivedCheckerEvent($item);

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Order ID"      => $item->getOrderId(),
                "Order item ID" => $item->getId(),
                "Message"       => "Retry success handle done."
            ]), self::LOG_FOLDER_NAME);
        }

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Message"   => "Ready to fire arrived checker event with order IDs.",
            "Order IDs" => array_keys($this->orderIdsForArrivedCheckerEvent)
        ]), self::LOG_FOLDER_NAME);

        $this->fireArrivedCheckerEvent();

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Message" => "OpenHubTicketRequestRetry cron end.",
        ]), self::LOG_FOLDER_NAME);
    }

    protected function getTargetOrderItemCollection(): OrderItemCollection
    {
        $collection = $this->orderItemCollectionFactory->create();

        $collection->addFieldToSelect([
            'item_id',
            'order_id',
            'product_id',
            'product_options',
            'ticket_retry_status',
            'ticket_retry_count'
        ]);

        $collection->addFieldToFilter("ticket_retry_status", TicketRetryStatus::STATUS_NEED_RETRY);

        return $collection;
    }

    protected function removeNonTargetTicketItem(array $itemObjArray): array
    {
        foreach ($itemObjArray as $key => $item) {
            $type = $this->virtualProductHelper->getProductTicketTypeByOrderItemId($item->getId());

            if ($type == self::TARGET_TICKET_TYPE) {
                continue;
            }

            unset($itemObjArray[$key]);
        }

        return $itemObjArray;
    }

    protected function checkIfRecordAlreadyExist(OrderItem $item): bool
    {
        $openHubCollection = $this->openHubCollectionFactory->create();
        $openHubCollection->addFieldToSelect([
            OpenHubTicketRecord::RECORD_ID,
            OpenHubTicketRecord::SALES_ORDER_ITEM_ID,
        ]);
        $openHubCollection->addFieldToFilter(OpenHubTicketRecord::SALES_ORDER_ITEM_ID, $item->getId());
        $openHubCollection->addFieldToFilter(OpenHubTicketRecord::STATUS, TicketStatus::STATUS_UNUSED);

        if ($openHubCollection->getSize() > 0) {
            return true;
        }
        // -----------------

        $customerTicketCollection = $this->customerTicketCollectionFactory->create();
        $customerTicketCollection->addFieldToSelect([
            CustomerTicket::RECORD_ID,
            CustomerTicket::SALES_ORDER_ITEM_ID
        ]);
        $customerTicketCollection->addFieldToFilter(CustomerTicket::TYPE, self::TARGET_TICKET_TYPE);
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

        if ($newCount >= self::RETRY_COUNT_LIMIT) {
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
                "ecpay_inovice_ticket_item_arrived_check",
                [
                    "orderId" => $orderId,
                ]
            );
        }
    }

    /**
     * Resolve custom owner info for gift orders
     *
     * @param OrderItem $item
     * @return array|null
     */
    protected function resolveCustomOwnerForGiftOrder(OrderItem $item): ?array
    {
        try {
            // Get the order
            $order = $this->orderRepository->get($item->getOrderId());
            
            // Get the quote to check gift order status
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $isGiftOrder = max((int)$order->getData('is_gift_order'), (int)$quote->getData('is_gift_order'));
            $isGiftConfirmed = max((int)$order->getData('is_gift_confirmed'), (int)$quote->getData('is_gift_confirmed'));

            // If not a gift order or not confirmed yet, return null (use normal customer ID)
            if (!$isGiftOrder || !$isGiftConfirmed) {
                return null;
            }

            // Get billing address telephone for gift orders
            $billingAddress = $order->getBillingAddress();
            if (!$billingAddress) {
                return null;
            }

            $telephone = $billingAddress->getTelephone();
            if (!$telephone) {
                return null;
            }

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "Found telephone for gift order",
                "Order ID" => $order->getId(),
                "Item ID" => $item->getId(),
                "Telephone" => $telephone
            ]), self::LOG_FOLDER_NAME);

            // Return custom owner info
            return [
                'telephone' => $telephone,
                'customer_id' => 0  // Will be resolved in OpenHubTicketRecordRepository
            ];

        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "Error resolving custom owner for gift order",
                "Order ID" => $item->getOrderId(),
                "Item ID" => $item->getId(),
                "Exception" => $e->getMessage()
            ]), self::LOG_FOLDER_NAME);
            
            return null;
        }
    }
}
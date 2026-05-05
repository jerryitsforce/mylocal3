<?php

namespace HotaiConnected\OpenHub\Observer;

use HotaiConnected\OpenHub\Helper\Api as ApiHelper;
use HotaiConnected\OpenHub\Helper\Common as CommonHelper;
use HotaiConnected\OpenHub\Model\OpenHubTicketRecordRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order\Item;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class RequestOpenHubApiForTicket implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'OpenHub/Observer/RequestOpenHubApiForTicket';

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var OpenHubTicketRecordRepository */
    protected $openHubTicketRecordRepository;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var EventManager */
    protected $eventManager;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var CustomerCollectionFactory */
    protected $customerCollectionFactory;

    protected $customOwner = null;

    public function __construct(
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        ApiHelper $apiHelper,
        OpenHubTicketRecordRepository $openHubTicketRecordRepository,
        ProductRepositoryInterface $productRepository,
        EventManager $eventManager,
        QuoteRepository $quoteRepository,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->orderRepository                 = $orderRepository;
        $this->orderItemRepository             = $orderItemRepository;
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->commonHelper                    = $commonHelper;
        $this->apiHelper                       = $apiHelper;
        $this->openHubTicketRecordRepository   = $openHubTicketRecordRepository;
        $this->productRepository               = $productRepository;
        $this->eventManager                    = $eventManager;
        $this->quoteRepository                 = $quoteRepository;
        $this->customerCollectionFactory       = $customerCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->hotaiCoreCommonHelper->writeLog("=== OpenHub Observer TRIGGERED ===", self::LOG_FOLDER_NAME);
        
        $data    = $observer->getEvent()->getData();
        $orderId = $data["orderId"];
        $this->customOwner = isset($data['custom_owner']) ? $data['custom_owner'] : null;

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Event Data" => $data,
            "Order ID"   => $orderId,
            "Custom Owner" => $this->customOwner
        ]), self::LOG_FOLDER_NAME);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $isGiftOrder = max((int)$order->getData('is_gift_order'), (int)$quote->getData('is_gift_order'));
        $isGiftConfirmed = max((int)$order->getData('is_gift_confirmed'), (int)$quote->getData('is_gift_confirmed'));

        if($isGiftOrder && !$isGiftConfirmed ){
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Order ID" => $orderId,
                "Is Gift Order" => $isGiftOrder,
                "Is Gift Confirmed" => $isGiftConfirmed,
                "Message" => "Skip OpenHub ticket processing for unconfirmed gift order"
            ]), self::LOG_FOLDER_NAME);
            return;
        }

        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Processing Item" => [
                    "Item ID" => $item->getId(),
                    "Product ID" => $item->getProductId(),
                    "SKU" => $item->getSku()
                ]
            ]), self::LOG_FOLDER_NAME);
            
            // 只處理 OpenHub 票券
            $isOpenHubProduct = $this->commonHelper->IsOpenHubTicketProduct($item->getProductId());
            
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Product Check" => [
                    "Product ID" => $item->getProductId(),
                    "Is OpenHub Product" => $isOpenHubProduct ? "YES" : "NO"
                ]
            ]), self::LOG_FOLDER_NAME);
            
            if (!$isOpenHubProduct) {
                continue;
            }

            // 檢查是否已有 API 呼叫記錄，避免重複處理
            if ($this->isTicketDataAlreadyExistInDatabase($item->getId())) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"      => $order->getId(),
                    "Order Item ID" => $item->getId(),
                    "Message"       => "OpenHub ticket data already exist in database so skip requestApiCreateOrder.",
                ]), self::LOG_FOLDER_NAME);

                continue;
            }

            try {
                // 呼叫 OpenHub API 獲取票券
                $apiResponse = $this->apiHelper->requestApiCreateOrder($item->getId());

                // 儲存票券資料，同時整合到 GeneralNotifyTicket 系統
                $this->openHubTicketRecordRepository->storeTicketDataInDatabase($apiResponse, $item, $this->customOwner);
                
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"      => $order->getId(),
                    "Order Item ID" => $item->getId(),
                    "Message"       => "OpenHub API call successful, tickets stored."
                ]), self::LOG_FOLDER_NAME);

                // 觸發票券到貨檢查事件
                $this->eventManager->dispatch(
                    "ecpay_inovice_ticket_item_arrived_check",
                    [
                        "orderId" => $order->getId(),
                    ]
                );

                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"      => $order->getId(),
                    "Order Item ID" => $item->getId(),
                    "Message"       => "Triggered ecpay_inovice_ticket_item_arrived_check event."
                ]), self::LOG_FOLDER_NAME);
                
            } catch (\Exception $e) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"          => $order->getId(),
                    "Order Item ID"     => $item->getId(),
                    "Exception Message" => $e->getMessage(),
                    "Message"           => "Should set ticket_retry_status to " . TicketRetryStatus::STATUS_NEED_RETRY . ", let cron handle retry."
                ]), self::LOG_FOLDER_NAME);

                // 設定重試狀態，讓 Cron 處理
                $item->setData("ticket_retry_status", TicketRetryStatus::STATUS_NEED_RETRY);
                $this->orderItemRepository->save($item);
            }
        }
    }

    /**
     * 確認票券是否已經存在於資料庫中避免同一個order item重複請求票券
     * @param integer $itemId
     * @return boolean
     */
    protected function isTicketDataAlreadyExistInDatabase(int $itemId): bool
    {
        $collection = $this->openHubTicketRecordRepository->getRecordsByOrderItemId($itemId);

        return !empty($collection->getItems());
    }
}
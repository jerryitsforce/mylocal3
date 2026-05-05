<?php

namespace HotaiConnected\Qware\Observer;

use HotaiConnected\Qware\Helper\Api as ApiHelper;
use HotaiConnected\Qware\Helper\Common as CommonHelper;
use HotaiConnected\Qware\Model\QwareTicketRecordRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order\Item;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class RequestQwareApiForTicket implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Qware/Observer/RequestQwareApiForTicket';

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

    /** @var QwareTicketRecordRepository */
    protected $qwareTicketRecordRepository;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

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
        QwareTicketRecordRepository $qwareTicketRecordRepository,
        ProductRepositoryInterface $productRepository,
        QuoteRepository $quoteRepository,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->orderRepository               = $orderRepository;
        $this->orderItemRepository           = $orderItemRepository;
        $this->hotaiCoreCommonHelper         = $hotaiCoreCommonHelper;
        $this->commonHelper                  = $commonHelper;
        $this->apiHelper                     = $apiHelper;
        $this->qwareTicketRecordRepository   = $qwareTicketRecordRepository;
        $this->productRepository             = $productRepository;
        $this->quoteRepository                = $quoteRepository;
        $this->customerCollectionFactory       = $customerCollectionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data    = $observer->getEvent()->getData();
        $orderId = $data["orderId"];
        $this->customOwner = isset($data['custom_owner']) ? $data['custom_owner'] : null;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $isGiftOrder = max((int)$order->getData('is_gift_order'), (int)$quote->getData('is_gift_order'));
        $isGiftConfirmed = max((int)$order->getData('is_gift_confirmed'), (int)$quote->getData('is_gift_confirmed'));

        if($isGiftOrder && !$isGiftConfirmed ){
            return;
        }

        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if (!$this->commonHelper->IsQwareTicketProduct($item->getProductId())) {
                continue;
            }

            if ($this->isVoucherDataAlreadyExistInDatabase($item->getId())) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"      => $order->getId(),
                    "Order Item ID" => $item->getId(),
                    "Message"       => "Voucher data already exist in database so skip requestApiGetMultiVouchers.",
                ]), self::LOG_FOLDER_NAME);

                continue;
            }

            try {
                $apiResponse = $this->apiHelper->requestApiGetMultiVouchers($item->getId());

                $this->qwareTicketRecordRepository->storeVoucherDataInDatabase($apiResponse, $item, $this->customOwner);
            } catch (\Exception $e) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Order ID"          => $order->getId(),
                    "Order Item ID"     => $item->getId(),
                    "Exception Message" => $e->getMessage(),
                    "Message"           => "Should set ticket_retry_status to " . TicketRetryStatus::STATUS_NEED_RETRY . ", let cron handle retry."
                ]), self::LOG_FOLDER_NAME);

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
    protected function isVoucherDataAlreadyExistInDatabase(int $itemId): bool
    {
        $collection = $this->qwareTicketRecordRepository->getRecordsByOrderItemId($itemId);

        return !empty($collection->getItems());
    }
}

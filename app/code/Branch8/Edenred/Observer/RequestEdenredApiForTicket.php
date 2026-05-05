<?php

namespace Branch8\Edenred\Observer;

use Branch8\Edenred\Helper\Api as ApiHelper;
use Branch8\Edenred\Helper\Common as CommonHelper;
use Branch8\Edenred\Model\Config\Source\LogOption as EdenredLogOption;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order\Item;
use Branch8\HotaiCore\Model\Ticket\TicketRetryStatus;

class RequestEdenredApiForTicket implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Edenred/Observer/RequestEdenredApiForTicket';
    const LOG_OPTION_VALUE = EdenredLogOption::LOG_OPTION_VALUE_REQUEST_API_FOR_TICKET;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    protected $customOwner = NULL;

    protected $quoteRepository;

    public function __construct(
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        CommonHelper $commonHelper,
        ApiHelper $apiHelper,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->orderRepository               = $orderRepository;
        $this->orderItemRepository           = $orderItemRepository;
        $this->commonHelper                  = $commonHelper;
        $this->apiHelper                     = $apiHelper;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        $this->productRepository             = $productRepository;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data    = $observer->getEvent()->getData();
        $orderId = $data["orderId"];

        $this->customOwner = isset($data['custom_owner']) ? $data['custom_owner'] : NULL;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $isGiftOrder = max((int)$order->getData('is_gift_order'), (int)$quote->getData('is_gift_order'));
        $isGiftConfirmed = max((int)$order->getData('is_gift_confirmed'), (int)$quote->getData('is_gift_confirmed'));

        if($isGiftOrder && !$isGiftConfirmed ){
            return;
        }
        /** @var Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if (!$this->commonHelper->IsEdenredTicketProduct($item->getProductId())) {
                continue;
            }

            if ($this->isVoucherDataAlreadyExistInDatabase($item->getId())) {
                $this->commonHelper->writeLogIfEnabled(json_encode([
                    "Order ID"      => $order->getId(),
                    "Order Item ID" => $item->getId(),
                    "Message"       => "Voucher data already exist in database so skip requestApiGetMultiVouchers.",
                ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

                continue;
            }

            try {
                $apiResponse = $this->apiHelper->requestApiGetMultiVouchers($item->getId());

                $this->edenredTicketRecordRepository->storeVoucherDataInDatabase($apiResponse, $item, $this->customOwner);
            } catch (\Exception $e) {
                $this->commonHelper->writeLogIfEnabled(json_encode([
                    "Order ID"          => $order->getId(),
                    "Order Item ID"     => $item->getId(),
                    "Exception Message" => $e->getMessage(),
                    "Message"           => "Should set ticket_retry_status to " . TicketRetryStatus::STATUS_NEED_RETRY . ", let cron handle retry."
                ]), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

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
        $collection = $this->edenredTicketRecordRepository->getRecordsByOrderItemId($itemId);

        return !empty($collection->getItems());
    }
}

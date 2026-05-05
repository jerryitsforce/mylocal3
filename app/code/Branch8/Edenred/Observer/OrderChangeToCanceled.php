<?php

namespace Branch8\Edenred\Observer;

use Branch8\Edenred\Helper\Api as ApiHelper;
use Branch8\Edenred\Helper\Common as CommonHelper;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord\Collection as EdenredTicketRecordCollection;
use Branch8\Edenred\Model\Config\Source\LogOption as EdenredLogOption;
use Branch8\HotaiCore\Model\Order\Status;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class OrderChangeToCanceled implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Edenred/Observer/OrderChangeToCanceled';
    const LOG_OPTION_VALUE = EdenredLogOption::LOG_OPTION_VALUE_ORDER_CHANGE_TO_CANCELED;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var Transaction */
    protected $transaction;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    public function __construct(
        CommonHelper $commonHelper,
        ApiHelper $apiHelper,
        Transaction $transaction,
        EdenredTicketRecordRepository $edenredTicketRecordRepository
    ) {
        $this->commonHelper                  = $commonHelper;
        $this->apiHelper                     = $apiHelper;
        $this->transaction                   = $transaction;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();

        if (!$this->checkOrderStatusCondition($order)) {
            return;
        }

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            try {
                if (!$this->commonHelper->IsEdenredTicketProduct($item->getProductId())) {
                    continue;
                }

                $collection = $this->edenredTicketRecordRepository->getRecordsByOrderItemIdAndStatus(
                    $item->getId(),
                    TicketStatus::STATUS_UNUSED
                );

                if ($this->checkCollectionEmpty($collection)) {
                    continue;
                }

                $response = $this->apiHelper->requestApiCancelMultiVouchers($item->getId());

                $this->edenredTicketRecordRepository->setCanceledStatusToEdenredTicketRecordsInDb($collection, $response);
            } catch (\Exception $e) {
                $this->commonHelper->writeLogIfEnabled($e->getMessage(), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);
            }
        }
    }

    /**
     * 確認觸發observer的sales_order是否將訂單狀態轉為"已取消"(canceled)
     *
     * @param Order $order
     * @return boolean
     */
    protected function checkOrderStatusCondition(Order $order): bool
    {
        if ($order->getOrigData('status') == $order->getStatus()) {
            return false;
        }

        return ($order->getStatus() == Status::STATUS_CANCELED);
    }

    /**
     * 確認資料庫中是否有找到對應的宜睿票券紀錄
     * @param EdenredTicketRecordCollection $collection
     * @return boolean
     */
    protected function checkCollectionEmpty(EdenredTicketRecordCollection $collection): bool
    {
        $recordArray = $collection->getItems();

        return empty($recordArray);
    }
}

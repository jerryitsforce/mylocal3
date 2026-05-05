<?php

namespace Branch8\GeneralNonNotifyTicket\Observer;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\GeneralNonNotifyTicket\Helper\Common as CommonHelper;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord\CollectionFactory;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord as TicketRecordModel;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;

class TicketResetImportedObserver implements ObserverInterface
{
    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var Transaction */
    protected $transaction;

    protected $eventName;

    public function __construct(
        QuoteRepository $quoteRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        CollectionFactory $collectionFactory,
        Transaction $transaction
    ) {
        $this->quoteRepository       = $quoteRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->commonHelper          = $commonHelper;
        $this->collectionFactory     = $collectionFactory;
        $this->transaction           = $transaction;
    }

    public function execute(Observer $observer)
    {
        $quoteItemIds    = [];
        $this->eventName = $observer->getEvent()->getName();
        $data            = $observer->getEvent()->getData();
        $quoteId         = $data["quoteId"];
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        foreach ($quote->getAllVisibleItems() as $item) {
            if (!$this->commonHelper->IsGeneralNonNotifyTicketProduct($item->getProductId())) {
                continue;
            }

            $quoteItemIds[] = $item->getId();
        }

        if (count($quoteItemIds) == 0) {
            return;
        }

        $this->resetRecordsCollectionByQuoteItemIds($quoteItemIds);
    }

    /**
     * 根據傳入的quote_item_id array將對應的票券記錄重置為剛匯入的狀態
     * @param array $quoteItemIds
     * @return void
     */
    private function resetRecordsCollectionByQuoteItemIds(array $quoteItemIds): void
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            TicketRecordModel::QUOTE_ITEM_ID,
            ["in" => $quoteItemIds]
        );

        $collection->load();

        /** @var TicketRecordModel $record */
        foreach ($collection->getItems() as $record) {
            $record = $this->setTicketRecordUpdateValue($record);
            $this->transaction->addObject($record);
        }

        $this->transaction->save();
    }

    /**
     * 將傳入的TicketRecordModel寫入欲更新的值
     * @param TicketRecordModel $record
     * @return TicketRecordModel
     */
    private function setTicketRecordUpdateValue(TicketRecordModel $record): TicketRecordModel
    {
        $beforeStatus = $record->getStatus();
        $afterStatus  = TicketRecordModel::STATUS_IMPORTED;

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $record->getData(TicketRecordModel::MEMO),
            [
                "Timestamp"          => time(),
                "Datetime"           => date("Y-m-d H:i:s"),
                "Title"              => "GeneralNonNotifyTicket TicketResetImportedObserver triggered by event: {$this->eventName}",
                "Message"            => "Set status back to imported, before status: {$beforeStatus}, after status: {$afterStatus}",
                "Message from event" => $data["message"] ?? ""
            ]
        );

        $record->setQuoteItemId(null);
        $record->setSalesOrderItemId(null);
        $record->setStatus($afterStatus);
        $record->setUseStartTime(null);
        $record->setUseEndTime(null);
        $record->setDueDays(null);
        $record->setMemo($memoMessage);

        return $record;
    }
}

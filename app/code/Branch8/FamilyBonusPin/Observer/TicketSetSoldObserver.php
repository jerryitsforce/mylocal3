<?php

namespace Branch8\FamilyBonusPin\Observer;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\FamilyBonusPin\Helper\Common as CommonHelper;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\CollectionFactory;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\Collection;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord as TicketRecordModel;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\CustomerTicketTable\Model\CustomerTicketFactory;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting as BatchSettingModel;
use Branch8\HotaiCore\Model\Product\VirtualProductType;

class TicketSetSoldObserver implements ObserverInterface
{
    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var CustomerTicketFactory */
    protected $customerTicketFactory;

    protected $eventName;
    protected $order;

    protected $customOwner = NULL;

    protected $quoteRepository;

    protected $customerCollectionFactory;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        CollectionFactory $collectionFactory,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        Transaction $transaction,
        CustomerTicketFactory $customerTicketFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->commonHelper          = $commonHelper;
        $this->collectionFactory     = $collectionFactory;
        $this->orderRepository       = $orderRepository;
        $this->orderItemRepository   = $orderItemRepository;
        $this->transaction           = $transaction;
        $this->customerTicketFactory = $customerTicketFactory;
        $this->quoteRepository = $quoteRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    public function execute(Observer $observer)
    {
        $salesOrderItemIds = [];
        $this->eventName   = $observer->getEvent()->getName();
        $data              = $observer->getEvent()->getData();
        $orderId           = $data["orderId"];

        $this->customOwner = isset($data['custom_owner']) ? $data['custom_owner'] : NULL;

        /** @var \Magento\Sales\Model\Order $order */
        $order       = $this->orderRepository->get($orderId);

        $quote = $this->quoteRepository->get($order->getQuoteId());
        $isGiftOrder = max((int)$order->getData('is_gift_order'), (int)$quote->getData('is_gift_order'));
        $isGiftConfirmed = max((int)$order->getData('is_gift_confirmed'), (int)$quote->getData('is_gift_confirmed'));

        if($isGiftOrder && !$isGiftConfirmed ){
            return;
        }

        $this->order = $order;

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$this->commonHelper->IsFamilyBonusPinTicketProduct($item->getProductId())) {
                continue;
            }

            $salesOrderItemIds[] = $item->getId();
        }

        if (count($salesOrderItemIds) == 0) {
            return;
        }

        $this->updateTicketRecordsFlow($salesOrderItemIds);
    }

    /**
     * 根據傳入的sales order item IDs更新對應的票券紀錄
     * 1. 以sales order item ID找到quote item ID
     * 2. 以quote item ID找到票券紀錄
     * 3. 更新票券紀錄的sales order item ID和"已售出"狀態
     * @param array $salesOrderItemIds
     * @return void
     */
    private function updateTicketRecordsFlow(array $salesOrderItemIds): void
    {
        foreach ($salesOrderItemIds as $salesOrderItemId) {
            if ($this->checkIfSoldRecordExistBySalesOrderItemId($salesOrderItemId)) {
                continue;
            }

            $salesOrderItem = $this->orderItemRepository->get($salesOrderItemId);
            $quoteItemId    = $salesOrderItem->getQuoteItemId();

            $collection = $this->getTicketCollectionByQuoteItemId($quoteItemId);

            /** @var TicketRecordModel $record */
            foreach ($collection->getItems() as $record) {
                $record = $this->setTicketRecordUpdateValue($record, $salesOrderItemId);
                $this->transaction->addObject($record);

                $customerTicketRecord = $this->prepareNewCustomerTicketRecord($record, $salesOrderItemId);
                $this->transaction->addObject($customerTicketRecord);
            }
        }

        $this->transaction->save();
    }

    /**
     * 確認資料庫中是否已經有屬於salesOrderItemId的紀錄
     * @param integer $salesOrderItemId
     * @return boolean
     */
    private function checkIfSoldRecordExistBySalesOrderItemId(int $salesOrderItemId): bool
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(TicketRecordModel::SALES_ORDER_ITEM_ID, $salesOrderItemId);

        $result = $collection->getFirstItem();

        return !empty($result->getId());
    }

    /**
     * 利用傳入的quote_item ID找出目標票券紀錄
     * @param integer $quoteItemId
     * @return Collection
     */
    private function getTicketCollectionByQuoteItemId(int $quoteItemId): Collection
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $batchSettingIdFieldName = TicketRecordModel::BATCH_SETTING_ID;
        $batchSettingTableName   = BatchSettingModel::TABLE_NAME;
        $settingIdFieldName      = BatchSettingModel::SETTING_ID;

        $collection
            ->join(
                [BatchSettingModel::TABLE_NAME => BatchSettingModel::TABLE_NAME],
                "main_table.{$batchSettingIdFieldName} = {$batchSettingTableName}.{$settingIdFieldName}",
                [
                    'batch_code' => 'batch_code',
                ]
            );

        $collection->addFieldToFilter(TicketRecordModel::QUOTE_ITEM_ID, $quoteItemId);

        $collection->load();

        return $collection;
    }

    /**
     * 將傳入的TicketRecordModel寫入欲更新的值
     * @param TicketRecordModel $record
     * @param integer $salesOrderItemId
     * @return TicketRecordModel
     */
    private function setTicketRecordUpdateValue(TicketRecordModel $record, int $salesOrderItemId): TicketRecordModel
    {
        $beforeStatus = $record->getStatus();
        $afterStatus  = TicketRecordModel::STATUS_SOLD;

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $record->getData(TicketRecordModel::MEMO),
            [
                "Timestamp" => time(),
                "Datetime"  => date("Y-m-d H:i:s"),
                "Title"     => "FamilyBonusPin TicketSetSoldObserver triggered by event: {$this->eventName}",
                "Message"   => "Set sold status, sales order item ID: {$salesOrderItemId}, before status: {$beforeStatus}, after status: {$afterStatus}",
            ]
        );

        // 將due_days轉換成實際的use_end_time
        if (!is_null($record->getDueDays())) {
            // $currentTime = date("Y-m-d");
            // $dueDays     = $record->getDueDays();
            // $useEndTime  = strtotime("+{$dueDays} days", strtotime($currentTime));
            // $useEndTime  = date('Y-m-d', $useEndTime);
            $dueDays       = $record->getDueDays();
            $taiwanDateObj = new \DateTime();
            $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
            $taiwanDateObj->modify("+{$dueDays} days");
            $taiwanDateObj->setTime(23, 59, 59);
            $useEndTime = $taiwanDateObj->format("Y-m-d H:i:s");

            $record->setUseEndTime($useEndTime);
        }

        $record->setSalesOrderItemId($salesOrderItemId);
        $record->setStatus(TicketRecordModel::STATUS_SOLD);
        $record->setMemo($memoMessage);

        return $record;
    }

    /**
     * 準備新增用戶持有表紀錄
     * @param TicketRecordModel $record
     * @param int $salesOrderItemId
     * @return \Branch8\CustomerTicketTable\Model\CustomerTicket
     */
    private function prepareNewCustomerTicketRecord(TicketRecordModel $record, int $salesOrderItemId): CustomerTicket
    {
        $ticketUniqueContent = $record->getSerialNumber();

        $customerTicket = $this->customerTicketFactory->create();
        $customerTicket->setType(VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET);
        $customerTicket->setTicketTableName(TicketRecordModel::TABLE_NAME);
        $customerTicket->setTicketTableRecordId($record->getId());
        $customerTicket->setBatchCode($record->getData("batch_code"));

        $customerId = $this->order->getCustomerId();
        if($this->customOwner){
            if(isset($this->customOwner['telephone']) && $this->customOwner['telephone'] != ''){
                $customerId = 0;
                $customerTicket->setTelephone($this->customOwner['telephone']);
                $customerTicket->setMemberSeq($this->customOwner['member_seq']);
                /** Load customer and set customer if the phone is an account */
                if($this->customOwner['member_seq']){
                    $customerCollection = $this->customerCollectionFactory->create()
                        ->addAttributeToFilter('member_seq', $this->customOwner['member_seq']);
                    $customerCollection->getSelect()->order('entity_id desc')->limit(1);
                    $customer = $customerCollection->getFirstItem();
                    if($customer->getId()){
                        $customerId = $customer->getId();
                    }
                }
                
            }else{
                $customerId = (int)$this->customOwner['customer_id'];
                $customerTicket->setTelephone(NULL);
            }
        }
        
        
        $customerTicket->setCustomerId($customerId);
        $customerTicket->setSalesOrderItemId($salesOrderItemId);
        $customerTicket->setBelongToProductId($record->getBelongToProductId());
        $customerTicket->setSellerId($record->getSellerId());
        $customerTicket->setTicketUniqueContent($ticketUniqueContent);
        $customerTicket->setUseStartTime($record->getUseStartTime());
        $customerTicket->setUseEndTime($record->getUseEndTime());
        $customerTicket->setStatus(\Branch8\HotaiCore\Model\Ticket\Status::STATUS_UNUSED);

        return $customerTicket;
    }
}

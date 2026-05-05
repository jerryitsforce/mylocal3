<?php

namespace HotaiConnected\Qware\Helper;

use HotaiConnected\Qware\Helper\Api as ApiHelper;
use HotaiConnected\Qware\Model\QwareTicketRecord;
use HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord\CollectionFactory;
use HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord\Collection;
use HotaiConnected\Qware\Api\QwareTicketRecordRepositoryInterface;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Psr\Log\LoggerInterface;

class Flow
{
    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var QwareTicketRecordRepositoryInterface */
    protected $qwareTicketRecordRepository;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    public function __construct(
        ApiHelper $apiHelper,
        CollectionFactory $collectionFactory,
        QwareTicketRecordRepositoryInterface $qwareTicketRecordRepository,
        LoggerInterface $logger,
        CustomerTicketRepository $customerTicketRepository
    ) {
        $this->apiHelper                     = $apiHelper;
        $this->collectionFactory             = $collectionFactory;
        $this->qwareTicketRecordRepository   = $qwareTicketRecordRepository;
        $this->logger                        = $logger;
        $this->customerTicketRepository      = $customerTicketRepository;
    }

    /**
     * 取消/作廢票券
     *
     * @param int $orderItemId
     * @return void
     * @throws \Exception
     */
    public function cancelTickets(int $orderItemId): void
    {
        $ticketSnArray = [];
        $collection    = $this->getCollection($orderItemId);

        if ($collection->getSize() === 0) {
            $this->logger->info('[qware_flow] No tickets found for cancellation', [
                'order_item_id' => $orderItemId
            ]);
            return;
        }

        /** @var QwareTicketRecord $record */
        foreach ($collection->getItems() as $record) {
            $ticketSn = $record->getQwareSn();
            if (!empty($ticketSn)) {
                $ticketSnArray[] = $ticketSn;
            }
        }

        if (empty($ticketSnArray)) {
            $this->logger->warning('[qware_flow] No valid ticket SNs found for cancellation', [
                'order_item_id' => $orderItemId,
                'collection_size' => $collection->getSize()
            ]);
            return;
        }

        // 取得第一筆記錄的訂單編號
        $firstRecord = $collection->getFirstItem();
        $orderNo = $firstRecord->getQwareOrderNumber();

        $this->logger->info('[qware_flow] Starting ticket cancellation', [
            'order_item_id' => $orderItemId,
            'order_no' => $orderNo,
            'ticket_count' => count($ticketSnArray),
            'ticket_sns' => $ticketSnArray
        ]);

        // 調用作廢 API
        $apiResponse = $this->apiHelper->requestApiRefundImmediately($orderNo, $ticketSnArray);

        // 更新票券狀態
        $this->updateTicketStatusAfterRefund($collection, $apiResponse);
    }

    /**
     * 根據 API 回應更新票券狀態
     *
     * @param Collection $collection
     * @param array $apiResponse
     * @return void
     */
    protected function updateTicketStatusAfterRefund(Collection $collection, array $apiResponse): void
    {
        try {
            
            if (!isset($apiResponse['Code']) || !isset($apiResponse['Data'])) {
                throw new \Exception('Invalid API response format');
            }

            $responseCode = $apiResponse['Code'];
            $responseData = $apiResponse['Data'];

            // 建立 SN 對應的記錄映射
            $snToRecordMap = [];
            /** @var QwareTicketRecord $record */
            foreach ($collection->getItems() as $record) {
                $sn = $record->getQwareSn();
                if (!empty($sn)) {
                    $snToRecordMap[$sn] = $record;
                }
            }

            // 處理每筆票券的回應
            foreach ($responseData as $ticketResult) {
                $sn = $ticketResult['Sn'] ?? '';
                $success = $ticketResult['Success'] ?? false;
                $returnCode = $ticketResult['ReturnCode'] ?? 0;
                $returnMessage = $ticketResult['ReturnMessage'] ?? '';

                if (isset($snToRecordMap[$sn])) {
                    $record = $snToRecordMap[$sn];
                    
                    if ($success && $returnCode == 200) {
                        // 作廢成功，更新狀態為已退貨
                        $record->setStatus(TicketStatus::STATUS_RETURNED);
                        
                        // 添加備註
                        $memo = $record->getMemo() ?: '';
                        $memo .= "\n[" . date('Y-m-d H:i:s') . "] 作廢成功 - " . $returnMessage;
                        $record->setMemo($memo);
                        
                        // 保存 qware_ticket_record
                        $this->qwareTicketRecordRepository->save($record);
                        
                        // 更新 customer_ticket 狀態
                        $customerTicketRecord = $this->customerTicketRepository->getByTypeAndTicketRecordId(
                            VirtualProductType::TYPE_QWARE_TICKET,
                            $record->getId()
                        );
                        
                        if (!is_null($customerTicketRecord)) {
                            $customerTicketRecord->setStatus(TicketStatus::STATUS_RETURNED);
                            $this->customerTicketRepository->save($customerTicketRecord);
                        }
                        
                        $this->logger->info('[qware_flow] Ticket refunded successfully', [
                            'sn' => $sn,
                            'record_id' => $record->getId(),
                            'return_message' => $returnMessage
                        ]);
                    } else {
                        // 作廢失敗，記錄錯誤資訊
                        $memo = $record->getMemo() ?: '';
                        $memo .= "\n[" . date('Y-m-d H:i:s') . "] 作廢失敗 - Code: {$returnCode}, Message: {$returnMessage}";
                        $record->setMemo($memo);
                        
                        // 保存 qware_ticket_record
                        $this->qwareTicketRecordRepository->save($record);
                        
                        $this->logger->error('[qware_flow] Ticket refund failed', [
                            'sn' => $sn,
                            'record_id' => $record->getId(),
                            'return_code' => $returnCode,
                            'return_message' => $returnMessage
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('[qware_flow] Error updating ticket status after refund', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 取得可取消的票券集合
     *
     * @param int $orderItemId
     * @return Collection
     */
    protected function getCollection(int $orderItemId): Collection
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(
            QwareTicketRecord::SALES_ORDER_ITEM_ID,
            (string) $orderItemId
        );
        
        // 只有未使用狀態的票券才能作廢
        $collection->addFieldToFilter(
            QwareTicketRecord::STATUS,
            TicketStatus::STATUS_UNUSED
        );

        $collection->load();

        return $collection;
    }
}
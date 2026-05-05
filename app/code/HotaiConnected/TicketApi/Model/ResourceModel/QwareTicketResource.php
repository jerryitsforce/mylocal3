<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Model\ResourceModel;

use HotaiConnected\Qware\Model\QwareTicketRecord;
use HotaiConnected\Qware\Api\QwareTicketRecordRepositoryInterface;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;

class QwareTicketResource
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var QwareTicketRecordRepositoryInterface
     */
    private QwareTicketRecordRepositoryInterface $qwareTicketRecordRepository;

    /**
     * @var CustomerTicketRepository
     */
    private CustomerTicketRepository $customerTicketRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    public function __construct(
        ResourceConnection $resourceConnection,
        QwareTicketRecordRepositoryInterface $qwareTicketRecordRepository,
        CustomerTicketRepository $customerTicketRepository,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->qwareTicketRecordRepository = $qwareTicketRecordRepository;
        $this->customerTicketRepository = $customerTicketRepository;
        $this->logger = $logger;
        $this->connection = $this->resourceConnection->getConnection();
    }

    /**
     * 根據 Qware SN 查找票券記錄
     */
    public function getTicketRecordByQwareSn(string $qwareSn): ?QwareTicketRecord
    {
        try {
            $tableName = $this->resourceConnection->getTableName('qware_ticket_record');
            $select = $this->connection->select()
                ->from($tableName)
                ->where('qware_sn = ?', $qwareSn)
                ->limit(1);

            $data = $this->connection->fetchRow($select);
            
            if (!$data) {
                return null;
            }

            $ticketRecord = $this->qwareTicketRecordRepository->getById((int)$data['record_id']);
            return $ticketRecord;

        } catch (\Exception $e) {
            $this->logger->error('[QwareTicketResource] Error finding ticket by SN: ' . $e->getMessage(), [
                'qware_sn' => $qwareSn,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * 根據 Pincode (比對 qware_vendor_sn) 查找票券對應的 Qware SN
     */
    public function getQwareSnByPincode(string $pincode): ?string
    {
        try {
            $tableName = $this->resourceConnection->getTableName('qware_ticket_record');
            $select = $this->connection->select()
                ->from($tableName, 'qware_sn')
                ->where('qware_vendor_sn = ?', $pincode)
                ->limit(1);

            $qwareSn = $this->connection->fetchOne($select);
            return $qwareSn !== false && $qwareSn !== '' ? (string)$qwareSn : null;

        } catch (\Exception $e) {
            $this->logger->error('[QwareTicketResource] Error finding SN by pincode: ' . $e->getMessage(), [
                'pincode' => $pincode,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * 檢查票券是否已使用
     */
    public function isTicketUsed(QwareTicketRecord $ticketRecord): bool
    {
        return $ticketRecord->getStatus() === QwareTicketRecord::STATUS_USED;
    }

    /**
     * 檢查票券是否已退貨
     */
    public function isTicketReturned(QwareTicketRecord $ticketRecord): bool
    {
        return $ticketRecord->getStatus() === QwareTicketRecord::STATUS_RETURNED;
    }

    /**
     * 檢查票券是否可用
     */
    public function isTicketAvailable(QwareTicketRecord $ticketRecord): bool
    {
        return $ticketRecord->getStatus() === QwareTicketRecord::STATUS_UNUSED;
    }

    /**
     * 更新票券狀態為已使用
     */
    public function updateTicketToUsed(QwareTicketRecord $ticketRecord, array $notificationData): bool
    {
        try {
            // 更新票券狀態和相關通知資訊
            $ticketRecord->setStatus(QwareTicketRecord::STATUS_USED);
            
            // 儲存通知相關資訊
            if (isset($notificationData['NotificationId'])) {
                $ticketRecord->setUsedTransactionNo($notificationData['NotificationId']);
            }
            
            if (isset($notificationData['Type'])) {
                $ticketRecord->setNotificationType((int)$notificationData['Type']);
            }

            $data = $notificationData['Data'] ?? [];
            
            if (isset($data['Type'])) {
                $ticketRecord->setQwareType((int)$data['Type']);
            }
            
            if (isset($data['Qty'])) {
                $ticketRecord->setQwareQty((int)$data['Qty']);
            }
            
            if (isset($data['BranchCode'])) {
                $ticketRecord->setBranchCode($data['BranchCode']);
            }
            
            if (isset($data['BranchName'])) {
                $ticketRecord->setBranchName($data['BranchName']);
            }
            
            if (isset($data['PosCode'])) {
                $ticketRecord->setPosCode($data['PosCode']);
            }
            
            $usedDate = $data['TransDate'] ?? null;
            if ($usedDate === null && isset($data['TradeTime'])) {
                // TradeTime 格式為 YYYYMMDDHHMMSS，需轉換為 Y-m-d H:i:s
                $dt = \DateTime::createFromFormat('YmdHis', $data['TradeTime']);
                $usedDate = $dt ? $dt->format('Y-m-d H:i:s') : $data['TradeTime'];
            }
            if ($usedDate !== null) {
                $ticketRecord->setUsedDate($usedDate);
            }

            // 儲存變更
            $this->qwareTicketRecordRepository->save($ticketRecord);

            // 同步更新對應的 customer_ticket 狀態
            $this->updateCustomerTicketStatus($ticketRecord, TicketStatus::STATUS_USED);

            $this->logger->info('[QwareTicketResource] Ticket updated to used successfully', [
                'record_id' => $ticketRecord->getId(),
                'qware_sn' => $ticketRecord->getQwareSn(),
                'notification_id' => $notificationData['NotificationId'] ?? null
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('[QwareTicketResource] Error updating ticket to used: ' . $e->getMessage(), [
                'record_id' => $ticketRecord->getId(),
                'qware_sn' => $ticketRecord->getQwareSn(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 更新票券狀態為取消使用（如果需要的話）
     */
    public function updateTicketToCancelled(QwareTicketRecord $ticketRecord, array $notificationData): bool
    {
        try {
            // 將狀態重設為未使用狀態（取消兌換後還原為可用狀態）
            $ticketRecord->setStatus(QwareTicketRecord::STATUS_UNUSED);
            
            // 清空使用相關資訊
            $ticketRecord->setUsedTransactionNo(null);
            $ticketRecord->setUsedDate(null);
            $ticketRecord->setNotificationType(null);
            $ticketRecord->setQwareType(null);
            $ticketRecord->setQwareQty(null);
            $ticketRecord->setBranchCode(null);
            $ticketRecord->setBranchName(null);
            $ticketRecord->setPosCode(null);
            
            // 記錄取消操作的備註
            $memo = $ticketRecord->getMemo() ?: '';
            $memo .= "\n[" . date('Y-m-d H:i:s') . "] 取消兌換，NotificationId: " . ($notificationData['NotificationId'] ?? 'N/A');
            $ticketRecord->setMemo($memo);

            // 儲存變更
            $this->qwareTicketRecordRepository->save($ticketRecord);

            // 同步更新對應的 customer_ticket 狀態
            $this->updateCustomerTicketStatus($ticketRecord, TicketStatus::STATUS_UNUSED);

            $this->logger->info('[QwareTicketResource] Ticket cancelled successfully', [
                'record_id' => $ticketRecord->getId(),
                'qware_sn' => $ticketRecord->getQwareSn(),
                'notification_id' => $notificationData['NotificationId'] ?? null
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('[QwareTicketResource] Error cancelling ticket: ' . $e->getMessage(), [
                'record_id' => $ticketRecord->getId(),
                'qware_sn' => $ticketRecord->getQwareSn(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 記錄票券操作日誌
     */
    public function logTicketOperation(QwareTicketRecord $ticketRecord, string $operation, array $data = []): void
    {
        try {
            $memo = $ticketRecord->getMemo() ?: '';
            $memo .= "\n[" . date('Y-m-d H:i:s') . "] " . $operation;
            
            if (!empty($data)) {
                $memo .= " - " . json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            
            $ticketRecord->setMemo($memo);
            $this->qwareTicketRecordRepository->save($ticketRecord);

        } catch (\Exception $e) {
            $this->logger->error('[QwareTicketResource] Error logging ticket operation: ' . $e->getMessage(), [
                'record_id' => $ticketRecord->getId(),
                'operation' => $operation
            ]);
        }
    }

    /**
     * 檢查是否已存在相同的通知ID處理記錄
     */
    public function isNotificationAlreadyProcessed(string $notificationId): bool
    {
        try {
            $tableName = $this->resourceConnection->getTableName('qware_ticket_record');
            $select = $this->connection->select()
                ->from($tableName, 'COUNT(*)')
                ->where('used_transaction_no = ?', $notificationId);

            $count = $this->connection->fetchOne($select);
            return (int)$count > 0;

        } catch (\Exception $e) {
            $this->logger->error('[QwareTicketResource] Error checking notification: ' . $e->getMessage(), [
                'notification_id' => $notificationId
            ]);
            return false;
        }
    }

    /**
     * 獲取票券的狀態描述
     */
    public function getStatusDescription(int $status): string
    {
        switch ($status) {
            case QwareTicketRecord::STATUS_RETURNED:
                return '已退貨';
            case QwareTicketRecord::STATUS_IMPORTED:
                return '已匯入';
            case QwareTicketRecord::STATUS_USED:
                return '已使用';
            default:
                return '未知狀態';
        }
    }

    /**
     * 更新對應的 customer_ticket 狀態
     */
    private function updateCustomerTicketStatus(QwareTicketRecord $ticketRecord, int $status): void
    {
        try {
            // 根據 QwareTicketRecord 找到對應的 CustomerTicket
            $customerTicket = $this->customerTicketRepository->getByTypeAndTicketRecordId(
                VirtualProductType::TYPE_QWARE_TICKET,
                $ticketRecord->getId()
            );

            if ($customerTicket) {
                $oldStatus = $customerTicket->getStatus();
                $customerTicket->setStatus($status);
                
                // 如果是核銷狀態，記錄核銷時間
                if ($status === TicketStatus::STATUS_USED) {
                    // 優先使用安源提供的交易時間，如果沒有才用當前時間
                    $transDate = $ticketRecord->getUsedDate();
                    $redeemedAt = $transDate ?: date('Y-m-d H:i:s');
                    $customerTicket->setRedeemedAt($redeemedAt);
                }
                
                $this->customerTicketRepository->save($customerTicket);

                $this->logger->info('[QwareTicketResource] Customer ticket status updated', [
                    'customer_ticket_id' => $customerTicket->getId(),
                    'qware_record_id' => $ticketRecord->getId(),
                    'qware_sn' => $ticketRecord->getQwareSn(),
                    'old_status' => $oldStatus,
                    'new_status' => $status
                ]);
            } else {
                $this->logger->warning('[QwareTicketResource] Customer ticket not found', [
                    'qware_record_id' => $ticketRecord->getId(),
                    'qware_sn' => $ticketRecord->getQwareSn()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('[QwareTicketResource] Error updating customer ticket status: ' . $e->getMessage(), [
                'qware_record_id' => $ticketRecord->getId(),
                'qware_sn' => $ticketRecord->getQwareSn(),
                'target_status' => $status,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
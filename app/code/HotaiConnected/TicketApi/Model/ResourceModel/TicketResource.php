<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\TicketApi\Model\TicketApiMerchant;
use Branch8\TicketApi\Model\TicketApiMerchantRepository;
use Branch8\TicketApi\Model\TicketApiBrandRepository;
use Branch8\TicketApi\Model\TicketApiPermissionRepository;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use Psr\Log\LoggerInterface;
use Branch8\TicketApi\Helper\Api;

class TicketResource extends AbstractDb
{
    // 狀態常數
    const STATUS_OVER_DUE  = -3;  // 已過期
    const STATUS_RETURNED  = -2;  // 已退貨(已取消)
    const STATUS_ERROR     = -1;  // 異常
    const STATUS_IMPORTED  = 0;   // 初始匯入
    const STATUS_ALLOCATED = 1;   // 預分配(已指定給quote_item)
    const STATUS_UNUSED    = 2;   // 未使用;已賣出
    const STATUS_USED      = 3;   // 已使用

    /**
     * @var Request 
     */
    private Request $request;

    /**
     * @var HotaiCoreCommonHelper 
     */
    private HotaiCoreCommonHelper $hotaiCoreCommonHelper;

    /**
     * @var LoggerInterface 
     */
    private LoggerInterface $logger;

    /**
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * @var TicketApiMerchantRepository
     */
    private TicketApiMerchantRepository $merchantRepository;

    /**
     * @var TicketApiBrandRepository
     */
    private TicketApiBrandRepository $brandRepository;

    /**
     * @var TicketApiPermissionRepository
     */
    private TicketApiPermissionRepository $permissionRepository;

    /**
     * @var SellerCollectionFactory
     */
    private SellerCollectionFactory $sellerCollectionFactory;

    /**
     * @var TicketApiMerchant|null
     */
    private ?TicketApiMerchant $merchant = null;

    /**
     * @var Api
     */
    private Api $apiHelper;
    
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        Request $request,
        LoggerInterface $logger,
        EventManager $eventManager,
        TicketApiMerchantRepository $merchantRepository,
        TicketApiBrandRepository $brandRepository,
        TicketApiPermissionRepository $permissionRepository,
        SellerCollectionFactory $sellerCollectionFactory,
        Api $apiHelper,
        ?string $connectionName = null
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->request = $request;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->merchantRepository = $merchantRepository;
        $this->brandRepository = $brandRepository;
        $this->permissionRepository = $permissionRepository;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->apiHelper = $apiHelper;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('family_bonus_pin_ticket_record_v2', 'id');
        date_default_timezone_set('Asia/Taipei');
    }

    /**
     * Check if transaction number exists
     *
     * @param  string $transactionNo
     * @return bool
     */
    public function isTransactionExists(string $transactionNo): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from('family_bonus_pin_ticket_record_v2')
            ->where('used_transaction_no = ?', $transactionNo)
            ->limit(1);
       
        return (bool)$connection->fetchOne($select);
    }

    /**
     * Get record ID by serial number
     *
     * @param  string $serialNo
     * @return int|null
     */
    public function getRecordIdBySerialNumber(string $serialNo): ?int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                'family_bonus_pin_ticket_record_v2',
                ['record_id']
            )
            ->where('serial_number = ?', $serialNo)
            ->limit(1);

        $recordId = $connection->fetchOne($select);
    
        return $recordId ? (int)$recordId : null;
    }

    /**
     * Check if ticket is already used
     *
     * @param  int $ticketId
     * @return bool
     */
    public function isTicketUsed(int $ticketId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                'family_bonus_pin_ticket_record_v2',
                [new \Zend_Db_Expr('COUNT(*) as count')]
            )
            ->where('record_id = ?', $ticketId)
            ->where('status != ?', self::STATUS_UNUSED)
            ->limit(1);

        $count = (int)$connection->fetchOne($select);
    
        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Check if ticket is not yet available
     *
     * @param  int $ticketId
     * @return bool
     */
    public function isTicketNotYetAvailable(int $ticketId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('record_id = ?', $ticketId)
            ->where('status = ?', self::STATUS_UNUSED)
            ->where('use_start_time IS NOT NULL')
            ->where('UNIX_TIMESTAMP(use_start_time) > UNIX_TIMESTAMP(NOW())')
            ->limit(1);

        return (bool)$connection->fetchOne($select);
    }

    /**
     * Check if ticket is overdue
     *
     * @param  int $ticketId
     * @return bool
     */
    public function isTicketOverdue(int $ticketId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('record_id = ?', $ticketId)
            ->where(
                '(' .
                $connection->quoteInto('status = ?', self::STATUS_UNUSED) .
                ' AND use_end_time IS NOT NULL' .
                ' AND UNIX_TIMESTAMP(use_end_time) < UNIX_TIMESTAMP(NOW())' .
                ') OR ' .
                $connection->quoteInto('status = ?', self::STATUS_OVER_DUE)
            )
            ->limit(1);

        return (bool)$connection->fetchOne($select);
    }

    /**
     * Update event ticket to used status
     *
     * @param  string $serialNo
     * @return void
     */
    public function updateEventTicketToUsed(string $serialNo, array $detail): bool
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            // 確認票券存在
            $select = $connection->select()
                ->from($this->getTable('ticket_event_ticket'), ['entity_id'])
                ->where('serial_number = ?', $serialNo);
        
            $ticketId = $connection->fetchOne($select);

            // 查無資料
            if (!$ticketId) {
                $connection->rollBack();
                return false;
            }
            $redeemedAt = time();//GMT +8
            // 更新狀態
            $this->getConnection()->update(
                $this->getTable('ticket_event_ticket'),
                [
                    'status' => 3, 
                    'redeemed_at' => date("Y-m-d H:i:s", $redeemedAt - 8*60*60), 
                    'used_transaction_no' => $detail['TRAN_NO'] ?? '',
                    'used_store_no' => $detail['STORE_CODE'] ?? ''
                ],
                ['serial_number = ?' => $serialNo]
            );

            // 更新客戶票券表
            $customerTicketUpdated = $this->getConnection()->update(
                $this->getTable(CustomerTicket::TABLE_NAME),
                ['status' => self::STATUS_USED, 'redeemed_at' => date("Y-m-d H:i:s", $redeemedAt)],
                [
                    'ticket_table_name = ?' => 'ticket_event_ticket',
                    'ticket_table_record_id = ?' => $ticketId
                ]
            );
            
            $connection->commit();

            return true;
    
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->critical(
                '[family_notify] Failed to update event ticket status: ' . $e->getMessage(),
                [
                    'serial_no' => $serialNo,
                    'trace' => $e->getTraceAsString()
                ]
            );
            return false;
        }
    }

    /**
     * Update ticket to used status
     *
     * @param  int    $ticketId
     * @param  string $currentMemo
     * @return bool
     * @throws \Exception
     */
    public function updateTicketToUsed(int $ticketId, string $serialNo, array $detail): bool
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        
        try {
            $this->logTicketStatus($ticketId, "Notify used update.");

            // 更新票券表
            $updateData = [
                'status'              => self::STATUS_USED,
                'used_date'           => date("Y-m-d H:i:s"),
                'used_transaction_no' => $detail['TRAN_NO'] ?? '',
                'used_store_no'       => $detail['STORE_CODE'] ?? '',
                'used_count'          => new \Zend_Db_Expr('used_count + 1')
            ];

            $ticketUpdated = $this->getConnection()->update(
                $this->getMainTable(),
                $updateData,
                ['record_id = ?' => $ticketId]
            );

            // 更新客戶票券表
            $customerTicketUpdated = $this->getConnection()->update(
                $this->getTable(CustomerTicket::TABLE_NAME),
                ['status' => self::STATUS_USED, 'redeemed_at' => date("Y-m-d H:i:s")],
                [
                    'ticket_table_name = ?' => 'family_bonus_pin_ticket_record_v2',
                    'ticket_table_record_id = ?' => $ticketId
                ]
            );

            $connection->commit();
            return true;
            
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->critical(
                '[family_notify] Failed to update ticket status: ' . $e->getMessage(),
                [
                    'ticket_id' => $ticketId,
                    'trace' => $e->getTraceAsString()
                ]
            );
            return false;
        }
    }

    /**
     * Log ticket status change
     *
     * @param  int    $recordId
     * @param  string $title  
     * @return bool
     */
    public function logTicketStatus(int $recordId, string $title): bool 
    {
        try {
            $connection = $this->getConnection();
   
            // 取得票券資訊
            $select = $connection->select()
                ->from(
                    'family_bonus_pin_ticket_record_v2',
                    ['status', 'memo', 'use_start_time', 'use_end_time']
                )
                ->where('record_id = ?', $recordId)
                ->limit(1);
       
            $result = $connection->fetchRow($select);
            if (!$result) {
                return false;
            }

            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $result['memo'],
                [
                    "Title" => $title,
                    "Current Status" => $result['status'],
                    "Use Start Time" => $result['use_start_time'],
                    "Use End Time" => $result['use_end_time'],
                    "IP" => $this->request->getClientIp(),
                    "Datetime" => date("Y-m-d H:i:s"),
                    "Timestamp" => time(),
                ]
            );

            $affected = $connection->update(
                'family_bonus_pin_ticket_record_v2',
                ['memo' => $memo],
                ['record_id = ?' => $recordId]
            );

            return $affected > 0;
        } catch (\Exception $e) {
            $this->logger->error(
                '[family_notify] Failed to log ticket status', 
                [
                'record_id' => $recordId,
                'error' => $e->getMessage()
                ]
            );
            return false;
        }
    }

    /**
     * fire event by recordId
     *
     * @param int $recordId 票券記錄ID
     * @return void
     */
    public function fireEventByRecordId(int $recordId): void
    {
        try {
            $connection = $this->getConnection();
            
            // 從票券記錄取得 sales_order_item_id
            $select = $connection->select()
                ->from($this->getMainTable(), ['sales_order_item_id'])
                ->where('record_id = ?', $recordId)
                ->limit(1);
            
            $salesOrderItemId = $connection->fetchOne($select);
            
            if (!$salesOrderItemId) {
                return;
            }
            
            // 從 sales_order_item 取得 order_id
            $select = $connection->select()
                ->from('sales_order_item', ['order_id'])
                ->where('item_id = ?', $salesOrderItemId)
                ->limit(1);
            
            $orderId = $connection->fetchOne($select);
            
            if (!$orderId) {
                return;
            }
            
            $this->apiHelper->fireEventAfterUseHandle((int) $orderId);
            
            return;
            
        } catch (\Exception $e) {
            $this->logger->critical(
                '[family_notify] Failed to fire event by record id: ' . $e->getMessage(),
                [
                    'record_id' => $recordId,
                    'trace' => $e->getTraceAsString()
                ]
            );
            return;
        }
    }
}
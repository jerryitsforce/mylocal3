<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Model\Api;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use HotaiConnected\TicketApi\Api\QwareNotifyInterface;
use HotaiConnected\TicketApi\Model\ResourceModel\QwareTicketResource;
use HotaiConnected\Qware\Helper\Common as QwareCommonHelper;
use HotaiConnected\Qware\Helper\Common as CommonHelper;
use Branch8\TicketApi\Helper\Api as TicketApiHelper;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Qware 通知處理 API
 * 
 * 支援的通知類型：
 * - Type = 1: 一般兌換/取消兌換通知
 *   - Action = 1: 兌換操作
 *   - Action = 2: 取消兌換操作
 * - Type = 10: i禮贈/商品卡兌換通知
 */
class QwareNotify implements QwareNotifyInterface
{
    private const SUCCESS_CODE = 200;
    private const FAILURE_CODE = 500;
    private const TYPE_GENERAL_EXCHANGE = 1;
    private const TYPE_GIFT_CARD_EXCHANGE = 10;
    
    // 一般兌換類型
    private const PRODUCT_TYPE = 1;
    private const CASH_TYPE = 2;
    private const ACTION_EXCHANGE = 1;
    private const ACTION_CANCEL = 2;

    // i禮贈/商品卡兌換 LastAmt 狀態
    private const GIFT_CARD_LAST_AMT_EXCHANGE = 0;
    private const GIFT_CARD_LAST_AMT_CANCEL = 1;

    /**
     * @var Request 
     */
    private Request $request;

    /**
     * @var Response 
     */
    private Response $response;

    /**
     * @var LoggerInterface 
     */
    private LoggerInterface $logger;

    /**
     * @var QwareTicketResource 
     */
    private QwareTicketResource $qwareTicketResource;

    /**
     * @var TicketApiHelper
     */
    private TicketApiHelper $ticketApiHelper;

    /**
     * @var OrderItemRepositoryInterface
     */
    private OrderItemRepositoryInterface $orderItemRepository;

    /**
     * @var QwareCommonHelper
     */
    private QwareCommonHelper $qwareCommonHelper;

    /**
     * @var CommonHelper
     */
    private CommonHelper $commonHelper;

    public function __construct(
        Request $request,
        Response $response,
        LoggerInterface $logger,
        QwareTicketResource $qwareTicketResource,
        TicketApiHelper $ticketApiHelper,
        OrderItemRepositoryInterface $orderItemRepository,
        QwareCommonHelper $qwareCommonHelper,
        CommonHelper $commonHelper
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->qwareTicketResource = $qwareTicketResource;
        $this->ticketApiHelper = $ticketApiHelper;
        $this->orderItemRepository = $orderItemRepository;
        $this->qwareCommonHelper = $qwareCommonHelper;
        $this->commonHelper = $commonHelper;
    }

    /**
     * @inheritDoc
     */
    public function handle(): void
    {
        date_default_timezone_set('Asia/Taipei');

        try {
            $this->logRequest();

            // 檢查 IP 白名單
            if (!$this->validateClientIp()) {
                $this->sendErrorResponse('Access denied: IP not allowed');
                return;
            }

            $requestData = $this->getRequestData();
            $requestBody = $this->request->getContent();

            // 驗證 X-CHECKSUM
            if (!$this->validateChecksum($requestBody)) {
                $this->sendErrorResponse('Invalid checksum');
                return;
            }

            // 驗證必要欄位
            if (!$this->validateRequestData($requestData)) {
                $this->sendErrorResponse('Invalid request data');
                return;
            }

            $notificationId = $requestData['NotificationId'];
            $type = $requestData['Type'];
            $data = $requestData['Data'];

            // 檢查是否已處理過相同的通知
            if ($this->qwareTicketResource->isNotificationAlreadyProcessed($notificationId)) {
                $this->logger->info('[qware_notify] Notification already processed', [
                    'notification_id' => $notificationId
                ]);
                $this->sendSuccessResponse();
                return;
            }

            // 記錄已驗證的請求到日誌
            $this->logValidatedRequest($requestData);

            // 處理不同類型的通知
            switch ($type) {
                case self::TYPE_GENERAL_EXCHANGE:
                    $this->handleGeneralExchange($requestData);
                    break;
                case self::TYPE_GIFT_CARD_EXCHANGE:
                    $this->handleGiftCardExchange($requestData);
                    break;
                default:
                    $this->sendErrorResponse('Unsupported notification type');
                    return;
            }

            $this->sendSuccessResponse();

        } catch (\Exception $e) {
            $this->logger->critical('[qware_notify] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->sendErrorResponse('Internal server error');
        }
    }

    /**
     * 驗證客戶端 IP 是否在白名單中
     */
    private function validateClientIp(): bool
    {
        $clientIp = $this->request->getClientIp();
        $allowedIps = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_NOTIFICATION_ALLOWED_IPS);
        
        if (empty($allowedIps)) {
            $this->logger->warning('[qware_notify] No IP whitelist configured, allowing all IPs', [
                'client_ip' => $clientIp
            ]);
            return true;
        }

        // 將白名單 IP 字串轉換為陣列
        $allowedIpArray = array_map('trim', explode(',', $allowedIps));
        
        // 檢查客戶端 IP 是否在白名單中
        $isAllowed = in_array($clientIp, $allowedIpArray);
        
        if (!$isAllowed) {
            $this->logger->error('[qware_notify] IP access denied', [
                'client_ip' => $clientIp,
                'allowed_ips' => $allowedIpArray
            ]);
        }

        return $isAllowed;
    }

    /**
     * 驗證 X-CHECKSUM
     */
    private function validateChecksum(string $requestBody): bool
    {
        $headers = $this->request->getHeaders();
        $receivedChecksum = $headers->get('X-CHECKSUM');
        
        if (!$receivedChecksum) {
            $this->logger->error('[qware_notify] Missing X-CHECKSUM header');
            return false;
        }

        // 從配置中獲取安全代碼
        $securityCode = $this->getSecurityCode();
        if (!$securityCode) {
            $this->logger->error('[qware_notify] Security code not configured');
            return false;
        }

        // 計算預期的 checksum
        $expectedChecksum = strtoupper(md5($securityCode . $requestBody));
        
        $isValid = hash_equals($expectedChecksum, strtoupper($receivedChecksum->getFieldValue()));
        
        if (!$isValid) {
            $this->logger->error('[qware_notify] Checksum validation failed', [
                'expected' => $expectedChecksum,
                'received' => $receivedChecksum->getFieldValue()
            ]);
        }

        return $isValid;
    }

    /**
     * 驗證請求資料
     */
    private function validateRequestData(array $requestData): bool
    {
        if (!isset($requestData['NotificationId']) || 
            !isset($requestData['Type']) || 
            !isset($requestData['Data'])) {
            return false;
        }

        return true;
    }

    /**
     * 處理一般兌換通知
     */
    private function handleGeneralExchange(array $notificationData): void
    {
        $notificationId = $notificationData['NotificationId'];
        $data = $notificationData['Data'];
        
        // 驗證必要欄位
        if (!isset($data['Sn']) || !isset($data['Action'])) {
            throw new \Exception('Missing required fields: Sn or Action');
        }
        
        $qwareSn = $data['Sn'];
        $type = $data['Type'] ?? 0;
        $action = $data['Action'];
        $qty = $data['Qty'] ?? 0;
        $transDate = $data['TransDate'] ?? '';
        $branchCode = $data['BranchCode'] ?? '';
        $branchName = $data['BranchName'] ?? '';
        $posCode = $data['PosCode'] ?? '';

        $this->logger->info('[qware_notify] Processing general exchange notification', [
            'notification_id' => $notificationId,
            'qware_sn' => $qwareSn,
            'type' => $type,
            'action' => $action,
            'qty' => $qty,
            'trans_date' => $transDate,
            'branch_code' => $branchCode,
            'branch_name' => $branchName,
            'pos_code' => $posCode
        ]);

        // 根據 Action 參數處理不同的操作
        if ($action === self::ACTION_EXCHANGE) {
            // Action = 1: 兌換操作
            $this->processTicketExchange($qwareSn, $notificationData);
        } elseif ($action === self::ACTION_CANCEL) {
            // Action = 2: 取消兌換操作  
            $this->processTicketCancel($qwareSn, $notificationData);
        } else {
            throw new \Exception('Unsupported action: ' . $action);
        }
    }

    /**
     * 處理 i禮贈/商品卡兌換通知
     */
    private function handleGiftCardExchange(array $notificationData): void
    {
        $notificationId = $notificationData['NotificationId'];
        $data = $notificationData['Data'];
        
        // 驗證商品卡通知必要欄位
        if (!isset($data['Pincode']) || !isset($data['Type'])) {
            throw new \Exception('Missing required fields for gift card: Pincode or Type');
        }
        
        $pincode = $data['Pincode'];
        $type = $data['Type']; // G：i禮贈 C:商品卡
        $lastAmt = $data['LastAmt'] ?? '';
        $tradeTime = $data['TradeTime'] ?? '';
        $storeId = $data['StoreId'] ?? '';
        $store = $data['Store'] ?? '';

        // 透過 Pincode 查資料庫 (qware_pwd) 取得對應的 Qware SN
        $qwareSn = $this->qwareTicketResource->getQwareSnByPincode($pincode);

        $this->logger->info('[qware_notify] Processing gift card/i-gift exchange notification for redemption', [
            'notification_id' => $notificationId,
            'pincode' => $pincode,
            'qware_sn' => $qwareSn,
            'type' => $type,
            'last_amt' => $lastAmt,
            'trade_time' => $tradeTime,
            'store_id' => $storeId,
            'store' => $store
        ]);

        if (empty($qwareSn)) {
            throw new \Exception('Unable to resolve Qware SN from Pincode: ' . $pincode);
        }

        // 依 LastAmt 分流：0 = 兌換、1 = 取消核銷，其餘狀態僅記錄不處理
        $lastAmtInt = (int)$lastAmt;
        if ($lastAmtInt === self::GIFT_CARD_LAST_AMT_EXCHANGE) {
            $this->processTicketExchange($qwareSn, $notificationData);
        } elseif ($lastAmtInt === self::GIFT_CARD_LAST_AMT_CANCEL) {
            $this->processTicketCancel($qwareSn, $notificationData);
        } else {
            $this->logger->warning('[qware_notify] Unsupported LastAmt value, skip processing', [
                'notification_id' => $notificationId,
                'pincode' => $pincode,
                'qware_sn' => $qwareSn,
                'last_amt' => $lastAmt
            ]);
        }
    }

    /**
     * 處理票券兌換
     */
    private function processTicketExchange(string $qwareSn, array $notificationData): void
    {
        // 根據 qware_sn 查找票券記錄
        $ticketRecord = $this->qwareTicketResource->getTicketRecordByQwareSn($qwareSn);
        if (!$ticketRecord) {
            throw new \Exception('Ticket not found: ' . $qwareSn);
        }

        // 檢查票券狀態（冪等處理：已完成的狀態直接回傳成功）
        if ($this->qwareTicketResource->isTicketUsed($ticketRecord)) {
            $this->qwareTicketResource->logTicketOperation($ticketRecord, "Qware notify received but ticket already used");
            return;
        }

        if (!$this->qwareTicketResource->isTicketAvailable($ticketRecord)) {
            $this->qwareTicketResource->logTicketOperation($ticketRecord, "Qware notify received but ticket not available");
            throw new \Exception('Ticket not available: ' . $qwareSn);
        }

        // 更新票券狀態為已使用
        $success = $this->qwareTicketResource->updateTicketToUsed($ticketRecord, $notificationData);
        
        if (!$success) {
            throw new \Exception('Failed to update ticket status: ' . $qwareSn);
        }

        // 觸發訂單狀態檢查事件
        try {
            // 從 qwareTicketRecord 取得 sales_order_item_id
            $salesOrderItemId = $ticketRecord->getSalesOrderItemId();
            // 透過 OrderItemRepository 取得 order_id
            $orderItem = $this->orderItemRepository->get($salesOrderItemId);
            $orderId = $orderItem->getOrderId();
            
            // 觸發事件
            $this->ticketApiHelper->fireEventAfterUseHandle((int) $orderId);
            
        } catch (\Exception $eventException) {
            $this->logger->error('[qware_notify] Failed to fire event after use handle', [
                'record_id' => $ticketRecord->getId(),
                'qware_sn' => $qwareSn,
                'error' => $eventException->getMessage()
            ]);
        }

        $this->logger->info('[qware_notify] Ticket exchanged successfully', [
            'qware_sn' => $qwareSn,
            'record_id' => $ticketRecord->getId(),
            'notification_id' => $notificationData['NotificationId'] ?? null
        ]);
    }

    /**
     * 處理票券取消兌換
     */
    private function processTicketCancel(string $qwareSn, array $notificationData): void
    {
        // 根據 qware_sn 查找票券記錄
        $ticketRecord = $this->qwareTicketResource->getTicketRecordByQwareSn($qwareSn);
        if (!$ticketRecord) {
            throw new \Exception('Ticket not found: ' . $qwareSn);
        }

        if ($this->qwareTicketResource->isTicketReturned($ticketRecord)) {
            $this->qwareTicketResource->logTicketOperation($ticketRecord, "Qware notify received but ticket already returned");
            return;
        }

        // 只有已使用的票券才能取消
        if (!$this->qwareTicketResource->isTicketUsed($ticketRecord)) {
            $this->qwareTicketResource->logTicketOperation($ticketRecord, "Cancel request received but ticket not used");
            throw new \Exception('Ticket not used, cannot cancel: ' . $qwareSn);
        }

        // 取消兌換
        $success = $this->qwareTicketResource->updateTicketToCancelled($ticketRecord, $notificationData);
        
        if (!$success) {
            throw new \Exception('Failed to cancel ticket: ' . $qwareSn);
        }

        // 觸發訂單狀態檢查事件
        try {
            // 從 qwareTicketRecord 取得 sales_order_item_id
            $salesOrderItemId = $ticketRecord->getSalesOrderItemId();
            // 透過 OrderItemRepository 取得 order_id
            $orderItem = $this->orderItemRepository->get($salesOrderItemId);
            $orderId = $orderItem->getOrderId();
            
            // 觸發取消事件
            $this->ticketApiHelper->fireEventAfterCancelHandle((int) $orderId);
            
        } catch (\Exception $eventException) {
            $this->logger->error('[qware_notify] Failed to fire event after cancel handle', [
                'record_id' => $ticketRecord->getId(),
                'qware_sn' => $qwareSn,
                'error' => $eventException->getMessage()
            ]);
            // 不拋出異常，避免影響主要流程
        }

        $this->logger->info('[qware_notify] Ticket cancelled successfully', [
            'qware_sn' => $qwareSn,
            'record_id' => $ticketRecord->getId(),
            'notification_id' => $notificationData['NotificationId'] ?? null
        ]);
    }

    /**
     * 獲取安全代碼
     */
    private function getSecurityCode(): ?string
    {
        // 使用與其他 API 配置相同的方式獲取安全代碼
        return $this->qwareCommonHelper->getApiConfigByConfigCode(QwareCommonHelper::CONFIG_CODE_SECURITY_CODE);
    }

    /**
     * Log request
     */
    private function logRequest(): void
    {
        $requestContent = $this->request->getContent();
        $requestData = [
            'headers' => $this->request->getHeaders()->toArray(),
            'body' => json_decode($requestContent, true)
        ];
        
        $this->logger->info('[qware_notify] API Request:', [
            'timestamp' => date('Y-m-d H:i:s'),
            'request' => json_encode($requestData, JSON_PRETTY_PRINT)
        ]);
    }

    /**
     * Log validated request after successful verification
     */
    private function logValidatedRequest(array $requestData): void
    {
        $this->logger->info('[qware_notify] Validated Request:', [
            'timestamp' => date('Y-m-d H:i:s'),
            'notification_id' => $requestData['NotificationId'] ?? null,
            'type' => $requestData['Type'] ?? null,
            'data' => $requestData['Data'] ?? null,
            'client_ip' => $this->request->getClientIp(),
            'user_agent' => $this->request->getHeader('User-Agent'),
            'request_body' => json_encode($requestData, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Get request data
     */
    private function getRequestData(): array
    {
        return json_decode($this->request->getContent(), true) ?? [];
    }

    /**
     * Send success response
     */
    private function sendSuccessResponse(): void
    {
        $response = self::SUCCESS_CODE;
        
        $this->logger->info('[qware_notify] API Response:', [
            'timestamp' => date('Y-m-d H:i:s'),
            'response' => $response
        ]);

        $this->response
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'text/plain', true)
            ->setBody((string) $response)
            ->sendResponse();
    }

    /**
     * Send error response
     */
    private function sendErrorResponse(string $message): void
    {
        $response = self::FAILURE_CODE . ':' . substr($message, 0, 50);
        
        $this->logger->error('[qware_notify] API Error Response:', [
            'timestamp' => date('Y-m-d H:i:s'),
            'response' => $response,
            'error' => $message
        ]);

        $this->response
            ->setHttpResponseCode(500)
            ->setHeader('Content-Type', 'text/plain', true)
            ->setBody($response)
            ->sendResponse();
    }
}
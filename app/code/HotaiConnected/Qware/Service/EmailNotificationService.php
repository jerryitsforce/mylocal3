<?php

namespace HotaiConnected\Qware\Service;

use Branch8\HotaiCore\Service\MailService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Ecpay\General\Helper\Foundation\GeneralHelper;

class EmailNotificationService extends MailService
{
    const TEMPLATE_CRON_RETRY_FAILED = 'qware_cron_retry_failed_template';
    const TEMPLATE_API_ERROR = 'qware_api_error_template';
    
    const CONFIG_PATH_API_ERROR_RECIPIENTS = 'qware/notification/api_error_recipients';

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Escaper $escaper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        GeneralHelper $generalHelper,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        parent::__construct($inlineTranslation, $escaper, $storeManager, $transportBuilder, $generalHelper);
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * 發送 Cron Job 重試失敗通知
     *
     * @param string $cronJobName Cron Job 名稱
     * @param int $orderItemId 訂單項目ID
     * @param string $errorMessage 錯誤訊息
     * @return void
     */
    public function sendCronRetryFailedNotification(
        string $cronJobName,
        int $orderItemId,
        string $errorMessage
    ): void {
        try {
            // 取得訂單相關資訊
            $orderItem = $this->orderItemRepository->get($orderItemId);
            $order = $this->orderRepository->get($orderItem->getOrderId());
            $product = $orderItem->getProduct();

            $templateVars = [
                'cron_job_name' => $cronJobName,
                'order_item_id' => $orderItemId,
                'order_increment_id' => $order->getIncrementId(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                'product_name' => $product->getName(),
                'product_sku' => $product->getSku(),
                'error_message' => $errorMessage,
                'failed_time' => date('Y-m-d H:i:s'),
                'order_total' => $order->getGrandTotal(),
                'order_currency' => $order->getOrderCurrencyCode(),
                'store_name' => $this->storeManager->getStore($order->getStoreId())->getName()
            ];

            $this->sendToAdmins(
                self::TEMPLATE_CRON_RETRY_FAILED,
                $templateVars,
                "Qware {$cronJobName} 重試失敗通知 - 訂單 #{$order->getIncrementId()}"
            );

            $this->logger->info('[Qware Email] Cron retry failed notification sent', [
                'cron_job' => $cronJobName,
                'order_item_id' => $orderItemId,
                'order_id' => $order->getIncrementId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[Qware Email] Failed to send cron retry notification', [
                'error' => $e->getMessage(),
                'cron_job' => $cronJobName,
                'order_item_id' => $orderItemId
            ]);
        }
    }

    /**
     * 發送 API 錯誤通知
     *
     * @param string $apiMethod API 方法名稱
     * @param int $orderItemId 訂單項目ID
     * @param int $httpStatus HTTP 狀態碼
     * @param int|null $apiCode API 回應碼
     * @param string $errorMessage 錯誤訊息
     * @return void
     */
    public function sendApiErrorNotification(
        string $apiMethod,
        int $orderItemId,
        int $httpStatus,
        ?int $apiCode,
        string $errorMessage
    ): void {
        try {
            // 取得訂單相關資訊
            $orderItem = $this->orderItemRepository->get($orderItemId);
            $order = $this->orderRepository->get($orderItem->getOrderId());
            $product = $orderItem->getProduct();

            $templateVars = [
                'api_method' => $apiMethod,
                'order_item_id' => $orderItemId,
                'order_increment_id' => $order->getIncrementId(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                'product_name' => $product->getName(),
                'product_sku' => $product->getSku(),
                'http_status' => $httpStatus,
                'api_code' => $apiCode ?? 'N/A',
                'error_message' => $errorMessage,
                'failed_time' => date('Y-m-d H:i:s'),
                'order_total' => $order->getGrandTotal(),
                'order_currency' => $order->getOrderCurrencyCode(),
                'store_name' => $this->storeManager->getStore($order->getStoreId())->getName()
            ];

            $this->sendToAdmins(
                self::TEMPLATE_API_ERROR,
                $templateVars,
                "Qware API 錯誤通知 - {$apiMethod} - 訂單 #{$order->getIncrementId()}"
            );

            $this->logger->info('[Qware Email] API error notification sent', [
                'api_method' => $apiMethod,
                'order_item_id' => $orderItemId,
                'order_id' => $order->getIncrementId(),
                'http_status' => $httpStatus,
                'api_code' => $apiCode
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[Qware Email] Failed to send API error notification', [
                'error' => $e->getMessage(),
                'api_method' => $apiMethod,
                'order_item_id' => $orderItemId
            ]);
        }
    }

    /**
     * 發送郵件給所有管理員
     *
     * @param string $templateId 模板ID
     * @param array $templateVars 模板變數
     * @param string $subject 郵件主題
     * @return void
     */
    protected function sendToAdmins(string $templateId, array $templateVars, string $subject): void
    {
        $recipients = $this->getAdminRecipients();
        
        // 如果沒有收件人，記錄 log 並直接返回
        if (empty($recipients)) {
            $this->logger->info('[Qware Email] No recipients configured, email not sent', [
                'template' => $templateId,
                'subject' => $subject
            ]);
            return;
        }

        $this->templateId = $templateId;
        $this->subject = $subject;
        
        foreach ($recipients as $recipient) {
            $this->receiverEmail = $recipient['email'];
            $this->receiverName = $recipient['name'];
            
            try {
                $this->send($templateVars);
            } catch (\Exception $e) {
                $this->logger->error('[Qware Email] Failed to send to admin', [
                    'admin_email' => $recipient['email'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 取得管理員收件人列表
     *
     * @return array
     */
    protected function getAdminRecipients(): array
    {
        $configEmails = $this->scopeConfig->getValue(
            self::CONFIG_PATH_API_ERROR_RECIPIENTS,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($configEmails)) {
            // 如果沒有設定，回傳空陣列（不寄信）
            return [];
        }

        $recipients = [];
        $emails = array_map('trim', explode(',', $configEmails));
        
        foreach ($emails as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = [
                    'email' => $email,
                    'name' => 'Hotai購 營運團隊'
                ];
            }
        }

        return $recipients;
    }

}
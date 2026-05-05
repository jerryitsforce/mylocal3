<?php
declare(strict_types=1);

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;

class LogOrderViewAccess implements ObserverInterface
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $file;

    public function __construct(
        CustomerSession $customerSession,
        AdminSession $adminSession,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->customerSession = $customerSession;
        $this->adminSession = $adminSession;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Log controller access (unified for order view and page access)
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $request = $observer->getEvent()->getRequest();
            $fullActionName = $request->getFullActionName();
            
            // 支援兩種參數名稱：order_id (sales頁面) 和 id (marketplace頁面)
            $orderId = $request->getParam('order_id') ?: $request->getParam('id');
            
            // 取得使用者資訊
            $userId = null;
            $userType = 'guest';
            $userName = 'Guest';

            // 檢查是否為客戶登入
            if ($this->customerSession->isLoggedIn()) {
                $customer = $this->customerSession->getCustomer();
                $userId = $customer->getId();
                $userType = 'customer';
                $userName = $customer->getName() ?: $customer->getEmail();
            }
            // 檢查是否為管理員登入
            elseif ($this->adminSession->isLoggedIn()) {
                $admin = $this->adminSession->getUser();
                if ($admin) {
                    $userId = $admin->getId();
                    $userType = 'admin';
                    $userName = $admin->getUsername();
                }
            }

            // 根據 action 判斷是否為訂單相關頁面或一般頁面存取
            $isOrderAction = $this->isOrderRelatedAction($fullActionName);
            
            // 記錄存取到自定義日誌檔案
            $customLogger = $this->getCustomLogger($isOrderAction);
            $logData = [
                'action' => $fullActionName,
                'user_id' => $userId,
                'user_type' => $userType,
                'user_name' => $userName,
                'ip_address' => $request->getClientIp(),
                'user_agent' => $request->getHeader('User-Agent'),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // 如果是訂單相關且有 order_id，則記錄 order_id
            if ($isOrderAction && $orderId) {
                $logData['order_id'] = $orderId;
            }
            
            $logPrefix = $isOrderAction ? '[order_access]' : '[page_access]';
            $customLogger->info($logPrefix . ' ' . var_export($logData, true));

        } catch (\Exception $exception) {
            // 靜默處理錯誤，避免影響正常流程
            try {
                $this->logger->error('Error logging order view access: ' . $exception->getMessage(), [
                    'exception_type' => get_class($exception),
                    'stack_trace' => $exception->getTraceAsString()
                ]);
            } catch (\Exception $logException) {
                // 如果連日誌記錄都失敗，則完全靜默處理
                error_log('LogOrderViewAccess Observer failed: ' . $exception->getMessage());
            }
        }
    }

    /**
     * 判斷是否為訂單相關動作
     *
     * @param string $fullActionName
     * @return bool
     */
    private function isOrderRelatedAction($fullActionName)
    {
        $orderActions = [
            'sales_order_view',
            'marketplace_order_view',
            'sales_parent_order_view'
        ];
        
        return in_array($fullActionName, $orderActions);
    }

    /**
     * 創建自定義 Logger
     *
     * @param bool $isOrderAction
     * @return LoggerInterface
     */
    private function getCustomLogger($isOrderAction = true)
    {
        try {
            $logPath = $this->directoryList->getPath(DirectoryList::LOG);
            $logFile = $logPath . '/Sales/LogOrderViewAccess/order_view_access_' . date('Y_m_d') . '.log';
            
            // 確保目錄存在
            $logDir = dirname($logFile);
            if (!$this->file->isExists($logDir)) {
                $this->file->createDirectory($logDir, 0755);
            }

            // 使用 Zend_Log_Writer_Stream 而不是 BaseHandler
            
            $writer = new \Zend_Log_Writer_Stream($logFile);
            $customLogger = new \Zend_Log();
            $customLogger->addWriter($writer);
            
            return $customLogger;
            
        } catch (\Exception $e) {
            // 如果創建自定義 logger 失敗，返回標準 logger
            return $this->logger;
        }
    }
}
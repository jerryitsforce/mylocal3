<?php

namespace Branch8\EmailNotification\Cron;

use Branch8\EmailNotification\Helper\GeneralHelper;
use Branch8\EmailNotification\Helper\ScopeConfig;
use Branch8\HotaiCore\Service\MailService;
use Carbon\Carbon;
use Ecpay\General\Helper\Services\Config\MainService;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Zend_Log_Exception;

class OrderNotificationCronJob
{
    public const TEMPLATE_ID = 'branch8_email_notification_not_complete_template';

    protected GeneralHelper      $_loggerInterface;
    protected MainService        $_ecpayMainService;
    protected ResourceConnection $_resourceConnection;
    protected MailService        $_mailService;
    protected ScopeConfig        $_scopeConfig;

    /**
     * @param GeneralHelper $loggerInterface
     * @param MainService $ecpayMainService
     * @param ResourceConnection $_resourceConnection
     * @param MailService $_mailService
     * @param ScopeConfig $_scopeConfig
     */
    public function __construct(
        GeneralHelper $loggerInterface,
        MainService $ecpayMainService,
        ResourceConnection $_resourceConnection,
        MailService $_mailService,
        ScopeConfig $_scopeConfig,
    ) {
        $this->_loggerInterface    = $loggerInterface;
        $this->_ecpayMainService   = $ecpayMainService;
        $this->_resourceConnection = $_resourceConnection;
        $this->_mailService        = $_mailService;
        $this->_scopeConfig        = $_scopeConfig;
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function execute()
    {
        $this->writeLog(
            '-----------------------------Branch8 Email Notification Cron Job----------------------------------'
        );

        $isEnabled = $this->_scopeConfig->getScopeConfig(ScopeConfig::ENABLE);

        if (!$isEnabled) {
            $this->writeLog(
                '-----------------------------Branch8 Email Notification Cron Job: END due to this function is disable ----------------------------------'
            );
            return 0;
        }

        // ecpay not complete orders
        $ecpayOrders = $this->getInvoiceNotCompleteOrders();

        // point not complete orders
        $pointOrders = $this->getPointNotCompleteOrders();

        $emailString = $this->_scopeConfig->getScopeConfig(ScopeConfig::EMAIL_TO);

        $emails = explode(',', $emailString);

        if (count($emails) === 0) {
            $this->writeLog(
                '-----------------------------Branch8 Email Notification Cron Job: END due to no email address ----------------------------------'
            );
            return 0;
        }

        $this->_mailService
            ->setTemplateId(self::TEMPLATE_ID)
            ->setReceiverEmail($emails)
            ->send([
                'ecpay_orders' => $ecpayOrders,
                'point_orders' => $pointOrders,
            ]);

        return $this;
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeLog($message): void
    {
        $fileName = 'cron_email_notification_' . trim(date("Y_m_d"), '/');
        $this->_loggerInterface->writeLog($message, 'cron', $fileName);
    }

    /**
     * @return array
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function getInvoiceNotCompleteOrders(): array
    {
        $orders = [];

        // 判斷發票模組是否啟用
        $ecpayInvoiceEnable = $this->_ecpayMainService->isInvoiceModuleEnable();
        $this->writeLog('Branch8 Email Notification Cron Job => Ecpay Invoice Enable: ' . $ecpayInvoiceEnable);
        if ((int) $ecpayInvoiceEnable === 0) {
            return $orders;
        }

        $nowDateString = Carbon::now()->toDateTimeString();

        $connection = $this->_resourceConnection->getConnection();
        $select     = $connection->select()->from('sales_order', ['entity_id', 'increment_id']);

        $select->where(
            "state in ('processing', 'complete') AND ecpay_invoice_tag = 0 AND is_paid = 1 AND created_at < '" . $nowDateString . "'"
        )->order('entity_id ASC');

        $collection = $connection->fetchAll($select);

        foreach ($collection as $item) {
            $orders[] = ['increment_id' => $item['increment_id']];
        }

        return $orders;
    }

    /**
     * @return array
     */
    private function getPointNotCompleteOrders(): array
    {
        $orders = [];

        $connection = $this->_resourceConnection->getConnection();
        $select     = $connection->select()->from('sales_order', ['entity_id', 'increment_id']);

        $select->where(
            "sales_order.is_paid = 1 and sales_order.point_used_total > 0 and sales_order.hotai_point_deduction_point_complete != 1"
        )->order('entity_id ASC');

        $collection = $connection->fetchAll($select);

        foreach ($collection as $item) {
            $orders[] = ['increment_id' => $item['increment_id']];
        }

        return $orders;
    }
}

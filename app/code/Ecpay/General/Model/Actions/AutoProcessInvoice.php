<?php

namespace Ecpay\General\Model\Actions;

use Branch8\HotaiPoint\Helper\CacheLock as PointCacheLock;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use JsonException;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Zend_Log_Exception;

class AutoProcessInvoice
{
    public const CACHE_KEY      = 'order_auto_invoice_procedure_';
    public const CACHE_LIFETIME = 60 * 30; // 30min

    private GeneralHelper $_loggerInterface;
    private OrderService $_orderService;
    private InvoiceService $_invoiceService;
    private CacheInterface $_cache;
    private PointCacheLock $pointCacheLock;

    public function __construct(
        GeneralHelper $loggerInterface,
        OrderService $orderService,
        InvoiceService $invoiceService,
        CacheInterface $cache,
        PointCacheLock $pointCacheLock
    ) {
        $this->_loggerInterface = $loggerInterface;
        $this->_orderService    = $orderService;
        $this->_invoiceService  = $invoiceService;
        $this->_cache           = $cache;
        $this->pointCacheLock   = $pointCacheLock;
    }

    /**
     * @param int $orderId
     * @return void
     * @throws FileSystemException
     * @throws JsonException
     * @throws LocalizedException
     * @throws Zend_Log_Exception
     */
    public function execute(int $orderId)
    {
        if ($this->pointCacheLock->checkIsPointCommitProcedureLockNow($orderId)) {
            return;
        }

        $cacheKey = self::CACHE_KEY . $orderId;
        if ($this->_cache->load($cacheKey)) {
            return;
        }

        $this->_cache->save(1, $cacheKey, [], self::CACHE_LIFETIME);

        $this->writeLog('OrderAutoProcedure invoiceAutoProcess orderId:' . print_r($orderId, true));

        // 開立發票
        $result = $this->_invoiceService->invoiceIssue($orderId);
        $this->writeLog('OrderAutoProcedure invoiceIssue result:' . print_r($result, true));

        if ($result['code'] === '1005' || $result['code'] === '0999') {
            $comment = '自動開立發票訂單(成功)：' . $result['code'];
        } else {
            // 回傳結果寫入備註
            $comment = '自動開立發票訂單(失敗)，請重新手動開立。錯誤代碼：' . $result['code'];
        }
        $this->_orderService->setOrderCommentForBack($orderId, $comment);
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeLog($message): void
    {
        $fileName = 'cron_orderAutoProcedure_' . trim(date("Y_m_d"), '/');
        $this->_loggerInterface->writeLog($message, 'cron', $fileName);
    }
}

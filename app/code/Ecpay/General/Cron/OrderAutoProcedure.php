<?php

namespace Ecpay\General\Cron;

use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Model\Actions\AutoProcessInvoice;
use JsonException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Zend_Log_Exception;

class OrderAutoProcedure
{
    protected GeneralHelper $_loggerInterface;
    protected OrderService $_orderService;
    protected MainService $_mainService;
    private AutoProcessInvoice $autoProcessInvoice;

    public function __construct(
        GeneralHelper $loggerInterface,
        OrderService $orderService,
        MainService $mainService,
        AutoProcessInvoice $autoProcessInvoice
    ) {
        $this->_loggerInterface      = $loggerInterface;
        $this->_orderService         = $orderService;
        $this->_mainService          = $mainService;
        $this->autoProcessInvoice   = $autoProcessInvoice;
    }

    /**
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws JsonException
     * @throws Zend_Log_Exception
     */
    public function execute()
    {
        $this->writeLog(
            '-----------------------------ECPay Cron Job OrderAutoProcedure----------------------------------'
        );

        // 取得需要處理的訂單編號

        // 判斷發票是否啟用自動開立
        $ecpayInvoiceAuto = $this->_mainService->getInvoiceConfig('enabled_invoice_auto');
        $this->writeLog('OrderAutoProcedure ecpayInvoiceAuto:' . print_r($ecpayInvoiceAuto, true));

        $invoiceOrders = ($ecpayInvoiceAuto == 1) ? $this->_orderService->getOrderForInvoiceAutoProcedure() : [];
        $this->writeLog('OrderAutoProcedure invoiceOrders:' . print_r($invoiceOrders, true));

//        // 判斷物流模組是否啟動
//        $ecpayEnableLogistic = $this->_mainService->isLogisticModuleEnable();
//        $this->writeLog('OrderAutoProcedure ecpayEnableLogistic:'. print_r($ecpayEnableLogistic, true));
//
//        // 判斷物流是否啟用自動開立
//        $ecpayLogisticAuto = $this->_mainService->getLogisticConfig('enable_logistic_auto') ;
//        $this->writeLog('OrderAutoProcedure ecpayLogisticAuto:'. print_r($ecpayLogisticAuto, true));

//        $logisticOrders = ($ecpayEnableLogistic == 1 && $ecpayLogisticAuto == 1) ? $this->_orderService->getOrderForLogisticAutoProcedure() : [];
//        $this->writeLog('OrderAutoProcedure logisticOrders:' . print_r($logisticOrders, true));

        // 訂單編號合併
//        $orders = array_unique(array_merge($invoiceOrders , $logisticOrders));
        $orders = array_unique(array_merge($invoiceOrders, []));

        foreach ($orders as $key => $orderId) {
            $orderId = (int)$orderId;
            // 開立發票
            if (in_array($orderId, $invoiceOrders)) {
                $this->autoProcessInvoice->execute($orderId);
            }
            // 開立物流訂單
//            if (in_array($orderId, $logisticOrders)) {
//                $this->logisticAutoProcess($orderId);
//            }
        }
        $this->writeLog(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true));
        return $this;
    }

//    /**
//     * 自動產生物流單程序
//     *
//     * @param int $orderId
//     */
//    public function logisticAutoProcess(int $orderId)
//    {
//        $this->writeLog('OrderAutoProcedure logisticAutoProcess orderId : ' . print_r($orderId, true));
//
//        // 建立物流訂單
//        $result = $this->_logisticService->logisticCreateOrder($orderId);
//        $this->writeLog('OrderAutoProcedure logisticCreateOrder result:' . print_r($result, true));
//
//        // 關閉自動開立
//        $this->_orderService->setOrderData($orderId, 'ecpay_logistic_auto_tag', 0);
//        if ($result['code'] !== '0999') {
//            // 回傳結果寫入備註
//            $comment          = '自動建立物流訂單(失敗)，請重新手動建立。錯誤代碼：' . $result['code'];
//            $status           = false;
//            $isVisibleOnFront = false;
//            $this->_orderService->setOrderCommentForBack($orderId, $comment, $status, $isVisibleOnFront);
//        }
//    }

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

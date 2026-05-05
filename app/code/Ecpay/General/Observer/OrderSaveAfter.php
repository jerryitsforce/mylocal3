<?php
namespace Ecpay\General\Observer;

use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Ecpay\General\Model\EcpayLogisticFactory;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;

class OrderSaveAfter  implements ObserverInterface
{

    protected $_loggerInterface;
    protected $_urlInterface;

    protected $_ecpayLogisticFactory;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;


    public function __construct(
        GeneralHelper $loggerInterface,
        UrlInterface $urlInterface,

        EcpayLogisticFactory $ecpayLogisticFactory,

        EncryptionsService $encryptionsService,
        OrderService $orderService,
        MainService $mainService,
        InvoiceService $invoiceService,
        LogisticService $logisticService,
        PaymentService $paymentService
    )
    {
        $this->_loggerInterface = $loggerInterface;
        $this->_urlInterface = $urlInterface;

        $this->_ecpayLogisticFactory = $ecpayLogisticFactory;

        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;
    }

    public function execute(Observer $observer) {

        $order = $observer->getEvent()->getOrder();
        $orderId = intval($order->getId());
        $status = $order->getStatus() ;

        // 訂單付款方式
        $paymentMethod = $this->_orderService->getPaymentMethod($orderId);

        $this->_loggerInterface->writeLog('OrderSaveAfter Observer orderId:' . $orderId);
        $this->_loggerInterface->writeLog('OrderSaveAfter Observer status:' . $status);
        $this->_loggerInterface->writeLog('OrderSaveAfter Observer paymentMethod:'.$paymentMethod);

        // 貨到付款判斷
        $situationTag = false;
        if ($paymentMethod == 'cashondelivery' && $status == 'pending') {
            $situationTag = true ;
        }

        if ($paymentMethod != 'cashondelivery' && $status == 'processing') {
            $situationTag = true ;
        }

        $this->_loggerInterface->writeLog('OrderSaveAfter Observer situationTag:'. $situationTag);

        if ($situationTag) {

            // 自動開立發票程序

            // 判斷發票模組是否啟動
            $ecpayEnableInvoice = $this->_mainService->isInvoiceModuleEnable();
            $this->_loggerInterface->writeLog('OrderSaveAfter ecpayEnableInvoice:'. print_r($ecpayEnableInvoice,true));

            // 判斷發票是否啟用自動開立
            $ecpayInvoiceAuto = $this->_mainService->getInvoiceConfig('enabled_invoice_auto') ;
            $this->_loggerInterface->writeLog('OrderSaveAfter ecpayInvoiceAuto:'. print_r($ecpayInvoiceAuto,true));

            if ($ecpayEnableInvoice == 1 && $ecpayInvoiceAuto == 1) {
                $this->_orderService->setOrderData($orderId, 'ecpay_invoice_auto_tag', 1) ;
            }

            // 自動產生物流單程序

            // 判斷物流模組是否啟動
            $ecpayEnableLogistic = $this->_mainService->isLogisticModuleEnable();
            $this->_loggerInterface->writeLog('OrderSaveAfter ecpayEnableLogistic:'. print_r($ecpayEnableLogistic,true));

            // 判斷物流是否啟用自動開立
            $ecpayLogisticAuto = $this->_mainService->getLogisticConfig('enable_logistic_auto') ;
            $this->_loggerInterface->writeLog('OrderSaveAfter ecpayLogisticAuto:'. print_r($ecpayLogisticAuto,true));

            // 判斷是否使用綠界物流
            $shippingMethod = $this->_orderService->getShippingMethod($orderId);
            $isEcpayLogistics = $this->_logisticService->isEcpayLogistics($shippingMethod);

            if ($isEcpayLogistics == 1 && $ecpayEnableLogistic == 1 && $ecpayLogisticAuto == 1) {
                $this->_orderService->setOrderData($orderId, 'ecpay_logistic_auto_tag', 1);
            }
        }

        return $this;
    }
}

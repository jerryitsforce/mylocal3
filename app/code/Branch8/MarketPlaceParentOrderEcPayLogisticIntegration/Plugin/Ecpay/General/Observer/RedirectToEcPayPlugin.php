<?php

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Ecpay\General\Observer;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Branch8\MarketPlaceParentOrderHelpDesk\Model\ParentOrder;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Model\EcpayInvoice;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;


class RedirectToEcPayPlugin
{
    const XML_PATH_ENABLE_SPLIT_ORDER = 'marketplace/mpsplitorder/mpsplitorder_enable';
    private ScopeConfigInterface $scopeConfig;

    private ParentOrderRepository $parentOrderRepository;
    private LoggerInterface $logger;

    private $encryptionService;
    private PaymentService $paymentService;
    private UrlInterface $urlInterface;
    private ResponseFactory $responseFactory;
    private OrderService $orderService;
    private LogisticService $logisticService;
    private MainService $mainService;
    private GeneralHelper $generalHelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ParentOrderRepository $parentOrderRepository
     * @param UrlInterface $urlInterface
     * @param ResponseFactory $responseFactory
     * @param EncryptionsService $encryptionsService
     * @param OrderService $orderService
     * @param MainService $mainService
     * @param PaymentService $paymentService
     * @param LogisticService $logisticService
     * @param GeneralHelper $generalHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfig,
        ParentOrderRepository $parentOrderRepository,
        UrlInterface          $urlInterface,
        ResponseFactory       $responseFactory,
        EncryptionsService    $encryptionsService,
        OrderService          $orderService,
        MainService           $mainService,
        PaymentService        $paymentService,
        LogisticService       $logisticService,
        GeneralHelper         $generalHelper,
        LoggerInterface       $logger
    )
    {
        $this->logger = $logger;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->encryptionService = $encryptionsService;
        $this->paymentService = $paymentService;
        $this->urlInterface = $urlInterface;
        $this->responseFactory = $responseFactory;
        $this->logisticService = $logisticService;
        $this->orderService = $orderService;
        $this->mainService = $mainService;
        $this->generalHelper = $generalHelper;
    }

    /**
     * @param $id
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|\Branch8\MarketPlaceParentOrder\Model\ParentOrder|mixed|string
     */
    private function getParentOrder($id)
    {
        try {
            return $this->parentOrderRepository->get((int)$id);
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @param Observer $observer
     * @param callable $process
     * @param ...$args
     * @return $this
     */
    public function aroundExecute($subject, callable $process, $observer)
    {
        $isEnable = (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE_SPLIT_ORDER);
        if (!$isEnable) {
            return $process($observer);
        }
        /**
         * @see \Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Magento\Checkout\Controller\Onepage::aroundExecute
         * @var $parentOrder ParentOrder
         *
         */
        $parentOrderId = $observer->getData('parent_order_id');
        $this->logger->info(sprintf('Begin process redirect with parent order id:%s', $parentOrderId));
        $parentOrder = $this->getParentOrder($parentOrderId);
        if (empty($parentOrder)) {
            $this->logger->info(sprintf('No information:%s', $parentOrderId));
            return;
        }
        /**
         * @var $detail ParentOrderDetail
         */
        $detail = $parentOrder->getDetail();
        $parentOrderId = $parentOrder->getId();
        $subOrders = $parentOrder->getSubOrders();
        $this->logger->info('RedirectProcess Event $result:');
        $this->logger->info('RedirectProcess Event $orderId:' . print_r($parentOrderId, true));
        $shippingMethod = $detail->getShippingMethod();
        $paymentMethod = $detail->getPaymentMethod();
        $this->logger->info('RedirectProcess Event $shippingMethod:' . $shippingMethod);
        $this->logger->info('RedirectProcess Event $paymentMethod:' . $paymentMethod);
        // 訂單編號加密
        $encOrderId = $this->encryptionService->encrypt($parentOrderId);
        $this->logger->info('RedirectProcess Event $orderId:' . print_r($parentOrderId, true));
        $this->logger->info('RedirectProcess Event $encOrderId:' . print_r($parentOrderId, true));
        $isEcPayment = $this->paymentService->isEcpayPayment($paymentMethod);
        $isEcpayLogistic = $this->logisticService->isEcpayLogistics($shippingMethod);
        if ($isEcpayLogistic) {
            if ($this->logisticService->isEcpayCvsLogistics($shippingMethod)) {
                $cvsOrHomeCheck = 'cvs';
            } else {
                $cvsOrHomeCheck = 'home';
            }
        }
        if ($isEcpayLogistic) {
            if ($cvsOrHomeCheck == 'cvs') {
                $redirectUrl = $this->urlInterface->getUrl("ecpaygeneral/Process/LogisticMapToEcpay");
                $redirectUrl = $redirectUrl . '?id=' . $encOrderId;
                $this->logger->info('RedirectProcess Event $redirectUrl:' . print_r($redirectUrl, true));
                $this->responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
            } elseif ($cvsOrHomeCheck == 'home') {
                if ($isEcPayment) {
                    $this->paymentToEcpay($parentOrderId);
                }
            }
        } else {
            if ($isEcPayment) {
                $this->paymentToEcpay($parentOrderId);
            } else {
                $invoiceType = $detail->getData('ecpay_invoice_type');
                $this->logger->info('RedirectProcess Event $invoiceType:' . print_r($invoiceType, true));
                if (in_array($invoiceType, [
                        EcpayInvoice::ECPAY_INVOICE_TYPE_C,
                        EcpayInvoice::ECPAY_INVOICE_TYPE_D,
                        EcpayInvoice::ECPAY_INVOICE_TYPE_P]
                )
                ) {
                    // 判斷發票模組是否啟動
                    $ecpayEnableInvoice = $this->mainService->isInvoiceModuleEnable();
                    $this->logger->info('RedirectProcess ecpayEnableInvoice:' . print_r($ecpayEnableInvoice, true));
                    // 判斷發票是否啟用自動開立
                    $ecpayInvoiceAuto = $this->mainService->getInvoiceConfig('enabled_invoice_auto');
                    $this->logger->info('RedirectProcess ecpayInvoiceAuto:' . print_r($ecpayInvoiceAuto, true));
                    if ($ecpayEnableInvoice == 1 && $ecpayInvoiceAuto == 1) {
                        foreach ($subOrders as $subOrder) {
                            $this->orderService->setOrderData(
                                $subOrder->getEntityId(),
                                'ecpay_invoice_auto_tag', 1
                            );
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * 轉導到綠界AIO
     *
     * @return void
     */
    private function paymentToEcpay(string $orderId)
    {
        $encOrderId = $this->encryptionService->encrypt($orderId);
        $redirectUrl = $this->urlInterface->getUrl(
            'ecpaygeneral/Process/PaymentToEcpay'
        );
        $redirectUrl = $redirectUrl . '?id=' . $encOrderId;
        $this->logger->info('RedirectProcess Event $redirectUrl:' . print_r($redirectUrl, true));
        $this->responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
    }
}

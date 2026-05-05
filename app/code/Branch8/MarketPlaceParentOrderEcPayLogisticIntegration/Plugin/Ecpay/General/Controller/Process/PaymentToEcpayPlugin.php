<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Ecpay\General\Controller\Process;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Model\ParentOrderDetailService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Psr\Log\LoggerInterface;

class PaymentToEcPayPlugin
{
    const XML_PATH_ENABLE_SPLIT_ORDER = 'marketplace/mpsplitorder/mpsplitorder_enable';
    protected $urlInterface;
    protected $loggerInterface;
    protected $requestInterface;
    protected $encryptionsService;
    protected $orderService;
    protected $mainService;
    protected $invoiceService;
    protected $logisticService;
    protected $paymentService;
    protected $generalHelper;
    private ScopeConfigInterface $scopeConfig;
    private ParentOrderRepository $parentOrderRepository;
    private LoggerInterface $logger;

    private ParentOrderManagementInterface $parentOrderManagement;

    private $parentOrderDetailService;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlInterface
     * @param GeneralHelper $loggerInterface
     * @param RequestInterface $requestInterface
     * @param EncryptionsService $encryptionsService
     * @param OrderService $orderService
     * @param MainService $mainService
     * @param InvoiceService $invoiceService
     * @param LogisticService $logisticService
     * @param PaymentService $paymentService
     * @param GeneralHelper $generalHelper
     * @param ParentOrderRepository $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param ParentOrderDetailService $detailService
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ScopeConfigInterface           $scopeConfig,
        UrlInterface                   $urlInterface,
        GeneralHelper                  $loggerInterface,
        RequestInterface               $requestInterface,
        EncryptionsService             $encryptionsService,
        OrderService                   $orderService,
        MainService                    $mainService,
        InvoiceService                 $invoiceService,
        LogisticService                $logisticService,
        PaymentService                 $paymentService,
        GeneralHelper                  $generalHelper,
        ParentOrderRepository          $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement,
        ParentOrderDetailService       $detailService,
        ?LoggerInterface $logger = null,
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->loggerInterface = $loggerInterface;
        $this->urlInterface = $urlInterface;
        $this->requestInterface = $requestInterface;
        $this->encryptionsService = $encryptionsService;
        $this->orderService = $orderService;
        $this->mainService = $mainService;
        $this->invoiceService = $invoiceService;
        $this->logisticService = $logisticService;
        $this->paymentService = $paymentService;
        $this->generalHelper = $generalHelper;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->logger = $logger?:ObjectManager::getInstance()->get('ecPayPaymentWithParentRequestOrderDebugLogger');
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderDetailService = $detailService;
    }

    /**
     * @param $parentOderId
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|\Branch8\MarketPlaceParentOrder\Model\ParentOrder|mixed|void
     */
    private function getParentOrder($parentOderId)
    {
        return $this->parentOrderRepository->get((int)$parentOderId);
    }

    /**
     * @param $subject
     * @param callable $process
     * @param ...$args
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Log_Exception
     */
    public function aroundExecute($subject, callable $process, ...$args)
    {
        $isEnable = (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE_SPLIT_ORDER);
        if (!$isEnable) {
            return $process($args);
        }
        $paymentStage = $this->mainService->getPaymentConfig('enabled_payment_stage');
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
            $this->logger->info('PaymentToEcpay paymentStage:' . print_r($paymentStage, true));
        }
        // 取出 URL
        $apiUrl = $this->paymentService->getApiUrl('check_out', (int)$paymentStage);
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
            $this->logger->info('PaymentToEcpay apiUrl:' . print_r($apiUrl, true));
        }
        // 判斷測試模式
        if ($paymentStage == 1) {
            // 取出 KEY IV MID (測試模式)
            $accountInfo = $this->paymentService->getStageAccount();
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
                $this->logger->info('PaymentToEcpay accountInfo:' . print_r($accountInfo, true));
            }
        } else {
            // 取出 KEY IV MID (正式模式)
            $paymentMerchantId = $this->mainService->getPaymentConfig('payment_mid');
            $paymentHashKey = $this->mainService->getPaymentConfig('payment_hashkey');
            $paymentHashIv = $this->mainService->getPaymentConfig('payment_hashiv');
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
                $this->logger->info('PaymentToEcpay paymentMerchantId:' . print_r($paymentMerchantId, true));
                $this->logger->info('PaymentToEcpay paymentHashKey:' . print_r($paymentHashKey, true));
                $this->logger->info('PaymentToEcpay paymentHashIv:' . print_r($paymentHashIv, true));
            }
            $accountInfo = [
                'MerchantId' => $paymentMerchantId,
                'HashKey' => $paymentHashKey,
                'HashIv' => $paymentHashIv,
            ];
        }
        // 解密訂單編號
        $enctyOrderId = $this->requestInterface->getParam('id');
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId);
        $orderId = (int)$this->encryptionsService->decrypt($enctyOrderId);
        $orderId=27;
        $parentOrder = $this->getParentOrder($orderId);
        $detail = $parentOrder->getDetail();
        $suborders = $parentOrder->getSubOrders();
        $paymentMethod = $detail->getPaymentMethod();
        $paymentOrderPreFix = $this->mainService->getPaymentConfig('payment_order_prefix');
        $merchantTradeNo = $this->orderService->getMerchantTradeNo($orderId, $paymentOrderPreFix);
        $total = $this->parentOrderManagement->getTotalByKey($parentOrder, 'grand_total')->getValue();
        $totalAmount = (int)ceil($total);
        $paymentDisplayItemName = $this->mainService->getPaymentConfig('enabled_payment_disp_item_name');
        $paymentDisplayItemName=1;
        $items = $parentOrder->getAllItems();
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
            $this->logger->info('PaymentToEcpay enctyOrderId:' . print_r($enctyOrderId, true));
            $this->logger->info('PaymentToEcpay orderId:' . print_r($orderId, true));
            $this->logger->info('PaymentToEcpay paymentMethod:' . print_r($paymentMethod, true));
            $this->logger->info('PaymentToEcpay paymentOrderPreFix:' . print_r($paymentOrderPreFix, true));
            $this->logger->info('PaymentToEcpay merchantTradeNo:' . print_r($merchantTradeNo, true));
            $this->logger->info('PaymentToEcpay $totalAmount:' . print_r($totalAmount, true));
            $this->logger->info('PaymentToEcpay paymentDispItemName:' . print_r($paymentDisplayItemName, true));
        }
        $itemNameDefault = __('A Package Of Online Goods');
        if ($paymentDisplayItemName == 1) {
            $salesOrderItems = [];
            foreach ($items as $item) {
                $salesOrderItems[] = ['name' => $item->getName()];
            }
            $this->logger->info('PaymentToEcpay salesOrderItem:' . print_r($salesOrderItems, true));
            $itemName = $this->paymentService->convertToPaymentItemName($salesOrderItems);
            $this->logger->info('PaymentToEcpay itemName:' . print_r($itemName, true));
            // 判斷是否超過長度，如果超過長度改為預設文字
            if (strlen($itemName) > 400) {
                $itemName = $itemNameDefault;
                $comment = '商品名稱超過綠界金流可允許長度強制改為:' . $itemName;
                $parentOrder->addComment($comment);
            }
        } else {
            $itemName = $itemNameDefault;
        }
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
            $this->logger->info('PaymentToEcpay itemName:' . print_r($itemName, true));
        }
        $paymentInfoURL = $this->urlInterface->getUrl('ecpaygeneral/Process/PaymentInfoResponse');
        $paymentInfoURL = $paymentInfoURL . '?id=' . $enctyOrderId;
        $this->logger->info('PaymentToEcpay $paymentInfoURL:' . print_r($paymentInfoURL, true));
        $returnURL = $this->urlInterface->getUrl('ecpaygeneral/Process/PaymentResponse');
        $returnURL = $returnURL . '?id=' . $enctyOrderId;
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
            $this->logger->info('PaymentToEcpay $returnURL:' . print_r($returnURL, true));
        }
        $clientBackURL = $this->urlInterface->getUrl('ecpaygeneral/ParentPage/ThankYou');
        $clientBackURL = $clientBackURL . '?id=' . $enctyOrderId;
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentrequestlog')) {
            $this->logger->info('PaymentToEcpay $clientBackURL:' . print_r($clientBackURL, true));
        }
        /// set status sub order into Pending
        foreach ($suborders as $subOrder) {
            $this->orderService->setOrderState($subOrder->getEntityId(), Order::STATE_PENDING_PAYMENT);
            $this->orderService->setOrderStatus($subOrder->getEntityId(), Order::STATE_PENDING_PAYMENT);
        }
        /*    $additionalInformation = $this->orderService->getAdditionalInformation($orderId);
            $this->logger->info('PaymentToEcpay $additionalInformation:' . print_r($additionalInformation, true));*/
        $comment = sprintf(__('ECPay Payment, MerchantTradeNo :%s')->render(), $merchantTradeNo);
        $parentOrder->addComment($comment);
        $this->parentOrderDetailService->setDetaiLDataByResource($orderId,
            ['ecpay_payment_merchant_trade_no' => $merchantTradeNo]
        );
        $input = [
            'enctyOrderId' => $enctyOrderId,
            'paymentMethod' => $paymentMethod,
            'merchantId' => $accountInfo['MerchantId'],
            'merchantTradeNo' => $merchantTradeNo,
            'totalAmount' => $totalAmount,
            'itemName' => $itemName,
            'returnUrl' => $returnURL,
            'clientBackUrl' => $clientBackURL,
            'orderResultUrl' => $returnURL,
            'paymentInfoUrl' => $paymentInfoURL,
            'additionalInformation' => []
        ];
        echo $this->paymentService->checkout($accountInfo, $input, $apiUrl);
        exit();
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $requestInterface
    ): ?InvalidRequestException
    {

        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $requestInterface): ?bool
    {
        return true;
    }
}

<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Ecpay\General\Controller\Process;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Psr\Log\LoggerInterface;

class LogisticMapToEcPayPlugin
{
    const XML_PATH_ENABLE_SPLIT_ORDER = 'marketplace/mpsplitorder/mpsplitorder_enable';

    protected $_loggerInterface;
    protected $urlBuilder;
    protected $_requestInterface;

    protected $_encryptionsService;
    protected $_orderService;
    protected $_mainService;
    protected $_invoiceService;
    protected $_logisticService;
    protected $_paymentService;
    protected $_generalHelper;
    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;
    private RequestInterface $request;

    private ParentOrderRepository $parentOrderRepository;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlInterface
     * @param RequestInterface $requestInterface
     * @param EncryptionsService $encryptionsService
     * @param OrderService $orderService
     * @param MainService $mainService
     * @param InvoiceService $invoiceService
     * @param LogisticService $logisticService
     * @param PaymentService $paymentService
     * @param GeneralHelper $generalHelper
     * @param ParentOrderRepository $parentOrderRepository
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfig,
        UrlInterface          $urlInterface,
        RequestInterface      $requestInterface,
        EncryptionsService    $encryptionsService,
        OrderService          $orderService,
        MainService           $mainService,
        InvoiceService        $invoiceService,
        LogisticService       $logisticService,
        PaymentService        $paymentService,
        GeneralHelper         $generalHelper,
        ParentOrderRepository $parentOrderRepository,
        RequestInterface      $request,
        ?LoggerInterface $logger = null
    )
    {
        $this->urlBuilder = $urlInterface;
        $this->_requestInterface = $requestInterface;
        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_mainService = $mainService;
        $this->_invoiceService = $invoiceService;
        $this->_logisticService = $logisticService;
        $this->_paymentService = $paymentService;
        $this->_generalHelper = $generalHelper;
        $this->logger = $logger? :ObjectManager::getInstance()->get('ecPayLogisticWithParentOrderDebugRequestLogger');
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->parentOrderRepository = $parentOrderRepository;
    }

    /**
     * @param $parentOderId
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|\Branch8\MarketPlaceParentOrder\Model\ParentOrder|mixed|void
     */
    private function geParentOrder($parentOderId)
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
        try {
            // 取出是否為測試模式
            $logisticStage = $this->_mainService->getLogisticConfig('enabled_logistic_stage');
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay logisticStage:' . print_r($logisticStage, true));
            }
            // 取出CvsType
            $logisticCvsType = $this->_mainService->getLogisticConfig('logistic_cvs_type');
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay logisticCvsType:' . print_r($logisticCvsType, true));
            }
            // 取出 URL
            $apiUrl = $this->_logisticService->getApiUrl('map', $logisticStage, $logisticCvsType);
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay apiUrl:' . print_r($apiUrl, true));
            }
            // 判斷測試模式
            if ($logisticStage == 1) {
                // 取出 KEY IV MID (測試模式)
                $accountInfo = $this->_logisticService->getStageAccount($logisticCvsType);
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                    $this->logger->info('LogisticMapToEcpay accountInfo:' . print_r($accountInfo, true));
                }
            } else {
                // 取出 KEY IV MID (正式模式)
                $logisticMerchantId = $this->_mainService->getLogisticConfig('logistic_mid');
                $logisticHashKey = $this->_mainService->getLogisticConfig('logistic_hashkey');
                $logisticHashIv = $this->_mainService->getLogisticConfig('logistic_hashiv');
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                    $this->logger->info('LogisticMapToEcpay logisticMerchantId:' . print_r($logisticMerchantId, true));
                    $this->logger->info('LogisticMapToEcpay logisticHashKey:' . print_r($logisticHashKey, true));
                    $this->logger->info('LogisticMapToEcpay logisticHashIv:' . print_r($logisticHashIv, true));
                }
                $accountInfo = [
                    'MerchantId' => $logisticMerchantId,
                    'HashKey' => $logisticHashKey,
                    'HashIv' => $logisticHashIv,
                ];
            }
            // 解密訂單編號
            $enctyOrderId = $this->request->getParam('id');
            $enctyOrderId = str_replace(' ', '+', $enctyOrderId);
            $orderId = intval($this->_encryptionsService->decrypt($enctyOrderId));
            $parentOrder = $this->geParentOrder($orderId);
            $detail = $parentOrder->getDetail();
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay enctyOrderId:' . print_r($enctyOrderId, true));
                $this->logger->info('LogisticMapToEcpay parentOrderId:' . print_r($orderId, true));
            }

            // 取出訂單物流方式
            $shippingMethod = $detail->getShippingMethod();
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay shippingMethod:' . print_r($shippingMethod, true));
            }
            // 取出訂單前綴
            $logisticOrderPreFix = $this->_mainService->getLogisticConfig('logistic_order_prefix');
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay logisticOrderPreFix:' . print_r($logisticOrderPreFix, true));
            }
            // 組合廠商訂單編號
            $merchantTradeNo = $this->_orderService->getMerchantTradeNo($orderId, $logisticOrderPreFix);
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay merchantTradeNo:' . print_r($merchantTradeNo, true));
            }
            // 取出物流子類型
            $logisticsSubType = $this->_logisticService->getCvsLogisticsSubType($logisticCvsType, $shippingMethod);
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay logisticsSubType:' . print_r($logisticsSubType, true));
            }
            // 取出訂單金流方式
            $paymentMethod = $detail->getPaymentMethod();
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('LogisticMapToEcpay paymentMethod:' . print_r($paymentMethod, true));
            }
            // 判斷是否為貨到付款
            $isCollection = ($paymentMethod == 'cashondelivery') ? 'Y' : 'N';

            // 回傳門市資訊網址
            $serverReplyURL = $this->urlBuilder->getUrl('ecpaygeneral/Process/LogisticMapResponse');
            $serverReplyURL = $serverReplyURL . '?id=' . $enctyOrderId;
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->info('MapToEcpay $serverReplyURL:' . print_r($serverReplyURL, true));
            }
            // 組合門市選擇API
            $input = [
                'merchantId' => $accountInfo['MerchantId'],
                'merchantTradeNo' => $merchantTradeNo,
                'logisticsSubType' => $logisticsSubType,
                'isCollection' => $isCollection,
                'serverReplyURL' => $serverReplyURL,
            ];
            echo $this->_logisticService->mapToEcpay($accountInfo, $input, $apiUrl);
        } catch (NoSuchEntityException $exception) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->critical($exception->getMessage());
            }
        } catch (\Exception $exception) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticrequestlog')) {
                $this->logger->critical($exception->getMessage());
            }
        }
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

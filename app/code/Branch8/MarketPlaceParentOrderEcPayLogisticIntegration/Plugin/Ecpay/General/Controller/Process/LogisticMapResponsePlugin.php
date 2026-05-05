<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Ecpay\General\Controller\Process;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Model\ParentOrderDetailService;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\UrlInterface;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class LogisticMapResponsePlugin
{
    const XML_PATH_ENABLE_SPLIT_ORDER = 'marketplace/mpsplitorder/mpsplitorder_enable';

    protected $requestInterface;
    protected $loggerInterface;
    protected $urlInterface;
    protected $responseFactory;
    protected $encryptionsService;
    protected $orderService;
    protected $mainService;
    protected $invoiceService;
    protected $logisticService;
    protected $paymentService;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;
    private ParentOrderRepository $parentOrderRepository;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private ParentOrderDetailService $detailService;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlInterface
     * @param RequestInterface $requestInterface
     * @param GeneralHelper $loggerInterface
     * @param ResponseFactory $responseFactory
     * @param EncryptionsService $encryptionsService
     * @param OrderService $orderService
     * @param MainService $mainService
     * @param InvoiceService $invoiceService
     * @param LogisticService $logisticService
     * @param PaymentService $paymentService
     * @param ParentOrderRepository $parentOrderRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param ParentOrderDetailService $detailService
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ScopeConfigInterface                      $scopeConfig,
        UrlInterface                              $urlInterface,
        RequestInterface                          $requestInterface,
        GeneralHelper                             $loggerInterface,
        ResponseFactory                           $responseFactory,
        EncryptionsService                        $encryptionsService,
        OrderService                              $orderService,
        MainService                               $mainService,
        InvoiceService                            $invoiceService,
        LogisticService                           $logisticService,
        PaymentService                            $paymentService,
        ParentOrderRepository                     $parentOrderRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ParentOrderDetailService                  $detailService,
        ?LoggerInterface $logger = null,
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->requestInterface = $requestInterface;
        $this->loggerInterface = $loggerInterface;
        $this->logger = $logger?:ObjectManager::getInstance()->get('ecPayLogisticWithParentOrderDebugResponseLogger');
        $this->urlInterface = $urlInterface;
        $this->responseFactory = $responseFactory;
        $this->encryptionsService = $encryptionsService;
        $this->orderService = $orderService;
        $this->mainService = $mainService;
        $this->invoiceService = $invoiceService;
        $this->logisticService = $logisticService;
        $this->paymentService = $paymentService;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->detailService = $detailService;
    }

    /**
     * @param $parentOderId
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|\Branch8\MarketPlaceParentOrder\Model\ParentOrder|mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
        // 接收門市資訊
        $storeInfo = $this->requestInterface->getPostValue();
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticresponse')){
            $this->logger->info('MapResponse storeInfo:' . print_r($storeInfo, true));
        }
        // 解密訂單編號
        $enctyOrderId = $this->requestInterface->getParam('id');
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId);
        $parentOrderId = intval($this->encryptionsService->decrypt($enctyOrderId));
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticresponse')) {
            $this->logger->info('MapResponse enctyOrderId:' . print_r($enctyOrderId, true));
        }
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticresponse')) {
            $this->logger->info('MapResponse orderId:' . print_r($parentOrderId, true));
        }
        $parentOrder = $this->getParentOrder($parentOrderId);
        $detail = $parentOrder->getDetail();
        // 驗證訂單資訊 (驗證物流方式)
        $shippingMethod = $detail->getShippingMethod($parentOrderId);
        $paymentMethod = $detail->getPaymentMethod();
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticresponse')) {
            $this->logger->info('MapResponse shippingMethod:' . print_r($shippingMethod, true));
        }
        if ($this->logisticService->isEcpayCvsLogistics($shippingMethod)) {
            // 門市資訊寫入資料庫
            $CVSStoreID = isset($storeInfo['CVSStoreID']) ? $storeInfo['CVSStoreID'] : '';
            $CVSStoreName = isset($storeInfo['CVSStoreName']) ? $storeInfo['CVSStoreName'] : '';
            $CVSAddress = isset($storeInfo['CVSAddress']) ? $storeInfo['CVSAddress'] : '';
            $CVSTelephone = isset($storeInfo['CVSTelephone']) ? $storeInfo['CVSTelephone'] : '';
            if (!empty($CVSStoreID)) {
                // update these information for parent order detail
                $updateData = [
                    'ecpay_logistic_cvs_store_id' => $CVSStoreID,
                    'ecpay_logistic_cvs_store_name' => $CVSStoreName,
                    'ecpay_logistic_cvs_store_address' => $CVSAddress,
                    'ecpay_logistic_cvs_store_telephone' => $CVSTelephone,
                ];
                $this->detailService->setDetaiLDataByResource($parentOrder->getId(), $updateData);
                $dbWrite = $this->resourceConnection->getConnection();
                $addressTable = $this->resourceConnection->getTableName('sales_order_address');
                $suborders = $parentOrder->getSubOrders();
                // update address information for sub order
                /**
                 * @var $suborder Order
                 */
                foreach ($suborders as $suborder) {
                    if ($suborder->getShippingAddress()) {
                        $this->orderService->setOrderData($suborder->getId(),
                            'ecpay_logistic_cvs_store_id', $CVSStoreID
                        );
                        $this->orderService->setOrderData($suborder->getId(),
                            'ecpay_logistic_cvs_store_name', $CVSStoreName
                        );
                        $this->orderService->setOrderData($suborder->getId(),
                            'ecpay_logistic_cvs_store_address', $CVSAddress
                        );
                        $this->orderService->setOrderData($suborder->getId(),
                            'ecpay_logistic_cvs_store_telephone', $CVSTelephone
                        );
                        $dbWrite->update(
                            $addressTable,
                            [
                                'region' => NULL,
                                'postcode' => $CVSStoreID,
                                'street' => $CVSAddress . '(門市地址)',
                                'city' => $CVSStoreName,
                                'company' => NULL,
                            ],
                            [
                                'parent_id = ?' => $suborder->getId(),
                                'address_type = ?' => 'shipping',
                            ]
                        );
                    }
                }
                $dbWrite->closeConnection();
            }
        }
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticresponse')) {
            $this->logger->info('MapResponse paymentMethod:' . print_r($paymentMethod, true));
        }
        if ($this->paymentService->isEcpayPayment($paymentMethod)) {
            $redirectUrl = $this->urlInterface->getUrl('ecpaygeneral/Process/PaymentToEcpay');
            $redirectUrl = $redirectUrl . '?id=' . $enctyOrderId;
        } else {
            // NO 轉到感謝頁面 帶ORDER_ID走
            $redirectUrl = $this->urlInterface->getUrl('ecpaygeneral/ParentPage/ThankYou');
            $redirectUrl = $redirectUrl . '?id=' . $enctyOrderId;
        }
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'logisticresponse')) {
            $this->logger->info('MapResponse $redirectUrl:' . print_r($redirectUrl, true));
        }
        $this->responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
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

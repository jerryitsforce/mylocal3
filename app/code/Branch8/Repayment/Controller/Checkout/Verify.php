<?php

namespace Branch8\Repayment\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Branch8\HotaiPay\Service\HotaiPay;
use Branch8\HotaiPay\Model\Ui\ConfigProvider as HotaiPayConfigProvider;
use \Magento\Checkout\Model\Session;
use  \Magento\Framework\Registry;
use Magento\Framework\Session\SessionManager;
use Branch8\Repayment\Model\OrderManagement;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\Repayment\Helper\Log as RepaymentLog;
use Magento\Sales\Api\OrderRepositoryInterface;
use Branch8\HotaiPoint\Helper\CacheLock as PointCacheLock;

class Verify extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public $hotaiPayService;
    protected $pageFactory;
    public $hotaiPayConfigProvider;
    private $checkoutSession;
    protected $coreSession;
    protected $orderManagement;
    private $redirectPath;
    private $paymentMethod;
    private $creditCardId;
    private $parentOrderId;
    private $ccData;
    protected $registry;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var PointCacheLock */
    protected $pointCacheLock;

    /** @var RepaymentLog */
    private RepaymentLog $repaymentLog;

    protected $processId = "";

    /**
     * __construct
     *
     * @param SessionManager $coreSession Core session manager.
     * @param Context $context Action context.
     * @param HotaiPay $hotaiPayService HotaiPay service.
     * @param HotaiPayConfigProvider $hotaiPayConfigProvider Payment config provider.
     * @param Session $checkoutSession Checkout session.
     * @param Registry $registry Magento registry.
     * @param OrderManagement $orderManagement Repayment order management service.
     * @param HotaiCoreCommon $hotaiCoreCommon Shared helper.
     * @param OrderRepositoryInterface $orderRepository Order repository.
     * @param PointCacheLock $pointCacheLock Point cache lock helper.
     * @param RepaymentLog $repaymentLog Repayment log helper.
     */
    public function __construct(
        SessionManager $coreSession,
        Context $context,
        HotaiPay $hotaiPayService,
        HotaiPayConfigProvider $hotaiPayConfigProvider,
        Session $checkoutSession,
        Registry $registry,
        OrderManagement $orderManagement,
        HotaiCoreCommon $hotaiCoreCommon,
        OrderRepositoryInterface $orderRepository,
        PointCacheLock $pointCacheLock,
        RepaymentLog $repaymentLog
    ) {
        $this->hotaiPayService = $hotaiPayService;
        $this->hotaiPayConfigProvider = $hotaiPayConfigProvider;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        $this->orderManagement = $orderManagement;
        $this->coreSession = $coreSession;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->orderRepository = $orderRepository;
        $this->pointCacheLock = $pointCacheLock;
        $this->repaymentLog = $repaymentLog;
        $this->processId = getmypid();
        parent::__construct($context);
    }
    /**
     * Process repayment verification and redirect flow.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $this->prepareData(
                $this->getDataFromRequest($this->getRequest())
            );

            //更新圈存狀態
            foreach($this->checkoutSession->getSubOrderIds() as $orderId) {

                $this->repaymentLog->info(
                    '[Repayment Order Id] ' . $orderId,
                    $this->repaymentLog->verifyOption()
                );

                // Point re-deduction procedure
                $order = $this->orderRepository->get($orderId);
                if (!$this->pointCacheLock->checkIsPointReDeductionProcedureLockNow($order->getId())) {
                    $pointCacheLockMessage = "Point re-deduction cache lock not found at Verify, add cache lock by current process, self process ID: {$this->processId}.";
                    $this->hotaiCoreCommon->addSalesOrderHistoryComment($order, $pointCacheLockMessage, self::class);

                    $this->pointCacheLock->pointReDeductionProcedureLock($order->getId(), $this->processId);
                } else {
                    $lockId = $this->pointCacheLock->getPointReDeductionProcedureLockValue($order->getId());
                    $pointCacheLockMessage = "Found point re-deduction cache lock at Verify, self process ID: {$this->processId}, locked process ID: {$lockId}.";
                    $this->hotaiCoreCommon->addSalesOrderHistoryComment($order, $pointCacheLockMessage, self::class);
                }

                if ($this->checkIfNeedPointReDeductionProcedure($order)) {
                    $this->_eventManager->dispatch('hotaiPay_payment_retry', [
                        "orderId" => $orderId,
                    ]);
                }
                // -----------------------------------------------------------

                $this->repaymentLog->info(
                    '[event - hotaiPay_payment_retry] ended.',
                    $this->repaymentLog->verifyOption()
                );
            }

            return $this->resultRedirectFactory->create()->setPath($this->redirectPath, [
                '_secure' => $this->getRequest()->isSecure(),
            ]);
        } catch (\Exception $e) {
            $this->checkoutSession->setIsRepayment(false);
            $this->checkoutSession->setErrorMsg(['error' => $e->getMessage()]);

            $this->repaymentLog->exception(
                $e,
                $this->repaymentLog->verifyOption(),
                __METHOD__,
                [
                    'redirect_path' => $this->redirectPath,
                    'parent_order_id' => $this->parentOrderId,
                ]
            );

            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure', [
                '_secure' => $this->getRequest()->isSecure(),
            ]);
        }
    }

    /**
     * Prepare repayment request data and update checkout session.
     *
     * @param array<string, mixed> $postData Posted repayment data.
     */
    private function prepareData($postData)
    {
        $this->paymentMethod = $postData['paymentMethod'];
        $this->creditCardId =  $postData['creditCardId'];
        $this->parentOrderId = $postData['parentOrderId'];

        $this->paymentSwitcher();

        $orderCheckoutData = $this->orderManagement->updateOrderPaymentInfo(
            $this->parentOrderId,
            $this->paymentMethod,
            $this->ccData
        );

        $this->setOrderCheckoutSessionData($orderCheckoutData);
    }

    /**
     * Save order checkout data into checkout session.
     *
     * @param array<string, mixed> $orderCheckoutData Checkout data for redirect flow.
     */
    private function setOrderCheckoutSessionData($orderCheckoutData)
    {
        $this->checkoutSession->setData('parentOrderId', $this->parentOrderId);
        $this->checkoutSession->setLastSuccessQuoteId($orderCheckoutData[$this->orderManagement::LAST_SUCCESS_QUOTE_ID]);
        $this->checkoutSession->setLastQuoteId($orderCheckoutData[$this->orderManagement::LAST_QUOTE_ID]);
        $this->checkoutSession->setLastOrderId($orderCheckoutData[$this->orderManagement::LAST_ORDER_ID]);
        $this->checkoutSession->setLastRealOrderId($orderCheckoutData[$this->orderManagement::LAST_REAL_ORDER_ID]);
        $this->checkoutSession->setData('isRepayment', true);
        $this->coreSession->setData('grand_total', $orderCheckoutData[$this->orderManagement::GRAND_TOTAL]);
        $this->checkoutSession->setSubOrderIds($orderCheckoutData[$this->orderManagement::SUB_ORDERS]);
    }

    /**
     * Resolve payment method specific handling logic.
     */
    private function paymentSwitcher()
    {

        switch ($this->paymentMethod) {
            case $this->hotaiPayConfigProvider::CODE:
                $this->ccData = (object) $this->getHotaiPayCcData($this->creditCardId);
                $this->checkoutSession->setData('ccData', $this->ccData);
                $this->redirectPath = 'hotaipay/payment/checkout';
                return;
            default:
                return;
        }
    }

    /**
     * Get HotaiPay credit card data from config payload.
     *
     * @param string|int $creditCardId Credit card data index.
     * @return array<string, mixed>
     */
    private function getHotaiPayCcData($creditCardId)
    {
        $configData = $this->hotaiPayConfigProvider->getConfig();
        return $configData['payment'][$this->hotaiPayConfigProvider::CODE]['creditCardDetailData'][$creditCardId];
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Extract post data from request object.
     *
     * @param RequestInterface $request Incoming request object.
     * @return array
     */
    private function getDataFromRequest(RequestInterface $request): array
    {
        /** @var Http $request */
        if ($request->isPost()) {
            $data = $request->getPostValue();
        } else {
            return [];
        }
        return $data;
    }

    /**
     * Check whether point re-deduction procedure should continue.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order Order entity.
     * @return bool
     */
    protected function checkIfNeedPointReDeductionProcedure($order): bool
    {
        $cond1 = $this->pointCacheLock->checkIsPointReDeductionProcedureLockNow($order->getId());

        $lockId = $this->pointCacheLock->getPointReDeductionProcedureLockValue($order->getId());
        $cond2 = $lockId == $this->processId;

        $pointCacheLockMessage = "";

        if ($cond1 && $cond2) {
            $pointCacheLockMessage = "Pass checkIfNeedPointReDeductionProcedure check at Verify, self process ID: {$this->processId}, locked process ID: {$lockId}.";
        } else {
            $pointCacheLockMessage = "Failed checkIfNeedPointReDeductionProcedure check at Verify, self process ID: {$this->processId}, locked process ID: {$lockId}.";
        }

        $this->hotaiCoreCommon->addSalesOrderHistoryComment($order, $pointCacheLockMessage, self::class);

        return $cond1 && $cond2;
    }
}

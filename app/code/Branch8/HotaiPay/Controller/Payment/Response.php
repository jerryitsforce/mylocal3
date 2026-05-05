<?php

namespace Branch8\HotaiPay\Controller\Payment;

use Branch8\HotaiPay\Helper\Crypt;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\HotaiPay\Helper\Order\OrderManagement;
use Branch8\HotaiPay\Logger\Payment\Logger;
use Branch8\HotaiPay\Model\Payment\Update;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use \Magento\Checkout\Model\Session;
use Branch8\HotaiPay\Helper\CacheLock as HotaiPayCacheLock;

class Response extends Action
{

    /** @var mixed $orderModel */
    protected $orderModel;

    /** @var \Magento\Framework\View\Result\PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /** @var MessageManagerInterface */
    protected $messageManager;

    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    private $checkoutSession;

    /** @var \Branch8\HotaiPay\Helper\Crypt $crypt */
    protected $crypt;

    /** @var \Magento\Sales\Model\Order\Payment\Transaction $transaction */
    protected $transaction;

    /** @var \Branch8\HotaiPay\Model\Payment\Update $update */
    private $update;

    /** @var \Branch8\HotaiPay\Logger\Payment\Logger $logger */
    protected $logger;

    /** @var \Branch8\HotaiPay\Helper\Order\OrderManagement $orderManagement */
    private $orderManagement;

    private HotaiPayLogHelper $hotaiPayLogHelper;

    protected ParentOrderManagementInterface $parentOrderManagement;

    protected ParentOrderFactory $parentOrderFactory;

    protected HotaiPayCacheLock $hotaiPayCacheLock;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Registry $coreRegistry,
        Context $context,
        PageFactory $resultPageFactory,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        crypt $crypt,
        Transaction $transaction,
        Update $update,
        Logger $logger,
        OrderManagement $orderManagement,
        ParentOrderManagementInterface               $parentOrderManagement,
        ParentOrderFactory                           $parentOrderFactory,
        HotaiPayCacheLock $hotaiPayCacheLock,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->crypt = $crypt;
        $this->transaction = $transaction;
        $this->update = $update;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->hotaiPayCacheLock = $hotaiPayCacheLock;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
        return parent::__construct($context);
    }

    /**
     * execute
     *
     * @return void
     */
    public function execute()
    {
        try {
            $resultMap = $this->checkResponseParams();
            $this->checkoutSession->setData('successPayment', $resultMap);
            $orderIsPaid = $this->orderIsPaid();

            //success payment
            if ($resultMap) {
                $parentOrderId = $this->checkoutSession->getData('parentOrderId');

                if(!$orderIsPaid) {
                    if ($this->hotaiPayCacheLock->checkIsUpdateStatusAfterCheckoutSuccessLockNow($parentOrderId)) {
                        $lockValue = $this->hotaiPayCacheLock->getUpdateStatusAfterCheckoutSuccessLockValue($parentOrderId);
                        $this->hotaiPayCacheLock->writeLog("Found cache lock for update status after checkout success, current class: ".__CLASS__.", found lock value: ".$lockValue);
                    } else {
                        $this->hotaiPayCacheLock->writeLog("No cache lock for update status after checkout success, add cache lock by current process, current class: ".__CLASS__.", current process ID: ".getmypid());
                        $this->hotaiPayCacheLock->updateStatusAfterCheckoutSuccessLock($parentOrderId, __CLASS__."|".getmypid());
                        try {
                            $this->update->setStatusAfterCheckoutSuccess();
                        } finally {
                            $this->hotaiPayCacheLock->updateStatusAfterCheckoutSuccessUnlock($parentOrderId);
                            $this->hotaiPayCacheLock->writeLog("Unlock cache lock for update status after checkout success, current class: ".__CLASS__.", current process ID: ".getmypid());
                        }
                    }
                }

                if($parentOrderId){
                    $parentOrder = $this->parentOrderFactory->create()->load($parentOrderId);
                    $this->parentOrderManagement->sendConfirmationEmail($parentOrder);
                }
                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', [
                    '_secure' => $this->getRequest()->isSecure(),
                ]);
            } else {
                //set Fraud order
                if ($this->isFraud()) {
                    $this->update->setFraud();
                }

                //fail payment
                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure', [
                    '_secure' => $this->getRequest()->isSecure(),
                ]);
            };
        } catch (\Exception $exception) {
            $this->messageManager->addError($exception->getMessage());

            $this->checkoutSession->setData(
                'errorMsg',
                [
                    "success" => false,
                    "error" => $exception->getMessage(),
                    "errorMsg" => $exception->getMessage(),
                ]
            );

            $this->hotaiPayLogHelper->writeLog($exception->getMessage(), __CLASS__);

            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure', [
                '_secure' => $this->getRequest()->isSecure(),
            ]);
        }
    }

    /**
     * checkResponseParams
     *
     * @return void | bool
     */
    private function checkResponseParams()
    {
        $errorCode = '';
        //get params
        $params = $this->getRequest()->getParams();

        if (!empty($params['po'])) {
            $parentOrderId = base64_decode(urldecode($params['po']));
            $params['parentOrderId'] = $parentOrderId;
            $this->checkoutSession->setData('parentOrderId', $parentOrderId);
        }

        $this->hotaiPayLogHelper->writeLog('[Response Params Result] ' . json_encode($params, JSON_UNESCAPED_UNICODE), __CLASS__);

        //save params to db
        $this->update->setPaymentResult($params);

        $statusDesc = $params['StatusDesc'];
        $statusCode = $params['StatusCode'];
        $cryptInput = $params['q'];
        $decryptResponse = $this->crypt->decrypt($cryptInput, $this->crypt::CHAR_CODE_BASE64);

        $decryptArray = explode("_", $decryptResponse);

        $mac = $this->update->getPaymentMac($parentOrderId);

        //payment success
        if (strtoupper($statusDesc) == 'SUCCESS') {
            /** 配對成功且未付款 */
            if ($decryptArray[0] == $statusCode && $decryptArray[1] == $mac) {
                $this->hotaiPayLogHelper->writeLog('[Response Params] Successful Mapped.', __CLASS__);
                return true;
            }

            $this->hotaiPayLogHelper->writeLog('[Response Params] Failed Mapped.', __CLASS__);

            $statusDesc = "Payment Info From CTBC does not Match.";
            $this->update->setPaymentFailedReason('Mac or Status Code does not match.');

        }

        if (isset($params['Errcode'])) {
            $errorCode = $params['Errcode'];
        }

        //payment fail
        $this->checkoutSession->setData(
            'errorMsg',
            [
                "success" => false,
                "error" => $statusDesc,
                "errorMsg" => $statusCode,
                "statusCode" => $statusCode,
                "errorCode" => $errorCode,
            ]
        );

        return false;
    }

    /**
     * isFraud
     *
     * @return bool
     */
    private function isFraud()
    {
        $errorMsg = $this->checkoutSession->getData('errorMsg');
        if (isset($errorMsg['statusCode']) && isset($errorMsg['errorCode'])) {
            return $this->orderManagement->isFraudOrder(
                $errorMsg['statusCode'],
                $errorMsg['errorCode']
            );
        }

        return false;
    }

    protected function orderIsPaid() {
        $params = $this->getRequest()->getParams();

        /** 是否已經付款 */
        $orderIsPaid = $this->update->checkSubOrderIsPaid($params);

        return $orderIsPaid;
    }
}

<?php

namespace Branch8\HotaiPay\Controller\Payment;

use Branch8\HotaiPay\Helper\Order\OrderManagement;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\HotaiPay\Logger\Payment\Logger;
use Branch8\HotaiPay\Model\Payment\Update as PaymentUpdate;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Checkout\Model\Session;
use \Magento\Customer\Model\Session as CustomerSession;

class Checkout extends Action
{
    /** @var mixed $orderModel */
    protected $orderModel;

    /** @var \Magento\Framework\Session\SessionManager $_coreSession */
    protected $_coreSession;

    /** @var \Magento\Framework\View\Result\PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /**　@var \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;

    /** @var MessageManagerInterface */
    protected $messageManager;

    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    private $checkoutSession;

    /** @var \Branch8\HotaiPay\Helper\Order\OrderManagement $orderManagement */
    private $orderManagement;

    /**　@var \Branch8\HotaiPay\Model\Payment\Update $paymentUpdate */
    private $paymentUpdate;

    /** @var \Branch8\HotaiPay\Logger\Payment\Logger $logger */
    protected $logger;

    /** @var \Magento\Customer\Model\Session $customerSession */
    protected $customerSession;

    private HotaiPayLogHelper $hotaiPayLogHelper;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        SessionManager $coreSession,
        Registry $coreRegistry,
        Context $context,
        PageFactory $resultPageFactory,
        Session $checkoutSession,
        ManagerInterface $messageManager,
        OrderManagement $orderManagement,
        PaymentUpdate $paymentUpdate,
        CustomerSession $customerSession,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->_coreSession = $coreSession;
        $this->_coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->orderManagement = $orderManagement;
        $this->paymentUpdate = $paymentUpdate;
        $this->customerSession = $customerSession;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
        return parent::__construct($context);
    }

    /**
     * execute
     *
     * @return void | \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {

        //$this->paymentUpdate->setStatusBeforeCheckout();

        $parentOrderId = $this->orderManagement->getParentOrderIncrementId();
        $this->hotaiPayLogHelper->writeLog("[ParentOrder] {$parentOrderId} - Checkout Process.", __CLASS__);

        // Check customer is login or not
        if (!$this->customerSession->isLoggedIn()
            || !$this->checkoutSession->getData('ccData')) {
            
            $errorMsg = 'Please Relogin And Repay.';
            $this->hotaiPayLogHelper->writeLog($errorMsg, __CLASS__);
            $this->messageManager->addErrorMessage($errorMsg);
            $this->checkoutSession->setData('errorMsg', ['error' => $errorMsg]);

            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure', [
                '_secure' => $this->getRequest()->isSecure(),
            ]);
        }

        try {
            $this->orderManagement->getOrderTxn();

        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->checkoutSession->setData('errorMsg', ['error' => $e->getMessage()]);

            $this->hotaiPayLogHelper->writeLog("[ParentOrder] {$parentOrderId} - [Error] " . $e->getMessage(), __CLASS__);

            return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure', [
                '_secure' => $this->getRequest()->isSecure(),
            ]);
        }

        $errorMsg = $this->checkoutSession->getData('errorMsg');
        $successPayment = $this->checkoutSession->getData('successPayment');
        $reqjsonpwd = $this->checkoutSession->getData('reqjsonpwd');

        if ($errorMsg && $successPayment === false) {
            try {
                //$this->paymentUpdate->revertStatusDuringCheckout();

                $this->hotaiPayLogHelper->writeLog("[ParentOrder] {$parentOrderId} - [PaymentFailed] " . json_encode($errorMsg, JSON_UNESCAPED_UNICODE), __CLASS__);

                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure', [
                    '_secure' => $this->getRequest()->isSecure(),
                ]);

            } catch (\Exception $e) {
                $this->checkoutSession->setData('errorMsg', ['error' => $e->getMessage()]);

                $this->hotaiPayLogHelper->writeLog("[ParentOrder] {$parentOrderId} - [Error] ". $e->getMessage(), __CLASS__);

                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure', [
                    '_secure' => $this->getRequest()->isSecure(),
                ]);
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('hotaipay.payment.checkout');
        $block->setData('reqjsonpwd', $reqjsonpwd);
        return $resultPage;
    }
}

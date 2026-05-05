<?php

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Block\Onepage;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Services\Config\LogisticService;

class ThankYou extends Template
{
    protected $_checkoutSession;
    protected $_loggerInterface;
    protected $_encryptionsService;
    protected $_orderService;
    protected $_paymentService;
    protected $_logisticService;
    protected $orderId;
    protected $parentOrderRepository;

    /**
     * @param ParentOrderRepository $parentOrderRepository
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param GeneralHelper $loggerInterface
     * @param EncryptionsService $encryptionsService
     * @param OrderService $orderService
     * @param PaymentService $paymentService
     * @param LogisticService $logisticService
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param array $data
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Log_Exception
     */
    public function __construct(
        ParentOrderRepository          $parentOrderRepository,
        Context                        $context,
        CheckoutSession                $checkoutSession,
        GeneralHelper                  $loggerInterface,
        EncryptionsService             $encryptionsService,
        OrderService                   $orderService,
        PaymentService                 $paymentService,
        LogisticService                $logisticService,
        ParentOrderManagementInterface $parentOrderManagement,
        array                          $data = []
    )
    {
        $this->parentOrderRepository = $parentOrderRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->_loggerInterface = $loggerInterface;
        $this->_encryptionsService = $encryptionsService;
        $this->_orderService = $orderService;
        $this->_paymentService = $paymentService;
        $this->_logisticService = $logisticService;
        parent::__construct($context, $data);
        $this->orderId = $this->getOrderId();
        $this->parentOrderManagement = $parentOrderManagement;
        $this->_loggerInterface->writeLog('ThankYou Block orderId:' . print_r($this->orderId, true));
    }

    /**
     * 檢查 Order ID，是否顯示
     *
     * @return bool
     */
    public function isShow()
    {
        return ($this->orderId !== 0);
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|\Branch8\MarketPlaceParentOrder\Model\ParentOrder|mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrder()
    {
        return $this->parentOrderRepository->get((int)$this->orderId);
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDetail()
    {
        return $this->getOrder()->getDetail();
    }

    /**\
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGrandTotal()
    {
        return $this->parentOrderManagement->getTotalByKey($this->getOrder(), 'grand_total');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPaymentHtml()
    {
        return $this->parentOrderManagement->getPaymentHtml($this->getOrder());
    }

    /**
     * 取得運送資訊
     *
     * @return array
     */
    public function getShippingInfo()
    {
        $shippingMethod = $this->_orderService->getShippingMethod($this->orderId);
        $shippingMethod = empty($shippingMethod) ? '' : $shippingMethod;
        $methodTitle = $this->_orderService->getShippingDescription($this->orderId);
        $this->_loggerInterface->writeLog('ThankYou Block shippingMethod:' . print_r($shippingMethod, true));
        $this->_loggerInterface->writeLog('ThankYou Block methodTitle:' . print_r($methodTitle, true));

        $isEcpayCvsLogistics = $this->_logisticService->isEcpayCvsLogistics($shippingMethod);
        $this->_loggerInterface->writeLog('ThankYou Block isEcpayCvsLogistics:' . print_r($isEcpayCvsLogistics, true));

        // 判斷是否為綠界超商物流
        $cvsInfo = [];
        if ($isEcpayCvsLogistics) {
            $cvsInfo = [
                'cvs_store_id' => $this->_orderService->getEcpayLogisticCvsStoreId($this->orderId),
                'cvs_store_name' => $this->_orderService->getEcpayLogisticCvsStoreName($this->orderId),
                'cvs_store_address' => $this->_orderService->getEcpayLogisticCvsStoreAddress($this->orderId)
            ];
        }

        return [
            'is_ecpay_cvs_logistics' => ($isEcpayCvsLogistics) ? 'Y' : 'N',
            'shipping_method' => $methodTitle,
            'cvs_info' => $cvsInfo,
        ];
    }

    /**
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * 取得訂單編號
     *
     * @return int
     */
    private function getOrderId()
    {
        // 解密訂單編號
        $enctyOrderId = $this->getRequest()->getParam('id');
        $enctyOrderId = str_replace(' ', '+', $enctyOrderId);
        $this->_loggerInterface->writeLog('ThankYou Block enctyOrderId:' . print_r($enctyOrderId, true));
        $orderId = $this->_encryptionsService->decrypt($enctyOrderId);
        $this->_loggerInterface->writeLog('ThankYou Block orderId:' . print_r(intval($orderId), true));
        return intval($orderId);
    }

    /**
     *  Payment custom error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        $error = $this->_checkoutSession->getErrorMessage();
        return $error;
    }
}

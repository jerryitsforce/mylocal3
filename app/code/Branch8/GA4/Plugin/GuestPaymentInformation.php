<?php

namespace Branch8\GA4\Plugin;

use Branch8\GA4\Model\Config;

class GuestPaymentInformation
{
    /**
     * @var \WeltPixel\GA4\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var \Branch8\GA4\Model\Datalayer
     */
    private $datalayer;

    /**
     * /**
     * @param \Branch8\GA4\Model\Datalayer $datalayer
     * @param Config $config
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Branch8\GA4\Model\Datalayer                $datalayer,
        Config                                      $config,
        \Magento\Checkout\Model\Session             $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->datalayer = $datalayer;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @return int Order ID.
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\GuestPaymentInformationManagement $subject,
                                                                  $result
    )
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $orderId = $result;

        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order->getId()) {
            try {
                $order = $this->orderRepository->get($orderId);
            } catch (\Exception $ex) {
                return $result;
            }
        }

        $additionalInformation = $order->getPayment()->getAdditionalInformation();

        if ($additionalInformation && isset($additionalInformation['method_title'])) {
            $paymentMethodTitle = $additionalInformation['method_title'];
            $this->_checkoutSession->setGA4CheckoutOptionsData(
                $this->datalayer->addCheckoutStepPushData('2', $paymentMethodTitle)
            );
        }

        return $result;
    }


}

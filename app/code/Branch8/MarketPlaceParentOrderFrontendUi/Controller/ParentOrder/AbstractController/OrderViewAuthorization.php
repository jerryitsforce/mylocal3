<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

class OrderViewAuthorization implements OrderViewAuthorizationInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     */
    public function __construct(
        \Magento\Customer\Model\Session   $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig
    )
    {
        $this->customerSession = $customerSession;
        $this->orderConfig = $orderConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(ParentOrder $order)
    {
        $customerId = $this->customerSession->getCustomerId();
        $availableStatuses = $this->orderConfig->getVisibleOnFrontStatuses();
        if ($order->getId()
            && $order->getDetail()
            && $order->getDetail()->getId()
            && $order->getDetail()->getCustomerId()
            && $order->getDetail()->getCustomerId() == $customerId
            && in_array($order->getDetail()->getStatus(), $availableStatuses, true)
        ) {
            return true;
        }
        return false;
    }
}

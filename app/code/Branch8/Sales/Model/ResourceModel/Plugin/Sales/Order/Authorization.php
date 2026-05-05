<?php

namespace Branch8\Sales\Model\ResourceModel\Plugin\Sales\Order;

use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Area;

class Authorization extends \Ecpay\General\Model\ResourceModel\Plugin\Sales\Order\Authorization
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @param GeneralHelper $loggerInterface
     * @param Session $backendSession
     * @param UserContextInterface $userContext
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        GeneralHelper $loggerInterface,
        Session $backendSession,
        UserContextInterface $userContext,
        \Magento\Framework\App\State $state
    ) {
        $this->state = $state;
        parent::__construct($loggerInterface, $backendSession, $userContext);
    }

    /**
     * Check if the current user is allowed to perform an action on the given order.
     *
     * @param Order $order The order object.
     * @return bool True if the user is allowed, false otherwise.
     */
    protected function isAllowed(Order $order)
    {
        // Get the current area code
        $areaCode = $this->state->getAreaCode();
        // Check if the current area is adminhtml, webapi_rest, or frontend
        if ($areaCode == Area::AREA_ADMINHTML || $areaCode == Area::AREA_WEBAPI_REST || $areaCode == Area::AREA_FRONTEND) {
            return true;
        }
        // Check if the user is not a customer or if the customer ID matches the order customer ID
        else {
            return !($this->_userContext->getUserType() == UserContextInterface::USER_TYPE_CUSTOMER) || $order->getCustomerId() == $this->_userContext->getUserId();
        }
    }
}

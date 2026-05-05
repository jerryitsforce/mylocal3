<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Model\Services\ReorderHelper;
use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Branch8\MarketPlaceParentOrderAdminUi\Model\AdminOrder\Create;

abstract class AbstractCreate extends ParentOrder
{
    /**
     * Retrieve session object
     *
     * @return \Magento\Backend\Model\Session\Quote
     */
    protected function _getSession()
    {
        return $this->_objectManager->get(\Magento\Backend\Model\Session\Quote::class);
    }

    /**
     * @return ReorderHelper|mixed
     */
    protected function _getHelper()
    {
        return $this->_objectManager->get(ReorderHelper::class);
    }

    /**
     * @return Create|mixed
     */
    protected function _getOrderCreateModel()
    {
        return $this->_objectManager->get(Create::class);
    }
}

<?php
namespace Branch8\MarketplaceProduct\Controller\Adminhtml\ManagerProduct;

class Approve extends \Branch8\MarketplaceProduct\Controller\Adminhtml\Product\Approve
{
    const ADMIN_RESOURCE = 'Branch8_MarketplaceProduct::manager_product_approval';

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}

<?php
namespace Branch8\MarketplaceProduct\Controller\Adminhtml\ManagerProduct;

class Disapprove extends \Branch8\MarketplaceProduct\Controller\Adminhtml\Product\Disapprove
{
    const ADMIN_RESOURCE = 'Branch8_MarketplaceProduct::disapprove';

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

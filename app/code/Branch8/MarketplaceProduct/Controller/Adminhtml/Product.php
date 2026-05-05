<?php
namespace Branch8\MarketplaceProduct\Controller\Adminhtml;

use Magento\Backend\App\Action;

/**
 * Webkul Marketplace admin product controller
 */
abstract class Product extends \Magento\Backend\App\Action
{
    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::product');
    }
}

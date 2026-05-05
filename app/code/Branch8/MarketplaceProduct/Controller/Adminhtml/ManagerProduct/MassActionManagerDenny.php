<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\ManagerProduct;

/**
 * Class MassDisapprove used to mass Disapproved.
 */
class MassActionManagerDenny extends \Branch8\MarketplaceProduct\Controller\Adminhtml\Product\MassDisapprove
{
    
    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::manager_product_approval');
    }
}

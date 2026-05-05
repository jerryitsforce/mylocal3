<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\ManagerProduct;


/**
 * Class MassApprove used to mass approved.
 */
class MassActionManagerApprove extends \Branch8\MarketplaceProduct\Controller\Adminhtml\Product\MassApprove
{
    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::manager_product_approval');
    }
}

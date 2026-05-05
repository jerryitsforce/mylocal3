<?php

declare(strict_types=1);

namespace Branch8\Report\Controller\Adminhtml\Product\Logging;

use Magento\Backend\App\Action;

class Grid extends Action
{
    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        $productId = (int)$this->getRequest()->getParam('id');
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()
            ->prepend(__('History of Changes for Product #%1', $productId));
        $this->_view->renderLayout();
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Catalog::products');
    }
}

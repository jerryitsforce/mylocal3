<?php

declare(strict_types=1);

namespace Branch8\Catalog\Controller\Adminhtml\Product\Status;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Catalog\Api\Data\ProductAttributeInterface;

class History extends Action
{
    /**
     * @var BackendSession
     */
    private BackendSession $backendSession;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param BackendSession $backendSession
     */
    public function __construct(
        Context        $context,
        BackendSession $backendSession
    ) {
        parent::__construct($context);
        $this->backendSession = $backendSession;
    }

    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->backendSession->setData('product_attribute_trace', ProductAttributeInterface::CODE_STATUS);
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()
            ->prepend(__('Status Change History of Product #%1', $id));
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

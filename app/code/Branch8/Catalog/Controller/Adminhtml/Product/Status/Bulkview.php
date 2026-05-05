<?php

declare(strict_types=1);

namespace Branch8\Catalog\Controller\Adminhtml\Product\Status;

use Branch8\Catalog\Model\ResourceModel\ProductChangeHistory as HistoryResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class Bulkview extends Action
{
    /**
     * @inheritdoc
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var HistoryResource
     */
    private HistoryResource $historyResource;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param HistoryResource $historyResource
     */
    public function __construct(
        Context         $context,
        Registry        $registry,
        HistoryResource $historyResource
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->historyResource = $historyResource;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        try {
            $history = $this->historyResource->getById($id);
        } catch (LocalizedException $e) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        if (empty($history['updated_in_bulk'])) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $this->registry->register('historyBulkData', (string)$history['updated_in_bulk']);

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Updated in Bulk'));
        $this->_view->renderLayout();
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Catalog::products');
    }
}

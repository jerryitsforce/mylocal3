<?php

declare(strict_types=1);

namespace Branch8\Report\Controller\Adminhtml\Product\Logging;

use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class View extends Action implements HttpGetActionInterface
{
    /**
     * @inheritdoc
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var ProductChangeLogRepositoryInterface
     */
    private ProductChangeLogRepositoryInterface $productChangeLogRepository;

    /**
     * View constructor.
     *
     * @param Context $context
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        Context                             $context,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        parent::__construct($context);
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        try {
            $changeLog = $this->productChangeLogRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Log Entry #%1', $changeLog->getId()));
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

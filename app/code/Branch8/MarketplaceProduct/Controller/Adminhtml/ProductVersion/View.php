<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\ProductVersion;

use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class View extends Action implements HttpGetActionInterface
{
    /**
     * @inheritdoc
     */
    const ADMIN_RESOURCE = 'Branch8_MarketplaceProduct::marketplace_product_version_view';

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * View constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ProductVersionRepositoryInterface $productVersionRepository
     */
    public function __construct(
        Context                           $context,
        Registry                          $coreRegistry,
        ProductVersionRepositoryInterface $productVersionRepository
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->productVersionRepository = $productVersionRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        try {
            $productVersion = $this->productVersionRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $this->coreRegistry->register('current_log_entry', [$productVersion->getData()]);

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Log Entry #%1', $productVersion->getId()));
        $this->_view->renderLayout();
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::marketplace_product_version_view');
    }
}
